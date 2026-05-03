<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileAttendanceController;
use App\Http\Controllers\Api\MobilePayslipController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('mobile')->group(function () {
    Route::post('login', [MobileAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [MobileAuthController::class, 'me']);
        Route::post('logout', [MobileAuthController::class, 'logout']);
        Route::get('attendances/status', [MobileAttendanceController::class, 'status']);
        Route::get('attendances/history', [MobileAttendanceController::class, 'history']);
        Route::post('attendances/submit', [MobileAttendanceController::class, 'submit']);
        Route::get('payslips', [MobilePayslipController::class, 'index']);
        Route::get('payslips/{payroll}', [MobilePayslipController::class, 'show']);
    });
});
