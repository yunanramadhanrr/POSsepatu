<?php
/**
 * Konfigurasi & koneksi database menggunakan PDO.
 * Semua query di aplikasi WAJIB lewat prepared statement (lihat class Database).
 */

// --- Kredensial database (untuk produksi, pindahkan ke environment variable) ---
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'pos_toko_sepatu');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

class Database
{
    private static ?PDO $instance = null;

    /**
     * Ambil instance PDO tunggal (singleton) agar koneksi tidak dibuat berulang.
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // gunakan prepared statement asli MySQL
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Jangan tampilkan detail koneksi ke user (keamanan)
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(500);
                die('Koneksi database gagal. Silakan hubungi administrator.');
            }
        }

        return self::$instance;
    }
}
