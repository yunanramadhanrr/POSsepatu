<?php
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Kirim email lewat SMTP asli (PHPMailer, dibundel manual tanpa Composer — lihat vendor/phpmailer/).
 *
 * Perilaku fallback yang disengaja: jika MAIL_ENABLED=false (SMTP belum dikonfigurasi) ATAU pengiriman
 * gagal karena alasan apa pun (kredensial salah, host tidak terjangkau, dsb.), fungsi ini TIDAK melempar
 * error ke pengguna — cukup mencatat ke error_log dan mengembalikan false. Ini penting khususnya untuk
 * alur lupa password: kegagalan kirim email tidak boleh membuat halaman crash atau membocorkan info
 * (mis. "email tidak terdaftar") ke penyerang; pesan ke user tetap generik apa pun hasil pengiriman.
 */
function send_email(string $toEmail, string $toName, string $subject, string $htmlBody): bool
{
    if (!MAIL_ENABLED) {
        error_log("[MAIL DISABLED] Email ke {$toEmail} tidak dikirim (MAIL_ENABLED=false). Subjek: {$subject}");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->Port = MAIL_PORT;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;

        if (MAIL_ENCRYPTION === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif (MAIL_ENCRYPTION === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPAutoTLS = false;
        }

        $mail->CharSet = 'UTF-8';
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('[MAIL ERROR] Gagal mengirim email ke ' . $toEmail . ': ' . $mail->ErrorInfo);
        return false;
    }
}

/** Template HTML sederhana untuk email reset password. */
function reset_password_email_body(string $userName, string $resetLink): string
{
    $safeName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

    return <<<HTML
    <div style="font-family: Arial, sans-serif; max-width: 480px; margin: 0 auto;">
        <h2 style="color: #0d6efd;">Reset Password</h2>
        <p>Halo {$safeName},</p>
        <p>Kami menerima permintaan untuk mengatur ulang password akun Anda. Klik tombol di bawah untuk melanjutkan:</p>
        <p style="text-align: center; margin: 24px 0;">
            <a href="{$safeLink}" style="background: #0d6efd; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
                Reset Password
            </a>
        </p>
        <p style="font-size: 13px; color: #666;">Atau salin link berikut ke browser Anda:<br>{$safeLink}</p>
        <p style="font-size: 13px; color: #666;">Link ini berlaku selama 1 jam. Jika Anda tidak meminta reset password, abaikan email ini.</p>
    </div>
    HTML;
}
