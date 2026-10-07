<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes — laravel-sast-lab
|
| ⚠️  FILE INI MENGANDUNG KERENTANAN YANG DISENGAJA UNTUK TUJUAN PEMBELAJARAN.
|     JANGAN GUNAKAN KODE INI DI ENVIRONMENT PRODUCTION!
|--------------------------------------------------------------------------
*/

// =============================================================================
// VULNERABILITY — CSRF Protection Disabled
// CWE-352: Cross-Site Request Forgery (CSRF)
// =============================================================================

// ❌ VULNERABLE: withoutMiddleware(VerifyCsrfToken) untuk route-route kritis.
//    Attacker bisa membuat form di website mereka yang men-submit ke endpoint ini.
//    Karena browser otomatis menyertakan cookie session, request akan dianggap sah.
Route::withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])->group(function () {

    // Auth routes — CSRF disabled!
    Route::post('/auth/login',    [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/reset',    [AuthController::class, 'resetPassword']);

    // User routes — CSRF disabled!
    Route::post('/user/update',   [UserController::class, 'updateProfile']);
    Route::post('/user/delete',   [UserController::class, 'deleteUser']);

    // File routes — CSRF disabled!
    Route::post('/file/upload',   [FileController::class, 'uploadFile']);
    Route::post('/file/convert',  [FileController::class, 'convertImage']);
});

// =============================================================================
// Routes tanpa autentikasi — Missing Authentication
// CWE-306: Missing Authentication for Critical Function
// =============================================================================

// ❌ VULNERABLE: Route sensitif tanpa middleware 'auth'
// Seharusnya semua route di bawah ini dibungkus: Route::middleware('auth')->group(...)

// User endpoints
Route::get('/user',            [UserController::class, 'getUserById']);    // ❌ Tidak perlu login
Route::get('/users',           [UserController::class, 'getAllUsers']);    // ❌ Expose semua data user!
Route::get('/user/search',     [UserController::class, 'searchUser']);

// File endpoints
Route::get('/file',            [FileController::class, 'readFile']);      // ❌ Path traversal tanpa auth
Route::post('/file/info',      [FileController::class, 'getFileInfo']);
Route::delete('/file',         [FileController::class, 'deleteFile']);

// Payment endpoints
Route::get('/payment/card',    [PaymentController::class, 'getCardDetails']);  // ❌ Data kartu kredit!
Route::get('/transaction',     [PaymentController::class, 'getTransaction']);

// =============================================================================
// Routes lain
// =============================================================================

// Payment webhook — tidak ada verifikasi signature
Route::post('/payment/webhook',   [PaymentController::class, 'paymentWebhook']);
Route::get('/payment/callback',   [PaymentController::class, 'paymentCallback']);

// ❌ VULNERABLE: Route admin tanpa middleware 'can:admin' atau 'role:admin'
Route::post('/admin/refund',      [PaymentController::class, 'processRefund']);

Route::get('/devsecops-test', function () {
    $userInput = $_GET['id'] ?? '';
    eval($userInput); // Sengaja dibuat vulnerable
    return "Testing DevSecOps";
});