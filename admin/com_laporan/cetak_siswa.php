<?php
// Cetak Data Siswa Per Kelas
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../config/cetak_helper.php';
cek_role(['admin','bendahara','kepsek']);

$kelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : 0;

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $headers = ['No','NISN','NIS','Nama','JK','Kelas','Tempat Lahir','Tanggal Lahir','Alamat','HP Ortu','Status'];
    $rows = [];
    $q = "SELECT s.*, k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE 1=1";
    $params = [];
    if ($kelas) { $q .= " AND s.id_kelas=?"; $params[] = $kelas; }
    $q .= " ORDER BY k.nama_kelas, s.nama";
    $stmt = $pdo->prepare($q); $stmt->execute($params);
    $no = 1;
    while ($r = $stmt->fetch()) {
        $rows[] = [$no++, $r['nisn'], $r['nis'], $r['nama'], $r['jk'], $r['nama_kelas'], $r['tempat_lahir'], $r['tanggal_lahir'], $r['alamat'], $r['no_hp_ortu'], $r['status']];
    }
    export_csv('data_siswa'.($kelas ? '_kelas'.$kelas : ''), $headers, $rows);
}

$q = "SELECT s.*, k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE 1=1";
$params = [];
if ($kelas) { $q .= " AND s.id_kelas=?"; $params[] = $kelas; }
$q .= " ORDER BY k.nama_kelas, s.nama";
$stmt = $pdo->prepare($q); $stmt->execute($params);
$data = $stmt->fetchAll();

$kelas_info = '';
if ($kelas) { $ki = $pdo->prepare("SELECT nama_kelas FROM tbl_kelas WHERE id_kelas=?"); $ki->execute([$kelas]); $kelas_info = $ki->fetchColumn(); }

cetak_header('Data Siswa' . ($kelas_info ? ' - '.$kelas_info : ' - Semua Kelas'), $setting);
?>
<table>
    <thead>
        <tr><th style="width:30px">No</th><th>NISN</th><th>NIS</th><th>Nama Siswa</th><th>JK</th><th>Kelas</th><th>TTL</th><th>HP Ortu</th><th>Status</th></tr>
    </thead>
    <tbody>
    <?php foreach ($data as $i => $r): ?>
        <tr>
            <td class="text-center"><?= $i+1 ?></td>
            <td><?= htmlspecialchars($r['nisn'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['nis'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['nama']) ?></td>
            <td class="text-center"><?= $r['jk'] ?></td>
            <td><?= htmlspecialchars($r['nama_kelas'] ?? '-') ?></td>
            <td style="font-size:10px"><?= htmlspecialchars($r['tempat_lahir'] ?? '') ?>, <?= $r['tanggal_lahir'] ? date('d/m/Y', strtotime($r['tanggal_lahir'])) : '-' ?></td>
            <td><?= htmlspecialchars($r['no_hp_ortu'] ?? '-') ?></td>
            <td class="text-center"><strong><?= $r['status'] ?></strong></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p style="margin-top:10px;font-size:11px;color:#666">Total: <?= count($data) ?> siswa</p>
<?php cetak_footer($setting); ?>
