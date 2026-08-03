<?php
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/User.php';

class AuditLogController
{
    /** GET /audit-logs */
    public function index(): void
    {
        RoleMiddleware::handle('audit_logs.index', 'view');

        $filters = [
            'user_id'    => $_GET['user_id'] ?? '',
            'action'     => $_GET['action'] ?? '',
            'table_name' => $_GET['table_name'] ?? '',
            'date_from'  => $_GET['date_from'] ?? '',
            'date_to'    => $_GET['date_to'] ?? '',
        ];

        $logs = AuditLog::filtered($filters, 300);
        $users = User::allWithRole();
        $actions = AuditLog::distinctActions();
        $tables = AuditLog::distinctTables();

        require __DIR__ . '/../views/audit_logs/index.php';
    }
}
