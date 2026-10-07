<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes — laravel-sast-lab
|
| ⚠️  FILE INI MENGANDUNG KERENTANAN YANG DISENGAJA UNTUK TUJUAN PEMBELAJARAN.
|--------------------------------------------------------------------------
*/

// =============================================================================
// VULNERABILITY — API Routes tanpa Rate Limiting
// CWE-307: Improper Restriction of Excessive Authentication Attempts
// =============================================================================

// ❌ VULNERABLE: Tidak ada throttle middleware → brute force tidak dilindungi
// Seharusnya: Route::middleware(['throttle:5,1'])->group(...)
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// =============================================================================
// VULNERABILITY — Sensitive Endpoints tanpa Autentikasi di API
// =============================================================================

// ❌ VULNERABLE: Endpoint ini seharusnya membutuhkan auth:sanctum atau auth:api
Route::get('/users',         [UserController::class, 'getAllUsers']);
Route::get('/user',          [UserController::class, 'getUserById']);
Route::get('/transaction',   [PaymentController::class, 'getTransaction']);
Route::get('/card',          [PaymentController::class, 'getCardDetails']);

// Route dengan autentikasi (contoh yang benar — untuk perbandingan)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', function () {
        return auth()->user()->only(['id', 'name', 'email']); // Correct: filter fields!
    });
});
