<?php
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);

$id = (int)$_GET['id'];
$data = $pdo->prepare("SELECT s.*, si.*, t.tahun FROM tbl_skl s JOIN tbl_siswa si ON s.id_siswa=si.id_siswa LEFT JOIN tbl_tahun_ajaran t ON s.id_ta=t.id_ta WHERE s.id=?");
$data->execute([$id]);
$skl = $data->fetch();

if (!$skl) die("Data SKL tidak ditemukan!");

$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
$verify_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . BASE_URL . "verify.php?token=" . $skl['v_token'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak SKL - <?= clean($skl['nama']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 0; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; padding: 20px; }
        .sheet { background: white; width: 210mm; height: 297mm; margin: 0 auto; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); padding: 25mm 20mm; position: relative; overflow: hidden; }
        .watermark { 
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px; font-weight: 900; color: rgba(0,0,0,0.03); white-space: nowrap; pointer-events: none; text-transform: uppercase; z-index: 0;
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 100px;
        }
        .content { position: relative; z-index: 1; }
        .kop-surat { border-bottom: 4px double #000; padding-bottom: 15px; margin-bottom: 30px; }
        h1, h2, h3 { font-family: 'Crimson Pro', serif; }
        .line-height-extra { line-height: 1.8; }
        
        @media print {
            body { background: none; padding: 0; }
            .sheet { margin: 0; box-shadow: none; width: 100%; height: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="fixed top-6 right-6 no-print flex gap-2">
    <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-3 rounded-xl shadow-xl font-bold flex items-center gap-2 hover:bg-blue-500 transition-all"><i class="fas fa-print"></i> Cetak SKL</button>
    <button onclick="window.close()" class="bg-slate-600 text-white px-6 py-3 rounded-xl shadow-xl font-bold flex items-center gap-2 hover:bg-slate-500 transition-all">Tutup</button>
</div>

<div class="sheet">
    <!-- Watermark Background -->
    <div class="watermark"><?php for($i=0;$i<12;$i++) echo "<div>".clean($setting['nama_sekolah'])."</div>"; ?></div>
    
    <div class="content">
        <!-- ADVANCED UNIFIED KOP SURAT -->
        <div class="kop-surat flex items-center justify-between font-serif pb-4 mb-8 border-b-[3px] border-black">
            <div class="w-24 text-center">
                <?php if(!empty($setting['logo_kiri'])): ?>
                    <img src="../gambar/<?= $setting['logo_kiri'] ?>" class="w-20 h-20 mx-auto object-contain">
                <?php else: ?>
                    <div class="w-20 h-20 mx-auto rounded-full border-2 border-black flex items-center justify-center font-bold text-2xl">S</div>
                <?php endif; ?>
            </div>
            <div class="flex-1 text-center px-4">
                <?php if(!empty($setting['instansi_atas'])): ?>
                    <h3 class="text-xs font-bold uppercase tracking-widest leading-none mb-1 text-black"><?= clean($setting['instansi_atas']) ?></h3>
                <?php endif; ?>
                <?php if(!empty($setting['nama_yayasan'])): ?>
                    <h2 class="text-sm font-bold uppercase tracking-wide leading-none mb-1 text-black"><?= clean($setting['nama_yayasan']) ?></h2>
                <?php endif; ?>
                <h1 class="text-2xl font-extrabold uppercase mt-1 tracking-wider text-black"><?= clean($setting['nama_sekolah'] ?? 'SEKOLAH MASA DEPAN') ?></h1>
                <p class="text-[11px] mt-1 pr-2 text-black"><?= clean($setting['alamat'] ?? '-') ?></p>
                <p class="text-[10px] italic text-black">Telp: <?= clean($setting['telepon'] ?? '-') ?> | Email: <?= clean($setting['email'] ?? '-') ?></p>
            </div>
            <div class="w-24 text-center">
                <?php if(!empty($setting['logo_kanan'])): ?>
                    <img src="../gambar/<?= $setting['logo_kanan'] ?>" class="w-20 h-20 mx-auto object-contain">
                <?php endif; ?>
            </div>
        </div>

        <!-- Judul Surat -->
        <div class="text-center mb-10">
            <h2 class="text-xl font-bold uppercase underline underline-offset-4 decoration-2">Surat Keterangan Lulus</h2>
            <p class="text-sm font-semibold mt-1">Nomor: <?= clean($skl['nomor_skl']) ?></p>
        </div>

        <!-- Isi Surat -->
        <div class="text-sm line-height-extra mb-8 text-justify">
            <p class="mb-4">Perkenalkan, Kepala <?= clean($setting['nama_sekolah']) ?> dengan ini menerangkan bahwa:</p>
            
            <table class="w-full mb-6 ml-10">
                <tr><td class="w-48 py-1">Nama Lengkap</td><td class="w-4 py-1 text-center">:</td><td class="py-1 font-bold uppercase"><?= clean($skl['nama']) ?></td></tr>
                <tr><td class="py-1">Tempat, Tanggal Lahir</td><td class="py-1 text-center">:</td><td class="py-1"><?= clean($skl['tempat_lahir']) ?>, <?= tgl_indo($skl['tgl_lahir']) ?></td></tr>
                <tr><td class="py-1">Nomor Induk Siswa (NIS)</td><td class="py-1 text-center">:</td><td class="py-1"><?= clean($skl['nis']) ?></td></tr>
                <tr><td class="py-1">Nomor Induk Siswa Nasional</td><td class="py-1 text-center">:</td><td class="py-1 font-semibold text-blue-900"><?= clean($skl['nisn']) ?></td></tr>
                <tr><td class="py-1">Nama Orang Tua / Wali</td><td class="py-1 text-center">:</td><td class="py-1"><?= clean($skl['nama_ayah'] ?? $skl['nama_wali']) ?></td></tr>
            </table>

            <p class="mb-4">Berdasarkan hasil rapat Dewan Guru tentang kelulusan siswa Tahun Ajaran <?= clean($skl['tahun']) ?>, yang bersangkutan dinyatakan:</p>
            
            <div class="text-center my-8">
                <div class="inline-block px-12 py-4 border-4 border-emerald-600 rounded-2xl">
                    <h3 class="text-4xl font-black text-emerald-700 uppercase tracking-widest italic">LULUS</h3>
                </div>
            </div>

            <p>Demikian Surat Keterangan Lulus ini diberikan untuk dapat dipergunakan sebagaimana mestinya. Surat ini berlaku sampai dengan diterbitkannya Ijazah Asli dari Dinas Pendidikan terkait.</p>
        </div>

        <!-- Tanda Tangan & QR -->
        <div class="flex justify-between items-end mt-20 relative">
            <div class="w-48 text-center ml-10">
                <p class="text-[10px] text-slate-400 mb-2 uppercase tracking-tighter">Scan untuk Verifikasi Keaslian</p>
                <div class="p-2 border rounded-xl bg-white inline-block">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($verify_url) ?>" class="w-24 h-24">
                </div>
            </div>
            
            <div class="w-64 text-center mr-10 relative text-black font-serif">
                <p class="mb-0 text-sm"><?= clean($setting['kota'] ?? 'Kabupaten/Kota') ?>, <?= tgl_indo($skl['tanggal']) ?></p>
                <p class="mb-20 text-sm font-semibold">Kepala Tata Usaha,</p>
                <p class="font-bold text-base underline uppercase"><?= clean($setting['nama_tu'] ?? '........................................') ?></p>
                <p class="text-sm">NIP. -</p>
            </div>
        </div>
    </div>
    
    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 text-[9px] text-slate-400 font-mono tracking-widest border-t border-slate-100 pt-2 w-full text-center">
        Diterbitkan secara resmi oleh <?= clean($setting['nama_sekolah']) ?> SIPANDA Security Verified Token ID: <?= clean($skl['v_token']) ?>
    </div>
</div>

</body>
</html>
