<?php
$page_title = 'Laporan Pembayaran Per Bulan';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','bendahara','kepsek']);
cek_fitur('laporan');
$thn = isset($_GET['thn']) ? (int)$_GET['thn'] : (int)date('Y');
$data = [];
for ($i=1;$i<=12;$i++) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran WHERE MONTH(tanggal_bayar)=? AND YEAR(tanggal_bayar)=?");
    $stmt->execute([$i, $thn]);
    $jml = $stmt->fetchColumn();
    $data[] = ['bulan'=>bulan_indo($i),'no'=>$i,'jumlah'=>$jml];
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
            <a href="cetak_pembayaran.php?thn=<?= $thn ?>&export=csv" class="bg-emerald-600/80 hover:bg-emerald-600 px-3 py-2 rounded-lg text-xs"><i class="fas fa-file-excel mr-1"></i>Excel</a>
        </div>
    </div>
</div>
<div class="glass rounded-xl p-5">
    <div class="table-container"><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">Bulan</th><th class="pb-3">Jumlah Pembayaran</th><th class="pb-3" style="width:120px">Grafik</th></tr></thead>
        <tbody><?php $max = max(array_column($data,'jumlah')) ?: 1; foreach ($data as $d): ?>
        <tr class="border-b border-white/5">
            <td class="py-2 font-medium"><?= $d['bulan'] ?></td>
            <td class="text-emerald-400 font-medium"><?= rupiah($d['jumlah']) ?></td>
            <td><div class="bg-slate-800 rounded-full h-2 overflow-hidden"><div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full" style="width:<?= ($d['jumlah']/$max)*100 ?>%"></div></div></td>
        </tr>
        <?php endforeach; ?></tbody>
        <tfoot><tr class="font-bold border-t border-white/10"><td class="py-2">Total <?= $thn ?></td><td class="text-emerald-400"><?= rupiah($total) ?></td><td></td></tr></tfoot>
    </table></div>
</div>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
