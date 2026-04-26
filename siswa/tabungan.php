<?php
$page_title = 'Tabungan Saya';
require_once __DIR__ . '/../config/init.php';
cek_role(['siswa']);
cek_fitur('tabungan');
$id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT n.* FROM tbl_nasabah n WHERE n.id_siswa=?"); $stmt->execute([$id]); $nasabah = $stmt->fetch();
if ($nasabah) { $stmt = $pdo->prepare("SELECT * FROM tbl_transaksi_tabungan WHERE id_nasabah=? ORDER BY id DESC LIMIT 20"); $stmt->execute([$nasabah['id_nasabah']]); $transaksi = $stmt->fetchAll(); } else { $transaksi = []; }
require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?php if ($nasabah): ?>
<div class="glass rounded-xl p-6 mb-6 text-center">
    <p class="text-xs text-slate-400">No. Rekening: <?= $nasabah['no_rekening'] ?></p>
    <p class="text-3xl font-bold text-emerald-400 mt-2"><?= rupiah($nasabah['saldo']) ?></p>
    <p class="text-xs text-slate-400 mt-1">Saldo Tabungan</p>
</div>
<div class="glass rounded-xl p-5"><h4 class="text-sm font-semibold mb-3">Riwayat</h4>
    <div class="table-container"><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">Tanggal</th><th class="pb-3">Jenis</th><th class="pb-3">Jumlah</th><th class="pb-3">Keterangan</th></tr></thead>
        <tbody><?php foreach ($transaksi as $t): ?>
        <tr class="border-b border-white/5"><td class="py-2"><?= tgl_indo($t['tanggal']) ?></td>
        <td><span class="px-2 py-0.5 rounded-full text-xs <?= $t['jenis']=='Debit'?'bg-emerald-500/20 text-emerald-400':'bg-red-500/20 text-red-400' ?>"><?= $t['jenis']=='Debit'?'Setor':'Tarik' ?></span></td>
        <td class="font-medium <?= $t['jenis']=='Debit'?'text-emerald-400':'text-red-400' ?>"><?= rupiah($t['jumlah']) ?></td><td class="text-slate-400"><?= clean($t['keterangan']) ?></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
</div>
<?php else: ?><div class="glass rounded-xl p-5 text-center text-slate-400">Belum terdaftar sebagai nasabah tabungan.</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../template/footer.php'; ?>
