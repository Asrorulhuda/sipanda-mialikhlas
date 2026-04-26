<?php
$page_title = 'Kelola Keunggulan (Why Choose Us)';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);

// 1. Update Section Headers
if (isset($_POST['update_headers'])) {
    $stmt = $pdo->prepare("UPDATE tbl_setting SET keunggulan_sub = ?, keunggulan_judul = ? WHERE id = 1");
    $stmt->execute([$_POST['sub'], $_POST['judul']]);
    flash('msg', 'Header keunggulan berhasil diperbarui!');
    header('Location: keunggulan.php'); exit;
}

// 2. CRUD Keunggulan Cards
if (isset($_POST['simpan'])) {
    $stmt = $pdo->prepare("INSERT INTO tbl_keunggulan (judul, deskripsi, ikon, warna, urutan) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['judul'], $_POST['deskripsi'], $_POST['ikon'], $_POST['warna'], $_POST['urutan']]);
    flash('msg', 'Keunggulan berhasil ditambahkan!');
    header('Location: keunggulan.php'); exit;
}

if (isset($_POST['update'])) {
    $stmt = $pdo->prepare("UPDATE tbl_keunggulan SET judul = ?, deskripsi = ?, ikon = ?, warna = ?, urutan = ? WHERE id = ?");
    $stmt->execute([$_POST['judul'], $_POST['deskripsi'], $_POST['ikon'], $_POST['warna'], $_POST['urutan'], $_POST['id']]);
    flash('msg', 'Keunggulan berhasil diperbarui!');
    header('Location: keunggulan.php'); exit;
}

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_keunggulan WHERE id = ?")->execute([$_GET['hapus']]);
    flash('msg', 'Keunggulan dihapus!', 'warning');
    header('Location: keunggulan.php'); exit;
}

$s = $pdo->query("SELECT * FROM tbl_setting WHERE id = 1")->fetch();
$data = $pdo->query("SELECT * FROM tbl_keunggulan ORDER BY urutan ASC")->fetchAll();

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_keunggulan WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
} else {
    $edit = null;
}

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<?= alert_flash('msg') ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left: Header Settings -->
    <div class="lg:col-span-1">
        <div class="glass rounded-xl p-5 border border-white/5">
            <h3 class="text-sm font-bold mb-4 flex items-center gap-2 text-gold-400">
                <i class="fas fa-heading"></i> Pengaturan Header Section
            </h3>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Sub-Title (Label Atas)</label>
                    <input type="text" name="sub" value="<?= clean($s['keunggulan_sub']) ?>" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-gold-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Main Title</label>
                    <textarea name="judul" rows="2" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-gold-500 focus:outline-none"><?= clean($s['keunggulan_judul']) ?></textarea>
                </div>
                <button type="submit" name="update_headers" class="w-full bg-gold-600 hover:bg-gold-500 py-2 rounded-lg text-sm font-bold transition-all mt-2">Update Header</button>
            </form>
        </div>
    </div>

    <!-- Right: CRUD Form & List -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Form -->
        <div class="glass rounded-xl p-5 border border-white/5 <?= $edit?'':'hidden' ?>" id="formArea">
            <h3 class="text-sm font-bold mb-4 flex items-center gap-2 text-blue-400">
                <i class="fas fa-edit"></i> <?= $edit ? 'Edit Keunggulan' : 'Tambah Keunggulan Baru' ?>
            </h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
                <div class="md:col-span-2">
                    <label class="block text-xs text-slate-400 mb-1">Judul Keunggulan</label>
                    <input type="text" name="judul" value="<?= clean($edit['judul']??'') ?>" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs text-slate-400 mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="3" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm"><?= clean($edit['deskripsi']??'') ?></textarea>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Icon FontAwesome (e.g., fas fa-quran)</label>
                    <input type="text" name="ikon" value="<?= clean($edit['ikon']??'fas fa-star') ?>" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Warna Tema (emerald/blue/purple/amber)</label>
                    <select name="warna" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm">
                        <?php foreach(['blue','emerald','purple','amber','pink','indigo'] as $w): ?>
                            <option value="<?= $w ?>" <?= ($edit['warna']??'')==$w?'selected':'' ?>><?= ucfirst($w) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Urutan</label>
                    <input type="number" name="urutan" value="<?= $edit['urutan']??0 ?>" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2 flex gap-2 pt-2">
                    <button type="submit" name="<?= $edit?'update':'simpan' ?>" class="bg-blue-600 hover:bg-blue-500 px-6 py-2 rounded-lg text-sm font-bold">Simpan</button>
                    <?php if($edit): ?><a href="keunggulan.php" class="bg-slate-700 px-6 py-2 rounded-lg text-sm font-bold">Batal</a><?php endif; ?>
                </div>
            </form>
        </div>

        <button onclick="document.getElementById('formArea').classList.toggle('hidden')" class="<?= $edit?'hidden':'' ?> bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium mb-4"><i class="fas fa-plus mr-1"></i>Tambah Keunggulan</button>

        <!-- List -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($data as $d): ?>
            <div class="glass rounded-xl p-5 border border-white/5 relative group hover:border-gold-500/30 transition-all">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-10 h-10 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-<?= $d['warna'] ?>-400 text-lg">
                        <i class="<?= $d['ikon'] ?>"></i>
                    </div>
                    <div class="flex gap-2">
                        <a href="?edit=<?= $d['id'] ?>" class="text-xs text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-edit"></i></a>
                        <button onclick="confirmDelete('?hapus=<?= $d['id'] ?>')" class="text-xs text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <h4 class="font-bold text-slate-200"><?= clean($d['judul']) ?></h4>
                <p class="text-xs text-slate-400 mt-2 leading-relaxed"><?= clean($d['deskripsi']) ?></p>
                <div class="absolute top-2 right-12 text-[10px] text-slate-600 font-mono">#<?= $d['urutan'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
