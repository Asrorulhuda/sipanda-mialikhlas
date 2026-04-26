<?php
$page_title = 'Laporan Tagihan Siswa';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','bendahara','kepsek']);
cek_fitur('laporan');
$kelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : 0;
$kelas_list = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();
$data = [];
if ($kelas) {
    $stmt = $pdo->prepare("SELECT s.*,k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE s.id_kelas=? AND s.status='Aktif' ORDER BY s.nama");
    $stmt->execute([$kelas]);
    $siswa_list = $stmt->fetchAll();
    foreach ($siswa_list as $s) {
        $tarif_stmt = $pdo->prepare("SELECT COALESCE(SUM(t.nominal),0)*12 FROM tbl_tarif t JOIN tbl_jenis_bayar j ON t.id_jenis=j.id_jenis WHERE t.id_kelas=? AND j.tipe='Bulanan'");
        $tarif_stmt->execute([$s['id_kelas']]);
        $tarif_total = $tarif_stmt->fetchColumn();
        $bayar_stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran WHERE id_siswa=?");
        $bayar_stmt->execute([$s['id_siswa']]);
        $sudah_bayar = $bayar_stmt->fetchColumn();
        $sisa = $tarif_total - $sudah_bayar;
        if ($sisa > 0) $data[] = ['nama'=>$s['nama'],'nisn'=>$s['nisn'],'kelas'=>$s['nama_kelas'],'tarif'=>$tarif_total,'bayar'=>$sudah_bayar,'sisa'=>$sisa];
    }
}
require_once __DIR__ . '/../../template/header.php'; require_once __DIR__ . '/../../template/sidebar.php'; require_once __DIR__ . '/../../template/topbar.php';
?>
<div class="glass rounded-xl p-5 mb-6">
    <div class="flex flex-col sm:flex-row gap-3 items-end justify-between">
        <form method="GET" class="flex gap-3 items-end">
            <div><label class="block text-xs text-slate-400 mb-1">Kelas</label><select name="kelas" onchange="this.form.submit()" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><option value="">-- Pilih --</option><?php foreach ($kelas_list as $k): ?><option value="<?= $k['id_kelas'] ?>" <?= $kelas==$k['id_kelas']?'selected':'' ?>><?= clean($k['nama_kelas']) ?></option><?php endforeach; ?></select></div>
        </form>
        <?php if ($kelas && $data): ?>
        <div class="flex gap-2">
            <a href="cetak_tagihan.php?kelas=<?= $kelas ?>" target="_blank" class="bg-purple-600/80 hover:bg-purple-600 px-3 py-2 rounded-lg text-xs"><i class="fas fa-print mr-1"></i>Cetak</a>
            <a href="cetak_tagihan.php?kelas=<?= $kelas ?>&export=csv" class="bg-emerald-600/80 hover:bg-emerald-600 px-3 py-2 rounded-lg text-xs"><i class="fas fa-file-excel mr-1"></i>Excel</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php if ($data): ?>
<div class="glass rounded-xl p-5"><div class="table-container"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">Nama</th><th class="pb-3">Total Tarif</th><th class="pb-3">Sudah Bayar</th><th class="pb-3">Sisa Tagihan</th></tr></thead>
    <tbody><?php foreach ($data as $i => $r): ?>
    <tr class="border-b border-white/5"><td class="py-2"><?= $i+1 ?></td><td class="font-medium"><?= clean($r['nama']) ?></td><td><?= rupiah($r['tarif']) ?></td><td class="text-emerald-400"><?= rupiah($r['bayar']) ?></td><td class="text-red-400 font-bold"><?= rupiah($r['sisa']) ?></td></tr>
    <?php endforeach; ?></tbody>
    <tfoot><tr class="font-bold border-t border-white/10"><td class="py-2" colspan="2">Total (<?= count($data) ?> siswa)</td><td><?= rupiah(array_sum(array_column($data,'tarif'))) ?></td><td class="text-emerald-400"><?= rupiah(array_sum(array_column($data,'bayar'))) ?></td><td class="text-red-400"><?= rupiah(array_sum(array_column($data,'sisa'))) ?></td></tr></tfoot>
</table></div></div>
<?php elseif ($kelas): ?><div class="glass rounded-xl p-5 text-center text-emerald-400"><i class="fas fa-check-circle mr-1"></i>Semua siswa sudah lunas!</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
