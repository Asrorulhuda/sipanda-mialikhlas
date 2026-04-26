<?php
// ============================================================
// INIT & AUTH
// ============================================================
require_once __DIR__ . '/../../config/init.php';

cek_role(['admin', 'waka_kurikulum', 'guru']);
cek_fitur('akademik');

// ============================================================
// VALIDASI PARAMETER KELAS
// ============================================================
$id_kelas = (int) ($_GET['kelas'] ?? 0);

if (!$id_kelas) {
    die("Pilih kelas terlebih dahulu!");
}

$stmt_kelas = $pdo->prepare("SELECT * FROM tbl_kelas WHERE id_kelas = ?");
$stmt_kelas->execute([$id_kelas]);
$kelas = $stmt_kelas->fetch();

if (!$kelas) {
    die("Kelas tidak ditemukan!");
}

// ============================================================
// AMBIL DATA PENDUKUNG
// ============================================================
$setting  = $pdo->query("SELECT * FROM tbl_setting WHERE id = 1")->fetch();
$jam_list = $pdo->query("SELECT * FROM tbl_jam ORDER BY jam_mulai")->fetchAll();

$hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

// Ambil Tahun Ajaran Aktif
$ta_aktif = $pdo->query("SELECT tahun FROM tbl_tahun_ajaran WHERE status='aktif' LIMIT 1")->fetchColumn();
if (!$ta_aktif) $ta_aktif = date('Y') . '/' . (date('Y') + 1);

// Parameter dari jadwal.php
$semester   = $_GET['semester'] ?? '1';
$kurikulum  = $_GET['kurikulum'] ?? 'Kurikulum Merdeka';
$smt_text   = $semester == '1' ? '1 (SATU)' : '2 (DUA)';

// Ambil Wali Kelas dari tbl_guru.id_kelas_wali
$stmt_wali = $pdo->prepare("SELECT nama, nip FROM tbl_guru WHERE id_kelas_wali = ? AND status = 'Aktif' LIMIT 1");
$stmt_wali->execute([$id_kelas]);
$wali = $stmt_wali->fetch();

// ============================================================
// AMBIL & SUSUN DATA JADWAL
// ============================================================
$jadwal = [];

