<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;

/**
 * UserController
 *
 * ⚠️  FILE INI MENGANDUNG KERENTANAN YANG DISENGAJA UNTUK TUJUAN PEMBELAJARAN.
 *     JANGAN GUNAKAN KODE INI DI ENVIRONMENT PRODUCTION!
 *
 * Kerentanan yang ada:
 *  1. SQL Injection (baris ~35)   — OWASP A03:2021
 *  2. Mass Assignment (baris ~52) — OWASP A01:2021
 *  3. No Input Validation (baris ~60)
 */
class UserController extends Controller
{
    // =========================================================================
    // VULNERABILITY #1 — SQL Injection
    // CWE-89: Improper Neutralization of Special Elements used in an SQL Command
    // =========================================================================

    /**
     * Mengambil data user berdasarkan ID.
     *
     * BAHAYA: $id dari request langsung di-concat ke query string.
     * Attacker bisa input: 1 OR 1=1 -- untuk dump semua data.
     */
    public function getUserById(Request $request)
    {
        $id = $request->input('id');

        // ❌ VULNERABLE: Raw string concatenation — SQL Injection!
        $users = DB::select("SELECT * FROM users WHERE id = " . $id);

        return response()->json($users);
    }

    /**
     * Mencari user berdasarkan nama.
     *
     * BAHAYA: parameter 'name' langsung dimasukkan ke LIKE query.
     */
    public function searchUser(Request $request)
    {
        $name = $request->input('name');

        // ❌ VULNERABLE: SQL Injection via LIKE clause
        $users = DB::select("SELECT id, name, email FROM users WHERE name LIKE '%" . $name . "%'");

        return response()->json($users);
    }

    // =========================================================================
    // VULNERABILITY #2 — Mass Assignment
    // CWE-915: Improperly Controlled Modification of Dynamically-Determined Object Attributes
    // =========================================================================

    /**
     * Update profile user.
     *
     * BAHAYA: $request->all() menerima SEMUA field dari user,
     * termasuk 'is_admin', 'role', 'email_verified_at', dll.
     * Attacker bisa kirim: {"name":"hacker","is_admin":1}
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        // ❌ VULNERABLE: Mass Assignment — semua input diterima tanpa whitelist
        $user->update($request->all());

        return response()->json([
            'status'  => 'Profile updated',
            'user'    => $user,
        ]);
    }

    // =========================================================================
    // VULNERABILITY #3 — No Input Validation + Insecure Direct Object Reference
    // CWE-20 + CWE-639
    // =========================================================================

    /**
     * Menghapus user berdasarkan ID.
     *
     * BAHAYA: Tidak ada validasi apakah user yang login berhak menghapus user ini.
     * Tidak ada cek bahwa $id adalah integer valid.
     */
    public function deleteUser(Request $request)
    {
        $id = $request->input('id');  // ❌ Tidak divalidasi, bisa null atau string

        // ❌ VULNERABLE: IDOR — siapapun bisa hapus user lain tanpa otorisasi
        $deleted = DB::delete("DELETE FROM users WHERE id = " . $id);  // ❌ SQL Injection juga!

        return response()->json([
            'deleted' => $deleted,
        ]);
    }

    /**
     * Mengambil semua data user termasuk field sensitif.
     *
     * BAHAYA: Mengembalikan kolom password_hash, remember_token, dll.
     */
    public function getAllUsers()
    {
        // ❌ VULNERABLE: Sensitive Data Exposure — SELECT * tanpa filter field
        $users = DB::select("SELECT * FROM users");

        return response()->json($users);
    }
}
