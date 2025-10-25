<?php

namespace App\Services\Student;

use App\Contracts\ListGenerator;
use App\Enums\Student\AdmissionType;
use App\Http\Resources\Student\StudentListResource;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StudentListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'code_number', 'name', 'course', 'gender', 'birth_date', 'religion', 'caste', 'category'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('student.admission.props.code_number'),
                'print_label' => 'code_number',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'joiningDate',
                'label' => trans('student.admission.props.date'),
                'print_label' => 'joining_date.formatted',
                'sortable' => true,
                'visibility' => false,
                'printable' => true,
            ],
            [
                'key' => 'name',
                'label' => trans('contact.props.name'),
                'print_label' => 'name',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'course_name + batch_name',
                // 'print_sub_label' => 'batch_name',
                // 'print_sub_label' => 'enrollment_type.name',
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
                'key' => 'rollNumber',
                'label' => trans('student.roll_number.roll_number'),
                'print_label' => 'roll_number',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'enrollmentType',
                'label' => trans('student.enrollment_type.enrollment_type'),
                'print_label' => 'enrollment_type_name',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'enrollmentStatus',
                'label' => trans('student.enrollment_status.enrollment_status'),
                'print_label' => 'enrollment_status_name',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'mentor',
                'label' => trans('student.mentor.mentor'),
                'print_label' => 'mentor.name',
                'print_sub_label' => 'mentor.code_number',
                'sortable' => false,
                'visibility' => false,
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
                'visibility' => false,
            ],
            [
                'key' => 'guardian',
                'label' => trans('guardian.guardian'),
                'print_label' => 'guardian.contact.name',
                'print_sub_label' => 'guardian.contact.contact_number',
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
                'key' => 'locality',
                'label' => trans('contact.props.locality'),
                'print_label' => 'locality.label',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'religion',
                'label' => trans('contact.religion.religion'),
                'print_label' => 'religion_name',
                'sortable' => true,
                'visibility' => false,
            ],
        ];

        if (config('config.contact.enable_category')) {
            $headers[] = [
                'key' => 'category',
                'label' => trans('contact.category.category'),
                'print_label' => 'category_name',
                'sortable' => true,
                'visibility' => false,
            ];
        }

        if (config('config.contact.enable_caste')) {
            $headers[] = [
                'key' => 'caste',
                'label' => trans('contact.caste.caste'),
                'print_label' => 'caste_name',
                'sortable' => true,
                'visibility' => false,
            ];
        }

        if (config('config.student.is_unique_id_number1_enabled')) {
            $headers[] = [
                'key' => 'uniqueIdNumber1',
                'label' => config('config.student.unique_id_number1_label'),
                'sortable' => true,
                'visibility' => false,
            ];
        }

        if (config('config.student.is_unique_id_number2_enabled')) {
            $headers[] = [
                'key' => 'uniqueIdNumber2',
                'label' => config('config.student.unique_id_number2_label'),
                'sortable' => true,
                'visibility' => false,
            ];
        }

        if (config('config.student.is_unique_id_number3_enabled')) {
            $headers[] = [
                'key' => 'uniqueIdNumber3',
                'label' => config('config.student.unique_id_number3_label'),
                'sortable' => true,
                'visibility' => false,
            ];
        }

        if (config('config.student.is_unique_id_number4_enabled')) {
            $headers[] = [
                'key' => 'uniqueIdNumber4',
                'label' => config('config.student.unique_id_number4_label'),
                'sortable' => true,
                'visibility' => false,
            ];
        }

        if (config('config.student.is_unique_id_number5_enabled')) {
            $headers[] = [
                'key' => 'uniqueIdNumber5',
                'label' => config('config.student.unique_id_number5_label'),
                'sortable' => true,
                'visibility' => false,
            ];
        }

        $headers[] = [
            'key' => 'transferDate',
            'label' => trans('student.transfer.props.date'),
            'print_label' => 'leaving_date.formatted',
            'sortable' => false,
            'visibility' => false,
        ];

        $headers[] = [
            'key' => 'address',
            'label' => trans('contact.props.address.address'),
            'print_label' => 'address',
            'sortable' => false,
            'visibility' => false,
        ];

        $headers[] = [
            'key' => 'createdAt',
            'label' => trans('general.created_at'),
            'print_label' => 'created_at.formatted',
            'sortable' => true,
            'visibility' => true,
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
            array_unshift($headers, ['key' => 'selectAll', 'sortable' => false]);
        }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $search = $request->query('search');
        $status = $request->query('status', 'studying');
        $withTransferred = $request->boolean('with_transferred');
        $withCancelled = $request->boolean('with_cancelled');
        $forSubject = $request->boolean('for_subject');

        $courses = Str::toArray($request->query('courses'));
        $tagsIncluded = Str::toArray($request->query('tags_included'));
        $tagsExcluded = Str::toArray($request->query('tags_excluded'));
        $groups = Str::toArray($request->query('groups'));
        $admissionType = $request->query('admission_type');

        if (! $request->has('for_subject')) {
            $forSubject = config('config.academic.allow_listing_subject_wise_student', false);
        }

        return Student::query()
            ->detail()
            ->byPeriod()
            ->filterAccessible($forSubject)
            ->with(['mentor' => fn ($q) => $q->summary()])
            ->when($withTransferred == false && $withCancelled == false, function ($q) use ($status) {
                $q->filterByStatus($status);
            })
            ->when($admissionType == AdmissionType::PROVISIONAL->value, function ($q) {
                $q->where('admissions.is_provisional', true);
            })
            ->when($admissionType == AdmissionType::REGULAR->value, function ($q) {
                $q->where('admissions.is_provisional', false);
            })
            ->when($withTransferred == true, function ($q) {
                $q->whereNull('students.cancelled_at');
            })
            ->when($withCancelled == true, function ($q) {
                $q->whereNull('students.end_date');
            })
            ->when($courses, function ($q, $courses) {
                $q->whereHas('batch', function ($q1) use ($courses) {
                    $q1->whereHas('course', function ($q2) use ($courses) {
                        $q2->whereIn('uuid', $courses);
                    });
                });
            })
            ->when($request->query('program'), function ($q, $program) {
                $q->whereHas('batch', function ($q1) use ($program) {
                    $q1->whereHas('course', function ($q2) use ($program) {
                        $q2->whereHas('division', function ($q3) use ($program) {
                            $q3->whereHas('program', function ($q4) use ($program) {
                                $q4->where('uuid', $program);
                            });
                        });
                    });
                });
            })
            ->when($request->query('department'), function ($q, $department) {
                $q->whereHas('batch', function ($q1) use ($department) {
                    $q1->whereHas('course', function ($q2) use ($department) {
                        $q2->whereHas('division', function ($q3) use ($department) {
                            $q3->whereHas('program', function ($q4) use ($department) {
                                $q4->whereHas('department', function ($q5) use ($department) {
                                    $q5->where('uuid', $department);
                                });
                            });
                        });
                    });
                });
            })
            ->when($request->query('status') == 'alumni' && $request->query('alumni_period'), function ($q) use ($request) {
                $q->whereHas('period', function ($q) use ($request) {
                    $q->where('uuid', $request->query('alumni_period'));
                });
            })
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->when($search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$search}%")
                        ->orWhere('admissions.code_number', 'like', "%{$search}%");
                });
            })
            ->when($tagsIncluded, function ($q, $tagsIncluded) {
                $q->whereHas('tags', function ($q) use ($tagsIncluded) {
                    $q->whereIn('name', $tagsIncluded);
                });
            })
            ->when($tagsExcluded, function ($q, $tagsExcluded) {
                $q->whereDoesntHave('tags', function ($q) use ($tagsExcluded) {
                    $q->whereIn('name', $tagsExcluded);
                });
            })
            ->when($request->query('enrollment_type'), function ($q, $enrollmentType) {
                $q->where('enrollment_types.uuid', $enrollmentType);
            })
            ->when($request->query('enrollment_status'), function ($q, $enrollmentStatus) {
                $q->where('enrollment_statuses.uuid', $enrollmentStatus);
            })
            ->when($groups, function ($q, $groups) {
                $q->leftJoin('group_members', 'students.id', 'group_members.model_id')
                    ->where('group_members.model_type', 'Student')
                    ->join('options as student_groups', 'group_members.model_group_id', 'student_groups.id')
                    ->whereIn('student_groups.uuid', $groups);
            })
            ->when($request->query('address'), function ($q, $address) {
                $q->filterByAddress($address);
            })
            ->when($request->query('previous_institute'), function ($q, $previousInstitute) {
                $q->whereHas('contact', function ($q) use ($previousInstitute) {
                    $q->whereHas('qualifications', function ($q) use ($previousInstitute) {
                        $q->where('institute', 'like', "%{$previousInstitute}%");
                    });
                });
            })
            ->filter([
                'App\QueryFilters\UuidMatch:students.uuid',
                'App\QueryFilters\LikeMatch:first_name',
                'App\QueryFilters\LikeMatch:last_name',
                'App\QueryFilters\LikeMatch:father_name',
                'App\QueryFilters\LikeMatch:mother_name',
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\LikeMatch:contact_number',
                'App\QueryFilters\ExactMatch:gender',
                'App\QueryFilters\ExactMatch:unique_id_number_1,contacts.unique_id_number1',
                'App\QueryFilters\ExactMatch:unique_id_number_2,contacts.unique_id_number2',
                'App\QueryFilters\ExactMatch:unique_id_number_3,contacts.unique_id_number3',
                'App\QueryFilters\ExactMatch:unique_id_number_4,contacts.unique_id_number4',
                'App\QueryFilters\ExactMatch:unique_id_number_5,contacts.unique_id_number5',
                'App\QueryFilters\WhereInMatch:students.uuid,uuid',
                'App\QueryFilters\WhereInMatch:blood_group,blood_groups',
                'App\QueryFilters\WhereInMatch:locality,localities',
                'App\QueryFilters\WhereInMatch:religions.uuid,religions',
                'App\QueryFilters\WhereInMatch:categories.uuid,categories',
                'App\QueryFilters\WhereInMatch:castes.uuid,castes',
                'App\QueryFilters\WhereInMatch:batches.uuid,batches',
                'App\QueryFilters\DateBetween:birth_start_date,birth_end_date,birth_date',
                'App\QueryFilters\DateBetween:admission_start_date,admission_end_date,joining_date',
                'App\QueryFilters\DateBetween:leaving_start_date,leaving_end_date,leaving_date',
                'App\QueryFilters\DateBetween:start_date,end_date,students.created_at,datetime',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $view = $request->query('view', 'card');
        $request->merge(['view' => $view]);

        $query = $this->filter($request);

        if ($this->getSort() == 'course') {
            $query->orderBy('course_name', $this->getOrder());
        } elseif ($this->getSort() == 'religion') {
            $query->orderBy('religion_name', $this->getOrder());
        } elseif ($this->getSort() == 'category') {
            $query->orderBy('category_name', $this->getOrder());
        } elseif ($this->getSort() == 'caste') {
            $query->orderBy('caste_name', $this->getOrder());
        } else {
            $query->orderBy($this->getSort(), $this->getOrder());
        }

        $query->orderBy('students.number', 'asc');

        return StudentListResource::collection($query
            ->when($request->query('output') == 'export_all_excel', function ($q) {
                return $q->get();
            }, function ($q) {
                return $q->paginate((int) $this->getPageLength(), ['*'], 'current_page');
            }))
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

    public function listAll(Request $request): Collection
    {
        return $this->filter($request)->get();
    }
}
