<?php
$page_title = 'Absensi Kelas';
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
cek_fitur('absensi');
$id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT DISTINCT k.* FROM tbl_jadwal j JOIN tbl_kelas k ON j.id_kelas=k.id_kelas WHERE j.id_guru=? ORDER BY k.nama_kelas"); $stmt->execute([$id]); $my_kelas = $stmt->fetchAll();
$sel = (int)($_GET['kelas'] ?? 0); $tgl = $_GET['tgl'] ?? date('Y-m-d');
if (isset($_POST['simpan_absen'])) {
    foreach ($_POST['status'] as $id_siswa => $status) {
        $jam = date('H:i:s');
        
        // Prevent overwriting attendance made by RFID, QR, or Aplikasi
        $chk = $pdo->prepare("SELECT metode FROM tbl_absensi_siswa WHERE id_siswa=? AND tanggal=?");
        $chk->execute([$id_siswa, $_POST['tanggal']]);
        $curr_metode = $chk->fetchColumn();
        if ($curr_metode && strtolower($curr_metode) !== 'manual') {
            continue; // Skip
        }

        $pdo->prepare("INSERT INTO tbl_absensi_siswa (id_siswa,tanggal,jam_masuk,status,keterangan,metode) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE keterangan=?")->execute([$id_siswa,$_POST['tanggal'],$jam,'COMPLETE',$status,'Manual',$status]);
    }
    flash('msg','Absensi berhasil disimpan!'); header('Location: absensi.php?kelas='.$_POST['id_kelas'].'&tgl='.$_POST['tanggal']); exit;
}
if ($sel) { $stmt = $pdo->prepare("SELECT * FROM tbl_siswa WHERE id_kelas=? AND status='Aktif' ORDER BY nama"); $stmt->execute([$sel]); $siswa = $stmt->fetchAll(); } else { $siswa = []; }
require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<div class="glass rounded-xl p-5 mb-6"><form method="GET" class="flex flex-wrap gap-3 items-end">
    <div><label class="block text-xs text-slate-400 mb-1">Kelas</label><select name="kelas" onchange="this.form.submit()" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><option value="">-- Pilih --</option><?php foreach ($my_kelas as $k): ?><option value="<?= $k['id_kelas'] ?>" <?= $sel==$k['id_kelas']?'selected':'' ?>><?= clean($k['nama_kelas']) ?></option><?php endforeach; ?></select></div>
    <div><label class="block text-xs text-slate-400 mb-1">Tanggal</label><input type="date" name="tgl" value="<?= $tgl ?>" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()"></div>
</form></div>
<?php if ($siswa): ?>
<form method="POST" class="glass rounded-xl p-5">
    <input type="hidden" name="id_kelas" value="<?= $sel ?>"><input type="hidden" name="tanggal" value="<?= $tgl ?>">
    <div class="space-y-2"><?php foreach ($siswa as $s):
        $stmt_ex = $pdo->prepare("SELECT * FROM tbl_absensi_siswa WHERE id_siswa=? AND tanggal=?"); 
        $stmt_ex->execute([$s['id_siswa'], $tgl]); 
        $ex_data = $stmt_ex->fetch(); 
        $existing = $ex_data ? $ex_data['keterangan'] : ''; 
        $metode = $ex_data ? ($ex_data['metode'] ?: 'Manual') : '';
        $is_locked = ($metode && strtolower($metode) !== 'manual');
    ?>
    <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg border border-white/5 hover:bg-white/10 transition-colors">
        <span class="font-medium text-sm text-slate-200"><?= clean($s['nama']) ?></span>
        <?php if ($is_locked): 
            $c_badge = ($existing=='Izin'||$existing=='Sakit') ? 'bg-amber-500/20 text-amber-400 border-amber-500/30' : 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30';
        ?>
            <div class="px-3 py-1 bg-black/30 border <?= $c_badge ?> rounded text-xs flex items-center gap-2">
                <i class="fas fa-lock opacity-50"></i>
                <span class="font-bold"><?= strtoupper($existing) ?></span>
                <span class="text-[9px] px-1.5 rounded-full bg-white/10"><?= $metode ?></span>
            </div>
        <?php else: ?>
            <select name="status[<?= $s['id_siswa'] ?>]" class="bg-slate-800/80 border border-white/10 rounded-lg px-3 py-1.5 text-sm outline-none focus:border-blue-500 transition-colors">
                <?php foreach (['Alpha'=>'Alpha','Hadir'=>'Hadir','Izin'=>'Izin','Sakit'=>'Sakit','Tepat Waktu'=>'Tepat Waktu','Terlambat'=>'Terlambat'] as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= $existing==$k?'selected':'' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
    </div>
    <?php endforeach; ?></div>
    <button type="submit" name="simpan_absen" class="bg-emerald-600 hover:bg-emerald-500 px-6 py-2 rounded-lg text-sm font-medium mt-4 w-full"><i class="fas fa-save mr-1"></i>Simpan Absensi</button>
</form>
<?php endif; ?>
<?php require_once __DIR__ . '/../template/footer.php'; ?>
