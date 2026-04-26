<?php
// ============================================================
// CETAK JADWAL MENGAJAR GURU
// ============================================================
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
cek_fitur('akademik');

$id_guru = (int) $_SESSION['user_id'];

// Data guru
$stmt = $pdo->prepare("SELECT * FROM tbl_guru WHERE id_guru = ?");
$stmt->execute([$id_guru]);
$guru = $stmt->fetch();
if (!$guru) die("Data guru tidak ditemukan!");

// Setting sekolah
$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id = 1")->fetch();

// Tahun ajaran
$ta_aktif = $pdo->query("SELECT tahun FROM tbl_tahun_ajaran WHERE status='aktif' LIMIT 1")->fetchColumn();
if (!$ta_aktif) $ta_aktif = date('Y') . '/' . (date('Y') + 1);

// Jam & Hari
$jam_list  = $pdo->query("SELECT * FROM tbl_jam ORDER BY jam_mulai")->fetchAll();
$hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

// Ambil semua jadwal guru ini
$jadwal = [];
$stmt = $pdo->prepare("
    SELECT j.*, m.nama_mapel, k.nama_kelas, jm.nama_jam, jm.jam_mulai, jm.jam_selesai
    FROM tbl_jadwal j
    JOIN tbl_mapel m ON j.id_mapel = m.id_mapel
    JOIN tbl_kelas k ON j.id_kelas = k.id_kelas
    JOIN tbl_jam jm ON j.id_jam = jm.id_jam
    WHERE j.id_guru = ?
    ORDER BY jm.jam_mulai
");
$stmt->execute([$id_guru]);
foreach ($stmt->fetchAll() as $j) {
    $jadwal[$j['hari']][$j['id_jam']] = $j;
}

// Palet warna per kelas
$pastel_colors = [
    '#dbeafe', '#dcfce7', '#fef9c3', '#fce7f3', '#e0e7ff',
    '#f3e8ff', '#ccfbf1', '#ffedd5', '#fee2e2', '#ecfeff',
    '#fef3c7', '#ede9fe',
];
function get_kelas_color($nama, $palette) {
    return $palette[abs(crc32($nama)) % count($palette)];
}

// Hitung total jam
$total = 0;
foreach ($jadwal as $h => $jams) $total += count($jams);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Jadwal Mengajar - <?= clean($guru['nama']) ?></title>
    <style>
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

        /* Watermark */
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

        .content { position: relative; z-index: 1; }

        /* Kop Surat */
        .kop { display: flex; align-items: center; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 14px; }
        .kop-logo { width: 70px; text-align: center; flex-shrink: 0; }
        .kop-logo img { width: 60px; height: 60px; object-fit: contain; }
        .kop-text { flex: 1; text-align: center; padding: 0 10px; }
        .kop-text .instansi { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 1px; }
        .kop-text .yayasan  { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1px; }
        .kop-text .sekolah  { font-size: 20px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; margin: 2px 0; }
        .kop-text .alamat   { font-size: 9px; margin-top: 2px; }
        .kop-text .kontak   { font-size: 9px; font-style: italic; }

        .judul { text-align: center; margin-bottom: 10px; }
        .judul h2 { font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .judul p  { font-size: 13px; font-weight: bold; text-transform: uppercase; margin-top: 2px; }

        .info-bar { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 8px; }
        .info-bar .col { line-height: 1.6; }

        /* Tabel */
        table.jadwal {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 2px solid #000;
        }
        table.jadwal th, table.jadwal td {
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
            padding: 4px 3px;
            overflow: hidden;
            word-wrap: break-word;
        }
        table.jadwal th {
            background: #d9d9d9;
            font-weight: bold;
            font-size: 11px;
        }
        table.jadwal td {
            font-size: 10px;
            height: 30px;
        }
        .istirahat {
            background-color: #f9cb9c !important;
            font-weight: bold;
            font-style: italic;
            letter-spacing: 2px;
        }

        /* TTD */
        .ttd { display: flex; justify-content: space-between; margin-top: 25px; padding: 0 30px; font-size: 12px; }
        .ttd-col { text-align: center; width: 240px; }
        .ttd-col .nama { font-weight: bold; text-decoration: underline; text-transform: uppercase; margin-top: 60px; }
        .ttd-col .nip  { margin-top: 2px; }

        /* Tombol */
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
                    <img src="../gambar/<?= $setting['logo_kiri'] ?>" alt="Logo">
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
                    <img src="../gambar/<?= $setting['logo_kanan'] ?>" alt="Logo">
                <?php endif; ?>
            </div>
        </div>

        <!-- Judul -->
        <div class="judul">
            <h2>Jadwal Mengajar Guru</h2>
            <p>Tahun Pelajaran <?= clean($ta_aktif) ?></p>
        </div>

        <!-- Info Guru -->
        <div class="info-bar">
            <div class="col">
                <div><strong>Nama Guru</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= clean($guru['nama']) ?></div>
                <div><strong>NIP</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= clean($guru['nip'] ?? '-') ?></div>
            </div>
            <div class="col" style="text-align:right;">
                <div><strong>Total Jam/Minggu</strong> : <?= $total ?> Jam</div>
                <div><strong>Jabatan</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= clean($guru['tugas_tambahan'] ?? 'Guru Mata Pelajaran') ?></div>
            </div>
        </div>

        <!-- Tabel Jadwal -->
        <table class="jadwal">
            <colgroup>
                <col style="width: 30px;">
                <col style="width: 95px;">
                <?php for ($c = 0; $c < 6; $c++): ?>
                    <col>
                <?php endfor; ?>
            </colgroup>
            <thead>
                <tr>
                    <th>NO</th>
                    <th>WAKTU</th>
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
                    <td style="font-size:10px;"><?= str_replace(':', '.', substr($jam['jam_mulai'], 0, 5)) ?> - <?= str_replace(':', '.', substr($jam['jam_selesai'], 0, 5)) ?></td>

                    <?php if ($is_istirahat): ?>
                        <td colspan="6" class="istirahat">Istirahat</td>
                    <?php else: ?>
                        <?php foreach ($hari_list as $hari): ?>
                            <?php
                            $item = $jadwal[$hari][$jam['id_jam']] ?? null;
                            $bg   = '';
                            $text = '';
                            $kelas_text = '';

                            if ($item) {
                                $text = clean($item['nama_mapel']);
                                $kelas_text = clean($item['nama_kelas']);
                                $bg = get_kelas_color($kelas_text, $pastel_colors);
                            }
                            ?>
                            <td style="<?= $bg ? "background-color: {$bg};" : '' ?>">
                                <?php if ($item): ?>
                                    <div style="font-weight:bold; font-size:10px;"><?= $text ?></div>
                                    <div style="font-size:8px; font-style:italic; color:#555; margin-top:1px;"><?= $kelas_text ?></div>
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
                <div>Guru Yang Bersangkutan</div>
                <div class="nama"><?= clean($guru['nama']) ?></div>
                <div class="nip">NIP. <?= clean($guru['nip'] ?? '-') ?></div>
            </div>
        </div>

    </div>
</div>

</body>
</html>
