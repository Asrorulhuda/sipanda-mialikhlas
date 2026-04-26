<?php
// Cetak Tagihan Siswa
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../config/cetak_helper.php';
cek_role(['admin','bendahara','kepsek']);

$kelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : 0;
if (!$kelas) { die('Pilih kelas terlebih dahulu.'); }

$kelas_info = $pdo->prepare("SELECT nama_kelas FROM tbl_kelas WHERE id_kelas=?");
$kelas_info->execute([$kelas]); $nama_kelas = $kelas_info->fetchColumn();

$siswa_list = $pdo->prepare("SELECT s.*,k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE s.id_kelas=? AND s.status='Aktif' ORDER BY s.nama");
$siswa_list->execute([$kelas]); $siswa_list = $siswa_list->fetchAll();

$data = [];
foreach ($siswa_list as $s) {
    $tarif_stmt = $pdo->prepare("SELECT COALESCE(SUM(t.nominal),0)*12 FROM tbl_tarif t JOIN tbl_jenis_bayar j ON t.id_jenis=j.id_jenis WHERE t.id_kelas=? AND j.tipe='Bulanan'");
    $tarif_stmt->execute([$s['id_kelas']]);
    $tarif_total = $tarif_stmt->fetchColumn();
    
    $bayar_stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran WHERE id_siswa=?");
    $bayar_stmt->execute([$s['id_siswa']]);
    $sudah_bayar = $bayar_stmt->fetchColumn();
    
    $sisa = $tarif_total - $sudah_bayar;
    $data[] = ['nama'=>$s['nama'],'nisn'=>$s['nisn'],'kelas'=>$s['nama_kelas'],'tarif'=>$tarif_total,'bayar'=>$sudah_bayar,'sisa'=>$sisa];
}

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $headers = ['No','NISN','Nama Siswa','Kelas','Total Tarif','Sudah Bayar','Sisa Tagihan','Status'];
    $rows = [];
    foreach ($data as $i => $d) {
        $rows[] = [$i+1, $d['nisn'], $d['nama'], $d['kelas'], $d['tarif'], $d['bayar'], $d['sisa'], $d['sisa'] > 0 ? 'Belum Lunas' : 'Lunas'];
    }
    export_csv('tagihan_'.$nama_kelas, $headers, $rows);
}

cetak_header('Laporan Tagihan Siswa - ' . htmlspecialchars($nama_kelas), $setting);

$total_tarif = array_sum(array_column($data,'tarif'));
$total_bayar = array_sum(array_column($data,'bayar'));
$total_sisa = array_sum(array_column($data,'sisa'));
?>
<table>
    <thead>
        <tr><th style="width:30px">No</th><th>NISN</th><th>Nama Siswa</th><th class="text-right">Total Tarif</th><th class="text-right">Sudah Bayar</th><th class="text-right">Sisa Tagihan</th><th>Status</th></tr>
    </thead>
    <tbody>
    <?php foreach ($data as $i => $r): ?>
        <tr>
            <td class="text-center"><?= $i+1 ?></td>
            <td><?= htmlspecialchars($r['nisn']) ?></td>
            <td><?= htmlspecialchars($r['nama']) ?></td>
            <td class="text-right"><?= number_format($r['tarif'],0,',','.') ?></td>
            <td class="text-right text-green"><?= number_format($r['bayar'],0,',','.') ?></td>
            <td class="text-right <?= $r['sisa'] > 0 ? 'text-red text-bold' : 'text-green' ?>"><?= number_format($r['sisa'],0,',','.') ?></td>
            <td class="text-center"><?= $r['sisa'] > 0 ? '❌ Belum Lunas' : '✅ Lunas' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="3" class="text-right text-bold">TOTAL</td>
            <td class="text-right"><?= number_format($total_tarif,0,',','.') ?></td>
            <td class="text-right"><?= number_format($total_bayar,0,',','.') ?></td>
            <td class="text-right"><?= number_format($total_sisa,0,',','.') ?></td>
            <td></td>
        </tr>
    </tfoot>
</table>
<p style="margin-top:10px;font-size:11px;color:#666">
    Total <?= count($data) ?> siswa | 
    Lunas: <?= count(array_filter($data, fn($d) => $d['sisa'] <= 0)) ?> | 
    Belum: <?= count(array_filter($data, fn($d) => $d['sisa'] > 0)) ?>
</p>
<?php cetak_footer($setting); ?>
