<?php
$page_title = 'Jadwal & Riwayat Pemeliharaan Aset';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin', 'waka_sarpras']);
cek_fitur('sarpras');

if (isset($_POST['simpan'])) {
    $img = upload_file('foto','gambar',['jpg','jpeg','png']);
    $pdo->prepare("INSERT INTO tbl_sarpras_pemeliharaan (id_barang, tanggal, deskripsi, biaya, status, bukti_foto) VALUES (?,?,?,?,?,?)")
        ->execute([$_POST['id_barang'], $_POST['tanggal'], $_POST['deskripsi'], $_POST['biaya'], $_POST['status'], $img]);
    flash('msg', 'Jadwal/Riwayat pemeliharaan berhasil dicatat!'); header('Location: pemeliharaan.php'); exit;
}

if (isset($_POST['update'])) {
    $img = upload_file('foto','gambar',['jpg','jpeg','png']);
    $sql = "UPDATE tbl_sarpras_pemeliharaan SET id_barang=?, tanggal=?, deskripsi=?, biaya=?, status=?";
    $params = [$_POST['id_barang'], $_POST['tanggal'], $_POST['deskripsi'], $_POST['biaya'], $_POST['status']];
    if ($img) { $sql .= ", bukti_foto=?"; $params[] = $img; }
    $sql .= " WHERE id=?"; $params[] = $_POST['id'];
    $pdo->prepare($sql)->execute($params);
    flash('msg', 'Catatan pemeliharaan berhasil diupdate!'); header('Location: pemeliharaan.php'); exit;
}

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_sarpras_pemeliharaan WHERE id=?")->execute([$_GET['hapus']]);
    flash('msg', 'Catatan dihapus!', 'warning'); header('Location: pemeliharaan.php'); exit;
}

$barang = $pdo->query("SELECT * FROM tbl_sarpras_barang ORDER BY nama_barang")->fetchAll();
$data = $pdo->query("SELECT p.*, b.nama_barang, b.kode_barang FROM tbl_sarpras_pemeliharaan p JOIN tbl_sarpras_barang b ON p.id_barang = b.id_barang ORDER BY p.tanggal DESC")->fetchAll();
if (isset($_GET['edit'])) { 
    $stmt = $pdo->prepare("SELECT * FROM tbl_sarpras_pemeliharaan WHERE id=?"); 
    $stmt->execute([(int)$_GET['edit']]); 
    $edit = $stmt->fetch(); 
} else { $edit = null; }

require_once __DIR__ . '/../../template/header.php';
require_once __DIR__ . '/../../template/sidebar.php';
require_once __DIR__ . '/../../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<button onclick="document.getElementById('frm').classList.toggle('hidden')" class="bg-amber-600 hover:bg-amber-500 px-4 py-2 rounded-lg text-sm font-medium mb-4 shadow-lg shadow-amber-500/20"><i class="fas fa-tools mr-1"></i><?= $edit ? 'Edit Pemeliharaan' : 'Catat Pemeliharaan Baru' ?></button>

