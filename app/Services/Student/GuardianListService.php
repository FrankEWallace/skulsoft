<?php

namespace App\Services\Student;

use App\Contracts\ListGenerator;
use App\Http\Resources\GuardianResource;
use App\Models\Guardian;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GuardianListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name'];

    protected $defaultSort = 'created_at';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('guardian.props.name'),
                'print_label' => 'contact.name',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'relation',
                'label' => trans('contact.props.relation'),
                'print_label' => 'relation.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'contactNumber',
                'label' => trans('contact.props.contact_number'),
                'print_label' => 'contact.contact_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'createdAt',
                'label' => trans('general.created_at'),
                'print_label' => 'created_at.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request, Student $student): Builder
    {
        $firstName = $request->query('first_name');
        $lastName = $request->query('last_name');

        return Guardian::query()
            ->with('contact.user')
            ->wherePrimaryContactId($student->contact_id)
            ->when($firstName, function ($q, $firstName) {
                $q->whereHas('contact', function ($q) use ($firstName) {
                    $q->where('first_name', 'like', "%{$firstName}%");
                });
            })
            ->when($lastName, function ($q, $lastName) {
                $q->whereHas('contact', function ($q) use ($lastName) {
                    $q->where('last_name', 'like', "%{$lastName}%");
                });
            })
            ->filter([
                //
            ]);
    }

    public function paginate(Request $request, Student $student): AnonymousResourceCollection
    {
        return GuardianResource::collection($this->filter($request, $student)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                ],
            ]);
    }

    public function list(Request $request, Student $student): AnonymousResourceCollection
    {
        return $this->paginate($request, $student);
    }
}
