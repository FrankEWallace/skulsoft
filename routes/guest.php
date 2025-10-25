<?php

use App\Http\Controllers\Recruitment\Job\ApplicationController;
use App\Http\Controllers\Recruitment\Job\VacancyController;
use App\Http\Controllers\Student\AnonymousPaymentController;
use App\Http\Controllers\Student\GuestPaymentController;
use App\Http\Controllers\Student\OnlineRegistrationController;
use App\Http\Controllers\Student\OnlineRegistrationPaymentController;
use App\Http\Controllers\Student\TransferCertificateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['feature.available:feature.enable_guest_payment'])->group(function () {
    Route::get('app/guest-payments/pre-requisite', [GuestPaymentController::class, 'preRequisite'])->name('guest-payment.preRequisite');
    Route::get('app/guest-payments/{team}/periods', [GuestPaymentController::class, 'getPeriods'])->name('guest-payment.getPeriods');
    Route::get('app/guest-payments/{team}/{period}/courses', [GuestPaymentController::class, 'getCourses'])->name('guest-payment.getCourses');
    Route::post('app/guest-payments/fee-detail', [GuestPaymentController::class, 'getFeeDetail'])->name('guest-payment.getFeeDetail');

    Route::post('app/anonymous-payments/fee-detail', [AnonymousPaymentController::class, 'getFeeDetail'])->name('anonymous-payment.getFeeDetail')->withoutMiddleware(['guest']);

    Route::post('app/guest-payments/{student}/initiate', [GuestPaymentController::class, 'initiate'])->name('guest-payment.initiatePayment')->withoutMiddleware(['guest']);
    Route::post('app/guest-payments/{student}/complete', [GuestPaymentController::class, 'complete'])->name('guest-payment.completePayment')->withoutMiddleware(['guest']);
    Route::post('app/guest-payments/{student}/fail', [GuestPaymentController::class, 'fail'])->name('guest-payment.failPayment')->withoutMiddleware(['guest']);
});

Route::middleware(['feature.available:feature.enable_online_registration'])->group(function () {
    Route::get('app/online-registrations/pre-requisite', [OnlineRegistrationController::class, 'preRequisite'])->name('online-registration.preRequisite');
    Route::get('app/online-registrations/{team}/programs', [OnlineRegistrationController::class, 'getPrograms'])->name('online-registration.getPrograms');
    Route::get('app/online-registrations/{team}/periods', [OnlineRegistrationController::class, 'getPeriods'])->name('online-registration.getPeriods');
    Route::get('app/online-registrations/{period}/courses', [OnlineRegistrationController::class, 'getCourses'])->name('online-registration.getCourses');
    Route::get('app/online-registrations/{period}/{course}/batches', [OnlineRegistrationController::class, 'getBatches'])->name('online-registration.getBatches');
    Route::post('app/online-registrations', [OnlineRegistrationController::class, 'initiate'])->name('online-registration.initiate');
    Route::post('app/online-registrations/minimal', [OnlineRegistrationController::class, 'initiateMinimal'])->name('online-registration.initiateMinimal');
    Route::post('app/online-registrations/confirm', [OnlineRegistrationController::class, 'confirm'])->name('online-registration.confirm');
    Route::post('app/online-registrations/find', [OnlineRegistrationController::class, 'find'])->name('online-registration.find');
    Route::post('app/online-registrations/verify', [OnlineRegistrationController::class, 'verify'])->name('online-registration.verify');

    Route::get('app/online-registrations/{number}', [OnlineRegistrationController::class, 'show'])->name('online-registration.show');

    Route::patch('app/online-registrations/{number}/basic', [OnlineRegistrationController::class, 'updateBasic'])->name('online-registration.updateBasic');
    Route::patch('app/online-registrations/{number}/contact', [OnlineRegistrationController::class, 'updateContact'])->name('online-registration.updateContact');
    Route::post('app/online-registrations/{number}/photo', [OnlineRegistrationController::class, 'uploadPhoto'])->name('online-registration.uploadPhoto');
    Route::delete('app/online-registrations/{number}/photo', [OnlineRegistrationController::class, 'removePhoto'])->name('online-registration.removePhoto');
    Route::post('app/online-registrations/{number}/upload', [OnlineRegistrationController::class, 'uploadFile'])->name('online-registration.uploadFile');
    Route::patch('app/online-registrations/{number}/review', [OnlineRegistrationController::class, 'updateReview'])->name('online-registration.updateReview');

    Route::get('app/online-registrations/{number}/payment/pre-requisite', [OnlineRegistrationPaymentController::class, 'preRequisite'])->name('online-registration.payment.preRequisite');
    Route::post('app/online-registrations/{number}/payment/initiate', [OnlineRegistrationPaymentController::class, 'initiate'])->name('online-registration.payment.initiate');
    Route::post('app/online-registrations/{number}/payment/complete', [OnlineRegistrationPaymentController::class, 'complete'])->name('online-registration.payment.complete');
    Route::post('app/online-registrations/{number}/payment/fail', [OnlineRegistrationPaymentController::class, 'fail'])->name('online-registration.payment.fail');
});

Route::middleware(['feature.available:feature.enable_job_application'])->group(function () {
    Route::get('app/job/vacancies/pre-requisite', [VacancyController::class, 'preRequisite'])->name('job.vacancies.preRequisite');
    Route::get('app/job/vacancies', [VacancyController::class, 'list'])->name('job.vacancies.list');
    Route::get('app/job/vacancies/{slug}', [VacancyController::class, 'detail'])->name('job.vacancies.detail');

    Route::post('app/job/vacancies/{slug}/applications', [ApplicationController::class, 'store'])->name('job.vacancies.applications');
});

Route::get('app/transfer-certificate/pre-requisite', [TransferCertificateController::class, 'preRequisite'])->name('transfer-certificate.preRequisite')->withoutMiddleware(['guest']);
Route::post('app/transfer-certificate/verify', [TransferCertificateController::class, 'verify'])->name('transfer-certificate.verify')->withoutMiddleware(['guest']);
