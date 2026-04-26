<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../config/cetak_helper.php';
cek_role(['admin','guru','kepsek']);

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT h.*, g.nama, g.nip, g.tmt, g.tugas_tambahan, c.jtm_jumlah, c.tunjangan_tetap 
                       FROM tbl_payroll_history h 
                       JOIN tbl_guru g ON h.id_guru = g.id_guru 
                       LEFT JOIN tbl_payroll_config c ON g.id_guru = c.id_guru
                       WHERE h.id_payroll = ?");
$stmt->execute([$id]);
$r = $stmt->fetch();

if (!$r) die("Data tidak ditemukan.");

$settings = $pdo->query("SELECT * FROM tbl_payroll_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();

// Redirect check: If guru, can only see their own
if ($_SESSION['role'] == 'guru' && $_SESSION['user_id'] != $r['id_guru']) {
    die("Akses ditolak.");
}

$thn_kerja = 0;
if ($r['tmt']) {
    $diff = (new DateTime($r['tmt']))->diff(new DateTime($r['tahun'].'-'.$r['bulan'].'-01'));
    $thn_kerja = $diff->y;
}

$total_gapok = $r['gapok_jtm'] + $r['gapok_masa_kerja'];
$penerimaan_lalu = $r['total_diterima'] - $r['selisih_bulan_lalu'];

cetak_header('SLIP RINCIAN HONOR GURU', $setting);
?>

<div style="text-align: center; margin-bottom: 20px;">
    <h3 style="margin: 0; text-transform: uppercase; font-size: 14px;">DAFTAR PENERIMAAN HONORARIUM GURU</h3>
    <p style="margin: 0; font-size: 11px;">Bulan: <?= bulan_indo($r['bulan']) ?> <?= $r['tahun'] ?></p>
</div>

<table style="border: none; margin-bottom: 15px; font-size: 11px;">
    <tr>
        <td style="border: none; width: 100px;">Nama Guru</td>
        <td style="border: none;">: <b><?= clean($r['nama']) ?></b></td>
        <td style="border: none; width: 100px; padding-left: 50px;">NIP</td>
        <td style="border: none;">: <?= clean($r['nip'] ?: '-') ?></td>
    </tr>
    <tr>
        <td style="border: none;">Tugas Utama</td>
        <td style="border: none;">: <?= clean($r['tugas_tambahan'] ?: '-') ?></td>
        <td style="border: none; padding-left: 50px;">TMT</td>
        <td style="border: none;">: <?= $r['tmt'] ? tgl_indo($r['tmt']) : '-' ?></td>
    </tr>
</table>

<table style="width: 100%; border-collapse: collapse; font-size: 11px;">
    <thead>
        <tr style="background: #f0f0f0;">
            <th style="padding: 6px; text-align: left; width: 30px;">NO</th>
            <th style="padding: 6px; text-align: left;">URAIAN KOMPONEN HONOR</th>
            <th style="padding: 6px; text-align: center; width: 100px;">SATUAN</th>
            <th style="padding: 6px; text-align: right; width: 120px;">JUMLAH (Rp)</th>
        </tr>
    </thead>
    <tbody>
        <!-- GAJI POKOK -->
        <tr style="background: #f9f9f9; font-weight: bold;">
            <td>2</td>
            <td>GAJI POKOK</td>
            <td></td>
            <td style="text-align: right;"><?= number_format($total_gapok, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td></td>
            <td style="padding-left: 20px;">a. JTM (Jam Tatap Muka)</td>
            <td style="text-align: center;"><?= $r['jam_kerja_jtm'] ?> Jam</td>
            <td style="text-align: right;"><?= number_format($r['gapok_jtm'], 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td></td>
            <td style="padding-left: 20px;">b. Masa Kerja</td>
            <td style="text-align: center;"><?= $thn_kerja ?> Tahun</td>
            <td style="text-align: right;"><?= number_format($r['gapok_masa_kerja'], 0, ',', '.') ?></td>
        </tr>

        <!-- TUNJANGAN -->
        <tr style="background: #f9f9f9; font-weight: bold;">
            <td>3</td>
            <td>TUNJANGAN-TUNJANGAN</td>
            <td></td>
            <td style="text-align: right;"><?= number_format($r['tunjangan_jabatan'] + $r['tunjangan_kehadiran'], 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td></td>
            <td style="padding-left: 20px;">a. Jabatan / Tugas Tambahan</td>
            <td style="text-align: center;">Score: <?= $r['score_total'] ?></td>
            <td style="text-align: right;"><?= number_format($r['tunjangan_jabatan'], 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td></td>
            <td style="padding-left: 20px;">b. Tunjangan Kehadiran (RFID)</td>
            <td style="text-align: center;"><?= $r['tunjangan_kehadiran'] / ($r['rate_kehadiran_pilih'] ?: 1) ?> Hari</td>
            <td style="text-align: right;"><?= number_format($r['tunjangan_kehadiran'], 0, ',', '.') ?></td>
        </tr>

        <!-- KELEBIHAN JTM -->
        <tr style="background: #f9f9f9; font-weight: bold;">
            <td>4</td>
            <td>KELEBIHAN JTM</td>
            <?php $jam_lebih = ($settings['rate_kelebihan_jtm'] > 0) ? round($r['kelebihan_jtm'] / $settings['rate_kelebihan_jtm']) : 0; ?>
            <td style="text-align: center; font-weight: normal;"><?= $jam_lebih ?> Jam</td>
            <td style="text-align: right;"><?= number_format($r['kelebihan_jtm'], 0, ',', '.') ?></td>
        </tr>

        <!-- POTONGAN -->
        <tr style="background: #f9f9f9; font-weight: bold;">
            <td>5</td>
            <td>POTONGAN JTM</td>
            <td style="text-align: center; font-weight: normal;"><?= $r['pot_jtm_jam'] ?> Jam</td>
            <td style="text-align: right; color: red;">(<?= number_format($r['potongan_jtm'], 0, ',', '.') ?>)</td>
        </tr>

        <?php if ($r['tunjangan_tetap'] > 0): ?>
        <tr>
            <td>6</td>
            <td>Tunjangan Lain-lain (Tetap)</td>
            <td></td>
            <td style="text-align: right;"><?= number_format($r['tunjangan_tetap'], 0, ',', '.') ?></td>
        </tr>
        <?php endif; ?>

        <!-- NETTO -->
        <tr style="background: #333; color: white; font-weight: bold;">
            <td colspan="2" style="padding: 10px;">6. PENDAPATAN YANG DITERIMA (TOTAL)</td>
            <td colspan="2" style="text-align: right; padding: 10px; font-size: 13px;">Rp <?= number_format($r['total_diterima'], 0, ',', '.') ?></td>
        </tr>
    </tbody>
</table>

<div style="margin-top: 15px; border: 1px solid #ddd; padding: 10px; font-size: 10px; background: #fafafa;">
    <div style="display: flex; justify-content: space-between;">
        <span>Pendapatan Bulan Lalu: <b>Rp <?= number_format($penerimaan_lalu, 0, ',', '.') ?></b></span>
        <span>Selisih: 
            <b style="color: <?= $r['selisih_bulan_lalu'] >= 0 ? 'green' : 'red' ?>;">
                <?= $r['selisih_bulan_lalu'] >= 0 ? '+' : '' ?><?= number_format($r['selisih_bulan_lalu'], 0, ',', '.') ?>
            </b>
        </span>
    </div>
</div>

<!-- REKAP KEHADIRAN BULANAN -->
<div style="margin-top: 15px; border: 1px solid #ddd; padding: 10px; font-size: 10px; background: #f7fbff;">
    <div style="font-weight: bold; margin-bottom: 8px; text-transform: uppercase; font-size: 11px; border-bottom: 1px solid #ccc; padding-bottom: 4px;">
        Rekap Kehadiran Bulan <?= bulan_indo($r['bulan']) ?> <?= $r['tahun'] ?>
    </div>
    <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
        <tr>
            <td style="padding: 5px 8px; border: 1px solid #ddd; background: #e8f5e9; text-align: center; width: 20%;">
                <div style="font-size: 9px; color: #666;">Hadir</div>
                <div style="font-size: 16px; font-weight: bold; color: #2e7d32;"><?= $r['hari_hadir'] ?? 0 ?></div>
                <div style="font-size: 8px; color: #999;">hari</div>
            </td>
            <td style="padding: 5px 8px; border: 1px solid #ddd; background: #e3f2fd; text-align: center; width: 20%;">
                <div style="font-size: 9px; color: #666;">Sakit</div>
                <div style="font-size: 16px; font-weight: bold; color: #1565c0;"><?= $r['hari_sakit'] ?? 0 ?></div>
                <div style="font-size: 8px; color: #999;">hari</div>
            </td>
            <td style="padding: 5px 8px; border: 1px solid #ddd; background: #f3e5f5; text-align: center; width: 20%;">
                <div style="font-size: 9px; color: #666;">Izin</div>
                <div style="font-size: 16px; font-weight: bold; color: #7b1fa2;"><?= $r['hari_izin'] ?? 0 ?></div>
                <div style="font-size: 8px; color: #999;">hari</div>
            </td>
            <td style="padding: 5px 8px; border: 1px solid #ddd; background: #ffebee; text-align: center; width: 20%;">
                <div style="font-size: 9px; color: #666;">Alpha</div>
                <div style="font-size: 16px; font-weight: bold; color: #c62828;"><?= $r['hari_alpha'] ?? 0 ?></div>
                <div style="font-size: 8px; color: #999;">hari</div>
            </td>
            <td style="padding: 5px 8px; border: 1px solid #ddd; background: #fff3e0; text-align: center; width: 20%;">
                <div style="font-size: 9px; color: #666;">Hari Efektif</div>
                <div style="font-size: 16px; font-weight: bold; color: #e65100;"><?= $r['hari_kerja_efektif'] ?? 0 ?></div>
                <div style="font-size: 8px; color: #999;">hari</div>
            </td>
        </tr>
    </table>
</div>

<div style="margin-top: 30px; display: flex; justify-content: space-between; font-size: 11px;">
    <div style="text-align: center; width: 200px;">
        <p>Penerima,</p>
        <br><br><br>
        <p><b>( <?= clean($r['nama']) ?> )</b></p>
    </div>
    <div style="text-align: center; width: 250px;">
        <p><?= $setting['kota'] ?? '' ?>, <?= tgl_indo(date('Y-m-d')) ?></p>
        <p>Bendahara Sekolah,</p>
        <br><br><br>
        <p><b>( ____________________ )</b></p>
    </div>
</div>

<?php cetak_footer($setting); ?>
