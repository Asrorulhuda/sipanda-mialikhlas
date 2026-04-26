<?php
$page_title = 'Bahan & Tugas';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','guru']);
cek_fitur('akademik');

if (isset($_POST['simpan'])) {
    $file = upload_file('file', 'gambar', ['jpg','jpeg','png','pdf','doc','docx','xls','xlsx','ppt','pptx']);
    $pdo->prepare("INSERT INTO tbl_bahan_tugas (id_mapel,id_kelas,id_guru,judul,deskripsi,file,tipe,deadline) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$_POST['id_mapel'],$_POST['id_kelas'],$_SESSION['user_id'],$_POST['judul'],$_POST['deskripsi'],$file,$_POST['tipe'],$_POST['deadline']??null]);
    flash('msg','Berhasil!'); header('Location: bahan_tugas.php'); exit;
}
if (isset($_GET['hapus'])) { $pdo->prepare("DELETE FROM tbl_bahan_tugas WHERE id=?")->execute([$_GET['hapus']]); flash('msg','Dihapus!','warning'); header('Location: bahan_tugas.php'); exit; }

$mapel = $pdo->query("SELECT * FROM tbl_mapel ORDER BY nama_mapel")->fetchAll();
$kelas = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();
$data = $pdo->query("SELECT bt.*,m.nama_mapel,k.nama_kelas,g.nama as nama_guru FROM tbl_bahan_tugas bt LEFT JOIN tbl_mapel m ON bt.id_mapel=m.id_mapel LEFT JOIN tbl_kelas k ON bt.id_kelas=k.id_kelas LEFT JOIN tbl_guru g ON bt.id_guru=g.id_guru ORDER BY bt.id DESC")->fetchAll();
require_once __DIR__ . '/../../template/header.php'; require_once __DIR__ . '/../../template/sidebar.php'; require_once __DIR__ . '/../../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<button onclick="document.getElementById('frm').classList.toggle('hidden')" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium mb-4"><i class="fas fa-plus mr-1"></i>Tambah</button>
<div id="frm" class="hidden glass rounded-xl p-5 mb-6">
    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><label class="block text-xs text-slate-400 mb-1">Judul</label><input type="text" name="judul" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Tipe</label><select name="tipe" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><option value="Bahan">Bahan Ajar</option><option value="Tugas">Tugas</option></select></div>
        <div><label class="block text-xs text-slate-400 mb-1">Mapel</label><select name="id_mapel" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><?php foreach ($mapel as $m): ?><option value="<?= $m['id_mapel'] ?>"><?= clean($m['nama_mapel']) ?></option><?php endforeach; ?></select></div>
        <div><label class="block text-xs text-slate-400 mb-1">Kelas</label><select name="id_kelas" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><?php foreach ($kelas as $k): ?><option value="<?= $k['id_kelas'] ?>"><?= clean($k['nama_kelas']) ?></option><?php endforeach; ?></select></div>
        <div><label class="block text-xs text-slate-400 mb-1">Deadline</label><input type="datetime-local" name="deadline" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-slate-400 mb-1">File</label><input type="file" name="file" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-1.5 text-sm"></div>
        <div class="md:col-span-2"><label class="block text-xs text-slate-400 mb-1">Deskripsi</label><textarea name="deskripsi" rows="3" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></textarea></div>
        <div><button type="submit" name="simpan" class="bg-blue-600 hover:bg-blue-500 px-6 py-2 rounded-lg text-sm font-medium"><i class="fas fa-save mr-1"></i>Simpan</button></div>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php foreach ($data as $r): ?>
    <div class="glass rounded-xl p-5">
        <div class="flex items-start justify-between mb-2">
            <div><span class="px-2 py-0.5 rounded-full text-xs <?= $r['tipe']=='Tugas'?'bg-amber-500/20 text-amber-400':'bg-blue-500/20 text-blue-400' ?>"><?= $r['tipe'] ?></span></div>
            <button onclick="confirmDelete('?hapus=<?= $r['id'] ?>')" class="text-red-400 text-xs"><i class="fas fa-trash"></i></button>
        </div>
        <h4 class="font-bold text-sm mb-1"><?= clean($r['judul']) ?></h4>
        <p class="text-xs text-slate-400"><?= clean($r['nama_mapel']) ?> · <?= clean($r['nama_kelas']) ?> · <?= clean($r['nama_guru']) ?></p>
        <?php if ($r['deadline']): ?><p class="text-xs text-amber-400 mt-1"><i class="fas fa-clock mr-1"></i>Deadline: <?= tgl_indo($r['deadline']) ?></p><?php endif; ?>
        <?php if ($r['file']): ?><a href="<?= BASE_URL ?>gambar/<?= $r['file'] ?>" target="_blank" class="text-xs text-blue-400 mt-1 inline-block"><i class="fas fa-paperclip mr-1"></i>File lampiran</a><?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
