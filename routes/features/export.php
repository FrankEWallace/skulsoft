<?php

use App\Http\Controllers\CustomFieldExportController;
use App\Http\Controllers\OptionExportController;
use App\Http\Controllers\Team\RoleAndPermissionExport;
use App\Http\Controllers\Team\RoleExportController;
use App\Http\Controllers\TeamExportController;
use App\Http\Controllers\UserExportController;
use App\Http\Controllers\Utility\ActivityLogExportController;
use App\Http\Controllers\Utility\BackupExportController;
use App\Http\Controllers\Utility\TodoExportController;
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

// export route
Route::get('teams/export', TeamExportController::class)->middleware('permission:team:manage');
Route::get('teams/{team}/roles/export', RoleExportController::class)->middleware('permission:team:manage');
Route::get('custom-fields/export', CustomFieldExportController::class)->middleware('permission:custom-field:manage');
Route::get('utility/todos/export', TodoExportController::class)->middleware('permission:todo:export');
Route::get('utility/activity-logs/export', ActivityLogExportController::class)->middleware('permission:activity-log:export');
Route::get('utility/backups/export', BackupExportController::class)->middleware('permission:backup:manage');
Route::get('users/export', UserExportController::class)->middleware('permission:user:export');
Route::get('options/export', OptionExportController::class)->middleware('option.verifier');

Route::get('roles-and-permissions', RoleAndPermissionExport::class)
    ->middleware('role:admin');
