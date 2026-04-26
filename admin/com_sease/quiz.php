<?php
$page_title = 'Quiz / Ujian Online';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','guru']);
cek_fitur('akademik');

if (isset($_POST['buat_quiz'])) { $pdo->prepare("INSERT INTO tbl_quiz (judul,id_mapel,id_kelas,id_guru,waktu_menit) VALUES (?,?,?,?,?)")->execute([$_POST['judul'],$_POST['id_mapel'],$_POST['id_kelas'],$_SESSION['user_id'],$_POST['waktu']]); flash('msg','Quiz dibuat!'); header('Location: quiz.php'); exit; }
if (isset($_POST['tambah_soal'])) { $pdo->prepare("INSERT INTO tbl_soal (id_quiz,pertanyaan,opsi_a,opsi_b,opsi_c,opsi_d,jawaban,bobot) VALUES (?,?,?,?,?,?,?,?)")->execute([$_POST['id_quiz'],$_POST['pertanyaan'],$_POST['a'],$_POST['b'],$_POST['c'],$_POST['d'],$_POST['jawaban'],$_POST['bobot']??1]); flash('msg','Soal ditambahkan!'); header('Location: quiz.php?detail='.$_POST['id_quiz']); exit; }
if (isset($_GET['status_quiz'])) { $pdo->prepare("UPDATE tbl_quiz SET status=? WHERE id_quiz=?")->execute([$_GET['status_quiz'],$_GET['id']]); header('Location: quiz.php'); exit; }
if (isset($_GET['hapus_soal'])) { $pdo->prepare("DELETE FROM tbl_soal WHERE id_soal=?")->execute([$_GET['hapus_soal']]); header('Location: quiz.php?detail='.$_GET['qid']); exit; }
if (isset($_GET['hapus'])) { $pdo->prepare("DELETE FROM tbl_quiz WHERE id_quiz=?")->execute([$_GET['hapus']]); flash('msg','Dihapus!','warning'); header('Location: quiz.php'); exit; }

$mapel = $pdo->query("SELECT * FROM tbl_mapel ORDER BY nama_mapel")->fetchAll();
$kelas = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();
$quizzes = $pdo->query("SELECT q.*,m.nama_mapel,k.nama_kelas FROM tbl_quiz q LEFT JOIN tbl_mapel m ON q.id_mapel=m.id_mapel LEFT JOIN tbl_kelas k ON q.id_kelas=k.id_kelas ORDER BY q.id_quiz DESC")->fetchAll();

if (isset($_GET['detail'])) { $stmt = $pdo->prepare("SELECT q.*,m.nama_mapel,k.nama_kelas FROM tbl_quiz q LEFT JOIN tbl_mapel m ON q.id_mapel=m.id_mapel LEFT JOIN tbl_kelas k ON q.id_kelas=k.id_kelas WHERE q.id_quiz=?"); $stmt->execute([(int)$_GET['detail']]); $detail = $stmt->fetch(); } else { $detail = null; }
if ($detail) { $stmt = $pdo->prepare("SELECT * FROM tbl_soal WHERE id_quiz=? ORDER BY id_soal"); $stmt->execute([$detail['id_quiz']]); $soal = $stmt->fetchAll(); } else { $soal = []; }

require_once __DIR__ . '/../../template/header.php'; require_once __DIR__ . '/../../template/sidebar.php'; require_once __DIR__ . '/../../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<?php if ($detail): ?>
<!-- Detail Quiz -->
<a href="quiz.php" class="text-blue-400 text-sm mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i>Kembali</a>
<div class="glass rounded-xl p-5 mb-6">
    <h3 class="font-bold text-lg"><?= clean($detail['judul']) ?></h3>
    <p class="text-sm text-slate-400"><?= clean($detail['nama_mapel']) ?> · <?= clean($detail['nama_kelas']) ?> · <?= $detail['waktu_menit'] ?> menit</p>
    <span class="px-2 py-0.5 rounded-full text-xs <?= $detail['status']=='Aktif'?'bg-emerald-500/20 text-emerald-400':($detail['status']=='Selesai'?'bg-slate-500/20 text-slate-400':'bg-amber-500/20 text-amber-400') ?>"><?= $detail['status'] ?></span>
</div>

<!-- Add Soal -->
<div class="glass rounded-xl p-5 mb-6">
    <h4 class="text-sm font-semibold mb-3">Tambah Soal</h4>
    <form method="POST" class="space-y-3">
        <input type="hidden" name="id_quiz" value="<?= $detail['id_quiz'] ?>">
        <div><textarea name="pertanyaan" required placeholder="Pertanyaan" rows="2" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></textarea></div>
        <div class="grid grid-cols-2 gap-3">
            <input type="text" name="a" required placeholder="A." class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
            <input type="text" name="b" required placeholder="B." class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
            <input type="text" name="c" required placeholder="C." class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
            <input type="text" name="d" required placeholder="D." class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="flex gap-3"><select name="jawaban" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><option value="A">Jawaban: A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option></select><input type="number" name="bobot" value="1" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm w-20" placeholder="Bobot">
        <button type="submit" name="tambah_soal" class="bg-emerald-600 hover:bg-emerald-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-plus mr-1"></i>Tambah</button></div>
    </form>
