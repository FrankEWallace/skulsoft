<?php

namespace App\Services\Reception;

use App\Enums\OptionType;
use App\Enums\Reception\ComplaintStatus;
use App\Http\Resources\OptionResource;
use App\Http\Resources\Student\StudentSummaryResource;
use App\Models\Option;
use App\Models\Reception\Complaint;
use App\Models\Student\Student;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ComplaintService
{
    use FormatCodeNumber;

    private function codeNumber(): array
    {
        $numberPrefix = config('config.reception.complaint_number_prefix');
        $numberSuffix = config('config.reception.complaint_number_suffix');
        $digit = config('config.reception.complaint_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) Complaint::query()
            ->byTeam()
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function preRequisite(Request $request): array
    {
        $students = [];
        if (auth()->user()->is_student_or_guardian) {
            $students = StudentSummaryResource::collection(Student::query()
                ->byPeriod()
                ->summary()
                ->filterForStudentAndGuardian()
                ->get());
        }

        $statuses = ComplaintStatus::getOptions();

        $types = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::COMPLAINT_TYPE->value)
            ->get());

        return compact('types', 'statuses', 'students');
    }

    public function create(Request $request): Complaint
    {
        \DB::beginTransaction();

        $complaint = Complaint::forceCreate($this->formatParams($request));

        $complaint->addMedia($request);

        \DB::commit();

        return $complaint;
    }

    private function formatParams(Request $request, ?Complaint $complaint = null): array
    {
        $formatted = [
            'type_id' => $request->type_id,
            'subject' => $request->subject,
            'date' => $request->date,
            'complainant' => [
                'name' => $request->complainant_name,
                'contact_number' => $request->complainant_contact_number,
                'address' => $request->complainant_address,
            ],
            'description' => $request->description,
            'action' => $request->action,
        ];

        if ($request->student_id) {
            $formatted['model_type'] = 'Student';
            $formatted['model_id'] = $request->student_id;
        }

        if (! $complaint) {
            $codeNumberDetail = $this->codeNumber();

            if (empty($request->code_number) || $request->code_number == Arr::get($codeNumberDetail, 'code_number')) {
                $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
                $formatted['number'] = Arr::get($codeNumberDetail, 'number');
                $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');
            } else {
                $formatted['code_number'] = $request->code_number;
            }

            $formatted['user_id'] = auth()->id();
            $formatted['team_id'] = auth()->user()?->current_team_id;
            $formatted['status'] = ComplaintStatus::SUBMITTED;
        }

        $meta = $complaint?->meta ?? [];
        $meta['is_online'] = $request->boolean('is_online') ?? false;

        $formatted['meta'] = $meta;

        return $formatted;
    }

    private function isEditable(Request $request, Complaint $complaint)
    {
        if (! $complaint->is_editable) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }
    }

    public function update(Request $request, Complaint $complaint): void
    {
        $this->isEditable($request, $complaint);

        \DB::beginTransaction();

        $complaint->forceFill($this->formatParams($request, $complaint))->save();

        $complaint->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Request $request, Complaint $complaint): void
    {
        $this->isEditable($request, $complaint);
    }
}
