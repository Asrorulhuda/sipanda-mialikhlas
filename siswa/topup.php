<?php
$page_title = 'Top-up Saldo Jajan';
require_once __DIR__ . '/../config/init.php';
cek_role(['siswa']);
cek_fitur('ekantin');

$id_siswa = $_SESSION['user_id'];
$siswa = $pdo->prepare("SELECT * FROM tbl_siswa WHERE id_siswa = ?");
$siswa->execute([$id_siswa]); $s = $siswa->fetch();

$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();

// Determine Snap JS URL
$is_sandbox = (strpos($setting['midtrans_client_key'], 'SB-') === 0);
$snap_js_url = $is_sandbox ? "https://app.sandbox.midtrans.com/snap/snap.js" : "https://app.midtrans.com/snap/snap.js";

if (isset($_POST['submit_topup_manual'])) {
    $nominal = (float)$_POST['nominal'];
    if ($nominal < 10000) {
        flash('msg', 'Minimal top-up Rp 10.000', 'danger');
    } else {
        $order_id = 'TOPUP-'.time().'-'.$id_siswa;
        $pdo->prepare("INSERT INTO tbl_topup (order_id, id_user, role, nominal, method, status) VALUES (?, ?, 'siswa', ?, 'Manual', 'pending')")
            ->execute([$order_id, $id_siswa, $nominal]);
            
        flash('interactive', [
            'title' => 'PERMINTAAN TERKIRIM!',
            'nominal' => rupiah($nominal),
            'user' => clean($s['nama']),
            'text' => 'Silakan bawa uang tunai ke Bendahara untuk aktivasi saldo jajan Anda.'
        ]);
        header('Location: kantin.php'); exit;
    }
}

$riwayat_topup = $pdo->prepare("SELECT * FROM tbl_topup WHERE id_user = ? AND role = 'siswa' ORDER BY created_at DESC LIMIT 10");
$riwayat_topup->execute([$id_siswa]); $history = $riwayat_topup->fetchAll();

require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<script src="<?= $snap_js_url ?>" data-client-key="<?= $setting['midtrans_client_key'] ?>"></script>
<?= alert_flash('msg') ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
    <!-- Form Top-up -->
    <div class="glass rounded-3xl p-8">
        <h4 class="font-bold text-lg mb-8 uppercase tracking-widest text-emerald-400">Pilih Nominal Top-up</h4>
        
        <form id="form-topup" method="POST" class="space-y-6">
            <div class="grid grid-cols-3 gap-3 mb-6">
                <?php foreach([20000, 50000, 100000] as $amount): ?>
                <div onclick="document.getElementById('nominal_input').value=<?=$amount?>" class="p-4 bg-white/5 border border-white/5 rounded-2xl text-center cursor-pointer hover:bg-emerald-500/10 hover:border-emerald-500/30 transition-all active:scale-95">
                    <p class="text-xs text-slate-400 mb-1">Rp</p>
                    <p class="font-bold text-sm"><?= number_format($amount/1000, 0) ?>K</p>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-400 uppercase">Nominal Kustom (Min. 10.000)</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-slate-500">Rp</span>
                    <input type="number" name="nominal" id="nominal_input" placeholder="0" required class="w-full bg-slate-800 border border-white/10 rounded-2xl pl-12 pr-4 py-4 text-xl font-black focus:border-emerald-500 outline-none transition-all">
                </div>
            </div>

            <div class="space-y-4">
                <label class="text-xs font-bold text-slate-400 uppercase">Metode Pembayaran</label>
                <div class="grid grid-cols-1 gap-3">
                    <label class="cursor-pointer group">
                        <input type="radio" name="method" value="Midtrans" checked class="peer hidden">
                        <div class="flex items-center justify-between p-4 bg-white/5 border border-white/5 rounded-2xl peer-checked:bg-blue-500/10 peer-checked:border-blue-500/50 transition-all">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center"><i class="fas fa-credit-card text-blue-400"></i></div>
                                <div><p class="text-sm font-bold">Midtrans</p><p class="text-[10px] text-slate-500">Virtual Account, Alfamart, QRIS</p></div>
                            </div>
                            <div class="w-4 h-4 rounded-full border-2 border-slate-700 peer-checked:border-blue-500 flex items-center justify-center"><div class="w-2 h-2 rounded-full bg-blue-500 opacity-0 peer-checked:opacity-100"></div></div>
                        </div>
                    </label>
                    <label class="cursor-pointer group">
                        <input type="radio" name="method" value="Tripay" class="peer hidden">
                        <div class="flex items-center justify-between p-4 bg-white/5 border border-white/5 rounded-2xl peer-checked:bg-orange-500/10 peer-checked:border-orange-500/50 transition-all">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center"><i class="fas fa-bolt text-orange-400"></i></div>
                                <div><p class="text-sm font-bold">Tripay</p><p class="text-[10px] text-slate-500">Direct Bank Transfer, E-Wallet</p></div>
                            </div>
                        </div>
                    </label>
                    <label class="cursor-pointer group">
                        <input type="radio" name="method" value="Xendit" class="peer hidden">
                        <div class="flex items-center justify-between p-4 bg-white/5 border border-white/5 rounded-2xl peer-checked:bg-purple-500/10 peer-checked:border-purple-500/50 transition-all">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center"><i class="fas fa-microchip text-purple-400"></i></div>
                                <div><p class="text-sm font-bold">Xendit</p><p class="text-[10px] text-slate-500">Modern Enterprise API Payment</p></div>
                            </div>
                        </div>
                    </label>
                    <label class="cursor-pointer group text-slate-400">
                        <input type="radio" name="method" value="Manual" class="peer hidden">
                        <div class="flex items-center justify-between p-4 bg-white/5 border border-white/5 border-dashed rounded-2xl peer-checked:bg-slate-700 peer-checked:border-slate-500 transition-all">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center"><i class="fas fa-hand-holding-usd"></i></div>
                                <div><p class="text-sm font-bold">Manual / Tunai</p><p class="text-[10px] text-slate-500">Bayar Cash ke Bendahara / Bapak Guru</p></div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit" name="submit_topup" class="w-full py-5 bg-emerald-600 hover:bg-emerald-500 rounded-3xl font-black text-lg shadow-xl shadow-emerald-900/40 transition-all active:scale-95">PROSES PEMBAYARAN</button>
        </form>
    </div>

    <!-- Info & History -->
    <div class="space-y-6">
        <div class="glass rounded-3xl p-8 bg-gradient-to-br from-white/5 to-white/[0.02]">
            <h4 class="font-bold text-sm mb-6 uppercase tracking-widest text-slate-400">Riwayat Top-up</h4>
            <div class="space-y-4">
                <?php if (empty($history)): ?>
                    <div class="text-center py-10 opacity-30"><i class="fas fa-receipt text-3xl mb-3"></i><p class="text-xs">Belum ada riwayat</p></div>
                <?php else: foreach ($history as $h): ?>
                <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl">
                    <div>
                        <p class="text-xs font-bold"><?= clean($h['order_id']) ?></p>
                        <p class="text-[10px] text-slate-500"><?= $h['method'] ?> • <?= date('d/m/y H:i', strtotime($h['created_at'])) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-black text-emerald-400">+ <?= number_format($h['nominal'], 0, ',', '.') ?></p>
                        <span class="text-[9px] uppercase px-2 py-0.5 rounded-lg bg-<?= $h['status'] == 'success' ? 'emerald' : ($h['status'] == 'pending' ? 'yellow' : 'red') ?>-500/10 text-<?= $h['status'] == 'success' ? 'emerald' : ($h['status'] == 'pending' ? 'yellow' : 'red') ?>-400 font-bold"><?= $h['status'] ?></span>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="p-8 rounded-3xl bg-blue-600/10 border border-blue-500/20">
            <h5 class="text-blue-400 font-bold text-xs mb-3 flex items-center"><i class="fas fa-info-circle mr-2"></i>Tentang Saldo Jajan</h5>
            <p class="text-xs text-blue-100/70 leading-relaxed mb-4">Saldo Jajan (E-Wallet) adalah dompet digital terpisah dari Tabungan Siswa yang dapat digunakan khusus untuk belanja di Kantin Sekolah menggunakan kartu RFID.</p>
            <ul class="text-[10px] text-blue-100/50 space-y-2">
                <li>• Pembayaran terverifikasi otomatis (PG)</li>
                <li>• Saldo langsung masuk setelah pembayaran sukses</li>
                <li>• Batasi jajan harian di menu Pengaturan Jajan</li>
            </ul>
        </div>
    </div>
