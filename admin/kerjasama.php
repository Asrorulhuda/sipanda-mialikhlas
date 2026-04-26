<?php
$page_title = 'Kelola Kerjasama & MOU';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);

// Handle Save (Add / Edit)
if (isset($_POST['simpan'])) {
    $id = $_POST['id'] ?? null;
    $nama_instansi = $_POST['nama_instansi'];
    $website = $_POST['website'];
    $urutan = (int)$_POST['urutan'];
    
    // Handle Logo Upload
    $logo = upload_file('logo', 'gambar', ['jpg', 'jpeg', 'png', 'svg']);

    if ($id) {
        // UPDATE
        $sql = "UPDATE tbl_kerjasama SET nama_instansi=?, website=?, urutan=? WHERE id=?";
        $params = [$nama_instansi, $website, $urutan, $id];
        if ($logo) {
            $sql = "UPDATE tbl_kerjasama SET nama_instansi=?, website=?, urutan=?, logo=? WHERE id=?";
            $params = [$nama_instansi, $website, $urutan, $logo, $id];
        }
        $pdo->prepare($sql)->execute($params);
        flash('msg', 'Data kerjasama berhasil diperbarui!');
    } else {
        // INSERT
        $pdo->prepare("INSERT INTO tbl_kerjasama (nama_instansi, logo, website, urutan, status) VALUES (?,?,?,?,?)")
            ->execute([$nama_instansi, $logo, $website, $urutan, 'aktif']);
        flash('msg', 'Mitra kerjasama baru berhasil ditambahkan!');
    }
    header('Location: kerjasama.php');
    exit;
}

// Handle Delete
if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_kerjasama WHERE id=?")->execute([$_GET['hapus']]);
    flash('msg', 'Data kerjasama berhasil dihapus!', 'warning');
    header('Location: kerjasama.php');
    exit;
}

// Handle Toggle Status
if (isset($_GET['toggle'])) {
    $stmt_t = $pdo->prepare("SELECT status FROM tbl_kerjasama WHERE id=?");
    $stmt_t->execute([(int)$_GET['toggle']]);
    $d = $stmt_t->fetchColumn();
    $pdo->prepare("UPDATE tbl_kerjasama SET status=? WHERE id=?")->execute([$d == 'aktif' ? 'nonaktif' : 'aktif', (int)$_GET['toggle']]);
    header('Location: kerjasama.php');
    exit;
}

// Fetch Edit Data
$edit = null;
if (isset($_GET['edit'])) {
    $stmt_e = $pdo->prepare("SELECT * FROM tbl_kerjasama WHERE id=?");
    $stmt_e->execute([$_GET['edit']]);
    $edit = $stmt_e->fetch();
}

$data = $pdo->query("SELECT * FROM tbl_kerjasama ORDER BY urutan ASC, id DESC")->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<?= alert_flash('msg') ?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold flex items-center gap-2"><i class="fas fa-handshake text-blue-400"></i> Kelola Kerjasama & MOU</h2>
    <button onclick="document.getElementById('frm').classList.toggle('hidden')" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 shadow-lg shadow-blue-600/20">
        <i class="fas fa-<?= $edit ? 'times' : 'plus' ?>"></i>
        <span><?= $edit ? 'Batal Edit' : 'Tambah Mitra' ?></span>
    </button>
</div>

<!-- Form Tambah / Edit -->
<div id="frm" class="<?= $edit ? '' : 'hidden' ?> glass rounded-2xl p-6 mb-8 border border-white/5">
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Nama Instansi</label>
                <input type="text" name="nama_instansi" required value="<?= clean($edit['nama_instansi'] ?? '') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-blue-500 outline-none transition-all" placeholder="Contoh: Universitas Indonesia / PT. Maju Jaya">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Website Mitra (Opsional)</label>
                <input type="url" name="website" value="<?= clean($edit['website'] ?? '') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-blue-500 outline-none transition-all" placeholder="https://...">
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Urutan Tampil</label>
                <input type="number" name="urutan" value="<?= $edit['urutan'] ?? '0' ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-xl px-4 py-2.5 text-sm">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Logo Instansi</label>
                <input type="file" name="logo" accept="image/*" class="w-full bg-slate-800/50 border border-white/10 rounded-xl px-4 py-1.5 text-sm file:bg-blue-600 file:border-none file:text-white file:px-3 file:py-1 file:rounded-md file:text-xs file:mr-4 file:cursor-pointer">
            </div>
            <div class="flex items-end">
                <button type="submit" name="simpan" class="w-full bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl text-sm font-bold shadow-xl shadow-blue-600/20 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i>
                    <span>Simpan</span>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- List Grid -->
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
    <?php foreach ($data as $d): ?>
    <div class="glass rounded-2xl p-4 border border-white/5 hover:border-blue-500/30 transition-all group text-center relative">
        <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <a href="?edit=<?= $d['id'] ?>" class="w-6 h-6 rounded-lg bg-amber-500 text-white flex items-center justify-center"><i class="fas fa-edit text-[10px]"></i></a>
            <button onclick="confirmDelete('?hapus=<?= $d['id'] ?>')" class="w-6 h-6 rounded-lg bg-red-500 text-white flex items-center justify-center"><i class="fas fa-trash text-[10px]"></i></button>
        </div>
        
        <div class="h-20 flex items-center justify-center mb-3">
            <img src="<?= BASE_URL ?>gambar/<?= $d['logo'] ?>" class="max-h-full max-w-full object-contain filter grayscale group-hover:grayscale-0 transition-all">
        </div>
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 line-clamp-1"><?= clean($d['nama_instansi']) ?></p>
        
        <div class="mt-2">
            <a href="?toggle=<?= $d['id'] ?>" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[8px] font-bold <?= $d['status'] == 'aktif' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-700 text-slate-500' ?>">
                <i class="fas fa-circle text-[6px]"></i> <?= strtoupper($d['status']) ?>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
