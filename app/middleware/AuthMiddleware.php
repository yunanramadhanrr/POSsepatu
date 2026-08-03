<?php
/**
 * Memastikan request hanya bisa diakses oleh user yang sudah login.
 * Juga menangani "Remember Me": jika session habis tapi cookie remember_token valid,
 * user akan di-auto-login kembali.
 */
class AuthMiddleware
{
    public static function handle(): void
    {
        if (is_logged_in()) {
            return;
        }

        // Coba auto-login lewat remember token jika ada
        if (!empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
            $user = User::findByRememberToken($_COOKIE[REMEMBER_COOKIE_NAME]);
            if ($user) {
                AuthController::loginUserToSession($user);
                return;
            }
        }

        flash('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
        redirect('/login');
    }
}
