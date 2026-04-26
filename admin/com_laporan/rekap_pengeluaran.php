<?php
$page_title = 'Rekapitulasi Pengeluaran';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','bendahara','kepsek']);
cek_fitur('laporan');
$thn = isset($_GET['thn']) ? (int)$_GET['thn'] : (int)date('Y');
$data = [];
for ($i=1;$i<=12;$i++) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM tbl_pengeluaran_kas WHERE MONTH(tanggal)=? AND YEAR(tanggal)=?");
    $stmt->execute([$i, $thn]);
    $jml = $stmt->fetchColumn();
    $data[] = ['bulan'=>bulan_indo($i),'jumlah'=>$jml];
}
$total = array_sum(array_column($data,'jumlah'));
require_once __DIR__ . '/../../template/header.php'; require_once __DIR__ . '/../../template/sidebar.php'; require_once __DIR__ . '/../../template/topbar.php';
?>
<div class="glass rounded-xl p-5 mb-6">
    <div class="flex flex-col sm:flex-row gap-3 items-end justify-between">
        <form method="GET" class="flex gap-3 items-center">
            <input type="number" name="thn" value="<?= $thn ?>" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm w-24">
            <button class="bg-blue-600 px-3 py-2 rounded-lg text-sm"><i class="fas fa-filter"></i></button>
        </form>
        <div class="flex gap-2">
            <button onclick="window.print()" class="bg-purple-600/80 hover:bg-purple-600 px-3 py-2 rounded-lg text-xs"><i class="fas fa-print mr-1"></i>Cetak</button>
        </div>
    </div>
</div>
<!-- Total Card -->
<div class="glass rounded-xl p-4 mb-4 flex items-center justify-between">
    <span class="text-sm text-slate-400"><i class="fas fa-chart-line text-red-400 mr-2"></i>Total Pengeluaran <?= $thn ?></span>
    <span class="text-xl font-bold text-red-400"><?= rupiah($total) ?></span>
</div>
<div class="glass rounded-xl p-5"><div class="table-container"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">Bulan</th><th class="pb-3">Pengeluaran</th><th class="pb-3" style="width:120px">Grafik</th></tr></thead>
    <tbody><?php $max = max(array_column($data,'jumlah')) ?: 1; foreach ($data as $d): ?>
    <tr class="border-b border-white/5">
        <td class="py-2 font-medium"><?= $d['bulan'] ?></td>
        <td class="text-red-400 font-medium"><?= rupiah($d['jumlah']) ?></td>
        <td><div class="bg-slate-800 rounded-full h-2 overflow-hidden"><div class="bg-gradient-to-r from-red-500 to-pink-500 h-full rounded-full" style="width:<?= ($d['jumlah']/$max)*100 ?>%"></div></div></td>
    </tr>
    <?php endforeach; ?></tbody>
    <tfoot><tr class="font-bold border-t border-white/10"><td class="py-2">Total</td><td class="text-red-400"><?= rupiah($total) ?></td><td></td></tr></tfoot>
</table></div></div>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
