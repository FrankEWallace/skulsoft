<?php

namespace App\Services\Utility;

use App\Enums\OptionType;
use App\Models\Option;
use App\Models\Utility\Todo;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TodoActionService
{
    public function status(Request $request, Todo $todo): void
    {
        $todo->completed_at = $todo->completed_at->value ? null : now()->toDateTimeString();
        $todo->save();
    }

    public function archive(Request $request, Todo $todo): void
    {
        if ($todo->archived_at->value) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $todo->archived_at = now()->toDateTimeString();
        $todo->save();
    }

    public function unarchive(Request $request, Todo $todo): void
    {
        if (empty($todo->archived_at)) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $todo->archived_at = null;
        $todo->save();
    }

    public function moveList(Request $request, Todo $todo): void
    {
        $todoList = Option::query()
            ->whereType(OptionType::TODO_LIST->value)
            ->whereUuid($request->list_uuid)
            ->first();

        if (! $todoList) {
            throw ValidationException::withMessages(['message' => trans('utility.todo.list.could_not_perform_if_empty_list')]);
        }

        $todo->list_id = $todoList?->id;
        $todo->save();

        foreach ($request->item_uuids as $order => $uuid) {
            Todo::query()
                ->whereUuid($uuid)
                ->update(['position' => $order]);
        }
    }

    public function reorder(Request $request): void
    {
        $request->validate(['uuids' => 'array|min:1']);

        foreach ($request->uuids as $order => $uuid) {
            Todo::query()
                ->whereUuid($uuid)
                ->update(['position' => $order]);
        }
    }
}
