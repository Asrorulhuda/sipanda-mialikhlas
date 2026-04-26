<?php
require_once __DIR__ . '/../config/init.php';
cek_role(['admin', 'waka_kesiswaan', 'guru']);

$id_siswa = $_GET['id_siswa'] ?? 0;
$level = $_GET['level'] ?? 1;

// Ambil Data Siswa & Setting
$siswa = $pdo->prepare("SELECT s.*, k.nama_kelas FROM tbl_siswa s JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE s.id_siswa=?");
$siswa->execute([$id_siswa]);
$s = $siswa->fetch();

if(!$s) { echo "Siswa tidak ditemukan."; exit; }

$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
$ta_aktif = $pdo->query("SELECT * FROM tbl_tahun_ajaran WHERE status='aktif' LIMIT 1")->fetch();

// Ambil List Pelanggaran Tahun Ini
$violation_stmt = $pdo->prepare("SELECT * FROM tbl_bk WHERE id_siswa = ? AND id_ta = ? AND poin > 0 ORDER BY tanggal ASC");
$violation_stmt->execute([$id_siswa, $ta_aktif['id_ta'] ?? 0]);
$violations = $violation_stmt->fetchAll();

$total_poin = 0;
foreach($violations as $v) $total_poin += $v['poin'];

// Judul Surat
$titles = [1 => "SURAT PERINGATAN 1 (SP 1)", 2 => "SURAT PERINGATAN 2 (SP 2)", 3 => "SURAT PERNYATAAN / PENGEMBALIAN SISWA (SP 3)"];
$surat_no = "BK/" . date('Y') . "/" . str_pad($id_siswa, 4, '0', STR_PAD_LEFT);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak SP <?= $level ?> - <?= clean($s['nama']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none; }
            body { background: white; color: black; }
        }
        body { font-family: 'Times New Roman', Times, serif; }
        .kop-border { border-bottom: 3px double black; }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-12 shadow-lg min-h-[29.7cm]">
        <!-- ADVANCED UNIFIED KOP SURAT -->
        <div class="flex items-center justify-between kop-border pb-4 mb-6 text-black font-serif">
            <div class="w-24 text-center">
                <?php if(!empty($setting['logo_kiri'])): ?>
                    <img src="../gambar/<?= $setting['logo_kiri'] ?>" class="w-20 h-20 mx-auto object-contain">
                <?php else: ?>
                    <div class="w-20 h-20 mx-auto rounded-full border-2 border-black flex items-center justify-center font-bold text-2xl text-black">S</div>
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
                <p class="text-[11px] mt-1 pr-2"><?= clean($setting['alamat'] ?? '-') ?></p>
                <p class="text-[10px] italic">Telp: <?= clean($setting['telepon'] ?? '-') ?> | Email: <?= clean($setting['email'] ?? '-') ?></p>
            </div>
            <div class="w-24 text-center">
                <?php if(!empty($setting['logo_kanan'])): ?>
                    <img src="../gambar/<?= $setting['logo_kanan'] ?>" class="w-20 h-20 mx-auto object-contain">
                <?php endif; ?>
            </div>
        </div>

        <!-- NOMOR SURAT -->
        <div class="text-center mb-8">
            <h3 class="text-lg font-bold underline uppercase"><?= $titles[$level] ?></h3>
            <p class="text-sm">Nomor: <?= $surat_no ?></p>
        </div>

        <!-- ISI SURAT -->
        <div class="space-y-4 text-sm leading-relaxed text-justify">
            <p>Yang bertanda tangan di bawah ini, Kepala Sekolah / Guru Bimbingan Konseling <?= clean($setting['nama_sekolah']) ?>, menerangkan bahwa siswa tersebut di bawah ini:</p>
            
            <div class="pl-8 grid grid-cols-[120px_10px_1fr] gap-y-1">
                <span class="font-bold">Nama</span><span>:</span><span class="uppercase"><?= clean($s['nama']) ?></span>
                <span>NIS / NISN</span><span>:</span><span><?= clean($s['nis']) ?> / <?= clean($s['nisn']) ?></span>
                <span>Kelas</span><span>:</span><span><?= clean($s['nama_kelas']) ?></span>
                <span>Orang Tua</span><span>:</span><span><?= clean($s['nama_ayah'] ?: $s['nama_ibu']) ?></span>
            </div>

            <p>Telah mencapai akumulasi poin pelanggaran sebesar <b class="text-lg"><?= $total_poin ?> Poin</b> pada Tahun Pelajaran <?= clean($ta_aktif['tahun'] ?? '-') ?>. Berdasarkan peraturan tata tertib sekolah, maka dengan ini sekolah memberikan keputusan sebagai berikut:</p>

            <!-- KONTEN SP DINAMIS -->
            <?php if($level == 1): ?>
                <div class="bg-gray-50 p-4 border border-gray-200 indent-8 italic">
                    Memberikan <b>Surat Peringatan 1 (SP 1)</b> kepada siswa yang bersangkutan. Orang tua/wali siswa diwajibkan datang ke sekolah untuk melakukan koordinasi pembinaan. Siswa juga dibebankan sanksi pembinaan berupa penugasan sosial dalam lingkungan sekolah.
                </div>
            <?php elseif($level == 2): ?>
                <div class="bg-gray-50 p-4 border border-gray-200 indent-8 italic">
                    Memberikan <b>Surat Peringatan 2 (SP 2)</b>. Sehubungan dengan hal tersebut, siswa dikenakan <b>SKORSING selama 3 (tiga) hari kerja</b> terhitung sejak tanggal surat ini diterbitkan. Jika siswa kembali melakukan pelanggaran, maka sekolah akan mengambil tindakan tegas berupa pengembalian kepada orang tua.
                </div>
            <?php elseif($level == 3): ?>
                <div class="bg-gray-100 p-6 border-2 border-dashed border-black indent-8 font-bold">
                    Berdasarkan rapat pleno dewan guru, maka dengan ini siswa tersebut di atas dinyatakan <b>DIKEMBALIKAN KEPADA ORANG TUA / WALI</b> secara resmi (Dikeluarkan). Segala hak dan kewajiban siswa di sekolah ini dinyatakan berakhir.
                </div>
            <?php endif; ?>

            <p class="mt-4 font-bold underline">Riwayat Pelanggaran (Tahun Ini):</p>
            <table class="w-full border-collapse border border-gray-300 text-xs">
                <thead><tr class="bg-gray-100"><th class="border border-gray-300 p-1">No</th><th class="border border-gray-300 p-1">Tanggal</th><th class="border border-gray-300 p-1">Kategori</th><th class="border border-gray-300 p-1">Deskripsi</th><th class="border border-gray-300 p-1">Poin</th></tr></thead>
                <tbody>
                    <?php foreach($violations as $i => $v): ?>
                        <tr><td class="border border-gray-300 p-1 text-center"><?= $i+1 ?></td><td class="border border-gray-300 p-1"><?= tgl_indo($v['tanggal']) ?></td><td class="border border-gray-300 p-1 text-center"><?= $v['kategori'] ?></td><td class="border border-gray-300 p-1"><?= clean($v['deskripsi']) ?></td><td class="border border-gray-300 p-1 text-center"><?= $v['poin'] ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="font-bold bg-gray-50"><td colspan="4" class="border border-gray-300 p-1 text-right">TOTAL AKUMULASI POIN</td><td class="border border-gray-300 p-1 text-center"><?= $total_poin ?></td></tr>
                </tbody>
            </table>

            <p class="mt-6">Demikian surat ini dibuat untuk dapat dipergunakan sebagaimana mestinya. Semoga menjadi perhatian bagi siswa dan orang tua/wali agar dapat memperbaiki perilaku di masa mendatang.</p>
        </div>

        <!-- TANDA TANGAN -->
        <div class="mt-12 grid grid-cols-2 text-center text-sm text-black font-serif">
            <div>
                <p>Mengetahui,</p>
                <p class="mb-20">Orang Tua / Wali Siswa</p>
                <p class="font-bold underline uppercase">( ........................................ )</p>
            </div>
            <div>
                <p><?= clean($setting['kota'] ?? 'Kabupaten/Kota') ?>, <?= tgl_indo(date('Y-m-d')) ?></p>
                <p class="mb-20">Kepala Tata Usaha,</p>
                <p class="font-bold underline uppercase"><?= clean($setting['nama_tu'] ?? '........................................') ?></p>
                <p>NIP. -</p>
            </div>
            <?php if($level < 3): ?>
            <div class="col-span-2 mt-12">
                <div class="border-2 border-black p-4 inline-block italic">
                    <p class="text-xs mb-8">Materai 10.000</p>
                    <p class="font-bold underline uppercase"><?= clean($s['nama']) ?></p>
                    <p class="text-[10px]">(Tanda Tangan Siswa)</p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="mt-8 pt-4 border-t border-gray-200 text-[10px] text-center text-gray-500 italic">
            Dokumen ini dihasilkan secara otomatis oleh Sistem Informasi SIPANDA2 - <?= clean($setting['nama_sekolah']) ?>
        </div>
    </div>

    <!-- Tombol Print -->
    <div class="fixed bottom-8 right-8 no-print flex gap-2">
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-full shadow-xl font-bold hover:bg-blue-500 transition-all flex items-center gap-2"><i class="fas fa-print"></i> Cetak Surat Sekarang</button>
        <button onclick="window.close()" class="bg-gray-600 text-white px-6 py-2 rounded-full shadow-xl font-bold hover:bg-gray-500 transition-all flex items-center gap-2">Tutup</button>
    </div>
</body>
</html>
