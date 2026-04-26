<?php
$page_title = 'Ekstrakurikuler';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin', 'waka_kesiswaan']);
cek_fitur('kesiswaan');
if (isset($_POST['simpan'])) { $pdo->prepare("INSERT INTO tbl_eskul (nama_eskul,hari,jam,pembina,id_guru) VALUES (?,?,?,?,?)")->execute([$_POST['nama'],$_POST['hari'],$_POST['jam'],$_POST['pembina'],$_POST['id_guru']??null]); flash('msg','Berhasil!'); header('Location: eskul.php'); exit; }
if (isset($_POST['enroll'])) { $pdo->prepare("INSERT IGNORE INTO tbl_eskul_anggota (id_eskul,id_siswa) VALUES (?,?)")->execute([$_POST['id_eskul'],$_POST['id_siswa']]); flash('msg','Siswa ditambahkan!'); header('Location: eskul.php?detail='.$_POST['id_eskul']); exit; }
if (isset($_GET['hapus'])) { $pdo->prepare("DELETE FROM tbl_eskul WHERE id_eskul=?")->execute([$_GET['hapus']]); flash('msg','Dihapus!','warning'); header('Location: eskul.php'); exit; }
if (isset($_GET['unenroll'])) { $pdo->prepare("DELETE FROM tbl_eskul_anggota WHERE id=?")->execute([$_GET['unenroll']]); header('Location: eskul.php?detail='.$_GET['eid']); exit; }

$guru = $pdo->query("SELECT * FROM tbl_guru WHERE status='Aktif' ORDER BY nama")->fetchAll();
$data = $pdo->query("SELECT * FROM tbl_eskul ORDER BY nama_eskul")->fetchAll();
if (isset($_GET['detail'])) { $stmt_d = $pdo->prepare("SELECT * FROM tbl_eskul WHERE id_eskul=?"); $stmt_d->execute([(int)$_GET['detail']]); $detail = $stmt_d->fetch(); } else { $detail = null; }
if ($detail) { $stmt_a = $pdo->prepare("SELECT ea.*,s.nama,s.nisn,k.nama_kelas FROM tbl_eskul_anggota ea JOIN tbl_siswa s ON ea.id_siswa=s.id_siswa LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE ea.id_eskul=? ORDER BY s.nama"); $stmt_a->execute([$detail['id_eskul']]); $anggota = $stmt_a->fetchAll(); } else { $anggota = []; }
$siswa = $pdo->query("SELECT * FROM tbl_siswa WHERE status='Aktif' ORDER BY nama")->fetchAll();

require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<?php if ($detail): ?>
<a href="eskul.php" class="text-blue-400 text-sm mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i>Kembali</a>
<div class="glass rounded-xl p-5 mb-6">
    <h3 class="font-bold text-lg"><?= clean($detail['nama_eskul']) ?></h3>
    <p class="text-sm text-slate-400"><?= $detail['hari'] ?> · <?= $detail['jam'] ?> · Pembina: <?= clean($detail['pembina']) ?></p>
</div>
<div class="glass rounded-xl p-5 mb-4"><form method="POST" class="flex gap-3 items-end">
    <input type="hidden" name="id_eskul" value="<?= $detail['id_eskul'] ?>">
    <div class="flex-1"><select name="id_siswa" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><?php foreach ($siswa as $s): ?><option value="<?= $s['id_siswa'] ?>"><?= clean($s['nama']) ?></option><?php endforeach; ?></select></div>
    <button type="submit" name="enroll" class="bg-emerald-600 hover:bg-emerald-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-plus mr-1"></i>Tambah Anggota</button>
</form></div>
<div class="glass rounded-xl p-5"><div class="table-container"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">Nama</th><th class="pb-3">Kelas</th><th class="pb-3">Aksi</th></tr></thead>
    <tbody><?php foreach ($anggota as $i => $a): ?>
    <tr class="border-b border-white/5"><td class="py-2"><?= $i+1 ?></td><td class="font-medium"><?= clean($a['nama']) ?></td><td><?= clean($a['nama_kelas']) ?></td>
    <td><button onclick="confirmDelete('?unenroll=<?= $a['id'] ?>&eid=<?= $detail['id_eskul'] ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 text-xs"><i class="fas fa-times"></i></button></td></tr>
    <?php endforeach; ?></tbody>
</table></div></div>

<?php else: ?>
<button onclick="document.getElementById('frm').classList.toggle('hidden')" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium mb-4"><i class="fas fa-plus mr-1"></i>Tambah</button>
<div id="frm" class="hidden glass rounded-xl p-5 mb-6"><form method="POST" class="flex flex-wrap gap-3 items-end">
    <div><label class="block text-xs text-slate-400 mb-1">Nama</label><input type="text" name="nama" required class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
    <div><label class="block text-xs text-slate-400 mb-1">Hari</label><input type="text" name="hari" placeholder="Selasa" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
    <div><label class="block text-xs text-slate-400 mb-1">Jam</label><input type="text" name="jam" placeholder="15:00-17:00" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
    <div><label class="block text-xs text-slate-400 mb-1">Pembina</label><input type="text" name="pembina" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
    <div><label class="block text-xs text-slate-400 mb-1">Guru</label><select name="id_guru" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><option value="">-</option><?php foreach ($guru as $g): ?><option value="<?= $g['id_guru'] ?>"><?= clean($g['nama']) ?></option><?php endforeach; ?></select></div>
    <button type="submit" name="simpan" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-save mr-1"></i>Simpan</button>
</form></div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php foreach ($data as $e): $stmt_c = $pdo->prepare("SELECT COUNT(*) FROM tbl_eskul_anggota WHERE id_eskul=?"); $stmt_c->execute([$e['id_eskul']]); $jml = $stmt_c->fetchColumn(); ?>
    <div class="glass rounded-xl p-5"><div class="flex justify-between items-start">
        <div><a href="?detail=<?= $e['id_eskul'] ?>" class="font-bold hover:text-blue-400"><?= clean($e['nama_eskul']) ?></a><p class="text-xs text-slate-400 mt-1"><?= $e['hari'] ?> · <?= $e['jam'] ?> · <?= clean($e['pembina']) ?></p><span class="text-xs text-blue-400"><?= $jml ?> anggota</span></div>
        <button onclick="confirmDelete('?hapus=<?= $e['id_eskul'] ?>')" class="text-red-400 text-xs"><i class="fas fa-trash"></i></button>
    </div></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../template/footer.php'; ?>
