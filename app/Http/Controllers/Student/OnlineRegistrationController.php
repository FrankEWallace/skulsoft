<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\OnlineRegistrationBasicRequest;
use App\Http\Requests\Student\OnlineRegistrationContactRequest;
use App\Http\Requests\Student\OnlineRegistrationRequest;
use App\Http\Requests\Student\OnlineRegistrationReviewRequest;
use App\Http\Requests\Student\OnlineRegistrationUploadRequest;
use App\Http\Resources\Student\OnlineRegistrationResource;
use App\Models\Team;
use App\Services\Contact\PhotoService;
use App\Services\Student\OnlineRegistrationService;
use Illuminate\Http\Request;

class OnlineRegistrationController extends Controller
{
    public function preRequisite(Request $request, OnlineRegistrationService $service)
    {
        return $service->preRequisite($request);
    }

    public function getPrograms(Team $team, OnlineRegistrationService $service)
    {
        return $service->getPrograms($team);
    }

    public function getPeriods(Team $team, OnlineRegistrationService $service)
    {
        return $service->getPeriods($team);
    }

    public function getCourses(string $period, OnlineRegistrationService $service)
    {
        return $service->getCourses($period);
    }

    public function getBatches(string $period, string $course, OnlineRegistrationService $service)
    {
        return $service->getBatches($period, $course);
    }

    public function initiate(OnlineRegistrationRequest $request, OnlineRegistrationService $service)
    {
        $registration = $service->initiate($request);

        return response()->success([
            'token' => $registration->getMeta('verification_token'),
            'message' => trans('student.online_registration.received', ['email' => $registration->contact->email]),
        ]);
    }

    public function initiateMinimal(OnlineRegistrationRequest $request, OnlineRegistrationService $service)
    {
        $registration = $service->initiateMinimal($request);

        return response()->success([
            'message' => trans('student.online_registration.submitted'),
        ]);
    }

    public function confirm(Request $request, OnlineRegistrationService $service)
    {
        $registration = $service->confirm($request);

        return response()->success([
            'token' => $registration->getMeta('auth_token'),
            'application_number' => $registration->getMeta('application_number'),
            'message' => trans('student.online_registration.confirmed', ['attribute' => $registration->getMeta('application_number')]),
        ]);
    }

    public function find(Request $request, OnlineRegistrationService $service)
    {
        $service->find($request);

        return response()->ok();
    }

    public function verify(Request $request, OnlineRegistrationService $service)
    {
        $registration = $service->verify($request);

        return response()->success([
            'token' => $registration->getMeta('auth_token'),
            'application_number' => $registration->getMeta('application_number'),
        ]);
    }

    public function show(Request $request, string $number, OnlineRegistrationService $service)
    {
        $registration = $service->findByUuidOrFail($request, $number);

        $registration->load('media');

        return OnlineRegistrationResource::make($registration);
    }

    public function updateBasic(OnlineRegistrationBasicRequest $request, string $number, OnlineRegistrationService $service)
    {
        $registration = $service->findByUuidOrFail($request, $number);

        $service->updateBasic($request, $registration);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.online_registration.application')]),
        ]);
    }

    public function updateContact(OnlineRegistrationContactRequest $request, string $number, OnlineRegistrationService $service)
    {
        $registration = $service->findByUuidOrFail($request, $number);

        $service->updateContact($request, $registration);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.online_registration.application')]),
        ]);
    }

    public function uploadPhoto(Request $request, string $number, OnlineRegistrationService $service, PhotoService $photoService)
    {
        $registration = $service->findByUuidOrFail($request, $number);

        $photoService->upload($request, $registration->contact);

        $service->photoUploaded($registration);

        return response()->success([
            'message' => trans('global.uploaded', ['attribute' => trans('contact.props.photo')]),
        ]);
    }

    public function removePhoto(Request $request, string $number, OnlineRegistrationService $service, PhotoService $photoService)
    {
        $registration = $service->findByUuidOrFail($request, $number);

        $photoService->remove($request, $registration->contact);

        $service->photoRemoved($registration);

        return response()->success([
            'message' => trans('global.removed', ['attribute' => trans('contact.props.photo')]),
        ]);
    }

    public function uploadFile(OnlineRegistrationUploadRequest $request, string $number, OnlineRegistrationService $service)
    {
        $registration = $service->findByUuidOrFail($request, $number);

        $service->uploadFile($request, $registration);

        return response()->success([
            'message' => trans('global.uploaded', ['attribute' => trans('general.file')]),
        ]);
    }

    public function updateReview(OnlineRegistrationReviewRequest $request, string $number, OnlineRegistrationService $service)
    {
        $registration = $service->findByUuidOrFail($request, $number);

        $service->updateReview($request, $registration);

        return response()->success([
            'message' => trans('student.online_registration.submitted'),
        ]);
    }

    public function download(Request $request, string $number, OnlineRegistrationService $service)
    {
        $request->headers->set('auth-token', $request->query('auth-token'));

        $registration = $service->findByUuidOrFail($request, $number);

        $service->isDownloadable($registration);

        $registration->load('contact');

        config(['config.team' => $registration->contact->team]);

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
        ]);
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->WriteHTML(view()->first([config('config.print.custom_path').'student.online-registration', 'print.student.online-registration'], compact('registration'))->render());
        $mpdf->Output();
    }
}
