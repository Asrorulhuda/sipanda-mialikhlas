<?php
$page_title = 'Kelola Berita & Informasi';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);

// Handle Save (Add / Edit)
if (isset($_POST['simpan'])) {
    $id = $_POST['id'] ?? null;
    $judul = $_POST['judul'];
    $slug = $_POST['slug'] ?: strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul), '-'));
    $konten = $_POST['konten'];
    $external_link = $_POST['external_link'];
    
    // Handle Image Upload
    $img = upload_file('gambar', 'cms_images', ['jpg', 'jpeg', 'png']);

    if ($id) {
        // UPDATE
        $sql = "UPDATE tbl_display_info SET judul=?, slug=?, konten=?, external_link=? WHERE id=?";
        $params = [$judul, $slug, $konten, $external_link, $id];
        if ($img) {
            $sql = "UPDATE tbl_display_info SET judul=?, slug=?, konten=?, external_link=?, gambar=? WHERE id=?";
            $params = [$judul, $slug, $konten, $external_link, $img, $id];
        }
        $pdo->prepare($sql)->execute($params);
        flash('msg', 'Berita berhasil diperbarui!');
    } else {
        // INSERT
        $pdo->prepare("INSERT INTO tbl_display_info (judul, slug, konten, external_link, gambar, status) VALUES (?,?,?,?,?,?)")
            ->execute([$judul, $slug, $konten, $external_link, $img, 'aktif']);
        flash('msg', 'Berita baru berhasil ditambahkan!');
    }
    header('Location: display_info.php');
    exit;
}

// Handle Delete
if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_display_info WHERE id=?")->execute([$_GET['hapus']]);
    flash('msg', 'Berita berhasil dihapus!', 'warning');
    header('Location: display_info.php');
    exit;
}

// Handle Toggle Status
if (isset($_GET['toggle'])) {
    $stmt_t = $pdo->prepare("SELECT status FROM tbl_display_info WHERE id=?");
    $stmt_t->execute([(int)$_GET['toggle']]);
    $d = $stmt_t->fetchColumn();
    $pdo->prepare("UPDATE tbl_display_info SET status=? WHERE id=?")->execute([$d == 'aktif' ? 'nonaktif' : 'aktif', (int)$_GET['toggle']]);
    header('Location: display_info.php');
    exit;
}

// Fetch Edit Data
$edit = null;
if (isset($_GET['edit'])) {
    $stmt_e = $pdo->prepare("SELECT * FROM tbl_display_info WHERE id=?");
    $stmt_e->execute([$_GET['edit']]);
    $edit = $stmt_e->fetch();
}

$data = $pdo->query("SELECT * FROM tbl_display_info ORDER BY id DESC")->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<!-- TinyMCE CDN -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#editor',
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
    height: 400,
    background_color: '#1e293b',
    skin: 'oxide-dark',
    content_css: 'dark'
  });

  function generateSlug(text) {
    return text.toString().toLowerCase()
      .replace(/\s+/g, '-')           // Replace spaces with -
      .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
      .replace(/\-\-+/g, '-')         // Replace multiple - with single -
      .replace(/^-+/, '')             // Trim - from start of text
      .replace(/-+$/, '');            // Trim - from end of text
  }
</script>

<?= alert_flash('msg') ?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold flex items-center gap-2"><i class="fas fa-newspaper text-blue-400"></i> Manajemen Informasi</h2>
    <button onclick="document.getElementById('frm').classList.toggle('hidden')" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 shadow-lg shadow-blue-600/20">
        <i class="fas fa-<?= $edit ? 'times' : 'plus' ?>"></i>
        <span><?= $edit ? 'Batal Edit' : 'Tambah Berita' ?></span>
    </button>
</div>

