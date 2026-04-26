<?php
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin', 'waka_keagamaan']);

if (empty($_POST['id_cetak'])) {
    die("<div style='font-family:sans-serif;text-align:center;padding:50px;'>Pilih minimal satu data santri yang akan dicetak!</div>");
}

$ids = implode(',', array_map('intval', $_POST['id_cetak']));
$data = $pdo->query("SELECT s.*, m.nama, m.nis, m.nisn, m.tempat_lahir, m.tanggal_lahir FROM tbl_agama_sertifikasi s JOIN tbl_siswa m ON s.id_siswa=m.id_siswa WHERE s.id IN ($ids) ORDER BY m.nama ASC")->fetchAll();
$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();

$logo = BASE_URL . "assets/uploads/" . ($setting['logo_web'] ?? 'logo.png');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Sertifikasi Keagamaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #e2e8f0; margin: 0; padding: 0; }
        .page { background: white; margin: 0 auto; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); width: 210mm; min-height: 297mm; padding: 20mm; position: relative; }
        .serif { font-family: 'Playfair Display', serif; }
        .kop-surat { border-bottom: 3px solid #000; padding-bottom: 5px; margin-bottom: 2px; }
        .kop-surat::after { content: ''; display: block; border-bottom: 1px solid #000; margin-top: 2px; }
        @media print {
            body { background: white; }
            .page { margin: 0; box-shadow: none; page-break-after: always; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="fixed top-4 right-4 no-print flex gap-2">
    <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded shadow"><i class="fas fa-print"></i> Cetak PDF</button>
    <button onclick="window.close()" class="bg-slate-600 text-white px-4 py-2 rounded shadow">Tutup</button>
</div>

<?php foreach ($data as $r): ?>
<div class="page text-slate-800">
    <!-- Kop Surat -->
    <div class="kop-surat flex items-center justify-between">
        <div class="w-24 text-center">
            <?php if($setting['logo_kiri']): ?><img src="<?= BASE_URL ?>assets/uploads/<?= $setting['logo_kiri'] ?>" class="max-h-20 mx-auto"><?php endif; ?>
        </div>
        <div class="flex-1 text-center">
            <h1 class="text-xl font-bold uppercase tracking-wide">Pemerintah Provinsi Pendidikan</h1>
            <h2 class="text-2xl font-black uppercase serif mt-1"><?= clean($setting['nama_sekolah']) ?></h2>
            <p class="text-sm mt-1"><?= clean($setting['alamat']) ?></p>
            <p class="text-xs">Telp: <?= clean($setting['telepon']??'-') ?> | Email: <?= clean($setting['email']??'-') ?> | NPSN: <?= clean($setting['npsn']??'-') ?></p>
        </div>
        <div class="w-24 text-center">
            <?php if($setting['logo_kanan']): ?><img src="<?= BASE_URL ?>assets/uploads/<?= $setting['logo_kanan'] ?>" class="max-h-20 mx-auto"><?php endif; ?>
        </div>
    </div>
    
    <div class="text-center mt-8 mb-10">
        <h3 class="text-2xl font-bold serif uppercase underline decoration-2 underline-offset-4 mb-2">Sertifikat Kelulusan</h3>
        <p class="text-sm">Nomor: SR.<?= date('Y/m', strtotime($r['tanggal_lulus'])) ?>/<?= str_pad($r['id'], 3, '0', STR_PAD_LEFT) ?>/BINA-AGAMA</p>
    </div>

    <div class="mb-8 text-justify leading-relaxed">
        <p class="mb-4">Segenap Pimpinan dan Pengasuh Pendidikan <?= clean($setting['nama_sekolah']) ?>, menerangkan dengan sesungguhnya bahwa:</p>
        
        <table class="w-4/5 mx-auto font-medium mb-6 mt-4">
            <tr><td class="w-40 py-1">Nama Lengkap</td><td class="w-4">:</td><td><b class="text-lg"><?= clean($r['nama']) ?></b></td></tr>
            <tr><td class="py-1">Tempat, Tgl Lahir</td><td>:</td><td><?= clean($r['tempat_lahir']) ?>, <?= tgl_indo($r['tanggal_lahir']) ?></td></tr>
            <tr><td class="py-1">NIS / NISN</td><td>:</td><td><?= clean($r['nis']??'-') ?> / <?= clean($r['nisn']??'-') ?></td></tr>
        </table>
        
        <p class="mb-2">Telah dinyatakan <b>LULUS</b> dalam menempuh munaqosyah / ujian program khusus:</p>
        <div class="text-center py-6 border-y-2 border-dashed border-slate-300 my-4 bg-slate-50">
            <h4 class="text-3xl font-black serif text-emerald-800"><?= clean($r['jenis_sertifikasi']) ?></h4>
            <p class="mt-2 text-sm text-slate-500">Dengan predikat kelulusan:</p>
            <p class="text-2xl font-bold mt-1 uppercase text-slate-800 tracking-wider">" <?= clean($r['predikat']) ?> "</p>
        </div>
        
        <?php if($r['catatan']): ?>
        <p class="italic text-sm my-4 text-center">Catatan Dewan Munaqis: " <?= clean($r['catatan']) ?> "</p>
        <?php endif; ?>
        
        <p class="mt-4">Demikian sertifikat penghargaan ini diberikan sebagai bukti atas pencapaian dan kompetensi spiritual siswa. Semoga ilmu yang diperoleh menjadi berkah, diamalkan, dan bermanfaat bagi agama, bangsa, dan negara.</p>
    </div>
    
    <!-- TTD -->
    <div class="flex justify-between mt-16 text-center text-sm">
        <div class="w-64">
            <p>Kepala <?= clean($setting['nama_sekolah']) ?></p>
            <div class="h-24"></div>
            <p class="font-bold underline"><?= clean($setting['kepsek']??'__________________') ?></p>
            <p>NIP. -</p>
        </div>
        <div class="w-64">
            <p>Penerbitan, <?= tgl_indo($r['tanggal_lulus']) ?></p>
            <p>Waka Bina Keagamaan</p>
            <div class="h-24"></div>
            <p class="font-bold underline">Dewan Penguji / Munaqis</p>
            <p>NIP. -</p>
        </div>
    </div>
    
</div>
<?php endforeach; ?>

</body>
</html>
