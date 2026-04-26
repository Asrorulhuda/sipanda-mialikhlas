<?php
// ============================================================
// CETAK REKAP JTM GURU
// ============================================================
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin', 'waka_kurikulum']);
cek_fitur('akademik');

// Setting sekolah
$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id = 1")->fetch();

// Tahun ajaran
$ta_aktif = $pdo->query("SELECT tahun FROM tbl_tahun_ajaran WHERE status='aktif' LIMIT 1")->fetchColumn();
if (!$ta_aktif) $ta_aktif = date('Y') . '/' . (date('Y') + 1);

// Ambil semua guru aktif
$guru_stmt = $pdo->query("SELECT id_guru, nama, nip, tugas_tambahan FROM tbl_guru WHERE status='Aktif' ORDER BY nama");
$guru_list = $guru_stmt->fetchAll();

// Ambil semua kelas untuk header kolom
$kelas_stmt = $pdo->query("SELECT id_kelas, nama_kelas FROM tbl_kelas ORDER BY nama_kelas ASC");
$semua_kelas = $kelas_stmt->fetchAll();

// Ambil rekap JTM per guru per kelas
$jadwal_stmt = $pdo->query("
    SELECT id_guru, id_kelas, COUNT(id_jam) as jtm
    FROM tbl_jadwal
    GROUP BY id_guru, id_kelas
");
$rekap_jadwal = [];
foreach ($jadwal_stmt->fetchAll() as $row) {
    $id = $row['id_guru'];
    if (!isset($rekap_jadwal[$id])) {
        $rekap_jadwal[$id] = [
            'total_jtm' => 0,
            'per_kelas' => []
        ];
    }
    $rekap_jadwal[$id]['per_kelas'][$row['id_kelas']] = $row['jtm'];
    $rekap_jadwal[$id]['total_jtm'] += $row['jtm'];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap JTM & Hari Mengajar Guru</title>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4 portrait; margin: 15mm; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 13px;
            background: #e2e8f0;
            padding: 20px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .sheet {
            background: #fff;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 15mm;
            box-shadow: 0 8px 30px rgba(0,0,0,.15);
            position: relative;
            overflow: hidden;
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%; left: 50%;
            width: 200%; height: 200%;
            transform: translate(-50%, -50%) rotate(-45deg);
            display: flex; flex-wrap: wrap;
            gap: 20px 40px;
            justify-content: center; align-content: center;
            pointer-events: none;
            z-index: 0;
            opacity: .04;
        }
        .watermark span {
            font-size: 14px; font-weight: 900;
            text-transform: uppercase; white-space: nowrap;
            color: #000;
        }

        .content { position: relative; z-index: 1; }

        /* Kop Surat */
        .kop { display: flex; align-items: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop-logo { width: 80px; text-align: center; flex-shrink: 0; }
        .kop-logo img { width: 70px; height: 70px; object-fit: contain; }
        .kop-text { flex: 1; text-align: center; padding: 0 15px; }
        .kop-text .instansi { font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 2px; }
        .kop-text .yayasan  { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .kop-text .sekolah  { font-size: 24px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; margin: 3px 0; }
        .kop-text .alamat   { font-size: 11px; margin-top: 3px; }
        .kop-text .kontak   { font-size: 11px; font-style: italic; }

        .judul { text-align: center; margin-bottom: 15px; }
        .judul h2 { font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; text-decoration: underline; }
        .judul p  { font-size: 13px; font-weight: normal; margin-top: 4px; }

        /* Tabel Rekap */
        table.rekap {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 20px;
            table-layout: fixed;
        }
        table.rekap th, table.rekap td {
            border: 1px solid #000;
            padding: 4px 2px;
            vertical-align: middle;
            word-wrap: break-word;
            overflow: hidden;
        }
        table.rekap th {
            background: #d9d9d9;
            font-weight: bold;
            font-size: 10px;
            text-align: center;
        }
        table.rekap td {
            font-size: 10px;
        }
        table.rekap td.center { text-align: center; }

        /* Badge Hari */
        .badge-hari {
            display: inline-block;
            background: #e2e8f0;
            border: 1px solid #94a3b8;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 10px;
            margin: 2px;
            font-weight: bold;
        }
        
        /* TTD */
        .ttd { display: flex; justify-content: flex-end; margin-top: 40px; padding: 0 20px; font-size: 12px; }
        .ttd-col { text-align: center; width: 250px; }
        .ttd-col .nama { font-weight: bold; text-decoration: underline; text-transform: uppercase; margin-top: 70px; }
        .ttd-col .nip  { margin-top: 2px; }

        /* Tombol */
        .btn-bar { position: fixed; top: 20px; right: 20px; z-index: 999; display: flex; gap: 8px; }
        .btn-bar button {
            font-family: sans-serif; font-weight: bold; font-size: 14px;
            padding: 10px 20px; border: none; border-radius: 10px;
            cursor: pointer; color: #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,.2);
        }
        .btn-cetak { background: #10b981; }
        .btn-cetak:hover { background: #059669; }
        .btn-tutup { background: #475569; }
        .btn-tutup:hover { background: #334155; }

        @media print {
            body { background: none; padding: 0; }
            .sheet { margin: 0; box-shadow: none; padding: 0; width: 100%; min-height: auto; }
            .btn-bar { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="btn-bar">
    <button class="btn-cetak" onclick="window.print()">🖨️ Cetak / Save PDF</button>
    <button class="btn-tutup" onclick="window.close()">✖ Tutup</button>
</div>

<div class="sheet">
    <div class="watermark">
        <?php for ($i = 0; $i < 400; $i++): ?>
            <span><?= clean($setting['nama_sekolah']) ?></span>
        <?php endfor; ?>
    </div>

    <div class="content">

        <!-- Kop Surat -->
        <div class="kop">
            <div class="kop-logo">
                <?php if (!empty($setting['logo_kiri'])): ?>
                    <img src="../../gambar/<?= $setting['logo_kiri'] ?>" alt="Logo">
                <?php endif; ?>
            </div>
            <div class="kop-text">
                <?php if (!empty($setting['instansi_atas'])): ?>
                    <div class="instansi"><?= clean($setting['instansi_atas']) ?></div>
                <?php endif; ?>
                <?php if (!empty($setting['nama_yayasan'])): ?>
                    <div class="yayasan"><?= clean($setting['nama_yayasan']) ?></div>
                <?php endif; ?>
                <div class="sekolah"><?= clean($setting['nama_sekolah'] ?? 'SEKOLAH') ?></div>
                <div class="alamat"><?= clean($setting['alamat'] ?? '-') ?></div>
                <div class="kontak">Telp: <?= clean($setting['telepon'] ?? '-') ?> | Email: <?= clean($setting['email'] ?? '-') ?></div>
            </div>
            <div class="kop-logo">
                <?php if (!empty($setting['logo_kanan'])): ?>
                    <img src="../../gambar/<?= $setting['logo_kanan'] ?>" alt="Logo">
                <?php endif; ?>
            </div>
        </div>

<?php
// Catch params
$semester_val = $_GET['semester'] ?? '1';
$semester_str = ($semester_val == '1') ? '1 (Ganjil)' : '2 (Genap)';
$kurikulum_str = $_GET['kurikulum'] ?? 'Kurikulum Merdeka';
?>
        <!-- Judul -->
        <div class="judul">
            <h2>REKAPITULASI BEBAN MENGAJAR GURU</h2>
            <p style="margin-top:4px; font-weight:bold; font-size:11px; text-transform:uppercase;">
                <?= clean($kurikulum_str) ?> &mdash; SEMESTER : <?= $semester_str ?>
            </p>
            <p>Tahun Pelajaran <?= clean($ta_aktif) ?></p>
        </div>

        <!-- Tabel Rekap Matrix -->
        <table class="rekap">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 4%;">NO</th>
                    <th rowspan="2" style="width: 26%;">NAMA GURU</th>
                    <th rowspan="2" style="width: 15%;">JABATAN</th>
                    <th colspan="<?= count($semua_kelas) ?>">KELAS</th>
                    <th rowspan="2" style="width: 10%;">TOTAL<br>JTM</th>
                </tr>
                <tr>
                    <?php foreach ($semua_kelas as $k): ?>
                        <th style="font-size: 8px; width: <?= 45 / max(1, count($semua_kelas)) ?>%;"><?= clean($k['nama_kelas']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $total_jtm_sekolah = 0;
                $total_per_kelas = []; // Untuk footer
                foreach ($semua_kelas as $k) {
                    $total_per_kelas[$k['id_kelas']] = 0;
                }

                foreach ($guru_list as $g): 
                    $rekap = $rekap_jadwal[$g['id_guru']] ?? ['total_jtm' => 0, 'per_kelas' => []];
                    $total_jtm_sekolah += $rekap['total_jtm'];
                ?>
                <tr>
                    <td class="center"><?= $no++ ?></td>
                    <td>
                        <strong><?= clean($g['nama']) ?></strong>
                    </td>
                    <td style="font-size:10px;"><?= clean($g['tugas_tambahan'] ?: 'Guru Mata Pelajaran') ?></td>
                    
                    <?php foreach ($semua_kelas as $k): 
                        $jtm_kelas = $rekap['per_kelas'][$k['id_kelas']] ?? 0;
                        $total_per_kelas[$k['id_kelas']] += $jtm_kelas;
                    ?>
                        <td class="center" style="<?= $jtm_kelas > 0 ? 'font-weight:bold;' : '' ?>">
                            <?= $jtm_kelas > 0 ? $jtm_kelas : '' ?>
                        </td>
                    <?php endforeach; ?>

                    <td class="center" style="font-size:12px;"><?= $rekap['total_jtm'] > 0 ? $rekap['total_jtm'] : '' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f1f5f9; font-weight:bold;">
                    <td colspan="3" style="text-align:right; padding-right:10px;">TOTAL JTM SEKOLAH</td>
                    <?php foreach ($semua_kelas as $k): ?>
                        <td class="center"><?= $total_per_kelas[$k['id_kelas']] > 0 ? $total_per_kelas[$k['id_kelas']] : '' ?></td>
                    <?php endforeach; ?>
                    <td class="center" style="font-size:13px;"><?= $total_jtm_sekolah ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Tanda Tangan -->
        <div class="ttd">
            <div class="ttd-col">
                <div><?= clean($setting['kota'] ?? 'Kota') ?>, <?= tgl_indo(date('Y-m-d')) ?></div>
                <div>Kepala Sekolah</div>
                <div class="nama"><?= clean($setting['kepsek'] ?? '........................................') ?></div>
                <div class="nip">NIP. <?= clean($setting['nip_kepsek'] ?? '-') ?></div>
            </div>
        </div>

    </div>
</div>

</body>
</html>
