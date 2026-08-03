<?php
require_once __DIR__ . '/../models/MembershipLevel.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Validation.php';

class MembershipLevelController
{
    /** POST /membership-levels/{id}/update — dipakai dari halaman Pelanggan (menu yang sama: customers.index) */
    public function update(string $id): void
    {
        RoleMiddleware::handle('customers.index', 'edit');

        $level = MembershipLevel::find((int) $id);
        if (!$level) {
            abort(404, 'Level membership tidak ditemukan.');
        }

        $validator = new Validation($_POST);
        $validator->required('min_points', 'Minimal poin wajib diisi')
                  ->numeric('min_points', 'Minimal poin harus angka')
                  ->required('discount_percent', 'Diskon wajib diisi')
                  ->numeric('discount_percent', 'Diskon harus angka');

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/customers');
        }

        MembershipLevel::update((int) $id, [
            'min_points'       => (int) $_POST['min_points'],
            'discount_percent' => (float) $_POST['discount_percent'],
        ]);

        AuditLog::record(current_user()['id'], 'update', 'membership_levels', (int) $id, null, json_encode($_POST));

        flash('success', 'Pengaturan level ' . $level['name'] . ' berhasil diperbarui.');
        redirect('/customers');
    }
}
