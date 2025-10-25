<?php

namespace App\Services\Student;

use App\Contracts\ListGenerator;
use App\Enums\Student\AdmissionType;
use App\Enums\Student\RegistrationStatus;
use App\Http\Resources\Student\RegistrationResource;
use App\Models\Student\Registration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class RegistrationListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'date'];

    protected $defaultSort = 'date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('student.registration.props.code_number'),
                'print_label' => 'code_number',
                'print_sub_label' => 'period.name',
                'print_additional_label' => 'admission_date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'name',
                'label' => trans('student.props.name'),
                'print_label' => 'contact.name',
                'print_sub_label' => 'contact.contact_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'guardianName',
                'label' => trans('guardian.props.name'),
                'print_label' => 'contact.guardian.contact.name',
                'print_sub_label' => 'contact.guardian.contact.contact_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'birthDate',
                'label' => trans('contact.props.birth_date'),
                'print_label' => 'contact.birth_date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'course.name_with_term',
                'print_sub_label' => 'batch_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'enrollmentType',
                'label' => trans('student.enrollment_type.enrollment_type'),
                'print_label' => 'enrollment_type.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'stage',
                'label' => trans('student.registration_stage.registration_stage'),
                'print_label' => 'stage.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'status',
                'label' => trans('student.registration.props.status'),
                'print_label' => 'status.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'date',
                'label' => trans('student.registration.props.date'),
                'print_label' => 'date.formatted',
                'print_sub_label' => 'application_number',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'employee',
                'label' => trans('student.registration.props.assigned_to'),
                'print_label' => 'employee.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'createdAt',
                'label' => trans('general.created_at'),
                'print_label' => 'created_at.formatted',
                'sortable' => true,
                'visibility' => false,
            ],
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
            array_unshift($headers, ['key' => 'selectAll', 'sortable' => false]);
        }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $stages = Str::toArray($request->query('stages'));
        $courses = $request->query('courses');
        $name = $request->query('name');
        $contactNumber = $request->query('contact_number');
        $gender = $request->query('gender');
        $address = $request->query('address');
        $guardianName = $request->query('guardian_name');
        $status = $request->query('status');
        $applicationNumber = $request->query('application_number');
        $type = $request->query('type');
        $employees = Str::toArray($request->query('employees'));
        $categories = Str::toArray($request->query('categories'));
        $program = $request->query('program');
        $department = $request->query('department');
        $admissionType = $request->query('admission_type');

        return Registration::query()
            ->select('registrations.*', 'admissions.is_provisional', 'admissions.code_number as admission_number', 'admissions.joining_date as admission_date', 'batches.name as batch_name')
            ->with(['stage', 'enrollmentType', 'period', 'course', 'contact' => function ($q) {
                $q->withGuardian();
            }, 'contact.guardian', 'employee' => fn ($q) => $q->summary()])
            ->byPeriod()
            ->leftJoin('admissions', 'admissions.registration_id', '=', 'registrations.id')
            ->leftJoin('batches', 'batches.id', '=', 'admissions.batch_id')
            ->when($status, function ($q, $status) {
                $q->where('status', '=', $status);
            }, function ($q) {
                $q->where('status', '!=', RegistrationStatus::INITIATED);
            })
            ->when($applicationNumber, function ($q, $applicationNumber) {
                $q->where('meta->application_number', 'like', "%{$applicationNumber}%");
            })
            ->when($admissionType == AdmissionType::PROVISIONAL->value, function ($q) {
                $q->where('admissions.is_provisional', true);
            })
            ->when($admissionType == AdmissionType::REGULAR->value, function ($q) {
                $q->where('admissions.is_provisional', false);
            })
            ->when($type, function ($q, $type) {
                if ($type == 'online') {
                    $q->where('is_online', true);
                } else {
                    $q->where('is_online', false);
                }
            })
            ->when($request->query('enrollment_type'), function ($q, $enrollmentType) {
                $q->whereHas('enrollmentType', function ($q) use ($enrollmentType) {
                    $q->where('uuid', $enrollmentType);
                });
            })
            ->when($employees, function ($q, $employees) {
                $q->whereHas('employee', function ($q) use ($employees) {
                    $q->whereIn('uuid', $employees);
                });
            })
            ->when($stages, function ($q, $stages) {
                $q->whereHas('stage', function ($q1) use ($stages) {
                    $q1->whereIn('uuid', $stages);
                });
            })
            ->when($courses, function ($q, $courses) {
                $q->whereHas('course', function ($q1) use ($courses) {
                    $q1->whereIn('uuid', Str::toArray($courses));
                });
            })
            ->when($program, function ($q, $program) {
                $q->whereHas('course', function ($q1) use ($program) {
                    $q1->whereHas('division', function ($q) use ($program) {
                        $q->whereHas('program', function ($q2) use ($program) {
                            $q2->where('uuid', $program);
                        });
                    });
                });
            })
            ->when($department, function ($q, $department) {
                $q->whereHas('course', function ($q1) use ($department) {
                    $q1->whereHas('division', function ($q) use ($department) {
                        $q->whereHas('program', function ($q2) use ($department) {
                            $q2->whereHas('department', function ($q3) use ($department) {
                                $q3->where('uuid', $department);
                            });
                        });
                    });
                });
            })
            ->when($name, function ($q, $name) {
                $q->whereHas('contact', function ($q) use ($name) {
                    $q->searchByName($name);
                });
            })
            ->when($gender, function ($q, $gender) {
                $q->whereHas('contact', function ($q) use ($gender) {
                    $q->where('gender', $gender);
                });
            })
            ->when($contactNumber, function ($q, $contactNumber) {
                $q->whereHas('contact', function ($q) use ($contactNumber) {
                    $q->where('contact_number', $contactNumber);
                });
            })
            ->when($categories, function ($q, $categories) {
                $q->whereHas('contact', function ($q) use ($categories) {
                    $q->whereHas('category', function ($q) use ($categories) {
                        $q->whereIn('uuid', $categories);
                    });
                });
            })
            ->when($address, function ($q, $address) {
                $q->whereHas('contact', function ($q) use ($address) {
                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.present.address_line1')) LIKE ?", ['%'.$address.'%'])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.present.address_line2')) LIKE ?", ['%'.$address.'%'])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.present.city')) LIKE ?", ['%'.$address.'%'])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.present.state')) LIKE ?", ['%'.$address.'%'])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.present.country')) LIKE ?", ['%'.$address.'%']);
                });
            })
            ->when($guardianName, function ($q, $guardianName) {
                $q->whereHas('contact', function ($q1) use ($guardianName) {
                    $q1->whereHas('guardians', function ($q2) use ($guardianName) {
                        $q2->whereHas('contact', function ($q3) use ($guardianName) {
                            $q3->searchByName($guardianName);
                        });
                    });
                });
            })
            ->when($request->query('previous_institute'), function ($q, $previousInstitute) {
                $q->whereHas('contact', function ($q) use ($previousInstitute) {
                    $q->whereHas('qualifications', function ($q) use ($previousInstitute) {
                        $q->where('institute', 'like', "%{$previousInstitute}%");
                    });
                });
            })
            ->filter([
                'App\QueryFilters\ExactMatch:registrations.code_number',
                'App\QueryFilters\ExactMatch:status',
                'App\QueryFilters\DateBetween:start_date,end_date,date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return RegistrationResource::collection($this->filter($request)
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

    public function getIds(Request $request): array
    {
        return $this->filter($request)->select('registrations.uuid')->get()->pluck('uuid')->all();
    }
}
