<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk <?= e($sale['invoice_number']) ?></title>
    <script src="<?= asset('vendor/qrcode/qrcode.js') ?>"></script>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Courier New', Courier, monospace;
            background: #e9e9e9;
            margin: 0;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .toolbar {
            width: <?= $width ?>mm;
            max-width: 95vw;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            gap: 6px;
        }
        .toolbar a, .toolbar button {
            font-family: Arial, sans-serif;
            font-size: 12px;
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #999;
            background: #fff;
            cursor: pointer;
            text-decoration: none;
            color: #333;
        }
        .toolbar a.active, .toolbar button.active {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }
        .receipt {
            width: <?= $width ?>mm;
            max-width: 95vw;
            background: #fff;
            padding: <?= $width === 58 ? '8px 6px' : '12px 10px' ?>;
            font-size: <?= $width === 58 ? '10px' : '12px' ?>;
            line-height: 1.4;
            box-shadow: 0 0 6px rgba(0,0,0,0.2);
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; gap: 4px; }
        .item-name { font-weight: bold; }
        .item-sub { display: flex; justify-content: space-between; padding-left: 4px; }
        .store-name { font-size: <?= $width === 58 ? '13px' : '15px' ?>; }
        .qr-wrap { display: flex; justify-content: center; margin-top: 8px; }
        .footer { margin-top: 8px; }

        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .receipt { box-shadow: none; padding: 0; }
            @page { size: <?= $width ?>mm auto; margin: 2mm; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <div>
        <a href="?width=58" class="<?= $width === 58 ? 'active' : '' ?>">58mm</a>
        <a href="?width=80" class="<?= $width === 80 ? 'active' : '' ?>">80mm</a>
    </div>
    <button onclick="window.print()">🖨️ Cetak</button>
</div>

<div class="receipt" id="receiptArea">
    <div class="center">
        <?php if ($storeLogo): ?>
            <img src="<?= e(url('/uploads/store/' . $storeLogo)) ?>" style="max-height: 50px; margin-bottom: 4px;">
            <br>
        <?php endif; ?>
        <div class="store-name bold"><?= e($storeName) ?></div>
        <?php if ($storeAddress): ?><div><?= e($storeAddress) ?></div><?php endif; ?>
    </div>

    <div class="line"></div>

    <div class="row"><span>No.</span><span><?= e($sale['invoice_number']) ?></span></div>
    <div class="row"><span>Kasir</span><span><?= e($sale['user_name']) ?></span></div>
    <div class="row"><span>Tanggal</span><span><?= format_tanggal($sale['sale_date'], 'd-m-Y') ?></span></div>
    <div class="row"><span>Jam</span><span><?= format_tanggal($sale['sale_date'], 'H:i') ?></span></div>
    <?php if ($sale['customer_name']): ?>
        <div class="row"><span>Member</span><span><?= e($sale['customer_name']) ?></span></div>
    <?php endif; ?>

    <div class="line"></div>

    <?php foreach ($sale['items'] as $item): ?>
        <div class="item-name"><?= e($item['product_name']) ?> (<?= e($item['size']) ?>/<?= e($item['color']) ?>)</div>
        <div class="item-sub">
            <span><?= (int) $item['qty'] ?> x <?= number_format($item['price'], 0, ',', '.') ?></span>
            <span><?= number_format($item['subtotal'], 0, ',', '.') ?></span>
        </div>
        <?php if ($item['discount'] > 0): ?>
            <div class="item-sub"><span>Diskon item</span><span>-<?= number_format($item['discount'], 0, ',', '.') ?></span></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="line"></div>

    <div class="row"><span>Subtotal</span><span><?= number_format($sale['subtotal'], 0, ',', '.') ?></span></div>
    <div class="row"><span>Diskon</span><span>-<?= number_format($sale['discount_total'], 0, ',', '.') ?></span></div>
    <div class="row"><span>PPN</span><span><?= number_format($sale['tax'], 0, ',', '.') ?></span></div>
    <div class="row bold"><span>GRAND TOTAL</span><span><?= number_format($sale['grand_total'], 0, ',', '.') ?></span></div>

    <?php if ($sale['status'] === 'completed'): ?>
        <div class="line"></div>
        <?php foreach ($sale['payments'] as $p): ?>
            <div class="row">
                <span><?= e($p['payment_method_name']) ?></span>
                <span><?= number_format($p['amount'], 0, ',', '.') ?></span>
            </div>
        <?php endforeach; ?>
        <div class="row"><span>Bayar</span><span><?= number_format($sale['paid_amount'], 0, ',', '.') ?></span></div>
        <div class="row bold"><span>Kembalian</span><span><?= number_format($sale['change_amount'], 0, ',', '.') ?></span></div>
    <?php endif; ?>

    <?php if ($sale['note']): ?>
        <div class="line"></div>
        <div>Catatan: <?= e($sale['note']) ?></div>
    <?php endif; ?>

    <div class="qr-wrap">
        <canvas id="qrCanvas"></canvas>
    </div>

    <div class="footer center">
        <div class="line"></div>
        <div>Terima kasih atas kunjungan Anda 🙏</div>
        <div style="font-size: 9px; margin-top:4px;">Scan QR untuk verifikasi struk digital</div>
    </div>
</div>

<script>
    var qr = qrcode(0, 'M'); // 0 = auto-detect ukuran, M = error correction medium
    qr.addData(<?= json_encode($receiptUrl) ?>);
    qr.make();
    document.getElementById('qrCanvas').outerHTML = qr.createSvgTag(<?= $width === 58 ? 3 : 4 ?>, 0);
</script>

</body>
</html>
