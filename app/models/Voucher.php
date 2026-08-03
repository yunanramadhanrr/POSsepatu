<?php
require_once __DIR__ . '/Model.php';

class Voucher extends Model
{
    protected static string $table = 'vouchers';

    public static function generateUniqueCode(): string
    {
        do {
            $code = 'VCR-' . strtoupper(bin2hex(random_bytes(4)));
            $exists = self::findOneWhere(['code' => $code]);
        } while ($exists);

        return $code;
    }

    public static function findByCode(string $code): ?array
    {
        return self::findOneWhere(['code' => $code]);
    }
}
