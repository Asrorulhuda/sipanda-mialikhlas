<?php
$page_title = 'Laporan Keuangan Harian';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','bendahara','kepsek']);
cek_fitur('laporan');
$tgl = $_GET['tgl'] ?? date('Y-m-d');

$masuk_spp = $pdo->prepare("SELECT p.*, s.nama, j.nama_jenis FROM tbl_pembayaran p JOIN tbl_siswa s ON p.id_siswa=s.id_siswa JOIN tbl_jenis_bayar j ON p.id_jenis=j.id_jenis WHERE DATE(p.tanggal_bayar)=? ORDER BY p.tanggal_bayar");
$masuk_spp->execute([$tgl]); $data_masuk = $masuk_spp->fetchAll();

$masuk_kas = $pdo->prepare("SELECT pk.*, jm.nama as jenis FROM tbl_penerimaan_kas pk LEFT JOIN tbl_jenis_masuk jm ON pk.id_jenis=jm.id WHERE pk.tanggal=?");
$masuk_kas->execute([$tgl]); $data_penerimaan = $masuk_kas->fetchAll();

$keluar_kas = $pdo->prepare("SELECT pk.*, jk.nama as jenis FROM tbl_pengeluaran_kas pk LEFT JOIN tbl_jenis_keluar jk ON pk.id_jenis=jk.id WHERE pk.tanggal=?");
$keluar_kas->execute([$tgl]); $data_keluar = $keluar_kas->fetchAll();

$total_masuk = array_sum(array_column($data_masuk,'jumlah_bayar')) + array_sum(array_column($data_penerimaan,'jumlah'));
$total_keluar = array_sum(array_column($data_keluar,'jumlah'));
$selisih = $total_masuk - $total_keluar;

require_once __DIR__ . '/../../template/header.php'; require_once __DIR__ . '/../../template/sidebar.php'; require_once __DIR__ . '/../../template/topbar.php';
?>
<div class="glass rounded-xl p-5 mb-6">
    <div class="flex flex-col sm:flex-row gap-3 items-end justify-between">
        <form method="GET" class="flex gap-3 items-end">
            <div><label class="block text-xs text-slate-400 mb-1">Tanggal</label><input type="date" name="tgl" value="<?= $tgl ?>" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
            <button class="bg-blue-600 px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i>Filter</button>
        </form>
        <div class="flex gap-2">
            <a href="cetak_harian.php?tgl=<?= $tgl ?>" target="_blank" class="bg-purple-600/80 hover:bg-purple-600 px-3 py-2 rounded-lg text-xs"><i class="fas fa-print mr-1"></i>Cetak</a>
            <a href="cetak_harian.php?tgl=<?= $tgl ?>&export=csv" class="bg-emerald-600/80 hover:bg-emerald-600 px-3 py-2 rounded-lg text-xs"><i class="fas fa-file-excel mr-1"></i>Excel</a>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="glass rounded-xl p-5"><p class="text-xs text-slate-400 mb-1"><i class="fas fa-arrow-down text-emerald-400 mr-1"></i>Total Pemasukan</p><p class="text-xl font-bold text-emerald-400"><?= rupiah($total_masuk) ?></p></div>
    <div class="glass rounded-xl p-5"><p class="text-xs text-slate-400 mb-1"><i class="fas fa-arrow-up text-red-400 mr-1"></i>Total Pengeluaran</p><p class="text-xl font-bold text-red-400"><?= rupiah($total_keluar) ?></p></div>
    <div class="glass rounded-xl p-5"><p class="text-xs text-slate-400 mb-1"><i class="fas fa-balance-scale text-blue-400 mr-1"></i>Selisih</p><p class="text-xl font-bold <?= $selisih>=0?'text-emerald-400':'text-red-400' ?>"><?= rupiah($selisih) ?></p></div>
</div>

<!-- Pemasukan SPP -->
<div class="glass rounded-xl p-5 mb-4">
    <h4 class="font-semibold text-sm mb-3"><i class="fas fa-coins text-emerald-400 mr-2"></i>Pemasukan SPP</h4>
    <div class="table-container"><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">Waktu</th><th class="pb-3">Nama</th><th class="pb-3">Jenis</th><th class="pb-3">Jumlah</th></tr></thead>
        <tbody>
        <?php if ($data_masuk): foreach ($data_masuk as $r): ?>
        <tr class="border-b border-white/5"><td class="py-2"><?= date('H:i', strtotime($r['tanggal_bayar'])) ?></td><td class="font-medium"><?= clean($r['nama']) ?></td><td><?= clean($r['nama_jenis']) ?></td><td class="text-emerald-400 font-medium"><?= rupiah($r['jumlah_bayar']) ?></td></tr>
        <?php endforeach; else: ?><tr><td colspan="4" class="py-4 text-center text-slate-500">Tidak ada pemasukan SPP</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>

<?php if ($data_penerimaan): ?>
<div class="glass rounded-xl p-5 mb-4">
    <h4 class="font-semibold text-sm mb-3"><i class="fas fa-sign-in-alt text-blue-400 mr-2"></i>Penerimaan Kas</h4>
    <div class="table-container"><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">Jenis</th><th class="pb-3">Uraian</th><th class="pb-3">Jumlah</th></tr></thead>
        <tbody><?php foreach ($data_penerimaan as $r): ?>
        <tr class="border-b border-white/5"><td class="py-2"><?= clean($r['jenis'] ?? '-') ?></td><td><?= clean($r['uraian']) ?></td><td class="text-emerald-400 font-medium"><?= rupiah($r['jumlah']) ?></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
</div>
<?php endif; ?>

<!-- Pengeluaran -->
<div class="glass rounded-xl p-5">
    <h4 class="font-semibold text-sm mb-3"><i class="fas fa-arrow-up text-red-400 mr-2"></i>Pengeluaran</h4>
    <div class="table-container"><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">Jenis</th><th class="pb-3">Uraian</th><th class="pb-3">Jumlah</th></tr></thead>
        <tbody>
        <?php if ($data_keluar): foreach ($data_keluar as $r): ?>
        <tr class="border-b border-white/5"><td class="py-2"><?= clean($r['jenis'] ?? '-') ?></td><td><?= clean($r['uraian']) ?></td><td class="text-red-400 font-medium"><?= rupiah($r['jumlah']) ?></td></tr>
        <?php endforeach; else: ?><tr><td colspan="3" class="py-4 text-center text-slate-500">Tidak ada pengeluaran</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
