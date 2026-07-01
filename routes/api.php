<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'loginWithCode'])->name('login');
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
Route::post('/auth/register', [AuthController::class, 'register'])->name('register');
Route::post('/auth/resend-code', [AuthController::class, 'resendCode'])->name('auth.resend_code');
