<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;

/**
 * AuthController
 *
 * ⚠️  FILE INI MENGANDUNG KERENTANAN YANG DISENGAJA UNTUK TUJUAN PEMBELAJARAN.
 *     JANGAN GUNAKAN KODE INI DI ENVIRONMENT PRODUCTION!
 *
 * Kerentanan yang ada:
 *  1. Hardcoded Credentials (baris ~30) — CWE-798
 *  2. Sensitive Data in Log (baris ~55) — CWE-532
 *  3. Weak/No Password Hashing (baris ~68)
 *  4. Broken Authentication — hardcoded token (baris ~72)
 */
class AuthController extends Controller
{
    // =========================================================================
    // VULNERABILITY #1 — Hardcoded Credentials
    // CWE-798: Use of Hard-coded Credentials
    // =========================================================================

    // ❌ VULNERABLE: Credential admin di-hardcode langsung di source code.
    //    Siapapun yang akses repository bisa melihat ini.
    private string $adminEmail    = 'admin@company.com';
    private string $adminPassword = 'Admin@123!Secret';      // HARDCODED PASSWORD!
    private string $dbPassword    = 'mysql_root_pass_2024';  // HARDCODED DB PASSWORD!
    private string $apiKey        = 'sk-prod-abc123xyz789secretkey'; // HARDCODED API KEY!

    // =========================================================================
    // VULNERABILITY #2 — Sensitive Data Exposure via Logging
    // CWE-532: Insertion of Sensitive Information into Log File
    // =========================================================================

    /**
     * Login user.
     *
     * BAHAYA:
     *   - Password user di-log dalam plaintext
     *   - Email + password tercatat di log file yang mungkin tidak terenkripsi
     *   - Log file bisa diakses banyak pihak (DevOps, logging service, dsb)
     */
    public function login(Request $request)
    {
        $email    = $request->input('email');
        $password = $request->input('password');

        // ❌ VULNERABLE: Password plaintext dicatat di log
        Log::info("Login attempt: email={$email} password={$password}");
        Log::debug("Auth debug", ['email' => $email, 'password' => $password, 'ip' => $request->ip()]);

        // ❌ VULNERABLE: Hardcoded credential check
        if ($email === $this->adminEmail && $password === $this->adminPassword) {
            Log::info("Admin login SUCCESS: {$email}");

            // ❌ VULNERABLE: Hardcoded token — semua admin dapat token yang sama!
            return response()->json([
                'token'   => 'hardcoded-admin-token-abc123',
                'role'    => 'admin',
                'message' => 'Login berhasil',
            ]);
        }

        // ❌ VULNERABLE: No rate limiting, no brute force protection
        $user = DB::select("SELECT * FROM users WHERE email = '{$email}'"); // SQL Injection juga!

        if (!$user) {
            // ❌ VULNERABLE: Error message terlalu informatif — mengungkap bahwa email tidak ada
            return response()->json(['error' => "Email {$email} tidak ditemukan di sistem"], 404);
        }

        // ❌ VULNERABLE: Membandingkan password tanpa hashing
        if ($user[0]->password === $password) {  // No password_verify()!
            return response()->json(['token' => 'user-token-' . $user[0]->id]);
        }

        return response()->json(['error' => 'Password salah'], 401);
    }

    // =========================================================================
    // VULNERABILITY #3 — Weak Password Storage (No Hashing)
    // CWE-916: Use of Password Hash With Insufficient Computational Effort
    // =========================================================================

    /**
     * Register user baru.
     *
     * BAHAYA: Password disimpan dalam plaintext di database.
     */
    public function register(Request $request)
    {
        $email    = $request->input('email');
        $name     = $request->input('name');
        $password = $request->input('password');

        // ❌ VULNERABLE: Password disimpan TANPA hashing (plaintext!)
        // Seharusnya: bcrypt($password) atau Hash::make($password)
        DB::insert(
            "INSERT INTO users (name, email, password) VALUES ('{$name}', '{$email}', '{$password}')"
            // ❌ SQL Injection juga! Dan password plaintext!
        );

        Log::info("New user registered: {$email} with password: {$password}"); // ❌ Log password!

        return response()->json(['message' => 'Registrasi berhasil']);
    }

    // =========================================================================
    // VULNERABILITY #4 — Insecure Password Reset
    // CWE-640: Weak Password Recovery Mechanism for Forgotten Password
    // =========================================================================

    /**
     * Reset password.
     *
     * BAHAYA: Token reset bersifat predictable (timestamp + email).
     */
    public function resetPassword(Request $request)
    {
        $email = $request->input('email');

        // ❌ VULNERABLE: Token yang predictable — attacker bisa tebak/brute force
        $resetToken = md5($email . time());  // md5 sudah deprecated untuk keamanan!

        // ❌ VULNERABLE: Token disimpan tanpa expiry time
        DB::update("UPDATE users SET reset_token = '{$resetToken}' WHERE email = '{$email}'");

        Log::info("Password reset requested for: {$email}, token: {$resetToken}"); // ❌ Token di log!

        return response()->json([
            'message' => 'Token reset dikirim',
            'debug_token' => $resetToken, // ❌ Token dikembalikan ke response! (info disclosure)
        ]);
    }
}
