<?php

namespace App\Services;

use App\Contracts\ListGenerator;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ContactListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name', 'birth_date'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('contact.props.name'),
                'print_label' => 'name',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'type',
                'label' => trans('contact.props.type'),
                'print_label' => 'source.label',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'gender',
                'label' => trans('contact.props.gender'),
                'print_label' => 'gender.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'birthDate',
                'label' => trans('contact.props.birth_date'),
                'print_label' => 'birth_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'contactNumber',
                'label' => trans('contact.props.contact_number'),
                'print_label' => 'contact_number',
                'print_sub_label' => 'email',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'fatherName',
                'label' => trans('contact.props.father_name'),
                'print_label' => 'father_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'motherName',
                'label' => trans('contact.props.mother_name'),
                'print_label' => 'mother_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'bloodGroup',
                'label' => trans('contact.props.blood_group'),
                'print_label' => 'blood_group.label',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'maritalStatus',
                'label' => trans('contact.props.marital_status'),
                'print_label' => 'marital_status.label',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'religion',
                'label' => trans('contact.religion.religion'),
                'print_label' => 'religion.name',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'category',
                'label' => trans('contact.category.category'),
                'print_label' => 'category.name',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'caste',
                'label' => trans('contact.caste.caste'),
                'print_label' => 'caste.name',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'address',
                'label' => trans('contact.props.address.address'),
                'print_label' => 'address',
                'sortable' => false,
                'visibility' => false,
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

    public function filter(Request $request): Builder
    {
        $bloodGroups = Str::toArray($request->query('blood_groups'));
        $maritalStatuses = Str::toArray($request->query('marital_statuses'));
        $religions = Str::toArray($request->query('religions'));
        $categories = Str::toArray($request->query('categories'));
        $castes = Str::toArray($request->query('castes'));

        return Contact::query()
            ->with('religion', 'category', 'caste')
            ->select('*',
                (app()->environment('testing') ?
                'contacts.first_name as name' :
                \DB::raw('concat_ws(" ",contacts.first_name,contacts.middle_name,contacts.third_name,contacts.last_name) as name')))
            ->where('contacts.team_id', auth()->user()->current_team_id)
            ->when($bloodGroups, function ($q, $bloodGroups) {
                return $q->whereIn('blood_group', $bloodGroups);
            })
            ->when($request->query('address'), function ($q, $address) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.present.address_line1')) LIKE ?", ['%'.$address.'%'])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.present.address_line2')) LIKE ?", ['%'.$address.'%'])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.present.city')) LIKE ?", ['%'.$address.'%'])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.present.state')) LIKE ?", ['%'.$address.'%'])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.present.country')) LIKE ?", ['%'.$address.'%'])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.present.zipcode')) LIKE ?", ['%'.$address.'%']);
            })
            ->when($request->query('source'), function ($q, $source) {
                return $q->where('meta->source', 'like', '%'.$source.'%');
            })
            ->when($maritalStatuses, function ($q, $maritalStatuses) {
                return $q->whereIn('marital_status', $maritalStatuses);
            })
            ->when($religions, function ($q, $religions) {
                $q->whereHas('religion', function ($q) use ($religions) {
                    $q->whereIn('uuid', $religions);
                });
            })
            ->when($categories, function ($q, $categories) {
                $q->whereHas('category', function ($q) use ($categories) {
                    $q->whereIn('uuid', $categories);
                });
            })
            ->when($castes, function ($q, $castes) {
                $q->whereHas('caste', function ($q) use ($castes) {
                    $q->whereIn('uuid', $castes);
                });
            })
            ->when($request->query('father_name'), function ($q, $fatherName) {
                return $q->where('father_name', 'like', '%'.$fatherName.'%');
            })
            ->filter([
                'App\QueryFilters\LikeMatch:first_name',
                'App\QueryFilters\LikeMatch:last_name',
                'App\QueryFilters\LikeMatch:email',
                'App\QueryFilters\ExactMatch:gender',
                'App\QueryFilters\DateBetween:birth_start_date,birth_end_date,birth_date',
                'App\QueryFilters\DateBetween:start_date,end_date,created_at,datetime',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return ContactResource::collection($this->filter($request)
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

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
