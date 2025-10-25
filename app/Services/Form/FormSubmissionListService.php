<?php

namespace App\Services\Form;

use App\Contracts\ListGenerator;
use App\Enums\CustomFieldType;
use App\Http\Resources\Form\FormSubmissionResource;
use App\Models\Form\Form;
use App\Models\Form\Submission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class FormSubmissionListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'submitted_at'];

    protected $defaultSort = 'submitted_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(Form $form): array
    {
        $headers = [
            [
                'key' => 'type',
                'label' => trans('general.type'),
                'print_label' => 'type.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'name',
                'label' => trans('contact.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'code_number',
                'print_additional_label' => 'detail',
                'sortable' => false,
                'visibility' => true,
            ],
        ];

        $i = 1;
        foreach ($form->fields as $field) {
            if (in_array($field->type, [CustomFieldType::PARAGRAPH])) {
                continue;
            }

            if (request()->query('export')) {
                if (in_array($field->type, [CustomFieldType::CAMERA_IMAGE])) {
                    continue;
                }
            }

            $headers[] = [
                'key' => $field->name,
                'label' => trans('form.props.question').' '.$i,
                'print_label' => 'responses.'.$field->uuid.'.value',
                'sortable' => false,
                'visibility' => true,
            ];

            $i++;
        }

        array_push($headers, [
            'key' => 'submittedAt',
            'label' => trans('form.props.submitted_at'),
            'print_label' => 'submitted_at.formatted',
            'sortable' => true,
            'visibility' => true,
        ]);

        // [
        //     'key' => 'createdAt',
        //     'label' => trans('general.created_at'),
        //     'print_label' => 'created_at.formatted',
        //     'sortable' => true,
        //     'visibility' => true,
        // ],

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request, Form $form): Builder
    {
        $type = $request->query('type');
        $admissionNumber = $request->query('admission_number');
        $employeeCode = $request->query('employee_code');
        $batches = Str::toArray($request->query('batches'));

        if ($type == 'student') {
            $employeeCode = null;
        } else {
            $admissionNumber = null;
        }

        return Submission::query()
            ->with([
                'model.contact',
                'records.field',
            ])
            ->select(
                'form_submissions.*',
                'admissions.code_number as admission_number',
                'batches.name as batch_name',
                'courses.name as course_name',
                'courses.term as course_term',
                'employees.code_number as employee_code'
            )
            ->whereFormId($form->id)
            ->leftJoin('students', function ($join) {
                $join->on('form_submissions.model_id', '=', 'students.id')
                    ->where('form_submissions.model_type', '=', 'Student');
            })
            ->leftJoin('contacts as student_contacts', 'students.contact_id', '=', 'student_contacts.id')
            ->leftJoin('admissions', 'students.admission_id', '=', 'admissions.id')
            ->leftJoin('batches', 'students.batch_id', '=', 'batches.id')
            ->leftJoin('courses', 'batches.course_id', '=', 'courses.id')
            ->leftJoin('employees', function ($join) {
                $join->on('form_submissions.model_id', '=', 'employees.id')
                    ->where('form_submissions.model_type', '=', 'Employee');
            })
            ->leftJoin('contacts as employee_contacts', 'employees.contact_id', '=', 'employee_contacts.id')
            ->when($batches, function ($q, $batches) {
                $q->whereIn('batches.uuid', $batches);
            })
            ->when($type, function ($q, $type) {
                $q->where('form_submissions.model_type', $type);
            })
            ->when($admissionNumber, function ($q, $admissionNumber) {
                $q->where('admissions.code_number', $admissionNumber);
            })
            ->when($employeeCode, function ($q, $employeeCode) {
                $q->where('employees.code_number', $employeeCode);
            })
            ->filter([
                'App\QueryFilters\DateBetween:start_date,end_date,submitted_at,datetime',
            ]);
    }

    public function paginate(Request $request, Form $form): AnonymousResourceCollection
    {
        return FormSubmissionResource::collection($this->filter($request, $form)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders($form),
                'meta' => [
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                ],
            ]);
    }

    public function list(Request $request, Form $form): AnonymousResourceCollection
    {
        return $this->paginate($request, $form);
    }
}
