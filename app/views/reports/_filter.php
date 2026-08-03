<?php
/**
 * Partial ini mengharapkan variabel berikut sudah didefinisikan oleh view pemanggil:
 * $reportPath (string, contoh: '/reports/sales'), $from, $to, dan opsional $extraQuery (array, mis. ['type' => 'best'])
 */
$extraQuery = $extraQuery ?? [];
$baseUrl = url($reportPath);
$queryString = http_build_query(array_merge($extraQuery, ['from' => $from, 'to' => $to]));
?>
<div class="card shadow-sm mb-3 no-print">
    <div class="card-body">
        <form method="GET" action="<?= $baseUrl ?>" class="row g-2 align-items-end">
            <?php foreach ($extraQuery as $key => $val): ?>
                <input type="hidden" name="<?= e($key) ?>" value="<?= e($val) ?>">
            <?php endforeach; ?>
            <div class="col-md-3">
                <label class="form-label small">Dari Tanggal</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Sampai Tanggal</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Preset Cepat</label>
                <select name="preset" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">- Pilih -</option>
                    <option value="today">Hari Ini</option>
                    <option value="week">Minggu Ini</option>
                    <option value="month">Bulan Ini</option>
                    <option value="year">Tahun Ini</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mb-2 no-print">
    <a href="<?= $baseUrl ?>?<?= $queryString ?>&export=csv" class="btn btn-sm btn-outline-success">📥 Export Excel (CSV)</a>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">🖨️ Print / Simpan PDF</button>
</div>
