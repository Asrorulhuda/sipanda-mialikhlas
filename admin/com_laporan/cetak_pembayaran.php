<?php
// Cetak Laporan Pembayaran Per Kelas
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../config/cetak_helper.php';
cek_role(['admin','bendahara','kepsek']);

$kelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : 0;
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 0;
$thn = isset($_GET['thn']) ? (int)$_GET['thn'] : (int)date('Y');

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $headers = ['No','NISN','Nama Siswa','Kelas','Jenis Bayar','Jumlah','Tanggal','Cara Bayar'];
    $rows = [];
    $q = "SELECT p.*, s.nama, s.nisn, k.nama_kelas, j.nama_jenis 
          FROM tbl_pembayaran p 
          JOIN tbl_siswa s ON p.id_siswa=s.id_siswa 
          LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas 
          LEFT JOIN tbl_jenis_bayar j ON p.id_jenis=j.id_jenis 
          WHERE YEAR(p.tanggal_bayar)=?";
    $params = [$thn];
    if ($kelas) { $q .= " AND s.id_kelas=?"; $params[] = $kelas; }
    if ($bulan) { $q .= " AND MONTH(p.tanggal_bayar)=?"; $params[] = $bulan; }
    $q .= " ORDER BY p.tanggal_bayar DESC";
    $stmt = $pdo->prepare($q); $stmt->execute($params);
    $no = 1;
    while ($r = $stmt->fetch()) {
        $rows[] = [$no++, $r['nisn'], $r['nama'], $r['nama_kelas'], $r['nama_jenis'], $r['jumlah_bayar'], date('d/m/Y', strtotime($r['tanggal_bayar'])), $r['cara_bayar']];
    }
    export_csv('laporan_pembayaran_'.$thn, $headers, $rows);
}

// Query data
$q = "SELECT p.*, s.nama, s.nisn, k.nama_kelas, j.nama_jenis 
      FROM tbl_pembayaran p 
      JOIN tbl_siswa s ON p.id_siswa=s.id_siswa 
      LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas 
      LEFT JOIN tbl_jenis_bayar j ON p.id_jenis=j.id_jenis 
      WHERE YEAR(p.tanggal_bayar)=?";
$params = [$thn];
if ($kelas) { $q .= " AND s.id_kelas=?"; $params[] = $kelas; }
if ($bulan) { $q .= " AND MONTH(p.tanggal_bayar)=?"; $params[] = $bulan; }
$q .= " ORDER BY p.tanggal_bayar DESC";
$stmt = $pdo->prepare($q); $stmt->execute($params);
$data = $stmt->fetchAll();
$total = array_sum(array_column($data, 'jumlah_bayar'));

$kelas_info = '';
if ($kelas) { $ki = $pdo->prepare("SELECT nama_kelas FROM tbl_kelas WHERE id_kelas=?"); $ki->execute([$kelas]); $kelas_info = $ki->fetchColumn(); }

$title = 'Laporan Pembayaran' . ($kelas_info ? ' - '.$kelas_info : '') . ($bulan ? ' - '.bulan_indo($bulan) : '') . ' Tahun '.$thn;
cetak_header($title, $setting);
?>

<table>
    <thead>
        <tr>
            <th style="width:30px">No</th>
            <th>NISN</th>
            <th>Nama Siswa</th>
            <th>Kelas</th>
            <th>Jenis Bayar</th>
            <th class="text-right">Jumlah</th>
            <th>Tanggal</th>
            <th>Cara Bayar</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $i => $r): ?>
        <tr>
            <td class="text-center"><?= $i+1 ?></td>
            <td><?= htmlspecialchars($r['nisn']) ?></td>
            <td><?= htmlspecialchars($r['nama']) ?></td>
            <td><?= htmlspecialchars($r['nama_kelas']) ?></td>
            <td><?= htmlspecialchars($r['nama_jenis']) ?></td>
            <td class="text-right"><?= number_format($r['jumlah_bayar'],0,',','.') ?></td>
            <td><?= date('d/m/Y', strtotime($r['tanggal_bayar'])) ?></td>
            <td><?= $r['cara_bayar'] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="5" class="text-right text-bold">TOTAL</td>
            <td class="text-right text-bold"><?= number_format($total,0,',','.') ?></td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

<p style="margin-top:10px;font-size:11px;color:#666">Total <?= count($data) ?> transaksi pembayaran.</p>

<?php cetak_footer($setting); ?>
