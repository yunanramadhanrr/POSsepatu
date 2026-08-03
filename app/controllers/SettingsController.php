<?php
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/DatabaseBackup.php';
require_once __DIR__ . '/../models/AuditLog.php';

class SettingsController
{
    private const LOGO_DIR = __DIR__ . '/../../public/uploads/store';
    private const LOGO_URL_PREFIX = '/uploads/store/';

    /** GET /settings */
    public function index(): void
    {
        require_role(['Owner']);

        $settings = [
            'store_name'                => Setting::get('store_name', APP_NAME),
            'store_address'              => Setting::get('store_address', ''),
            'store_tax_percent'          => Setting::get('store_tax_percent', '0'),
            'receipt_printer_width'      => Setting::get('receipt_printer_width', '80'),
            'default_discount_percent'   => Setting::get('default_discount_percent', '0'),
            'store_logo'                 => Setting::get('store_logo', ''),
        ];

        require __DIR__ . '/../views/settings/index.php';
    }

    /** POST /settings */
    public function update(): void
    {
        require_role(['Owner']);

        Setting::set('store_name', trim($_POST['store_name'] ?? APP_NAME));
        Setting::set('store_address', trim($_POST['store_address'] ?? ''));
        Setting::set('store_tax_percent', (string) (float) ($_POST['store_tax_percent'] ?? 0));
        Setting::set('default_discount_percent', (string) (float) ($_POST['default_discount_percent'] ?? 0));

        $printerWidth = (int) ($_POST['receipt_printer_width'] ?? 80);
        Setting::set('receipt_printer_width', in_array($printerWidth, [58, 80], true) ? (string) $printerWidth : '80');

        try {
            $logo = handle_photo_upload('store_logo', self::LOGO_DIR);
            if ($logo) {
                $oldLogo = Setting::get('store_logo', '');
                delete_photo_if_exists($oldLogo, self::LOGO_DIR);
                Setting::set('store_logo', $logo);
            }
        } catch (RuntimeException $e) {
            flash('errors', $e->getMessage());
            redirect('/settings');
        }

        AuditLog::record(current_user()['id'], 'update', 'settings', null, null, 'Pengaturan toko diperbarui');

        flash('success', 'Pengaturan berhasil disimpan.');
        redirect('/settings');
    }

    /** GET /settings/backup — unduh file backup database (.sql) */
    public function backup(): void
    {
        require_role(['Owner']);

        $sql = DatabaseBackup::generate();
        $filename = 'backup-pos-toko-sepatu-' . date('Y-m-d_His') . '.sql';

        Setting::set('last_backup_at', date('Y-m-d H:i:s'));
        AuditLog::record(current_user()['id'], 'backup', 'database', null, null, $filename);

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sql));
        echo $sql;
        exit;
    }

    /** POST /settings/restore — upload file .sql dan jalankan restore */
    public function restore(): void
    {
        require_role(['Owner']);

        if (empty($_POST['confirm_restore'])) {
            flash('errors', 'Anda harus mencentang konfirmasi sebelum melakukan restore.');
            redirect('/settings');
        }

        if (empty($_FILES['backup_file']['tmp_name']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            flash('errors', 'File backup (.sql) wajib diupload.');
            redirect('/settings');
        }

        $extension = strtolower(pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION));
        if ($extension !== 'sql') {
            flash('errors', 'File harus berformat .sql.');
            redirect('/settings');
        }

        $content = file_get_contents($_FILES['backup_file']['tmp_name']);

        try {
            $executed = DatabaseBackup::restore($content);
        } catch (RuntimeException $e) {
            flash('errors', 'Restore gagal: ' . $e->getMessage());
            redirect('/settings');
        }

        AuditLog::record(current_user()['id'], 'restore', 'database', null, null, "$executed statement dieksekusi");

        flash('success', "Restore database berhasil ($executed statement dieksekusi). Silakan login ulang untuk memastikan sesi Anda konsisten dengan data baru.");
        redirect('/settings');
    }
}
