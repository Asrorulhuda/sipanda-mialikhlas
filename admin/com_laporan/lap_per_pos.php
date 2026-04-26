<?php
$page_title = 'Laporan Per Pos Bayar';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','bendahara','kepsek']);
cek_fitur('laporan');
$thn = isset($_GET['thn']) ? (int)$_GET['thn'] : (int)date('Y');

$stmt = $pdo->prepare("SELECT pb.nama_pos, jb.nama_jenis, jb.tipe, COALESCE(SUM(p.jumlah_bayar),0) as total, COUNT(p.id_pembayaran) as trx 
    FROM tbl_pos_bayar pb 
    LEFT JOIN tbl_jenis_bayar jb ON pb.id_pos=jb.id_pos 
    LEFT JOIN tbl_pembayaran p ON jb.id_jenis=p.id_jenis AND YEAR(p.tanggal_bayar)=? 
    GROUP BY pb.id_pos, jb.id_jenis 
    ORDER BY pb.nama_pos, jb.nama_jenis");
$stmt->execute([$thn]);
$data = $stmt->fetchAll();
$grand_total = array_sum(array_column($data, 'total'));

require_once __DIR__ . '/../../template/header.php'; require_once __DIR__ . '/../../template/sidebar.php'; require_once __DIR__ . '/../../template/topbar.php';
?>
<div class="glass rounded-xl p-5 mb-6">
    <div class="flex flex-col sm:flex-row gap-3 items-end justify-between">
        <form method="GET" class="flex gap-3 items-end">
            <div><label class="block text-xs text-slate-400 mb-1">Tahun</label><input type="number" name="thn" value="<?= $thn ?>" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm w-28"></div>
            <button class="bg-blue-600 px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i>Filter</button>
        </form>
        <button onclick="window.print()" class="bg-purple-600/80 hover:bg-purple-600 px-3 py-2 rounded-lg text-xs"><i class="fas fa-print mr-1"></i>Cetak</button>
    </div>
</div>
<div class="glass rounded-xl p-4 mb-4 flex items-center justify-between">
    <span class="text-sm text-slate-400"><i class="fas fa-layer-group text-blue-400 mr-2"></i>Grand Total <?= $thn ?></span>
    <span class="text-xl font-bold text-emerald-400"><?= rupiah($grand_total) ?></span>
</div>
<div class="glass rounded-xl p-5"><div class="table-container"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">Pos Bayar</th><th class="pb-3">Jenis Bayar</th><th class="pb-3">Tipe</th><th class="pb-3">Transaksi</th><th class="pb-3">Total</th></tr></thead>
    <tbody><?php foreach ($data as $i => $r): ?>
    <tr class="border-b border-white/5"><td class="py-2"><?= $i+1 ?></td><td class="font-medium"><?= clean($r['nama_pos']) ?></td><td><?= clean($r['nama_jenis']??'-') ?></td>
    <td><span class="px-2 py-0.5 rounded-full text-xs <?= ($r['tipe']??'')=='Bulanan'?'bg-blue-500/20 text-blue-400':'bg-amber-500/20 text-amber-400' ?>"><?= $r['tipe'] ?? '-' ?></span></td>
    <td><?= $r['trx'] ?></td><td class="text-emerald-400 font-medium"><?= rupiah($r['total']) ?></td></tr>
    <?php endforeach; ?></tbody>
    <tfoot><tr class="font-bold border-t border-white/10"><td colspan="5" class="py-2 text-right">TOTAL</td><td class="text-emerald-400"><?= rupiah($grand_total) ?></td></tr></tfoot>
</table></div></div>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
