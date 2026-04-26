<?php
// Guru quiz management - redirects to admin quiz with guru context
$page_title = 'Quiz / Ujian';
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
cek_fitur('akademik');
$id = $_SESSION['user_id'];

if (isset($_POST['buat_quiz'])) { $pdo->prepare("INSERT INTO tbl_quiz (judul,id_mapel,id_kelas,id_guru,waktu_menit) VALUES (?,?,?,?,?)")->execute([$_POST['judul'],$_POST['id_mapel'],$_POST['id_kelas'],$id,$_POST['waktu']]); flash('msg','Quiz dibuat!'); header('Location: quiz.php'); exit; }
if (isset($_POST['tambah_soal'])) { $pdo->prepare("INSERT INTO tbl_soal (id_quiz,pertanyaan,opsi_a,opsi_b,opsi_c,opsi_d,jawaban,bobot) VALUES (?,?,?,?,?,?,?,?)")->execute([$_POST['id_quiz'],$_POST['pertanyaan'],$_POST['a'],$_POST['b'],$_POST['c'],$_POST['d'],$_POST['jawaban'],$_POST['bobot']??1]); flash('msg','Soal ditambahkan!'); header('Location: quiz.php?detail='.$_POST['id_quiz']); exit; }
if (isset($_GET['hapus'])) { 
    $id_q = $_GET['hapus'];
    $pdo->prepare("DELETE FROM tbl_jawaban_siswa WHERE id_quiz=?")->execute([$id_q]);
    $pdo->prepare("DELETE FROM tbl_soal WHERE id_quiz=?")->execute([$id_q]);
    $pdo->prepare("DELETE FROM tbl_quiz WHERE id_quiz=? AND id_guru=?")->execute([$id_q,$id]); 
    flash('msg','Quiz berhasil dihapus!','warning'); 
    header('Location: quiz.php'); 
    exit; 
}

$stmt = $pdo->prepare("SELECT DISTINCT m.* FROM tbl_jadwal j JOIN tbl_mapel m ON j.id_mapel=m.id_mapel WHERE j.id_guru=?"); $stmt->execute([$id]); $mapel = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT DISTINCT k.* FROM tbl_jadwal j JOIN tbl_kelas k ON j.id_kelas=k.id_kelas WHERE j.id_guru=?"); $stmt->execute([$id]); $kelas = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT q.*,m.nama_mapel,k.nama_kelas FROM tbl_quiz q LEFT JOIN tbl_mapel m ON q.id_mapel=m.id_mapel LEFT JOIN tbl_kelas k ON q.id_kelas=k.id_kelas WHERE q.id_guru=? ORDER BY q.id_quiz DESC"); $stmt->execute([$id]); $quizzes = $stmt->fetchAll();
if (isset($_GET['detail'])) { $stmt = $pdo->prepare("SELECT * FROM tbl_quiz WHERE id_quiz=? AND id_guru=?"); $stmt->execute([(int)$_GET['detail'], $id]); $detail = $stmt->fetch(); } else { $detail = null; }
if ($detail) { $stmt = $pdo->prepare("SELECT * FROM tbl_soal WHERE id_quiz=? ORDER BY id_soal"); $stmt->execute([$detail['id_quiz']]); $soal = $stmt->fetchAll(); } else { $soal = []; }

require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<?php if ($detail): ?>
<a href="quiz.php" class="text-blue-400 text-sm mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i>Kembali</a>
<div class="glass rounded-xl p-5 mb-6">
    <h3 class="font-bold text-lg"><?= clean($detail['judul']) ?></h3>
    <p class="text-sm text-slate-400"><?= $detail['waktu_menit'] ?> menit · <?= count($soal) ?> soal</p>
