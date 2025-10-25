<?php

namespace App\Services\Student;

use App\Actions\Student\FetchBatchWiseStudent;
use App\Enums\OptionType;
use App\Enums\ServiceRequestType;
use App\Enums\ServiceType;
use App\Http\Resources\OptionResource;
use App\Http\Resources\Student\ServiceAllocationResource;
use App\Http\Resources\Transport\StoppageResource;
use App\Models\Option;
use App\Models\Student\ServiceAllocation;
use App\Models\Student\Student;
use App\Models\Transport\Stoppage;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServiceAllocationService
{
    public function preRequisite(Request $request)
    {
        $transportStoppages = StoppageResource::collection(Stoppage::query()
            ->byPeriod()
            ->get());

        $groups = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::STUDENT_GROUP->value)
            ->get());

        $types = ServiceType::getOptions();

        $availableServices = explode(',', config('config.student.services'));

        $types = collect($types)->filter(function ($type) use ($availableServices) {
            return in_array(Arr::get($type, 'value'), $availableServices);
        })->values()->toArray();

        $requestTypes = ServiceRequestType::getOptions();

        return compact('transportStoppages', 'groups', 'types', 'requestTypes');
    }

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
                'label' => trans('contact.props.name'),
                'print_label' => 'name',
                'print_label' => 'gender.label',
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
            // [
            //     'key' => 'installmentCount',
            //     'label' => trans('finance.fee_structure.installment_count'),
            //     'print_label' => 'fees_count',
            //     'print_sub_label' => 'fee_concession_type.name',
            //     'sortable' => false,
            //     'visibility' => true,
            // ],
            [
                'key' => 'contactNumber',
                'label' => trans('contact.props.contact_number'),
                'print_label' => 'contact_number',
                'print_sub_label' => 'email',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'parent',
                'label' => trans('student.props.parent'),
                'print_label' => 'father_name',
                'print_sub_label' => 'mother_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'address',
                'label' => trans('contact.props.address.address'),
                'print_label' => 'short_address',
                'sortable' => false,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            array_unshift($headers, ['key' => 'selectAll', 'sortable' => false]);
        }

        return $headers;
    }

    public function fetch(Request $request)
    {
        $request->validate([
            'batch' => 'required',
            'type' => 'required',
        ]);

        $request->merge([
            'paginate' => true,
        ]);
        $students = (new FetchBatchWiseStudent)->execute($request->all());

        $serviceAllocations = ServiceAllocation::query()
            ->with('transportStoppage')
            ->where('model_type', 'Student')
            ->whereIn('model_id', Arr::pluck($students, 'id'))
            ->where('type', $request->type)
            ->get();

        $request->merge([
            'service_allocations' => $serviceAllocations,
        ]);

        return ServiceAllocationResource::collection($students)
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'allowed_sorts' => ['name'],
                    'default_sort' => 'name',
                    'default_order' => 'asc',
                ],
            ]);
    }

    public function allocate(Request $request)
    {
        $selectAll = $request->boolean('select_all');

        if ($selectAll) {
            $students = (new FetchBatchWiseStudent)->execute($request->all(), true);
        } else {
            $students = (new FetchBatchWiseStudent)->execute($request->all(), true);

            if (array_diff($request->students, Arr::pluck($students, 'uuid'))) {
                throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
            }
        }

        $serviceAllocationBatch = Str::random(10);

        \DB::beginTransaction();

        foreach ($students as $student) {
            $student = Student::whereUuid(Arr::get($student, 'uuid'))->first();

            if ($request->request_type == ServiceRequestType::OPT_IN->value) {
                $serviceAllocation = ServiceAllocation::query()
                    ->firstOrCreate([
                        'model_type' => 'Student',
                        'model_id' => $student->id,
                        'type' => $request->type,
                    ]);

                $serviceAllocation->update([
                    'transport_stoppage_id' => $request->transport_stoppage_id,
                ]);
            } else {
                ServiceAllocation::query()
                    ->where('model_type', 'Student')
                    ->where('model_id', $student->id)
                    ->where('type', $request->type)
                    ->delete();
            }
        }

        \DB::commit();
    }

    public function remove(Request $request)
    {
        $selectAll = $request->boolean('select_all');

        if ($selectAll) {
            $students = (new FetchBatchWiseStudent)->execute($request->all(), true);
        } else {
            $students = (new FetchBatchWiseStudent)->execute($request->all(), true);

            if (array_diff($request->students, Arr::pluck($students, 'uuid'))) {
                throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
            }
        }

        $students = Student::query()
            ->whereNotNull('fee_structure_id')
            ->whereIn('uuid', $request->students)
            ->get();

        if (! $students->count()) {
            throw ValidationException::withMessages(['message' => trans('student.fee.no_allocation_found')]);
        }

        \DB::beginTransaction();

        $removeCount = 0;
        foreach ($students as $student) {
            $student->save();
            $removeCount++;
        }

        \DB::commit();

        if (! $removeCount) {
            throw ValidationException::withMessages(['message' => trans('student.fee.could_not_remove_paid_allocation')]);
        }
    }
}
