<?php
$page_title = 'Koleksi Buku';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin', 'petugas']);
cek_fitur('perpustakaan');

// Ensure directories exist
$dir_cover = __DIR__ . '/../assets/uploads/lib_covers';
$dir_ebook = __DIR__ . '/../assets/uploads/lib_ebooks';
if (!is_dir($dir_cover)) mkdir($dir_cover, 0777, true);
if (!is_dir($dir_ebook)) mkdir($dir_ebook, 0777, true);

if (isset($_POST['simpan'])) {
    $cover = upload_file('cover', 'assets/uploads/lib_covers', ['jpg','jpeg','png']) ?? 'default_book.png';
    $ebook = upload_file('file_ebook', 'assets/uploads/lib_ebooks', ['pdf','epub']);
    
    $sql = "INSERT INTO tbl_lib_buku (id_kategori, judul, penulis, penerbit, tahun, isbn, ringkasan, cover, file_ebook, stok, is_digital) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([
        $_POST['id_kategori'], $_POST['judul'], $_POST['penulis'], $_POST['penerbit'], 
        $_POST['tahun'], $_POST['isbn'], $_POST['ringkasan'], $cover, $ebook, 
        $_POST['stok'], $_POST['is_digital']
    ]);
    
    flash('msg', 'Buku berhasil ditambahkan!');
    header('Location: lib_buku.php'); exit;
}

if (isset($_POST['update'])) {
    $sql = "UPDATE tbl_lib_buku SET id_kategori=?, judul=?, penulis=?, penerbit=?, tahun=?, isbn=?, ringkasan=?, stok=?, is_digital=?";
    $params = [
        $_POST['id_kategori'], $_POST['judul'], $_POST['penulis'], $_POST['penerbit'], 
        $_POST['tahun'], $_POST['isbn'], $_POST['ringkasan'], $_POST['stok'], $_POST['is_digital']
    ];
    
    $c = upload_file('cover', 'assets/uploads/lib_covers', ['jpg','jpeg','png']);
    if ($c) { $sql .= ", cover=?"; $params[] = $c; }
    
    $e = upload_file('file_ebook', 'assets/uploads/lib_ebooks', ['pdf','epub']);
    if ($e) { $sql .= ", file_ebook=?"; $params[] = $e; }
    
    $sql .= " WHERE id=?"; $params[] = $_POST['id'];
    $pdo->prepare($sql)->execute($params);
    
    flash('msg', 'Berhasil diupdate!');
    header('Location: lib_buku.php'); exit;
}

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_lib_buku WHERE id=?")->execute([$_GET['hapus']]);
    flash('msg', 'Buku dihapus!', 'warning');
    header('Location: lib_buku.php'); exit;
}

$search = $_GET['q'] ?? '';
$cat_filter = $_GET['cat'] ?? '';

$query = "SELECT b.*, k.nama_kategori FROM tbl_lib_buku b LEFT JOIN tbl_lib_kategori k ON b.id_kategori = k.id WHERE 1=1";
$params = [];
if ($search) { $query .= " AND (b.judul LIKE ? OR b.penulis LIKE ? OR b.isbn LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]); }
if ($cat_filter) { $query .= " AND b.id_kategori = ?"; $params[] = $cat_filter; }
$query .= " ORDER BY b.id DESC";

$data = $pdo->prepare($query); $data->execute($params); $data = $data->fetchAll();
$categories = $pdo->query("SELECT * FROM tbl_lib_kategori ORDER BY nama_kategori")->fetchAll();

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_lib_buku WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch();
}

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex flex-col md:flex-row items-center justify-between mb-8 gap-4">
    <div>
        <h2 class="text-2xl font-black italic tracking-tighter uppercase text-white"><i class="fas fa-book-open mr-2 text-blue-500"></i>Koleksi Perpustakaan</h2>
        <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Kelola data buku fisik dan digital sekolah</p>
    </div>
    <div class="flex gap-2 w-full md:w-auto">
        <button onclick="document.getElementById('form-buku').classList.toggle('hidden')" class="flex-1 md:flex-none bg-blue-600 hover:bg-blue-500 px-6 py-3 rounded-2xl text-sm font-bold shadow-lg shadow-blue-600/20 transition-all text-white">
            <i class="fas fa-plus mr-2"></i><?= $edit ? 'Edit' : 'Tambah' ?> Koleksi
        </button>
    </div>
</div>

<?= alert_flash('msg') ?>

