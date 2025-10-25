<?php

namespace App\Actions\Student;

use App\Enums\ServiceRequestType;
use App\Models\Student\ServiceAllocation;
use App\Models\Student\ServiceRequest;

class UpdateServiceRequest
{
    public function execute(ServiceRequest $serviceRequest)
    {
        if ($serviceRequest->request_type == ServiceRequestType::OPT_IN) {
            ServiceAllocation::firstOrCreate([
                'model_id' => $serviceRequest->model_id,
                'model_type' => $serviceRequest->model_type,
                'type' => $serviceRequest->type,
            ], [
                'transport_stoppage_id' => $serviceRequest->transport_stoppage_id,
            ]);
        } else {
            $serviceAllocation = ServiceAllocation::query()
                ->where('model_id', $serviceRequest->model_id)
                ->where('model_type', $serviceRequest->model_type)
                ->where('type', $serviceRequest->type)
                ->first();

            if ($serviceAllocation) {
                $serviceAllocation->delete();
            }
        }
    }
}
