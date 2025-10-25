<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\TransferCertificateService;
use Illuminate\Http\Request;

class TransferCertificateController extends Controller
{
    public function __construct()
    {
        $this->middleware('feature.available:feature.enable_transfer_certificate_verification');
    }

    public function preRequisite(TransferCertificateService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function verify(Request $request, TransferCertificateService $service)
    {
        return $service->verify($request);
    }

    public function downloadMedia(Request $request, string $uuid, TransferCertificateService $service)
    {
        $student = $service->getStudent($request);

        $student->load('admission.media');

        return $student->admission->downloadMedia($uuid);
    }
}
