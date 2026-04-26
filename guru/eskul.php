<?php
$page_title = 'Eskul Saya';
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
cek_fitur('kesiswaan');
$id = $_SESSION['user_id'];

// Show eskul where guru is pembina
$stmt = $pdo->prepare("SELECT e.*,(SELECT COUNT(*) FROM tbl_eskul_anggota WHERE id_eskul=e.id_eskul) as jml FROM tbl_eskul e WHERE e.id_guru=? ORDER BY e.nama_eskul");
$stmt->execute([$id]);
$my_eskul = $stmt->fetchAll();

// Also show all eskul
$all_eskul = $pdo->query("SELECT e.*,(SELECT COUNT(*) FROM tbl_eskul_anggota WHERE id_eskul=e.id_eskul) as jml FROM tbl_eskul e ORDER BY e.nama_eskul")->fetchAll();

require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>

<?php if ($my_eskul): ?>
<h3 class="text-sm font-semibold text-blue-400 mb-3"><i class="fas fa-star mr-1"></i>Eskul Saya (Pembina)</h3>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8"><?php foreach ($my_eskul as $e): ?>
<div class="glass rounded-xl p-5 border-l-4 border-blue-500">
    <div class="flex justify-between items-start">
        <div>
            <h4 class="font-bold text-lg"><?= clean($e['nama_eskul']) ?></h4>
            <p class="text-xs text-slate-400 mt-1"><i class="fas fa-calendar mr-1"></i><?= $e['hari'] ?> · <i class="fas fa-clock mr-1"></i><?= $e['jam'] ?></p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs bg-blue-500/20 text-blue-400 font-medium"><?= $e['jml'] ?> anggota</span>
    </div>
    <div class="flex gap-2 mt-4">
        <a href="<?= BASE_URL ?>admin/absensi_eskul.php?eskul=<?= $e['id_eskul'] ?>" class="flex-1 text-center bg-emerald-600/20 hover:bg-emerald-600/40 text-emerald-400 px-3 py-2 rounded-lg text-xs font-medium transition-colors">
            <i class="fas fa-user-check mr-1"></i>Absensi
        </a>
        <a href="<?= BASE_URL ?>admin/eskul.php?detail=<?= $e['id_eskul'] ?>" class="flex-1 text-center bg-purple-600/20 hover:bg-purple-600/40 text-purple-400 px-3 py-2 rounded-lg text-xs font-medium transition-colors">
            <i class="fas fa-users mr-1"></i>Anggota
        </a>
    </div>
</div>
<?php endforeach; ?></div>
<?php endif; ?>

<h3 class="text-sm font-semibold text-slate-400 mb-3"><i class="fas fa-futbol mr-1"></i>Semua Eskul</h3>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"><?php foreach ($all_eskul as $e): ?>
<div class="glass rounded-xl p-5 hover:bg-white/5 transition-colors">
    <h4 class="font-bold"><?= clean($e['nama_eskul']) ?></h4>
    <p class="text-xs text-slate-400 mt-1"><?= $e['hari'] ?> · <?= $e['jam'] ?> · Pembina: <?= clean($e['pembina']) ?></p>
    <span class="text-xs text-blue-400 mt-2 inline-block"><?= $e['jml'] ?> anggota</span>
</div>
<?php endforeach; ?></div>
<?php if (empty($all_eskul)): ?><div class="glass rounded-xl p-5 text-center text-slate-400">Belum ada data eskul.</div><?php endif; ?>
<?php require_once __DIR__ . '/../template/footer.php'; ?>
