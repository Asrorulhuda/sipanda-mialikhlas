<?php
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
cek_fitur('ai_rpp');

if (!isset($_POST['simpan'])) {
    header('Location: rpp_tambah.php');
    exit;
}

$id_guru = $_SESSION['user_id'];

// ─── 1. WHITELIST & SANITASI ──────────────────────────────────────────────────
$allowed_types = ['Kurmer', 'KBC'];
$allowed_semesters = ['Ganjil', 'Genap'];

$type = $_POST['kurikulum_type'] ?? '';
if (!in_array($type, $allowed_types, true)) {
    flash('msg', 'Tipe kurikulum tidak valid!', 'error');
    header('Location: rpp_tambah.php');
    exit;
}

$semester = trim($_POST['semester'] ?? '');
if (!in_array($semester, $allowed_semesters, true)) {
    flash('msg', 'Nilai semester tidak valid!', 'error');
    header('Location: rpp_tambah.php');
    exit;
}

$id_mapel = (int) ($_POST['id_mapel'] ?? 0);
$id_kelas = (int) ($_POST['id_kelas'] ?? 0);
$judul = trim($_POST['judul_rpp'] ?? '');
$ajp = (int) ($_POST['alokasi_jp'] ?? 0);
$amp = (int) ($_POST['menit_per_jp'] ?? 0);
$total_m = $ajp * $amp;
$alokasi = $ajp > 0 ? "{$ajp} JP × {$amp} Menit (Total {$total_m} Menit)" : '-';
$tujuan = trim($_POST['tujuan'] ?? '');
$cp = trim($_POST['cp'] ?? '');

$praktik = trim($_POST['praktik_pedagogis'] ?? '-');
$dpl = trim($_POST['dimensi_dpl'] ?? '-');
$model_pembelajaran = trim($_POST['model_pembelajaran'] ?? '-');

// ─── 2. VALIDASI SERVER-SIDE ─────────────────────────────────────────────────
$errors = [];
if ($id_mapel <= 0) $errors[] = 'Mata pelajaran harus dipilih.';
if ($id_kelas <= 0) $errors[] = 'Kelas harus dipilih.';
if ($judul === '') $errors[] = 'Judul RPP tidak boleh kosong.';
if ($tujuan === '') $errors[] = 'Tujuan pembelajaran tidak boleh kosong (generate AI terlebih dahulu).';

if (!empty($errors)) {
    flash('msg', implode(' ', $errors), 'error');
    header('Location: rpp_tambah.php');
    exit;
}

// ─── 3. VERIFIKASI DATA (IDOR prevention) ────────────────────────────────────
$sch = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();

$guru_stmt = $pdo->prepare("SELECT * FROM tbl_guru WHERE id_guru=?");
$guru_stmt->execute([$id_guru]);
$guru = $guru_stmt->fetch();
if (!$guru) {
    flash('msg', 'Data guru tidak ditemukan!', 'error');
    header('Location: rpp_tambah.php');
    exit;
}

$mapel_stmt = $pdo->prepare("
    SELECT m.nama_mapel FROM tbl_mapel m
    INNER JOIN tbl_jadwal j ON j.id_mapel = m.id_mapel
    WHERE m.id_mapel = ? AND j.id_guru = ? LIMIT 1
");
$mapel_stmt->execute([$id_mapel, $id_guru]);
$nama_mapel = $mapel_stmt->fetchColumn();
if (!$nama_mapel) {
    flash('msg', 'Mata pelajaran tidak valid.', 'error');
    header('Location: rpp_tambah.php');
    exit;
}

$kelas_stmt = $pdo->prepare("
    SELECT k.nama_kelas FROM tbl_kelas k
    INNER JOIN tbl_jadwal j ON j.id_kelas = k.id_kelas
    WHERE k.id_kelas = ? AND j.id_guru = ? LIMIT 1
");
$kelas_stmt->execute([$id_kelas, $id_guru]);
$nama_kelas = $kelas_stmt->fetchColumn();
if (!$nama_kelas) {
    flash('msg', 'Kelas tidak valid.', 'error');
    header('Location: rpp_tambah.php');
    exit;
}

// Deteksi fase dari nama kelas
$fase_map = [1 => 'A', 2 => 'A', 3 => 'B', 4 => 'B', 5 => 'C', 6 => 'C', 7 => 'D', 8 => 'D', 9 => 'D', 10 => 'E', 11 => 'F', 12 => 'F'];
$fase = '-';
preg_match('/\d+/', $nama_kelas, $m_num);
$k_num = isset($m_num[0]) ? (int) $m_num[0] : 0;
if (isset($fase_map[$k_num])) $fase = $fase_map[$k_num];

// ─── 4. GENERATE NAMA FILE ──────────────────────────────────────────────────
$clean_judul = substr(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $judul)), 0, 50);
if ($clean_judul === '') $clean_judul = 'rpp';
$filename = 'RPP_' . $type . '_' . $clean_judul . '_' . time() . '.html';

