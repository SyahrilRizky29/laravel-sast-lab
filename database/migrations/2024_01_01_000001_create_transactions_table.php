<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending');
            $table->decimal('refund_amount', 15, 2)->nullable();
            $table->string('gateway_transaction_id')->nullable();

            // ❌ VULNERABILITY: payment_signature disimpan plaintext di DB
            // Seharusnya tidak perlu disimpan, cukup di-verify saat terima webhook
            $table->string('payment_signature')->nullable();

            $table->timestamps();

            // ❌ VULNERABILITY: Tidak ada foreign key constraint ke users
            // Seharusnya: $table->foreign('user_id')->references('id')->on('users')
        });

        Schema::create('credit_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');

            // ❌ VULNERABILITY: Nomor kartu kredit, CVV disimpan plaintext!
            // PCI-DSS melarang penyimpanan CVV sama sekali, dan card_number harus dienkripsi/tokenisasi
            $table->string('card_number');        // Seharusnya: tokenisasi via payment gateway
            $table->string('cvv');                // DILARANG disimpan! (PCI-DSS Req 3.2)
            $table->string('expiry_date');
            $table->string('cardholder_name');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_cards');
        Schema::dropIfExists('transactions');
    }
};
