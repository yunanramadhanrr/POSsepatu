<?php
require_once __DIR__ . '/Model.php';

class AuditLog extends Model
{
    protected static string $table = 'audit_logs';

    /** Ambil N aktivitas terbaru beserta nama user, untuk feed "Aktivitas Terbaru" di dashboard. */
    public static function recent(int $limit = 10): array
    {
        $safeLimit = (int) $limit;

        return self::raw(
            "SELECT al.*, u.name AS user_name
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC
             LIMIT {$safeLimit}"
        );
    }

    /** Daftar audit log dengan filter untuk halaman UI Audit Log (Tahap 15). */
    public static function filtered(array $filters = [], int $limit = 200): array
    {
        $sql = "SELECT al.*, u.name AS user_name
                FROM audit_logs al
                LEFT JOIN users u ON u.id = al.user_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= ' AND al.user_id = :user_id';
            $params[':user_id'] = $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $sql .= ' AND al.action = :action';
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['table_name'])) {
            $sql .= ' AND al.table_name = :table_name';
            $params[':table_name'] = $filters['table_name'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= ' AND DATE(al.created_at) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND DATE(al.created_at) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        $safeLimit = (int) $limit;
        $sql .= " ORDER BY al.created_at DESC LIMIT {$safeLimit}";

        return self::raw($sql, $params);
    }

    /** Daftar aksi & tabel unik yang pernah tercatat, untuk isi dropdown filter. */
    public static function distinctActions(): array
    {
        $rows = self::raw('SELECT DISTINCT action FROM audit_logs ORDER BY action ASC');
        return array_column($rows, 'action');
    }

    public static function distinctTables(): array
    {
        $rows = self::raw('SELECT DISTINCT table_name FROM audit_logs WHERE table_name IS NOT NULL ORDER BY table_name ASC');
        return array_column($rows, 'table_name');
    }

    public static function record(
        ?int $userId,
        string $action,
        ?string $tableName = null,
        ?int $recordId = null,
        ?string $oldValue = null,
        ?string $newValue = null
    ): void {
        self::insert([
            'user_id'    => $userId,
            'action'     => $action,
            'table_name' => $tableName,
            'record_id'  => $recordId,
            'old_value'  => $oldValue,
            'new_value'  => $newValue,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
