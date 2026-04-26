<?php
$page_title = 'E-Kantin Siswa';
require_once __DIR__ . '/../config/init.php';
cek_role(['siswa']);
cek_fitur('ekantin');

$id_siswa = $_SESSION['user_id'];

// Handle Limit Update
if (isset($_POST['update_limit'])) {
    $limit = (float)$_POST['limit_jajan'];
    if ($limit < 0) $limit = 0;
    $pdo->prepare("UPDATE tbl_siswa SET limit_jajan_harian = ? WHERE id_siswa = ?")->execute([$limit, $id_siswa]);
    flash('msg', 'Batas jajan harian berhasil diperbarui!');
    header('Location: kantin.php'); exit;
}

$siswa = $pdo->prepare("SELECT * FROM tbl_siswa WHERE id_siswa = ?");
$siswa->execute([$id_siswa]); $s = $siswa->fetch();

$spent_today = $pdo->prepare("SELECT SUM(total) as current FROM tbl_order WHERE id_siswa = ? AND DATE(tanggal) = CURDATE()");
$spent_today->execute([$id_siswa]); $current = $spent_today->fetch()['current'] ?? 0;

$riwayat = $pdo->prepare("SELECT * FROM tbl_order WHERE id_siswa = ? ORDER BY tanggal DESC LIMIT 20");
$riwayat->execute([$id_siswa]); $transactions = $riwayat->fetchAll();

require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Saldo & Limit Info -->
    <div class="md:col-span-2 space-y-6">
        <div class="glass rounded-3xl p-8 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500/10 rounded-full -mr-20 -mt-20 blur-3xl group-hover:bg-emerald-500/20 transition-all duration-700"></div>
            <div class="relative">
                <p class="text-slate-400 text-sm font-medium mb-1">Saldo Jajan Sekarang</p>
                <h2 class="text-4xl font-black text-white italic mb-6"><?= rupiah($s['saldo_jajan']) ?></h2>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white/5 rounded-2xl p-4 border border-white/5">
                        <p class="text-xs text-slate-500 mb-1">Terpakai Hari Ini</p>
                        <p class="font-bold text-emerald-400"><?= rupiah($current) ?></p>
                    </div>
                    <div class="bg-white/5 rounded-2xl p-4 border border-white/5">
                        <p class="text-xs text-slate-500 mb-1">Limit Harian</p>
                        <p class="font-bold text-blue-400"><?= $s['limit_jajan_harian'] > 0 ? rupiah($s['limit_jajan_harian']) : 'Tanpa Batas' ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Riwayat Transaksi -->
        <div class="glass rounded-3xl p-8">
            <h4 class="font-bold text-lg mb-6"><i class="fas fa-receipt text-emerald-400 mr-2"></i>Riwayat Jajan</h4>
            <div class="space-y-4">
                <?php if (empty($transactions)): ?>
                    <div class="text-center py-10 opacity-30"><i class="fas fa-cookie-bite text-4xl mb-3"></i><p>Belum ada transaksi</p></div>
                <?php else: foreach ($transactions as $t): ?>
                <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl hover:bg-white/10 transition-all cursor-default">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-400">
                            <i class="fas fa-shopping-cart text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold"><?= clean($t['kode_order']) ?></p>
                            <p class="text-[10px] text-slate-500 uppercase tracking-widest"><?= date('d M Y • H:i', strtotime($t['tanggal'])) ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-black text-red-400">- <?= rupiah($t['total']) ?></p>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400"><?= $t['cara_bayar'] ?></span>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="space-y-6">
        <!-- Pengaturan Limit -->
        <div class="glass rounded-3xl p-8 bg-gradient-to-br from-white/5 to-white/[0.02]">
            <h4 class="font-bold text-sm mb-6 uppercase tracking-wider text-slate-400">Atur Limit Jajan</h4>
            <p class="text-xs text-slate-500 mb-6 italic leading-relaxed">Limit ini berguna untuk membatasi pengeluaran harian siswa agar tidak berlebihan.</p>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold mb-2">Batas Harian (Rp)</label>
                    <input type="number" name="limit_jajan" value="<?= (int)$s['limit_jajan_harian'] ?>" step="1000" class="w-full bg-slate-800 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 outline-none transition-all">
                    <p class="text-[10px] text-slate-500 mt-2">Isi 0 untuk tanpa batas.</p>
                </div>
                <button type="submit" name="update_limit" class="w-full py-3 bg-white/10 hover:bg-white/20 border border-white/10 rounded-xl text-xs font-bold transition-all active:scale-95">SIMPAN PERUBAHAN</button>
            </form>
        </div>

        <!-- Top-up Button -->
        <a href="topup.php" class="block group">
            <div class="glass rounded-3xl p-8 bg-emerald-600 hover:bg-emerald-500 transition-all duration-500 shadow-xl shadow-emerald-900/40 border-none">
                <div class="flex justify-between items-center mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center group-hover:scale-110 transition-all">
                        <i class="fas fa-wallet text-xl"></i>
                    </div>
                    <i class="fas fa-arrow-right text-white/40 group-hover:translate-x-2 transition-all"></i>
                </div>
                <h4 class="font-black text-xl mb-1">Top-up Saldo</h4>
                <p class="text-xs text-white/70">Isi saldo jajan via transfer bank atau e-wallet</p>
            </div>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
