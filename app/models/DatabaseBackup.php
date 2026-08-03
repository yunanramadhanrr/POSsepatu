<?php
require_once __DIR__ . '/../../config/database.php';

/**
 * Backup & restore database murni lewat PDO (tidak bergantung pada binary `mysqldump` yang belum
 * tentu tersedia/di-PATH-kan di semua server), supaya portable untuk shared hosting sekalipun.
 */
class DatabaseBackup
{
    /** Generate isi file .sql backup lengkap (struktur + data) untuk seluruh tabel di database. */
    public static function generate(): string
    {
        $db = Database::getConnection();
        $sql = "-- Backup Database " . DB_NAME . "\n-- Dibuat otomatis: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Struktur tabel
            $createRow = $db->query("SHOW CREATE TABLE `$table`")->fetch();
            $createSql = $createRow['Create Table'] ?? '';

            $sql .= "-- ----------------------------\n-- Struktur tabel `$table`\n-- ----------------------------\n";
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $createSql . ";\n\n";

            // Data tabel
            $rows = $db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                continue;
            }

            $sql .= "-- Data tabel `$table`\n";
            $columns = array_keys($rows[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';

            foreach ($rows as $row) {
                $values = array_map(function ($value) use ($db) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return $db->quote((string) $value);
                }, $row);

                $sql .= "INSERT INTO `$table` ($columnList) VALUES (" . implode(', ', $values) . ");\n";
            }

            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $sql;
    }

    /**
     * Restore database dari isi file .sql. Memakai pemisah statement sederhana (titik koma di akhir
     * baris) — cocok untuk file backup yang dihasilkan oleh method generate() di atas (round-trip aman),
     * namun TIDAK dijamin aman untuk dump SQL kompleks dari sumber lain (mis. mengandung titik koma
     * di dalam string/prosedur tersimpan).
     *
     * Return jumlah statement yang berhasil dieksekusi.
     */
    public static function restore(string $sqlContent): int
    {
        $db = Database::getConnection();

        // Buang komentar baris (--) dan baris kosong sebelum split
        $lines = explode("\n", $sqlContent);
        $cleanLines = array_filter($lines, fn($line) => trim($line) !== '' && !str_starts_with(trim($line), '--'));
        $cleaned = implode("\n", $cleanLines);

        $statements = array_filter(array_map('trim', explode(";\n", $cleaned)));

        $executed = 0;
        $db->beginTransaction();

        try {
            foreach ($statements as $statement) {
                $statement = rtrim($statement, "; \t\n");
                if ($statement === '') {
                    continue;
                }
                $db->exec($statement);
                $executed++;
            }
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw new RuntimeException('Gagal restore pada statement ke-' . ($executed + 1) . ': ' . $e->getMessage());
        }

        return $executed;
    }
}
