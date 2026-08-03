<?php
require_once __DIR__ . '/Model.php';

class Menu extends Model
{
    protected static string $table = 'menus';

    public static function allOrdered(): array
    {
        return self::all('sort_order ASC');
    }
}
