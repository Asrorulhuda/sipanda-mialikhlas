<?php
/**
 * Cetak Naskah Soal Ujian — Format Profesional dengan Kop Surat
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naskah Soal — <?= clean($paket['nama_ujian']) ?></title>
    <style>
        @page { size: A4; margin: 15mm 18mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Roboto, Arial, sans-serif; font-size: 11pt; color: #1a1a1a; line-height: 1.55; background: #fff; padding: 20px; }

        .kop-surat { display: flex; align-items: center; justify-content: center; gap: 20px; border-bottom: 4px double #000; padding-bottom: 12px; margin-bottom: 20px; text-align: center; }
        .kop-logo { width: 80px; height: 80px; object-fit: contain; }
        .kop-text { flex: 1; }
        .kop-text p.atas { font-size: 11pt; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        .kop-text h1 { font-size: 17pt; font-weight: bold; text-transform: uppercase; color: #000; line-height: 1.15; }
        .kop-text p.info { font-size: 8.5pt; color: #444; margin-top: 4px; }

        .doc-title-box { text-align: center; margin: 20px 0 15px; }
        .doc-title { font-size: 13pt; font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #333; display: inline-block; padding: 0 30px 4px; letter-spacing: 1px; }

        .info-tbl { width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1.5px solid #000; }
        .info-tbl td { padding: 5px 10px; font-size: 10pt; border: 1px solid #444; }
        .info-tbl td.lbl { width: 145px; font-weight: bold; background: #f5f5f5; }
        .info-tbl td.sep { width: 10px; text-align: center; font-weight: bold; }

        .petunjuk { background: #f9f9f9; border: 1px solid #ddd; border-left: 5px solid #333; padding: 10px 15px; margin-bottom: 20px; font-size: 10pt; }
        .petunjuk b { display: block; margin-bottom: 5px; font-size: 10.5pt; text-transform: uppercase; }

        .section-hdr { font-size: 11pt; font-weight: bold; text-transform: uppercase; background: #eee; padding: 6px 12px; margin: 20px 0 12px; border: 1px solid #ccc; border-left: 5px solid #333; }

        .soal-item { margin-bottom: 12px; page-break-inside: avoid; }
        .soal-no { display: inline-block; width: 28px; font-weight: bold; vertical-align: top; }
        .soal-text { display: inline-block; width: calc(100% - 35px); vertical-align: top; }
        .opsi-grid { margin: 4px 0 0 28px; }
        .opsi-item { padding: 2px 0; }
        .opsi-letter { display: inline-block; width: 22px; font-weight: bold; }

        .essay-lines { margin: 8px 0 0 28px; }
        .essay-line { border-bottom: 1px dotted #999; height: 24px; }

        .footer-sign { margin-top: 40px; width: 100%; page-break-inside: avoid; }
        .footer-sign td { width: 50%; text-align: center; padding: 8px; vertical-align: top; font-size: 10pt; }
        .sign-space { height: 65px; }
        .sign-name { font-weight: bold; text-decoration: underline; text-transform: uppercase; }

        .no-print { padding: 12px 20px; background: #7c3aed; color: #fff; border: 0; border-radius: 8px; font-size: 13px; font-weight: bold; cursor: pointer; margin-bottom: 20px; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>

<button class="no-print" onclick="window.print()"><i>🖨️</i> Cetak Naskah Soal</button>

<!-- KOP SURAT -->
<div class="kop-surat">
    <?php if (!empty($sch['logo_kiri'])): ?>
    <img src="<?= BASE_URL ?>gambar/<?= $sch['logo_kiri'] ?>" class="kop-logo" alt="Logo">
    <?php endif; ?>
    <div class="kop-text">
        <p class="atas"><?= clean($sch['instansi_atas'] ?? 'PEMERINTAH KABUPATEN') ?></p>
        <h1><?= clean($sch['nama_sekolah'] ?? '-') ?></h1>
        <?php if (!empty($sch['nama_yayasan'])): ?>
        <p class="atas" style="margin-top:3px;font-size:10pt;"><?= clean($sch['nama_yayasan']) ?></p>
        <?php endif; ?>
        <p class="info"><?= clean($sch['alamat'] ?? '') ?><br>NPSN: <?= clean($sch['npsn'] ?? '-') ?> | Telp: <?= clean($sch['telepon'] ?? '-') ?> | Email: <?= clean($sch['email'] ?? '-') ?></p>
    </div>
    <?php if (!empty($sch['logo_kanan'])): ?>
    <img src="<?= BASE_URL ?>gambar/<?= $sch['logo_kanan'] ?>" class="kop-logo" alt="Logo">
    <?php endif; ?>
</div>

<div class="doc-title-box">
    <h2 class="doc-title">NASKAH SOAL <?= strtoupper($paket['jenis_ujian']) ?></h2>
</div>

<!-- IDENTITAS UJIAN -->
<table class="info-tbl">
    <tr>
        <td class="lbl">Nama Ujian</td><td class="sep">:</td>
        <td colspan="4" style="font-weight:bold;"><?= clean($paket['nama_ujian']) ?></td>
    </tr>
    <tr>
        <td class="lbl">Mata Pelajaran</td><td class="sep">:</td>
        <td style="font-weight:bold;"><?= clean($paket['nama_mapel'] ?? '-') ?></td>
        <td class="lbl">Kelas</td><td class="sep">:</td>
        <td><?= clean($paket['nama_kelas'] ?? '-') ?></td>
    </tr>
    <tr>
        <td class="lbl">Tipe Ujian</td><td class="sep">:</td>
        <td><?= clean($paket['tipe_ujian']) ?></td>
        <td class="lbl">Jenis</td><td class="sep">:</td>
        <td><?= clean($paket['jenis_ujian']) ?></td>
    </tr>
    <tr>
        <td class="lbl">Hari / Tanggal</td><td class="sep">:</td>
        <td><?= $paket['tanggal_ujian'] ? tgl_indo($paket['tanggal_ujian']) : '...........................' ?></td>
        <td class="lbl">Waktu</td><td class="sep">:</td>
        <td><?= $paket['durasi_menit'] ?> Menit</td>
    </tr>
    <tr>
        <td class="lbl">Semester</td><td class="sep">:</td>
        <td colspan="4"><?= clean($paket['semester']) ?></td>
    </tr>
</table>

<!-- PETUNJUK -->
<?php if ($paket['petunjuk_umum']): ?>
<div class="petunjuk">
    <b>Petunjuk Pengerjaan:</b>
    <?= nl2br(clean($paket['petunjuk_umum'])) ?>
</div>
<?php else: ?>
<div class="petunjuk">
    <b>Petunjuk Pengerjaan:</b>
    1. Tulislah nama, kelas, dan no. absen pada lembar jawaban.<br>
    2. Kerjakan soal yang dianggap mudah terlebih dahulu.<br>
    3. Periksa kembali jawaban sebelum dikumpulkan.
</div>
<?php endif; ?>

<!-- SOAL PG -->
<?php if (!empty($soal_pg)): ?>
<div class="section-hdr">I. Pilihan Ganda (<?= count($soal_pg) ?> Soal)</div>
<?php foreach ($soal_pg as $i => $s): ?>
<div class="soal-item">
    <span class="soal-no"><?= $i + 1 ?>.</span>
    <span class="soal-text">
        <?= clean($s['pertanyaan']) ?>
        <div class="opsi-grid">
            <?php
            $opts = ['A' => $s['opsi_a'], 'B' => $s['opsi_b'], 'C' => $s['opsi_c'], 'D' => $s['opsi_d'], 'E' => $s['opsi_e']];
            foreach ($opts as $k => $v):
                if (!$v) continue;
            ?>
            <div class="opsi-item"><span class="opsi-letter"><?= $k ?>.</span> <?= clean($v) ?></div>
            <?php endforeach; ?>
        </div>
    </span>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- SOAL ESSAY -->
<?php if (!empty($soal_essay)): ?>
<div class="section-hdr">II. Essay / Uraian (<?= count($soal_essay) ?> Soal)</div>
<?php foreach ($soal_essay as $i => $s): ?>
<div class="soal-item">
    <span class="soal-no"><?= $i + 1 ?>.</span>
    <span class="soal-text">
        <?= clean($s['pertanyaan']) ?>
        <div class="essay-lines">
            <?php for ($l = 0; $l < 5; $l++): ?><div class="essay-line"></div><?php endfor; ?>
        </div>
    </span>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- TANDA TANGAN -->
<table class="footer-sign">
    <tr>
        <td>
            Mengetahui,<br><strong>Kepala Sekolah</strong>
            <div class="sign-space"></div>
            <p class="sign-name"><?= clean($sch['kepsek'] ?? '......................') ?></p>
            <p>NIP. <?= clean($sch['nip_kepsek'] ?? '......................') ?></p>
        </td>
        <td>
            <?= clean($sch['kota'] ?? 'Bekasi') ?>, <?= $paket['tanggal_ujian'] ? tgl_indo($paket['tanggal_ujian']) : tgl_indo(date('Y-m-d')) ?><br>
            <strong>Guru Pengampu</strong>
            <div class="sign-space"></div>
            <p class="sign-name"><?= clean($guru['nama'] ?? '......................') ?></p>
            <p>NIP. <?= clean($guru['nip'] ?? '-') ?></p>
        </td>
    </tr>
</table>

</body>
</html>
