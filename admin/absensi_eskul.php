<?php
$page_title = 'Absensi Eskul';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin','guru']);
cek_fitur('absensi');
$eskul_list = $pdo->query("SELECT * FROM tbl_eskul ORDER BY nama_eskul")->fetchAll();
$sel = (int)($_GET['eskul'] ?? 0); $tgl = $_GET['tgl'] ?? date('Y-m-d');
if (isset($_POST['simpan_absen'])) {
    foreach ($_POST['status'] as $id_siswa => $status) {
        $pdo->prepare("INSERT INTO tbl_absensi_eskul (id_eskul,id_siswa,tanggal,status) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE status=?")->execute([$_POST['id_eskul'],$id_siswa,$_POST['tanggal'],$status,$status]);
    }
    flash('msg','Absensi berhasil!');
    header('Location: absensi_eskul.php?eskul='.$_POST['id_eskul'].'&tgl='.$_POST['tanggal']); exit;
}
if ($sel) { $stmt = $pdo->prepare("SELECT ea.id_siswa,s.nama FROM tbl_eskul_anggota ea JOIN tbl_siswa s ON ea.id_siswa=s.id_siswa WHERE ea.id_eskul=? ORDER BY s.nama"); $stmt->execute([$sel]); $anggota = $stmt->fetchAll(); } else { $anggota = []; }
require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<div class="glass rounded-xl p-5 mb-6"><form method="GET" class="flex flex-wrap gap-3 items-end">
    <div><label class="block text-xs text-slate-400 mb-1">Eskul</label><select name="eskul" onchange="this.form.submit()" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><option value="">-- Pilih --</option><?php foreach ($eskul_list as $e): ?><option value="<?= $e['id_eskul'] ?>" <?= $sel==$e['id_eskul']?'selected':'' ?>><?= clean($e['nama_eskul']) ?></option><?php endforeach; ?></select></div>
    <div><label class="block text-xs text-slate-400 mb-1">Tanggal</label><input type="date" name="tgl" value="<?= $tgl ?>" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()"></div>
</form></div>
<?php if ($anggota): ?>
<form method="POST" class="glass rounded-xl p-5">
    <input type="hidden" name="id_eskul" value="<?= $sel ?>"><input type="hidden" name="tanggal" value="<?= $tgl ?>">
    <div class="space-y-2"><?php foreach ($anggota as $a):
        $stmt_ex = $pdo->prepare("SELECT status FROM tbl_absensi_eskul WHERE id_eskul=? AND id_siswa=? AND tanggal=?"); $stmt_ex->execute([$sel, $a['id_siswa'], $tgl]); $existing = $stmt_ex->fetchColumn(); ?>
    <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
        <span class="font-medium text-sm"><?= clean($a['nama']) ?></span>
        <select name="status[<?= $a['id_siswa'] ?>]" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-1 text-sm">
            <?php foreach (['Hadir','Izin','Sakit','Alpha'] as $st): ?><option value="<?= $st ?>" <?= $existing==$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?>
        </select>
    </div>
    <?php endforeach; ?></div>
    <button type="submit" name="simpan_absen" class="bg-emerald-600 hover:bg-emerald-500 px-6 py-2 rounded-lg text-sm font-medium mt-4"><i class="fas fa-save mr-1"></i>Simpan Absensi</button>
</form>
<?php endif; ?>
<?php require_once __DIR__ . '/../template/footer.php'; ?>
