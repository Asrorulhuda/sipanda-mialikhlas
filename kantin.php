<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/fungsi.php';

$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();

$kategori_filter = $_GET['cat'] ?? '';
$search = $_GET['q'] ?? '';

$sql = "SELECT p.*, g.nama as nama_guru FROM tbl_produk p LEFT JOIN tbl_guru g ON p.id_guru_penjual = g.id_guru WHERE 1=1";
$params = [];

if ($kategori_filter) {
    $sql .= " AND p.kategori = ?";
    $params[] = $kategori_filter;
}
if ($search) {
    $sql .= " AND (p.nama_produk LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}

$sql .= " ORDER BY p.nama_produk ASC";
$produk = $pdo->prepare($sql);
$produk->execute($params);
$items = $produk->fetchAll();

$categories = $pdo->query("SELECT * FROM tbl_kategori_produk ORDER BY nama_kategori ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog E-Kantin — <?= clean($setting['nama_sekolah']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0a1628; color: #f1f5f9; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .text-gradient { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body class="min-h-screen">

    <!-- Header -->
    <header class="sticky top-0 z-50 glass border-b border-white/5 py-4 px-6 md:px-12 flex justify-between items-center group">
        <a href="index.php" class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-orange-500 flex items-center justify-center text-white shadow-lg shadow-orange-500/30 font-bold italic">K</div>
            <div>
                <h1 class="text-lg font-black tracking-tighter uppercase italic leading-none group-hover:text-orange-400 transition-colors">E-KANTIN</h1>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">SIPANDA SMART CAMPUS</p>
            </div>
        </a>
        <div class="flex items-center gap-4">
            <a href="login.php" class="text-xs font-bold px-5 py-2.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl transition-all">MASUK SISTEM</a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-12">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-black mb-4"><span class="text-gradient">Menu Lezat</span> Hari Ini</h2>
            <p class="text-slate-400 max-w-xl mx-auto text-sm leading-relaxed">Cek persediaan stok dan harga terbaru dari kantin sekolah. Gunakan kartu RFID Anda untuk kemudahan pembayaran.</p>
        </div>

        <!-- Filters -->
        <div class="flex flex-col md:flex-row gap-6 mb-12 items-center justify-between">
            <div class="flex gap-2 overflow-x-auto pb-2 w-full md:w-auto no-scrollbar">
                <a href="kantin.php" class="px-5 py-2.5 rounded-full text-xs font-bold transition-all <?= !$kategori_filter ? 'bg-orange-500 text-white' : 'bg-white/5 text-slate-400 hover:bg-white/10' ?>">SEMUA</a>
                <?php foreach($categories as $cat): ?>
                <a href="?cat=<?= urlencode($cat['nama_kategori']) ?>" class="px-5 py-2.5 rounded-full text-xs font-bold transition-all <?= $kategori_filter == $cat['nama_kategori'] ? 'bg-orange-500 text-white' : 'bg-white/5 text-slate-400 hover:bg-white/10' ?> uppercase"><?= clean($cat['nama_kategori']) ?></a>
                <?php endforeach; ?>
            </div>
            
            <form class="relative w-full md:w-80 group">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-orange-400 transition-colors"></i>
                <input type="text" name="q" value="<?= clean($search) ?>" placeholder="Cari menu..." class="w-full bg-white/5 border border-white/10 rounded-2xl pl-12 pr-4 py-3 text-sm focus:border-orange-500 outline-none transition-all">
            </form>
        </div>

        <!-- Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php if (empty($items)): ?>
            <div class="col-span-full py-20 text-center opacity-30">
                <i class="fas fa-cookie-bite text-6xl mb-4"></i>
                <p class="text-lg">Ops! Menu belum tersedia</p>
            </div>
            <?php else: foreach($items as $i): ?>
            <div class="glass rounded-[2rem] overflow-hidden group hover:-translate-y-2 transition-all duration-500 hover:border-orange-500/30">
                <div class="h-56 overflow-hidden relative">
                    <img src="gambar/produk/<?= $i['gambar'] ?: 'default.png' ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                    <div class="absolute top-4 left-4 flex flex-col gap-2">
                        <span class="px-2 py-0.5 bg-orange-600 rounded-md text-[8px] font-black tracking-widest text-white uppercase shadow-lg">
                            <i class="fas fa-store mr-1"></i><?= $i['nama_guru'] ? 'GURU: '.strtoupper($i['nama_guru']) : 'KANTIN SEKOLAH' ?>
                        </span>
                        <span class="w-fit px-3 py-1 bg-black/60 backdrop-blur-md rounded-lg text-[10px] font-bold tracking-widest text-white border border-white/10 uppercase"><?= clean($i['kategori']) ?></span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="font-bold text-lg mb-1 group-hover:text-orange-400 transition-colors"><?= clean($i['nama_produk']) ?></h3>
                    <p class="text-[10px] text-slate-500 font-mono mb-4"><?= clean($i['sku']) ?></p>
                    
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Harga</p>
                            <h4 class="text-xl font-black text-white"><?= rupiah($i['harga']) ?></h4>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1">Stok</p>
                            <span class="px-2 py-1 rounded-md text-[10px] font-bold <?= $i['stok'] <= 5 ? 'bg-red-500/20 text-red-400' : 'bg-emerald-500/20 text-emerald-400' ?>"><?= $i['stok'] ?> Porsi</span>
                        </div>
                    </div>
                </div>
                <div class="p-6 pt-0">
                    <a href="login.php" class="block w-full py-3 bg-white/5 hover:bg-orange-600 rounded-xl text-center text-xs font-black transition-all group-hover:bg-orange-500/10 group-hover:border group-hover:border-orange-500/50">PESAN SEKARANG</a>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </main>

    <footer class="mt-20 py-12 px-6 border-t border-white/5 text-center bg-black/20">
        <p class="text-slate-500 text-xs">&copy; <?= date('Y') ?> <?= clean($setting['nama_sekolah']) ?> — E-Kantin SIPANDA Digital Ecosystem</p>
    </footer>

</body>
</html>
