<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactRequestController;
use App\Http\Controllers\EventCategoryController;
use App\Http\Controllers\UniversityController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventSeriesController;
use App\Http\Controllers\EventActionController;
use App\Http\Controllers\EventParticipationController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
  Route::post('/login', [AuthController::class, 'loginWithCode'])->name('login');
  Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
  Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
  Route::post('/resend-code', [AuthController::class, 'resendCode'])->name('auth.resend_code');
});

Route::apiResource('universities', UniversityController::class)
  ->names(['index' => 'university.index']);

Route::apiResource('event-categories', EventCategoryController::class)
  ->names(['index' => 'event-categories.index'])
  ->middleware('auth:sanctum');

Route::get('events/feed', [EventController::class, 'feed'])->middleware('auth:sanctum')->name('events.feed');

Route::apiResource('events', EventController::class)
  ->names([
    'index' => 'event.index',
    'store' => 'event.store',
    'update' => 'event.update',
    'destroy' => 'event.destroy',
  ])->middleware('auth:sanctum');

Route::post('event-series', [EventSeriesController::class, 'store'])->middleware('auth:sanctum');
Route::put('event-series/{repeatCode}', [EventSeriesController::class, 'update'])->middleware('auth:sanctum');
Route::delete('event-series/{repeatCode}', [EventSeriesController::class, 'destroy'])->middleware('auth:sanctum');

Route::apiResource('contact-requests', ContactRequestController::class)
  ->names([
    'index' => 'contact_request.index',
    'store' => 'contact_request.store',
    'update' => 'contact_request.update',
    'destroy' => 'contact_request.destroy',
  ])->middleware('auth:sanctum');


Route::apiResource('event-actions', EventActionController::class)
  ->names([
    'index' => 'event_action.index',
    'store' => 'event_action.store',
    'update' => 'event_action.update',
    'destroy' => 'event_action.destroy',
  ])->middleware('auth:sanctum');

Route::post('admin/event-participations/{event_id}/users/{user_id}', [EventParticipationController::class, 'addParticipant'])->middleware('auth:sanctum');
Route::delete('admin/event-participations/{event_id}/users/{user_id}', [EventParticipationController::class, 'removeParticipant'])->middleware('auth:sanctum');
Route::post('event-participations', [EventParticipationController::class, 'store'])->middleware('auth:sanctum');
Route::put('event-participations', [EventParticipationController::class, 'update'])->middleware('auth:sanctum');
Route::delete('event-participations', [EventParticipationController::class, 'destroy'])->middleware('auth:sanctum');
