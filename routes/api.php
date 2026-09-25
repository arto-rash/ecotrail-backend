<?php

use App\Http\Controllers\Api\GuideApplicationController;
use App\Http\Controllers\Api\MapController;
use App\Http\Controllers\Api\MetaController;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\ReviewController;
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
Route::get('/trails/{trail}/gpx', [TrailController::class, 'gpx']);
Route::post('/trails/{trail}/reviews', [ReviewController::class, 'storeForTrail'])->middleware('throttle:5,60');

Route::get('/places', [PlaceController::class, 'index']);
Route::get('/places/{place}', [PlaceController::class, 'show']);
Route::get('/places/{place}/nearby', [PlaceController::class, 'nearby']);
Route::post('/places/{place}/reviews', [ReviewController::class, 'storeForPlace'])->middleware('throttle:5,60');

Route::get('/map', MapController::class);
Route::get('/regions', [MetaController::class, 'regions']);
Route::get('/districts', [MetaController::class, 'districts']);
Route::get('/guides', [MetaController::class, 'guides']);
Route::post('/guide-applications', [GuideApplicationController::class, 'store'])->middleware('throttle:5,60');
Route::get('/stats', [MetaController::class, 'stats']);
Route::get('/options', [MetaController::class, 'options']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
