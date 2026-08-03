<?php
/**
 * Memvalidasi CSRF token pada setiap request dengan method POST/PUT/DELETE.
 */
class CsrfMiddleware
{
    public static function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
            $token = $_POST['csrf_token'] ?? '';

            if (!verify_csrf($token)) {
                abort(419, 'Sesi form telah kedaluwarsa. Silakan refresh halaman dan coba lagi.');
            }
        }
    }
}
