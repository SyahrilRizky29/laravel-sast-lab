<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PaymentController
 *
 * ⚠️  FILE INI MENGANDUNG KERENTANAN YANG DISENGAJA UNTUK TUJUAN PEMBELAJARAN.
 *     JANGAN GUNAKAN KODE INI DI ENVIRONMENT PRODUCTION!
 *
 * Kerentanan yang ada:
 *  1. Insecure Direct Object Reference / IDOR (baris ~35) — CWE-639
 *  2. Missing Authorization Check (baris ~55)             — CWE-862
 *  3. Sensitive Data Exposure (baris ~80)                 — CWE-200
 *  4. Unvalidated Redirect (baris ~100)                   — CWE-601
 */
class PaymentController extends Controller
{
    // Hardcoded payment gateway secret — VULNERABILITY!
    private string $paymentGatewaySecret = 'pg_secret_live_abc123xyz456';  // ❌ CWE-798
    private string $midtransServerKey    = 'Mid-server-XXXXXX-LIVE-KEY';   // ❌ CWE-798

    // =========================================================================
    // VULNERABILITY #1 — Insecure Direct Object Reference (IDOR)
    // CWE-639: Authorization Bypass Through User-Controlled Key
    // =========================================================================

    /**
     * Mengambil detail transaksi berdasarkan transaction_id.
     *
     * BAHAYA: Tidak ada cek apakah transaksi milik user yang sedang login.
     * Attacker bisa mengakses transaksi user lain hanya dengan mengubah transaction_id.
     */
    public function getTransaction(Request $request)
    {
        $transactionId = $request->input('transaction_id');

        // ❌ VULNERABLE: IDOR — tidak ada WHERE user_id = auth()->id()
        $transaction = DB::select(
            "SELECT * FROM transactions WHERE id = " . $transactionId  // SQL Injection juga!
        );

        return response()->json($transaction);
    }

    // =========================================================================
    // VULNERABILITY #2 — Missing Authorization
    // CWE-862: Missing Authorization
    // =========================================================================

    /**
     * Melakukan refund transaksi.
     *
     * BAHAYA: Tidak ada validasi role/permission.
     * Semua user yang terautentikasi bisa melakukan refund transaksi manapun.
     */
    public function processRefund(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $amount        = $request->input('amount');

        // ❌ VULNERABLE: Tidak ada cek role admin atau ownership transaksi
        // Seharusnya: $this->authorize('refund', $transaction);

        DB::update(
            // ❌ SQL Injection juga!
            "UPDATE transactions SET status = 'refunded', refund_amount = {$amount} WHERE id = {$transactionId}"
        );

        Log::info("Refund processed", [
            'transaction_id' => $transactionId,
            'amount'         => $amount,
            'gateway_secret' => $this->paymentGatewaySecret, // ❌ Secret key masuk ke log!
        ]);

        return response()->json(['status' => 'refunded', 'amount' => $amount]);
    }

    // =========================================================================
    // VULNERABILITY #3 — Sensitive Data Exposure
    // CWE-200: Exposure of Sensitive Information to an Unauthorized Actor
    // =========================================================================

    /**
     * Mengambil detail kartu kredit user.
     *
     * BAHAYA: Mengembalikan data kartu kredit secara penuh (nomor, CVV, dsb).
     */
    public function getCardDetails(Request $request)
    {
        $userId = $request->input('user_id');

        // ❌ VULNERABLE: SELECT * pada tabel yang berisi data sensitif
        $cards = DB::select("SELECT * FROM credit_cards WHERE user_id = {$userId}");
        // Mengembalikan: card_number, cvv, expiry_date, cardholder_name — semua!

        return response()->json($cards);
        // Seharusnya: Hanya kembalikan 4 digit terakhir, JANGAN kembalikan CVV
    }

    // =========================================================================
    // VULNERABILITY #4 — Open Redirect / Unvalidated Redirect
    // CWE-601: URL Redirection to Untrusted Site ('Open Redirect')
    // =========================================================================

    /**
     * Redirect setelah payment selesai.
     *
     * BAHAYA: URL redirect dari parameter user tanpa validasi.
     * Attacker bisa phishing: /payment/callback?redirect=https://evil-site.com
     */
    public function paymentCallback(Request $request)
    {
        $status      = $request->input('status');
        $redirectUrl = $request->input('redirect');  // URL dari user!

        if ($status === 'success') {
            Log::info("Payment success, redirecting to: {$redirectUrl}");

            // ❌ VULNERABLE: Open Redirect — redirect ke URL sembarang dari user
            return redirect($redirectUrl);
            // Seharusnya: Validasi bahwa $redirectUrl ada di whitelist domain
        }

        return response()->json(['status' => 'payment failed']);
    }

    /**
     * Webhook dari payment gateway.
     *
     * BAHAYA: Tidak ada validasi signature/HMAC dari payment gateway.
     */
    public function paymentWebhook(Request $request)
    {
        $payload       = $request->all();
        $transactionId = $payload['transaction_id'] ?? null;
        $status        = $payload['status'] ?? null;

        // ❌ VULNERABLE: Tidak ada verifikasi bahwa request benar-benar dari payment gateway
        // Seharusnya: hash_hmac('sha256', $rawPayload, $this->paymentGatewaySecret) == $signature

        if ($status === 'success') {
            // ❌ Siapapun bisa kirim webhook palsu dan men-trigger status paid!
            DB::update(
                "UPDATE transactions SET status = 'paid' WHERE id = '{$transactionId}'"
            );
        }

        return response()->json(['received' => true]);
    }
}
