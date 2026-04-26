<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/fungsi.php';

$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
$nama = $setting['nama_sekolah'] ?? 'SIPANDA';

$search = $_GET['q'] ?? '';
$cat_filter = $_GET['cat'] ?? '';

$categories = $pdo->query("SELECT * FROM tbl_lib_kategori ORDER BY nama_kategori")->fetchAll();

$query = "SELECT b.*, k.nama_kategori FROM tbl_lib_buku b LEFT JOIN tbl_lib_kategori k ON b.id_kategori = k.id WHERE 1=1";
$params = [];
if ($search) { $query .= " AND (b.judul LIKE ? OR b.penulis LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%"]); }
if ($cat_filter) { $query .= " AND b.id_kategori = ?"; $params[] = $cat_filter; }
$query .= " ORDER BY b.id DESC";

$data = $pdo->prepare($query); $data->execute($params); $data = $data->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Library - <?= clean($nama) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fafbfd; color: #1e293b; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .glass-nav { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(0, 0, 0, 0.05); }
        .glass-card { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03); }
        .text-gradient { background: linear-gradient(135deg, #0a1628 0%, #1e3a8a 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-bg { background-color: #f8fafc; background-image: radial-gradient(at 10% 20%, rgba(212, 168, 83, 0.05) 0px, transparent 50%), radial-gradient(at 90% 80%, rgba(59, 130, 246, 0.05) 0px, transparent 50%); }
    </style>
</head>
<body class="antialiased overflow-x-hidden">

    <!-- Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass-nav">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <a href="homepage.php" class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white font-bold shadow-lg">L</div>
                    <div class="flex flex-col">
                        <span class="font-bold text-lg text-slate-900">E-Library</span>
                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest leading-none"><?= clean($nama) ?></span>
                    </div>
                </a>
                <div class="flex items-center gap-4">
                    <a href="homepage.php" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition-colors">Beranda</a>
                    <a href="login.php" class="bg-slate-900 text-white px-6 py-2.5 rounded-full text-xs font-bold shadow-lg shadow-slate-900/10 hover:-translate-y-0.5 transition-all">Portal Login</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <header class="pt-32 pb-12 px-4 hero-bg">
        <div class="max-w-7xl mx-auto text-center">
            <h1 class="text-3xl md:text-5xl font-serif font-bold text-slate-900 mb-4 leading-tight">Jendela Dunia di <br><span class="text-gradient">Genggaman Anda</span></h1>
            <p class="text-slate-500 max-w-xl mx-auto mb-8 text-xs md:text-sm leading-relaxed">Ekspresikan minat baca Anda dengan koleksi ribuan buku digital dan fisik kami. Belajar tanpa batas, kapan saja, di mana saja.</p>
            
            <!-- Search & Filter Bar -->
            <div class="max-w-2xl mx-auto glass-card p-2 rounded-[2rem] flex flex-col md:flex-row gap-2 shadow-2xl">
                <form method="GET" class="w-full flex flex-col md:flex-row gap-2">
                    <div class="flex-1 relative">
                        <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="q" value="<?= clean($search) ?>" placeholder="Cari judul buku atau penulis..." class="w-full h-12 bg-white/50 border-0 rounded-[1.5rem] pl-12 pr-4 text-xs focus:ring-2 focus:ring-blue-500/20 transition-all">
                        <?php if($cat_filter): ?>
                        <input type="hidden" name="cat" value="<?= $cat_filter ?>">
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="h-12 bg-slate-900 hover:bg-slate-800 text-white px-8 rounded-[1.5rem] text-xs font-bold shadow-xl transition-all active:scale-95">Cari</button>
                </form>
            </div>
        </div>
    </header>

    <!-- Categories -->
    <section class="py-10 px-4 bg-white/30 backdrop-blur-sm relative overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-10 h-1 text-slate-200 bg-slate-200 rounded-full"></div>
                <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Jelajahi Kategori</h3>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                <!-- Folder ALL -->
                <a href="perpustakaan.php" class="group relative">
                    <div class="relative w-full aspect-[4/3] bg-slate-100 rounded-2xl overflow-hidden transition-all duration-500 group-hover:-translate-y-2 group-hover:shadow-2xl group-hover:shadow-slate-200">
                        <div class="absolute top-0 left-0 w-1/2 h-4 bg-slate-200 rounded-tr-xl"></div>
                        <div class="absolute inset-0 mt-3 bg-white border border-slate-100 rounded-xl p-4 flex flex-col justify-between">
                            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-slate-900 group-hover:text-white transition-colors">
                                <i class="fas fa-th-large text-xs"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 text-xs">Semua</h4>
                                <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">Koleksi</p>
                            </div>
                        </div>
                    </div>
                </a>

                <?php 
                $colors = [
                    ['bg' => 'bg-blue-50', 'tab' => 'bg-blue-100', 'text' => 'text-blue-500', 'btn' => 'group-hover:bg-blue-600', 'shadow' => 'group-hover:shadow-blue-100'],
                    ['bg' => 'bg-emerald-50', 'tab' => 'bg-emerald-100', 'text' => 'text-emerald-500', 'btn' => 'group-hover:bg-emerald-600', 'shadow' => 'group-hover:shadow-emerald-100'],
                    ['bg' => 'bg-rose-50', 'tab' => 'bg-rose-100', 'text' => 'text-rose-500', 'btn' => 'group-hover:bg-rose-600', 'shadow' => 'group-hover:shadow-rose-100'],
                    ['bg' => 'bg-amber-50', 'tab' => 'bg-amber-100', 'text' => 'text-amber-500', 'btn' => 'group-hover:bg-amber-600', 'shadow' => 'group-hover:shadow-amber-100'],
                    ['bg' => 'bg-purple-50', 'tab' => 'bg-purple-100', 'text' => 'text-purple-500', 'btn' => 'group-hover:bg-purple-600', 'shadow' => 'group-hover:shadow-purple-100'],
                    ['bg' => 'bg-teal-50', 'tab' => 'bg-teal-100', 'text' => 'text-teal-500', 'btn' => 'group-hover:bg-teal-600', 'shadow' => 'group-hover:shadow-teal-100'],
                ];
                
                foreach($categories as $i => $c): 
                    $clr = $colors[$i % count($colors)];
                    $count = $pdo->prepare("SELECT COUNT(*) FROM tbl_lib_buku WHERE id_kategori = ?");
                    $count->execute([$c['id']]);
                    $jml = $count->fetchColumn();
                ?>
                <a href="?cat=<?= $c['id'] ?>" class="group relative">
                    <div class="relative w-full aspect-[4/3] <?= $clr['bg'] ?> rounded-2xl overflow-hidden transition-all duration-500 group-hover:-translate-y-2 group-hover:shadow-2xl <?= $clr['shadow'] ?>">
                        <div class="absolute top-0 left-0 w-1/2 h-4 <?= $clr['tab'] ?> rounded-tr-xl"></div>
                        <div class="absolute inset-0 mt-3 bg-white border <?= $clr['bg'] ?> rounded-xl p-4 flex flex-col justify-between">
                            <div class="w-8 h-8 rounded-lg <?= $clr['bg'] ?> flex items-center justify-center <?= $clr['text'] ?> <?= $clr['btn'] ?> group-hover:text-white transition-colors">
                                <i class="fas fa-folder-open text-xs"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-slate-800 text-[11px] truncate" title="<?= clean($c['nama_kategori']) ?>"><?= clean($c['nama_kategori']) ?></h4>
                                <p class="text-[8px] text-slate-400 font-bold uppercase mt-1 tracking-wider"><?= $jml ?> Buku</p>
                            </div>
                        </div>
                    </div>
                    <?php if ($cat_filter == $c['id']): ?>
                    <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-slate-900 rounded-full"></div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Catalog -->
    <main class="py-20 px-4" id="katalog">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-12">
                <div class="flex flex-col">
                    <h3 class="text-sm font-bold uppercase tracking-widest text-slate-400 mb-1">
                        <?= $cat_filter ? 'Kategori: ' . ($pdo->query("SELECT nama_kategori FROM tbl_lib_kategori WHERE id=$cat_filter")->fetchColumn() ?: 'Semua') : 'Koleksi Terbaru' ?>
                    </h3>
                    <p class="text-xs font-bold text-slate-300"><?= count($data) ?> Buku ditemukan</p>
                </div>
                <div class="h-px flex-1 bg-slate-100 mx-8 hidden md:block"></div>
                <div class="flex gap-2">
                    <button class="w-10 h-10 rounded-xl border border-slate-100 flex items-center justify-center text-slate-400 hover:bg-slate-50 transition-colors"><i class="fas fa-th-large text-xs"></i></button>
                    <button class="w-10 h-10 rounded-xl border border-slate-100 flex items-center justify-center text-slate-200 cursor-not-allowed"><i class="fas fa-list text-xs"></i></button>
                </div>
            </div>

            <?php 
            // Group data by category
            $grouped_data = [];
            foreach ($data as $b) {
                $grouped_data[$b['id_kategori']][] = $b;
            }

            // Determine which categories to loop through
            $display_cats = [];
            if ($cat_filter) {
                // If filtering by specific category, only show that if it has matching data
                if (isset($grouped_data[$cat_filter])) {
                    $display_cats[] = ['id' => $cat_filter, 'nama' => ($pdo->query("SELECT nama_kategori FROM tbl_lib_kategori WHERE id=$cat_filter")->fetchColumn() ?: 'Uncategorized')];
                }
            } else {
                // Otherwise show all categories that have matching books
                foreach ($categories as $cat) {
                    if (isset($grouped_data[$cat['id']])) {
                        $display_cats[] = ['id' => $cat['id'], 'nama' => $cat['nama_kategori']];
                    }
                }
                // Handle books with no category if any
                if (isset($grouped_data[''])) {
                    $display_cats[] = ['id' => '', 'nama' => 'Tanpa Kategori'];
                }
            }

            if (empty($display_cats)): ?>
                <div class="py-20 text-center text-slate-400 italic">
                    <i class="fas fa-search-minus text-4xl mb-4 opacity-20 block"></i>
                    Belum ada koleksi yang cocok dengan pencarian Anda...
                </div>
            <?php else: foreach ($display_cats as $cat_info): 
                $cat_id = $cat_info['id'];
                $cat_name = $cat_info['nama'];
                $books = $grouped_data[$cat_id];
            ?>
                <!-- Category Section -->
                <div class="mb-16 last:mb-0">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 flex items-center justify-center">
                            <i class="fas fa-folder-open text-xs"></i>
                        </div>
                        <h4 class="font-black italic uppercase tracking-wider text-slate-800 text-sm"><?= clean($cat_name) ?> <span class="ml-2 text-[10px] font-bold text-slate-400 normal-case not-italic tracking-normal bg-slate-100 px-2 py-0.5 rounded-full"><?= count($books) ?> Koleksi</span></h4>
                        <div class="h-px flex-1 bg-gradient-to-r from-slate-100 to-transparent"></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                        <?php foreach($books as $b): ?>
                        <div class="group bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 border border-slate-100 flex flex-col h-full">
                            <div class="relative h-72 overflow-hidden">
                                <img src="<?= BASE_URL ?>assets/uploads/lib_covers/<?= $b['cover'] ?>" 
                                    onerror="this.onerror=null;this.src='<?= BASE_URL ?>assets/uploads/lib_covers/default_book.png';" 
                                    class="w-full h-full object-cover transition-all duration-700 group-hover:scale-110">
                                <div class="absolute inset-x-0 bottom-0 p-6 bg-gradient-to-t from-black/80 via-black/40 to-transparent flex items-end opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="login.php" class="w-full bg-white text-slate-900 py-3 rounded-2xl text-[10px] font-bold uppercase tracking-widest text-center shadow-xl">Baca Selengkapnya</a>
                                </div>
                                <div class="absolute top-4 left-4">
                                    <span class="px-3 py-1 bg-white/90 backdrop-blur-md rounded-lg text-[9px] font-black tracking-widest uppercase shadow-sm text-slate-600"><?= clean($b['nama_kategori']??'Uncategorized') ?></span>
                                </div>
                            </div>
                            <div class="p-6 flex-1 flex flex-col">
                                <h4 class="font-bold text-slate-900 text-lg leading-tight mb-2 group-hover:text-blue-600 transition-colors"><?= clean($b['judul']) ?></h4>
                                <p class="text-xs text-slate-500 font-medium mb-6"><?= clean($b['penulis']) ?></p>
                                
                                <div class="mt-auto pt-4 border-t border-slate-50 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full <?= $b['is_digital'] == 'E-Book' ? 'bg-blue-400' : ($b['is_digital'] == 'Fisik' ? 'bg-emerald-500' : 'bg-indigo-500') ?>"></div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"><?= clean($b['is_digital']) ?></span>
                                    </div>
                                    <?php if (strpos($b['is_digital'], 'E-Book') !== false): ?>
                                        <span class="text-[9px] font-bold bg-blue-50 text-blue-600 px-2 py-0.5 rounded-md border border-blue-100 uppercase">Digital</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-12 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-400 font-medium">&copy; <?= date('Y') ?> <?= clean($nama) ?> Library Service. All rights reserved.</p>
    </footer>

</body>
</html>
