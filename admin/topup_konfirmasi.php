<?php
$page_title = 'Konfirmasi Top-up Manual';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin', 'bendahara', 'kasir']);
cek_fitur('ekantin');

// Handle Approval
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $stmt = $pdo->prepare("SELECT * FROM tbl_topup WHERE id = ? AND status = 'pending'");
    $stmt->execute([$id]);
    $t = $stmt->fetch();

    if ($t) {
        $nominal = (float)$t['nominal'];
        $id_user = (int)$t['id_user'];
        $role = $t['role'];

        if ($role == 'siswa') {
            $pdo->prepare("UPDATE tbl_siswa SET saldo_jajan = saldo_jajan + ? WHERE id_siswa = ?")->execute([$nominal, $id_user]);
            $user_info = $pdo->prepare("SELECT nama, no_hp_ortu FROM tbl_siswa WHERE id_siswa = ?");
            $user_info->execute([$id_user]); $u = $user_info->fetch();
            $no_hp = $u['no_hp_ortu'];
        } else {
            $pdo->prepare("UPDATE tbl_guru SET saldo_jajan = saldo_jajan + ? WHERE id_guru = ?")->execute([$nominal, $id_user]);
            $user_info = $pdo->prepare("SELECT nama, no_hp FROM tbl_guru WHERE id_guru = ?");
            $user_info->execute([$id_user]); $u = $user_info->fetch();
            $no_hp = $u['no_hp'];
        }

        // Update Topup Status
        $pdo->prepare("UPDATE tbl_topup SET status = 'success' WHERE id = ?")->execute([$id]);

        // WA Notification
        require_once __DIR__ . '/../api/wa_helper.php';
        if (!empty($no_hp)) {
            send_wa($no_hp, "🏫 *TOP-UP E-KANTIN SUCCESS*\n\nBerhasil disetujui! Saldo jajan an. *{$u['nama']}* sebesar *Rp ".number_format($nominal, 0, ',', '.')."* telah aktif. Selamat jajan! 🍱");
        }

        flash('interactive', [
            'title' => 'KONFIRMASI BERHASIL!',
            'nominal' => rupiah($nominal),
            'user' => clean($u['nama']),
            'text' => 'Saldo telah diaktifkan dan notifikasi WhatsApp telah dikirim ke ' . ($role == 'siswa' ? 'Orang Tua' : 'User') . '.'
        ]);
    }
    header('Location: topup_konfirmasi.php'); exit;
}

// Handle Rejection
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $pdo->prepare("UPDATE tbl_topup SET status = 'failed' WHERE id = ? AND status = 'pending'")->execute([$id]);
    flash('msg', 'Top-up berhasil ditolak!', 'warning');
    header('Location: topup_konfirmasi.php'); exit;
}

// Load Pending Requests
$stmt = $pdo->prepare("
    SELECT t.*, 
    CASE WHEN t.role = 'siswa' THEN s.nama ELSE g.nama END as nama_user,
    CASE WHEN t.role = 'siswa' THEN k.nama_kelas ELSE 'GURU' END as info_user
    FROM tbl_topup t
    LEFT JOIN tbl_siswa s ON t.id_user = s.id_siswa AND t.role = 'siswa'
    LEFT JOIN tbl_kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN tbl_guru g ON t.id_user = g.id_guru AND t.role = 'guru'
    WHERE t.status = 'pending' AND t.method = 'Manual'
    ORDER BY t.created_at ASC
");
$stmt->execute();
$requests = $stmt->fetchAll();

require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex items-center justify-between mb-8">
    <div>
        <h2 class="text-2xl font-black italic uppercase tracking-widest text-white">Antrean Konfirmasi Saldo</h2>
        <p class="text-slate-500 text-xs mt-1">Verifikasi pembayaran tunai dari siswa sebelum mengaktifkan saldo jajan.</p>
    </div>
    <div class="px-4 py-2 glass rounded-xl border border-white/5">
        <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest block">Total Antrean</span>
        <span class="text-xl font-black text-amber-400 italic"><?= count($requests) ?> Request</span>
    </div>
</div>

<?= alert_flash('msg') ?>

<?php if (count($requests) > 0): ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($requests as $r): ?>
    <div class="glass rounded-3xl p-6 border border-white/5 hover:border-amber-500/30 transition-all group relative overflow-hidden">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/5 rounded-full blur-2xl group-hover:bg-amber-500/10 transition-all pointer-events-none"></div>
        
        <div class="flex items-start justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white shadow-lg shadow-amber-900/20">
                    <i class="fas fa-coins text-xl"></i>
                </div>
                <div>
                    <h4 class="font-bold text-white text-sm"><?= clean($r['nama_user']) ?></h4>
                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest"><?= clean($r['info_user']) ?></p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-[9px] text-slate-500 uppercase font-bold tracking-widest mb-1"><?= date('H:i', strtotime($r['created_at'])) ?></p>
                <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 text-[8px] font-black uppercase tracking-widest border border-amber-500/20">Pending</span>
            </div>
        </div>

        <div class="p-4 bg-white/5 rounded-2xl border border-white/5 mb-6 text-center italic relative">
            <p class="text-[10px] text-slate-500 uppercase font-bold tracking-widest mb-1">Nominal Request</p>
            <h3 class="text-2xl font-black text-white italic"><?= rupiah($r['nominal']) ?></h3>
        </div>

        <div class="flex gap-3 relative z-10">
            <button type="button" onclick="confirmApprove(<?= $r['id'] ?>, <?= htmlspecialchars(json_encode(clean($r['nama_user']))) ?>)" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-500 rounded-xl font-bold text-[10px] tracking-widest text-white shadow-lg shadow-emerald-900/40 transition-all active:scale-95 uppercase">
                <i class="fas fa-check-circle mr-2"></i>KONFIRMASI
            </button>
            <button type="button" onclick="confirmReject(<?= $r['id'] ?>)" class="flex-1 py-3 bg-rose-600/10 hover:bg-rose-600 border border-rose-600/20 rounded-xl font-bold text-[10px] tracking-widest text-rose-500 hover:text-white transition-all active:scale-95 uppercase">
                <i class="fas fa-times-circle mr-2"></i>TOLAK
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="glass rounded-3xl p-20 text-center border border-white/5">
    <div class="w-20 h-20 bg-slate-800/50 rounded-full flex items-center justify-center mx-auto mb-6">
        <i class="fas fa-check-double text-slate-600 text-3xl"></i>
    </div>
    <h3 class="text-xl font-bold text-slate-400">Semua Beres!</h3>
    <p class="text-slate-600 text-sm max-w-xs mx-auto mt-2">Tidak ada permintaan top-up yang menunggu konfirmasi saat ini.</p>
</div>
<?php endif; ?>

<script>
function confirmApprove(id, nama) {
    Swal.fire({
        title: 'Konfirmasi Top-up?',
        text: "Pastikan Anda telah menerima uang tunai dari " + nama,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#334155',
        confirmButtonText: 'Ya, Cairkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '?approve=' + id;
        }
    });
}

function confirmReject(id) {
    Swal.fire({
        title: 'Tolak Permintaan?',
        text: "Data permintaan ini akan dihapus dari antrean.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#334155',
        confirmButtonText: 'Ya, Tolak!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '?reject=' + id;
        }
    });
}
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
