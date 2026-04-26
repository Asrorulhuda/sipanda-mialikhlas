<?php
$page_title = 'Jam Pelajaran';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);

if (isset($_POST['simpan'])) { $pdo->prepare("INSERT INTO tbl_jam (nama_jam,jam_mulai,jam_selesai) VALUES (?,?,?)")->execute([$_POST['nama'],$_POST['mulai'],$_POST['selesai']]); flash('msg','Berhasil!'); header('Location: jam.php'); exit; }
if (isset($_POST['update'])) { $pdo->prepare("UPDATE tbl_jam SET nama_jam=?,jam_mulai=?,jam_selesai=? WHERE id_jam=?")->execute([$_POST['nama'],$_POST['mulai'],$_POST['selesai'],$_POST['id']]); flash('msg','Berhasil!'); header('Location: jam.php'); exit; }
if (isset($_GET['hapus'])) { $pdo->prepare("DELETE FROM tbl_jam WHERE id_jam=?")->execute([$_GET['hapus']]); flash('msg','Dihapus!','warning'); header('Location: jam.php'); exit; }

$data = $pdo->query("SELECT * FROM tbl_jam ORDER BY jam_mulai")->fetchAll();
if (isset($_GET['edit'])) { $stmt = $pdo->prepare("SELECT * FROM tbl_jam WHERE id_jam=?"); $stmt->execute([(int)$_GET['edit']]); $edit = $stmt->fetch(); } else { $edit = null; }

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<div class="glass rounded-xl p-5 mb-6">
    <form method="POST" class="flex flex-wrap gap-3 items-end">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id_jam'] ?>"><?php endif; ?>
        <div><label class="block text-xs text-slate-400 mb-1">Nama Jam</label><input type="text" name="nama" value="<?= clean($edit['nama_jam']??'') ?>" required class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Mulai</label><input type="time" name="mulai" value="<?= $edit['jam_mulai']??'' ?>" required class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Selesai</label><input type="time" name="selesai" value="<?= $edit['jam_selesai']??'' ?>" required class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"></div>
        <button type="submit" name="<?= $edit?'update':'simpan' ?>" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-save mr-1"></i><?= $edit?'Update':'Simpan' ?></button>
    </form>
</div>
<div class="glass rounded-xl p-5"><div class="table-container"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">Nama</th><th class="pb-3">Mulai</th><th class="pb-3">Selesai</th><th class="pb-3">Aksi</th></tr></thead>
    <tbody><?php foreach ($data as $i => $r): ?>
    <tr class="border-b border-white/5 hover:bg-white/5"><td class="py-2"><?= $i+1 ?></td><td><?= clean($r['nama_jam']) ?></td><td class="font-mono"><?= substr($r['jam_mulai'],0,5) ?></td><td class="font-mono"><?= substr($r['jam_selesai'],0,5) ?></td>
    <td class="flex gap-1"><a href="?edit=<?= $r['id_jam'] ?>" class="p-1.5 rounded bg-blue-600/20 text-blue-400 text-xs"><i class="fas fa-edit"></i></a><button onclick="confirmDelete('?hapus=<?= $r['id_jam'] ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 text-xs"><i class="fas fa-trash"></i></button></td></tr>
    <?php endforeach; ?></tbody>
</table></div></div>
<?php require_once __DIR__ . '/../template/footer.php'; ?>
