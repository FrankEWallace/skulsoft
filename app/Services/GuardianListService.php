<?php

namespace App\Services;

use App\Contracts\ListGenerator;
use App\Http\Resources\GuardianListResource;
use App\Models\Guardian;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GuardianListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name', 'birth_date', 'gender', 'relation'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('guardian.props.name'),
                'print_label' => 'name',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'gender',
                'label' => trans('contact.props.gender'),
                'print_label' => 'gender.label',
                'sortable' => true,
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
                'key' => 'relation',
                'label' => trans('contact.props.relation'),
                'print_label' => 'relation.label',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'studentName',
                'label' => trans('student.props.name'),
                'print_label' => 'student_name',
                'print_sub_label' => 'student.admission.code_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'student.batch.course.name',
                'print_sub_label' => 'student.batch.name',
                'sortable' => false,
                'visibility' => true,
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
        return Guardian::query()
            ->select('guardians.*', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", contacts.first_name, contacts.middle_name, contacts.third_name, contacts.last_name), "[[:space:]]+", " ") as name'), 'contacts.first_name', 'contacts.last_name', 'contacts.contact_number', 'contacts.email', 'contacts.birth_date', 'contacts.gender', 'contacts.address', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", primary.first_name, primary.middle_name, primary.third_name, primary.last_name), "[[:space:]]+", " ") as student_name'), 'primary.contact_number as student_contact_number')
            ->join('contacts', function ($join) {
                $join->on('guardians.contact_id', '=', 'contacts.id')
                    ->where('contacts.team_id', auth()->user()?->current_team_id);
            })
            ->join('contacts as primary', function ($join) {
                $join->on('guardians.primary_contact_id', '=', 'primary.id');
            })->addSelect(['student_id' => Student::select('id')
            ->whereColumn('contact_id', 'guardians.primary_contact_id')
            ->where('start_date', '<=', today()->toDateString())
            ->orderBy('start_date', 'desc')
            ->limit(1),
            ])->with(['student:id,uuid,batch_id,admission_id', 'student.batch:id,course_id,name', 'student.batch.course:id,name', 'student.admission:id,code_number'])
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", contacts.first_name, contacts.middle_name, contacts.third_name, contacts.last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->when($request->query('address'), function ($q, $address) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(contacts.address, '$.present.address_line1')) LIKE ?", ['%'.$address.'%'])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(contacts.address, '$.present.address_line2')) LIKE ?", ['%'.$address.'%'])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(contacts.address, '$.present.city')) LIKE ?", ['%'.$address.'%'])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(contacts.address, '$.present.state')) LIKE ?", ['%'.$address.'%'])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(contacts.address, '$.present.country')) LIKE ?", ['%'.$address.'%'])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(contacts.address, '$.present.zipcode')) LIKE ?", ['%'.$address.'%']);
            })
            ->filter([
                'App\QueryFilters\LikeMatch:first_name,contacts.first_name',
                'App\QueryFilters\LikeMatch:last_name,contacts.last_name',
                'App\QueryFilters\LikeMatch:email,contacts.email',
                'App\QueryFilters\LikeMatch:contact_number,contacts.contact_number',
                'App\QueryFilters\ExactMatch:gender,contacts.gender',
                'App\QueryFilters\DateBetween:birth_start_date,birth_end_date,contacts.birth_date',
                'App\QueryFilters\DateBetween:start_date,end_date,guardians.created_at,datetime',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return GuardianListResource::collection($this->filter($request)
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
