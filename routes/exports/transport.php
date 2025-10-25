<?php

use App\Http\Controllers\Transport\CircleExportController;
use App\Http\Controllers\Transport\FeeExportController;
use App\Http\Controllers\Transport\Report\BatchWiseRouteExportController;
use App\Http\Controllers\Transport\Report\RouteWiseStudentExportController;
use App\Http\Controllers\Transport\RouteController;
use App\Http\Controllers\Transport\RouteExportController;
use App\Http\Controllers\Transport\StoppageExportController;
use App\Http\Controllers\Transport\Vehicle\DocumentController;
use App\Http\Controllers\Transport\Vehicle\DocumentExportController;
use App\Http\Controllers\Transport\Vehicle\FuelRecordController;
use App\Http\Controllers\Transport\Vehicle\FuelRecordExportController;
use App\Http\Controllers\Transport\Vehicle\ServiceRecordController;
use App\Http\Controllers\Transport\Vehicle\ServiceRecordExportController;
use App\Http\Controllers\Transport\Vehicle\TravelRecordController;
use App\Http\Controllers\Transport\Vehicle\TravelRecordExportController;
use App\Http\Controllers\Transport\Vehicle\VehicleExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('transport')->name('transport.')->group(function () {
    Route::get('stoppages/export', StoppageExportController::class)->middleware('permission:transport-stoppage:manage')->name('stoppages.export');

    Route::get('routes/{route}/export', [RouteController::class, 'export']);

    Route::get('routes/export', RouteExportController::class)->middleware('permission:transport-route:export')->name('routes.export');

    Route::get('circles/export', CircleExportController::class)->middleware('permission:transport-circle:export')->name('circles.export');

    Route::get('fees/export', FeeExportController::class)->middleware('permission:transport-fee:export')->name('fees.export');

    Route::get('vehicles/export', VehicleExportController::class)->middleware('permission:vehicle:export')->name('vehicles.export');

    Route::get('reports/batch-wise-route/export', BatchWiseRouteExportController::class)->middleware('permission:transport:report')->name('reports.batch-wise-route.export');

    Route::get('reports/route-wise-student/export', RouteWiseStudentExportController::class)->middleware('permission:transport:report')->name('reports.route-wise-student.export');
});

Route::prefix('transport/vehicle')->name('transport.vehicle.')->group(function () {
    Route::get('documents/{document}/media/{uuid}', [DocumentController::class, 'downloadMedia']);

    Route::get('documents/export', DocumentExportController::class)->middleware('permission:document:export')->name('documents.export');

    Route::get('fuel-records/{fuel_record}/media/{uuid}', [FuelRecordController::class, 'downloadMedia']);

    Route::get('fuel-records/export', FuelRecordExportController::class)->middleware('permission:fuel-record:export')->name('fuel-records.export');

    Route::get('travel-records/{travel_record}/media/{uuid}', [TravelRecordController::class, 'downloadMedia']);

    Route::get('travel-records/export', TravelRecordExportController::class)->middleware('permission:travel-record:export')->name('travel-records.export');

    Route::get('service-records/{service_record}/media/{uuid}', [ServiceRecordController::class, 'downloadMedia']);

    Route::get('service-records/export', ServiceRecordExportController::class)->middleware('permission:service-record:export')->name('service-records.export');
});
