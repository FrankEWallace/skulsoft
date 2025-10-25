<?php

namespace App\Services\Student;

use App\Contracts\ListGenerator;
use App\Enums\FamilyRelation;
use App\Http\Resources\Student\StudentListResource;
use App\Models\Guardian;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SiblingListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('student.admission.props.code_number'),
                'print_label' => 'code_number',
                'print_sub_label' => 'joining_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'name',
                'label' => trans('student.props.name'),
                'print_label' => 'name',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'course_name + batch_name',
                // 'print_sub_label' => 'batch_name',
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
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request, Student $student): Builder
    {
        $guardianContactIds = Guardian::query()
            ->select('contact_id')
            ->where('primary_contact_id', '=', $student->contact_id)
            ->whereIn('relation', [
                FamilyRelation::FATHER->value,
                FamilyRelation::MOTHER->value,
            ])
            ->pluck('contact_id')
            ->all();

        $studentUuids = Student::query()
            ->select('students.uuid')
            ->join('contacts', function ($join) use ($guardianContactIds) {
                $join->on('students.contact_id', '=', 'contacts.id')
                    ->join('guardians', function ($join) use ($guardianContactIds) {
                        $join->on('primary_contact_id', '=', 'contacts.id')->whereIn('guardians.contact_id', $guardianContactIds);
                    });
            })
            ->where('students.uuid', '!=', $student->uuid)
            ->distinct()
            ->get()
            ->pluck('uuid')
            ->all();

        return Student::query()
            ->select('students.*', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'), 'contacts.first_name', 'contacts.last_name', 'contacts.contact_number', 'contacts.father_name', 'contacts.mother_name', 'contacts.email', 'contacts.birth_date', 'contacts.gender', 'admissions.code_number', 'admissions.joining_date', 'batches.uuid as batch_uuid', 'batches.name as batch_name', 'courses.uuid as course_uuid', 'courses.name as course_name')
            ->byPeriod()
            ->whereIn('students.uuid', $studentUuids)
            ->join('contacts', function ($join) {
                $join->on('students.contact_id', '=', 'contacts.id')
                    ->leftJoin('guardians', function ($join) {
                        $join->on('primary_contact_id', '=', 'contacts.id')->where('position', '=', 1);
                    });
            })
            ->join('admissions', function ($join) {
                $join->on('students.admission_id', '=', 'admissions.id');
            })
            ->join('batches', function ($join) {
                $join->on('students.batch_id', '=', 'batches.id')
                    ->leftJoin('courses', function ($join) {
                        $join->on('batches.course_id', '=', 'courses.id');
                    });
            })
            ->with(['guardian:id,contact_id,primary_contact_id,relation', 'guardian.contact:id,first_name,middle_name,third_name,last_name,contact_number'])
            ->filter([]);
    }

    public function paginate(Request $request, Student $student): AnonymousResourceCollection
    {
        return StudentListResource::collection($this->filter($request, $student)
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
