<?php

namespace App\Services\Student;

use App\Actions\Student\UpdateServiceRequest;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceType;
use App\Models\RequestRecord;
use App\Models\Student\ServiceAllocation;
use App\Models\Student\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ServiceRequestActionService
{
    public function updateStatus(Request $request, ServiceRequest $serviceRequest): void
    {
        if ($request->status == $serviceRequest->status->value) {
            $previousRequestRecord = RequestRecord::query()
                ->where('model_type', 'StudentServiceRequest')
                ->where('model_id', $serviceRequest->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($previousRequestRecord) {
                $previousRequestRecord->comment = $request->comment;
                $previousRequestRecord->remarks = $request->remarks;
                $previousRequestRecord->save();

                return;
            } else {
                throw ValidationException::withMessages([
                    'status' => trans('validation.exists', ['attribute' => trans('student.service_request.props.status')]),
                ]);
            }
        }

        if ($request->status == ServiceRequestStatus::REQUESTED->value) {
            throw ValidationException::withMessages([
                'message' => trans('general.errors.invalid_input'),
            ]);
        }

        \DB::beginTransaction();

        $requestRecord = RequestRecord::forceCreate([
            'model_type' => 'StudentServiceRequest',
            'model_id' => $serviceRequest->id,
            'status' => $request->status,
            'comment' => $request->comment,
            'remarks' => $request->remarks,
            'user_id' => auth()->id(),
        ]);

        // if service request status is requested and request status is rejected, then do nothing
        // if service request status is requested and request status is approved, then update the service allocation
        // if service request status is rejected and request status is approved, then update the service allocation
        // if service request status is approved and request status is rejected, then undo the service allocation

        $isUpdated = false;
        if ($serviceRequest->date->value <= today()->toDateString()) {
            if ($serviceRequest->status == ServiceRequestStatus::REQUESTED && $request->status == 'rejected') {
                $isUpdated = true;
                // do nothing
            } elseif ($serviceRequest->status == ServiceRequestStatus::REQUESTED && $request->status == 'approved') {
                $this->updateLastRequestRecord($requestRecord, $serviceRequest);
                (new UpdateServiceRequest)->execute($serviceRequest);
                $isUpdated = true;
            } elseif ($serviceRequest->status == ServiceRequestStatus::REJECTED && $request->status == 'approved') {
                $this->updateLastRequestRecord($requestRecord, $serviceRequest);
                (new UpdateServiceRequest)->execute($serviceRequest);
                $isUpdated = true;
            } elseif ($serviceRequest->status == ServiceRequestStatus::APPROVED && $request->status == 'rejected') {
                $isUpdated = true;
                $this->undoServiceAllocation($requestRecord, $serviceRequest);
            }
        }

        $serviceRequest->status = $request->status;
        if ($isUpdated) {
            $serviceRequest->setMeta(['is_updated' => true]);
        }
        $serviceRequest->save();

        \DB::commit();
    }

    private function updateLastRequestRecord(RequestRecord $requestRecord, ServiceRequest $serviceRequest): void
    {
        $existingAllocation = [];
        $existingServiceAllocation = ServiceAllocation::query()
            ->where('model_id', $serviceRequest->model_id)
            ->where('model_type', $serviceRequest->model_type)
            ->where('type', $serviceRequest->type)
            ->first();

        if ($existingServiceAllocation) {
            $existingAllocation = [
                'transport_stoppage_id' => $existingServiceAllocation->transport_stoppage_id,
            ];
        }

        $requestRecord->setMeta([
            'existing_allocation' => $existingAllocation,
        ]);
        $requestRecord->save();
    }

    private function undoServiceAllocation(RequestRecord $requestRecord, ServiceRequest $serviceRequest): void
    {
        $lastRequestRecord = RequestRecord::query()
            ->where('model_type', 'StudentServiceRequest')
            ->where('model_id', $serviceRequest->id)
            ->where('id', '!=', $requestRecord->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $serviceAllocation = ServiceAllocation::query()
            ->where('model_id', $serviceRequest->model_id)
            ->where('model_type', $serviceRequest->model_type)
            ->where('type', $serviceRequest->type)
            ->first();

        if ($lastRequestRecord && $serviceRequest->type == ServiceType::TRANSPORT->value) {
            $transportStoppageId = Arr::get($lastRequestRecord->meta, 'existing_allocation.transport_stoppage_id');

            if ($transportStoppageId != $serviceAllocation->transport_stoppage_id) {
                $serviceAllocation->transport_stoppage_id = $transportStoppageId;
                $serviceAllocation->save();
            } else {
                $serviceAllocation->delete();
            }
        } else {
            $serviceAllocation->delete();
        }
    }
}
