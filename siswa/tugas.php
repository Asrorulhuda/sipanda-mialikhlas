<?php
$page_title = 'Tugas';
require_once __DIR__ . '/../config/init.php';
cek_role(['siswa']);
cek_fitur('akademik');
$kelas = (int)($_SESSION['id_kelas'] ?? 0);
$stmt = $pdo->prepare("SELECT bt.*,m.nama_mapel,g.nama as nama_guru FROM tbl_bahan_tugas bt LEFT JOIN tbl_mapel m ON bt.id_mapel=m.id_mapel LEFT JOIN tbl_guru g ON bt.id_guru=g.id_guru WHERE bt.id_kelas=? ORDER BY bt.id DESC"); $stmt->execute([$kelas]); $data = $stmt->fetchAll();
require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php foreach ($data as $r): ?>
    <div class="glass rounded-xl p-5">
        <span class="px-2 py-0.5 rounded-full text-xs <?= $r['tipe']=='Tugas'?'bg-amber-500/20 text-amber-400':'bg-blue-500/20 text-blue-400' ?>"><?= $r['tipe'] ?></span>
        <h4 class="font-bold text-sm mt-2"><?= clean($r['judul']) ?></h4>
        <p class="text-xs text-slate-400 mt-1"><?= clean($r['nama_mapel']) ?> · <?= clean($r['nama_guru']) ?></p>
        <?php if ($r['deskripsi']): ?><p class="text-sm text-slate-300 mt-2"><?= nl2br(clean($r['deskripsi'])) ?></p><?php endif; ?>
        <?php if ($r['deadline']): ?><p class="text-xs text-amber-400 mt-2"><i class="fas fa-clock mr-1"></i>Deadline: <?= tgl_indo($r['deadline']) ?></p><?php endif; ?>
        <?php if ($r['file']): ?><a href="<?= BASE_URL ?>gambar/<?= $r['file'] ?>" target="_blank" class="text-xs text-blue-400 mt-2 inline-block"><i class="fas fa-paperclip mr-1"></i>Download File</a><?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php if (empty($data)): ?><div class="glass rounded-xl p-5 text-slate-400 col-span-2 text-center">Belum ada tugas.</div><?php endif; ?>
</div>
<?php require_once __DIR__ . '/../template/footer.php'; ?>
