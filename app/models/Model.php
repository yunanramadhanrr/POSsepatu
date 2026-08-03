<?php
require_once __DIR__ . '/../../config/database.php';

/**
 * Base Model — semua model (User, Product, dst.) sebaiknya extend class ini
 * agar seluruh query otomatis pakai prepared statement (aman dari SQL Injection).
 */
abstract class Model
{
    protected static string $table = '';
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    protected static function connection(): PDO
    {
        return Database::getConnection();
    }

    /** SELECT dengan kondisi WHERE sederhana (array kolom => nilai, digabung AND). */
    public static function where(array $conditions = [], string $orderBy = ''): array
    {
        $db = self::connection();
        $sql = 'SELECT * FROM ' . static::$table;
        $params = [];

        if (!empty($conditions)) {
            $clauses = [];
            foreach ($conditions as $column => $value) {
                $clauses[] = "$column = :$column";
                $params[":$column"] = $value;
            }
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        if ($orderBy !== '') {
            $sql .= ' ORDER BY ' . $orderBy;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::connection()->prepare('SELECT * FROM ' . static::$table . ' WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findOneWhere(array $conditions): ?array
    {
        $rows = self::where($conditions);
        return $rows[0] ?? null;
    }

    public static function all(string $orderBy = ''): array
    {
        return self::where([], $orderBy);
    }

    /** INSERT generik. Return insert id. */
    public static function insert(array $data): int
    {
        $db = self::connection();
        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            static::$table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $db->prepare($sql);
        $params = [];
        foreach ($data as $col => $val) {
            $params[":$col"] = $val;
        }
        $stmt->execute($params);

        return (int) $db->lastInsertId();
    }

    /** UPDATE generik berdasarkan id. */
    public static function update(int $id, array $data): bool
    {
        $db = self::connection();
        $sets = [];
        $params = [':id' => $id];

        foreach ($data as $col => $val) {
            $sets[] = "$col = :$col";
            $params[":$col"] = $val;
        }

        $sql = sprintf('UPDATE %s SET %s WHERE id = :id', static::$table, implode(', ', $sets));
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool
    {
        $stmt = self::connection()->prepare('DELETE FROM ' . static::$table . ' WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /** Untuk query kompleks (JOIN, agregasi) yang tidak tercakup helper generik di atas. */
    public static function raw(string $sql, array $params = []): array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