$save_dir = __DIR__ . '/../assets/rpp_history';
if (!is_dir($save_dir)) mkdir($save_dir, 0755, true);
$save_dir = realpath($save_dir);
$filepath = $save_dir . DIRECTORY_SEPARATOR . $filename;

// ─── 5. VARIABEL TEMPLATE ────────────────────────────────────────────────────
$tgl_kota = clean($sch['kota'] ?? 'Bekasi');
$tgl_ttd = $tgl_kota . ', ' . tgl_indo(date('Y-m-d'));
$nama_kepsek = clean($sch['kepsek'] ?? '..............................');
$nip_kepsek = clean($sch['nip_kepsek'] ?? '-');
$nama_guru_display = clean($guru['nama'] ?? '..............................');
$nip_guru = clean($guru['nip'] ?? '-');
$mapel_bersih = clean($nama_mapel);
$kelas_bersih = clean($nama_kelas);
$jenis_label = $type === 'KBC'
    ? 'Kurikulum Berbasis Cinta (KBC) &mdash; Deep Learning (PEDATTI)'
    : 'Kurikulum Merdeka (Kurmer)';

// Ambil tahun pelajaran dari setting jika ada, fallback ke kalkulasi
$tahun_pelajaran = !empty($sch['tahun_pelajaran'])
    ? clean($sch['tahun_pelajaran'])
    : date('Y') . '/' . (date('Y') + 1);

