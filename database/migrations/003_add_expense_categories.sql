-- ============================================================
-- MIGRASI TAMBAHAN: Kategori Pengeluaran Default
-- Jalankan file ini jika project Anda sudah pernah menjalankan
-- 001_seed_basic_data.sql sebelumnya (upgrade dari Tahap 2-11).
-- Untuk instalasi BARU, kategori ini sudah otomatis termasuk di
-- 001_seed_basic_data.sql.
-- ============================================================

USE pos_toko_sepatu;

INSERT INTO expense_categories (name)
SELECT * FROM (SELECT 'Listrik' AS name UNION ALL SELECT 'Air' UNION ALL SELECT 'Internet'
               UNION ALL SELECT 'Gaji' UNION ALL SELECT 'Transport' UNION ALL SELECT 'Lain-lain') AS defaults
WHERE NOT EXISTS (SELECT 1 FROM expense_categories WHERE expense_categories.name = defaults.name);
