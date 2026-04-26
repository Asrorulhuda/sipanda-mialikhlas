<?php
$page_title = 'Data Kelas';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);

if (isset($_POST['simpan'])) {
    $pdo->prepare("INSERT INTO tbl_kelas (nama_kelas, kategori, id_ta) VALUES (?,?,?)")->execute([$_POST['nama'], $_POST['kategori'], $_POST['id_ta']]);
    flash('msg', 'Kelas berhasil ditambahkan!'); header('Location: kelas.php'); exit;
}
if (isset($_POST['update'])) {
    $pdo->prepare("UPDATE tbl_kelas SET nama_kelas=?, kategori=?, id_ta=? WHERE id_kelas=?")->execute([$_POST['nama'], $_POST['kategori'], $_POST['id_ta'], $_POST['id']]);
    flash('msg', 'Berhasil diupdate!'); header('Location: kelas.php'); exit;
}
if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_kelas WHERE id_kelas=?")->execute([$_GET['hapus']]);
    flash('msg', 'Berhasil dihapus!', 'warning'); header('Location: kelas.php'); exit;
}

$ta_list = $pdo->query("SELECT * FROM tbl_tahun_ajaran ORDER BY id_ta DESC")->fetchAll();
$data = $pdo->query("SELECT k.*, t.tahun FROM tbl_kelas k LEFT JOIN tbl_tahun_ajaran t ON k.id_ta=t.id_ta ORDER BY k.nama_kelas")->fetchAll();
if (isset($_GET['edit'])) { $stmt = $pdo->prepare("SELECT * FROM tbl_kelas WHERE id_kelas=?"); $stmt->execute([(int)$_GET['edit']]); $edit = $stmt->fetch(); } else { $edit = null; }

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<div class="glass rounded-xl p-5 mb-6">
    <h3 class="text-sm font-semibold mb-3"><?= $edit ? 'Edit' : 'Tambah' ?> Kelas</h3>
    <form method="POST" class="flex flex-wrap gap-3 items-end">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id_kelas'] ?>"><?php endif; ?>
        <div class="flex-1 min-w-[150px]"><label class="block text-xs text-slate-400 mb-1">Nama Kelas</label>
            <input type="text" name="nama" value="<?= clean($edit['nama_kelas'] ?? '') ?>" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
        </div>
        <div class="min-w-[120px]"><label class="block text-xs text-slate-400 mb-1">Kategori</label>
            <input type="text" name="kategori" value="<?= clean($edit['kategori'] ?? '') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" placeholder="MI">
        </div>
        <div class="min-w-[180px]"><label class="block text-xs text-slate-400 mb-1">Tahun Ajaran</label>
            <select name="id_ta" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                <?php foreach ($ta_list as $t): ?><option value="<?= $t['id_ta'] ?>" <?= ($edit['id_ta']??'')==$t['id_ta']?'selected':'' ?>><?= $t['tahun'] ?></option><?php endforeach; ?>
            </select>
        </div>
        <button type="submit" name="<?= $edit ? 'update' : 'simpan' ?>" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium transition-colors"><i class="fas fa-save mr-1"></i><?= $edit ? 'Update' : 'Simpan' ?></button>
        <?php if ($edit): ?><a href="kelas.php" class="bg-slate-600 hover:bg-slate-500 px-4 py-2 rounded-lg text-sm transition-colors">Batal</a><?php endif; ?>
    </form>
</div>

<div class="glass rounded-xl p-5">
    <div class="table-container"><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">Nama Kelas</th><th class="pb-3">Kategori</th><th class="pb-3">Tahun Ajaran</th><th class="pb-3">Jumlah Siswa</th><th class="pb-3">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($data as $i => $r):
            $stmt_c = $pdo->prepare("SELECT COUNT(*) FROM tbl_siswa WHERE id_kelas=?"); $stmt_c->execute([$r['id_kelas']]); $jml = $stmt_c->fetchColumn(); ?>
        <tr class="border-b border-white/5 hover:bg-white/5">
            <td class="py-2.5"><?= $i+1 ?></td><td class="font-medium"><?= clean($r['nama_kelas']) ?></td><td><?= clean($r['kategori']) ?></td>
            <td class="text-slate-400"><?= $r['tahun'] ?></td>
            <td><span class="px-2 py-0.5 rounded-full text-xs bg-blue-500/20 text-blue-400"><?= $jml ?> siswa</span></td>
            <td class="flex gap-1">
                <a href="?edit=<?= $r['id_kelas'] ?>" class="p-1.5 rounded bg-blue-600/20 text-blue-400 hover:bg-blue-600/40 text-xs"><i class="fas fa-edit"></i></a>
                <button onclick="confirmDelete('?hapus=<?= $r['id_kelas'] ?>','<?= clean($r['nama_kelas']) ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 hover:bg-red-600/40 text-xs"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
