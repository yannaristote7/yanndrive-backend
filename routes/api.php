<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Middleware\CheckAllowedDomain;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\ActivityLogController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/register', [AuthController::class, 'register'])
    ->middleware(CheckAllowedDomain::class);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/documents', [DocumentController::class, 'store']);
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::get('/documents/{id}/download', [DocumentController::class, 'download']);
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);
    Route::post('/documents/{id}/share', [DocumentController::class, 'share']);
    Route::get('/admin/logs', [ActivityLogController::class, 'index']);
    Route::get('/admin/logs/stats', [ActivityLogController::class, 'stats']);

});
Route::middleware('auth:sanctum')->post(
    '/documents/{document}/share',
    [DocumentController::class, 'generatePublicLink']
);
Route::get('/public/share/{token}',
    [DocumentController::class, 'accessPublicDocument']
)->name('public.share')
 ->middleware(['signed', 'throttle:5,1']);
 Route::middleware('throttle:5,1')
    ->get('/public/share/{token}',
    [DocumentController::class, 'accessPublicDocument']);Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user()->load('role');
});
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user()->load('role');
});




