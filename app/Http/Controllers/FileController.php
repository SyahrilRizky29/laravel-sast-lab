<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * FileController
 *
 * ⚠️  FILE INI MENGANDUNG KERENTANAN YANG DISENGAJA UNTUK TUJUAN PEMBELAJARAN.
 *     JANGAN GUNAKAN KODE INI DI ENVIRONMENT PRODUCTION!
 *
 * Kerentanan yang ada:
 *  1. Path Traversal / Directory Traversal (baris ~30) — CWE-22
 *  2. Unrestricted File Upload (baris ~60)             — CWE-434
 *  3. Command Injection (baris ~90)                    — CWE-78
 */
class FileController extends Controller
{
    // =========================================================================
    // VULNERABILITY #1 — Path Traversal
    // CWE-22: Improper Limitation of a Pathname to a Restricted Directory
    // =========================================================================

    /**
     * Mengambil konten file berdasarkan nama file dari request.
     *
     * BAHAYA: Attacker bisa input "../../../etc/passwd" untuk membaca file sistem.
     */
    public function readFile(Request $request)
    {
        $filename = $request->input('filename');

        // ❌ VULNERABLE: Path Traversal — tidak ada sanitasi terhadap '../'
        $path = storage_path('app/uploads/' . $filename);

        if (!file_exists($path)) {
            return response()->json(['error' => 'File tidak ditemukan'], 404);
        }

        // ❌ VULNERABLE: Membaca dan mengembalikan konten file sembarang
        $content = file_get_contents($path);

        return response()->json([
            'filename' => $filename,
            'content'  => $content, // Bisa berisi /etc/passwd, .env, dsb!
        ]);
    }

    /**
     * Menghapus file berdasarkan path.
     *
     * BAHAYA: Attacker bisa hapus file kritis sistem dengan path traversal.
     */
    public function deleteFile(Request $request)
    {
        $filename = $request->input('filename');

        // ❌ VULNERABLE: Path Traversal + unlink tanpa validasi
        $path = '/var/www/html/storage/' . $filename;  // Hardcoded path!
        unlink($path);  // Bisa hapus file apapun yang bisa diakses web server!

        return response()->json(['deleted' => $filename]);
    }

    // =========================================================================
    // VULNERABILITY #2 — Unrestricted File Upload
    // CWE-434: Unrestricted Upload of File with Dangerous Type
    // =========================================================================

    /**
     * Upload file dari user.
     *
     * BAHAYA: Tidak ada validasi tipe file — attacker bisa upload .php webshell!
     */
    public function uploadFile(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'Tidak ada file'], 400);
        }

        $file = $request->file('file');

        // ❌ VULNERABLE: Tidak ada validasi ekstensi atau MIME type
        // Seharusnya: $request->validate(['file' => 'mimes:jpg,png,pdf|max:2048'])
        $originalName = $file->getClientOriginalName();  // ❌ Nama file dari user tanpa sanitasi

        // ❌ VULNERABLE: File disimpan di direktori public yang bisa diakses web
        $file->move(public_path('uploads'), $originalName);

        return response()->json([
            'message'  => 'File berhasil diupload',
            'filename' => $originalName,
            'url'      => url('uploads/' . $originalName), // Bisa akses webshell via URL!
        ]);
    }

    // =========================================================================
    // VULNERABILITY #3 — Command Injection
    // CWE-78: Improper Neutralization of Special Elements used in an OS Command
    // =========================================================================

    /**
     * Mengkonversi file gambar ke format lain.
     *
     * BAHAYA: $filename dari user langsung dimasukkan ke perintah shell.
     * Attacker bisa input: "file.jpg; cat /etc/passwd" atau "file.jpg && rm -rf /"
     */
    public function convertImage(Request $request)
    {
        $filename = $request->input('filename');
        $format   = $request->input('format', 'png');

        // ❌ VULNERABLE: Command Injection — input langsung ke shell_exec()!
        $output = shell_exec("convert " . $filename . " output." . $format);
        // Attacker input: "a.jpg; cat /etc/passwd > /var/www/html/public/hacked.txt"

        return response()->json([
            'output' => $output,
        ]);
    }

    /**
     * Mengambil informasi file menggunakan perintah 'file'.
     *
     * BAHAYA: exec() dengan input tidak tersanitasi.
     */
    public function getFileInfo(Request $request)
    {
        $filepath = $request->input('path');

        // ❌ VULNERABLE: exec() dengan user input langsung
        exec("file " . $filepath, $output, $returnCode);  // Command Injection!

        return response()->json([
            'info'   => implode("\n", $output),
            'status' => $returnCode,
        ]);
    }
}
