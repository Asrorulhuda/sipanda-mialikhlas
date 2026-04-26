<?php
$page_title = 'Jenis Kas Keluar';
$tabel = 'tbl_jenis_keluar';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','bendahara']);
cek_fitur('kas');

if (isset($_POST['simpan'])) { $pdo->prepare("INSERT INTO $tabel (nama) VALUES (?)")->execute([$_POST['nama']]); flash('msg','Berhasil!'); header('Location: jenis_keluar.php'); exit; }
if (isset($_POST['update'])) { $pdo->prepare("UPDATE $tabel SET nama=? WHERE id=?")->execute([$_POST['nama'],$_POST['id']]); flash('msg','Berhasil!'); header('Location: jenis_keluar.php'); exit; }
if (isset($_GET['hapus'])) { $pdo->prepare("DELETE FROM $tabel WHERE id=?")->execute([$_GET['hapus']]); flash('msg','Dihapus!','warning'); header('Location: jenis_keluar.php'); exit; }

$data = $pdo->query("SELECT * FROM $tabel ORDER BY id")->fetchAll();
if (isset($_GET['edit'])) { $stmt = $pdo->prepare("SELECT * FROM $tabel WHERE id=?"); $stmt->execute([(int)$_GET['edit']]); $edit = $stmt->fetch(); } else { $edit = null; }
require_once __DIR__ . '/../../template/header.php'; require_once __DIR__ . '/../../template/sidebar.php'; require_once __DIR__ . '/../../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<div class="glass rounded-xl p-5 mb-6"><form method="POST" class="flex gap-3 items-end">
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
    <div class="flex-1"><label class="block text-xs text-slate-400 mb-1">Nama Jenis</label><input type="text" name="nama" value="<?= clean($edit['nama']??'') ?>" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
    <button type="submit" name="<?= $edit?'update':'simpan' ?>" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-save mr-1"></i><?= $edit?'Update':'Simpan' ?></button>
</form></div>
<div class="glass rounded-xl p-5"><div class="table-container"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">Nama</th><th class="pb-3">Aksi</th></tr></thead>
    <tbody><?php foreach ($data as $i => $r): ?>
    <tr class="border-b border-white/5"><td class="py-2"><?= $i+1 ?></td><td><?= clean($r['nama']) ?></td>
    <td class="flex gap-1"><a href="?edit=<?= $r['id'] ?>" class="p-1.5 rounded bg-blue-600/20 text-blue-400 text-xs"><i class="fas fa-edit"></i></a><button onclick="confirmDelete('?hapus=<?= $r['id'] ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 text-xs"><i class="fas fa-trash"></i></button></td></tr>
    <?php endforeach; ?></tbody>
</table></div></div>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
