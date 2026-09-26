<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Middleware\CheckAllowedDomain;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Api\Admin\DomainController as AdminDomainController;
use App\Http\Controllers\Api\Admin\StatsController as AdminStatsController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;

// User avec role
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user()->load('role');
});

// Auth publique
Route::post('/register', [AuthController::class, 'register'])
    ->middleware([CheckAllowedDomain::class, 'throttle:3,1']);
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/storage-usage', [DocumentController::class, 'storageUsage']);

    // Documents
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::get('/documents/{id}/download', [DocumentController::class, 'download']);
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);

    // Partage interne par email
    Route::post('/documents/{id}/share', [DocumentController::class, 'share'])->middleware('throttle:5,1');

    // Lien public ← la route manquante
    Route::post('/documents/{document}/public-link', [DocumentController::class, 'generatePublicLink'])->middleware('throttle:5,1');

    // Panneau d'administration
    Route::middleware('can:admin')->prefix('admin')->group(function () {
        Route::get('/stats', AdminStatsController::class);

        Route::get('/logs', [ActivityLogController::class, 'index']);
        Route::get('/logs/stats', [ActivityLogController::class, 'stats']);

        Route::get('/roles', [AdminUserController::class, 'roles']);
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::patch('/users/{user}', [AdminUserController::class, 'update']);
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
        Route::post('/users/{user}/revoke-tokens', [AdminUserController::class, 'revokeTokens']);

        Route::get('/domains', [AdminDomainController::class, 'index']);
        Route::post('/domains', [AdminDomainController::class, 'store']);
        Route::delete('/domains/{domain}', [AdminDomainController::class, 'destroy']);
    });
});

// Accès lien public (sans auth)
Route::get('/public/share/{token}', [DocumentController::class, 'accessPublicDocument'])
    ->name('public.share')
    ->middleware(['signed', 'throttle:5,1']);