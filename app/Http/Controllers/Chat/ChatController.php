<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Resources\Chat\ChatResource;
use App\Models\Chat\Chat;
use App\Models\Chat\Participant;
use App\Models\Employee\Employee;
use App\Models\Student\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $name = $request->query('name');

        $chats = Chat::query()
            ->whereHas('participants', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->with(['participants.user', 'latestMessage'])
            ->withCount(['messages as unread_count' => function ($query) {
                $query->where('user_id', '!=', auth()->id())
                    ->whereNull('read_at');
            }])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('messages', function ($query) use ($search) {
                        $query->where('content', 'like', "%{$search}%");
                    })->orWhere(function ($q) use ($search) {
                        $q->whereIsGroupChat(true)
                            ->where('name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('participants.user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->where('id', '!=', auth()->id());
                        });
                });
            })
            ->orderBy('last_messaged_at', 'desc')
            ->cursorPaginate(10);

        $userIds = [];
        foreach ($chats->where('is_group_chat', false) as $chat) {
            foreach ($chat->participants as $participant) {
                if ($participant->user_id !== auth()->id()) {
                    $userIds[] = $participant->user_id;
                }
            }
        }

        $data = $this->getRecipientsDetail($userIds);

        $students = Arr::get($data, 'students', collect([]));
        $employees = Arr::get($data, 'employees', collect([]));

        $chats->map(function ($chat) use ($students, $employees) {
            $chat->recipient = $this->getRecipient($chat, $students, $employees);

            return $chat;
        });

        return ChatResource::collection($chats);
    }

    public function show(Chat $chat)
    {
        $chat->messages()
            ->where('user_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $chat = $this->getChatWithRecipient($chat);

        return ChatResource::make($chat);
    }

    private function getExistingChat(array $participantIds = [])
    {
        $chats = Participant::query()
            ->select('chat_id')
            ->whereIn('user_id', $participantIds)
            ->groupBy('chat_id')
            ->havingRaw('COUNT(*) = ?', [count($participantIds)])
            ->get();

        foreach ($chats as $chat) {
            $existingChatParticipants = Participant::query()
                ->where('chat_id', $chat->chat_id)
                ->pluck('user_id')
                ->toArray();

            if (! array_diff($existingChatParticipants, $participantIds)) {
                return $chat->chat_id;
            }
        }

        return null;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'is_group_chat' => 'boolean',
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:users,uuid',
        ]);

        $participantUuids = array_merge($validated['participants'], [Auth::user()->uuid]);

        $participantIds = User::whereIn('uuid', $participantUuids)->pluck('id')->all();

        if (in_array(auth()->user()->uuid, $validated['participants'])) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        if (auth()->user()->hasAnyRole(['student', 'guardian'])) {
            $employees = Employee::query()
                ->join('contacts', 'contacts.id', '=', 'employees.contact_id')
                ->byTeam()
                ->join('users', 'users.id', '=', 'contacts.user_id')
                ->whereIn('users.id', $participantIds)
                ->get();

            if ($employees->count() < count($participantIds) - 1) {
                throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
            }
        }

        $existingChat = $this->getExistingChat($participantIds);

        $name = $validated['name'].' - '.now()->timestamp;

        $chat = $existingChat ? Chat::find($existingChat) : Chat::firstOrCreate([
            'name' => $name,
            'is_group_chat' => (int) Arr::get($validated, 'is_group_chat', false),
        ]);

        if (! $existingChat) {
            $chat->last_messaged_at = now()->toDateTimeString();
            $chat->save();
        }

        if (! $existingChat) {
            $participants = array_map(function ($participantId) {
                return ['user_id' => $participantId];
            }, $participantIds);

            $chat->participants()->createMany($participants);
        }

        $chat = $this->getChatWithRecipient($chat);

        return response()->json(ChatResource::make($chat->load('participants.user')), 201);
    }

    public function markAsRead(Chat $chat)
    {
        $chat->messages()
            ->where('user_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->ok([]);
    }

    public function destroy(Chat $chat)
    {
        $chat->delete();

        return response()->ok([]);
    }

    private function getRecipientsDetail(array $participantIds = [])
    {
        $date = today()->toDateString();

        $students = Student::query()
            ->select('students.uuid', 'contacts.user_id', 'admissions.code_number', 'courses.name as course_name', 'batches.name as batch_name')
            ->join('contacts', 'contacts.id', '=', 'students.contact_id')
            ->join('admissions', 'admissions.id', '=', 'students.admission_id')
            ->join('batches', 'batches.id', '=', 'admissions.batch_id')
            ->join('courses', 'courses.id', '=', 'batches.course_id')
            ->whereIn('contacts.user_id', $participantIds)
            ->get();

        $employees = Employee::query()
            ->select('employees.uuid', 'contacts.user_id', 'employees.code_number', 'designations.name as designation_name')
            ->byTeam()
            ->join('contacts', 'contacts.id', '=', 'employees.contact_id')
            ->leftJoin('employee_records', function ($join) use ($date) {
                $join->on('employees.id', '=', 'employee_records.employee_id')
                    ->on('employee_records.start_date', '=', \DB::raw("(select employee_records.start_date from employee_records where employees.id = employee_records.employee_id and employee_records.start_date <= '".$date."' order by employee_records.start_date desc limit 1)"))
                    ->join('designations', 'employee_records.designation_id', '=', 'designations.id');
            })
            ->whereIn('contacts.user_id', $participantIds)
            ->get();

        return compact('students', 'employees');
    }

    private function getChatWithRecipient(Chat $chat): Chat
    {
        $participantIds = $chat->participants->pluck('user_id')->all();

        $data = $this->getRecipientsDetail($participantIds);

        $students = Arr::get($data, 'students', collect([]));
        $employees = Arr::get($data, 'employees', collect([]));

        $chat->recipient = $this->getRecipient($chat, $students, $employees);

        return $chat;
    }

    private function getRecipient(Chat $chat, Collection $students, Collection $employees)
    {
        $recipient = null;

        foreach ($chat->participants as $participant) {
            if ($participant->user_id !== auth()->id()) {
                $recipient = $participant->user_id;
            }
        }

        if ($recipient && $student = $students->firstWhere('user_id', $recipient)) {
            $recipient = [
                'type' => 'student',
                'uuid' => $student->uuid,
                'detail' => $student->admission_number.' - '.$student->course_name.' '.$student->batch_name,
            ];
        } elseif ($recipient && $employee = $employees->firstWhere('user_id', $recipient)) {
            $recipient = [
                'type' => 'employee',
                'uuid' => $employee->uuid,
                'detail' => $employee->code_number.' - '.$employee->designation_name,
            ];
        }

        return $recipient;
    }
}
