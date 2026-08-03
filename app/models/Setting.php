<?php
require_once __DIR__ . '/Model.php';

class Setting extends Model
{
    protected static string $table = 'settings';

    public static function get(string $key, string $default = ''): string
    {
        $rows = self::raw('SELECT setting_value FROM settings WHERE setting_key = :k', [':k' => $key]);
        return $rows[0]['setting_value'] ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        $existing = self::raw('SELECT id FROM settings WHERE setting_key = :k', [':k' => $key]);
        if ($existing) {
            self::connection()
                ->prepare('UPDATE settings SET setting_value = :v WHERE setting_key = :k')
                ->execute([':v' => $value, ':k' => $key]);
        } else {
            self::insert(['setting_key' => $key, 'setting_value' => $value]);
        }
    }
}
