<?php

use App\Http\Controllers\MeasurementsController;
use App\Http\Controllers\SensorModeController;
use Illuminate\Support\Facades\Route;

Route::get('/measurements', [MeasurementsController::class, 'index']);
Route::post('/measurements', [MeasurementsController::class, 'store']);
Route::get('/sensor-mode', [SensorModeController::class, 'show']);
Route::put('/sensor-mode', [SensorModeController::class, 'update']);
