<?php
$page_title = 'Kegiatan Keagamaan';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin', 'waka_keagamaan']);
cek_fitur('agama');

if (isset($_POST['simpan'])) {
    $foto = upload_file('foto', 'assets/uploads/gambar', ['jpg','jpeg','png']);
    $pdo->prepare("INSERT INTO tbl_agama_kegiatan (nama_kegiatan, jenis, tanggal, pelaksana, keterangan, foto) VALUES (?,?,?,?,?,?)")
        ->execute([$_POST['nama'], $_POST['jenis'], $_POST['tanggal'], $_POST['pelaksana'], $_POST['keterangan'], $foto]);
    flash('msg', 'Kegiatan berhasil dicatat!'); header('Location: kegiatan.php'); exit;
}

if (isset($_POST['update'])) {
    $foto = upload_file('foto', 'assets/uploads/gambar', ['jpg','jpeg','png']);
    $sql = "UPDATE tbl_agama_kegiatan SET nama_kegiatan=?, jenis=?, tanggal=?, pelaksana=?, keterangan=?";
    $params = [$_POST['nama'], $_POST['jenis'], $_POST['tanggal'], $_POST['pelaksana'], $_POST['keterangan']];
    if ($foto) { $sql .= ", foto=?"; $params[] = $foto; }
    $sql .= " WHERE id=?"; $params[] = $_POST['id'];
    $pdo->prepare($sql)->execute($params);
    flash('msg', 'Kegiatan berhasil diupdate!'); header('Location: kegiatan.php'); exit;
}

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_agama_kegiatan WHERE id=?")->execute([$_GET['hapus']]);
    flash('msg', 'Kegiatan dihapus!', 'warning'); header('Location: kegiatan.php'); exit;
}

$data = $pdo->query("SELECT * FROM tbl_agama_kegiatan ORDER BY tanggal DESC")->fetchAll();
if (isset($_GET['edit'])) { 
    $stmt = $pdo->prepare("SELECT * FROM tbl_agama_kegiatan WHERE id=?"); 
    $stmt->execute([(int)$_GET['edit']]); 
    $edit = $stmt->fetch(); 
} else { $edit = null; }

require_once __DIR__ . '/../../template/header.php';
require_once __DIR__ . '/../../template/sidebar.php';
require_once __DIR__ . '/../../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<button onclick="document.getElementById('frm').classList.toggle('hidden')" class="bg-teal-600 hover:bg-teal-500 px-4 py-2 rounded-lg text-sm font-medium mb-4 shadow-lg shadow-teal-500/20"><i class="fas fa-calendar-plus mr-1"></i><?= $edit ? 'Edit Kegiatan' : 'Tambah Kegiatan / Jadwal' ?></button>

<div id="frm" class="<?= $edit?'':'hidden' ?> glass rounded-xl p-5 mb-6 border border-white/5 border-t-teal-500 border-t-4 shadow-2xl">
    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php if($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
        
        <div class="md:col-span-2"><label class="block text-xs text-slate-400 mb-1">Nama Kegiatan</label><input type="text" name="nama" value="<?= clean($edit['nama_kegiatan'] ?? '') ?>" required placeholder="Misal: Peringatan Maulid Nabi / Sholat Dhuha Berjamaah" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-teal-500 focus:outline-none"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Jenis Kegiatan</label>
            <select name="jenis" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-teal-500 focus:outline-none">
                <?php foreach(['Ibadah Harian','PHBI','Pesantren Kilat','Baksos','Lainnya'] as $k): ?><option <?= ($edit['jenis']??'')==$k?'selected':'' ?>><?= $k ?></option><?php endforeach; ?>
            </select>
        </div>
        <div><label class="block text-xs text-slate-400 mb-1">Tanggal Kegiatan</label><input type="date" name="tanggal" value="<?= $edit['tanggal'] ?? date('Y-m-d') ?>" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-teal-500 focus:outline-none"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Pelaksana / Pemateri</label><input type="text" name="pelaksana" value="<?= clean($edit['pelaksana'] ?? '') ?>" placeholder="Misal: Ustadz Fulan / Rohis" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-teal-500 focus:outline-none"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Upload Bukti Foto</label><input type="file" name="foto" accept="image/*" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-1.5 text-sm file:bg-slate-700 file:border-0 file:text-white file:px-3 file:py-1 file:rounded file:text-xs"></div>
        <div class="md:col-span-2"><label class="block text-xs text-slate-400 mb-1">Deskripsi / Keterangan</label><input type="text" name="keterangan" value="<?= clean($edit['keterangan'] ?? '') ?>" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-teal-500 focus:outline-none"></div>
        
        <div class="md:col-span-2 pt-2">
            <button type="submit" name="<?= $edit?'update':'simpan' ?>" class="bg-teal-600 hover:bg-teal-500 px-6 py-2.5 rounded-lg text-sm font-bold"><i class="fas fa-save mr-1"></i><?= $edit?'Simpan Perubahan':'Simpan Jadwal' ?></button>
            <?php if($edit): ?><a href="kegiatan.php" class="bg-slate-700 px-6 py-2.5 rounded-lg text-sm font-bold ml-2">Batal</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php foreach ($data as $r): ?>
    <div class="glass rounded-xl p-5 border border-white/5 hover:border-teal-500/30 transition-colors">
        <div class="flex gap-4">
            <?php if($r['foto']): ?>
                <div class="w-24 h-24 rounded-lg overflow-hidden flex-shrink-0 border border-white/10">
                    <img src="<?= BASE_URL ?>assets/uploads/gambar/<?= $r['foto'] ?>" class="w-full h-full object-cover">
                </div>
            <?php else: ?>
                <div class="w-24 h-24 rounded-lg bg-slate-800/50 flex-shrink-0 flex flex-col items-center justify-center border border-white/5 text-slate-500">
                    <i class="fas fa-mosque mb-1 py-1 text-2xl"></i>
                </div>
            <?php endif; ?>
            
            <div class="flex-1 w-full flex flex-col">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-teal-500/20 text-teal-400"><?= $r['jenis'] ?></span>
                        <h4 class="font-bold text-white text-base mt-1 line-clamp-1"><?= clean($r['nama_kegiatan']) ?></h4>
                        <div class="text-xs text-slate-400 mt-1"><i class="far fa-calendar-alt mr-1"></i><?= tgl_indo($r['tanggal']) ?></div>
                    </div>
                </div>
                <div class="mt-2 text-xs text-slate-300">
                    <span class="block bg-black/20 p-1.5 rounded indent-1 mb-1 border border-white/5"><i class="fas fa-user-circle mr-1 text-slate-500"></i><?= clean($r['pelaksana'] ?: 'Oleh Panitia/Rohis') ?></span>
                    <span class="block text-slate-400 mt-1.5 line-clamp-2"><?= clean($r['keterangan']) ?></span>
                </div>
                <div class="flex gap-1 justify-end mt-auto pt-2">
                    <a href="?edit=<?= $r['id'] ?>" class="p-1 px-2 rounded bg-blue-600/20 text-blue-400 hover:bg-blue-600/40 text-[10px]"><i class="fas fa-edit"></i> Edit</a>
                    <button onclick="confirmDelete('?hapus=<?= $r['id'] ?>')" class="p-1 px-2 rounded bg-red-600/20 text-red-400 hover:bg-red-600/40 text-[10px]"><i class="fas fa-trash"></i> Hapus</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; if(!$data) echo '<div class="col-span-2 text-center py-8 text-slate-400"><i class="fas fa-calendar-alt text-3xl mb-3 opacity-50 block"></i>Belum ada jadwal yang didaftarkan.</div>'; ?>
</div>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
