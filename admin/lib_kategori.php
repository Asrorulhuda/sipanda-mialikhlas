<?php
$page_title = 'Kategori Buku';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin', 'petugas']);
cek_fitur('perpustakaan');

if (isset($_POST['simpan'])) {
    $pdo->prepare("INSERT INTO tbl_lib_kategori (nama_kategori) VALUES (?)")->execute([$_POST['nama_kategori']]);
    flash('msg', 'Kategori ditambahkan!');
    header('Location: lib_kategori.php');
    exit;
}

if (isset($_POST['update'])) {
    $pdo->prepare("UPDATE tbl_lib_kategori SET nama_kategori=? WHERE id=?")->execute([$_POST['nama_kategori'], $_POST['id']]);
    flash('msg', 'Berhasil diupdate!');
    header('Location: lib_kategori.php');
    exit;
}

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_lib_kategori WHERE id=?")->execute([$_GET['hapus']]);
    flash('msg', 'Dihapus!', 'warning');
    header('Location: lib_kategori.php');
    exit;
}

$data = $pdo->query("SELECT * FROM tbl_lib_kategori ORDER BY nama_kategori ASC")->fetchAll();
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_lib_kategori WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch();
}

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold">Manajemen Kategori Buku</h2>
    <button onclick="document.getElementById('form-kategori').classList.toggle('hidden')" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-xl text-sm font-medium transition-all">
        <i class="fas fa-plus mr-2"></i><?= $edit ? 'Edit' : 'Tambah' ?> Kategori
    </button>
</div>

<?= alert_flash('msg') ?>

<div id="form-kategori" class="<?= $edit ? '' : 'hidden' ?> glass rounded-2xl p-6 mb-8 border border-white/5">
    <form method="POST" class="flex flex-col md:flex-row gap-4 items-end">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
        <div class="flex-1 w-full">
            <label class="block text-xs text-slate-400 mb-2 uppercase font-bold tracking-widest">Nama Kategori</label>
            <input type="text" name="nama_kategori" value="<?= clean($edit['nama_kategori'] ?? '') ?>" required 
                class="w-full bg-slate-800/50 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 transition-all"
                placeholder="Contoh: Sains, Novel, Sejarah...">
        </div>
        <div class="flex gap-2">
            <button type="submit" name="<?= $edit ? 'update' : 'simpan' ?>" class="bg-emerald-600 hover:bg-emerald-500 px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-emerald-600/20 transition-all text-white">
                <i class="fas fa-save mr-2"></i>Simpan
            </button>
            <?php if ($edit): ?>
            <a href="lib_kategori.php" class="bg-slate-700 hover:bg-slate-600 px-6 py-2.5 rounded-xl text-sm font-bold transition-all text-white">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="glass rounded-2xl p-6 border border-white/5">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-400 border-b border-white/10">
                    <th class="pb-4 font-bold uppercase tracking-widest text-[10px]">#</th>
                    <th class="pb-4 font-bold uppercase tracking-widest text-[10px]">Nama Kategori</th>
                    <th class="pb-4 font-bold uppercase tracking-widest text-[10px] text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php if (empty($data)): ?>
                <tr>
                    <td colspan="3" class="py-10 text-center text-slate-500 italic">Belum ada data kategori.</td>
                </tr>
                <?php else: foreach ($data as $i => $r): ?>
                <tr class="hover:bg-white/5 transition-colors group">
                    <td class="py-4 text-slate-500"><?= $i + 1 ?></td>
                    <td class="py-4 font-medium"><?= clean($r['nama_kategori']) ?></td>
                    <td class="py-4 text-right">
                        <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="?edit=<?= $r['id'] ?>" class="w-8 h-8 rounded-lg bg-blue-600/20 text-blue-400 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all">
                                <i class="fas fa-edit text-xs"></i>
                            </a>
                            <button onclick="confirmDelete('?hapus=<?= $r['id'] ?>')" class="w-8 h-8 rounded-lg bg-red-600/20 text-red-400 flex items-center justify-center hover:bg-red-600 hover:text-white transition-all">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
