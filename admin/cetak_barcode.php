<?php
require_once __DIR__ . '/../config/init.php';
// Allows admin, bendahara, kasir, or guru
if (!in_array($_SESSION['role'], ['admin', 'bendahara', 'kasir', 'guru'])) {
    die("Access Denied");
}

$id = $_GET['id'] ?? null;
if (!$id) die("ID Produk tidak ditemukan");

// Fetch product
$p = $pdo->prepare("SELECT * FROM tbl_produk WHERE id_produk = ?");
$p->execute([$id]);
$product = $p->fetch();

if (!$product) die("Produk tidak ditemukan");

// Security check for Guru
if ($_SESSION['role'] === 'guru' && $product['id_guru_penjual'] != $_SESSION['user_id']) {
    die("Anda tidak berhak mencetak barcode produk ini.");
}

$qty = (int)($_GET['qty'] ?? 1);
$setting = $pdo->query("SELECT nama_sekolah FROM tbl_setting WHERE id=1")->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Barcode — <?= clean($product['nama_produk']) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        @page { size: auto; margin: 0; }
        body { font-family: 'Courier New', Courier, monospace; margin: 10px; background: #fff; color: #000; }
        .grid { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 2mm; 
            width: 100%;
        }
        .label-card {
            padding: 5mm 2mm;
            border: 0.1mm solid #eee;
            text-align: center;
            box-sizing: border-box;
            page-break-inside: avoid;
            background: #fff;
        }
        .school-name { font-size: 8px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        .product-name { font-size: 11px; font-weight: 800; margin-bottom: 5px; height: 30px; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .price { font-size: 14px; font-weight: 900; margin-top: 5px; border-top: 1px solid #000; padding-top: 2px; }
        .barcode-svg { width: 100%; height: 50px; }
        
        /* Print Button styles (hidden when printing) */
        @media print {
            .no-print { display: none !important; }
            .label-card { border: none; border: 1px solid #eee; }
        }
        
        .controls {
            position: fixed; top: 20px; right: 20px;
            background: #fff; padding: 15px; border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); border: 1px solid #ddd;
            z-index: 1000;
        }
    </style>
</head>
<body>

<div class="controls no-print">
    <label style="font-size: 12px; font-weight: bold;">Jumlah Copy:</label><br>
    <input type="number" id="qty_input" value="<?= $qty ?>" min="1" max="100" style="margin: 5px 0; padding: 5px; width: 60px;">
    <button onclick="window.location.href = '?id=<?= $id ?>&qty=' + document.getElementById('qty_input').value" style="padding: 5px 10px; cursor: pointer;">Update</button>
    <hr style="margin: 10px 0;">
    <button onclick="window.print()" style="background: #000; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-weight: bold;">🖨️ CETAK SEKARANG</button>
    <br><br>
    <button onclick="window.close()" style="background: #eee; border: 1px solid #ddd; padding: 8px 10px; border-radius: 5px; cursor:pointer; width: 100%; font-size: 12px;">Tutup</button>
</div>

<div class="grid">
    <?php for ($i = 0; $i < $qty; $i++): ?>
    <div class="label-card">
        <div class="school-name">SIPANDA — <?= clean($setting['nama_sekolah']) ?></div>
        <div class="product-name"><?= clean($product['nama_produk']) ?></div>
        <svg class="barcode-svg" id="barcode-<?= $i ?>"></svg>
        <div class="price"><?= rupiah($product['harga']) ?></div>
    </div>
    <?php endfor; ?>
</div>

<script>
    window.addEventListener('load', function() {
        const sku = "<?= clean($product['sku']) ?>";
        const qty = <?= $qty ?>;
        
        for (let i = 0; i < qty; i++) {
            JsBarcode("#barcode-" + i, sku, {
                format: "CODE128",
                width: 1.5,
                height: 40,
                displayValue: true,
                fontSize: 10,
                margin: 0
            });
        }
    });
</script>

</body>
</html>
