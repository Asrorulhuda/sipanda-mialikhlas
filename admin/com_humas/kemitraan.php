<?php
$page_title = 'Data Kemitraan DUDI & Instansi';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin', 'waka_humas']);
cek_fitur('humas');

if (isset($_POST['simpan'])) {
    $doc = upload_file('dokumen', 'assets/uploads/dokumen', ['pdf','doc','docx','jpg','png']);
    $pdo->prepare("INSERT INTO tbl_humas_kemitraan (nama_instansi, jenis_kerjasama, tgl_mulai, tgl_selesai, dokumen_mou, status, keterangan) VALUES (?,?,?,?,?,?,?)")
        ->execute([$_POST['nama_instansi'], $_POST['jenis_kerjasama'], $_POST['tgl_mulai'], $_POST['tgl_selesai'], $doc, $_POST['status'], $_POST['keterangan']]);
    flash('msg', 'Data kemitraan berhasil ditambahkan!'); header('Location: kemitraan.php'); exit;
}

if (isset($_POST['update'])) {
    $doc = upload_file('dokumen', 'assets/uploads/dokumen', ['pdf','doc','docx','jpg','png']);
    $sql = "UPDATE tbl_humas_kemitraan SET nama_instansi=?, jenis_kerjasama=?, tgl_mulai=?, tgl_selesai=?, status=?, keterangan=?";
    $params = [$_POST['nama_instansi'], $_POST['jenis_kerjasama'], $_POST['tgl_mulai'], $_POST['tgl_selesai'], $_POST['status'], $_POST['keterangan']];
    if ($doc) { $sql .= ", dokumen_mou=?"; $params[] = $doc; }
    $sql .= " WHERE id=?"; $params[] = $_POST['id'];
    $pdo->prepare($sql)->execute($params);
    flash('msg', 'Data kemitraan berhasil diupdate!'); header('Location: kemitraan.php'); exit;
}

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_humas_kemitraan WHERE id=?")->execute([$_GET['hapus']]);
    flash('msg', 'Data kemitraan dihapus!', 'warning'); header('Location: kemitraan.php'); exit;
}

$data = $pdo->query("SELECT * FROM tbl_humas_kemitraan ORDER BY status ASC, tgl_selesai DESC")->fetchAll();
if (isset($_GET['edit'])) { 
    $stmt = $pdo->prepare("SELECT * FROM tbl_humas_kemitraan WHERE id=?"); 
    $stmt->execute([(int)$_GET['edit']]); 
    $edit = $stmt->fetch(); 
} else { $edit = null; }

require_once __DIR__ . '/../../template/header.php';
require_once __DIR__ . '/../../template/sidebar.php';
require_once __DIR__ . '/../../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<button onclick="document.getElementById('frm').classList.toggle('hidden')" class="bg-rose-600 hover:bg-rose-500 px-4 py-2 rounded-lg text-sm font-medium mb-4 shadow-lg shadow-rose-500/20"><i class="fas fa-handshake mr-1"></i><?= $edit ? 'Edit Kemitraan' : 'Tambah MOU / Kemitraan' ?></button>

