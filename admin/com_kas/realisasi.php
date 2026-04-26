<?php
$page_title = 'Realisasi Anggaran';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','bendahara']);
cek_fitur('kas');

if (isset($_POST['simpan'])) { $pdo->prepare("INSERT INTO tbl_realisasi (tanggal,uraian,jenis,jumlah,id_ta) VALUES (?,?,?,?,?)")->execute([$_POST['tanggal'],$_POST['uraian'],$_POST['jenis'],$_POST['jumlah'],get_ta_aktif($pdo)['id_ta']??null]); flash('msg','Berhasil!'); header('Location: realisasi.php'); exit; }
if (isset($_GET['hapus'])) { $pdo->prepare("DELETE FROM tbl_realisasi WHERE id=?")->execute([$_GET['hapus']]); flash('msg','Dihapus!','warning'); header('Location: realisasi.php'); exit; }

$data = $pdo->query("SELECT * FROM tbl_realisasi ORDER BY tanggal DESC")->fetchAll();
require_once __DIR__ . '/../../template/header.php'; require_once __DIR__ . '/../../template/sidebar.php'; require_once __DIR__ . '/../../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<div class="glass rounded-xl p-5 mb-6"><form method="POST" class="flex flex-wrap gap-3 items-end">
    <div><label class="block text-xs text-slate-400 mb-1">Tanggal</label><input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
    <div class="flex-1 min-w-[200px]"><label class="block text-xs text-slate-400 mb-1">Uraian</label><input type="text" name="uraian" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
    <div><label class="block text-xs text-slate-400 mb-1">Jenis</label><select name="jenis" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><option value="Masuk">Masuk</option><option value="Keluar">Keluar</option></select></div>
    <div><label class="block text-xs text-slate-400 mb-1">Jumlah</label><input type="number" name="jumlah" required class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm w-40"></div>
    <button type="submit" name="simpan" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-save mr-1"></i>Simpan</button>
</form></div>
<div class="glass rounded-xl p-5"><div class="table-container"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">Tanggal</th><th class="pb-3">Uraian</th><th class="pb-3">Jenis</th><th class="pb-3">Jumlah</th><th class="pb-3">Aksi</th></tr></thead>
    <tbody><?php foreach ($data as $i => $r): ?>
    <tr class="border-b border-white/5"><td class="py-2"><?= $i+1 ?></td><td><?= tgl_indo($r['tanggal']) ?></td><td><?= clean($r['uraian']) ?></td>
    <td><span class="px-2 py-0.5 rounded-full text-xs <?= $r['jenis']=='Masuk'?'bg-emerald-500/20 text-emerald-400':'bg-red-500/20 text-red-400' ?>"><?= $r['jenis'] ?></span></td>
    <td class="font-medium <?= $r['jenis']=='Masuk'?'text-emerald-400':'text-red-400' ?>"><?= rupiah($r['jumlah']) ?></td>
    <td><button onclick="confirmDelete('?hapus=<?= $r['id'] ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 text-xs"><i class="fas fa-trash"></i></button></td></tr>
    <?php endforeach; ?></tbody>
</table></div></div>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
