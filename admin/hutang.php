<?php
$page_title = 'Hutang Piutang';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin','bendahara']);
cek_fitur('keuangan');

if (isset($_POST['simpan'])) { $pdo->prepare("INSERT INTO tbl_hutang (kepada,nominal,sisa,keterangan,tanggal) VALUES (?,?,?,?,?)")->execute([$_POST['kepada'],$_POST['nominal'],$_POST['nominal'],$_POST['ket'],$_POST['tanggal']]); flash('msg','Berhasil!'); header('Location: hutang.php'); exit; }
if (isset($_POST['bayar_angsuran'])) {
    $pdo->prepare("INSERT INTO tbl_angsuran_hutang (id_hutang,jumlah,tanggal,keterangan) VALUES (?,?,?,?)")->execute([$_POST['id_hutang'],$_POST['jumlah'],$_POST['tanggal'],$_POST['ket']]);
    $sisa_stmt = $pdo->prepare("SELECT sisa FROM tbl_hutang WHERE id=?"); $sisa_stmt->execute([(int)$_POST['id_hutang']]); $sisa = $sisa_stmt->fetchColumn();
    $baru = $sisa - $_POST['jumlah'];
    $pdo->prepare("UPDATE tbl_hutang SET sisa=?, status=? WHERE id=?")->execute([$baru, $baru<=0?'Lunas':'Belum', $_POST['id_hutang']]);
    flash('msg','Angsuran berhasil dicatat!'); header('Location: hutang.php'); exit;
}
if (isset($_GET['hapus'])) { $pdo->prepare("DELETE FROM tbl_hutang WHERE id=?")->execute([$_GET['hapus']]); flash('msg','Dihapus!','warning'); header('Location: hutang.php'); exit; }

$data = $pdo->query("SELECT * FROM tbl_hutang ORDER BY id DESC")->fetchAll();
require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<div class="glass rounded-xl p-5 mb-6">
    <h3 class="text-sm font-semibold mb-3">Tambah Hutang</h3>
    <form method="POST" class="flex flex-wrap gap-3 items-end">
        <div><label class="block text-xs text-slate-400 mb-1">Kepada</label><input type="text" name="kepada" required class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Nominal</label><input type="number" name="nominal" required class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm w-40"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Tanggal</label><input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Keterangan</label><input type="text" name="ket" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        <button type="submit" name="simpan" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-save mr-1"></i>Simpan</button>
    </form>
</div>

<?php foreach ($data as $r): $stmt_a = $pdo->prepare("SELECT * FROM tbl_angsuran_hutang WHERE id_hutang=? ORDER BY tanggal DESC"); $stmt_a->execute([$r['id']]); $angsuran = $stmt_a->fetchAll(); ?>
<div class="glass rounded-xl p-5 mb-4">
    <div class="flex items-center justify-between mb-3">
        <div><h4 class="font-semibold"><?= clean($r['kepada']) ?></h4><p class="text-xs text-slate-400"><?= tgl_indo($r['tanggal']) ?> · <?= clean($r['keterangan']) ?></p></div>
        <div class="text-right">
            <p class="text-sm">Total: <span class="font-bold"><?= rupiah($r['nominal']) ?></span></p>
            <p class="text-sm">Sisa: <span class="font-bold <?= $r['sisa']>0?'text-red-400':'text-emerald-400' ?>"><?= rupiah($r['sisa']) ?></span></p>
            <span class="px-2 py-0.5 rounded-full text-xs <?= $r['status']=='Lunas'?'bg-emerald-500/20 text-emerald-400':'bg-amber-500/20 text-amber-400' ?>"><?= $r['status'] ?></span>
        </div>
    </div>
    <?php if ($r['status']!='Lunas'): ?>
    <form method="POST" class="flex flex-wrap gap-2 items-end mb-3 p-3 bg-white/5 rounded-lg">
        <input type="hidden" name="id_hutang" value="<?= $r['id'] ?>">
        <div><label class="block text-xs text-slate-400 mb-1">Jumlah</label><input type="number" name="jumlah" required class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm w-32"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Tanggal</label><input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Ket</label><input type="text" name="ket" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        <button type="submit" name="bayar_angsuran" class="bg-emerald-600 hover:bg-emerald-500 px-3 py-2 rounded-lg text-xs font-medium"><i class="fas fa-plus mr-1"></i>Bayar</button>
    </form>
    <?php endif; ?>
    <?php if ($angsuran): ?>
    <div class="text-xs text-slate-400 space-y-1"><?php foreach ($angsuran as $a): ?><div class="flex justify-between p-1"><span><?= tgl_indo($a['tanggal']) ?></span><span class="text-emerald-400"><?= rupiah($a['jumlah']) ?></span></div><?php endforeach; ?></div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
