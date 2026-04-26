<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../config/cetak_helper.php';
cek_role(['admin','guru','kepsek']);

$tgl = $_GET['tgl'] ?? date('Y-m-d');
$id_kelas = (int)($_GET['kelas'] ?? 0);

if (!$id_kelas) {
    die("Pilih kelas terlebih dahulu.");
}

// Get Kelas Info
$stmt = $pdo->prepare("SELECT * FROM tbl_kelas WHERE id_kelas = ?");
$stmt->execute([$id_kelas]);
$kelas = $stmt->fetch();

// Get Attendance Data
$stmt = $pdo->prepare("SELECT s.nama, s.nisn, a.jam_masuk, a.jam_keluar, a.status, a.keterangan, a.metode 
                       FROM tbl_siswa s 
                       LEFT JOIN tbl_absensi_siswa a ON s.id_siswa = a.id_siswa AND a.tanggal = ?
                       WHERE s.id_kelas = ? AND s.status = 'Aktif'
                       ORDER BY s.nama");
$stmt->execute([$tgl, $id_kelas]);
$data = $stmt->fetchAll();

cetak_header('Laporan Kehadiran Siswa', $setting);
?>

<div style="margin-bottom: 20px;">
    <table style="border: none; width: auto;">
        <tr><td style="border: none; padding: 2px 10px 2px 0;">Kelas</td><td style="border: none; padding: 2px 10px;">: <b><?= clean($kelas['nama_kelas']) ?></b></td></tr>
        <tr><td style="border: none; padding: 2px 10px 2px 0;">Tanggal</td><td style="border: none; padding: 2px 10px;">: <b><?= tgl_indo($tgl) ?></b></td></tr>
    </table>
</div>

<table>
    <thead>
        <tr>
            <th style="width: 30px;">No</th>
            <th>Nama Siswa</th>
            <th style="width: 100px;">NISN</th>
            <th style="width: 60px;">Masuk</th>
            <th style="width: 60px;">Pulang</th>
            <th>Metode</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $i => $r): ?>
        <tr>
            <td class="text-center"><?= $i+1 ?></td>
            <td><?= clean($r['nama']) ?></td>
            <td class="text-center"><?= clean($r['nisn']) ?></td>
            <td class="text-center"><?= $r['jam_masuk'] ? substr($r['jam_masuk'], 0, 5) : '-' ?></td>
            <td class="text-center"><?= $r['jam_keluar'] ? substr($r['jam_keluar'], 0, 5) : '-' ?></td>
            <td class="text-center"><?= $r['metode'] ?: '-' ?></td>
            <td>
                <?php 
                if (!$r['status']) {
                    echo '<span style="color: red;">Alpa</span>';
                } else {
                    echo clean($r['keterangan']);
                    if ($r['status'] == 'IN') echo ' (Hanya Masuk)';
                }
                ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div style="margin-top: 30px; display: flex; justify-content: flex-end;">
    <div style="text-align: center; width: 200px;">
        <p>Dicetak pada: <?= date('d/m/Y H:i') ?></p>
        <br><br><br>
        <p><b>( ____________________ )</b></p>
        <p>Petugas Absensi</p>
    </div>
</div>

<?php cetak_footer($setting); ?>
