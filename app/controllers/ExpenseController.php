<?php
require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../models/ExpenseCategory.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Validation.php';

class ExpenseController
{
    /** GET /expenses */
    public function index(): void
    {
        RoleMiddleware::handle('expenses.index', 'view');

        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');

        $expenses = Expense::allWithCategory($from, $to);
        $categories = ExpenseCategory::all('name ASC');
        $total = array_sum(array_column($expenses, 'amount'));

        require __DIR__ . '/../views/expenses/index.php';
    }

    /** POST /expenses */
    public function store(): void
    {
        RoleMiddleware::handle('expenses.index', 'create');

        $validator = new Validation($_POST);
        $validator->required('expense_category_id', 'Kategori wajib dipilih')
                  ->required('amount', 'Jumlah wajib diisi')
                  ->numeric('amount', 'Jumlah harus angka')
                  ->required('expense_date', 'Tanggal wajib diisi');

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/expenses');
        }

        $id = Expense::insert([
            'expense_category_id' => (int) $_POST['expense_category_id'],
            'amount'               => (float) $_POST['amount'],
            'expense_date'         => $_POST['expense_date'],
            'note'                 => trim($_POST['note'] ?? ''),
            'user_id'              => current_user()['id'],
        ]);

        AuditLog::record(current_user()['id'], 'create', 'expenses', $id, null, $_POST['amount']);

        flash('success', 'Pengeluaran berhasil dicatat.');
        redirect('/expenses');
    }

    /** POST /expenses/{id}/update */
    public function update(string $id): void
    {
        RoleMiddleware::handle('expenses.index', 'edit');

        $expense = Expense::find((int) $id);
        if (!$expense) {
            abort(404, 'Data pengeluaran tidak ditemukan.');
        }

        $validator = new Validation($_POST);
        $validator->required('expense_category_id', 'Kategori wajib dipilih')
                  ->required('amount', 'Jumlah wajib diisi')
                  ->numeric('amount', 'Jumlah harus angka')
                  ->required('expense_date', 'Tanggal wajib diisi');

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/expenses');
        }

        Expense::update((int) $id, [
            'expense_category_id' => (int) $_POST['expense_category_id'],
            'amount'               => (float) $_POST['amount'],
            'expense_date'         => $_POST['expense_date'],
            'note'                 => trim($_POST['note'] ?? ''),
        ]);

        AuditLog::record(current_user()['id'], 'update', 'expenses', (int) $id, (string) $expense['amount'], $_POST['amount']);

        flash('success', 'Pengeluaran berhasil diperbarui.');
        redirect('/expenses');
    }

    /** POST /expenses/{id}/delete */
    public function destroy(string $id): void
    {
        RoleMiddleware::handle('expenses.index', 'delete');

        $expense = Expense::find((int) $id);
        if (!$expense) {
            abort(404, 'Data pengeluaran tidak ditemukan.');
        }

        Expense::delete((int) $id);
        AuditLog::record(current_user()['id'], 'delete', 'expenses', (int) $id, (string) $expense['amount'], null);

        flash('success', 'Pengeluaran berhasil dihapus.');
        redirect('/expenses');
    }
}