// ─── 6. GENERATE HTML ────────────────────────────────────────────────────────
ob_start();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPP <?= clean($type) ?> — <?= clean($judul) ?></title>
    <style>
        @page { size: A4; margin: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 10.5pt;
            color: #111;
            line-height: 1.65;
            background: #fff;
            padding: 24px;
        }

        /* ── Kop Surat ── */
        .kop {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            border-bottom: 4px double #111;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .kop-logo { width: 80px; height: 80px; object-fit: contain; }
        .kop-text { flex: 1; text-align: center; }
        .kop-text .instansi-atas { font-size: 10pt; font-weight: 700; text-transform: uppercase; }
        .kop-text .nama-sekolah { font-size: 17pt; font-weight: 800; text-transform: uppercase; line-height: 1.1; }
        .kop-text .yayasan { font-size: 9.5pt; font-weight: 600; margin-top: 2px; }
        .kop-text .info-sekolah { font-size: 8.5pt; color: #444; margin-top: 4px; }

        /* ── Judul Dokumen ── */
        .doc-title-wrap { text-align: center; margin-bottom: 20px; }
        .doc-title { font-size: 13pt; font-weight: 800; text-transform: uppercase; border-bottom: 2.5px solid #111; display: inline-block; padding: 0 36px 5px; }
        .doc-subtitle { font-size: 9.5pt; font-weight: 600; color: #444; text-transform: uppercase; letter-spacing: .8px; margin-top: 4px; }

        /* ── Tabel Identitas ── */
        .tbl-id { width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1.5px solid #111; }
        .tbl-id td { padding: 5px 10px; font-size: 10pt; border: 1px solid #555; }
        .tbl-id td.lbl { width: 150px; font-weight: 700; background: #f4f5f7; }
        .tbl-id td.sep { width: 12px; text-align: center; font-weight: 700; }

        /* ── Section Header ── */
        .sec-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10.5pt;
            font-weight: 700;
            text-transform: uppercase;
            background: #f0f2f5;
            padding: 7px 14px;
            margin: 22px 0 10px;
            border: 1px solid #cdd4df;
            border-left: 6px solid #111;
        }
        .sec-head .waktu-badge {
            font-size: 8.5pt;
            background: #fff;
            border: 1px solid #cdd4df;
            padding: 2px 10px;
            border-radius: 4px;
            font-weight: 700;
            color: #333;
        }

        /* ── Content Box ── */
        .content-box { padding: 4px 12px; margin-bottom: 12px; text-align: justify; font-size: 10.5pt; }
        .content-box p { margin-bottom: 6px; }

        /* ── Tabel Utama ── */
        .tbl-main { width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 10pt; }
        .tbl-main th, .tbl-main td { border: 1.5px solid #111; padding: 10px 12px; vertical-align: top; }
        .tbl-main th { background: #eef0f3; font-weight: 700; text-align: center; text-transform: uppercase; font-size: 9.5pt; }
        .tbl-main td.td-kegiatan { text-align: center; font-weight: 700; width: 150px; vertical-align: middle; }
        .tbl-main td.td-waktu { text-align: center; width: 90px; vertical-align: middle; }
        .sub-label { font-size: 8pt; font-weight: 400; display: block; color: #555; }

        /* ── Tabel Asesmen 3 kolom ── */
        .tbl-asesmen { width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 10pt; }
        .tbl-asesmen th, .tbl-asesmen td { border: 1.5px solid #111; padding: 10px 12px; vertical-align: top; width: 33.33%; }
        .tbl-asesmen th { background: #eef0f3; font-weight: 700; text-align: center; font-size: 9.5pt; text-transform: uppercase; }

        /* ── Tabel KBC (2 kolom) ── */
        .tbl-kbc { width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 10pt; }
        .tbl-kbc th, .tbl-kbc td { border: 1.5px solid #111; padding: 10px 12px; vertical-align: top; width: 50%; }
        .tbl-kbc th { background: #eef0f3; font-weight: 700; text-align: center; font-size: 9.5pt; text-transform: uppercase; }

        /* ── Tabel TTD ── */
        .tbl-ttd { width: 100%; border-collapse: collapse; margin-top: 45px; page-break-inside: avoid; }
        .tbl-ttd td { width: 33.33%; text-align: center; padding: 8px 10px; vertical-align: top; font-size: 10pt; }
        .ttd-space { height: 72px; }
        .ttd-nama { font-weight: 700; text-decoration: underline; text-transform: uppercase; }
        .ttd-nip { font-size: 9.5pt; color: #333; }

        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>

<body>

    <!-- KOP SURAT -->
    <div class="kop">
        <?php if (!empty($sch['logo_kiri'])): ?>
            <img src="<?= BASE_URL ?>gambar/<?= clean($sch['logo_kiri']) ?>" class="kop-logo" alt="Logo">
        <?php endif; ?>
        <div class="kop-text">
            <p class="instansi-atas"><?= clean($sch['instansi_atas'] ?? 'PEMERINTAH KABUPATEN') ?></p>
            <p class="nama-sekolah"><?= clean($sch['nama_sekolah'] ?? '—') ?></p>
            <?php if (!empty($sch['nama_yayasan'])): ?>
                <p class="yayasan"><?= clean($sch['nama_yayasan']) ?></p>
            <?php endif; ?>
            <p class="info-sekolah">
                <?= clean($sch['alamat'] ?? '') ?>
                <?php if (!empty($sch['npsn'])): ?>&nbsp;|&nbsp;NPSN: <?= clean($sch['npsn']) ?><?php endif; ?>
                <?php if (!empty($sch['telepon'])): ?>&nbsp;|&nbsp;Telp: <?= clean($sch['telepon']) ?><?php endif; ?>
                <?php if (!empty($sch['email'])): ?>&nbsp;|&nbsp;<?= clean($sch['email']) ?><?php endif; ?>
            </p>
        </div>
        <?php if (!empty($sch['logo_kanan'])): ?>
            <img src="<?= BASE_URL ?>gambar/<?= clean($sch['logo_kanan']) ?>" class="kop-logo" alt="Logo Kanan">
        <?php endif; ?>
    </div>

    <!-- JUDUL DOKUMEN -->
    <div class="doc-title-wrap">
        <h2 class="doc-title">RENCANA PELAKSANAAN PEMBELAJARAN (RPP)</h2>
        <p class="doc-subtitle"><?= $jenis_label ?></p>
    </div>

    <!-- TABEL IDENTITAS -->
    <table class="tbl-id">
        <tr>
            <td class="lbl">Mata Pelajaran</td>
            <td class="sep">:</td>
            <td style="font-weight:700"><?= $mapel_bersih ?></td>
            <td class="lbl">Kelas / Fase</td>
            <td class="sep">:</td>
            <td><?= $kelas_bersih ?><?= $fase !== '-' ? " / Fase {$fase}" : '' ?></td>
        </tr>
        <tr>
            <td class="lbl">Materi Pokok</td>
            <td class="sep">:</td>
            <td style="font-style:italic"><?= clean($judul) ?></td>
            <td class="lbl">Semester</td>
            <td class="sep">:</td>
            <td><?= clean($semester) ?></td>
        </tr>
        <tr>
            <td class="lbl">Alokasi Waktu</td>
            <td class="sep">:</td>
            <td colspan="4"><?= clean($alokasi) ?></td>
        </tr>
        <tr>
            <td class="lbl">Tahun Pelajaran</td>
            <td class="sep">:</td>
            <td colspan="4"><?= $tahun_pelajaran ?></td>
        </tr>
        <tr>
            <td class="lbl">Guru Pengampu</td>
            <td class="sep">:</td>
            <td colspan="4"><?= $nama_guru_display ?></td>
        </tr>
    </table>

    <!-- A. CAPAIAN PEMBELAJARAN -->
    <?php if ($cp): ?>
        <div class="sec-head">A. Capaian Pembelajaran (CP)</div>
        <div class="content-box"><?= nl2br(clean($cp)) ?></div>
    <?php endif; ?>

    <!-- B. TUJUAN PEMBELAJARAN -->
    <div class="sec-head">B. Tujuan Pembelajaran (TP)</div>
    <div class="content-box" style="border-left:4px solid #111; padding-left:18px; background:#fcfcfc;">
        <?= nl2br(clean($tujuan)) ?>
    </div>

    <!-- C. PENDEKATAN PEMBELAJARAN & DPL -->
    <div class="sec-head">C. Pendekatan Pembelajaran &amp; DPL</div>
    <div class="content-box" style="border-left:4px solid #111; padding-left:18px; background:#fcfcfc;">
        <strong>Model Pembelajaran:</strong><br>
        <?= nl2br(clean($praktik)) ?><br><br>
        
        <strong>Dimensi Profil Lulusan / DPL:</strong><br>
        <?= nl2br(clean($dpl)) ?>
    </div>

    <?php if ($type === 'KBC'): ?>

        <!-- D. IDENTIFIKASI KESIAPAN MURID -->
        <div class="sec-head">D. Identifikasi Kesiapan Murid</div>
        <div class="content-box" style="border-left:4px solid #111; padding-left:18px; background:#fcfcfc;">
            <?= nl2br(clean($_POST['kesiapan'] ?? '-')) ?>
        </div>

        <!-- E. PEDATTI -->
        <div class="sec-head">
            E. Pengalaman Belajar (PEDATTI)
            <span class="waktu-badge">Total Durasi: <?= clean($alokasi) ?></span>
        </div>
        <table class="tbl-main">
            <thead>
                <tr>
                    <th>Tahap</th>
                    <th>Deskripsi Kegiatan Pembelajaran</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="td-kegiatan">Pendahuluan</td>
                    <td><?= nl2br(clean($_POST['pendahuluan'] ?? '-')) ?></td>
                    <td class="td-waktu"><?= clean($_POST['waktu_pendahuluan'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="td-kegiatan"><strong>Dalami</strong><span class="sub-label">(Inti 1)</span></td>
                    <td><?= nl2br(clean($_POST['pedatti_dalami'] ?? '-')) ?></td>
                    <td class="td-waktu" rowspan="3" style="vertical-align:middle"><?= clean($_POST['waktu_inti'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="td-kegiatan"><strong>Terapkan</strong><span class="sub-label">(Inti 2)</span></td>
                    <td><?= nl2br(clean($_POST['pedatti_terapkan'] ?? '-')) ?></td>
                </tr>
                <tr>
                    <td class="td-kegiatan"><strong>Tularkan</strong><span class="sub-label">(Inti 3)</span></td>
                    <td><?= nl2br(clean($_POST['pedatti_tularkan'] ?? '-')) ?></td>
                </tr>
                <tr>
                    <td class="td-kegiatan">Penutup</td>
                    <td><?= nl2br(clean($_POST['penutup'] ?? '-')) ?></td>
                    <td class="td-waktu"><?= clean($_POST['waktu_penutup'] ?? '-') ?></td>
                </tr>
            </tbody>
        </table>

        <!-- F. ASESMEN -->
        <div class="sec-head">F. Asesmen Pembelajaran</div>
        <div class="content-box" style="border-left:4px solid #111; padding-left:18px; background:#fcfcfc;">
            <strong>1. Asesmen Awal (Diagnostik):</strong><br>
            <?= nl2br(clean($_POST['asesmen_awal'] ?? '-')) ?><br><br>
            
            <strong>2. Asesmen Proses (Formatif):</strong><br>
            <?= nl2br(clean($_POST['asesmen_proses'] ?? '-')) ?><br><br>
            
            <strong>3. Asesmen Akhir (Sumatif):</strong><br>
            <?= nl2br(clean($_POST['asesmen_akhir'] ?? '-')) ?>
        </div>

    <?php else: /* KURMER */ ?>

        <!-- D. LANGKAH PEMBELAJARAN -->
        <div class="sec-head">
            D. Langkah-Langkah Pembelajaran
            <span class="waktu-badge">Total Durasi: <?= clean($alokasi) ?></span>
        </div>
        <table class="tbl-main">
            <thead>
                <tr>
                    <th>Kegiatan</th>
                    <th>Deskripsi Aktivitas Pembelajaran</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="td-kegiatan">Pendahuluan</td>
                    <td><?= nl2br(clean($_POST['pendahuluan'] ?? '-')) ?></td>
                    <td class="td-waktu"><?= clean($_POST['waktu_pendahuluan'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="td-kegiatan">Kegiatan Inti</td>
                    <td><?= nl2br(clean($_POST['inti'] ?? '-')) ?></td>
                    <td class="td-waktu"><?= clean($_POST['waktu_inti'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="td-kegiatan">Penutup</td>
                    <td><?= nl2br(clean($_POST['penutup'] ?? '-')) ?></td>
                    <td class="td-waktu"><?= clean($_POST['waktu_penutup'] ?? '-') ?></td>
                </tr>
            </tbody>
        </table>

        <!-- E. PENILAIAN -->
        <div class="sec-head">E. Penilaian (Asesmen)</div>
        <div class="content-box"><?= nl2br(clean($_POST['penilaian'] ?? '-')) ?></div>

    <?php endif; ?>

    <!-- TANDA TANGAN -->
    <table class="tbl-ttd">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Kepala <?= !empty($sch['nama_sekolah']) ? 'Sekolah' : 'Madrasah/Sekolah' ?></strong>
                <div class="ttd-space"></div>
                <p class="ttd-nama"><?= $nama_kepsek ?></p>
                <p class="ttd-nip">NIP. <?= $nip_kepsek ?></p>
            </td>
            <td></td>
            <td>
                <?= $tgl_ttd ?><br>
                <strong>Guru Pengampu,</strong>
                <div class="ttd-space"></div>
                <p class="ttd-nama"><?= $nama_guru_display ?></p>
                <p class="ttd-nip">NIP. <?= $nip_guru ?></p>
            </td>
        </tr>
    </table>

</body>

</html>
<?php
$html = ob_get_clean();

// ─── 7. SIMPAN FILE ──────────────────────────────────────────────────────────
$write = file_put_contents($filepath, $html);
if ($write === false) {
    flash('msg', 'Gagal menyimpan file RPP. Periksa permission folder assets/rpp_history.', 'error');
    header('Location: rpp_tambah.php');
    exit;
}

// ─── 8. SIMPAN DATABASE ──────────────────────────────────────────────────────
$ins = $pdo->prepare("
    INSERT INTO tbl_rpp (id_guru, id_mapel, id_kelas, kurikulum_type, judul_rpp, file_path)
    VALUES (?, ?, ?, ?, ?, ?)
");
$ins->execute([$id_guru, $id_mapel, $id_kelas, $type, $judul, $filename]);

flash('msg', 'RPP berhasil digenerate dan disimpan! 🎉', 'success');
header('Location: rpp.php');
exit;