$stmt_jadwal = $pdo->prepare("
    SELECT
        j.id_jadwal, j.id_kelas, j.hari, j.id_jam, j.id_mapel, j.id_guru,
        m.nama_mapel, g.nama AS nama_guru,
        jm.nama_jam, jm.jam_mulai, jm.jam_selesai
    FROM tbl_jadwal j
    JOIN      tbl_mapel m  ON j.id_mapel = m.id_mapel
    LEFT JOIN tbl_guru  g  ON j.id_guru  = g.id_guru
    JOIN      tbl_jam   jm ON j.id_jam   = jm.id_jam
    WHERE j.id_kelas = ?
    GROUP BY j.hari, j.id_jam
    ORDER BY jm.jam_mulai
");
$stmt_jadwal->execute([$id_kelas]);

foreach ($stmt_jadwal->fetchAll() as $j) {
    $jadwal[$j['hari']][$j['id_jam']] = $j;
}

// ============================================================
// PALET WARNA PASTEL PER MAPEL (berdasarkan hash nama mapel)
// ============================================================
$pastel_colors = [
    '#dbeafe', // biru muda
    '#dcfce7', // hijau muda
    '#fef9c3', // kuning muda
    '#fce7f3', // pink muda
    '#e0e7ff', // indigo muda
    '#f3e8ff', // ungu muda
    '#ccfbf1', // teal muda
    '#ffedd5', // oranye muda
    '#fee2e2', // merah muda
    '#ecfeff', // cyan muda
    '#fef3c7', // amber muda
    '#ede9fe', // violet muda
];

function get_mapel_color($nama_mapel, $palette) {
    $hash = crc32($nama_mapel);
    $index = abs($hash) % count($palette);
    return $palette[$index];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Jadwal Pelajaran - <?= clean($kelas['nama_kelas']) ?></title>
    <style>
        /* ============================================
           RESET MURNI — tanpa framework CSS
           ============================================ */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        @page { size: A4 landscape; margin: 12mm; }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 13px;
            background: #e2e8f0;
            padding: 20px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* ── Sheet (kertas) ── */
        .sheet {
            background: #fff;
            width: 297mm;
            min-height: 209mm;
            margin: 0 auto;
            padding: 15mm 18mm;
            box-shadow: 0 8px 30px rgba(0,0,0,.15);
            position: relative;
            overflow: hidden;
        }

        /* ── Watermark — full A4 ── */
        .watermark {
            position: absolute;
            top: 50%; left: 50%;
            width: 200%; height: 200%;
            transform: translate(-50%, -50%) rotate(-25deg);
            display: flex; flex-wrap: wrap;
            gap: 18px 28px;
            justify-content: center; align-content: center;
            pointer-events: none;
            z-index: 0;
            opacity: .05;
        }
        .watermark span {
            font-size: 12px; font-weight: 900;
            text-transform: uppercase; white-space: nowrap;
            color: #000;
        }

        /* ── Content (z-index di atas watermark) ── */
        .content { position: relative; z-index: 1; }

        /* ── Kop Surat ── */
        .kop { display: flex; align-items: center; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 14px; }
        .kop-logo { width: 70px; text-align: center; flex-shrink: 0; }
        .kop-logo img { width: 60px; height: 60px; object-fit: contain; }
        .kop-text { flex: 1; text-align: center; padding: 0 10px; }
        .kop-text .instansi { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 1px; }
        .kop-text .yayasan  { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1px; }
        .kop-text .sekolah  { font-size: 20px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; margin: 2px 0; }
        .kop-text .alamat   { font-size: 9px; margin-top: 2px; }
        .kop-text .kontak   { font-size: 9px; font-style: italic; }

        /* ── Judul ── */
        .judul { text-align: center; margin-bottom: 14px; }
        .judul h2 { font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .judul p  { font-size: 13px; font-weight: bold; text-transform: uppercase; margin-top: 2px; }

        /* ── Kelas & Semester ── */
        .info-bar { display: flex; justify-content: space-between; font-weight: bold; font-size: 13px; text-transform: uppercase; margin-bottom: 6px; }

        /* ============================================
           TABEL — KUNCI UTAMA KOLOM RAPIH
           ============================================ */
        table.jadwal {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 2px solid #000;
        }

        table.jadwal th,
        table.jadwal td {
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
            padding: 5px 3px;
            overflow: hidden;
            word-wrap: break-word;
        }

        table.jadwal th {
            background: #d9d9d9;
            font-weight: bold;
            font-size: 12px;
        }

        table.jadwal td {
            font-size: 11px;
            height: 32px;
        }

        /* Istirahat row */
        .istirahat {
            background-color: #f9cb9c !important;
            font-weight: bold;
            font-style: italic;
            letter-spacing: 2px;
        }

        /* ── Tanda Tangan ── */
        .ttd { display: flex; justify-content: space-between; margin-top: 30px; padding: 0 30px; font-size: 13px; }
        .ttd-col { text-align: center; width: 250px; }
        .ttd-col .nama { font-weight: bold; text-decoration: underline; text-transform: uppercase; margin-top: 70px; }
        .ttd-col .nip  { margin-top: 2px; }

        /* ── Tombol cetak ── */
        .btn-bar { position: fixed; top: 20px; right: 20px; z-index: 999; display: flex; gap: 8px; }
        .btn-bar button {
            font-family: sans-serif; font-weight: bold; font-size: 14px;
            padding: 10px 20px; border: none; border-radius: 10px;
            cursor: pointer; color: #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,.2);
        }
        .btn-cetak { background: #2563eb; }
        .btn-cetak:hover { background: #1d4ed8; }
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

<!-- Tombol -->
<div class="btn-bar">
    <button class="btn-cetak" onclick="window.print()">🖨️ Cetak / Save PDF</button>
    <button class="btn-tutup" onclick="window.close()">✖ Tutup</button>
</div>

<div class="sheet">

    <!-- Watermark Full A4 -->
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

        <!-- Judul -->
        <div class="judul">
            <h2>Jadwal Pelajaran <?= clean($kurikulum) ?></h2>
            <p>Tahun Pelajaran <?= clean($ta_aktif) ?></p>
        </div>

        <!-- Kelas & Semester -->
        <div class="info-bar">
            <span>KELAS &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= clean($kelas['nama_kelas']) ?></span>
            <span>SEMESTER &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= clean($smt_text) ?></span>
        </div>

        <!-- ============================================
             TABEL JADWAL
             ============================================ -->
        <table class="jadwal">
            <colgroup>
                <col style="width: 30px;">
                <col style="width: 100px;">
                <?php for ($c = 0; $c < 6; $c++): ?>
                    <col>
                <?php endfor; ?>
            </colgroup>
            <thead>
                <tr>
                    <th>NO</th>
                    <th>HARI/WAKTU</th>
                    <?php foreach ($hari_list as $hari): ?>
                        <th><?= strtoupper($hari) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                foreach ($jam_list as $jam):
                    $is_istirahat = (stripos($jam['nama_jam'], 'istirahat') !== false);
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= str_replace(':', '.', substr($jam['jam_mulai'], 0, 5)) ?> - <?= str_replace(':', '.', substr($jam['jam_selesai'], 0, 5)) ?></td>

                    <?php if ($is_istirahat): ?>
                        <td colspan="6" class="istirahat">Istirahat</td>
                    <?php else: ?>
                        <?php foreach ($hari_list as $hari): ?>
                            <?php
                            $item = $jadwal[$hari][$jam['id_jam']] ?? null;
                            $bg   = '';
                            $text = '';

                            if ($item) {
                                $text = clean($item['nama_mapel']);
                                $bg = get_mapel_color($text, $pastel_colors);
                            }
                            ?>
                            <td style="<?= $bg ? "background-color: {$bg};" : '' ?>">
                                <?= $text ?>
                                <?php if ($item && !empty($item['nama_guru'])): ?>
                                    <br><span style="font-size:9px; font-style:italic; color:#555;"><?= clean($item['nama_guru']) ?></span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Tanda Tangan -->
        <div class="ttd">
            <div class="ttd-col">
                <div>Mengetahui</div>
                <div>Kepala Sekolah</div>
                <div class="nama"><?= clean($setting['kepsek'] ?? '........................................') ?></div>
                <div class="nip">NIP. <?= clean($setting['nip_kepsek'] ?? '-') ?></div>
            </div>
            <div class="ttd-col">
                <div><?= clean($setting['kota'] ?? 'Kota') ?>, <?= tgl_indo(date('Y-m-d')) ?></div>
                <div>Wali Kelas <?= clean($kelas['nama_kelas']) ?></div>
                <div class="nama"><?= clean($wali['nama'] ?? '........................................') ?></div>
                <div class="nip">NIP. <?= clean($wali['nip'] ?? '........................................') ?></div>
            </div>
        </div>

    </div><!-- /content -->
</div><!-- /sheet -->

</body>
</html>