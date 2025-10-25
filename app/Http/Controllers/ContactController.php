<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Http\Requests\ContactUpdateRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Services\ContactListService;
use App\Services\ContactService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function preRequisite(ContactService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, ContactListService $service)
    {
        $this->authorize('viewAny', Contact::class);

        return $service->paginate($request);
    }

    public function store(ContactRequest $request, ContactService $service)
    {
        $this->authorize('create', Contact::class);

        $contact = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('contact.contact')]),
            'contact' => ContactResource::make($contact),
        ]);
    }

    public function show(string $contact, ContactService $service): ContactResource
    {
        $contact = Contact::findByUuidOrFail($contact);

        $this->authorize('view', $contact);

        $contact->load('user.roles', 'religion', 'caste', 'category');

        $type = $service->getContactType($contact);
        $contact->type = $type;

        return ContactResource::make($contact);
    }

    public function update(ContactUpdateRequest $request, string $contact, ContactService $service)
    {
        $contact = Contact::findByUuidOrFail($contact);

        $this->authorize('update', $contact);

        $service->update($request, $contact);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('contact.contact')]),
        ]);
    }

    public function destroy(string $contact, ContactService $service)
    {
        $contact = Contact::findByUuidOrFail($contact);

        $this->authorize('delete', $contact);

        $service->deletable($contact);

        $contact->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('contact.contact')]),
        ]);
    }
}
