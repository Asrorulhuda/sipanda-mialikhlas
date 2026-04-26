<?php
// Cetak & Export Helper - SIPANDA v2.0.1

/**
 * Generate print-ready HTML page for reports
 */
function cetak_header($title, $setting) {
    global $pdo;
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        @page { size: A4; margin: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 12px; color: #1a1a1a; line-height: 1.5; }
        .kop-surat { display: flex; align-items: center; gap: 15px; border-bottom: 3px double #1a1a1a; padding-bottom: 12px; margin-bottom: 20px; }
        .kop-logo { width: 70px; height: 70px; }
        .kop-logo img { width: 100%; height: 100%; object-fit: contain; }
        .kop-text { flex: 1; text-align: center; }
        .kop-text h1 { font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .kop-text h2 { font-size: 14px; font-weight: 600; margin-top: 2px; }
        .kop-text p { font-size: 11px; color: #444; }
        .doc-title { text-align: center; font-size: 14px; font-weight: bold; text-transform: uppercase; margin: 15px 0; padding: 8px; background: #f0f0f0; border-radius: 4px; }
        .doc-meta { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 11px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table th, table td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; font-size: 11px; }
        table th { background: #f5f5f5; font-weight: 600; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; }
        table tbody tr:nth-child(even) { background: #fafafa; }
        table tfoot td { font-weight: bold; background: #eee; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .text-green { color: #059669; }
        .text-red { color: #dc2626; }
        .total-row { background: #e8f5e9 !important; font-weight: bold; }
        .footer-cetak { margin-top: 30px; text-align: right; font-size: 11px; }
        .ttd { margin-top: 60px; }
        .ttd-nama { border-bottom: 1px solid #333; display: inline-block; padding-bottom: 2px; font-weight: bold; }
        .no-print { } 
        @media print { 
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        @media screen {
            body { max-width: 800px; margin: 20px auto; padding: 40px; background: #f5f5f5; }
            body > * { background: white; }
            .print-container { background: white; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,.1); border-radius: 8px; }
        }
        .btn-print { background: #2563eb; color: white; border: none; padding: 10px 24px; border-radius: 6px; cursor: pointer; font-size: 13px; margin-right: 8px; }
        .btn-print:hover { background: #1d4ed8; }
        .btn-back { background: #64748b; color: white; border: none; padding: 10px 24px; border-radius: 6px; cursor: pointer; font-size: 13px; text-decoration: none; }
        .toolbar { padding: 15px; text-align: center; margin-bottom: 20px; }

        /* Kwitansi Style */
        .kwitansi { border: 2px solid #333; padding: 25px; margin: 10px 0; }
        .kwitansi-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px; }
        .kwitansi-body { }
        .kwitansi-row { display: flex; margin: 8px 0; }
        .kwitansi-label { width: 150px; font-weight: 600; }
        .kwitansi-value { flex: 1; }
        .kwitansi-amount { font-size: 18px; font-weight: bold; background: #f0f0f0; padding: 10px; text-align: center; margin: 15px 0; border-radius: 4px; }
        .kwitansi-footer { display: flex; justify-content: space-between; margin-top: 30px; text-align: center; }
    </style>
</head>
<body>
<div class="toolbar no-print">
    <button class="btn-print" onclick="window.print()"><i>🖨️</i> Cetak</button>
    <a href="javascript:history.back()" class="btn-back">← Kembali</a>
</div>
<div class="print-container">
    <div class="kop-surat">
        <?php if (!empty($setting['logo_kiri'])): ?>
        <div class="kop-logo"><img src="<?= BASE_URL ?>gambar/<?= $setting['logo_kiri'] ?>" alt="Logo"></div>
        <?php endif; ?>
        <div class="kop-text">
            <p>YAYASAN PENDIDIKAN ISLAM ASRORUL HUDA</p>
            <h1><?= htmlspecialchars($setting['nama_sekolah'] ?? 'MI Asrorul Huda') ?></h1>
            <p>NPSN: <?= htmlspecialchars($setting['npsn'] ?? '-') ?></p>
            <p><?= htmlspecialchars($setting['alamat'] ?? '') ?></p>
            <?php if (!empty($setting['telepon'])): ?><p>Telp: <?= htmlspecialchars($setting['telepon']) ?><?= !empty($setting['email']) ? ' | Email: '.$setting['email'] : '' ?></p><?php endif; ?>
        </div>
        <?php if (!empty($setting['logo_kanan'])): ?>
        <div class="kop-logo"><img src="<?= BASE_URL ?>gambar/<?= $setting['logo_kanan'] ?>" alt="Logo"></div>
        <?php endif; ?>
    </div>
    <div class="doc-title"><?= htmlspecialchars($title) ?></div>
    <div class="doc-meta">
        <span>Tahun Ajaran: <?php $ta = get_ta_aktif($pdo); echo $ta ? $ta['tahun'] : '-'; ?></span>
        <span>Dicetak: <?= date('d/m/Y H:i') ?></span>
    </div>
    <?php
}

function cetak_footer($setting) {
    ?>
    <div class="footer-cetak">
        <p><?= htmlspecialchars($setting['alamat'] ?? 'Bekasi') ?>, <?= tgl_indo(date('Y-m-d')) ?></p>
        <p>Mengetahui,</p>
        <div class="ttd">
            <p class="ttd-nama"><?= htmlspecialchars($setting['kepsek'] ?? '......................') ?></p>
            <p>Kepala Sekolah</p>
        </div>
    </div>
</div>
</body>
</html>
    <?php
}

/**
 * Export data ke CSV (Excel compatible)
 */
function export_csv($filename, $headers, $data) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // BOM for Excel UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, $headers, ';');
    foreach ($data as $row) {
        fputcsv($output, $row, ';');
    }
    fclose($output);
    exit;
}
