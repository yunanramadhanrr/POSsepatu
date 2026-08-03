<?php
/**
 * Kumpulan helper function global.
 * Semua fungsi di sini reusable dan bebas efek samping kecuali disebutkan (redirect, flash, session).
 */

/** Escape output untuk mencegah XSS. Selalu gunakan ini saat mencetak data ke view. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Redirect ke path relatif terhadap BASE_URL lalu hentikan eksekusi. */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

/** Bangun URL absolut relatif terhadap BASE_URL. */
function url(string $path = ''): string
{
    return BASE_URL . $path;
}

/** Bangun URL ke folder assets. */
function asset(string $path): string
{
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

/** Simpan flash message ke session (tampil sekali lalu hilang). */
function flash(string $key, ?string $message = null)
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }

    return null;
}

/** Ambil kembali input lama setelah redirect (untuk re-populate form saat validasi gagal). */
function old(string $key, $default = '')
{
    return e($_SESSION['old'][$key] ?? $default);
}

function set_old_input(array $input): void
{
    $_SESSION['old'] = $input;
}

function clear_old_input(): void
{
    unset($_SESSION['old']);
}

/** Format angka ke Rupiah, contoh: 15000 -> "Rp 15.000". */
function format_rupiah($amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

/** Format tanggal ke format Indonesia: dd-mm-Y H:i. */
function format_tanggal(?string $datetime, string $format = 'd-m-Y H:i'): string
{
    if (!$datetime) {
        return '-';
    }
    return date($format, strtotime($datetime));
}

/**
 * Generate kode unik dengan prefix + tanggal + nomor urut acak,
 * contoh: generate_code('PRD') -> "PRD-20260727-4821"
 */
function generate_code(string $prefix): string
{
    return sprintf('%s-%s-%04d', strtoupper($prefix), date('Ymd'), random_int(0, 9999));
}

/** Ambil CSRF token dari session, generate jika belum ada. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Cetak hidden input CSRF, dipakai di dalam setiap <form> method POST. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Verifikasi CSRF token dari request POST. */
function verify_csrf(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/** Ambil user login saat ini dari session (null jika belum login). */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

/**
 * Upload file gambar dengan aman: validasi tipe MIME asli (bukan cuma ekstensi),
 * validasi ukuran, dan nama file diacak (random) agar tidak menimpa file lain / path traversal.
 * Return nama file yang tersimpan, atau null jika tidak ada file diupload.
 * Melempar Exception jika file ada tapi tidak valid (supaya controller bisa tampilkan pesan error).
 */
function handle_photo_upload(string $inputName, string $destinationDir, int $maxSizeBytes = 2097152): ?string
{
    if (empty($_FILES[$inputName]['name']) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$inputName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload foto gagal (kode error: ' . $file['error'] . ').');
    }

    if ($file['size'] > $maxSizeBytes) {
        throw new RuntimeException('Ukuran foto maksimal ' . round($maxSizeBytes / 1024 / 1024, 1) . ' MB.');
    }

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowedMimes[$realMime])) {
        throw new RuntimeException('Format foto harus JPG, PNG, atau WEBP.');
    }

    $extension = $allowedMimes[$realMime];
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;

    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $destinationDir . '/' . $filename)) {
        throw new RuntimeException('Gagal menyimpan file foto ke server.');
    }

    return $filename;
}

/** Hapus file foto lama dari folder upload (dipanggil saat produk diupdate dengan foto baru, atau dihapus). */
function delete_photo_if_exists(?string $filename, string $destinationDir): void
{
    if ($filename && is_file($destinationDir . '/' . $filename)) {
        unlink($destinationDir . '/' . $filename);
    }
}

/**
 * Stream data sebagai file CSV (dibuka otomatis oleh Excel) lalu hentikan eksekusi.
 * $headers: array label kolom. $rows: array asosiatif per baris (urutan key harus sejajar dengan $headers).
 */
function download_csv(string $filename, array $headers, array $rows): void
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM agar Excel baca UTF-8 dgn benar
    fputcsv($output, $headers);

    foreach ($rows as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/** Hentikan request dengan HTTP status code tertentu (dipakai middleware saat menolak akses/CSRF gagal). */
function abort(int $code, string $message = ''): void
{
    http_response_code($code);
    echo $message !== '' ? e($message) : 'Request tidak valid.';
    exit;
}
