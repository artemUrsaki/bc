<?php

use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\ExperimentController;
use App\Http\Controllers\Api\V1\ProbeController;
use App\Http\Controllers\Api\V1\MeasurementController;
use App\Http\Controllers\Api\V1\RunController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function (): void {
    Route::get('/devices', [DeviceController::class, 'index']);
    Route::get('/devices/{device}', [DeviceController::class, 'show']);
    Route::get('/devices/{device}/latest', [MeasurementController::class, 'latest']);
    Route::get('/devices/{device}/measurements', [MeasurementController::class, 'index']);

    Route::get('/experiments', [ExperimentController::class, 'index']);
    Route::post('/experiments', [ExperimentController::class, 'store']);
    Route::get('/experiments/{experiment}', [ExperimentController::class, 'show']);

    Route::get('/runs', [RunController::class, 'index']);
    Route::post('/runs', [RunController::class, 'store']);
    Route::get('/runs/{run}', [RunController::class, 'show']);
    Route::get('/runs/{run}/aggregates', [RunController::class, 'aggregates']);
    Route::get('/runs/{run}/samples', [RunController::class, 'samples']);
    Route::get('/runs/{run}/events', [RunController::class, 'events']);
    Route::get('/runs/{run}/export', [RunController::class, 'export']);

    Route::match(['GET', 'POST'], '/probe/http-echo', [ProbeController::class, 'httpEcho']);
});
