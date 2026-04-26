<?php
/**
 * Cetak Kunci Jawaban — Format Profesional dengan Kop Surat
 */
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
$id_guru = $_SESSION['user_id'];
$id_paket = (int)($_GET['id'] ?? 0);

$paket_stmt = $pdo->prepare("SELECT pu.*, m.nama_mapel, k.nama_kelas FROM tbl_paket_ujian pu LEFT JOIN tbl_mapel m ON pu.id_mapel=m.id_mapel LEFT JOIN tbl_kelas k ON pu.id_kelas=k.id_kelas WHERE pu.id_paket=? AND pu.id_guru=?");
$paket_stmt->execute([$id_paket, $id_guru]);
$paket = $paket_stmt->fetch();
if (!$paket) { die('Paket tidak ditemukan.'); }

$soal_stmt = $pdo->prepare("SELECT bs.*, ps.nomor_urut, ps.bobot FROM tbl_paket_soal ps JOIN tbl_bank_soal bs ON ps.id_soal_bank=bs.id_soal_bank WHERE ps.id_paket=? ORDER BY ps.nomor_urut");
$soal_stmt->execute([$id_paket]);
$soal_all = $soal_stmt->fetchAll();
$soal_pg = array_values(array_filter($soal_all, fn($s) => $s['tipe_soal'] === 'PG'));
$soal_essay = array_values(array_filter($soal_all, fn($s) => $s['tipe_soal'] === 'Essay'));

$sch = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
$guru = $pdo->prepare("SELECT * FROM tbl_guru WHERE id_guru=?"); $guru->execute([$id_guru]); $guru = $guru->fetch();

