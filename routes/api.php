<?php

use App\Http\Controllers\MeasurementsController;
use Illuminate\Support\Facades\Route;

Route::get('/measurements', [MeasurementsController::class, 'index']);
Route::post('/measurements', [MeasurementsController::class, 'store']);
