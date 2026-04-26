<?php
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin', 'waka_humas']);

if (empty($_POST['id_cetak'])) {
    die("<div style='font-family:sans-serif;text-align:center;padding:50px;'>Pilih minimal satu data kemitraan yang akan dicetak!</div>");
}

$ids = implode(',', array_map('intval', $_POST['id_cetak']));
$data = $pdo->query("SELECT * FROM tbl_humas_kemitraan WHERE id IN ($ids) ORDER BY nama_instansi ASC")->fetchAll();
$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Rekap Kemitraan MOU</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #e2e8f0; }
        .page { background: white; margin: 0 auto; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); width: 210mm; min-height: 297mm; padding: 15mm 20mm; }
        .kop-surat { border-bottom: 3px solid #000; padding-bottom: 5px; margin-bottom: 2px; }
        .kop-surat::after { content: ''; display: block; border-bottom: 1px solid #000; margin-top: 2px; }
        @media print {
            body { background: white; }
            .page { margin: 0; box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="fixed top-4 right-4 no-print flex gap-2">
    <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded shadow text-sm font-bold"><i class="fas fa-print mr-1"></i> Cetak</button>
    <button onclick="window.close()" class="bg-slate-600 text-white px-4 py-2 rounded shadow text-sm font-bold">Tutup</button>
</div>

<div class="page text-slate-800 text-sm">
    <!-- Kop Surat -->
    <div class="kop-surat flex items-center justify-between">
        <div class="w-20 text-center">
            <?php if($setting['logo_kiri']): ?><img src="<?= BASE_URL ?>assets/uploads/<?= $setting['logo_kiri'] ?>" class="max-h-16 mx-auto"><?php endif; ?>
        </div>
        <div class="flex-1 text-center">
            <h1 class="text-lg font-bold uppercase tracking-wide">Pemerintah Provinsi Pendidikan</h1>
            <h2 class="text-xl font-black uppercase mt-0.5"><?= clean($setting['nama_sekolah']) ?></h2>
            <p class="text-xs mt-0.5"><?= clean($setting['alamat']) ?></p>
            <p class="text-[10px]">Telp: <?= clean($setting['telepon']??'-') ?> | Email: <?= clean($setting['email']??'-') ?> | NPSN: <?= clean($setting['npsn']??'-') ?></p>
        </div>
        <div class="w-20 text-center">
            <?php if($setting['logo_kanan']): ?><img src="<?= BASE_URL ?>assets/uploads/<?= $setting['logo_kanan'] ?>" class="max-h-16 mx-auto"><?php endif; ?>
        </div>
    </div>
    
    <div class="text-center mt-6 mb-6">
        <h3 class="text-base font-bold uppercase underline underline-offset-4">Rekap Data Kemitraan / MOU</h3>
        <p class="text-xs mt-1 text-slate-500">Dicetak pada: <?= tgl_indo(date('Y-m-d')) ?></p>
    </div>

    <table class="w-full border-collapse border border-slate-400 text-xs">
        <thead>
            <tr class="bg-slate-100">
                <th class="border border-slate-400 p-2 w-8">No</th>
                <th class="border border-slate-400 p-2">Nama Instansi</th>
                <th class="border border-slate-400 p-2">Jenis Kerjasama</th>
                <th class="border border-slate-400 p-2 w-24">Mulai</th>
                <th class="border border-slate-400 p-2 w-24">Berakhir</th>
                <th class="border border-slate-400 p-2 w-16">Status</th>
                <th class="border border-slate-400 p-2">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($data as $i => $r): ?>
            <tr>
                <td class="border border-slate-400 p-2 text-center"><?= $i+1 ?></td>
                <td class="border border-slate-400 p-2 font-bold"><?= clean($r['nama_instansi']) ?></td>
                <td class="border border-slate-400 p-2"><?= clean($r['jenis_kerjasama']) ?></td>
                <td class="border border-slate-400 p-2 text-center"><?= date('d/m/Y', strtotime($r['tgl_mulai'])) ?></td>
                <td class="border border-slate-400 p-2 text-center"><?= date('d/m/Y', strtotime($r['tgl_selesai'])) ?></td>
                <td class="border border-slate-400 p-2 text-center font-bold"><?= $r['status'] ?></td>
                <td class="border border-slate-400 p-2"><?= clean($r['keterangan'] ?: '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="flex justify-end mt-12 text-center text-xs">
        <div class="w-56">
            <p><?= clean($setting['alamat']) ?>, <?= tgl_indo(date('Y-m-d')) ?></p>
            <p>Waka Humas</p>
            <div class="h-20"></div>
            <p class="font-bold underline">__________________</p>
            <p>NIP. -</p>
        </div>
    </div>
</div>

</body>
</html>
