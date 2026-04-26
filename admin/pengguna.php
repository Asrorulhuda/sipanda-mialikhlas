<?php
$page_title = 'Pengguna';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);

if (isset($_POST['simpan'])) {
    $pw = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $foto = upload_file('foto', 'gambar', ['jpg','jpeg','png']) ?? 'default.png';
    $pdo->prepare("INSERT INTO tbl_admin (username,password,nama,foto,level) VALUES (?,?,?,?,?)")->execute([$_POST['username'],$pw,$_POST['nama'],$foto,$_POST['level']]);
    flash('msg','Pengguna ditambahkan!'); header('Location: pengguna.php'); exit;
}
if (isset($_POST['update'])) {
    $sql = "UPDATE tbl_admin SET username=?,nama=?,level=?"; $params = [$_POST['username'],$_POST['nama'],$_POST['level']];
    $f = upload_file('foto','gambar',['jpg','jpeg','png']); if ($f) { $sql .= ",foto=?"; $params[] = $f; }
    if (!empty($_POST['password'])) { $sql .= ",password=?"; $params[] = password_hash($_POST['password'],PASSWORD_DEFAULT); }
    $sql .= " WHERE id_admin=?"; $params[] = $_POST['id'];
    $pdo->prepare($sql)->execute($params); flash('msg','Berhasil!'); header('Location: pengguna.php'); exit;
}
if (isset($_GET['hapus'])) { $pdo->prepare("DELETE FROM tbl_admin WHERE id_admin=?")->execute([$_GET['hapus']]); flash('msg','Dihapus!','warning'); header('Location: pengguna.php'); exit; }

$data = $pdo->query("SELECT * FROM tbl_admin ORDER BY id_admin")->fetchAll();
if (isset($_GET['edit'])) { $stmt = $pdo->prepare("SELECT * FROM tbl_admin WHERE id_admin=?"); $stmt->execute([(int)$_GET['edit']]); $edit = $stmt->fetch(); } else { $edit = null; }

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<button onclick="document.getElementById('frm').classList.toggle('hidden')" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium mb-4"><i class="fas fa-plus mr-1"></i><?= $edit?'Edit':'Tambah' ?></button>
<div id="frm" class="<?= $edit?'':'hidden' ?> glass rounded-xl p-5 mb-6">
    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id_admin'] ?>"><?php endif; ?>
        <div><label class="block text-xs text-slate-400 mb-1">Username</label><input type="text" name="username" value="<?= clean($edit['username']??'') ?>" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Nama</label><input type="text" name="nama" value="<?= clean($edit['nama']??'') ?>" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Password</label><input type="password" name="password" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Level</label><select name="level" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
            <option value="admin" <?= ($edit['level']??'')=='admin'?'selected':'' ?>>Admin</option>
            <option value="petugas" <?= ($edit['level']??'')=='petugas'?'selected':'' ?>>Petugas Perpustakaan</option>
            <option value="bendahara" <?= ($edit['level']??'')=='bendahara'?'selected':'' ?>>Bendahara</option>
            <option value="kepsek" <?= ($edit['level']??'')=='kepsek'?'selected':'' ?>>Kepala Sekolah</option>
            <option value="kasir" <?= ($edit['level']??'')=='kasir'?'selected':'' ?>>Kasir Kantin</option>
        </select></div>
        <div><label class="block text-xs text-slate-400 mb-1">Foto</label><input type="file" name="foto" accept="image/*" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-1.5 text-sm"></div>
        <div class="flex items-end gap-2"><button type="submit" name="<?= $edit?'update':'simpan' ?>" class="bg-blue-600 hover:bg-blue-500 px-6 py-2 rounded-lg text-sm font-medium"><i class="fas fa-save mr-1"></i><?= $edit?'Update':'Simpan' ?></button></div>
    </form>
</div>

<div class="glass rounded-xl p-5"><div class="table-container"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">Username</th><th class="pb-3">Nama</th><th class="pb-3">Level</th><th class="pb-3">Aksi</th></tr></thead>
    <tbody><?php foreach ($data as $i => $r): ?>
    <tr class="border-b border-white/5 hover:bg-white/5"><td class="py-2"><?= $i+1 ?></td><td class="font-mono"><?= clean($r['username']) ?></td><td class="font-medium"><?= clean($r['nama']) ?></td>
    <td><span class="px-2 py-0.5 rounded-full text-xs bg-blue-500/20 text-blue-400 capitalize"><?= $r['level'] ?></span></td>
    <td class="flex gap-1"><a href="?edit=<?= $r['id_admin'] ?>" class="p-1.5 rounded bg-blue-600/20 text-blue-400 text-xs"><i class="fas fa-edit"></i></a><button onclick="confirmDelete('?hapus=<?= $r['id_admin'] ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 text-xs"><i class="fas fa-trash"></i></button></td></tr>
    <?php endforeach; ?></tbody>
</table></div></div>
<?php require_once __DIR__ . '/../template/footer.php'; ?>
