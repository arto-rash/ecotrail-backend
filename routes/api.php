<?php

use App\Http\Controllers\Api\MetaController;
use App\Http\Controllers\Api\TrailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'app' => config('app.name'),
    'time' => now()->toIso8601String(),
]));

Route::get('/trails', [TrailController::class, 'index']);
Route::post('/trails', [TrailController::class, 'store'])->middleware('throttle:10,60');
Route::get('/trails/{trail}', [TrailController::class, 'show']);
Route::get('/trails/{trail}/nearby', [TrailController::class, 'nearby']);

Route::get('/regions', [MetaController::class, 'regions']);
Route::get('/guides', [MetaController::class, 'guides']);
Route::get('/stats', [MetaController::class, 'stats']);
Route::get('/options', [MetaController::class, 'options']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
