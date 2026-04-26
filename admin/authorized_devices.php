<?php
$page_title = 'Manajemen Perangkat Terdaftar';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);

// Action: Approve
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $pdo->prepare("UPDATE tbl_authorized_devices SET status='Approved' WHERE id=?")->execute([$id]);
    flash('msg', 'Perangkat berhasil disetujui!');
    header('Location: authorized_devices.php'); exit;
}

// Action: Deny
if (isset($_GET['deny'])) {
    $id = (int)$_GET['deny'];
    $pdo->prepare("UPDATE tbl_authorized_devices SET status='Denied' WHERE id=?")->execute([$id]);
    flash('msg', 'Perangkat telah ditolak!');
    header('Location: authorized_devices.php'); exit;
}

// Action: Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM tbl_authorized_devices WHERE id=?")->execute([$id]);
    flash('msg', 'Data perangkat berhasil dihapus!');
    header('Location: authorized_devices.php'); exit;
}

// Action: Rename (AJAX or Simple POST)
if (isset($_POST['rename'])) {
    $id = (int)$_POST['id'];
    $name = $_POST['device_name'];
    $pdo->prepare("UPDATE tbl_authorized_devices SET device_name=? WHERE id=?")->execute([$name, $id]);
    flash('msg', 'Nama perangkat berhasil diubah!');
    header('Location: authorized_devices.php'); exit;
}

$devices = $pdo->query("SELECT * FROM tbl_authorized_devices ORDER BY status DESC, created_at DESC")->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-white flex items-center gap-2"><i class="fas fa-mobile-alt text-rose-500"></i> Otorisasi Perangkat Scanner</h2>
    <div class="px-4 py-2 glass rounded-xl border border-white/10">
        <span class="text-xs text-slate-400">Total: <?= count($devices) ?> Perangkat</span>
    </div>
</div>

<?= alert_flash('msg') ?>

<div class="glass rounded-2xl overflow-hidden border border-white/5">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-white/5 text-[10px] uppercase tracking-widest font-bold text-slate-400 border-b border-white/5">
                <tr>
                    <th class="px-6 py-4">Perangkat & Token</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Terakhir Digunakan</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($devices as $d): ?>
                <tr class="hover:bg-white/5 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <form method="POST" class="flex items-center gap-2 group">
                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                <input type="text" name="device_name" value="<?= clean($d['device_name']) ?>" 
                                    class="bg-transparent border-none p-0 font-bold text-white focus:ring-0 focus:outline-none focus:border-b focus:border-blue-500 w-full lg:w-48 text-sm">
                                <button type="submit" name="rename" class="opacity-0 group-hover:opacity-100 text-blue-400 transition-opacity"><i class="fas fa-save"></i></button>
                            </form>
                            <span class="text-[10px] text-slate-500 font-mono mt-1"><?= $d['device_token'] ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($d['status'] == 'Approved'): ?>
                            <span class="px-2 py-1 rounded-md bg-emerald-500/10 text-emerald-500 text-[10px] font-black uppercase tracking-widest border border-emerald-500/20 italic">Disetujui</span>
                        <?php elseif ($d['status'] == 'Denied'): ?>
                            <span class="px-2 py-1 rounded-md bg-rose-500/10 text-rose-500 text-[10px] font-black uppercase tracking-widest border border-rose-500/20 italic">Ditolak</span>
                        <?php else: ?>
                            <span class="px-2 py-1 rounded-md bg-amber-500/10 text-amber-500 text-[10px] font-black uppercase tracking-widest border border-amber-500/20 italic animate-pulse">Menunggu</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-xs text-slate-400">
                        <?= $d['last_used'] ? tgl_indo($d['last_used']) . ' ' . date('H:i', strtotime($d['last_used'])) : '<span class="italic">Belum pernah</span>' ?>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <?php if ($d['status'] != 'Approved'): ?>
                                <a href="?approve=<?= $d['id'] ?>" class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-500 flex items-center justify-center hover:bg-emerald-500 hover:text-white transition-all" title="Setujui"><i class="fas fa-check"></i></a>
                            <?php endif; ?>
                            <?php if ($d['status'] != 'Denied'): ?>
                                <a href="?deny=<?= $d['id'] ?>" class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-500 flex items-center justify-center hover:bg-amber-500 hover:text-white transition-all" title="Tolak"><i class="fas fa-ban"></i></a>
                            <?php endif; ?>
                            <a href="?delete=<?= $d['id'] ?>" onclick="return confirm('Hapus data perangkat ini?')" class="w-8 h-8 rounded-lg bg-rose-500/20 text-rose-500 flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all" title="Hapus"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; if (!$devices) echo '<tr><td colspan="4" class="px-6 py-10 text-center text-slate-500 italic">Belum ada perangkat yang terdeteksi menghubungi sistem.</td></tr>'; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6 glass p-6 rounded-2xl border border-blue-500/20 bg-blue-500/5">
    <h4 class="text-white font-bold text-sm mb-4 flex items-center gap-2"><i class="fas fa-info-circle text-blue-400"></i> Bagaimana Cara Kerja Otorisasi?</h4>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="flex gap-3">
            <div class="w-8 h-8 rounded-full bg-blue-500/20 flex-shrink-0 flex items-center justify-center text-xs font-bold text-blue-400 border border-blue-500/20">1</div>
            <p class="text-[11px] text-slate-400 leading-relaxed">Buka Homepage scanner di HP baru, sistem akan otomatis mengirim permintaan registrasi "Menunggu".</p>
        </div>
        <div class="flex gap-3">
            <div class="w-8 h-8 rounded-full bg-blue-500/20 flex-shrink-0 flex items-center justify-center text-xs font-bold text-blue-400 border border-blue-500/20">2</div>
            <p class="text-[11px] text-slate-400 leading-relaxed">Admin melakukan **Approve** pada daftar di atas dan memberi nama perangkat agar mudah dikenali.</p>
        </div>
        <div class="flex gap-3">
            <div class="w-8 h-8 rounded-full bg-blue-500/20 flex-shrink-0 flex items-center justify-center text-xs font-bold text-blue-400 border border-blue-500/20">3</div>
            <p class="text-[11px] text-slate-400 leading-relaxed">Setelah disetujui, HP tersebut sudah bisa digunakan untuk melakukan scan presensi secara normal.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
