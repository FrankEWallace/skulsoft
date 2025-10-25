<?php

namespace App\Http\Controllers\Utility;

use App\Http\Controllers\Controller;
use App\Http\Requests\Utility\TodoRequest;
use App\Http\Resources\Utility\TodoResource;
use App\Models\Utility\Todo;
use App\Services\Utility\TodoListService;
use App\Services\Utility\TodoService;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    public function __construct()
    {
        $this->middleware('feature.available:feature.enable_todo');
    }

    public function preRequisite(TodoService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, TodoListService $service)
    {
        return $service->paginate($request);
    }

    public function store(TodoRequest $request, TodoService $service)
    {
        $todo = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('utility.todo.todo')]),
            'todo' => TodoResource::make($todo),
        ]);
    }

    public function show(Request $request, Todo $todo): TodoResource
    {
        $this->authorize('manage', $todo);

        $request->merge([
            'has_custom_fields' => true,
        ]);

        return TodoResource::make($todo);
    }

    public function update(TodoRequest $request, Todo $todo, TodoService $service)
    {
        $this->authorize('manage', $todo);

        $service->update($request, $todo);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('utility.todo.todo')]),
        ]);
    }

    public function destroy(Todo $todo, TodoService $service)
    {
        $this->authorize('manage', $todo);

        $service->deletable($todo);

        $todo->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('utility.todo.todo')]),
        ]);
    }

    public function destroyMultiple(Request $request, TodoService $service)
    {
        $this->authorize('delete');

        $count = $service->deleteMultiple($request);

        return response()->success([
            'message' => trans('global.multiple_deleted', ['count' => $count, 'attribute' => trans('utility.todo.todo')]),
        ]);
    }
}
