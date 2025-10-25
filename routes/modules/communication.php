<?php

use App\Http\Controllers\Communication\AnnouncementActionController;
use App\Http\Controllers\Communication\AnnouncementController;
use App\Http\Controllers\Communication\EmailController;
use App\Http\Controllers\Communication\SMSController;
use App\Http\Controllers\Communication\WhatsAppController;
use Illuminate\Support\Facades\Route;

// Communication Routes

Route::prefix('communication')->name('communication.')->group(function () {
    Route::get('announcements/pre-requisite', [AnnouncementController::class, 'preRequisite'])->name('announcements.preRequisite');

    Route::post('announcements/{announcement}/pin', [AnnouncementActionController::class, 'pin'])->name('announcements.pin');
    Route::post('announcements/{announcement}/unpin', [AnnouncementActionController::class, 'unpin'])->name('announcements.unpin');

    Route::apiResource('announcements', AnnouncementController::class);

    Route::get('emails/pre-requisite', [EmailController::class, 'preRequisite'])->name('emails.preRequisite');
    Route::apiResource('emails', EmailController::class)->only(['index', 'store', 'show']);

    Route::get('sms/pre-requisite', [SMSController::class, 'preRequisite'])->name('sms.preRequisite');
    Route::apiResource('sms', SMSController::class)->only(['index', 'store', 'show']);

    Route::get('whatsapp/pre-requisite', [WhatsAppController::class, 'preRequisite'])->name('whatsapp.preRequisite');
    Route::apiResource('whatsapp', WhatsAppController::class)->only(['index', 'store', 'show']);
});
