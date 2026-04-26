<?php
$page_title = 'Kenaikan Kelas';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);

$kelas_list = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();

if (isset($_POST['proses'])) {
    $dari = $_POST['dari_kelas']; $ke = $_POST['ke_kelas'];
    $siswa_ids = $_POST['siswa'] ?? [];
    foreach ($siswa_ids as $id) {
        $pdo->prepare("UPDATE tbl_siswa SET id_kelas=? WHERE id_siswa=?")->execute([$ke, $id]);
    }
    flash('msg', count($siswa_ids).' siswa berhasil dinaikkan!');
    header('Location: kenaikan_kelas.php'); exit;
}

$dari_kelas = (int)($_GET['dari'] ?? 0);
$siswa_list = [];
if ($dari_kelas) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_siswa WHERE id_kelas=? AND status='Aktif' ORDER BY nama"); $stmt->execute([$dari_kelas]); $siswa_list = $stmt->fetchAll();
}

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<div class="glass rounded-xl p-5 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div><label class="block text-xs text-slate-400 mb-1">Dari Kelas</label>
            <select name="dari" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
                <option value="">-- Pilih --</option>
                <?php foreach ($kelas_list as $k): ?><option value="<?= $k['id_kelas'] ?>" <?= $dari_kelas==$k['id_kelas']?'selected':'' ?>><?= clean($k['nama_kelas']) ?></option><?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($dari_kelas && count($siswa_list) > 0): ?>
<form method="POST">
    <input type="hidden" name="dari_kelas" value="<?= $dari_kelas ?>">
    <div class="glass rounded-xl p-5 mb-4">
        <div class="flex items-center gap-4 mb-4">
            <label class="text-sm">Naikkan ke:</label>
            <select name="ke_kelas" required class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
                <?php foreach ($kelas_list as $k): ?><option value="<?= $k['id_kelas'] ?>"><?= clean($k['nama_kelas']) ?></option><?php endforeach; ?>
            </select>
            <button type="submit" name="proses" class="bg-emerald-600 hover:bg-emerald-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-check mr-1"></i>Proses Kenaikan</button>
        </div>
        <div class="space-y-2">
            <label class="flex items-center gap-2 text-sm text-slate-400 cursor-pointer"><input type="checkbox" onclick="document.querySelectorAll('[name=\'siswa[]\']').forEach(c=>c.checked=this.checked)"> Pilih Semua</label>
            <?php foreach ($siswa_list as $s): ?>
            <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-white/5 cursor-pointer">
                <input type="checkbox" name="siswa[]" value="<?= $s['id_siswa'] ?>" class="rounded">
                <span class="text-sm"><?= clean($s['nama']) ?></span>
                <span class="text-xs text-slate-500">NISN: <?= clean($s['nisn']) ?></span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
</form>
<?php elseif ($dari_kelas): ?>
<div class="glass rounded-xl p-5 text-center text-slate-400">Tidak ada siswa di kelas ini.</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
