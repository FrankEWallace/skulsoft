<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\AccountRequest;
use App\Http\Resources\Student\AccountResource;
use App\Models\Student\Student;
use App\Services\Student\AccountListService;
use App\Services\Student\AccountService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $student, AccountService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, string $student, AccountListService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return $service->paginate($request, $student);
    }

    public function store(AccountRequest $request, string $student, AccountService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('manageRecord', $student);

        $account = $service->create($request, $student);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('student.account.account')]),
            'account' => AccountResource::make($account),
        ]);
    }

    public function show(string $student, string $account, AccountService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $account = $service->findByUuidOrFail($student, $account);

        $account->load('media');

        return AccountResource::make($account);
    }

    public function update(AccountRequest $request, string $student, string $account, AccountService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('manageRecord', $student);

        $account = $service->findByUuidOrFail($student, $account);

        $service->update($request, $student, $account);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.account.account')]),
        ]);
    }

    public function destroy(string $student, string $account, AccountService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('manageRecord', $student);

        $account = $service->findByUuidOrFail($student, $account);

        $service->deletable($student, $account);

        $account->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('student.account.account')]),
        ]);
    }

    public function downloadMedia(string $student, string $account, string $uuid, AccountService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $account = $service->findByUuidOrFail($student, $account);

        return $account->downloadMedia($uuid);
    }
}
