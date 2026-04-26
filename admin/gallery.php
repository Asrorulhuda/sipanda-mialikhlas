<?php
$page_title = 'Galeri & Banner Sekolah';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);

// Handle Simpan / Tambah
if (isset($_POST['simpan'])) {
    $title = $_POST['title'];
    $type = $_POST['type'];
    $img = upload_file('image', 'assets/uploads/gallery', ['jpg','jpeg','png','gif']);
    
    if ($img) {
        $stmt = $pdo->prepare("INSERT INTO tbl_gallery (title, image, type) VALUES (?, ?, ?)");
        $stmt->execute([$title, $img, $type]);
        flash('msg', 'Gambar berhasil ditambahkan ke galeri!');
    } else {
        flash('msg', 'Gagal mengupload gambar. Pastikan format sesuai.', 'error');
    }
    header('Location: gallery.php');
    exit;
}

// Handle Hapus
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $item = $pdo->prepare("SELECT image FROM tbl_gallery WHERE id = ?");
    $item->execute([$id]);
    $data = $item->fetch();
    
    if ($data) {
        $path = __DIR__ . '/../assets/uploads/gallery/' . $data['image'];
        if (file_exists($path)) unlink($path);
        
        $pdo->prepare("DELETE FROM tbl_gallery WHERE id = ?")->execute([$id]);
        flash('msg', 'Gambar berhasil dihapus!', 'warning');
    }
    header('Location: gallery.php');
    exit;
}

$banners = $pdo->query("SELECT * FROM tbl_gallery WHERE type='banner' ORDER BY id DESC")->fetchAll();
$galeri = $pdo->query("SELECT * FROM tbl_gallery WHERE type='galeri' ORDER BY id DESC")->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-white">Kelola Galeri & Banner</h2>
    <button onclick="document.getElementById('modalAdd').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium transition-all shadow-lg shadow-blue-600/20">
        <i class="fas fa-plus mr-2"></i>Tambah Gambar
    </button>
</div>

<?= alert_flash('msg') ?>

<!-- Banner Section -->
<div class="mb-10">
    <div class="flex items-center gap-2 mb-4">
        <i class="fas fa-image text-blue-400"></i>
        <h3 class="font-bold text-white">Banner Utama (Hero Section)</h3>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($banners as $b): ?>
        <div class="glass rounded-2xl overflow-hidden group">
            <div class="aspect-video relative">
                <img src="<?= BASE_URL ?>assets/uploads/gallery/<?= $b['image'] ?>" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                    <button onclick="confirmDelete('gallery.php?hapus=<?= $b['id'] ?>')" class="w-10 h-10 rounded-full bg-red-500/20 text-red-500 border border-red-500/20 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="p-4">
                <p class="text-sm font-semibold text-white truncate"><?= clean($b['title']) ?></p>
                <p class="text-[10px] text-slate-500 mt-1"><?= tgl_indo($b['created_at']) ?></p>
            </div>
        </div>
        <?php endforeach; if(!$banners): ?>
            <div class="col-span-3 border border-white/5 border-dashed rounded-2xl p-10 text-center">
                <i class="far fa-images text-4xl text-slate-600 mb-3 block"></i>
                <p class="text-sm text-slate-500">Belum ada banner utama. Tambahkan satu untuk mengganti banner default.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Gallery Section -->
<div>
    <div class="flex items-center gap-2 mb-4">
        <i class="fas fa-images text-purple-400"></i>
        <h3 class="font-bold text-white">Galeri Foto Siswa</h3>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php foreach ($galeri as $g): ?>
        <div class="glass rounded-2xl overflow-hidden group">
            <div class="aspect-square relative">
                <img src="<?= BASE_URL ?>assets/uploads/gallery/<?= $g['image'] ?>" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                    <button onclick="confirmDelete('gallery.php?hapus=<?= $g['id'] ?>')" class="w-10 h-10 rounded-full bg-red-500/20 text-red-500 border border-red-500/20 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="p-3">
                <p class="text-xs font-medium text-white truncate"><?= clean($g['title']) ?></p>
            </div>
        </div>
        <?php endforeach; if(!$galeri): ?>
            <div class="col-span-4 border border-white/5 border-dashed rounded-2xl p-10 text-center">
                <p class="text-sm text-slate-500">Galeri foto siswa masih kosong.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Add -->
<div id="modalAdd" class="fixed inset-0 z-50 flex items-center justify-center px-4 hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="glass w-full max-w-md relative z-10 p-6 rounded-2xl border border-white/10 shadow-2xl animate-zoom-in">
        <h3 class="text-lg font-bold text-white mb-4">Upload Gambar Baru</h3>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block text-xs text-slate-400 mb-1">Judul / Keterangan</label>
                <input type="text" name="title" required placeholder="Contoh: Banner PPDB 2024" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Pilih Gambar</label>
                <input type="file" name="image" required accept="image/*" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm text-slate-400 file:bg-blue-600 file:border-0 file:text-white file:px-3 file:py-1 file:rounded file:text-xs file:mr-3 file:cursor-pointer">
                <p class="text-[10px] text-slate-500 mt-1">* Format JPG/PNG, ukuran maks 2MB</p>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Tipe Penempatan</label>
                <select name="type" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                    <option value="galeri">Galeri Foto Siswa</option>
                    <option value="banner">Banner Utama (Hero)</option>
                </select>
            </div>
            <div class="pt-2 flex gap-2">
                <button type="submit" name="simpan" class="flex-1 bg-blue-600 hover:bg-blue-500 py-2.5 rounded-xl text-sm font-bold transition-all">Upload Gambar</button>
                <button type="button" onclick="this.closest('#modalAdd').classList.add('hidden')" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-400 hover:text-white transition-all">Batal</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
