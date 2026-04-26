<?php
$page_title = 'Kelulusan';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);

$kelas_list = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();

if (isset($_POST['proses'])) {
    $ids = $_POST['siswa'] ?? [];
    foreach ($ids as $id) { $pdo->prepare("UPDATE tbl_siswa SET status='Lulus' WHERE id_siswa=?")->execute([$id]); }
    flash('msg', count($ids).' siswa berhasil diluluskan!'); header('Location: kelulusan.php'); exit;
}

$kelas = (int)($_GET['kelas'] ?? 0);
if ($kelas) { $stmt = $pdo->prepare("SELECT * FROM tbl_siswa WHERE id_kelas=? AND status='Aktif' ORDER BY nama"); $stmt->execute([$kelas]); $siswa = $stmt->fetchAll(); } else { $siswa = []; }

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<div class="glass rounded-xl p-5 mb-6">
    <form method="GET" class="flex gap-3 items-end">
        <div><label class="block text-xs text-slate-400 mb-1">Pilih Kelas</label>
            <select name="kelas" onchange="this.form.submit()" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
                <option value="">-- Pilih --</option>
                <?php foreach ($kelas_list as $k): ?><option value="<?= $k['id_kelas'] ?>" <?= $kelas==$k['id_kelas']?'selected':'' ?>><?= clean($k['nama_kelas']) ?></option><?php endforeach; ?>
            </select></div>
    </form>
</div>

<?php if ($kelas && count($siswa) > 0): ?>
<form method="POST" class="glass rounded-xl p-5">
    <div class="flex items-center justify-between mb-4">
        <label class="flex items-center gap-2 text-sm text-slate-400 cursor-pointer"><input type="checkbox" onclick="document.querySelectorAll('[name=\'siswa[]\']').forEach(c=>c.checked=this.checked)"> Pilih Semua</label>
        <button type="submit" name="proses" class="bg-amber-600 hover:bg-amber-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-graduation-cap mr-1"></i>Proses Kelulusan</button>
    </div>
    <?php foreach ($siswa as $s): ?>
    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-white/5 cursor-pointer border-b border-white/5">
        <input type="checkbox" name="siswa[]" value="<?= $s['id_siswa'] ?>">
        <span class="text-sm font-medium"><?= clean($s['nama']) ?></span>
        <span class="text-xs text-slate-500">NISN: <?= clean($s['nisn']) ?></span>
    </label>
    <?php endforeach; ?>
</form>
<?php endif; ?>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
