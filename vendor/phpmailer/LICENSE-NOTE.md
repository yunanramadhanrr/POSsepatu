# PHPMailer (Bundled Manually, Tanpa Composer)

Folder ini berisi PHPMailer (https://github.com/PHPMailer/PHPMailer), diunduh langsung dari source
resmi tanpa Composer agar konsisten dengan arsitektur "PHP 8 Native tanpa framework" pada project ini.

- **Lisensi**: LGPL 2.1 (https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html)
- **Sumber**: https://github.com/PHPMailer/PHPMailer/tree/master/src
- **File yang dibundel**: `PHPMailer.php`, `SMTP.php`, `Exception.php`

Hanya 3 file inti ini yang dipakai (bukan seluruh repository), cukup untuk mengirim email lewat SMTP
dengan autentikasi + TLS/SSL. Untuk update ke versi terbaru, unduh ulang ketiga file di atas dari
repository resmi dan timpa file di folder `src/` ini.

Dipakai lewat helper `app/helpers/Mailer.php` — lihat `config/mail.php` untuk konfigurasi SMTP.
