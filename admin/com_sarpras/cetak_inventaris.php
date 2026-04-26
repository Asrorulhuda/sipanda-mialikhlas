<?php
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin', 'waka_sarpras']);

if (empty($_POST['id_cetak'])) {
    die("<div style='font-family:sans-serif;text-align:center;padding:50px;'>Pilih minimal satu barang yang akan dicetak!</div>");
}

$ids = implode(',', array_map('intval', $_POST['id_cetak']));
$data = $pdo->query("SELECT b.*, g.nama as penanggung_jawab FROM tbl_sarpras_barang b LEFT JOIN tbl_guru g ON b.id_guru=g.id_guru WHERE b.id_barang IN ($ids) ORDER BY b.kode_barang ASC")->fetchAll();
$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();

$total_unit = 0;
foreach ($data as $d) $total_unit += $d['jumlah'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Inventaris Barang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #e2e8f0; }
        .page { background: white; margin: 0 auto; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); width: 297mm; min-height: 210mm; padding: 12mm 15mm; }
        .kop-surat { border-bottom: 3px solid #000; padding-bottom: 5px; margin-bottom: 2px; }
        .kop-surat::after { content: ''; display: block; border-bottom: 1px solid #000; margin-top: 2px; }
        @media print {
            body { background: white; }
            .page { margin: 0; box-shadow: none; }
            .no-print { display: none !important; }
            @page { size: landscape; margin: 10mm; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="fixed top-4 right-4 no-print flex gap-2 z-50">
    <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded shadow text-sm font-bold"><i class="fas fa-print mr-1"></i> Cetak</button>
    <button onclick="window.close()" class="bg-slate-600 text-white px-4 py-2 rounded shadow text-sm font-bold">Tutup</button>
</div>

<div class="page text-slate-800 text-sm">
    <!-- Kop Surat -->
    <div class="kop-surat flex items-center justify-between">
        <div class="w-20 text-center">
            <?php if($setting['logo_kiri']): ?><img src="<?= BASE_URL ?>assets/uploads/<?= $setting['logo_kiri'] ?>" class="max-h-14 mx-auto"><?php endif; ?>
        </div>
        <div class="flex-1 text-center">
            <h1 class="text-base font-bold uppercase tracking-wide">Pemerintah Provinsi Pendidikan</h1>
            <h2 class="text-lg font-black uppercase mt-0.5"><?= clean($setting['nama_sekolah']) ?></h2>
            <p class="text-[10px]"><?= clean($setting['alamat']) ?> | Telp: <?= clean($setting['telepon']??'-') ?> | NPSN: <?= clean($setting['npsn']??'-') ?></p>
        </div>
        <div class="w-20 text-center">
            <?php if($setting['logo_kanan']): ?><img src="<?= BASE_URL ?>assets/uploads/<?= $setting['logo_kanan'] ?>" class="max-h-14 mx-auto"><?php endif; ?>
        </div>
    </div>
    
    <div class="text-center mt-5 mb-4">
        <h3 class="text-sm font-bold uppercase underline underline-offset-4">Laporan Buku Induk Inventaris Barang / Aset</h3>
        <p class="text-[10px] mt-1 text-slate-500">Dicetak pada: <?= tgl_indo(date('Y-m-d')) ?> · Total <?= count($data) ?> jenis (<?= $total_unit ?> unit)</p>
    </div>

    <table class="w-full border-collapse border border-slate-400 text-[10px]">
        <thead>
            <tr class="bg-slate-100 font-bold">
                <th class="border border-slate-400 p-1.5 w-6">No</th>
                <th class="border border-slate-400 p-1.5 w-24">Kode Inventaris</th>
                <th class="border border-slate-400 p-1.5">Nama Barang/Aset</th>
                <th class="border border-slate-400 p-1.5 w-20">Kategori</th>
                <th class="border border-slate-400 p-1.5 w-16">Kondisi</th>
                <th class="border border-slate-400 p-1.5 w-10">Qty</th>
                <th class="border border-slate-400 p-1.5 w-10">Tahun</th>
                <th class="border border-slate-400 p-1.5 w-20">Sumber Dana</th>
                <th class="border border-slate-400 p-1.5 w-28">Penanggung Jawab</th>
                <th class="border border-slate-400 p-1.5">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($data as $i => $r): ?>
            <tr>
                <td class="border border-slate-400 p-1.5 text-center"><?= $i+1 ?></td>
                <td class="border border-slate-400 p-1.5 font-mono font-bold"><?= clean($r['kode_barang']) ?></td>
                <td class="border border-slate-400 p-1.5 font-semibold"><?= clean($r['nama_barang']) ?></td>
                <td class="border border-slate-400 p-1.5 text-center"><?= clean($r['kategori']) ?></td>
                <td class="border border-slate-400 p-1.5 text-center font-semibold <?= $r['kondisi']=='Baik' ? 'text-emerald-700' : 'text-red-700' ?>"><?= $r['kondisi'] ?></td>
                <td class="border border-slate-400 p-1.5 text-center font-bold"><?= $r['jumlah'] ?></td>
                <td class="border border-slate-400 p-1.5 text-center"><?= $r['tahun_pengadaan'] ?></td>
                <td class="border border-slate-400 p-1.5 text-center"><?= clean($r['sumber_dana'] ?: '-') ?></td>
                <td class="border border-slate-400 p-1.5"><?= clean($r['penanggung_jawab'] ?: '-') ?></td>
                <td class="border border-slate-400 p-1.5"><?= clean($r['keterangan'] ?: '-') ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="bg-slate-100 font-bold text-center">
                <td colspan="5" class="border border-slate-400 p-1.5 text-right">TOTAL UNIT :</td>
                <td class="border border-slate-400 p-1.5"><?= $total_unit ?></td>
                <td colspan="4" class="border border-slate-400 p-1.5"></td>
            </tr>
        </tbody>
    </table>
    
    <div class="flex justify-between mt-8 text-[10px] text-center">
        <div class="w-48">
            <p>Mengetahui,</p>
            <p>Kepala <?= clean($setting['nama_sekolah']) ?></p>
            <div class="h-16"></div>
            <p class="font-bold underline"><?= clean($setting['kepsek'] ?? '__________________') ?></p>
            <p>NIP. -</p>
        </div>
        <div class="w-48">
            <p><?= tgl_indo(date('Y-m-d')) ?></p>
            <p>Waka Sarana & Prasarana</p>
            <div class="h-16"></div>
            <p class="font-bold underline">__________________</p>
            <p>NIP. -</p>
        </div>
    </div>
</div>

</body>
</html>
