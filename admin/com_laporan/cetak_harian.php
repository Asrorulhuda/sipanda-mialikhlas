<?php
// Cetak Laporan Harian
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../config/cetak_helper.php';
cek_role(['admin','bendahara','kepsek']);

$tgl = $_GET['tgl'] ?? date('Y-m-d');

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $headers = ['No','Waktu','Tipe','Keterangan','Jumlah'];
    $rows = [];
    $masuk = $pdo->prepare("SELECT tanggal_bayar as waktu, CONCAT('SPP - ',j.nama_jenis,' (',s.nama,')') as ket, jumlah_bayar as jumlah FROM tbl_pembayaran p JOIN tbl_siswa s ON p.id_siswa=s.id_siswa JOIN tbl_jenis_bayar j ON p.id_jenis=j.id_jenis WHERE DATE(p.tanggal_bayar)=?");
    $masuk->execute([$tgl]);
    $no = 1;
    while ($r = $masuk->fetch()) { $rows[] = [$no++, date('H:i', strtotime($r['waktu'])), 'Pemasukan', $r['ket'], $r['jumlah']]; }
    $keluar = $pdo->prepare("SELECT tanggal as waktu, CONCAT(jk.nama,' - ',pk.uraian) as ket, pk.jumlah FROM tbl_pengeluaran_kas pk LEFT JOIN tbl_jenis_keluar jk ON pk.id_jenis=jk.id WHERE pk.tanggal=?");
    $keluar->execute([$tgl]);
    while ($r = $keluar->fetch()) { $rows[] = [$no++, '-', 'Pengeluaran', $r['ket'], $r['jumlah']]; }
    export_csv('laporan_harian_'.str_replace('-','', $tgl), $headers, $rows);
}

$masuk_spp = $pdo->prepare("SELECT p.*, s.nama, j.nama_jenis FROM tbl_pembayaran p JOIN tbl_siswa s ON p.id_siswa=s.id_siswa JOIN tbl_jenis_bayar j ON p.id_jenis=j.id_jenis WHERE DATE(p.tanggal_bayar)=? ORDER BY p.tanggal_bayar");
$masuk_spp->execute([$tgl]); $data_masuk = $masuk_spp->fetchAll();

$masuk_kas = $pdo->prepare("SELECT pk.*, jm.nama as jenis FROM tbl_penerimaan_kas pk LEFT JOIN tbl_jenis_masuk jm ON pk.id_jenis=jm.id WHERE pk.tanggal=?");
$masuk_kas->execute([$tgl]); $data_masuk_kas = $masuk_kas->fetchAll();

$keluar_kas = $pdo->prepare("SELECT pk.*, jk.nama as jenis FROM tbl_pengeluaran_kas pk LEFT JOIN tbl_jenis_keluar jk ON pk.id_jenis=jk.id WHERE pk.tanggal=?");
$keluar_kas->execute([$tgl]); $data_keluar = $keluar_kas->fetchAll();

$total_masuk = array_sum(array_column($data_masuk,'jumlah_bayar')) + array_sum(array_column($data_masuk_kas,'jumlah'));
$total_keluar = array_sum(array_column($data_keluar,'jumlah'));

cetak_header('Laporan Keuangan Harian - ' . tgl_indo($tgl), $setting);
?>

