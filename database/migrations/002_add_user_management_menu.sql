-- ============================================================
-- MIGRASI TAMBAHAN: Modul Manajemen User
-- Jalankan file ini SEKALI SAJA jika project Anda sudah pernah
-- menjalankan 001_seed_basic_data.sql sebelumnya (upgrade dari
-- Tahap 2-11). Untuk instalasi BARU dari nol, menu ini sudah
-- otomatis termasuk di 001_seed_basic_data.sql, jadi file ini
-- boleh dilewati.
-- ============================================================

USE pos_toko_sepatu;

INSERT INTO menus (label, route_key, icon, sort_order)
SELECT 'Manajemen User', 'users.index', 'bi-person-gear', 16
WHERE NOT EXISTS (SELECT 1 FROM menus WHERE route_key = 'users.index');

-- Hanya Owner yang diberi akses penuh (Admin/Kasir/Gudang sengaja TIDAK diberi akses
-- ke manajemen user demi keamanan; Owner bisa atur ulang lewat halaman ini jika perlu).
INSERT INTO role_permissions (role_id, menu_id, can_view, can_create, can_edit, can_delete)
SELECT r.id, m.id, 1, 1, 1, 1
FROM roles r
JOIN menus m ON m.route_key = 'users.index'
WHERE r.name = 'Owner'
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.menu_id = m.id
  );
