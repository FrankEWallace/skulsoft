<?php

namespace App\Http\Controllers\Contact;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\UserRequest;
use App\Http\Requests\Contact\UserUpdateRequest;
use App\Models\Contact;
use App\Services\Contact\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct()
    {
        // $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function confirm(Request $request, string $contact, UserService $service)
    {
        $contact = Contact::findByUuidOrFail($contact);

        $this->authorize('view', $contact);

        return $service->confirm($request, $contact);
    }

    public function index(string $contact, UserService $service)
    {
        $contact = Contact::findByUuidOrFail($contact);

        $this->authorize('view', $contact);

        return response()->ok($service->fetch($contact));
    }

    public function create(UserRequest $request, string $contact, UserService $service)
    {
        $contact = Contact::findByUuidOrFail($contact);

        $this->authorize('update', $contact);

        $service->create($request, $contact);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('contact.login.login')]),
        ]);
    }

    public function update(UserUpdateRequest $request, string $contact, UserService $service)
    {
        $contact = Contact::findByUuidOrFail($contact);

        $this->authorize('update', $contact);

        $contact->load('user');

        $this->denyAdmin($contact?->user);

        $service->update($request, $contact);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('contact.login.login')]),
        ]);
    }
}