</div>

<!-- LOADING OVERLAY -->
<div id="pay-loader" class="hidden fixed inset-0 bg-slate-900/90 backdrop-blur-md z-[9999] flex-col items-center justify-center">
    <div class="relative">
        <i class="fas fa-circle-notch fa-spin text-6xl text-emerald-400 mb-6 drop-shadow-[0_0_15px_rgba(52,211,153,0.5)]"></i>
    </div>
    <h3 class="text-white font-black italic uppercase tracking-widest animate-pulse text-center px-6">Menghubungkan ke Portal Pembayaran...</h3>
    <p class="text-slate-500 text-xs mt-2 uppercase font-bold tracking-widest">Jangan tutup halaman ini</p>
</div>

<script>
document.getElementById('form-topup').onsubmit = async function(e) {
    const method = document.querySelector('input[name="method"]:checked').value;
    const nominal = document.getElementById('nominal_input').value;
    
    if (nominal < 10000) {
        Swal.fire('Atensi', 'Minimal top-up Rp 10.000', 'warning');
        return false;
    }

    if (method === 'Manual') {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'submit_topup_manual';
        input.value = '1';
        this.appendChild(input);
        return true; 
    }

    e.preventDefault();
    
    const loader = document.getElementById('pay-loader');
    loader.classList.remove('hidden');
    loader.classList.add('flex');

    const formData = new FormData();
    formData.append('id_siswa', '<?= $id_siswa ?>');
    formData.append('jumlah', nominal);
    formData.append('gateway', method);

    try {
        const response = await fetch('<?= BASE_URL ?>api/payment.php?action=create_topup', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.status === 'ok') {
            if (data.gateway === 'Midtrans') {
                snap.pay(data.token, {
                    onSuccess: function(result) { location.reload(); },
                    onPending: function(result) { location.reload(); },
                    onError: function(result) { Swal.fire("Gagal", "Pembayaran gagal!", "error").then(() => location.reload()); },
                    onClose: function() { location.reload(); }
                });
            } else {
                window.open(data.checkout_url, '_blank');
                Swal.fire({
                    title: 'Pembayaran Dibuka',
                    text: 'Silakan selesaikan pembayaran pada tab baru yang terbuka. Segarkan halaman ini setelah selesai.',
                    icon: 'info',
                    confirmButtonText: 'Segarkan Halaman'
                }).then(() => location.reload());
            }
        } else {
            Swal.fire('Gagal!', data.message, 'error');
        }
    } catch (error) {
        Swal.fire('Error!', error.message || 'Tidak dapat terhubung ke server.', 'error');
    } finally {
        loader.classList.add('hidden');
        loader.classList.remove('flex');
    }
}
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
