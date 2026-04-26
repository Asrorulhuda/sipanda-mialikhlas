<?php
/**
 * Cetak Kisi-Kisi Soal — Format Profesional dengan Kop Surat
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

$sch = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
$guru = $pdo->prepare("SELECT * FROM tbl_guru WHERE id_guru=?"); $guru->execute([$id_guru]); $guru = $guru->fetch();

// Statistics
$dist_tingkat = ['Mudah' => 0, 'Sedang' => 0, 'Sulit' => 0];
$dist_taksonomi = ['C1' => 0, 'C2' => 0, 'C3' => 0, 'C4' => 0, 'C5' => 0, 'C6' => 0];
$dist_tipe = ['PG' => 0, 'Essay' => 0];
foreach ($soal_all as $s) {
    $dist_tingkat[$s['tingkat']] = ($dist_tingkat[$s['tingkat']] ?? 0) + 1;
    $dist_taksonomi[$s['taksonomi']] = ($dist_taksonomi[$s['taksonomi']] ?? 0) + 1;
    $dist_tipe[$s['tipe_soal']] = ($dist_tipe[$s['tipe_soal']] ?? 0) + 1;
}
$total = count($soal_all);

if (!function_exists('clean')) { function clean($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); } }
$taksonomi_label = [
    'C1' => 'Mengingat', 'C2' => 'Memahami', 'C3' => 'Mengaplikasikan',
    'C4' => 'Menganalisis', 'C5' => 'Mengevaluasi', 'C6' => 'Mencipta'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kisi-Kisi — <?= clean($paket['nama_ujian']) ?></title>
    <style>
        @page { size: A4 landscape; margin: 12mm 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Roboto, Arial, sans-serif; font-size: 10pt; color: #1a1a1a; line-height: 1.5; background: #fff; padding: 15px; }

        .kop-surat { display: flex; align-items: center; justify-content: center; gap: 20px; border-bottom: 4px double #000; padding-bottom: 10px; margin-bottom: 15px; text-align: center; }
        .kop-logo { width: 70px; height: 70px; object-fit: contain; }
        .kop-text { flex: 1; }
        .kop-text p.atas { font-size: 10pt; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        .kop-text h1 { font-size: 15pt; font-weight: bold; text-transform: uppercase; }
        .kop-text p.info { font-size: 8pt; color: #444; margin-top: 3px; }

        .doc-title-box { text-align: center; margin: 15px 0 10px; }
        .doc-title { font-size: 12pt; font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #333; display: inline-block; padding: 0 25px 4px; }

        .info-tbl { width: 100%; border-collapse: collapse; margin-bottom: 15px; border: 1.5px solid #000; }
        .info-tbl td { padding: 4px 8px; font-size: 9pt; border: 1px solid #444; }
        .info-tbl td.lbl { width: 130px; font-weight: bold; background: #f5f5f5; }
        .info-tbl td.sep { width: 8px; text-align: center; font-weight: bold; }

        .kisi-tbl { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .kisi-tbl th, .kisi-tbl td { border: 1.5px solid #000; padding: 5px 7px; font-size: 9pt; vertical-align: top; }
        .kisi-tbl th { background: #e5e7eb; font-weight: bold; text-align: center; text-transform: uppercase; font-size: 8.5pt; }
        .kisi-tbl td.center { text-align: center; }

        .dist-container { display: flex; gap: 20px; margin-bottom: 15px; }
        .dist-box { flex: 1; border: 1.5px solid #000; }
        .dist-box h4 { background: #e5e7eb; padding: 4px 8px; font-size: 9pt; text-align: center; text-transform: uppercase; border-bottom: 1px solid #000; }
        .dist-tbl { width: 100%; border-collapse: collapse; }
        .dist-tbl td { padding: 3px 8px; font-size: 9pt; border-top: 1px solid #ddd; }
        .dist-tbl td:last-child { text-align: center; font-weight: bold; }

        .footer-sign { margin-top: 30px; width: 100%; page-break-inside: avoid; }
        .footer-sign td { width: 50%; text-align: center; padding: 6px; vertical-align: top; font-size: 9.5pt; }
        .sign-space { height: 55px; }
        .sign-name { font-weight: bold; text-decoration: underline; text-transform: uppercase; }

        .no-print { padding: 10px 18px; background: #d97706; color: #fff; border: 0; border-radius: 8px; font-size: 13px; font-weight: bold; cursor: pointer; margin-bottom: 15px; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>

<button class="no-print" onclick="window.print()">🖨️ Cetak Kisi-Kisi</button>

<!-- KOP SURAT -->
<div class="kop-surat">
    <?php if (!empty($sch['logo_kiri'])): ?>
    <img src="<?= BASE_URL ?>gambar/<?= $sch['logo_kiri'] ?>" class="kop-logo" alt="Logo">
    <?php endif; ?>
    <div class="kop-text">
        <p class="atas"><?= clean($sch['instansi_atas'] ?? '') ?></p>
        <h1><?= clean($sch['nama_sekolah'] ?? '-') ?></h1>
        <?php if (!empty($sch['nama_yayasan'])): ?><p class="atas" style="margin-top:2px;font-size:9.5pt;"><?= clean($sch['nama_yayasan']) ?></p><?php endif; ?>
        <p class="info"><?= clean($sch['alamat'] ?? '') ?> | NPSN: <?= clean($sch['npsn'] ?? '-') ?> | Telp: <?= clean($sch['telepon'] ?? '-') ?></p>
    </div>
    <?php if (!empty($sch['logo_kanan'])): ?>
    <img src="<?= BASE_URL ?>gambar/<?= $sch['logo_kanan'] ?>" class="kop-logo" alt="Logo">
    <?php endif; ?>
</div>

<div class="doc-title-box">
    <h2 class="doc-title">KISI-KISI SOAL <?= strtoupper($paket['jenis_ujian']) ?></h2>
</div>

<table class="info-tbl">
    <tr><td class="lbl">Nama Ujian</td><td class="sep">:</td><td style="font-weight:bold;" colspan="4"><?= clean($paket['nama_ujian']) ?></td></tr>
    <tr>
        <td class="lbl">Mata Pelajaran</td><td class="sep">:</td><td style="font-weight:bold;"><?= clean($paket['nama_mapel'] ?? '-') ?></td>
        <td class="lbl">Kelas</td><td class="sep">:</td><td><?= clean($paket['nama_kelas'] ?? '-') ?></td>
    </tr>
    <tr>
        <td class="lbl">Semester</td><td class="sep">:</td><td><?= $paket['semester'] ?></td>
        <td class="lbl">Jumlah Soal</td><td class="sep">:</td><td><?= $total ?> (PG: <?= $dist_tipe['PG'] ?>, Essay: <?= $dist_tipe['Essay'] ?>)</td>
    </tr>
    <tr><td class="lbl">Materi / Topik</td><td class="sep">:</td><td colspan="4"><?= clean($paket['topik'] ?? '-') ?></td></tr>
</table>

<!-- TABEL KISI-KISI -->
<table class="kisi-tbl">
    <thead>
        <tr>
            <th width="35">No</th>
            <th width="180">Kompetensi / CP</th>
            <th width="120">Materi</th>
            <th>Indikator Soal</th>
            <th width="50">No Soal</th>
            <th width="50">Tipe</th>
            <th width="65">Tingkat</th>
            <th width="80">Taksonomi</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no_pg = 0; $no_essay = 0;
        foreach ($soal_all as $i => $s):
            if ($s['tipe_soal'] === 'PG') { $no_pg++; $no_display = "PG-{$no_pg}"; }
            else { $no_essay++; $no_display = "E-{$no_essay}"; }
        ?>
        <tr>
            <td class="center"><?= $i + 1 ?></td>
            <td style="font-size:8.5pt;"><?= clean($s['kompetensi'] ?? $paket['topik'] ?? '-') ?></td>
            <td style="font-size:8.5pt;"><?= clean($s['topik'] ?? $paket['topik'] ?? '-') ?></td>
            <td style="font-size:8.5pt;"><?= clean($s['indikator'] ?? mb_substr($s['pertanyaan'], 0, 80) . '...') ?></td>
            <td class="center" style="font-weight:bold;"><?= $no_display ?></td>
            <td class="center"><?= $s['tipe_soal'] ?></td>
            <td class="center" style="font-size:8.5pt;"><?= $s['tingkat'] ?></td>
            <td class="center" style="font-size:8.5pt;"><?= $s['taksonomi'] ?> (<?= $taksonomi_label[$s['taksonomi']] ?? '' ?>)</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- DISTRIBUSI -->
<div class="dist-container">
    <div class="dist-box">
        <h4>Distribusi Tingkat Kesulitan</h4>
        <table class="dist-tbl">
            <?php foreach ($dist_tingkat as $k => $v): ?>
            <tr><td><?= $k ?></td><td><?= $v ?> soal (<?= $total > 0 ? round($v / $total * 100) : 0 ?>%)</td></tr>
            <?php endforeach; ?>
            <tr style="border-top:2px solid #000;"><td><b>Total</b></td><td><b><?= $total ?></b></td></tr>
        </table>
    </div>
    <div class="dist-box">
        <h4>Distribusi Taksonomi Bloom</h4>
        <table class="dist-tbl">
            <?php foreach ($dist_taksonomi as $k => $v): if ($v === 0) continue; ?>
            <tr><td><?= $k ?> — <?= $taksonomi_label[$k] ?></td><td><?= $v ?> soal (<?= $total > 0 ? round($v / $total * 100) : 0 ?>%)</td></tr>
            <?php endforeach; ?>
            <tr style="border-top:2px solid #000;"><td><b>Total</b></td><td><b><?= $total ?></b></td></tr>
        </table>
    </div>
</div>

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
            <?= clean($sch['kota'] ?? 'Bekasi') ?>, <?= tgl_indo(date('Y-m-d')) ?><br>
            <strong>Guru Pengampu</strong>
            <div class="sign-space"></div>
            <p class="sign-name"><?= clean($guru['nama'] ?? '......................') ?></p>
            <p>NIP. <?= clean($guru['nip'] ?? '-') ?></p>
        </td>
    </tr>
</table>

</body>
</html>
