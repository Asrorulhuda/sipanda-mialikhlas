<?php
$page_title = 'E-Kantin Guru';
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
cek_fitur('ekantin');

$id_guru = $_SESSION['user_id'];

$guru = $pdo->prepare("SELECT * FROM tbl_guru WHERE id_guru = ?");
$guru->execute([$id_guru]); $g = $guru->fetch();

// History for guru is stored in tbl_order with a special description or linked to a placeholder student?
// Actually, in my POS logic, I recorded Guru transactions in tbl_order with id_siswa=NULL and a description in tbl_pos_transaksi.
// Let's adjust POS to better track Guru transactions. 
// For now, I'll fetch from tbl_pos_transaksi recorded with this guru's name.

$riwayat = $pdo->prepare("SELECT * FROM tbl_pos_transaksi WHERE kasir = ? OR nama_item LIKE ? ORDER BY tanggal DESC LIMIT 20");
// This is a bit tricky with current table schema. I'll use a specific query for guru.
$riwayat->execute([$g['nama'], "%Guru: ".$g['nama']."%"]); 
$transactions = $riwayat->fetchAll();

require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Saldo Info -->
    <div class="md:col-span-2 space-y-6">
        <div class="glass rounded-3xl p-10 bg-gradient-to-br from-indigo-600 to-indigo-800 border-none shadow-2xl shadow-indigo-900/40">
            <p class="text-indigo-100 text-sm font-medium mb-1 opacity-80">Saldo E-Kantin Bapak/Ibu</p>
            <h2 class="text-5xl font-black text-white italic mb-8"><?= rupiah($g['saldo_jajan']) ?></h2>
            <div class="flex gap-4">
                <div class="px-4 py-2 bg-white/10 rounded-xl text-xs font-bold text-white"><i class="fas fa-id-card mr-2"></i><?= $g['rfid_uid'] ?: 'Belum Ada Kartu' ?></div>
                <div class="px-4 py-2 bg-white/10 rounded-xl text-xs font-bold text-white"><i class="fas fa-check-circle mr-2"></i>Status Aktif</div>
            </div>
        </div>

        <!-- Riwayat Transaksi -->
        <div class="glass rounded-3xl p-8">
            <h4 class="font-bold text-lg mb-6"><i class="fas fa-history text-indigo-400 mr-2"></i>Riwayat Transaksi Terakhir</h4>
            <div class="space-y-4">
                <?php if (empty($transactions)): ?>
                    <div class="text-center py-10 opacity-30"><i class="fas fa-receipt text-4xl mb-3"></i><p>Belum ada transaksi</p></div>
                <?php else: foreach ($transactions as $t): ?>
                <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl hover:bg-white/10 transition-all cursor-default">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 text-xs">
                            <i class="fas fa-shopping-basket"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold"><?= clean($t['nama_item']) ?></p>
                            <p class="text-[10px] text-slate-500 uppercase tracking-widest"><?= date('d M Y • H:i', strtotime($t['tanggal'])) ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-black text-red-400">- <?= rupiah($t['total']) ?></p>
                        <span class="text-[10px] text-slate-500 italic">via RFID Card</span>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="space-y-6">
        <div class="glass rounded-3xl p-8">
            <h4 class="font-bold text-sm mb-6 uppercase tracking-wider text-slate-400">Informasi Guru</h4>
            <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-2xl overflow-hidden bg-slate-800 border border-white/10">
                    <img src="../gambar/guru/<?= $g['foto'] ?: 'default.png' ?>" class="w-full h-full object-cover">
                </div>
                <div>
                    <p class="text-sm font-bold truncate w-32"><?= clean($g['nama']) ?></p>
                    <p class="text-[10px] text-slate-500">NIP: <?= $g['nip'] ?: '-' ?></p>
                </div>
            </div>
            <p class="text-xs text-slate-400 leading-relaxed italic mb-6">"Gunakan kartu absensi Anda untuk pembayaran di kantin sekolah."</p>
            <div class="p-4 bg-yellow-500/10 border border-yellow-500/20 rounded-2xl">
                <p class="text-[10px] font-bold text-yellow-500 mb-1">PEMBERITAHUAN</p>
                <p class="text-[10px] text-slate-300">Untuk top-up saldo, silakan hubungi Bendahara atau Admin sekolah.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
