<?php
$page_title = 'Perpustakaan Digital';
require_once __DIR__ . '/../config/init.php';
cek_fitur('perpustakaan');
// Allows both siswa and guru
if (!in_array($_SESSION['role'], ['siswa', 'guru'])) { header('Location: ../login.php'); exit; }

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$search = $_GET['q'] ?? '';
$cat_filter = $_GET['cat'] ?? '';

$categories = $pdo->query("SELECT * FROM tbl_lib_kategori ORDER BY nama_kategori")->fetchAll();

$query = "SELECT b.*, k.nama_kategori FROM tbl_lib_buku b LEFT JOIN tbl_lib_kategori k ON b.id_kategori = k.id WHERE 1=1";
$params = [];
if ($search) { $query .= " AND (b.judul LIKE ? OR b.penulis LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%"]); }
if ($cat_filter) { $query .= " AND b.id_kategori = ?"; $params[] = $cat_filter; }
$query .= " ORDER BY b.id DESC";

$data = $pdo->prepare($query); $data->execute($params); $data = $data->fetchAll();

// Get My Loans
$loans = $pdo->prepare("SELECT p.*, b.judul, b.cover FROM tbl_lib_pinjam p JOIN tbl_lib_buku b ON p.id_buku=b.id WHERE p.user_id=? AND p.user_role=? ORDER BY p.id DESC");
$loans->execute([$user_id, $role]); $my_loans = $loans->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex flex-col lg:flex-row justify-between items-start mb-8 gap-4">
    <div>
        <h2 class="text-2xl font-black italic tracking-tighter uppercase"><i class="fas fa-book-reader mr-3 text-blue-500"></i>Perpustakaan Sekolah</h2>
        <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">E-Book Digital & Katalog Buku Fisik</p>
    </div>
    <form method="GET" class="flex gap-2 w-full lg:w-auto">
        <div class="relative flex-1 lg:w-64">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
            <input type="text" name="q" value="<?= clean($search) ?>" placeholder="Cari buku..." class="w-full bg-slate-800/50 border border-white/10 rounded-xl py-2.5 pl-10 pr-4 text-xs">
        </div>
        <select name="cat" onchange="this.form.submit()" class="bg-slate-800/50 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-slate-400">
            <option value="">Semua Kategori</option>
            <?php foreach($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $cat_filter==$c['id']?'selected':'' ?>><?= clean($c['nama_kategori']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
    <!-- Catalog Grid -->
    <div class="lg:col-span-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($data)): ?>
                <div class="col-span-3 glass rounded-3xl p-20 text-center opacity-30">
                    <i class="fas fa-search text-5xl mb-4 block"></i>
                    <p class="font-bold tracking-widest uppercase">Buku tidak ditemukan</p>
                </div>
            <?php else: foreach($data as $b): ?>
                <div class="group glass rounded-3xl overflow-hidden border border-white/5 hover:border-blue-500/30 transition-all duration-500 flex flex-col h-full bg-slate-900/20">
                    <div class="relative h-60 overflow-hidden">
                        <img src="<?= BASE_URL ?>assets/uploads/lib_covers/<?= $b['cover'] ?>" 
                            onerror="this.onerror=null;this.src='<?= BASE_URL ?>assets/uploads/lib_covers/default_book.png';" 
                            class="w-full h-full object-cover transition-all duration-700 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4">
                            <?php if (strpos($b['is_digital'], 'E-Book') !== false && !empty($b['file_ebook'])): ?>
                                <a href="<?= BASE_URL ?>assets/uploads/lib_ebooks/<?= $b['file_ebook'] ?>" target="_blank" class="w-full bg-blue-600 hover:bg-blue-500 text-white py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest text-center transition-all shadow-xl shadow-blue-900/40">
                                    <i class="fas fa-book-open mr-2"></i>Baca Online
                                </a>
                            <?php else: ?>
                                <button class="w-full bg-white/10 backdrop-blur-md text-white py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest text-center cursor-default">
                                    <i class="fas fa-map-marker-alt mr-2"></i>Tersedia di Rak
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="absolute top-4 left-4">
                            <span class="px-3 py-1 bg-black/60 backdrop-blur-md border border-white/10 rounded-lg text-[9px] font-black tracking-widest uppercase text-white"><?= clean($b['nama_kategori']) ?></span>
                        </div>
                    </div>
                    <div class="p-5 flex-1 flex flex-col">
                        <h4 class="font-bold text-base leading-tight mb-1 group-hover:text-blue-400"><?= clean($b['judul']) ?></h4>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-4 italic"><?= clean($b['penulis']) ?></p>
                        
                        <div class="mt-auto flex items-center justify-between pt-4 border-t border-white/5">
                            <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Tipe: <?= clean($b['is_digital']) ?></span>
                            <?php if (strpos($b['is_digital'], 'E-Book') !== false): ?>
                                <i class="fas fa-file-pdf text-blue-500"></i>
                            <?php else: ?>
                                <i class="fas fa-book text-slate-600"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- My Borrowing Status -->
    <div class="lg:col-span-1">
        <div class="sticky top-6">
            <div class="glass rounded-[2rem] p-6 border border-white/5 bg-gradient-to-br from-blue-600/10 to-transparent">
                <h4 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6 flex items-center"><i class="fas fa-history mr-2 text-blue-500"></i>Pinjaman Saya</h4>
                
                <div class="space-y-4">
                    <?php if (empty($my_loans)): ?>
                        <div class="text-center py-10 opacity-30 text-slate-500">
                            <i class="fas fa-book mb-2 text-2xl block"></i>
                            <p class="text-[9px] font-bold uppercase tracking-widest">Belum ada riwayat</p>
                        </div>
                    <?php else: foreach($my_loans as $ml): ?>
                        <div class="flex gap-3 p-3 bg-white/5 rounded-2xl border border-white/5 group hover:bg-white/10 transition-all">
                            <img src="<?= BASE_URL ?>assets/uploads/lib_covers/<?= $ml['cover'] ?>" 
                                onerror="this.onerror=null;this.src='<?= BASE_URL ?>assets/uploads/lib_covers/default_book.png';" 
                                class="w-10 h-14 object-cover rounded-lg shadow-lg">
                            <div class="flex-1 min-w-0">
                                <h5 class="text-[11px] font-bold text-white truncate"><?= clean($ml['judul']) ?></h5>
                                <p class="text-[9px] font-bold mt-1 <?= $ml['status'] == 'Pinjam' ? 'text-blue-400' : 'text-emerald-500' ?> uppercase tracking-widest"><?= $ml['status'] ?></p>
                                <p class="text-[8px] text-slate-500 mt-0.5"><?= $ml['status']=='Pinjam' ? 'Tempo: '.tgl_indo($ml['tgl_kembali_rencana']) : 'Dikembalikan' ?></p>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <div class="mt-8 pt-6 border-t border-white/5">
                    <div class="p-4 bg-amber-500/10 rounded-2xl border border-amber-500/20">
                        <p class="text-[10px] text-amber-500 font-bold uppercase tracking-widest mb-2"><i class="fas fa-info-circle mr-1"></i>Ingat!</p>
                        <p class="text-[9px] text-slate-400 leading-relaxed italic">Tap kartu RFID Anda di Perpustakaan saat meminjam buku fisik untuk pencatatan otomatis.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
