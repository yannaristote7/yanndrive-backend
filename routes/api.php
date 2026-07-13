<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Middleware\CheckAllowedDomain;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\ActivityLogController;

// User avec role
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user()->load('role');
});

// Auth publique
Route::post('/register', [AuthController::class, 'register'])
    ->middleware([CheckAllowedDomain::class, 'throttle:3,1']);
Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['signed', 'throttle:5,1']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Documents
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::get('/documents/{id}/download', [DocumentController::class, 'download']);
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);

    // Partage interne par email
    Route::post('/documents/{id}/share', [DocumentController::class, 'share']);

    // Lien public ← la route manquante
    Route::post('/documents/{document}/public-link', [DocumentController::class, 'generatePublicLink']);

    // Admin logs
    Route::get('/admin/logs', [ActivityLogController::class, 'index']);
    Route::get('/admin/logs/stats', [ActivityLogController::class, 'stats']);
});

// Accès lien public (sans auth)
Route::get('/public/share/{token}', [DocumentController::class, 'accessPublicDocument'])
    ->name('public.share')
    ->middleware(['signed', 'throttle:5,1']);