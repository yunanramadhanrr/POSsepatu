<?php
require_once __DIR__ . '/Model.php';

class Expense extends Model
{
    protected static string $table = 'expenses';

    public static function allWithCategory(string $from = '', string $to = ''): array
    {
        $from = $from ?: date('Y-m-01');
        $to = $to ?: date('Y-m-d');

        return self::raw(
            "SELECT e.*, ec.name AS category_name, u.name AS user_name
             FROM expenses e
             JOIN expense_categories ec ON ec.id = e.expense_category_id
             JOIN users u ON u.id = e.user_id
             WHERE e.expense_date BETWEEN :from AND :to
             ORDER BY e.expense_date DESC, e.id DESC",
            [':from' => $from, ':to' => $to]
        );
    }

    public static function findWithCategory(int $id): ?array
    {
        $rows = self::raw(
            "SELECT e.*, ec.name AS category_name, u.name AS user_name
             FROM expenses e
             JOIN expense_categories ec ON ec.id = e.expense_category_id
             JOIN users u ON u.id = e.user_id
             WHERE e.id = :id LIMIT 1",
            [':id' => $id]
        );
        return $rows[0] ?? null;
    }
}
