<?php

namespace App\Http\Controllers\Utility;

use App\Http\Controllers\Controller;
use App\Models\Utility\Todo;
use App\Services\Utility\TodoActionService;
use Illuminate\Http\Request;

class TodoActionController extends Controller
{
    public function status(Request $request, Todo $todo, TodoActionService $service)
    {
        $this->authorize('manage', $todo);

        $service->status($request, $todo);

        return response()->success([
            // 'message' => trans('global.updated', ['attribute' => trans('utility.todo.todo')]),
        ]);
    }

    public function archive(Request $request, Todo $todo, TodoActionService $service)
    {
        $this->authorize('manage', $todo);

        $service->archive($request, $todo);

        return response()->success([
            'message' => trans('global.archived', ['attribute' => trans('utility.todo.todo')]),
        ]);
    }

    public function unarchive(Request $request, Todo $todo, TodoActionService $service)
    {
        $this->authorize('manage', $todo);

        $service->unarchive($request, $todo);

        return response()->success([
            'message' => trans('global.unarchived', ['attribute' => trans('utility.todo.todo')]),
        ]);
    }

    public function moveList(Request $request, TodoActionService $service)
    {
        $todo = Todo::findByUuidOrFail($request->uuid);

        $this->authorize('manage', $todo);

        $service->moveList($request, $todo);

        return response()->ok([]);
    }

    public function reorder(Request $request, TodoActionService $service)
    {
        $service->reorder($request);

        return response()->ok([]);
    }
}