<h3 style="font-size:13px;margin:15px 0 8px;color:#059669">📥 Pemasukan SPP</h3>
<table>
    <thead><tr><th style="width:30px">No</th><th>Waktu</th><th>Nama Siswa</th><th>Jenis</th><th class="text-right">Jumlah</th></tr></thead>
    <tbody>
    <?php if ($data_masuk): foreach ($data_masuk as $i => $r): ?>
        <tr><td class="text-center"><?= $i+1 ?></td><td><?= date('H:i', strtotime($r['tanggal_bayar'])) ?></td><td><?= htmlspecialchars($r['nama']) ?></td><td><?= htmlspecialchars($r['nama_jenis']) ?></td><td class="text-right text-green"><?= number_format($r['jumlah_bayar'],0,',','.') ?></td></tr>
    <?php endforeach; else: ?><tr><td colspan="5" class="text-center" style="color:#999">Tidak ada pemasukan SPP</td></tr><?php endif; ?>
    </tbody>
    <tfoot><tr class="total-row"><td colspan="4" class="text-right">Subtotal SPP</td><td class="text-right"><?= number_format(array_sum(array_column($data_masuk,'jumlah_bayar')),0,',','.') ?></td></tr></tfoot>
</table>

<?php if ($data_masuk_kas): ?>
<h3 style="font-size:13px;margin:15px 0 8px;color:#2563eb">📥 Penerimaan Kas Lainnya</h3>
<table>
    <thead><tr><th style="width:30px">No</th><th>Jenis</th><th>Uraian</th><th class="text-right">Jumlah</th></tr></thead>
    <tbody>
    <?php foreach ($data_masuk_kas as $i => $r): ?>
        <tr><td class="text-center"><?= $i+1 ?></td><td><?= htmlspecialchars($r['jenis'] ?? '-') ?></td><td><?= htmlspecialchars($r['uraian']) ?></td><td class="text-right text-green"><?= number_format($r['jumlah'],0,',','.') ?></td></tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr class="total-row"><td colspan="3" class="text-right">Subtotal Penerimaan</td><td class="text-right"><?= number_format(array_sum(array_column($data_masuk_kas,'jumlah')),0,',','.') ?></td></tr></tfoot>
</table>
<?php endif; ?>

<h3 style="font-size:13px;margin:15px 0 8px;color:#dc2626">📤 Pengeluaran</h3>
<table>
    <thead><tr><th style="width:30px">No</th><th>Jenis</th><th>Uraian</th><th class="text-right">Jumlah</th></tr></thead>
    <tbody>
    <?php if ($data_keluar): foreach ($data_keluar as $i => $r): ?>
        <tr><td class="text-center"><?= $i+1 ?></td><td><?= htmlspecialchars($r['jenis'] ?? '-') ?></td><td><?= htmlspecialchars($r['uraian']) ?></td><td class="text-right text-red"><?= number_format($r['jumlah'],0,',','.') ?></td></tr>
    <?php endforeach; else: ?><tr><td colspan="4" class="text-center" style="color:#999">Tidak ada pengeluaran</td></tr><?php endif; ?>
    </tbody>
    <tfoot><tr class="total-row"><td colspan="3" class="text-right">Subtotal Pengeluaran</td><td class="text-right"><?= number_format($total_keluar,0,',','.') ?></td></tr></tfoot>
</table>

<table style="margin-top:20px">
    <tr style="background:#e8f5e9"><td class="text-bold" style="border:2px solid #4caf50;padding:10px">TOTAL PEMASUKAN</td><td class="text-right text-bold text-green" style="border:2px solid #4caf50;padding:10px;font-size:14px"><?= number_format($total_masuk,0,',','.') ?></td></tr>
    <tr style="background:#ffebee"><td class="text-bold" style="border:2px solid #f44336;padding:10px">TOTAL PENGELUARAN</td><td class="text-right text-bold text-red" style="border:2px solid #f44336;padding:10px;font-size:14px"><?= number_format($total_keluar,0,',','.') ?></td></tr>
    <tr style="background:#e3f2fd"><td class="text-bold" style="border:2px solid #2196f3;padding:10px">SELISIH</td><td class="text-right text-bold" style="border:2px solid #2196f3;padding:10px;font-size:14px;color:<?= ($total_masuk-$total_keluar)>=0?'#059669':'#dc2626' ?>"><?= number_format($total_masuk-$total_keluar,0,',','.') ?></td></tr>
</table>

<?php cetak_footer($setting); ?>
