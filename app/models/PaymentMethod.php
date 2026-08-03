<?php
require_once __DIR__ . '/Model.php';

class PaymentMethod extends Model
{
    protected static string $table = 'payment_methods';

    public static function active(): array
    {
        return self::raw('SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY id ASC');
    }
}
