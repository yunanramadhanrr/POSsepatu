<?php
require_once __DIR__ . '/../models/Supplier.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Validation.php';

class SupplierController
{
    /** GET /suppliers */
    public function index(): void
    {
        RoleMiddleware::handle('suppliers.index', 'view');
        $suppliers = Supplier::all('name ASC');
        require __DIR__ . '/../views/suppliers/index.php';
    }

    /** POST /suppliers */
    public function store(): void
    {
        RoleMiddleware::handle('suppliers.index', 'create');

        $validator = new Validation($_POST);
        $validator->required('name', 'Nama supplier wajib diisi');
        if (!empty($_POST['email'])) {
            $validator->email('email', 'Format email tidak valid');
        }

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/suppliers');
        }

        $id = Supplier::insert([
            'name'    => trim($_POST['name']),
            'address' => trim($_POST['address'] ?? ''),
            'phone'   => trim($_POST['phone'] ?? ''),
            'email'   => trim($_POST['email'] ?? ''),
            'pic'     => trim($_POST['pic'] ?? ''),
            'note'    => trim($_POST['note'] ?? ''),
        ]);
        AuditLog::record(current_user()['id'], 'create', 'suppliers', $id, null, trim($_POST['name']));

        flash('success', 'Supplier berhasil ditambahkan.');
        redirect('/suppliers');
    }

    /** POST /suppliers/{id}/update */
    public function update(string $id): void
    {
        RoleMiddleware::handle('suppliers.index', 'edit');

        $supplier = Supplier::find((int) $id);
        if (!$supplier) {
            abort(404, 'Supplier tidak ditemukan.');
        }

        $validator = new Validation($_POST);
        $validator->required('name', 'Nama supplier wajib diisi');
        if (!empty($_POST['email'])) {
            $validator->email('email', 'Format email tidak valid');
        }

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/suppliers');
        }

        Supplier::update((int) $id, [
            'name'    => trim($_POST['name']),
            'address' => trim($_POST['address'] ?? ''),
            'phone'   => trim($_POST['phone'] ?? ''),
            'email'   => trim($_POST['email'] ?? ''),
            'pic'     => trim($_POST['pic'] ?? ''),
            'note'    => trim($_POST['note'] ?? ''),
        ]);
        AuditLog::record(current_user()['id'], 'update', 'suppliers', (int) $id, $supplier['name'], trim($_POST['name']));

        flash('success', 'Supplier berhasil diperbarui.');
        redirect('/suppliers');
    }

    /** POST /suppliers/{id}/delete */
    public function destroy(string $id): void
    {
        RoleMiddleware::handle('suppliers.index', 'delete');

        $supplier = Supplier::find((int) $id);
        if (!$supplier) {
            abort(404, 'Supplier tidak ditemukan.');
        }

        if (Supplier::countProducts((int) $id) > 0) {
            flash('errors', 'Supplier tidak bisa dihapus karena masih dipakai oleh produk.');
            redirect('/suppliers');
        }

        Supplier::delete((int) $id);
        AuditLog::record(current_user()['id'], 'delete', 'suppliers', (int) $id, $supplier['name'], null);

        flash('success', 'Supplier berhasil dihapus.');
        redirect('/suppliers');
    }
}
