<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Users Table
 *
 * ⚠️  FILE INI MENGANDUNG KERENTANAN YANG DISENGAJA UNTUK TUJUAN PEMBELAJARAN.
 *
 * Kerentanan pada skema database:
 *  1. Password tidak di-hash pada level DB (serahkan ke aplikasi — tapi aplikasi juga tidak hash!)
 *  2. Kolom is_admin tanpa default yang aman
 *  3. Tidak ada kolom audit trail (created_by, updated_by)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();

            // ❌ VULNERABILITY: password disimpan plaintext oleh AuthController
            // Kolom ini dirancang untuk menerima plaintext maupun hash — seharusnya hanya hash
            $table->string('password');

            // ❌ VULNERABILITY: is_admin tanpa default false yang eksplisit di semua flow
            $table->boolean('is_admin')->default(false);
            $table->string('role')->default('user');

            // ❌ VULNERABILITY: reset_token tanpa expiry time dan tidak di-hash
            $table->string('reset_token')->nullable();

            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
