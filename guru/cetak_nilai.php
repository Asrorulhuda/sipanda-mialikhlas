<?php
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);

$id_guru = $_SESSION['user_id'];
$ta = get_ta_aktif($pdo);

$sel_kelas = (int)($_GET['kelas'] ?? 0); 
$sel_mapel = (int)($_GET['mapel'] ?? 0);

if (!$sel_kelas || !$sel_mapel || !$ta) {
    die("Data tidak lengkap.");
}

$stmt = $pdo->prepare("SELECT nama_kelas FROM tbl_kelas WHERE id_kelas=?"); $stmt->execute([$sel_kelas]); $nama_kelas = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT nama_mapel FROM tbl_mapel WHERE id_mapel=?"); $stmt->execute([$sel_mapel]); $nama_mapel = $stmt->fetchColumn();

// Fetch siswa and schema
$stmt = $pdo->prepare("SELECT * FROM tbl_siswa WHERE id_kelas=? AND status='Aktif' ORDER BY nama"); $stmt->execute([$sel_kelas]); $siswa_list = $stmt->fetchAll();
$stmtB = $pdo->prepare("SELECT komponen_json FROM tbl_bobot_nilai WHERE id_guru=? AND id_mapel=? AND id_kelas=? AND id_ta=? AND komponen_json IS NOT NULL ORDER BY id DESC LIMIT 1");
$stmtB->execute([$id_guru, $sel_mapel, $sel_kelas, $ta['id_ta']]);
$schema_json = $stmtB->fetchColumn();
$komponen_arr = json_decode($schema_json, true) ?: [];

if (empty($komponen_arr)) die("Konponen nilai belum diatur.");

require_once __DIR__ . '/../config/cetak_helper.php';

// School config
$school = $pdo->query("SELECT * FROM tbl_setting ORDER BY id DESC LIMIT 1")->fetch() ?: [];
$nama_sekolah = $school['nama_sekolah'] ?? 'NAMA SEKOLAH';
$logo_kiri = !empty($school['logo_kiri']) ? BASE_URL.'gambar/'.$school['logo_kiri'] : '';
$logo_kanan = !empty($school['logo_kanan']) ? BASE_URL.'gambar/'.$school['logo_kanan'] : '';

