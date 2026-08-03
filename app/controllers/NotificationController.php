<?php
require_once __DIR__ . '/../models/ProductVariant.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/Setting.php';

class NotificationController
{
    /** GET /notifications */
    public function index(): void
    {
        require_login();

        $data = self::gather();
        extract($data);

        require __DIR__ . '/../views/notifications/index.php';
    }

    /**
     * Kumpulkan seluruh notifikasi aktif saat ini. Dipanggil juga dari layout (badge lonceng navbar)
     * lewat helper notification_summary() di auth_helper.php, jadi query di sini harus ringan.
     */
    public static function gather(): array
    {
        $lowStockAll = ProductVariant::lowStock();
        $lowStock = array_values(array_filter($lowStockAll, fn($v) => (int) $v['stock'] > 0));
        $outOfStock = ProductVariant::outOfStock();
        $birthdays = Customer::birthdaysToday();

        $lastBackupAt = Setting::get('last_backup_at', '');
        $daysSinceBackup = $lastBackupAt ? (int) floor((time() - strtotime($lastBackupAt)) / 86400) : null;
        $backupReminder = $daysSinceBackup === null || $daysSinceBackup >= 7;

        return [
            'lowStock'        => $lowStock,
            'outOfStock'      => $outOfStock,
            'birthdays'       => $birthdays,
            'lastBackupAt'    => $lastBackupAt,
            'daysSinceBackup' => $daysSinceBackup,
            'backupReminder'  => $backupReminder,
        ];
    }

    /** Total badge count untuk lonceng notifikasi di navbar. */
    public static function totalCount(): int
    {
        $data = self::gather();
        return count($data['lowStock']) + count($data['outOfStock']) + count($data['birthdays']) + ($data['backupReminder'] ? 1 : 0);
    }
}
