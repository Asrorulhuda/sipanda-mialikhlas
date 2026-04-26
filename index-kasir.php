<?php
$page_title = 'Dashboard Kasir';
require_once __DIR__ . '/config/init.php';
cek_role(['kasir']);

$today = date('Y-m-d');
$sales_today = $pdo->prepare("SELECT SUM(total) as total FROM tbl_order WHERE DATE(tanggal) = ?");
$sales_today->execute([$today]);
$total_sales = $sales_today->fetch()['total'] ?? 0;

$trx_today = $pdo->prepare("SELECT COUNT(*) as count FROM tbl_order WHERE DATE(tanggal) = ?");
$trx_today->execute([$today]);
$count_trx = $trx_today->fetch()['count'] ?? 0;

$produk_habis = $pdo->query("SELECT COUNT(*) as count FROM tbl_produk WHERE stok <= 5")->fetch()['count'] ?? 0;

$cat_summary = $pdo->query("SELECT kp.nama_kategori, COUNT(p.id_produk) as total 
    FROM tbl_kategori_produk kp 
    LEFT JOIN tbl_produk p ON kp.nama_kategori = p.kategori 
    GROUP BY kp.nama_kategori 
    ORDER BY total DESC")->fetchAll();

require_once __DIR__ . '/template/header.php';
require_once __DIR__ . '/template/sidebar.php';
require_once __DIR__ . '/template/topbar.php';
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="glass rounded-3xl p-6 bg-gradient-to-br from-emerald-500/20 to-emerald-600/5 border border-emerald-500/10">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                <i class="fas fa-shopping-cart text-xl"></i>
            </div>
            <span class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest bg-emerald-500/10 px-2 py-1 rounded-lg border border-emerald-500/20">Hari Ini</span>
        </div>
        <p class="text-slate-400 text-xs font-medium mb-1">Penjualan Hari Ini</p>
        <h3 class="text-2xl font-black text-white italic"><?= rupiah($total_sales) ?></h3>
    </div>

    <div class="glass rounded-3xl p-6 bg-gradient-to-br from-blue-500/20 to-blue-600/5 border border-blue-500/10">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-500/20 flex items-center justify-center text-blue-400">
                <i class="fas fa-receipt text-xl"></i>
            </div>
        </div>
        <p class="text-slate-400 text-xs font-medium mb-1">Total Transaksi</p>
        <h3 class="text-2xl font-black text-white italic"><?= $count_trx ?> Transaksi</h3>
    </div>

    <div class="glass rounded-3xl p-6 bg-gradient-to-br from-amber-500/20 to-amber-600/5 border border-amber-500/10">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/20 flex items-center justify-center text-amber-400">
                <i class="fas fa-box-open text-xl"></i>
            </div>
            <?php if ($produk_habis > 0): ?>
            <span class="animate-pulse text-[10px] font-bold text-amber-500 uppercase tracking-widest bg-amber-500/10 px-2 py-1 rounded-lg border border-amber-500/20">Perlu Update</span>
            <?php endif; ?>
        </div>
        <p class="text-slate-400 text-xs font-medium mb-1">Stok Menipis</p>
        <h3 class="text-2xl font-black text-white italic"><?= $produk_habis ?> Produk</h3>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">
    <!-- Quick Actions -->
    <div class="glass rounded-3xl p-8 flex flex-col h-full">
        <h4 class="font-bold text-lg mb-6"><i class="fas fa-bolt text-emerald-400 mr-2"></i>Akses Cepat Kasir</h4>
        <div class="space-y-3 flex-1">
            <a href="admin/kasir.php" class="flex items-center justify-between p-3.5 bg-emerald-500/10 hover:bg-emerald-500/20 rounded-2xl border border-emerald-500/20 transition-all group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center text-white shadow-lg shadow-emerald-900/40">
                        <i class="fas fa-cash-register text-sm"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-white">Buka POS Kasir</p>
                        <p class="text-[9px] text-emerald-500/70">Mulai transaksi baru</p>
                    </div>
                </div>
                <i class="fas fa-chevron-right text-[10px] text-emerald-500 group-hover:translate-x-1 transition-transform"></i>
            </a>
            
            <a href="admin/topup_manual.php" class="flex items-center justify-between p-3.5 bg-white/5 hover:bg-white/10 rounded-2xl border border-white/5 transition-all group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-500">
                        <i class="fas fa-coins text-sm"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold">Top-up Manual</p>
                        <p class="text-[9px] text-slate-500">Isi saldo jajan siswa</p>
                    </div>
                </div>
                <i class="fas fa-chevron-right text-[10px] text-slate-600 group-hover:translate-x-1 transition-transform"></i>
            </a>

            <a href="admin/produk.php" class="flex items-center justify-between p-3.5 bg-white/5 hover:bg-white/10 rounded-2xl border border-white/5 transition-all group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-500">
                        <i class="fas fa-utensils text-sm"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold">Kelola Menu</p>
                        <p class="text-[9px] text-slate-500">Stok & harga barang</p>
                    </div>
                </div>
                <i class="fas fa-chevron-right text-[10px] text-slate-600 group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>
    </div>

    <div class="glass rounded-3xl p-8 flex flex-col h-full">
        <h4 class="font-bold text-lg mb-6"><i class="fas fa-tags text-indigo-400 mr-2"></i>Produk per Kategori</h4>
        <div class="space-y-3 flex-1">
            <?php if (empty($cat_summary)): ?>
                <div class="text-center py-10 opacity-30"><i class="fas fa-layer-group text-3xl mb-3 block"></i><p class="text-xs italic">Kategori kosong</p></div>
            <?php else: foreach($cat_summary as $cs): ?>
            <div class="flex items-center justify-between p-3.5 bg-slate-800/30 hover:bg-slate-800/50 rounded-2xl border border-white/5 transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500/20 to-purple-600/20 flex items-center justify-center text-[10px] text-indigo-400 font-black uppercase tracking-tighter border border-indigo-500/10">
                        <?= substr($cs['nama_kategori'], 0, 2) ?>
                    </div>
                    <span class="text-xs font-semibold text-slate-200"><?= clean($cs['nama_kategori']) ?></span>
                </div>
                <div class="flex items-center gap-2 bg-black/20 px-3 py-1.5 rounded-xl border border-white/5">
                    <span class="text-xs font-black text-indigo-400"><?= $cs['total'] ?></span>
                    <span class="text-[9px] text-slate-500 font-bold uppercase tracking-tighter">Items</span>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Recent Sales -->
    <div class="glass rounded-3xl p-8 flex flex-col h-full">
        <h4 class="font-bold text-lg mb-6"><i class="fas fa-history text-slate-400 mr-2"></i>Penjualan Terakhir</h4>
        <div class="space-y-4">
            <?php
            $recent = $pdo->prepare("SELECT * FROM tbl_order ORDER BY tanggal DESC LIMIT 5");
            $recent->execute();
            while($r = $recent->fetch()):
            ?>
            <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-xs">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold"><?= $r['kode_order'] ?></p>
                        <p class="text-[10px] text-slate-500"><?= date('H:i • d M', strtotime($r['tanggal'])) ?></p>
                    </div>
                </div>
                <p class="font-black text-emerald-400"><?= rupiah($r['total']) ?></p>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/template/footer.php'; ?>
