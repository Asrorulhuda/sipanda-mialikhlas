<?php
$page_title = 'Dompet Penjual';
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
cek_fitur('ekantin');

$id_guru = $_SESSION['user_id'];

// Handle Withdrawal Request
if (isset($_POST['tarik_dana'])) {
    $nominal = (float)$_POST['nominal'];
    $metode = $_POST['metode']; // 'Saldo Jajan' atau 'Tarik Tunai'
    
    // Check balance
    $g = $pdo->prepare("SELECT saldo_penjual FROM tbl_guru WHERE id_guru = ?");
    $g->execute([$id_guru]); $gd = $g->fetch();
    
    if ($nominal > $gd['saldo_penjual']) {
        flash('msg', 'Saldo tidak cukup!', 'danger');
    } elseif ($nominal < 1000) {
        flash('msg', 'Minimal penarikan Rp 1.000', 'warning');
    } else {
        // Record request
        $pdo->prepare("INSERT INTO tbl_penarikan_guru (id_guru, nominal, metode, status) VALUES (?, ?, ?, 'Pending')")
            ->execute([$id_guru, $nominal, $metode]);
            
        // Deduct balance immediately to prevent double withdrawal (will be reverted if rejected)
        $pdo->prepare("UPDATE tbl_guru SET saldo_penjual = saldo_penjual - ? WHERE id_guru = ?")
            ->execute([$nominal, $id_guru]);
            
        // Notify Bendahara (Optional: you can implement WA notif here)
        require_once __DIR__ . '/../api/wa_helper.php';
        $bendahara_no = $pdo->query("SELECT wa_tu FROM tbl_setting WHERE id=1")->fetch()['wa_tu'] ?? '';
        if ($bendahara_no) {
            $msg = "📢 *PENGAJUAN PENARIKAN SALDO PENJUAL*\n\nGuru: *{$_SESSION['nama']}*\nNominal: *".rupiah($nominal)."*\nMetode: *{$metode}*\n\nMohon dicek di Dashboard Admin/Bendahara. 🙏";
            send_wa($bendahara_no, $msg);
        }
            
        flash('msg', 'Permintaan penarikan berhasil terkirim ke Bendahara!');
    }
    header('Location: dompet_penjual.php'); exit;
}

$guru = $pdo->prepare("SELECT saldo_penjual, saldo_jajan FROM tbl_guru WHERE id_guru = ?");
$guru->execute([$id_guru]);
$g = $guru->fetch();

$riwayat = $pdo->prepare("SELECT * FROM tbl_penarikan_guru WHERE id_guru = ? ORDER BY id DESC LIMIT 20");
$riwayat->execute([$id_guru]);
$riwayat = $riwayat->fetchAll();

