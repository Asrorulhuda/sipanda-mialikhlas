<?php
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);

$id_guru = $_SESSION['user_id'];
$tgl = $_GET['tgl'] ?? date('Y-m-d');

// Fetch Setting
$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();

// Fetch Guru
$stmt = $pdo->prepare("SELECT nama FROM tbl_guru WHERE id_guru=?");
$stmt->execute([$id_guru]);
$nama_guru = $stmt->fetchColumn();

// Fetch Data Journal
$stmt = $pdo->prepare("SELECT j.*, m.nama_mapel, k.nama_kelas
    FROM tbl_journal j
    LEFT JOIN tbl_mapel m ON j.id_mapel = m.id_mapel
    LEFT JOIN tbl_kelas k ON j.id_kelas = k.id_kelas
    WHERE j.id_guru = ? AND j.tanggal = ?
    ORDER BY j.jam_ke ASC, j.id ASC
");
$stmt->execute([$id_guru, $tgl]);
$data = $stmt->fetchAll();

// Determine Semester based on month
$bulan = (int)date('n', strtotime($tgl));
$semester = ($bulan >= 7 && $bulan <= 12) ? '1 (Ganjil)' : '2 (Genap)';

// Get active TA
$ta = get_ta_aktif($pdo);
$tahun_pelajaran = $ta ? $ta['tahun'] : '-';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Jurnal KBM - <?= tgl_indo($tgl) ?></title>
    <style>
        @page { size: A4 landscape; margin: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 12px; color: #000; padding: 20px; }
        
        .toolbar { padding: 15px; text-align: center; margin-bottom: 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .btn-print { background: #2563eb; color: white; border: none; padding: 10px 24px; border-radius: 6px; cursor: pointer; font-size: 13px; margin-right: 8px; font-family: sans-serif; }
        .btn-print:hover { background: #1d4ed8; }
        .btn-back { background: #64748b; color: white; border: none; padding: 10px 24px; border-radius: 6px; cursor: pointer; font-size: 13px; text-decoration: none; font-family: sans-serif; }
        
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }

        /* Kop Surat (Custom Match Style) */
        .kop-surat { width: 100%; border: 1px solid #000; display: flex; align-items: center; padding: 10px; margin-bottom: 20px; }
        .kop-logo { width: 80px; text-align: center; }
        .kop-logo img { max-width: 100%; max-height: 80px; }
        .kop-text { flex: 1; text-align: center; }
        .kop-text h3 { font-size: 14px; font-weight: normal; margin-bottom: 2px; }
        .kop-text h2 { font-size: 16px; font-weight: bold; margin-bottom: 2px; }
        .kop-text h1 { font-size: 18px; font-weight: bold; margin-bottom: 3px; }
        .kop-text p { font-size: 11px; font-style: italic; }
        .kop-dokumen { width: 120px; border-left: 1px solid #000; text-align: center; align-self: stretch; display: flex; flex-direction: column; justify-content: center; }
        .kop-dokumen div { font-size: 11px; padding: 5px; }
        .kop-dokumen div:first-child { border-bottom: 1px solid #000; }

        /* Judul */
        .judul-jurnal { text-align: center; font-weight: bold; font-size: 14px; text-transform: uppercase; margin-bottom: 20px; }

        /* Meta Information */
        .meta-info { width: 100%; display: table; margin-bottom: 10px; font-size: 12px; }
        .meta-row { display: table-row; }
        .meta-col { display: table-cell; padding-bottom: 5px; }
        .meta-label { width: 100px; display: inline-block; }
        .meta-colon { width: 10px; display: inline-block; }

        /* Table Jurnal */
        .table-jurnal { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .table-jurnal th, .table-jurnal td { border: 1px solid #000; padding: 5px 8px; vertical-align: top; }
        .table-jurnal th { text-align: center; font-weight: bold; vertical-align: middle; }
        .text-center { text-align: center; }
        
        .w-no { width: 30px; }
        .w-tgl { width: 100px; }
        .w-jam { width: 50px; }
        .w-kelas { width: 80px; }
        .w-materi { width: auto; }
        .w-absen { width: 25px; }
        .w-ket { width: 100px; }
        
        /* TTD */
        .ttd-container { width: 100%; display: flex; justify-content: flex-end; margin-top: 30px; }
        .ttd-box { width: 250px; text-align: center; }
        .ttd-space { height: 70px; }
        .ttd-name { font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>

<div class="toolbar no-print">
    <button class="btn-print" onclick="window.print()">🖨️ Cetak Jurnal</button>
    <a href="journal.php" class="btn-back">← Kembali</a>
</div>

<div class="kop-surat">
    <div class="kop-logo">
        <?php if (!empty($setting['logo_kiri'])): ?>
            <img src="<?= BASE_URL ?>gambar/<?= $setting['logo_kiri'] ?>">
        <?php endif; ?>
    </div>
    <div class="kop-text">
        <h3>YAYASAN PENDIDIKAN ISLAM ASRORUL HUDA</h3>
        <h2><?= htmlspecialchars($setting['nama_sekolah'] ?? '') ?></h2>
        <p>NPSN: <?= htmlspecialchars($setting['npsn'] ?? '-') ?></p>
        <p><?= htmlspecialchars($setting['alamat'] ?? '') ?></p>
        <?php if (!empty($setting['telepon'])): ?>
            <p>Tlp. <?= htmlspecialchars($setting['telepon']) ?><?= !empty($setting['email']) ? ' | Email: '.$setting['email'] : '' ?></p>
        <?php endif; ?>
    </div>
    <div class="kop-logo" style="border-right: 1px solid #000; padding-right: 10px;">
        <?php if (!empty($setting['logo_kanan'])): ?>
            <img src="<?= BASE_URL ?>gambar/<?= $setting['logo_kanan'] ?>">
        <?php endif; ?>
    </div>
    <div class="kop-dokumen">
        <div>No.<br>Dokumen</div>
        <div style="font-weight: bold;">F.03.09.20.06</div>
    </div>
</div>

<div class="judul-jurnal">
    JURNAL KEGIATAN BELAJAR MENGAJAR (KBM)<br>
    <?= htmlspecialchars($setting['nama_sekolah'] ?? '') ?>
</div>

<!-- Meta Information -->
<div class="meta-info">
    <div class="meta-row">
        <div class="meta-col" style="width: 60%;">
            <span class="meta-label">Nama Guru</span><span class="meta-colon">:</span> <strong><?= clean($nama_guru) ?></strong><br>
            <span class="meta-label">Tanggal</span><span class="meta-colon">:</span> <?= tgl_indo($tgl) ?>
        </div>
        <div class="meta-col" style="width: 40%;">
            <span class="meta-label">Tahun Pelajaran</span><span class="meta-colon">:</span> <?= $tahun_pelajaran ?><br>
            <span class="meta-label">Semester</span><span class="meta-colon">:</span> <?= $semester ?>
        </div>
    </div>
</div>

<table class="table-jurnal">
    <thead>
        <tr>
            <th rowspan="2" class="w-no">No</th>
            <th rowspan="2" class="w-tgl">Mata Pelajaran</th>
            <th rowspan="2" class="w-jam">Jam<br>Ke-</th>
            <th rowspan="2" class="w-kelas">Kelas</th>
            <th colspan="2">Materi Mengajar</th>
            <th colspan="4">Kehadiran</th>
            <th rowspan="2" class="w-ket">KET</th>
        </tr>
        <tr>
            <th class="w-materi">Materi yang disampaikan</th>
            <th class="w-materi">Materi yang akan datang</th>
            <th class="w-absen">H</th>
            <th class="w-absen">I</th>
            <th class="w-absen">S</th>
            <th class="w-absen">A</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $no = 1;
        if (count($data) > 0) {
            foreach ($data as $r): 
        ?>
        <tr>
            <td class="text-center"><?= $no++ ?></td>
            <td><?= clean($r['nama_mapel']) ?></td>
            <td class="text-center"><?= clean($r['jam_ke']) ?></td>
            <td class="text-center"><?= clean($r['nama_kelas']) ?></td>
            <td><?= nl2br(clean($r['materi'])) ?></td>
            <td><?= nl2br(clean($r['materi_akan_datang'] ?? '')) ?></td>
            <td class="text-center"><?= $r['jml_h'] ?></td>
            <td class="text-center"><?= $r['jml_i'] ?></td>
            <td class="text-center"><?= $r['jml_s'] ?></td>
            <td class="text-center"><?= $r['jml_a'] ?></td>
            <td><?= clean($r['keterangan'] ?? '') ?></td>
        </tr>
        <?php 
            endforeach; 
        } else {
            /* Empty rows if no data */
            for ($i=0; $i<5; $i++) {
                echo '<tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
            }
        }
        ?>
    </tbody>
</table>

<div class="ttd-container">
    <div class="ttd-box">
        <p><?= htmlspecialchars($setting['alamat'] ?? 'Bekasi') ?>, <?= tgl_indo($tgl) ?></p>
        <p>Guru Mata Pelajaran,</p>
        <div class="ttd-space"></div>
        <p class="ttd-name"><?= clean($nama_guru) ?></p>
    </div>
</div>

</body>
</html>
