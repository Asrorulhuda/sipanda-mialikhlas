<?php
$page_title = 'Rekapitulasi Pembayaran';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','bendahara','kepsek']);
cek_fitur('laporan');
$thn = isset($_GET['thn']) ? (int)$_GET['thn'] : (int)date('Y');

$kelas_list = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();
$data = [];
foreach ($kelas_list as $k) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(p.jumlah_bayar),0) FROM tbl_pembayaran p JOIN tbl_siswa s ON p.id_siswa=s.id_siswa WHERE s.id_kelas=? AND YEAR(p.tanggal_bayar)=?");
    $stmt->execute([$k['id_kelas'], $thn]);
    $jml = $stmt->fetchColumn();
    
    $cnt = $pdo->prepare("SELECT COUNT(DISTINCT p.id_siswa) FROM tbl_pembayaran p JOIN tbl_siswa s ON p.id_siswa=s.id_siswa WHERE s.id_kelas=? AND YEAR(p.tanggal_bayar)=?");
    $cnt->execute([$k['id_kelas'], $thn]);
    
    $data[] = ['kelas'=>$k['nama_kelas'],'jumlah'=>$jml,'siswa_bayar'=>$cnt->fetchColumn()];
}
$total = array_sum(array_column($data,'jumlah'));

require_once __DIR__ . '/../../template/header.php'; require_once __DIR__ . '/../../template/sidebar.php'; require_once __DIR__ . '/../../template/topbar.php';
?>
<div class="glass rounded-xl p-5 mb-6">
    <div class="flex flex-col sm:flex-row gap-3 items-end justify-between">
        <form method="GET" class="flex gap-3 items-end">
            <div><label class="block text-xs text-slate-400 mb-1">Tahun</label><input type="number" name="thn" value="<?= $thn ?>" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm w-28"></div>
            <button class="bg-blue-600 px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i>Filter</button>
        </form>
        <div class="flex gap-2">
            <a href="cetak_pembayaran.php?thn=<?= $thn ?>" target="_blank" class="bg-purple-600/80 hover:bg-purple-600 px-3 py-2 rounded-lg text-xs"><i class="fas fa-print mr-1"></i>Cetak</a>
        </div>
    </div>
</div>
<!-- Summary -->
<div class="glass rounded-xl p-4 mb-4 flex items-center justify-between">
    <span class="text-sm text-slate-400"><i class="fas fa-chart-bar text-blue-400 mr-2"></i>Total Rekapitulasi <?= $thn ?></span>
    <span class="text-xl font-bold text-emerald-400"><?= rupiah($total) ?></span>
</div>
<div class="glass rounded-xl p-5"><div class="table-container"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">Kelas</th><th class="pb-3">Siswa Bayar</th><th class="pb-3">Total Pembayaran</th><th class="pb-3" style="width:150px">Progress</th></tr></thead>
    <tbody><?php $max = max(array_column($data,'jumlah')) ?: 1; foreach ($data as $i => $d): ?>
    <tr class="border-b border-white/5"><td class="py-2"><?= $i+1 ?></td><td class="font-medium"><?= clean($d['kelas']) ?></td><td><?= $d['siswa_bayar'] ?> siswa</td><td class="text-emerald-400 font-medium"><?= rupiah($d['jumlah']) ?></td>
    <td><div class="bg-slate-800 rounded-full h-2.5 overflow-hidden"><div class="bg-gradient-to-r from-blue-500 to-purple-500 h-full rounded-full transition-all" style="width:<?= ($d['jumlah']/$max)*100 ?>%"></div></div></td></tr>
    <?php endforeach; ?></tbody>
    <tfoot><tr class="font-bold border-t border-white/10"><td colspan="3" class="py-2 text-right">TOTAL</td><td class="text-emerald-400"><?= rupiah($total) ?></td><td></td></tr></tfoot>
</table></div></div>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
