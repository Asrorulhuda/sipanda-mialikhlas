<?php
// Cetak Kwitansi Pembayaran
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/cetak_helper.php';
cek_role(['admin','bendahara', 'siswa']); // Allow students to print their own

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_bebas = isset($_GET['id_bebas']) ? (int)$_GET['id_bebas'] : 0;
$id_siswa = isset($_GET['siswa']) ? (int)$_GET['siswa'] : 0;
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';

if (!$id && !$id_bebas && (!$id_siswa || !$tanggal)) { die('ID pembayaran tidak valid.'); }

// If student is logged in, ensure they only print their own kwitansi
if ($_SESSION['role'] === 'siswa') {
    $session_siswa = (int)$_SESSION['user_id'];
    if ($id_siswa && $id_siswa !== $session_siswa) die('Akses ditolak.');
}

$p = null;
$jenis_gabung = [];
$total_bayar = 0;
$cara_bayar = '';
$teller = '';
$tanggal_bayar = '';
$no_kwitansi = '';

if ($id_siswa && $tanggal) {
    // Mode Tagihan Gabungan Hari Ini
    $s_stmt = $pdo->prepare("SELECT s.*, k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE s.id_siswa=?");
    $s_stmt->execute([$id_siswa]);
    $p = $s_stmt->fetch();
    if(!$p) die('Data siswa tidak ditemukan.');
    
    // Fetch bulanan
    $b_stmt = $pdo->prepare("SELECT p.*, j.nama_jenis, j.tipe FROM tbl_pembayaran p LEFT JOIN tbl_jenis_bayar j ON p.id_jenis=j.id_jenis WHERE p.id_siswa=? AND DATE(p.tanggal_bayar)=?");
    $b_stmt->execute([$id_siswa, $tanggal]);
    while($row = $b_stmt->fetch()) {
        $jenis_gabung[] = $row['nama_jenis'] . ($row['bulan'] ? ' - '.bulan_indo($row['bulan']) : '');
        $total_bayar += $row['jumlah_bayar'];
        $cara_bayar = $row['cara_bayar'];
        $teller = $row['teller'];
        $tanggal_bayar = $row['tanggal_bayar'];
    }
    
    // Fetch bebas
    $bb_stmt = $pdo->prepare("SELECT p.*, j.nama_jenis, j.tipe FROM tbl_pembayaran_bebas p LEFT JOIN tbl_jenis_bayar j ON p.id_jenis=j.id_jenis WHERE p.id_siswa=? AND DATE(p.tanggal_bayar)=?");
    $bb_stmt->execute([$id_siswa, $tanggal]);
    while($row = $bb_stmt->fetch()) {
        $jenis_gabung[] = $row['nama_jenis'] . ' (Cicilan)';
        $total_bayar += $row['jumlah_bayar'];
        $cara_bayar = $row['cara_bayar'];
        $teller = $row['teller'];
        $tanggal_bayar = $row['tanggal_bayar'];
    }
    
    if($total_bayar == 0) die('Tidak ada pembayaran di tanggal ini.');
    $jenis_str = implode(', ', $jenis_gabung);
    $no_kwitansi = 'KW-HR-' . date('Ymd', strtotime($tanggal)) . str_pad($id_siswa, 4, '0', STR_PAD_LEFT);

} else {
    // Mode Tunggal
    if ($id) {
        $stmt = $pdo->prepare("SELECT p.*, s.nama, s.nisn, s.nis, k.nama_kelas, j.nama_jenis, j.tipe 
            FROM tbl_pembayaran p JOIN tbl_siswa s ON p.id_siswa=s.id_siswa LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas 
            LEFT JOIN tbl_jenis_bayar j ON p.id_jenis=j.id_jenis WHERE p.id_pembayaran=?");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("SELECT p.*, p.id_bebas as id_pembayaran, s.nama, s.nisn, s.nis, k.nama_kelas, j.nama_jenis, j.tipe 
            FROM tbl_pembayaran_bebas p JOIN tbl_siswa s ON p.id_siswa=s.id_siswa LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas 
            LEFT JOIN tbl_jenis_bayar j ON p.id_jenis=j.id_jenis WHERE p.id_bebas=?");
        $stmt->execute([$id_bebas]);
    }
    
    $p = $stmt->fetch();
    if (!$p) { die('Data pembayaran tidak ditemukan.'); }

    // Double check specific student mapping if required
    if ($_SESSION['role'] === 'siswa' && (int)$p['id_siswa'] !== (int)$_SESSION['user_id']) die('Akses ditolak.');
    
    $jenis_str = htmlspecialchars($p['nama_jenis'] ?? 'Tagihan') . ((isset($p['tipe']) && $p['tipe']=='Bulanan' && !empty($p['bulan'])) ? ' - '.bulan_indo($p['bulan']) : ' (Cicilan)');
    $total_bayar = $p['jumlah_bayar'];
    $cara_bayar = $p['cara_bayar'];
    $teller = $p['teller'] ?? 'Bendahara';
    $tanggal_bayar = $p['tanggal_bayar'];
    $no_kwitansi = 'KW-' . str_pad($p['id_pembayaran'] ?? $p['id_bebas'], 6, '0', STR_PAD_LEFT);
}

$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi <?= $no_kwitansi ?></title>
    <!-- Tailwind for Layout -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 13px; color: #1a1a1a; background: white; }
        .toolbar { padding: 15px; text-align: center; }
        .btn-print { background: #2563eb; color: white; border: none; padding: 10px 24px; border-radius: 6px; cursor: pointer; font-size: 13px; margin-right: 8px; }
        .btn-back { background: #64748b; color: white; border: none; padding: 10px 24px; border-radius: 6px; cursor: pointer; font-size: 13px; text-decoration: none; }
        @media print { 
            .no-print { display: none !important; } 
            body, .print-container { height: 99vh; width: 100%; }
        }
        @media screen {
            body { background: #f5f5f5; display: flex; flex-direction: column; align-items: center; padding: 15px; height: 100%; }
        }
        .print-container { width: 100%; max-width: 790px; height: 95vh; margin: 0 auto; display: flex; flex-direction: column; justify-content: space-between; }
        .kwitansi { position: relative; border: 2px solid #333; padding: 20px 30px; width: 100%; flex: 1; background: white; border-radius: 6px; display: flex; flex-direction: column; overflow: hidden; }
        .kwitansi-header img { max-width: 65px !important; max-height: 65px !important; width: auto !important; height: auto !important; }
        .copy-label { position: absolute; top: 12px; right: 15px; font-size: 10px; font-weight: bold; border: 1px solid #777; padding: 4px 10px; border-radius: 4px; color: #444; background: #fff; text-transform: uppercase; letter-spacing: 1px; }
        .cut-line { text-align: center; color: #999; font-size: 11px; letter-spacing: 3px; margin: 8px 0; }
        .kwitansi-header { text-align: center; border-bottom: 3px double #333; padding-bottom: 6px; margin-bottom: 10px; flex-shrink: 0; }
        .kwitansi-header h1 { font-size: 18px; text-transform: uppercase; letter-spacing: 2px; }
        .kwitansi-header h2 { font-size: 14px; color: #555; margin-top: 4px; }
        .kwitansi-header .no { font-size: 11px; color: #888; margin-top: 2px; font-weight: bold; }
        .kwitansi-body { margin: 10px 0; flex: 1; }
        .kw-row { display: flex; margin: 5px 0; font-size: 12px; align-items: center; }
        .kw-label { width: 160px; font-weight: 600; color: #444; }
        .kw-sep { width: 20px; text-align: center; }
        .kw-val { flex: 1; font-weight: 500; }
        .badge-jenis { display: inline-block; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; margin-right: 5px; margin-bottom: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .kw-amount { text-align: center; margin: 8px 0; padding: 10px; background: #f0f7f0; border: 1px solid #c3e6cb; border-radius: 6px; }
        .kw-amount .label { font-size: 9px; text-transform: uppercase; color: #666; letter-spacing: 1px; }
        .kw-amount .value { font-size: 20px; font-weight: bold; color: #1a7a3a; margin-top: 2px; }
        .kw-terbilang { font-style: italic; font-size: 10px; color: #555; text-align: center; margin: 4px 0; padding: 4px; background: #fafafa; border-radius: 4px; }
        .kw-footer { display: flex; justify-content: space-between; margin-top: 10px; font-size: 11px; flex-shrink: 0; }
        .kw-footer .sign { text-align: center; width: 160px; }
        .kw-footer .sign .line { border-bottom: 1px solid #333; margin-top: 35px; margin-bottom: 4px; }
        .kw-stamp { font-size: 9px; color: #999; text-align: center; margin-top: 8px; border-top: 1px dashed #ccc; padding-top: 5px; flex-shrink: 0; }
    </style>
</head>
<body>
<div class="toolbar no-print">
    <button class="btn-print" onclick="window.print()">🖨️ Cetak Kwitansi</button>
    <a href="javascript:history.back()" class="btn-back">← Kembali</a>
</div>

<div class="print-container">
<?php
function render_kwitansi($label, $p, $setting, $no_kwitansi, $jenis_str, $cara_bayar, $teller, $tanggal_bayar, $total_bayar) {
?>
<div class="kwitansi">
    <div class="copy-label"><?= $label ?></div>
    <div class="kwitansi-header flex items-center justify-between border-b-[3px] border-double border-black pb-2 mb-4 font-serif">
        <div class="w-16 h-16 flex-shrink-0">
            <?php if(!empty($setting['logo_kiri'])): ?>
                <img src="../gambar/<?= $setting['logo_kiri'] ?>" class="w-full h-full object-contain">
            <?php else: ?>
                <div class="w-full h-full rounded-full border border-black flex items-center justify-center font-bold text-xl">S</div>
            <?php endif; ?>
        </div>
        <div class="flex-1 text-center px-4">
            <?php if(!empty($setting['instansi_atas'])): ?>
                <h4 class="text-[9px] font-bold uppercase tracking-tighter leading-none mb-0.5"><?= clean($setting['instansi_atas'] ?? '') ?></h4>
            <?php endif; ?>
            <?php if(!empty($setting['nama_yayasan'])): ?>
                <h3 class="text-[10px] font-bold uppercase tracking-tight leading-none mb-0.5"><?= clean($setting['nama_yayasan'] ?? '') ?></h3>
            <?php endif; ?>
            <h1 class="text-sm font-extrabold uppercase leading-tight"><?= htmlspecialchars($setting['nama_sekolah'] ?? 'SIPANDA SCHOOL') ?></h1>
            <h2 class="text-[11px] font-semibold tracking-[4px] mt-0.5 text-slate-700">KWITANSI PEMBAYARAN</h2>
            <div class="no text-[9px] mt-0.5 font-mono text-slate-500">No: <?= $no_kwitansi ?></div>
        </div>
        <div class="w-16 h-16 flex-shrink-0">
            <?php if(!empty($setting['logo_kanan'])): ?>
                <img src="../gambar/<?= $setting['logo_kanan'] ?>" class="w-full h-full object-contain">
            <?php endif; ?>
        </div>
    </div>

    <div class="kwitansi-body">
        <div class="kw-row"><span class="kw-label">Telah Terima Dari</span><span class="kw-sep">:</span><span class="kw-val"><?= htmlspecialchars($p['nama'] ?? 'Tanpa Nama') ?></span></div>
        <div class="kw-row"><span class="kw-label">NISN / NIS</span><span class="kw-sep">:</span><span class="kw-val"><?= htmlspecialchars($p['nisn'] ?? '-') ?> / <?= htmlspecialchars($p['nis'] ?? '-') ?></span></div>
        <div class="kw-row"><span class="kw-label">Kelas</span><span class="kw-sep">:</span><span class="kw-val"><?= htmlspecialchars($p['nama_kelas'] ?? '-') ?></span></div>
        <div class="kw-row" style="align-items: start;"><span class="kw-label" style="margin-top: 5px;">Rincian Pembayaran</span><span class="kw-sep" style="margin-top: 5px;">:</span><span class="kw-val">
            <div style="display: flex; flex-wrap: wrap;">
            <?php 
            $badges = explode(', ', $jenis_str);
            foreach($badges as $bdg): ?>
                <span class="badge-jenis"><?= htmlspecialchars(trim($bdg)) ?></span>
            <?php endforeach; ?>
            </div>
        </span></div>
        <div class="kw-row"><span class="kw-label">Cara Bayar</span><span class="kw-sep">:</span><span class="kw-val"><?= clean($cara_bayar) ?></span></div>
        <div class="kw-row"><span class="kw-label">Tanggal Bayar</span><span class="kw-sep">:</span><span class="kw-val"><?= tgl_indo($tanggal_bayar) ?></span></div>

        <div class="kw-amount">
            <div class="label">Total Pembayaran</div>
            <div class="value"><?= rupiah($total_bayar) ?></div>
        </div>

        <div class="kw-terbilang">Terbilang: <strong><?= terbilang($total_bayar) ?> rupiah</strong></div>
    </div>

    <div class="kw-footer font-serif">
        <div class="sign">
            <p>Penyetor,</p>
            <div class="line"></div>
            <p><?= htmlspecialchars($p['nama'] ?? '') ?></p>
        </div>
        <div class="sign">
            <p><?= htmlspecialchars($setting['kota'] ?? 'Kabupaten/Kota') ?>, <?= tgl_indo(date('Y-m-d')) ?></p>
            <p>Kepala Tata Usaha,</p>
            <div class="line"></div>
            <p><?= htmlspecialchars($setting['nama_tu'] ?? $teller) ?></p>
            <?php if(!empty($setting['wa_tu'])): ?><p style="font-size: 8px; color: #777;">WA: <?= clean($setting['wa_tu']) ?></p><?php endif; ?>
        </div>
    </div>

    <div class="kw-stamp">
        Dicetak oleh <?= APP_NAME ?> <?= APP_VERSION ?> pada <?= date('d/m/Y H:i:s') ?>
    </div>
</div>
<?php 
} // end function

// Cetak Lembar Wali Murid
render_kwitansi("Lembar untuk Wali Murid", $p, $setting, $no_kwitansi, $jenis_str, $cara_bayar, $teller, $tanggal_bayar, $total_bayar);
?>

<div class="cut-line no-print">
    ✂️ - - - - - - - - - - - - - - - - - - - - - Potong di sini - - - - - - - - - - - - - - - - - - - - - ✂️
</div>
<div class="cut-line" style="display: none;" media="print">
    - - - - - - - - - - - - - - - - - - - - - - - - Potong di sini - - - - - - - - - - - - - - - - - - - - - - - - 
</div>

<?php
// Cetak Lembar Arsip
render_kwitansi("Lembar Arsip Sekolah", $p, $setting, $no_kwitansi, $jenis_str, $cara_bayar, $teller, $tanggal_bayar, $total_bayar);
?>
</div>
</body>
</html>
