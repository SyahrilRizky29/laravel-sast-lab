<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User Model
 *
 * ⚠️  FILE INI MENGANDUNG KERENTANAN YANG DISENGAJA UNTUK TUJUAN PEMBELAJARAN.
 *
 * Kerentanan yang ada:
 *  1. Tidak ada $fillable  — Mass Assignment rentan (CWE-915)
 *  2. $hidden tidak lengkap — Sensitive field ter-ekspos
 */
class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    // =========================================================================
    // VULNERABILITY — Tidak Ada $fillable (Mass Assignment Protection Off)
    // =========================================================================

    // ❌ VULNERABLE: $guarded = [] berarti SEMUA field bisa di-mass assign
    //    termasuk: is_admin, role, email_verified_at, password, dsb.
    // Seharusnya: definisikan $fillable = ['name', 'email', 'phone']
    protected $guarded = [];

    // =========================================================================
    // VULNERABILITY — $hidden Tidak Mencakup Field Sensitif
    // =========================================================================

    // ❌ VULNERABLE: reset_token dan is_admin masih ter-ekspos di JSON response
    // Seharusnya tambahkan: 'reset_token', 'is_admin', 'remember_token'
    protected $hidden = [
        'password',  // Hanya menyembunyikan password, bukan field sensitif lainnya!
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        // ❌ VULNERABLE: is_admin tidak di-cast ke boolean
        // Tanpa cast, bisa dimanipulasi via string "true" / "1"
    ];

    // Kolom sensitif yang tidak disembunyikan dari response:
    // - is_admin          → privilege escalation
    // - role              → privilege escalation
    // - reset_token       → account takeover
    // - remember_token    → session hijacking
}