<!-- Form Section -->
<div id="form-buku" class="<?= $edit ? '' : 'hidden' ?> glass rounded-3xl p-8 mb-10 border border-white/5 relative overflow-hidden">
    <!-- Decor -->
    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-600/10 rounded-full blur-3xl -mr-16 -mt-16"></div>
    
    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
        
        <div class="md:col-span-2 space-y-4">
            <div>
                <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Judul Buku</label>
                <input type="text" name="judul" value="<?= clean($edit['judul'] ?? '') ?>" required class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-blue-500 transition-all">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Penulis</label>
                    <input type="text" name="penulis" value="<?= clean($edit['penulis'] ?? '') ?>" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Penerbit</label>
                    <input type="text" name="penerbit" value="<?= clean($edit['penerbit'] ?? '') ?>" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Tahun</label>
                    <input type="number" name="tahun" value="<?= clean($edit['tahun'] ?? date('Y')) ?>" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm font-mono">
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">ISBN</label>
                    <input type="text" name="isbn" value="<?= clean($edit['isbn'] ?? '') ?>" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm font-mono">
                </div>
            </div>
        </div>

        <div class="md:col-span-1 space-y-4">
            <div>
                <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Kategori</label>
                <select name="id_kategori" required class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-blue-500">
                    <option value="">Pilih Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($edit['id_kategori']??'')==$cat['id'] ? 'selected' : '' ?>><?= clean($cat['nama_kategori']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Stok Fisik</label>
                <input type="number" name="stok" value="<?= clean($edit['stok'] ?? 0) ?>" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm font-bold">
            </div>
            <div>
                <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Tipe Koleksi</label>
                <div class="flex flex-col gap-2 mt-2">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="radio" name="is_digital" value="Fisik" <?= ($edit['is_digital']??'Fisik')=='Fisik' ? 'checked' : '' ?> class="hidden peer">
                        <div class="w-5 h-5 rounded-full border-2 border-slate-700 peer-checked:border-blue-500 peer-checked:bg-blue-600 transition-all"></div>
                        <span class="text-xs font-bold text-slate-400 peer-checked:text-white">Buku Fisik</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="radio" name="is_digital" value="E-Book" <?= ($edit['is_digital']??'')=='E-Book' ? 'checked' : '' ?> class="hidden peer">
                        <div class="w-5 h-5 rounded-full border-2 border-slate-700 peer-checked:border-blue-500 peer-checked:bg-blue-600 transition-all"></div>
                        <span class="text-xs font-bold text-slate-400 peer-checked:text-white">E-Book Digital</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="radio" name="is_digital" value="E-Book & Fisik" <?= ($edit['is_digital']??'')=='E-Book & Fisik' ? 'checked' : '' ?> class="hidden peer">
                        <div class="w-5 h-5 rounded-full border-2 border-slate-700 peer-checked:border-blue-500 peer-checked:bg-blue-600 transition-all"></div>
                        <span class="text-xs font-bold text-slate-400 peer-checked:text-white">E-Book & Fisik</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="md:col-span-1 space-y-4">
            <div>
                <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Cover (JPG/PNG)</label>
                <input type="file" name="cover" accept="image/*" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-2 py-1.5 text-[10px]">
            </div>
            <div>
                <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">File E-Book (PDF)</label>
                <input type="file" name="file_ebook" accept=".pdf" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-2 py-1.5 text-[10px]">
            </div>
            <div class="pt-2">
                <button type="submit" name="<?= $edit ? 'update' : 'simpan' ?>" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 py-4 rounded-2xl text-xs font-black uppercase tracking-widest text-white shadow-xl shadow-blue-600/30 transition-all hover:-translate-y-1">
                    <i class="fas fa-save mr-2"></i><?= $edit ? 'Update Koleksi' : 'Simpan Koleksi' ?>
                </button>
            </div>
        </div>

        <div class="md:col-span-4">
            <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Ringkasan / Deskripsi Buku</label>
            <textarea name="ringkasan" rows="3" class="w-full bg-slate-900/50 border border-white/10 rounded-2xl px-4 py-3 text-sm focus:border-blue-500 transition-all"><?= clean($edit['ringkasan'] ?? '') ?></textarea>
        </div>
    </form>
</div>

<!-- List Section -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="md:col-span-1 space-y-6">
        <!-- Search -->
        <div class="glass rounded-3xl p-6 border border-white/5">
            <h4 class="text-xs font-black uppercase tracking-widest text-slate-500 mb-4"><i class="fas fa-search mr-2"></i>Filter & Cari</h4>
            <form method="GET" class="space-y-4">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                    <input type="text" name="q" value="<?= clean($search) ?>" placeholder="Cari judul/penulis..." class="w-full bg-slate-800/30 border border-white/10 rounded-xl py-2.5 pl-10 pr-4 text-xs">
                </div>
                <select name="cat" class="w-full bg-slate-800/30 border border-white/10 rounded-xl py-2.5 px-3 text-xs">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat_filter==$cat['id'] ? 'selected' : '' ?>><?= clean($cat['nama_kategori']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="w-full bg-slate-700 hover:bg-slate-600 py-2.5 rounded-xl text-xs font-bold transition-all">Terapkan Filter</button>
                <?php if ($search || $cat_filter): ?>
                <a href="lib_buku.php" class="block text-center text-[10px] text-slate-500 hover:text-red-400">Hapus Semua Filter</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Summary Info -->
        <div class="glass rounded-3xl p-6 border border-white/5 bg-gradient-to-br from-blue-500/5 to-transparent">
            <h4 class="text-xs font-black uppercase tracking-widest text-slate-500 mb-4">Statistik</h4>
            <div class="space-y-4">
                <div class="flex justify-between items-center bg-white/5 p-3 rounded-2xl border border-white/5">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Koleksi</span>
                    <span class="text-lg font-black italic"><?= count($data) ?></span>
                </div>
                <div class="flex justify-between items-center bg-white/5 p-3 rounded-2xl border border-white/5">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Digital (PDF)</span>
                    <span class="text-lg font-black italic text-blue-400"><?= $pdo->query("SELECT COUNT(*) FROM tbl_lib_buku WHERE is_digital='Y'")->fetchColumn() ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Books Grid -->
    <div class="md:col-span-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($data)): ?>
                <div class="col-span-3 glass rounded-3xl p-20 text-center opacity-30 border-2 border-dashed border-white/10">
                    <i class="fas fa-book-dead text-6xl mb-4 block"></i>
                    <p class="font-bold tracking-widest uppercase">Belum ada buku ditemukan</p>
                </div>
            <?php else: foreach ($data as $r): ?>
                <div class="group h-full flex flex-col glass rounded-3xl overflow-hidden border border-white/5 hover:border-blue-500/30 transition-all duration-500">
                    <div class="relative h-64 overflow-hidden">
                        <img src="<?= BASE_URL ?>assets/uploads/lib_covers/<?= $r['cover'] ?>" 
                            onerror="this.onerror=null;this.src='<?= BASE_URL ?>assets/uploads/lib_covers/default_book.png';" 
                            class="w-full h-full object-cover transition-all duration-700 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4">
                            <div class="flex gap-2 w-full">
                                <a href="?edit=<?= $r['id'] ?>" class="flex-1 bg-white/10 hover:bg-blue-600 backdrop-blur-md py-2 px-3 rounded-xl text-xs font-black text-center transition-all"><i class="fas fa-edit"></i> Edit</a>
                                <button onclick="confirmDelete('?hapus=<?= $r['id'] ?>')" class="w-10 bg-white/10 hover:bg-red-600 backdrop-blur-md py-2 rounded-xl text-xs transition-all"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="absolute top-4 left-4 flex flex-col gap-2">
                            <span class="px-3 py-1 bg-black/60 backdrop-blur-md border border-white/10 rounded-lg text-[9px] font-black tracking-widest uppercase text-white"><?= clean($r['nama_kategori']) ?></span>
                        </div>
                        <?php if (strpos($r['is_digital'], 'E-Book') !== false): ?>
                        <div class="absolute top-4 right-4 w-7 h-7 bg-blue-500 rounded-lg flex items-center justify-center text-white shadow-lg shadow-blue-500/40">
                            <i class="fas fa-file-pdf text-xs"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-5 flex-1 flex flex-col">
                        <h4 class="font-bold text-white text-base mb-1 group-hover:text-blue-400 transition-colors line-clamp-2"><?= clean($r['judul']) ?></h4>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-3"><?= clean($r['penulis']) ?></p>
                        
                        <div class="mt-auto space-y-3 pt-4 border-t border-white/5">
                            <div class="flex justify-between items-center">
                                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Stok Fisik</span>
                                <span class="text-xs font-black text-white px-2 py-0.5 rounded bg-blue-600/10 border border-blue-500/20"><?= $r['stok'] ?> eks</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Tahun Terbit</span>
                                <span class="text-xs font-mono text-slate-300"><?= $r['tahun'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
