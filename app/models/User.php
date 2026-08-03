<?php
require_once __DIR__ . '/Model.php';

class User extends Model
{
    protected static string $table = 'users';

    /** Cari user beserta nama role-nya (JOIN) berdasarkan email. */
    public static function findByEmail(string $email): ?array
    {
        $rows = self::raw(
            'SELECT u.*, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = :email
             LIMIT 1',
            [':email' => $email]
        );
        return $rows[0] ?? null;
    }

    public static function findByIdWithRole(int $id): ?array
    {
        $rows = self::raw(
            'SELECT u.*, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id
             LIMIT 1',
            [':id' => $id]
        );
        return $rows[0] ?? null;
    }

    public static function findByRememberToken(string $token): ?array
    {
        $rows = self::raw(
            'SELECT u.*, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.remember_token = :token AND u.status = "active"
             LIMIT 1',
            [':token' => $token]
        );
        return $rows[0] ?? null;
    }

    public static function findByResetToken(string $token): ?array
    {
        $rows = self::raw(
            'SELECT * FROM users
             WHERE reset_token = :token
               AND reset_token_expires_at > NOW()
             LIMIT 1',
            [':token' => $token]
        );
        return $rows[0] ?? null;
    }

    public static function updateRememberToken(int $userId, ?string $token): bool
    {
        return self::update($userId, ['remember_token' => $token]);
    }

    public static function updatePassword(int $userId, string $plainPassword): bool
    {
        return self::update($userId, [
            'password'               => password_hash($plainPassword, PASSWORD_DEFAULT),
            'reset_token'            => null,
            'reset_token_expires_at' => null,
        ]);
    }

    public static function setResetToken(int $userId, string $token, string $expiresAt): bool
    {
        return self::update($userId, [
            'reset_token'            => $token,
            'reset_token_expires_at' => $expiresAt,
        ]);
    }

    public static function touchLastLogin(int $userId): bool
    {
        return self::update($userId, ['last_login_at' => date('Y-m-d H:i:s')]);
    }

    // ================================================================
    // MANAJEMEN USER (CRUD, khusus diakses Owner)
    // ================================================================

    /** Daftar seluruh user beserta nama role-nya, untuk halaman Manajemen User. */
    public static function allWithRole(): array
    {
        return self::raw(
            "SELECT u.*, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             ORDER BY u.name ASC"
        );
    }

    /** Hitung berapa user lain (selain $excludeId) yang masih berstatus aktif dengan role Owner. */
    public static function countOtherActiveOwners(int $excludeId): int
    {
        $rows = self::raw(
            "SELECT COUNT(*) AS total
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE r.name = 'Owner' AND u.status = 'active' AND u.id != :id",
            [':id' => $excludeId]
        );
        return (int) ($rows[0]['total'] ?? 0);
    }

    /** Cek apakah user pernah membuat transaksi (sales/purchases) — dipakai sebelum hard delete. */
    public static function hasTransactionHistory(int $userId): bool
    {
        $rows = self::raw(
            "SELECT
                (SELECT COUNT(*) FROM sales WHERE user_id = :id1) +
                (SELECT COUNT(*) FROM purchases WHERE user_id = :id2) AS total",
            [':id1' => $userId, ':id2' => $userId]
        );
        return (int) ($rows[0]['total'] ?? 0) > 0;
    }
}
