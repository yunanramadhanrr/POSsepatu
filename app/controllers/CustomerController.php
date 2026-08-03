<?php
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/MembershipLevel.php';
require_once __DIR__ . '/../models/PointHistory.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Validation.php';

class CustomerController
{
    /** GET /customers */
    public function index(): void
    {
        RoleMiddleware::handle('customers.index', 'view');

        $search = trim($_GET['search'] ?? '');
        $customers = Customer::allWithLevel($search);
        $levels = MembershipLevel::allOrderedByMinPoints();

        require __DIR__ . '/../views/customers/index.php';
    }

    /** GET /customers/{id} — detail: info, riwayat poin, riwayat pembelian, tukar poin */
    public function show(string $id): void
    {
        RoleMiddleware::handle('customers.index', 'view');

        $customer = Customer::findWithLevel((int) $id);
        if (!$customer) {
            abort(404, 'Pelanggan tidak ditemukan.');
        }

        $pointHistories = PointHistory::forCustomer((int) $id);
        $purchaseHistory = Customer::purchaseHistory((int) $id);

        require __DIR__ . '/../views/customers/show.php';
    }

    /** POST /customers */
    public function store(): void
    {
        RoleMiddleware::handle('customers.index', 'create');

        $validator = new Validation($_POST);
        $validator->required('name', 'Nama pelanggan wajib diisi');
        if (!empty($_POST['email'])) {
            $validator->email('email', 'Format email tidak valid');
        }

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/customers');
        }

        // Level default: level dengan min_points terendah (biasanya Silver / 0 poin)
        $defaultLevel = MembershipLevel::findLevelForPoints(0);

        $id = Customer::insert([
            'member_code'          => Customer::generateUniqueMemberCode(),
            'name'                 => trim($_POST['name']),
            'phone'                => trim($_POST['phone'] ?? ''),
            'email'                => trim($_POST['email'] ?? ''),
            'address'              => trim($_POST['address'] ?? ''),
            'birth_date'           => ($_POST['birth_date'] ?? '') ?: null,
            'membership_level_id'  => $defaultLevel['id'] ?? null,
            'points'               => 0,
        ]);

        AuditLog::record(current_user()['id'], 'create', 'customers', $id, null, trim($_POST['name']));

        flash('success', 'Pelanggan berhasil ditambahkan.');
        redirect('/customers');
    }

    /** POST /customers/{id}/update */
    public function update(string $id): void
    {
        RoleMiddleware::handle('customers.index', 'edit');

        $customer = Customer::find((int) $id);
        if (!$customer) {
            abort(404, 'Pelanggan tidak ditemukan.');
        }

        $validator = new Validation($_POST);
        $validator->required('name', 'Nama pelanggan wajib diisi');
        if (!empty($_POST['email'])) {
            $validator->email('email', 'Format email tidak valid');
        }

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/customers');
        }

        Customer::update((int) $id, [
            'name'       => trim($_POST['name']),
            'phone'      => trim($_POST['phone'] ?? ''),
            'email'      => trim($_POST['email'] ?? ''),
            'address'    => trim($_POST['address'] ?? ''),
            'birth_date' => ($_POST['birth_date'] ?? '') ?: null,
        ]);

        AuditLog::record(current_user()['id'], 'update', 'customers', (int) $id, $customer['name'], trim($_POST['name']));

        flash('success', 'Pelanggan berhasil diperbarui.');
        redirect('/customers');
    }

    /** POST /customers/{id}/delete */
    public function destroy(string $id): void
    {
        RoleMiddleware::handle('customers.index', 'delete');

        $customer = Customer::find((int) $id);
        if (!$customer) {
            abort(404, 'Pelanggan tidak ditemukan.');
        }

        Customer::delete((int) $id);
        AuditLog::record(current_user()['id'], 'delete', 'customers', (int) $id, $customer['name'], null);

        flash('success', 'Pelanggan berhasil dihapus.');
        redirect('/customers');
    }

    /** POST /customers/{id}/redeem-points */
    public function redeemPoints(string $id): void
    {
        RoleMiddleware::handle('customers.index', 'edit');

        $customer = Customer::find((int) $id);
        if (!$customer) {
            abort(404, 'Pelanggan tidak ditemukan.');
        }

        $points = (int) ($_POST['points'] ?? 0);

        try {
            $voucherCode = Customer::redeemPointsToVoucher((int) $id, $points);
            AuditLog::record(current_user()['id'], 'redeem_points', 'customers', (int) $id, null, "{$points} poin -> voucher {$voucherCode}");
            flash('success', "Berhasil menukar {$points} poin menjadi voucher: {$voucherCode}");
        } catch (RuntimeException $e) {
            flash('errors', $e->getMessage());
        }

        redirect('/customers/' . $id);
    }
}
