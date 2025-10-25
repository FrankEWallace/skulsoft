<?php

namespace App\Services\Academic;

use App\Contracts\ListGenerator;
use App\Http\Resources\Academic\CertificateSummaryResource;
use App\Models\Academic\Certificate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class CertificateListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'date'];

    protected $defaultSort = 'date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('academic.certificate.props.code_number'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'template',
                'label' => trans('academic.certificate.template.template'),
                'print_label' => 'template.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'label' => trans('academic.certificate.props.to'),
                'print_label' => 'to.name',
                'print_sub_label' => 'to.code_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'date',
                'label' => trans('academic.certificate.props.date'),
                'print_label' => 'date.formatted',
                'sortable' => true,
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

    public function filter(Request $request): Builder
    {
        $search = $request->query('search');

        $searchTerms = collect(explode(',', $search))
            ->filter()
            ->map(function ($term) {
                $parts = explode(':', $term, 2);

                return [
                    'key' => strtoupper($parts[0] ?? ''),
                    'value' => $parts[1] ?? '',
                ];
            })
            ->filter(function ($term) {
                return ! empty($term['key']) && $term['key'] != 'NAME' && ! empty($term['value']);
            })
            ->values()
            ->all();

        $name = Str::contains($search, 'name:') ? Str::of($search)->after('name:')->before(',')->value : null;

        return Certificate::query()
            ->with('template:id,name,for,custom_fields')
            ->select('certificates.uuid', 'certificates.date', 'certificates.code_number', 'certificates.template_id', 'certificates.model_type', 'certificates.meta', 'certificates.custom_fields', 'certificates.created_at', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as to_name'), 'admissions.code_number as admission_number', 'employees.code_number as employee_number')
            ->leftJoin('students', function ($join) {
                $join
                    ->on('certificates.model_id', '=', 'students.id')
                    ->where('certificates.model_type', '=', 'Student');
            })
            ->leftJoin('employees', function ($join) {
                $join
                    ->on('certificates.model_id', '=', 'employees.id')
                    ->where('certificates.model_type', '=', 'Employee');
            })
            ->leftJoin('contacts', function ($join) {
                $join
                    ->on('students.contact_id', '=', 'contacts.id')
                    ->orOn('employees.contact_id', '=', 'contacts.id');
            })
            ->leftJoin('admissions', 'students.admission_id', '=', 'admissions.id')
            ->when($request->query('admission_number'), function ($q, $admissionNumber) {
                $q->where('admissions.code_number', $admissionNumber);
            })
            ->when($request->query('employee_number'), function ($q, $employeeNumber) {
                $q->where('employees.code_number', $employeeNumber);
            })
            ->when($request->query('template'), function ($q, $template) {
                $q->whereHas('template', function ($q) use ($template) {
                    $q->where('uuid', $template);
                });
            })
            ->when($name, function ($q, $name) {
                $q->where(
                    \DB::raw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(certificates.meta, '$.name')))"),
                    'LIKE',
                    '%'.strtolower($name).'%');
            })
            ->when($searchTerms, function ($q, $searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->where(function ($query) use ($term) {
                        $query->where(
                            \DB::raw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(certificates.custom_fields, '$.{$term['key']}')))"),
                            'LIKE',
                            '%'.strtolower($term['value']).'%'
                        );
                    });
                }
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number,certificates.code_number',
                'App\QueryFilters\DateBetween:start_date,end_date,date',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return CertificateSummaryResource::collection($this->filter($request)
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
