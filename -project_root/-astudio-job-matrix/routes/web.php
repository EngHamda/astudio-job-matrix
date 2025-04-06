<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JobController;

Route::get('/', function () {
    return view('welcome');
});

// API Routes
Route::prefix('api')->middleware('api')->group(function () {
    Route::get('/jobs', [JobController::class, 'index']);
});