<div id="frm" class="<?= $edit?'':'hidden' ?> glass rounded-xl p-5 mb-6 border border-white/5 border-t-amber-500 border-t-4 shadow-2xl">
    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php if($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
        <div><label class="block text-xs text-slate-400 mb-1">Pilih Barang / Aset</label>
            <select name="id_barang" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
                <option value="">-- Pilih --</option>
                <?php foreach($barang as $b): ?><option value="<?= $b['id_barang'] ?>" <?= ($edit['id_barang']??'')==$b['id_barang']?'selected':'' ?>><?= clean($b['nama_barang']) ?> (<?= clean($b['kode_barang']) ?>)</option><?php endforeach; ?>
            </select>
        </div>
        <div><label class="block text-xs text-slate-400 mb-1">Tanggal Eksekusi</label><input type="date" name="tanggal" value="<?= $edit['tanggal'] ?? date('Y-m-d') ?>" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-amber-500 focus:outline-none"></div>
        <div class="md:col-span-2"><label class="block text-xs text-slate-400 mb-1">Deskripsi Kerusakan / Perawatan</label><input type="text" name="deskripsi" value="<?= clean($edit['deskripsi'] ?? '') ?>" required placeholder="Misal: Cek rutin instalasi / Penggantian layar mati" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-amber-500 focus:outline-none"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Biaya Perbaikan (Rp)</label><input type="number" name="biaya" value="<?= $edit['biaya'] ?? 0 ?>" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-amber-500 focus:outline-none"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Status Pengerjaan</label>
            <select name="status" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
                <?php foreach(['Direncanakan','Proses','Selesai'] as $k): ?><option <?= ($edit['status']??'Selesai')==$k?'selected':'' ?>><?= $k ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="md:col-span-2"><label class="block text-xs text-slate-400 mb-1">Upload Bukti Foto Nota / Pengerjaan</label><input type="file" name="foto" accept="image/*" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-1.5 text-sm file:bg-slate-700 file:border-0 file:text-white file:px-3 file:py-1 file:rounded file:text-xs"></div>
        <div class="md:col-span-2 pt-2">
            <button type="submit" name="<?= $edit?'update':'simpan' ?>" class="bg-amber-600 hover:bg-amber-500 px-6 py-2.5 rounded-lg text-sm font-bold"><i class="fas fa-save mr-1"></i><?= $edit?'Simpan Perubahan':'Simpan Jadwal' ?></button>
            <?php if($edit): ?><a href="pemeliharaan.php" class="bg-slate-700 px-6 py-2.5 rounded-lg text-sm font-bold ml-2">Batal</a><?php endif; ?>
        </div>
    </form>
</div>

<form action="cetak_pemeliharaan.php" method="POST" target="_blank">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-white font-bold"><i class="fas fa-history mr-2"></i>Log Pemeliharaan Aset</h3>
        <button type="submit" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium shadow-lg shadow-blue-500/20"><i class="fas fa-print mr-2"></i>Cetak Terpilih</button>
    </div>
    <div class="grid grid-cols-1 gap-4">
        <?php foreach ($data as $r): ?>
        <div class="glass rounded-xl p-5 border border-white/5 hover:border-amber-500/30 transition-colors">
            <div class="flex items-center gap-2 mb-3 pb-2 border-b border-white/5">
                <input type="checkbox" name="id_cetak[]" value="<?= $r['id'] ?>" class="rounded border-white/10 bg-slate-800 text-blue-500">
                <label class="text-xs text-slate-500 uppercase tracking-widest">Tandai untuk cetak laporan</label>
            </div>
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $r['status']=='Selesai'?'bg-emerald-500/20 text-emerald-400':($r['status']=='Proses'?'bg-blue-500/20 text-blue-400':'bg-slate-500/20 text-slate-400') ?>"><i class="fas fa-<?= $r['status']=='Selesai'?'check-circle':($r['status']=='Proses'?'spinner fa-spin':'clock') ?> mr-1"></i><?= $r['status'] ?></span>
                    <span class="text-xs text-slate-400"><i class="far fa-calendar-alt mr-1"></i><?= tgl_indo($r['tanggal']) ?></span>
                </div>
                <h4 class="font-bold text-amber-400 text-lg mb-1"><?= clean($r['nama_barang']) ?> <span class="text-sm font-normal text-slate-500">(<?= clean($r['kode_barang']) ?>)</span></h4>
                <p class="text-sm text-slate-200"><?= clean($r['deskripsi']) ?></p>
                <div class="mt-3 flex items-center gap-4 text-xs">
                    <div class="bg-black/30 px-3 py-1.5 rounded-lg border border-white/5 text-emerald-400 font-mono"><i class="fas fa-money-bill-wave mr-1.5"></i>Rp <?= number_format($r['biaya'],0,',','.') ?></div>
                </div>
            </div>
            <div class="flex flex-col md:items-end gap-3 justify-between">
                <?php if($r['bukti_foto']): ?>
                    <a href="<?= BASE_URL ?>assets/uploads/gambar/<?= $r['bukti_foto'] ?>" target="_blank" class="block w-24 h-16 rounded overflow-hidden border border-white/10 hover:opacity-80 transition-opacity">
                        <img src="<?= BASE_URL ?>assets/uploads/gambar/<?= $r['bukti_foto'] ?>" class="w-full h-full object-cover">
                    </a>
                <?php else: ?>
                    <div class="w-24 h-16 rounded border border-white/5 border-dashed flex items-center justify-center text-[10px] text-slate-500"><i class="fas fa-camera-slash mr-1"></i>No Image</div>
                <?php endif; ?>
                <div class="flex gap-1 justify-end w-full">
                    <a href="?edit=<?= $r['id'] ?>" class="p-1.5 rounded bg-blue-600/20 text-blue-400 hover:bg-blue-600/40 text-xs"><i class="fas fa-edit"></i> Edit</a>
                    <button onclick="confirmDelete('?hapus=<?= $r['id'] ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 hover:bg-red-600/40 text-xs"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        </div>
    </div>
        <?php endforeach; if(!$data) echo '<div class="text-center py-8 text-slate-400"><i class="fas fa-tools text-3xl mb-3 opacity-50 block"></i>Belum ada riwayat pemeliharaan.</div>'; ?>
    </div>
</form>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
