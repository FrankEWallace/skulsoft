<?php

namespace App\Services\Utility;

use App\Enums\CustomFieldForm;
use App\Enums\OptionType;
use App\Helpers\CalHelper;
use App\Http\Resources\CustomFieldResource;
use App\Http\Resources\OptionResource;
use App\Models\CustomField;
use App\Models\Option;
use App\Models\Utility\Todo;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TodoService
{
    public function preRequisite(): array
    {
        $statuses = [
            ['label' => trans('utility.todo.completed'), 'value' => 'completed'],
            ['label' => trans('utility.todo.incomplete'), 'value' => 'incomplete'],
        ];

        $todoLists = OptionResource::collection(Option::query()
            ->where('type', OptionType::TODO_LIST->value)
            ->orderBy('meta->position', 'asc')
            ->get()
        );

        $customFields = CustomFieldResource::collection(CustomField::query()
            ->byTeam()
            ->where('form', CustomFieldForm::TODO->value)
            ->get());

        return compact('statuses', 'todoLists', 'customFields');
    }

    public function create(Request $request): Todo
    {
        return Todo::forceCreate($this->formatParams($request));
    }

    private function formatParams(Request $request, ?Todo $todo = null): array
    {
        $dueDate = $request->due_date ?? today()->toDateString();
        $dueTime = $request->due_time ? CalHelper::storeDateTime($dueDate.' '.$request->due_time)?->toTimeString() : null;

        $formatted = [
            'title' => $request->title,
            'description' => clean($request->description),
            'due_date' => $dueDate,
            'due_time' => $dueTime,
        ];

        if (! $todo) {
            $formatted['user_id'] = \Auth::id();
        }

        $meta = $request->meta ?? [];
        $meta['custom_fields'] = $request->custom_fields;

        $formatted['meta'] = $meta;

        return $formatted;
    }

    public function update(Request $request, Todo $todo): void
    {
        $todo->forceFill($this->formatParams($request, $todo))->save();
    }

    public function deletable(Todo $todo, $validate = false): ?bool
    {
        if ($todo->user_id != \Auth::id()) {
            if ($validate) {
                return false;
            }

            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        return true;
    }

    private function findMultiple(Request $request): array
    {
        if ($request->boolean('global')) {
            $listService = new TodoListService;
            $uuids = $listService->getIds($request);
        } else {
            $uuids = is_array($request->uuids) ? $request->uuids : [];
        }

        if (! count($uuids)) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('general.data')])]);
        }

        return $uuids;
    }

    public function deleteMultiple(Request $request): int
    {
        $uuids = $this->findMultiple($request);

        $todos = Todo::whereIn('uuid', $uuids)->get();

        $deletable = [];
        foreach ($todos as $todo) {
            if ($this->deletable($todo, true)) {
                $deletable[] = $todo->uuid;
            }
        }

        if (! count($deletable)) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_delete_any', ['attribute' => trans('utility.todo.todo')])]);
        }

        Todo::whereUserId(\Auth::id())->whereIn('uuid', $deletable)->delete();

        return count($deletable);
    }
}
