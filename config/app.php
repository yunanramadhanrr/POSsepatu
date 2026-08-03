<?php
/**
 * Konfigurasi umum aplikasi.
 */

define('APP_NAME', 'POS Toko Sepatu');
define('APP_ENV', getenv('APP_ENV') ?: 'local'); // local | production
define('APP_DEBUG', APP_ENV === 'local');

// Base URL — sesuaikan dengan folder project di htdocs XAMPP
// Contoh jika project ada di http://localhost/pos-toko-sepatu
define('BASE_URL', '/pos-toko-sepatu/public');

define('UPLOAD_PATH', __DIR__ . '/../public/uploads/products/');
define('UPLOAD_URL', BASE_URL . '/uploads/products/');

define('SESSION_NAME', 'pos_toko_sepatu_session');
define('REMEMBER_COOKIE_NAME', 'pos_remember_token');
define('REMEMBER_COOKIE_DAYS', 30);

// Konversi poin membership -> voucher. 1 poin = Rp 100, minimal tukar 100 poin (= Rp 10.000).
define('POINTS_TO_RUPIAH_RATE', 100);
define('MIN_POINTS_REDEEM', 100);
define('VOUCHER_VALID_DAYS', 90);

// Perolehan poin dari transaksi: 1 poin didapat setiap kelipatan Rp 10.000 belanja (dibulatkan ke bawah).
define('RUPIAH_PER_POINT_EARNED', 10000);

// Konfigurasi session yang lebih aman
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
// Aktifkan baris berikut jika sudah menggunakan HTTPS di produksi:
// ini_set('session.cookie_secure', 1);

session_name(SESSION_NAME);

date_default_timezone_set('Asia/Jakarta');

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
<?php
/**
 * Konfigurasi umum aplikasi.
 */

define('APP_NAME', 'POS Toko Sepatu');
define('APP_ENV', getenv('APP_ENV') ?: 'local'); // local | production
define('APP_DEBUG', APP_ENV === 'local');

// Base URL — otomatis menyesuaikan environment.
// - Local (XAMPP/Laragon): project diakses lewat sub-folder, contoh http://localhost/pos-toko-sepatu/public
// - Production (Railway/VPS dengan domain sendiri): akses langsung dari root domain, tanpa prefix folder.
define('BASE_URL', APP_ENV === 'production' ? '' : '/pos-toko-sepatu/public');

define('UPLOAD_PATH', __DIR__ . '/../public/uploads/products/');
define('UPLOAD_URL', BASE_URL . '/uploads/products/');

define('SESSION_NAME', 'pos_toko_sepatu_session');
define('REMEMBER_COOKIE_NAME', 'pos_remember_token');
define('REMEMBER_COOKIE_DAYS', 30);

// Konversi poin membership -> voucher. 1 poin = Rp 100, minimal tukar 100 poin (= Rp 10.000).
define('POINTS_TO_RUPIAH_RATE', 100);
define('MIN_POINTS_REDEEM', 100);
define('VOUCHER_VALID_DAYS', 90);

// Perolehan poin dari transaksi: 1 poin didapat setiap kelipatan Rp 10.000 belanja (dibulatkan ke bawah).
define('RUPIAH_PER_POINT_EARNED', 10000);

// Konfigurasi session yang lebih aman
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
// Aktifkan baris berikut jika sudah menggunakan HTTPS di produksi:
// ini_set('session.cookie_secure', 1);

session_name(SESSION_NAME);

date_default_timezone_set('Asia/Jakarta');

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}