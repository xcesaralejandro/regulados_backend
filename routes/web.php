<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::prefix('auth')->group(function () {
    Route::get('/canvas', [AuthController::class, 'loginWithCanvas'])->name('auth.canvas');
});
