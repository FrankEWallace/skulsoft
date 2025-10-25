<?php

namespace App\Services\Student;

use App\Enums\OptionType;
use App\Enums\Student\TransferRequestStatus;
use App\Http\Resources\OptionResource;
use App\Http\Resources\Student\StudentSummaryResource;
use App\Models\Option;
use App\Models\Student\Student;
use App\Models\Student\TransferRequest;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class TransferRequestService
{
    use FormatCodeNumber;

    private function codeNumber(int $batchId): array
    {
        $numberPrefix = config('config.student.transfer_request_number_prefix');
        $numberSuffix = config('config.student.transfer_request_number_suffix');
        $digit = config('config.student.transfer_request_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $numberFormat = $this->preFormatForAcademicBatch($batchId, $numberFormat);

        $codeNumber = (int) TransferRequest::query()
            ->join('students', 'students.id', '=', 'transfer_requests.student_id')
            ->join('periods', 'periods.id', '=', 'students.period_id')
            ->where('periods.team_id', auth()->user()?->current_team_id)
            ->whereNumberFormat($numberFormat)
            ->max('transfer_requests.number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function preRequisite(Request $request): array
    {
        $statuses = TransferRequestStatus::getOptions();

        $reasons = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::STUDENT_TRANSFER_REASON->value)
            ->get());

        $students = [];
        if (auth()->user()->is_student_or_guardian) {
            $students = StudentSummaryResource::collection(Student::query()
                ->byPeriod()
                ->summary()
                ->filterForStudentAndGuardian()
                ->get());
        }

        return compact('students', 'statuses', 'reasons');
    }

    public function create(Request $request): void
    {
        \DB::beginTransaction();

        $transferRequest = TransferRequest::forceCreate($this->formatParams($request));

        $transferRequest->addMedia($request);

        \DB::commit();
    }

    private function formatParams(Request $request, ?TransferRequest $transferRequest = null): array
    {
        $formatted = [
            'request_date' => $request->request_date,
            'reason' => $request->reason,
        ];

        if (! $transferRequest) {
            $codeNumberDetail = $this->codeNumber($request->student->batch_id);

            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');

            $formatted['student_id'] = $request->student->id;
            $formatted['status'] = TransferRequestStatus::REQUESTED;
            $formatted['user_id'] = auth()->id();
        }

        return $formatted;
    }

    private function isEditable(Request $request, TransferRequest $transferRequest)
    {
        if (! in_array($transferRequest->status, [TransferRequestStatus::REQUESTED])) {
            throw ValidationException::withMessages(['message' => trans('student.transfer_request.could_not_perform_if_status_updated')]);
        }

        if (! $transferRequest->is_editable) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }
    }

    public function update(Request $request, TransferRequest $transferRequest): void
    {
        $this->isEditable($request, $transferRequest);

        \DB::beginTransaction();

        $transferRequest->forceFill($this->formatParams($request, $transferRequest))->save();

        $transferRequest->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Request $request, TransferRequest $transferRequest): void
    {
        $this->isEditable($request, $transferRequest);
    }

    public function delete(TransferRequest $transferRequest): void
    {
        \DB::beginTransaction();

        $transferRequest->delete();

        \DB::commit();
    }
}
