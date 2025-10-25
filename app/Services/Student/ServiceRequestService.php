<?php

namespace App\Services\Student;

use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceRequestType;
use App\Enums\ServiceType;
use App\Http\Resources\Student\StudentSummaryResource;
use App\Http\Resources\Transport\StoppageResource;
use App\Models\Student\ServiceRequest;
use App\Models\Student\Student;
use App\Models\Transport\Stoppage;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ServiceRequestService
{
    use FormatCodeNumber;

    private function codeNumber(int $batchId): array
    {
        $numberPrefix = config('config.student.service_request_number_prefix');
        $numberSuffix = config('config.student.service_request_number_suffix');
        $digit = config('config.student.service_request_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $numberFormat = $this->preFormatForAcademicBatch($batchId, $numberFormat);

        $codeNumber = (int) ServiceRequest::query()
            ->where('model_type', 'Student')
            ->join('students', 'students.id', '=', 'service_requests.model_id')
            ->join('periods', 'periods.id', '=', 'students.period_id')
            ->where('periods.team_id', auth()->user()?->current_team_id)
            ->whereNumberFormat($numberFormat)
            ->max('service_requests.number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function preRequisite(Request $request): array
    {
        $students = [];
        if (auth()->user()->is_student_or_guardian) {
            $students = StudentSummaryResource::collection(Student::query()
                ->byPeriod()
                ->summary()
                ->filterForStudentAndGuardian()
                ->get());
        }

        $types = ServiceType::getOptions();

        $availableServices = explode(',', config('config.student.services'));

        $types = collect($types)->filter(function ($type) use ($availableServices) {
            return in_array(Arr::get($type, 'value'), $availableServices);
        })->values()->toArray();

        $requestTypes = ServiceRequestType::getOptions();

        $statuses = ServiceRequestStatus::getOptions();

        $transportStoppages = StoppageResource::collection(Stoppage::query()
            ->byPeriod()
            ->get());

        return compact('types', 'requestTypes', 'students', 'transportStoppages', 'statuses');
    }

    public function create(Request $request): void
    {
        \DB::beginTransaction();

        $serviceRequest = ServiceRequest::forceCreate($this->formatParams($request));

        $serviceRequest->addMedia($request);

        \DB::commit();
    }

    private function formatParams(Request $request, ?ServiceRequest $serviceRequest = null): array
    {
        $formatted = [
            'model_type' => 'Student',
            'model_id' => $request->student_id,
            'type' => $request->type,
            'request_type' => $request->request_type,
            'transport_stoppage_id' => $request->transport_stoppage_id,
            'date' => $request->date,
            'description' => $request->description,
        ];

        if (! $serviceRequest) {
            $codeNumberDetail = $this->codeNumber($request->student->batch_id);

            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');

            $formatted['status'] = ServiceRequestStatus::REQUESTED;
            $formatted['request_user_id'] = auth()->id();
        }

        return $formatted;
    }

    private function isEditable(Request $request, ServiceRequest $serviceRequest)
    {
        if ($serviceRequest->date < today()->toDateString()) {
            throw ValidationException::withMessages(['message' => trans('student.service_request.could_not_perform_for_past_date')]);
        }

        if (! in_array($serviceRequest->status, [ServiceRequestStatus::REQUESTED])) {
            throw ValidationException::withMessages(['message' => trans('student.service_request.could_not_perform_if_status_updated')]);
        }

        if (! $serviceRequest->is_editable) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }
    }

    public function update(Request $request, ServiceRequest $serviceRequest): void
    {
        $this->isEditable($request, $serviceRequest);

        \DB::beginTransaction();

        $serviceRequest->forceFill($this->formatParams($request, $serviceRequest))->save();

        $serviceRequest->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Request $request, ServiceRequest $serviceRequest): void
    {
        $this->isEditable($request, $serviceRequest);
    }

    public function delete(ServiceRequest $serviceRequest): void
    {
        \DB::beginTransaction();

        $serviceRequest->delete();

        \DB::commit();
    }
}