// Get total earnings from sold items (summary)
$total_terjual = $pdo->prepare("SELECT SUM(subtotal) as total FROM tbl_order_detail od JOIN tbl_produk p ON od.id_produk=p.id_produk WHERE p.id_guru_penjual = ?");
$total_terjual->execute([$id_guru]);
$total_omzet = $total_terjual->fetch()['total'] ?? 0;

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Stat Saldo Penjual -->
    <div class="glass rounded-2xl p-6 bg-gradient-to-br from-blue-600/20 to-purple-600/20 border border-blue-500/30">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-500/20 flex items-center justify-center text-blue-400">
                <i class="fas fa-wallet text-xl"></i>
            </div>
            <span class="text-[10px] font-black text-blue-400 uppercase bg-blue-500/10 px-2 py-1 rounded">Saldo Tersedia</span>
        </div>
        <p class="text-xs text-slate-400 font-medium">Pendapatan Bersih Saya</p>
        <h2 class="text-3xl font-black text-white italic"><?= rupiah($g['saldo_penjual']) ?></h2>
    </div>

    <!-- Stat Omzet -->
    <div class="glass rounded-2xl p-6">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-400">
                <i class="fas fa-chart-line text-xl"></i>
            </div>
            <span class="text-[10px] font-black text-emerald-500 uppercase bg-emerald-500/10 px-2 py-1 rounded">Total Omzet</span>
        </div>
        <p class="text-xs text-slate-400 font-medium">Total Penjualan Kotor</p>
        <h2 class="text-3xl font-black text-white italic"><?= rupiah($total_omzet) ?></h2>
    </div>

    <!-- Stat Saldo Jajan -->
    <div class="glass rounded-2xl p-6">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 rounded-2xl bg-orange-500/10 flex items-center justify-center text-orange-400">
                <i class="fas fa-id-card text-xl"></i>
            </div>
            <span class="text-[10px] font-black text-orange-500 uppercase bg-orange-500/10 px-2 py-1 rounded">Saldo Jajan</span>
        </div>
        <p class="text-xs text-slate-400 font-medium">Saldo Dompet E-Kantin</p>
        <h2 class="text-3xl font-black text-white italic"><?= rupiah($g['saldo_jajan']) ?></h2>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Form Penarikan -->
    <div class="glass rounded-2xl p-6">
        <h4 class="font-bold text-sm mb-6 flex items-center gap-2"><i class="fas fa-hand-holding-usd text-blue-400"></i>Tarik Penghasilan</h4>
        <?= alert_flash('msg') ?>
        
        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-xs text-slate-400 mb-2 font-bold italic italic">Nominal Penarikan</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-slate-500">Rp</span>
                    <input type="number" name="nominal" required min="1000" max="<?= (int)$g['saldo_penjual'] ?>" placeholder="0" class="w-full bg-slate-800 border border-white/10 rounded-xl pl-12 pr-4 py-4 text-xl font-black text-white focus:border-blue-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs text-slate-400 mb-2 font-bold italic italic">Metode Pencairan</label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="cursor-pointer">
                        <input type="radio" name="metode" value="Saldo Jajan" checked class="peer hidden">
                        <div class="p-4 rounded-xl bg-white/5 border border-white/5 peer-checked:bg-blue-600/10 peer-checked:border-blue-500/50 transition-all text-center">
                            <i class="fas fa-id-card text-xl mb-2 block"></i>
                            <p class="text-[10px] font-bold uppercase">Ke Saldo Jajan</p>
                            <span class="text-[8px] text-slate-500">Instan setelah approve</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="metode" value="Tarik Tunai" class="peer hidden">
                        <div class="p-4 rounded-xl bg-white/5 border border-white/5 peer-checked:bg-orange-600/10 peer-checked:border-orange-500/50 transition-all text-center">
                            <i class="fas fa-money-bill-wave text-xl mb-2 block"></i>
                            <p class="text-[10px] font-bold uppercase">Tarik Tunai</p>
                            <span class="text-[8px] text-slate-500">Ambil di Bendahara</span>
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit" name="tarik_dana" class="w-full bg-blue-600 hover:bg-blue-500 py-4 rounded-2xl font-black italic tracking-widest shadow-lg shadow-blue-500/20 transition-all active:scale-95 disabled:opacity-50" <?= $g['saldo_penjual'] < 1000 ? 'disabled' : '' ?>>
                AJUKAN PENCAIRAN <i class="fas fa-paper-plane ml-2"></i>
            </button>
        </form>
    </div>

    <!-- Riwayat Penarikan -->
    <div class="glass rounded-2xl p-6">
        <h4 class="font-bold text-sm mb-6 flex items-center gap-2"><i class="fas fa-history text-slate-400"></i>Riwayat Pengajuan</h4>
        <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
            <?php if (empty($riwayat)): ?>
                <div class="text-center py-12 text-slate-600">
                    <i class="fas fa-receipt text-3xl mb-3 block opacity-20"></i>
                    <p class="text-xs">Belum ada riwayat penarikan</p>
                </div>
            <?php endif; ?>
            
            <?php foreach ($riwayat as $r): ?>
            <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl border border-white/5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center text-slate-400">
                        <i class="fas <?= $r['metode'] == 'Saldo Jajan' ? 'fa-id-card' : 'fa-money-bill-wave' ?>"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-white"><?= rupiah($r['nominal']) ?></p>
                        <p class="text-[9px] text-slate-500 font-medium"><?= date('d M Y, H:i', strtotime($r['tanggal'])) ?> • <?= $r['metode'] ?></p>
                    </div>
                </div>
                <div class="text-right">
                    <?php if ($r['status'] == 'Pending'): ?>
                        <span class="text-[9px] font-black uppercase text-amber-500 bg-amber-500/10 px-2 py-0.5 rounded italic">Menunggu</span>
                    <?php elseif ($r['status'] == 'Sukses'): ?>
                        <span class="text-[9px] font-black uppercase text-emerald-500 bg-emerald-500/10 px-2 py-0.5 rounded italic">Berhasil</span>
                    <?php else: ?>
                        <span class="text-[9px] font-black uppercase text-red-500 bg-red-500/10 px-2 py-0.5 rounded italic">Ditolak</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
</style>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
