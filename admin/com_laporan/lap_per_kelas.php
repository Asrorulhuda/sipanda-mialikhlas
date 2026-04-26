<?php
$page_title = 'Laporan Pembayaran Per Kelas';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','bendahara','kepsek']);
cek_fitur('laporan');
$kelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : 0;
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 0;
$thn = isset($_GET['thn']) ? (int)$_GET['thn'] : (int)date('Y');
$kelas_list = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();

$q = "SELECT p.*, s.nama, s.nisn, k.nama_kelas, j.nama_jenis FROM tbl_pembayaran p JOIN tbl_siswa s ON p.id_siswa=s.id_siswa LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas LEFT JOIN tbl_jenis_bayar j ON p.id_jenis=j.id_jenis WHERE YEAR(p.tanggal_bayar)=?";
$params = [$thn];
if ($kelas) { $q .= " AND s.id_kelas=?"; $params[] = $kelas; }
if ($bulan) { $q .= " AND MONTH(p.tanggal_bayar)=?"; $params[] = $bulan; }
$q .= " ORDER BY p.tanggal_bayar DESC";
$stmt = $pdo->prepare($q); $stmt->execute($params);
$data = $stmt->fetchAll();
$total = array_sum(array_column($data,'jumlah_bayar'));

require_once __DIR__ . '/../../template/header.php'; require_once __DIR__ . '/../../template/sidebar.php'; require_once __DIR__ . '/../../template/topbar.php';
?>
<div class="glass rounded-xl p-5 mb-6">
    <div class="flex flex-col sm:flex-row gap-3 items-end justify-between">
        <form method="GET" class="flex gap-3 items-end flex-wrap">
            <div><label class="block text-xs text-slate-400 mb-1">Kelas</label><select name="kelas" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><option value="">Semua</option><?php foreach ($kelas_list as $k): ?><option value="<?= $k['id_kelas'] ?>" <?= $kelas==$k['id_kelas']?'selected':'' ?>><?= clean($k['nama_kelas']) ?></option><?php endforeach; ?></select></div>
            <div><label class="block text-xs text-slate-400 mb-1">Bulan</label><select name="bulan" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><option value="">Semua</option><?php for($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $bulan==$m?'selected':'' ?>><?= bulan_indo($m) ?></option><?php endfor; ?></select></div>
            <div><label class="block text-xs text-slate-400 mb-1">Tahun</label><input type="number" name="thn" value="<?= $thn ?>" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm w-24"></div>
            <button class="bg-blue-600 px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i>Filter</button>
        </form>
        <div class="flex gap-2">
            <a href="cetak_pembayaran.php?kelas=<?= $kelas ?>&bulan=<?= $bulan ?>&thn=<?= $thn ?>" target="_blank" class="bg-purple-600/80 hover:bg-purple-600 px-3 py-2 rounded-lg text-xs"><i class="fas fa-print mr-1"></i>Cetak</a>
            <a href="cetak_pembayaran.php?kelas=<?= $kelas ?>&bulan=<?= $bulan ?>&thn=<?= $thn ?>&export=csv" class="bg-emerald-600/80 hover:bg-emerald-600 px-3 py-2 rounded-lg text-xs"><i class="fas fa-file-excel mr-1"></i>Excel</a>
        </div>
    </div>
</div>
<!-- Total Card -->
<div class="glass rounded-xl p-4 mb-4 flex items-center justify-between">
    <span class="text-sm text-slate-400"><i class="fas fa-coins text-emerald-400 mr-2"></i>Total Pembayaran</span>
    <span class="text-xl font-bold text-emerald-400"><?= rupiah($total) ?></span>
</div>
<div class="glass rounded-xl p-5"><div class="table-container"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">Tanggal</th><th class="pb-3">NISN</th><th class="pb-3">Nama</th><th class="pb-3">Kelas</th><th class="pb-3">Jenis</th><th class="pb-3">Jumlah</th><th class="pb-3">Cara</th></tr></thead>
    <tbody><?php if ($data): foreach ($data as $i => $r): ?>
    <tr class="border-b border-white/5"><td class="py-2"><?= $i+1 ?></td><td class="text-xs"><?= date('d/m/Y', strtotime($r['tanggal_bayar'])) ?></td><td class="font-mono text-xs"><?= clean($r['nisn']) ?></td><td class="font-medium"><?= clean($r['nama']) ?></td><td><?= clean($r['nama_kelas']) ?></td><td><?= clean($r['nama_jenis']) ?></td><td class="text-emerald-400 font-medium"><?= rupiah($r['jumlah_bayar']) ?></td><td><span class="px-2 py-0.5 rounded-full text-xs bg-blue-500/20 text-blue-400"><?= $r['cara_bayar'] ?></span></td></tr>
    <?php endforeach; else: ?><tr><td colspan="8" class="py-4 text-center text-slate-500">Tidak ada data pembayaran.</td></tr><?php endif; ?></tbody>
</table></div>
<p class="text-xs text-slate-500 mt-3">Menampilkan <?= count($data) ?> transaksi</p>
</div>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
