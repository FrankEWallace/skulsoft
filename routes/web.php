<?php

use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Config\MailTemplateController;
use App\Http\Controllers\DownloadFormatController;
use App\Http\Controllers\SignedMediaController;
use App\Http\Controllers\Student\OnlineRegistrationController;
use App\Http\Controllers\Student\PaymentController;
use App\Http\Controllers\Student\TransferCertificateController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/app/config/mail-template/{mail_template}', [MailTemplateController::class, 'detail'])
    ->name('config.mail-template.detail')
    ->middleware('permission:config:store');

Route::get('/media/{media}/{conversion?}', SignedMediaController::class)->name('media');

Route::prefix('app')
    ->middleware(['auth:sanctum', 'two.factor.security', 'screen.lock', 'under.maintenance', 'user.config'])
    ->group(function () {
        Route::get('students/{student}/transactions/{transaction}/export', [PaymentController::class, 'export'])
            ->name('students.transactions.export');
    });

Route::prefix('app')
    ->group(function () {
        Route::get('payment/export', [PaymentController::class, 'guestExport'])
            ->name('payment.export');

        Route::get('transfer-certificate/media/{uuid}', [TransferCertificateController::class, 'downloadMedia'])
            ->name('transfer-certificate.media.download');
    });

Route::get('/auth/{provider}/redirect', [OAuthController::class, 'redirect']);
Route::get('/auth/{provider}/callback', [OAuthController::class, 'callback']);

Route::get('online-registrations/{number}/download', [OnlineRegistrationController::class, 'download'])->name('online-registrations.download');

Route::redirect('/log', 'log-viewer', 301);

Route::view('/livewire-test', 'livewire-test');

Route::get('/download/formats', DownloadFormatController::class)->name('download.formats');

Route::get('/test', TestController::class)->name('test');

Route::get('/my-ip', function () {
    return [
        'direct_ip' => request()->ip(),
        'forwarded_for' => request()->header('X-Forwarded-For'),
        'real_ip' => request()->header('X-Real-IP'),
        'all_ips' => request()->ips(),
    ];
});

// app route
Route::redirect('/app', '/app/login');

Route::get('/app/login', function () {
    return view('app');
})->where('vue', '[\/\w\.-]*')->name('app');

Route::get('/app/{vue?}', function () {
    return view('app');
})->where('vue', '[\/\w\.-]*')->name('app.dashboard');

// Fallback route
Route::fallback(function () {
    abort(404);
});
