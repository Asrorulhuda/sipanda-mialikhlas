<?php
$page_title = 'Inventaris Barang Sarpras';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin', 'waka_sarpras']);
cek_fitur('sarpras');

if (isset($_POST['simpan'])) {
    $pdo->prepare("INSERT INTO tbl_sarpras_barang (kode_barang, nama_barang, kategori, kondisi, tahun_pengadaan, sumber_dana, jumlah, id_guru, keterangan) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([$_POST['kode'], $_POST['nama'], $_POST['kategori'], $_POST['kondisi'], $_POST['tahun'], $_POST['sumber'], $_POST['jumlah'], empty($_POST['id_guru'])?null:$_POST['id_guru'], $_POST['ket']]);
    flash('msg', 'Barang berhasil dicatat!'); header('Location: barang.php'); exit;
}

if (isset($_POST['update'])) {
    $pdo->prepare("UPDATE tbl_sarpras_barang SET kode_barang=?, nama_barang=?, kategori=?, kondisi=?, tahun_pengadaan=?, sumber_dana=?, jumlah=?, id_guru=?, keterangan=? WHERE id_barang=?")
        ->execute([$_POST['kode'], $_POST['nama'], $_POST['kategori'], $_POST['kondisi'], $_POST['tahun'], $_POST['sumber'], $_POST['jumlah'], empty($_POST['id_guru'])?null:$_POST['id_guru'], $_POST['ket'], $_POST['id']]);
    flash('msg', 'Barang berhasil diupdate!'); header('Location: barang.php'); exit;
}

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_sarpras_barang WHERE id_barang=?")->execute([$_GET['hapus']]);
    flash('msg', 'Barang dihapus!', 'warning'); header('Location: barang.php'); exit;
}

$guru = $pdo->query("SELECT * FROM tbl_guru WHERE status='Aktif' ORDER BY nama")->fetchAll();
$data = $pdo->query("SELECT b.*, g.nama as penanggung_jawab FROM tbl_sarpras_barang b LEFT JOIN tbl_guru g ON b.id_guru=g.id_guru ORDER BY b.id_barang DESC")->fetchAll();
if (isset($_GET['edit'])) { 
    $stmt = $pdo->prepare("SELECT * FROM tbl_sarpras_barang WHERE id_barang=?"); 
    $stmt->execute([(int)$_GET['edit']]); 
    $edit = $stmt->fetch(); 
} else { $edit = null; }

require_once __DIR__ . '/../../template/header.php';
require_once __DIR__ . '/../../template/sidebar.php';
require_once __DIR__ . '/../../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<button onclick="document.getElementById('frm').classList.toggle('hidden')" class="bg-indigo-600 hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium mb-4 shadow-lg shadow-indigo-500/20"><i class="fas fa-plus mr-1"></i><?= $edit ? 'Edit Barang' : 'Tambah Barang Baru' ?></button>

