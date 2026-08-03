<?php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/app.php';
}

/**
 * Konfigurasi SMTP. Diisi lewat environment variable agar kredensial tidak ikut ter-commit ke Git,
 * dengan nilai default aman (MAIL_ENABLED=false) supaya instalasi baru tidak error walau SMTP belum diisi.
 *
 * Cara mengaktifkan (contoh pakai Gmail App Password, atau layanan seperti Mailtrap/SMTP hosting):
 *   Set environment variable berikut sebelum menjalankan PHP (atau taruh di .env lalu load manual),
 *   atau langsung ubah nilai default di bawah ini untuk quick testing lokal:
 *
 *   MAIL_ENABLED=true
 *   MAIL_HOST=smtp.gmail.com
 *   MAIL_PORT=587
 *   MAIL_ENCRYPTION=tls
 *   MAIL_USERNAME=alamat-anda@gmail.com
 *   MAIL_PASSWORD=app-password-16-digit
 *   MAIL_FROM_ADDRESS=alamat-anda@gmail.com
 *   MAIL_FROM_NAME="POS Toko Sepatu"
 */

define('MAIL_ENABLED', filter_var(getenv('MAIL_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN));
define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.example.com');
define('MAIL_PORT', (int) (getenv('MAIL_PORT') ?: 587));
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'tls'); // 'tls', 'ssl', atau '' (tanpa enkripsi)
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: '');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'noreply@tokosepatu.test');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: APP_NAME);
