<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventCategoryController;
use App\Http\Controllers\UniversityController;
use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
  Route::post('/login', [AuthController::class, 'loginWithCode'])->name('login');
  Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
  Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
  Route::post('/resend-code', [AuthController::class, 'resendCode'])->name('auth.resend_code');
});

Route::apiResource('universities', UniversityController::class)
  ->names(['index' => 'university.index'])
  ->middleware('auth:sanctum');

Route::apiResource('event-categories', EventCategoryController::class)
  ->names(['index' => 'event-categories.index'])
  ->middleware('auth:sanctum');

Route::apiResource('events', EventController::class)
  ->names([
    'index' => 'event.index',
    'store' => 'event.store',
    'update' => 'event.update',
    'destroy' => 'event.destroy',
  ])->middleware('auth:sanctum');