</div>
<div class="glass rounded-xl p-5 mb-6"><h4 class="text-sm font-semibold mb-3">Tambah Soal</h4>
    <form method="POST" class="space-y-3"><input type="hidden" name="id_quiz" value="<?= $detail['id_quiz'] ?>">
        <textarea name="pertanyaan" required placeholder="Pertanyaan" rows="2" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></textarea>
        <div class="grid grid-cols-2 gap-3"><input type="text" name="a" required placeholder="A." class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><input type="text" name="b" required placeholder="B." class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><input type="text" name="c" required placeholder="C." class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><input type="text" name="d" required placeholder="D." class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        <div class="flex gap-3"><select name="jawaban" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><option>A</option><option>B</option><option>C</option><option>D</option></select><input type="number" name="bobot" value="1" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm w-20"><button type="submit" name="tambah_soal" class="bg-emerald-600 hover:bg-emerald-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-plus mr-1"></i>Tambah</button></div>
    </form>
</div>
<?php foreach ($soal as $i => $s): ?>
<div class="glass rounded-xl p-4 mb-3"><p class="text-xs text-slate-400 mb-1">Soal <?= $i+1 ?></p><p class="font-medium"><?= clean($s['pertanyaan']) ?></p>
    <div class="grid grid-cols-2 gap-2 mt-2 text-sm"><?php foreach (['A'=>$s['opsi_a'],'B'=>$s['opsi_b'],'C'=>$s['opsi_c'],'D'=>$s['opsi_d']] as $k=>$v): ?><div class="p-2 rounded <?= $s['jawaban']==$k?'bg-emerald-500/20 text-emerald-400':'bg-white/5' ?>"><?= $k ?>. <?= clean($v) ?></div><?php endforeach; ?></div>
</div>
<?php endforeach; ?>

<?php else: ?>
<button onclick="document.getElementById('frmQ').classList.toggle('hidden')" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium mb-4"><i class="fas fa-plus mr-1"></i>Buat Quiz</button>
<div id="frmQ" class="hidden glass rounded-xl p-5 mb-6"><form method="POST" class="flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[200px]"><label class="block text-xs text-slate-400 mb-1">Judul</label><input type="text" name="judul" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
    <div><label class="block text-xs text-slate-400 mb-1">Mapel</label><select name="id_mapel" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><?php foreach ($mapel as $m): ?><option value="<?= $m['id_mapel'] ?>"><?= clean($m['nama_mapel']) ?></option><?php endforeach; ?></select></div>
    <div><label class="block text-xs text-slate-400 mb-1">Kelas</label><select name="id_kelas" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><?php foreach ($kelas as $k): ?><option value="<?= $k['id_kelas'] ?>"><?= clean($k['nama_kelas']) ?></option><?php endforeach; ?></select></div>
    <div><label class="block text-xs text-slate-400 mb-1">Menit</label><input type="number" name="waktu" value="60" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm w-20"></div>
    <button type="submit" name="buat_quiz" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-save mr-1"></i>Buat</button>
</form></div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4"><?php foreach ($quizzes as $q): $stmt_c = $pdo->prepare("SELECT COUNT(*) FROM tbl_soal WHERE id_quiz=?"); $stmt_c->execute([$q['id_quiz']]); $jml = $stmt_c->fetchColumn(); ?>
<div class="glass rounded-xl p-5"><a href="?detail=<?= $q['id_quiz'] ?>" class="font-bold hover:text-blue-400"><?= clean($q['judul']) ?></a><p class="text-xs text-slate-400 mt-1"><?= clean($q['nama_mapel']) ?> · <?= clean($q['nama_kelas']) ?> · <?= $jml ?> soal</p>
<div class="flex gap-2 mt-2"><span class="px-2 py-0.5 rounded-full text-xs <?= $q['status']=='Aktif'?'bg-emerald-500/20 text-emerald-400':'bg-amber-500/20 text-amber-400' ?>"><?= $q['status'] ?></span><button onclick="confirmDelete('?hapus=<?= $q['id_quiz'] ?>')" class="text-xs text-red-400"><i class="fas fa-trash"></i></button></div></div>
<?php endforeach; ?></div>
<?php endif; ?>
<?php require_once __DIR__ . '/../template/footer.php'; ?>
