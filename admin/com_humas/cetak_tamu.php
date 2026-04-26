<?php
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin', 'waka_humas']);

$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
$data = [];
$periode_text = "";

if (isset($_POST['id_cetak'])) {
    $ids = implode(',', array_map('intval', $_POST['id_cetak']));
    $data = $pdo->query("SELECT * FROM tbl_humas_tamu WHERE id IN ($ids) ORDER BY tanggal DESC")->fetchAll();
    $periode_text = "Data Terpilih";
} elseif (isset($_POST['filter_range'])) {
    $tgl_awal = $_POST['tgl_awal'];
    $tgl_akhir = $_POST['tgl_akhir'];
    $stmt = $pdo->prepare("SELECT * FROM tbl_humas_tamu WHERE DATE(tanggal) BETWEEN ? AND ? ORDER BY tanggal ASC");
    $stmt->execute([$tgl_awal, $tgl_akhir]);
    $data = $stmt->fetchAll();
    $periode_text = tgl_indo($tgl_awal) . " s/d " . tgl_indo($tgl_akhir);
} elseif (isset($_POST['filter_bulan'])) {
    $bulan = $_POST['bulan'];
    $tahun = $_POST['tahun'];
    $stmt = $pdo->prepare("SELECT * FROM tbl_humas_tamu WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ? ORDER BY tanggal ASC");
    $stmt->execute([$bulan, $tahun]);
    $data = $stmt->fetchAll();
    $periode_text = "Bulan " . bulan_indo($bulan) . " " . $tahun;
} else {
    die("<div style='font-family:sans-serif;text-align:center;padding:50px;'>Parameter cetak tidak valid!</div>");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Buku Tamu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }
        .page { background: white; margin: 0 auto; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); width: 210mm; min-height: 297mm; padding: 15mm 20mm; }
        .kop-surat { border-bottom: 4px double #000; padding-bottom: 15px; margin-bottom: 20px; }
        @media print {
            body { background: white; }
            .page { margin: 0; box-shadow: none; width: 100%; border: none; }
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
    <!-- Kop Surat (Official) -->
    <div class="kop-surat flex items-center gap-6">
        <div class="w-24 text-center">
            <?php if($setting['logo_kiri']): ?><img src="<?= BASE_URL ?>gambar/<?= $setting['logo_kiri'] ?>" class="max-h-24 mx-auto"><?php endif; ?>
        </div>
        <div class="flex-1 text-center">
            <?php if(!empty($setting['instansi_atas'])): ?>
                <h4 class="text-xs font-bold uppercase tracking-widest text-slate-600"><?= clean($setting['instansi_atas']) ?></h4>
            <?php endif; ?>
            <?php if(!empty($setting['nama_yayasan'])): ?>
                <h4 class="text-sm font-bold uppercase text-slate-700 mt-1"><?= clean($setting['nama_yayasan']) ?></h4>
            <?php endif; ?>
            <h2 class="text-3xl font-black text-navy-950 uppercase mt-1 leading-none"><?= clean($setting['nama_sekolah']) ?></h2>
            <div class="w-20 h-0.5 bg-gold-500 mx-auto mt-2 mb-2"></div>
            <p class="text-[10px] leading-tight text-slate-500 italic"><?= clean($setting['alamat']) ?></p>
            <p class="text-[9px] text-slate-400 mt-1 font-mono uppercase tracking-tighter">Telp: <?= clean($setting['telepon']) ?> | Email: <?= clean($setting['email']) ?></p>
        </div>
        <div class="w-24 text-center">
            <?php if($setting['logo_web']): ?><img src="<?= BASE_URL ?>gambar/<?= $setting['logo_web'] ?>" class="max-h-24 mx-auto"><?php endif; ?>
        </div>
    </div>
    
    <div class="text-center mt-10 mb-8">
        <h3 class="text-lg font-black uppercase text-slate-900 tracking-wider">Laporan Buku Tamu Digital</h3>
        <p class="text-xs mt-1 text-slate-500">Periode: <span class="font-bold text-slate-800 italic"><?= $periode_text ?></span></p>
    </div>

    <table class="w-full border-collapse border border-slate-400 text-[9px]">
        <thead>
            <tr class="bg-slate-100">
                <th class="border border-slate-400 p-1 w-6 text-center uppercase tracking-tighter">No</th>
                <th class="border border-slate-400 p-1 w-16 text-center uppercase tracking-tighter">Tanggal</th>
                <th class="border border-slate-400 p-1 text-left uppercase tracking-tighter">Nama & Instansi Tamu</th>
                <th class="border border-slate-400 p-1 text-left uppercase tracking-tighter">Bertemu / Kepentingan</th>
                <th class="border border-slate-400 p-1 text-center uppercase tracking-tighter w-14">Masuk</th>
                <th class="border border-slate-400 p-1 text-center uppercase tracking-tighter w-14">Keluar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($data as $i => $r): ?>
            <tr>
                <td class="border border-slate-400 p-1 text-center font-mono text-slate-400"><?= $i+1 ?></td>
                <td class="border border-slate-400 p-1 text-center font-bold text-slate-700">
                    <?= date('d/m/y', strtotime($r['tanggal'])) ?>
                </td>
                <td class="border border-slate-400 p-1 leading-tight">
                    <span class="font-bold text-slate-900"><?= clean($r['nama_tamu']) ?></span>
                    <span class="text-slate-500 text-[8px] flex items-center gap-1 mt-0.5"><i class="fas fa-building opacity-30"></i> <?= clean($r['instansi']) ?></span>
                </td>
                <td class="border border-slate-400 p-1 leading-tight">
                    <span class="font-bold text-slate-700">Ke: <?= clean($r['bertemu_dengan'] ?: '-') ?></span><br>
                    <span class="text-slate-500 text-[8px] italic"><?= clean($r['tujuan']) ?></span>
                </td>
                <td class="border border-slate-400 p-1 text-center font-bold text-emerald-700">
                    <?= date('H:i', strtotime($r['tanggal'])) ?>
                </td>
                <td class="border border-slate-400 p-1 text-center font-bold text-slate-600">
                    <?= ($r['status'] == 'Keluar' && $r['waktu_keluar']) ? date('H:i', strtotime($r['waktu_keluar'])) : '-' ?>
                </td>
            </tr>
            <?php endforeach; if(!$data) echo '<tr><td colspan="6" class="p-8 text-center text-slate-400 italic">Tidak ada data tamu ditemukan.</td></tr>'; ?>
        </tbody>
    </table>
    
    <div class="flex justify-end mt-16 text-center text-xs">
        <div class="w-64">
            <p class="text-slate-500 mb-1 leading-tight"><?= clean($setting['alamat']) ?></p>
            <p class="font-bold text-slate-800"><?= tgl_indo(date('Y-m-d')) ?></p>
            <p class="mt-4 text-slate-600 italic">Petugas Keamanan / Humas,</p>
            <div class="h-24"></div>
            <p class="font-black underline uppercase text-slate-800 tracking-widest">__________________</p>
            <p class="text-[9px] text-slate-400 mt-1 uppercase italic">( Stempel & Tanda Tangan )</p>
        </div>
    </div>

    <!-- Page Footer -->
    <div class="mt-auto pt-10 text-[9px] text-slate-400 italic flex justify-between items-center border-t border-slate-100">
        <p>Dicetak otomatis melalui Sistem SIPANDA <?= APP_VERSION ?></p>
        <p>Halaman 1 dari 1</p>
    </div>
</div>

</body>
</html>
