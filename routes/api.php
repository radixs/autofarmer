<?php

use App\Http\Controllers\TestEntryController;
use Illuminate\Support\Facades\Route;

Route::get('/test-entries', [TestEntryController::class, 'index']);
Route::post('/test-entries', [TestEntryController::class, 'store']);
