<?php

use App\Http\Controllers\Api\AnomalyController;
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConsumptionReadingController;
use App\Http\Controllers\Api\DailyConsumptionSummaryController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\DeviceModelController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\DeviceTypeController;
use App\Http\Controllers\Api\ScheduleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::post('/devices/{device}/readings', [ConsumptionReadingController::class, 'store'])
    ->middleware('verify.device.token');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('areas', AreaController::class);
    Route::apiResource('device-types', DeviceTypeController::class);
    Route::apiResource('device-models', DeviceModelController::class);

    Route::apiResource('devices', DeviceController::class);
    Route::post('/devices/{device}/regenerate-token', [DeviceController::class, 'regenerateToken']);

    Route::apiResource('schedules', ScheduleController::class);

    Route::get('/consumption-readings', [ConsumptionReadingController::class, 'index']);
    Route::get('/consumption-readings/{consumptionReading}', [ConsumptionReadingController::class, 'show']);

    Route::get('/anomalies', [AnomalyController::class, 'index']);
    Route::get('/anomalies/{anomaly}', [AnomalyController::class, 'show']);
    Route::post('/anomalies/{anomaly}/review', [AnomalyController::class, 'review']);

    Route::get('/daily-consumption-summaries', [DailyConsumptionSummaryController::class, 'index']);
    Route::get('/daily-consumption-summaries/{dailyConsumptionSummary}', [DailyConsumptionSummaryController::class, 'show']);

    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::delete('/device-tokens/{deviceToken}', [DeviceTokenController::class, 'destroy']);
});