<div id="frm" class="<?= $edit?'':'hidden' ?> glass rounded-xl p-5 mb-6 border border-white/5 border-t-rose-500 border-t-4 shadow-2xl">
    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php if($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
        
        <div><label class="block text-xs text-slate-400 mb-1">Nama Instansi / Perusahaan</label><input type="text" name="nama_instansi" value="<?= clean($edit['nama_instansi'] ?? '') ?>" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-rose-500 focus:outline-none"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Jenis Kerjasama / Program</label><input type="text" name="jenis_kerjasama" value="<?= clean($edit['jenis_kerjasama'] ?? '') ?>" required placeholder="Misal: Magang Industri, Sponsorship, Kampus" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-rose-500 focus:outline-none"></div>
        
        <div class="grid grid-cols-2 gap-2">
            <div><label class="block text-xs text-slate-400 mb-1">Tanggal Mulai</label><input type="date" name="tgl_mulai" value="<?= clean($edit['tgl_mulai'] ?? date('Y-m-d')) ?>" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-rose-500 focus:outline-none"></div>
            <div><label class="block text-xs text-slate-400 mb-1">Tanggal Berakhir</label><input type="date" name="tgl_selesai" value="<?= clean($edit['tgl_selesai'] ?? date('Y-m-d', strtotime('+1 year'))) ?>" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-rose-500 focus:outline-none"></div>
        </div>
        
        <div><label class="block text-xs text-slate-400 mb-1">Status MOU</label>
            <select name="status" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-rose-500 focus:outline-none">
                <option value="Aktif" <?= ($edit['status']??'Aktif')=='Aktif'?'selected':'' ?>>Masih Berlaku (Aktif)</option>
                <option value="Berakhir" <?= ($edit['status']??'')=='Berakhir'?'selected':'' ?>>Sudah Berakhir (Expired)</option>
            </select>
        </div>

        <div><label class="block text-xs text-slate-400 mb-1">Upload Dokumen MOU (PDF/Foto)</label><input type="file" name="dokumen" accept=".pdf,.doc,.docx,image/*" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-1.5 text-sm file:bg-slate-700 file:border-0 file:text-white file:px-3 file:py-1 file:rounded file:text-xs"></div>
        <div class="md:col-span-1"><label class="block text-xs text-slate-400 mb-1">Target / Keterangan Khusus</label><input type="text" name="keterangan" value="<?= clean($edit['keterangan'] ?? '') ?>" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-rose-500 focus:outline-none"></div>
        
        <div class="md:col-span-2 pt-2">
            <button type="submit" name="<?= $edit?'update':'simpan' ?>" class="bg-rose-600 hover:bg-rose-500 px-8 py-2.5 rounded-lg text-sm font-bold"><i class="fas fa-save mr-1"></i><?= $edit?'Simpan Perubahan':'Daftarkan Kemitraan' ?></button>
            <?php if($edit): ?><a href="kemitraan.php" class="bg-slate-700 px-6 py-2.5 rounded-lg text-sm font-bold ml-2">Batal</a><?php endif; ?>
        </div>
    </form>
</div>

<form action="cetak_mou.php" method="POST" target="_blank">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-white font-bold"><i class="fas fa-list mr-2"></i>Data MOU/Kemitraan</h3>
        <button type="submit" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium shadow-lg shadow-blue-500/20"><i class="fas fa-print mr-2"></i>Cetak Terpilih</button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($data as $r): 
            $aktif = $r['status'] == 'Aktif' && (strtotime($r['tgl_selesai']) >= strtotime(date('Y-m-d')));
        ?>
        <div class="glass rounded-xl p-5 border <?= $aktif ? 'border-rose-500/30 bg-rose-500/5' : 'border-white/5 opacity-70' ?> hover:border-rose-500/50 transition-colors">
            <div class="flex items-center gap-2 mb-3 pb-2 border-b border-white/5">
                <input type="checkbox" name="id_cetak[]" value="<?= $r['id'] ?>" class="chk-cetak rounded border-white/10 bg-slate-800 text-blue-500">
                <label class="text-xs text-slate-500">Tandai cetak</label>
            </div>
        <div class="flex items-start justify-between">
            <div>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $aktif ?'bg-rose-500/20 text-rose-400':'bg-slate-500/20 text-slate-400' ?>"><i class="fas fa-file-signature mr-1"></i><?= $r['status'] ?></span>
                <h4 class="font-bold text-white text-lg mt-2 mb-1"><?= clean($r['nama_instansi']) ?></h4>
                <p class="text-sm font-medium text-rose-300"><?= clean($r['jenis_kerjasama']) ?></p>
                
                <div class="mt-4 flex gap-4 text-xs">
                    <div>
                        <span class="block text-slate-500 mb-1">Periode MOU</span>
                        <div class="text-slate-300 font-mono"><i class="far fa-calendar-check mr-1 text-emerald-400"></i><?= date('M y', strtotime($r['tgl_mulai'])) ?> <i class="fas fa-arrow-right mx-1 text-slate-600"></i> <i class="far fa-calendar-times mr-1 text-red-400"></i><?= date('M y', strtotime($r['tgl_selesai'])) ?></div>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col items-end gap-3 h-full justify-between">
                <?php if($r['dokumen_mou']): ?>
                    <a href="<?= BASE_URL ?>assets/uploads/dokumen/<?= $r['dokumen_mou'] ?>" target="_blank" class="w-10 h-10 rounded-full bg-rose-500/20 flex items-center justify-center text-rose-400 hover:bg-rose-500 hover:text-white transition-colors tooltip" title="Lihat Dokumen">
                        <i class="fas fa-file-pdf"></i>
                    </a>
                <?php endif; ?>
                <div class="flex gap-1 mt-auto">
                    <a href="?edit=<?= $r['id'] ?>" class="p-1.5 rounded bg-blue-600/20 text-blue-400 hover:bg-blue-600/40 text-[10px] px-2"><i class="fas fa-edit"></i> Edit</a>
                    <button onclick="confirmDelete('?hapus=<?= $r['id'] ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 hover:bg-red-600/40 text-[10px]"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        </div>
        <?php if($r['keterangan']): ?>
        <div class="mt-4 pt-3 border-t border-white/5 text-[11px] text-slate-400 bg-black/20 p-2 rounded">
            <i class="fas fa-info-circle mr-1 text-blue-400"></i> <?= clean($r['keterangan']) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; if(!$data) echo '<div class="col-span-2 text-center py-8 text-slate-400"><i class="fas fa-handshake text-3xl mb-3 opacity-50 block"></i>Belum ada data kemitraan DUDI/MOU.</div>'; ?>
    </div>
</form>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
