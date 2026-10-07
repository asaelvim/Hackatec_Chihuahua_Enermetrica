<?php

use App\Http\Controllers\Api\ConsumptionReadingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/devices/{device}/readings', [ConsumptionReadingController::class, 'store'])
    ->middleware('verify.device.token');