// Auto Semester Detection
$bulan = (int)date('m');
$semester_aktif = ($bulan >= 7 && $bulan <= 12) ? '1 (Ganjil)' : '2 (Genap)';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Daftar Nilai - <?= clean($nama_mapel) ?> - <?= clean($nama_kelas) ?></title>
    <style>
        @page { size: landscape; margin: 15mm; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 11px; color: #000; background: #fff; margin:0; padding:0; }
        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 15px; }
        .logo { width: 70px; height: 70px; object-fit: contain; }
        .kop-teks { flex: 1; text-align: center; padding: 0 10px; }
        .kop-teks h2 { margin: 0; font-size: 16px; }
        .kop-teks h1 { margin: 3px 0; font-size: 20px; text-transform: uppercase; }
        .kop-teks p { margin: 0; font-size: 11px; }
        
        .title { text-align: center; font-weight: bold; font-size: 14px; margin-bottom: 15px; text-transform: uppercase; }
        .meta-table { width: 100%; margin-bottom: 10px; font-size: 12px; }
        .meta-table td { padding: 2px 0; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #000; padding: 4px; text-align: center; }
        table.data-table th { background: #e2e8f0; font-weight: bold; }
        table.data-table td.left { text-align: left; padding-left: 5px; }
        
        .footer { width: 100%; margin-top: 30px; display: flex; justify-content: flex-end; }
        .ttd { text-align: center; width: 250px; }
        .ttd p { margin: 0 0 50px 0; }
        
        @media print {
            .no-print { display: none; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <?php if($logo_kiri): ?><img src="<?= $logo_kiri ?>" class="logo" alt="Logo"><?php else: ?><div class="logo"></div><?php endif; ?>
        <div class="kop-teks">
            <h2>YAYASAN PENDIDIKAN</h2>
            <h1><?= htmlspecialchars($nama_sekolah) ?></h1>
            <p><?= htmlspecialchars($school['alamat'] ?? '') ?></p>
            <p>Telp: <?= htmlspecialchars($school['telp'] ?? '') ?> | Email: <?= htmlspecialchars($school['email'] ?? '') ?></p>
        </div>
        <?php if($logo_kanan): ?><img src="<?= $logo_kanan ?>" class="logo" alt="Logo"><?php else: ?><div class="logo"></div><?php endif; ?>
    </div>
    
    <div class="title">DAFTAR NILAI SISWA PER MATA PELAJARAN</div>
    
    <table class="meta-table">
        <tr>
            <td width="100"><strong>Mata Pelajaran</strong></td><td width="10">:</td><td width="300"><?= clean($nama_mapel) ?></td>
            <td width="100"><strong>Semester</strong></td><td width="10">:</td><td><?= $semester_aktif ?></td>
        </tr>
        <tr>
            <td><strong>Kelas</strong></td><td>:</td><td><?= clean($nama_kelas) ?></td>
            <td><strong>Tahun Ajaran</strong></td><td>:</td><td><?= $ta['tahun'] ?></td>
        </tr>
        <tr>
            <td><strong>Nama Guru</strong></td><td>:</td><td><?= clean($_SESSION['nama']) ?></td>
            <td></td><td></td><td></td>
        </tr>
    </table>
    
    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width:30px;">NO</th>
                <th rowspan="2" style="width:150px;">NAMA SISWA</th>
                <?php foreach($komponen_arr as $comp): ?>
                    <th colspan="<?= $comp['kolom'] + 1 ?>"><?= htmlspecialchars($comp['nama']) ?> (<?= $comp['bobot'] ?>%)</th>
                <?php endforeach; ?>
                <th rowspan="2">NA (RAPORT)</th>
                <th rowspan="2">PREDIKAT</th>
            </tr>
            <tr>
                <?php foreach($komponen_arr as $comp): 
                    for($k=1; $k<=$comp['kolom']; $k++): ?>
                        <th><?= $k ?></th>
                    <?php endfor; ?>
                    <th style="background:#cbd5e1;">RATA</th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($siswa_list as $s): 
                $stmt_ex = $pdo->prepare("SELECT * FROM tbl_raport WHERE id_siswa=? AND id_mapel=? AND id_ta=?"); 
                $stmt_ex->execute([$s['id_siswa'], $sel_mapel, $ta['id_ta']]); 
                $ex = $stmt_ex->fetch();
                $data_nilai = $ex ? (json_decode($ex['data_nilai'], true) ?: []) : [];
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td class="left"><?= clean($s['nama']) ?></td>
                
                <?php foreach ($komponen_arr as $comp): 
                    $cid = $comp['id'];
                    $arr_vals = $data_nilai[$cid] ?? [];
                    $sum = 0; $cnt = 0;
                    
                    for($k=0; $k<$comp['kolom']; $k++):
                        $val = $arr_vals[$k] ?? '';
                        if($val !== '') { $sum += (float)$val; $cnt++; }
                ?>
                    <td><?= $val ?></td>
                <?php endfor; 
                    $rata = $cnt > 0 ? $sum / $cnt : 0;
                ?>
                    <td style="background:#f1f5f9; font-weight:bold;"><?= number_format($rata,1) ?></td>
                <?php endforeach; ?>
                
                <td style="font-weight:bold; font-size:12px;"><?= number_format($ex['nilai_akhir'] ?? 0, 1) ?></td>
                <td><?= $ex['predikat'] ?? '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <div class="ttd">
            <p>Mengetahui,</p>
            <p style="margin-bottom:60px;"><strong>Guru Mata Pelajaran</strong></p>
            <p><strong><u><?= clean($_SESSION['nama']) ?></u></strong></p>
        </div>
    </div>
</body>
</html>