if (!function_exists('clean')) { function clean($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); } }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kunci Jawaban — <?= clean($paket['nama_ujian']) ?></title>
    <style>
        @page { size: A4; margin: 15mm 18mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Roboto, Arial, sans-serif; font-size: 11pt; color: #1a1a1a; line-height: 1.55; background: #fff; padding: 20px; }

        .kop-surat { display: flex; align-items: center; justify-content: center; gap: 20px; border-bottom: 4px double #000; padding-bottom: 12px; margin-bottom: 20px; text-align: center; }
        .kop-logo { width: 80px; height: 80px; object-fit: contain; }
        .kop-text { flex: 1; }
        .kop-text p.atas { font-size: 11pt; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        .kop-text h1 { font-size: 17pt; font-weight: bold; text-transform: uppercase; }
        .kop-text p.info { font-size: 8.5pt; color: #444; margin-top: 4px; }

        .doc-title-box { text-align: center; margin: 20px 0 15px; }
        .doc-title { font-size: 13pt; font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #333; display: inline-block; padding: 0 30px 4px; }
        .doc-sub { font-size: 10pt; color: #555; margin-top: 5px; }

        .info-tbl { width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1.5px solid #000; }
        .info-tbl td { padding: 5px 10px; font-size: 10pt; border: 1px solid #444; }
        .info-tbl td.lbl { width: 145px; font-weight: bold; background: #f5f5f5; }
        .info-tbl td.sep { width: 10px; text-align: center; font-weight: bold; }

        .section-hdr { font-size: 11pt; font-weight: bold; text-transform: uppercase; background: #eee; padding: 6px 12px; margin: 20px 0 12px; border: 1px solid #ccc; border-left: 5px solid #333; }

        .kunci-tbl { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .kunci-tbl th, .kunci-tbl td { border: 1.5px solid #000; padding: 6px 10px; font-size: 10pt; vertical-align: top; }
        .kunci-tbl th { background: #eee; font-weight: bold; text-align: center; text-transform: uppercase; font-size: 9pt; }
        .kunci-tbl td.center { text-align: center; }
        .kunci-tbl td.jawaban { text-align: center; font-weight: bold; font-size: 12pt; color: #16a34a; }

        .essay-kunci { margin-bottom: 15px; page-break-inside: avoid; }
        .essay-kunci .no { font-weight: bold; }
        .essay-kunci .jawaban-box { background: #f0fdf4; border: 1px solid #bbf7d0; padding: 8px 12px; margin: 5px 0; border-radius: 4px; font-size: 10pt; }
        .essay-kunci .pembahasan { font-size: 9.5pt; color: #555; font-style: italic; margin-top: 3px; padding-left: 10px; border-left: 3px solid #e5e7eb; }

        .confidential { text-align: center; margin: 15px 0; padding: 8px; background: #fef2f2; border: 2px solid #fca5a5; color: #b91c1c; font-size: 10pt; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }

        .no-print { padding: 12px 20px; background: #059669; color: #fff; border: 0; border-radius: 8px; font-size: 13px; font-weight: bold; cursor: pointer; margin-bottom: 20px; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>

<button class="no-print" onclick="window.print()">🖨️ Cetak Kunci Jawaban</button>

<!-- KOP SURAT -->
<div class="kop-surat">
    <?php if (!empty($sch['logo_kiri'])): ?>
    <img src="<?= BASE_URL ?>gambar/<?= $sch['logo_kiri'] ?>" class="kop-logo" alt="Logo">
    <?php endif; ?>
    <div class="kop-text">
        <p class="atas"><?= clean($sch['instansi_atas'] ?? '') ?></p>
        <h1><?= clean($sch['nama_sekolah'] ?? '-') ?></h1>
        <?php if (!empty($sch['nama_yayasan'])): ?><p class="atas" style="margin-top:3px;font-size:10pt;"><?= clean($sch['nama_yayasan']) ?></p><?php endif; ?>
        <p class="info"><?= clean($sch['alamat'] ?? '') ?><br>NPSN: <?= clean($sch['npsn'] ?? '-') ?> | Telp: <?= clean($sch['telepon'] ?? '-') ?></p>
    </div>
    <?php if (!empty($sch['logo_kanan'])): ?>
    <img src="<?= BASE_URL ?>gambar/<?= $sch['logo_kanan'] ?>" class="kop-logo" alt="Logo">
    <?php endif; ?>
</div>

<div class="doc-title-box">
    <h2 class="doc-title">KUNCI JAWABAN <?= strtoupper($paket['jenis_ujian']) ?></h2>
    <p class="doc-sub"><?= clean($paket['nama_ujian']) ?></p>
</div>

<div class="confidential">⚠ RAHASIA — Hanya untuk Guru Pengampu ⚠</div>

<table class="info-tbl">
    <tr><td class="lbl">Mata Pelajaran</td><td class="sep">:</td><td style="font-weight:bold;"><?= clean($paket['nama_mapel'] ?? '-') ?></td><td class="lbl">Kelas</td><td class="sep">:</td><td><?= clean($paket['nama_kelas'] ?? '-') ?></td></tr>
    <tr><td class="lbl">Tipe / Jenis</td><td class="sep">:</td><td><?= $paket['tipe_ujian'] ?> / <?= $paket['jenis_ujian'] ?></td><td class="lbl">Semester</td><td class="sep">:</td><td><?= $paket['semester'] ?></td></tr>
</table>

<!-- KUNCI PG -->
<?php if (!empty($soal_pg)): ?>
<div class="section-hdr">I. Kunci Jawaban Pilihan Ganda</div>
<table class="kunci-tbl">
    <thead>
        <tr><th width="50">No</th><th width="80">Jawaban</th><th>Pembahasan</th><th width="70">Tingkat</th><th width="60">Taksonomi</th></tr>
    </thead>
    <tbody>
        <?php foreach ($soal_pg as $i => $s): ?>
        <tr>
            <td class="center"><?= $i + 1 ?></td>
            <td class="jawaban"><?= clean($s['jawaban']) ?></td>
            <td style="font-size:9.5pt;"><?= clean($s['pembahasan'] ?? '-') ?></td>
            <td class="center" style="font-size:9pt;"><?= $s['tingkat'] ?></td>
            <td class="center" style="font-size:9pt;"><?= $s['taksonomi'] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- KUNCI ESSAY -->
<?php if (!empty($soal_essay)): ?>
<div class="section-hdr">II. Kunci Jawaban Essay</div>
<?php foreach ($soal_essay as $i => $s): ?>
<div class="essay-kunci">
    <p><span class="no"><?= $i + 1 ?>.</span> <?= clean($s['pertanyaan']) ?></p>
    <div class="jawaban-box">
        <b>Jawaban:</b><br><?= nl2br(clean($s['jawaban'] ?? '-')) ?>
    </div>
    <?php if ($s['pembahasan']): ?>
    <div class="pembahasan"><b>Pembahasan:</b> <?= clean($s['pembahasan']) ?></div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

</body>
</html>