</div>

<!-- Soal List -->
<?php foreach ($soal as $i => $s): ?>
<div class="glass rounded-xl p-5 mb-3">
    <div class="flex justify-between items-start"><span class="text-xs text-slate-400">Soal <?= $i+1 ?> (Bobot: <?= $s['bobot'] ?>)</span><button onclick="confirmDelete('?hapus_soal=<?= $s['id_soal'] ?>&qid=<?= $detail['id_quiz'] ?>')" class="text-red-400 text-xs"><i class="fas fa-trash"></i></button></div>
    <p class="font-medium my-2"><?= clean($s['pertanyaan']) ?></p>
    <div class="grid grid-cols-2 gap-2 text-sm">
        <div class="p-2 rounded <?= $s['jawaban']=='A'?'bg-emerald-500/20 text-emerald-400':'bg-white/5' ?>">A. <?= clean($s['opsi_a']) ?></div>
        <div class="p-2 rounded <?= $s['jawaban']=='B'?'bg-emerald-500/20 text-emerald-400':'bg-white/5' ?>">B. <?= clean($s['opsi_b']) ?></div>
        <div class="p-2 rounded <?= $s['jawaban']=='C'?'bg-emerald-500/20 text-emerald-400':'bg-white/5' ?>">C. <?= clean($s['opsi_c']) ?></div>
        <div class="p-2 rounded <?= $s['jawaban']=='D'?'bg-emerald-500/20 text-emerald-400':'bg-white/5' ?>">D. <?= clean($s['opsi_d']) ?></div>
    </div>
</div>
<?php endforeach; ?>

<?php else: ?>
<!-- Quiz List -->
<button onclick="document.getElementById('frmQuiz').classList.toggle('hidden')" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium mb-4"><i class="fas fa-plus mr-1"></i>Buat Quiz</button>
<div id="frmQuiz" class="hidden glass rounded-xl p-5 mb-6">
    <form method="POST" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]"><label class="block text-xs text-slate-400 mb-1">Judul</label><input type="text" name="judul" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Mapel</label><select name="id_mapel" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><?php foreach ($mapel as $m): ?><option value="<?= $m['id_mapel'] ?>"><?= clean($m['nama_mapel']) ?></option><?php endforeach; ?></select></div>
        <div><label class="block text-xs text-slate-400 mb-1">Kelas</label><select name="id_kelas" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><?php foreach ($kelas as $k): ?><option value="<?= $k['id_kelas'] ?>"><?= clean($k['nama_kelas']) ?></option><?php endforeach; ?></select></div>
        <div><label class="block text-xs text-slate-400 mb-1">Waktu (menit)</label><input type="number" name="waktu" value="60" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm w-24"></div>
        <button type="submit" name="buat_quiz" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-save mr-1"></i>Buat</button>
    </form>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php foreach ($quizzes as $q): $stmt_c = $pdo->prepare("SELECT COUNT(*) FROM tbl_soal WHERE id_quiz=?"); $stmt_c->execute([$q['id_quiz']]); $jml_soal = $stmt_c->fetchColumn(); ?>
    <div class="glass rounded-xl p-5">
        <div class="flex justify-between items-start mb-2">
            <span class="px-2 py-0.5 rounded-full text-xs <?= $q['status']=='Aktif'?'bg-emerald-500/20 text-emerald-400':($q['status']=='Selesai'?'bg-slate-500/20 text-slate-400':'bg-amber-500/20 text-amber-400') ?>"><?= $q['status'] ?></span>
            <div class="flex gap-1">
                <?php if ($q['status']=='Draft'): ?><a href="?status_quiz=Aktif&id=<?= $q['id_quiz'] ?>" class="text-xs text-emerald-400"><i class="fas fa-play"></i></a><?php endif; ?>
                <?php if ($q['status']=='Aktif'): ?><a href="?status_quiz=Selesai&id=<?= $q['id_quiz'] ?>" class="text-xs text-amber-400"><i class="fas fa-stop"></i></a><?php endif; ?>
                <button onclick="confirmDelete('?hapus=<?= $q['id_quiz'] ?>')" class="text-xs text-red-400"><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <a href="?detail=<?= $q['id_quiz'] ?>" class="font-bold hover:text-blue-400"><?= clean($q['judul']) ?></a>
        <p class="text-xs text-slate-400 mt-1"><?= clean($q['nama_mapel']) ?> · <?= clean($q['nama_kelas']) ?> · <?= $q['waktu_menit'] ?> menit · <?= $jml_soal ?> soal</p>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