<!-- Form Tambah / Edit -->
<div id="frm" class="<?= $edit ? '' : 'hidden' ?> glass rounded-2xl p-6 mb-8 border border-white/5">
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Judul Berita</label>
                <input type="text" name="judul" id="judulInput" onkeyup="document.getElementById('slugInput').value = generateSlug(this.value)" required value="<?= clean($edit['judul'] ?? '') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-blue-500 outline-none transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Slug URL (Otomatis)</label>
                <input type="text" name="slug" id="slugInput" value="<?= clean($edit['slug'] ?? '') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-xl px-4 py-2.5 text-sm font-mono text-blue-300">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Direct Link (Opsional - Jika diisi, 'Baca Selengkapnya' akan diarahkan ke sini)</label>
            <input type="url" name="external_link" placeholder="https://..." value="<?= clean($edit['external_link'] ?? '') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-xl px-4 py-2.5 text-sm">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Konten Berita</label>
            <textarea name="konten" id="editor" class="w-full bg-slate-800/50 border border-white/10 rounded-xl px-4 py-2 text-sm"><?= $edit['konten'] ?? '' ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Gambar Cover</label>
                <input type="file" name="gambar" accept="image/*" class="w-full bg-slate-800/50 border border-white/10 rounded-xl px-4 py-1.5 text-sm file:bg-blue-600 file:border-none file:text-white file:px-3 file:py-1 file:rounded-md file:text-xs file:mr-4 file:cursor-pointer">
                <?php if ($edit && $edit['gambar']): ?>
                    <p class="text-[10px] text-slate-500 mt-1 italic">File saat ini: <?= $edit['gambar'] ?></p>
                <?php endif; ?>
            </div>
            <button type="submit" name="simpan" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl text-sm font-bold shadow-xl shadow-blue-600/20 transition-all flex items-center justify-center gap-2">
                <i class="fas fa-save"></i>
                <span>Simpan Berita</span>
            </button>
        </div>
    </form>
</div>

<!-- List View -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($data as $d): ?>
    <div class="glass rounded-2xl overflow-hidden border border-white/5 hover:border-blue-500/30 transition-all group">
        <div class="relative h-48 bg-slate-800 overflow-hidden">
            <?php if ($d['gambar']): ?>
                <img src="<?= BASE_URL ?>cms_images/<?= $d['gambar'] ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-slate-600 text-4xl"><i class="fas fa-image"></i></div>
            <?php endif; ?>
            <div class="absolute top-4 right-4 flex gap-2">
                <a href="?toggle=<?= $d['id'] ?>" class="w-8 h-8 rounded-lg flex items-center justify-center <?= $d['status'] == 'aktif' ? 'bg-emerald-500 text-white' : 'bg-slate-700 text-slate-400' ?> shadow-lg">
                    <i class="fas fa-power-off text-xs"></i>
                </a>
            </div>
        </div>
        <div class="p-5">
            <div class="flex justify-between items-start mb-3">
                <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-1 rounded bg-blue-500/10 text-blue-400">INFO RESMI</span>
                <span class="text-[10px] text-slate-500"><?= date('d/m/Y', strtotime($d['created_at'])) ?></span>
            </div>
            <h4 class="font-bold text-lg mb-2 line-clamp-2"><?= clean($d['judul']) ?></h4>
            <div class="flex justify-between items-center mt-6">
                <div class="flex gap-2">
                    <a href="?edit=<?= $d['id'] ?>" class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center hover:bg-amber-500 hover:text-white transition-all">
                        <i class="fas fa-edit text-sm"></i>
                    </a>
                    <button onclick="confirmDelete('?hapus=<?= $d['id'] ?>')" class="w-9 h-9 rounded-xl bg-red-500/10 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all">
                        <i class="fas fa-trash text-sm"></i>
                    </button>
                </div>
                <div class="text-[10px] <?= $d['status'] == 'aktif' ? 'text-emerald-400' : 'text-slate-500' ?> font-bold uppercase tracking-widest flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full <?= $d['status'] == 'aktif' ? 'bg-emerald-400' : 'bg-slate-500' ?>"></span>
                    <?= $d['status'] ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
