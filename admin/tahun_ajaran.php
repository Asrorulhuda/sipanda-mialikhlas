<?php
$page_title = 'Tahun Ajaran';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);

// Actions
if (isset($_POST['simpan'])) {
    $tahun = trim($_POST['tahun']);
    // Auto-calculate tgl_mulai & tgl_selesai dari format "YYYY/YYYY"
    $parts = explode('/', $tahun);
    $tgl_mulai = $parts[0] . '-07-01';
    $tgl_selesai = $parts[1] . '-06-30';
    $stmt = $pdo->prepare("INSERT INTO tbl_tahun_ajaran (tahun, tgl_mulai, tgl_selesai) VALUES (?, ?, ?)");
    $stmt->execute([$tahun, $tgl_mulai, $tgl_selesai]);
    flash('msg', 'Tahun ajaran berhasil ditambahkan!');
    header('Location: tahun_ajaran.php'); exit;
}
if (isset($_POST['update'])) {
    $tahun = trim($_POST['tahun']);
    $parts = explode('/', $tahun);
    $tgl_mulai = $parts[0] . '-07-01';
    $tgl_selesai = $parts[1] . '-06-30';
    $stmt = $pdo->prepare("UPDATE tbl_tahun_ajaran SET tahun=?, tgl_mulai=?, tgl_selesai=? WHERE id_ta=?");
    $stmt->execute([$tahun, $tgl_mulai, $tgl_selesai, $_POST['id']]);
    flash('msg', 'Berhasil diupdate!');
    header('Location: tahun_ajaran.php'); exit;
}
if (isset($_GET['aktifkan'])) {
    $pdo->query("UPDATE tbl_tahun_ajaran SET status='nonaktif'");
    $pdo->prepare("UPDATE tbl_tahun_ajaran SET status='aktif' WHERE id_ta=?")->execute([$_GET['aktifkan']]);
    flash('msg', 'Tahun ajaran berhasil diaktifkan!');
    header('Location: tahun_ajaran.php'); exit;
}
if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_tahun_ajaran WHERE id_ta=?")->execute([$_GET['hapus']]);
    flash('msg', 'Berhasil dihapus!', 'warning');
    header('Location: tahun_ajaran.php'); exit;
}

$data = $pdo->query("SELECT * FROM tbl_tahun_ajaran ORDER BY id_ta DESC")->fetchAll();
if (isset($_GET['edit'])) { $stmt = $pdo->prepare("SELECT * FROM tbl_tahun_ajaran WHERE id_ta=?"); $stmt->execute([(int)$_GET['edit']]); $edit = $stmt->fetch(); } else { $edit = null; }

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<?= alert_flash('msg') ?>

<!-- Form -->
<div class="glass rounded-xl p-5 mb-6">
    <h3 class="text-sm font-semibold mb-3"><?= $edit ? 'Edit' : 'Tambah' ?> Tahun Ajaran</h3>
    <form method="POST" class="flex flex-wrap gap-3 items-end">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id_ta'] ?>"><?php endif; ?>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs text-slate-400 mb-1">Tahun Ajaran</label>
            <input type="text" name="tahun" value="<?= clean($edit['tahun'] ?? '') ?>" required placeholder="2025/2026" pattern="\d{4}/\d{4}" title="Format: YYYY/YYYY contoh 2025/2026" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            <p class="text-[10px] text-slate-500 mt-1"><i class="fas fa-info-circle mr-1"></i>Format: YYYY/YYYY → Otomatis Juli s.d. Juni</p>
        </div>
        <button type="submit" name="<?= $edit ? 'update' : 'simpan' ?>" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium transition-colors"><i class="fas fa-save mr-1"></i><?= $edit ? 'Update' : 'Simpan' ?></button>
        <?php if ($edit): ?><a href="tahun_ajaran.php" class="bg-slate-600 hover:bg-slate-500 px-4 py-2 rounded-lg text-sm transition-colors">Batal</a><?php endif; ?>
    </form>
</div>

<!-- Table -->
<div class="glass rounded-xl p-5">
    <div class="table-container">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">Tahun Ajaran</th><th class="pb-3">Periode</th><th class="pb-3">Status</th><th class="pb-3">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($data as $i => $r): ?>
            <tr class="border-b border-white/5 hover:bg-white/5">
                <td class="py-2.5"><?= $i+1 ?></td>
                <td class="font-medium"><?= clean($r['tahun']) ?></td>
                <td class="text-slate-400">
                    <?php if ($r['tgl_mulai'] && $r['tgl_selesai']): ?>
                        <i class="fas fa-calendar-alt mr-1 text-blue-400"></i>
                        <?= date('M Y', strtotime($r['tgl_mulai'])) ?> — <?= date('M Y', strtotime($r['tgl_selesai'])) ?>
                    <?php else: ?>-<?php endif; ?>
                </td>
                <td><?php if ($r['status']=='aktif'): ?><span class="px-2 py-0.5 rounded-full text-xs bg-emerald-500/20 text-emerald-400">Aktif</span>
                    <?php else: ?><span class="px-2 py-0.5 rounded-full text-xs bg-slate-500/20 text-slate-400">Nonaktif</span><?php endif; ?></td>
                <td class="flex gap-1">
                    <?php if ($r['status']!='aktif'): ?><a href="?aktifkan=<?= $r['id_ta'] ?>" class="p-1.5 rounded bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600/40 text-xs" title="Aktifkan"><i class="fas fa-check"></i></a><?php endif; ?>
                    <a href="?edit=<?= $r['id_ta'] ?>" class="p-1.5 rounded bg-blue-600/20 text-blue-400 hover:bg-blue-600/40 text-xs"><i class="fas fa-edit"></i></a>
                    <button onclick="confirmDelete('?hapus=<?= $r['id_ta'] ?>','<?= clean($r['tahun']) ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 hover:bg-red-600/40 text-xs"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
