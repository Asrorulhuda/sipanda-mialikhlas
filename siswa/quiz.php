<?php
$page_title = 'Quiz / Ujian';
require_once __DIR__ . '/../config/init.php';
cek_role(['siswa']);
cek_fitur('akademik');
$id = $_SESSION['user_id']; $kelas = $_SESSION['id_kelas'] ?? 0;

if (isset($_POST['submit_quiz'])) {
    $quiz_id = (int)$_POST['id_quiz'];
    
    // Cek apakah siswa sudah mengerjakan sebelumnya
    $stmt_cek = $pdo->prepare("SELECT 1 FROM tbl_jawaban_siswa WHERE id_quiz=? AND id_siswa=?");
    $stmt_cek->execute([$quiz_id, $id]);
    if ($stmt_cek->fetch()) {
        flash('msg', 'Anda sudah mengerjakan quiz ini!', 'warning');
        header('Location: quiz.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM tbl_soal WHERE id_quiz=?"); $stmt->execute([$quiz_id]); $soal_list = $stmt->fetchAll();
    $total_skor = 0;
    foreach ($soal_list as $s) {
        $jawaban = $_POST['jawaban'][$s['id_soal']] ?? '-'; // Gunakan strip jika kosong
        $skor = ($jawaban == $s['jawaban']) ? $s['bobot'] : 0;
        $total_skor += $skor;
        $pdo->prepare("INSERT INTO tbl_jawaban_siswa (id_quiz,id_siswa,id_soal,jawaban,skor) VALUES (?,?,?,?,?)")->execute([$quiz_id,$id,$s['id_soal'],$jawaban,$skor]);
    }
    flash('msg',"Quiz selesai! Anda telah menyelesaikan <b>$total_skor</b> skor."); header('Location: quiz.php'); exit;
}

$kelas = (int)$kelas;
$stmt = $pdo->prepare("SELECT q.*,m.nama_mapel FROM tbl_quiz q LEFT JOIN tbl_mapel m ON q.id_mapel=m.id_mapel WHERE q.id_kelas=? AND q.status='Aktif' ORDER BY q.id_quiz DESC"); $stmt->execute([$kelas]); $quizzes = $stmt->fetchAll();
if (isset($_GET['take'])) { $stmt = $pdo->prepare("SELECT q.*,m.nama_mapel FROM tbl_quiz q LEFT JOIN tbl_mapel m ON q.id_mapel=m.id_mapel WHERE q.id_quiz=? AND q.status='Aktif'"); $stmt->execute([(int)$_GET['take']]); $detail = $stmt->fetch(); } else { $detail = null; }
if ($detail) { $stmt = $pdo->prepare("SELECT 1 FROM tbl_jawaban_siswa WHERE id_quiz=? AND id_siswa=?"); $stmt->execute([$detail['id_quiz'], $id]); $already = $stmt->fetch(); } else { $already = false; }
if ($detail && !$already) { $stmt = $pdo->prepare("SELECT * FROM tbl_soal WHERE id_quiz=? ORDER BY id_soal"); $stmt->execute([$detail['id_quiz']]); $soal = $stmt->fetchAll(); } else { $soal = []; }

require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<?php if ($detail && !$already && $soal): ?>
<a href="quiz.php" class="text-blue-400 text-sm mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i>Kembali</a>
<div class="glass rounded-xl p-5 mb-6">
    <h3 class="font-bold text-lg"><?= clean($detail['judul']) ?></h3>
    <p class="text-sm text-slate-400"><?= clean($detail['nama_mapel']) ?> · <?= $detail['waktu_menit'] ?> menit · <?= count($soal) ?> soal</p>
</div>
<form method="POST">
    <input type="hidden" name="id_quiz" value="<?= $detail['id_quiz'] ?>">
    <?php foreach ($soal as $i => $s): ?>
    <div class="glass rounded-xl p-5 mb-4">
        <p class="font-medium mb-3"><span class="text-blue-400"><?= $i+1 ?>.</span> <?= clean($s['pertanyaan']) ?></p>
        <div class="space-y-2">
            <?php foreach (['A'=>$s['opsi_a'],'B'=>$s['opsi_b'],'C'=>$s['opsi_c'],'D'=>$s['opsi_d']] as $k => $v): ?>
            <label class="flex items-center gap-3 p-3 bg-white/5 rounded-lg cursor-pointer hover:bg-white/10">
                <input type="radio" name="jawaban[<?= $s['id_soal'] ?>]" value="<?= $k ?>" class="text-blue-500">
                <span class="text-sm"><?= $k ?>. <?= clean($v) ?></span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <button type="submit" name="submit_quiz" class="bg-emerald-600 hover:bg-emerald-500 px-8 py-3 rounded-lg text-sm font-medium" onclick="return confirm('Yakin kirim jawaban?')"><i class="fas fa-paper-plane mr-2"></i>Kirim Jawaban</button>
</form>

<?php elseif ($detail && $already): ?>
<div class="glass rounded-xl p-5 text-center">
    <i class="fas fa-check-circle text-emerald-400 text-4xl mb-3"></i>
    <p class="font-bold">Kamu sudah mengerjakan quiz ini.</p>
    <?php $stmt_s = $pdo->prepare("SELECT SUM(skor) FROM tbl_jawaban_siswa WHERE id_quiz=? AND id_siswa=?"); $stmt_s->execute([$detail['id_quiz'], $id]); $skor = $stmt_s->fetchColumn(); ?>
    <p class="text-2xl font-bold text-emerald-400 mt-2">Skor: <?= $skor ?></p>
    <a href="quiz.php" class="text-blue-400 text-sm mt-4 inline-block"><i class="fas fa-arrow-left mr-1"></i>Kembali</a>
</div>

<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php foreach ($quizzes as $q):
        $stmt_d = $pdo->prepare("SELECT 1 FROM tbl_jawaban_siswa WHERE id_quiz=? AND id_siswa=?"); $stmt_d->execute([$q['id_quiz'], $id]); $done = $stmt_d->fetch();
        $stmt_j = $pdo->prepare("SELECT COUNT(*) FROM tbl_soal WHERE id_quiz=?"); $stmt_j->execute([$q['id_quiz']]); $jml = $stmt_j->fetchColumn(); ?>
    <div class="glass rounded-xl p-5">
        <h4 class="font-bold"><?= clean($q['judul']) ?></h4>
        <p class="text-xs text-slate-400 mt-1"><?= clean($q['nama_mapel']) ?> · <?= $q['waktu_menit'] ?> menit · <?= $jml ?> soal</p>
        <?php if ($done): ?>
            <?php $stmt_sk = $pdo->prepare("SELECT SUM(skor) FROM tbl_jawaban_siswa WHERE id_quiz=? AND id_siswa=?"); $stmt_sk->execute([$q['id_quiz'], $id]); $skor = $stmt_sk->fetchColumn(); ?>
            <span class="text-emerald-400 text-sm mt-2 inline-block"><i class="fas fa-check mr-1"></i>Selesai — Skor: <?= $skor ?></span>
        <?php else: ?>
            <a href="?take=<?= $q['id_quiz'] ?>" class="bg-blue-600 hover:bg-blue-500 px-4 py-1.5 rounded-lg text-xs font-medium mt-3 inline-block"><i class="fas fa-play mr-1"></i>Kerjakan</a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php if (empty($quizzes)): ?><div class="glass rounded-xl p-5 text-slate-400 col-span-2 text-center">Belum ada quiz aktif.</div><?php endif; ?>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../template/footer.php'; ?>
