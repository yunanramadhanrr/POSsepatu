<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Validation.php';
require_once __DIR__ . '/../helpers/Mailer.php';

class AuthController
{
    /** GET /login */
    public function showLogin(): void
    {
        if (is_logged_in()) {
            redirect('/dashboard');
        }
        require __DIR__ . '/../views/auth/login.php';
    }

    /** POST /login */
    public function login(): void
    {
        $validator = new Validation($_POST);
        $validator->required('email', 'Email wajib diisi')
                  ->email('email', 'Format email tidak valid')
                  ->required('password', 'Password wajib diisi');

        if ($validator->fails()) {
            set_old_input($_POST);
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/login');
        }

        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);

        $user = User::findByEmail($email);

        // Pesan generik (tidak bocorkan apakah email terdaftar) untuk mencegah user enumeration
        if (!$user || !password_verify($password, $user['password'])) {
            flash('errors', 'Email atau password salah.');
            set_old_input(['email' => $email]);
            redirect('/login');
        }

        if ($user['status'] !== 'active') {
            flash('errors', 'Akun Anda tidak aktif. Hubungi Owner/Admin.');
            redirect('/login');
        }

        self::loginUserToSession($user);
        User::touchLastLogin((int) $user['id']);
        AuditLog::record((int) $user['id'], 'login', 'users', (int) $user['id']);

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            User::updateRememberToken((int) $user['id'], $token);
            setcookie(
                REMEMBER_COOKIE_NAME,
                $token,
                [
                    'expires'  => time() + (REMEMBER_COOKIE_DAYS * 86400),
                    'path'     => BASE_URL,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        }

        redirect('/dashboard');
    }

    /** Simpan data user ke session (dipakai saat login biasa maupun auto-login via remember token). */
    public static function loginUserToSession(array $user): void
    {
        session_regenerate_id(true); // cegah session fixation

        $_SESSION['user'] = [
            'id'        => (int) $user['id'],
            'name'      => $user['name'],
            'email'     => $user['email'],
            'role_id'   => (int) $user['role_id'],
            'role_name' => $user['role_name'],
            'photo'     => $user['photo'],
        ];
    }

    /** POST /logout */
    public function logout(): void
    {
        $user = current_user();
        if ($user) {
            AuditLog::record($user['id'], 'logout', 'users', $user['id']);
            User::updateRememberToken($user['id'], null);
        }

        // Hapus cookie remember me
        if (!empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
            setcookie(REMEMBER_COOKIE_NAME, '', ['expires' => time() - 3600, 'path' => BASE_URL]);
        }

        $_SESSION = [];
        session_destroy();
        redirect('/login');
    }

    /** GET /forgot-password */
    public function showForgotPassword(): void
    {
        require __DIR__ . '/../views/auth/forgot_password.php';
    }

    /**
     * POST /forgot-password
     * Mengirim email reset password sungguhan lewat SMTP (lihat app/helpers/Mailer.php & config/mail.php).
     * Jika SMTP belum dikonfigurasi (MAIL_ENABLED=false) atau pengiriman gagal, link tetap dicatat ke
     * error_log sebagai fallback agar developer/administrator masih bisa membantu user secara manual.
     */
    public function sendResetLink(): void
    {
        $email = trim($_POST['email'] ?? '');
        $user = User::findByEmail($email);

        // Selalu tampilkan pesan sukses yang sama agar email terdaftar tidak bisa ditebak (anti user-enumeration)
        flash('success', 'Jika email terdaftar, link reset password telah dikirim ke email Anda.');

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            User::setResetToken((int) $user['id'], $token, $expiresAt);

            $resetLink = url('/reset-password?token=' . $token);
            $sent = send_email(
                $user['email'],
                $user['name'],
                'Reset Password - ' . APP_NAME,
                reset_password_email_body($user['name'], $resetLink)
            );

            // Fallback: selalu catat ke error_log juga, baik email berhasil terkirim maupun tidak,
            // supaya administrator tetap bisa membantu manual jika SMTP bermasalah di produksi.
            error_log('[RESET PASSWORD LINK] ' . $email . ' => ' . $resetLink . ' (email terkirim: ' . ($sent ? 'ya' : 'tidak') . ')');
        }

        redirect('/forgot-password');
    }

    /** GET /reset-password?token=... */
    public function showResetPassword(): void
    {
        $token = $_GET['token'] ?? '';
        $user = $token ? User::findByResetToken($token) : null;

        if (!$user) {
            flash('errors', 'Link reset password tidak valid atau sudah kedaluwarsa.');
            redirect('/forgot-password');
        }

        require __DIR__ . '/../views/auth/reset_password.php';
    }

    /** POST /reset-password */
    public function resetPassword(): void
    {
        $token = $_POST['token'] ?? '';
        $user = $token ? User::findByResetToken($token) : null;

        if (!$user) {
            flash('errors', 'Link reset password tidak valid atau sudah kedaluwarsa.');
            redirect('/forgot-password');
        }

        $validator = new Validation($_POST);
        $validator->required('password', 'Password baru wajib diisi')
                  ->minLength('password', 8, 'Password minimal 8 karakter')
                  ->matches('password', 'password_confirmation', 'Konfirmasi password tidak cocok');

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/reset-password?token=' . urlencode($token));
        }

        User::updatePassword((int) $user['id'], $_POST['password']);
        AuditLog::record((int) $user['id'], 'reset_password', 'users', (int) $user['id']);

        flash('success', 'Password berhasil diubah. Silakan login.');
        redirect('/login');
    }

    /** GET /change-password (harus login) */
    public function showChangePassword(): void
    {
        require __DIR__ . '/../views/auth/change_password.php';
    }

    /** POST /change-password (harus login) */
    public function changePassword(): void
    {
        $currentUser = current_user();
        $fullUser = User::find($currentUser['id']);

        $validator = new Validation($_POST);
        $validator->required('current_password', 'Password lama wajib diisi')
                  ->required('new_password', 'Password baru wajib diisi')
                  ->minLength('new_password', 8, 'Password baru minimal 8 karakter')
                  ->matches('new_password', 'new_password_confirmation', 'Konfirmasi password baru tidak cocok');

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/change-password');
        }

        if (!password_verify($_POST['current_password'], $fullUser['password'])) {
            flash('errors', 'Password lama tidak sesuai.');
            redirect('/change-password');
        }

        User::updatePassword((int) $currentUser['id'], $_POST['new_password']);
        AuditLog::record((int) $currentUser['id'], 'change_password', 'users', (int) $currentUser['id']);

        flash('success', 'Password berhasil diperbarui.');
        redirect('/change-password');
    }
}