<div id="frm" class="<?= $edit?'':'hidden' ?> glass rounded-xl p-5 mb-6 border border-white/5 border-t-indigo-500 border-t-4 shadow-2xl">
    <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php if($edit): ?><input type="hidden" name="id" value="<?= $edit['id_barang'] ?>"><?php endif; ?>
        <div><label class="block text-xs text-slate-400 mb-1">Kode Barang / Inventaris</label><input type="text" name="kode" value="<?= clean($edit['kode_barang'] ?? '') ?>" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></div>
        <div class="md:col-span-2"><label class="block text-xs text-slate-400 mb-1">Nama Barang / Aset</label><input type="text" name="nama" value="<?= clean($edit['nama_barang'] ?? '') ?>" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Kategori</label>
            <select name="kategori" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                <?php foreach(['Elektronik','Mebeler','Kendaraan','Gedung','Buku/Alat Tulis','Alat Olahraga','Lainnya'] as $k): ?><option <?= ($edit['kategori']??'')==$k?'selected':'' ?>><?= $k ?></option><?php endforeach; ?>
            </select>
        </div>
        <div><label class="block text-xs text-slate-400 mb-1">Kondisi</label>
            <select name="kondisi" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                <?php foreach(['Baik','Rusak Ringan','Rusak Berat'] as $k): ?><option <?= ($edit['kondisi']??'')==$k?'selected':'' ?>><?= $k ?></option><?php endforeach; ?>
            </select>
        </div>
        <div><label class="block text-xs text-slate-400 mb-1">Jumlah Unit</label><input type="number" name="jumlah" value="<?= $edit['jumlah'] ?? 1 ?>" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Tahun Pengadaan</label><input type="number" name="tahun" value="<?= $edit['tahun_pengadaan'] ?? date('Y') ?>" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Sumber Dana</label><input type="text" name="sumber" value="<?= clean($edit['sumber_dana'] ?? '') ?>" placeholder="Misal: BOS, Komite" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Penanggung Jawab Pengguna</label>
            <select name="id_guru" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                <option value="">-- Tidak Ada --</option>
                <?php foreach($guru as $g): ?><option value="<?= $g['id_guru'] ?>" <?= ($edit['id_guru']??'')==$g['id_guru']?'selected':'' ?>><?= clean($g['nama']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="md:col-span-3"><label class="block text-xs text-slate-400 mb-1">Keterangan Tambahan (Spek / Letak Ruang)</label><input type="text" name="ket" value="<?= clean($edit['keterangan'] ?? '') ?>" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></div>
        <div class="md:col-span-3 pt-2">
            <button type="submit" name="<?= $edit?'update':'simpan' ?>" class="bg-indigo-600 hover:bg-indigo-500 px-6 py-2.5 rounded-lg text-sm font-bold"><i class="fas fa-save mr-1"></i><?= $edit?'Simpan Perubahan':'Tambahkan Barang' ?></button>
            <?php if($edit): ?><a href="barang.php" class="bg-slate-700 px-6 py-2.5 rounded-lg text-sm font-bold ml-2">Batal</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="glass rounded-xl p-5 border border-white/5">
    <form action="cetak_inventaris.php" method="POST" target="_blank">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-white font-bold"><i class="fas fa-list mr-2"></i>Buku Induk Inventaris</h3>
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium shadow-lg shadow-blue-500/20"><i class="fas fa-print mr-2"></i>Cetak Terpilih</button>
        </div>
        <div class="table-container"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-400 border-b border-white/10">
        <th class="pb-3 w-10 text-center"><input type="checkbox" onchange="document.querySelectorAll('.chk-cetak').forEach(c => c.checked = this.checked)" class="rounded border-white/10 bg-slate-800 text-blue-500"></th>
        <th class="pb-3">Kode/Kategori</th><th class="pb-3">Nama Aset</th><th class="pb-3">Kondisi & Qty</th><th class="pb-3">Sumber/Tahun</th><th class="pb-3">PJ</th><th class="pb-3 text-right">Aksi</th>
    </tr></thead>
    <tbody><?php foreach ($data as $r): ?>
    <tr class="border-b border-white/5 hover:bg-white/5">
        <td class="py-3 text-center"><input type="checkbox" name="id_cetak[]" value="<?= $r['id_barang'] ?>" class="chk-cetak rounded border-white/10 bg-slate-800 text-blue-500"></td>
        <td class="py-3">
            <span class="block font-mono text-xs text-indigo-400 font-bold"><?= clean($r['kode_barang']) ?></span>
            <span class="block text-[10px] bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 px-2 py-0.5 rounded w-max mt-1"><?= $r['kategori'] ?></span>
        </td>
        <td class="font-medium"><?= clean($r['nama_barang']) ?><p class="text-[10px] text-slate-500 mt-0.5"><?= clean($r['keterangan']) ?></p></td>
        <td>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $r['kondisi']=='Baik'?'bg-emerald-500/20 text-emerald-400':($r['kondisi']=='Rusak Ringan'?'bg-amber-500/20 text-amber-400':'bg-red-500/20 text-red-400') ?>"><?= $r['kondisi'] ?></span>
            <span class="text-xs ml-2 font-mono"><?= $r['jumlah'] ?> <span class="text-[10px] text-slate-500">unit</span></span>
        </td>
        <td class="text-xs text-slate-400"><?= clean($r['sumber_dana']) ?><br><span class="text-[10px]"><?= $r['tahun_pengadaan'] ?></span></td>
        <td class="text-xs text-slate-300"><?= clean($r['penanggung_jawab'] ?: '-') ?></td>
        <td class="text-right">
            <div class="flex items-center justify-end gap-1">
                <a href="?edit=<?= $r['id_barang'] ?>" class="p-1.5 rounded bg-blue-600/20 text-blue-400 hover:bg-blue-600/40 text-xs"><i class="fas fa-edit"></i></a>
                <button onclick="confirmDelete('?hapus=<?= $r['id_barang'] ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 hover:bg-red-600/40 text-xs"><i class="fas fa-trash"></i></button>
            </div>
        </td>
    </tr>
    <?php endforeach; if(!$data) echo '<tr><td colspan="7" class="text-center py-8 text-slate-400"><i class="fas fa-box-open text-3xl mb-2 opacity-50 block"></i>Belum ada aset terdaftar.</td></tr>'; ?>
    </tbody>
</table></div></form></div>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
