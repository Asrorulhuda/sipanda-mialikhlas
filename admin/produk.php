<?php
$page_title = 'Manajemen Produk Kantin';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin', 'kasir']);
cek_fitur('ekantin');

// Handle CRUD Actions
if (isset($_POST['save'])) {
    $id = $_POST['id'] ?? null;
    $sku = $_POST['sku'];
    $nama = $_POST['nama_produk'];
    $kategori = $_POST['kategori'];
    $harga = (float)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    $id_guru_penjual = $_POST['id_guru_penjual'] ?: NULL;
    
    // Calculate harga dasar for Guru if applicable
    $harga_dasar = $harga;
    if ($id_guru_penjual) {
        $s = $pdo->query("SELECT fee_titipan FROM tbl_setting WHERE id=1")->fetch();
        $fee_p = $s['fee_titipan'] ?? 10;
        $harga_dasar = $harga - ($harga * $fee_p / 100);
    }
    
    $gambar = upload_file('gambar', 'gambar/produk', ['jpg','jpeg','png']);
    
    try {
        if ($id) {
            $sql = "UPDATE tbl_produk SET sku=?, nama_produk=?, kategori=?, harga=?, harga_dasar=?, stok=?, id_guru_penjual=?";
            $params = [$sku, $nama, $kategori, $harga, $harga_dasar, $stok, $id_guru_penjual];
            if ($gambar) { $sql .= ", gambar=?"; $params[] = $gambar; }
            $sql .= " WHERE id_produk=?"; $params[] = $id;
            $pdo->prepare($sql)->execute($params);
            flash('msg', 'Produk berhasil diperbarui!');
        } else {
            $pdo->prepare("INSERT INTO tbl_produk (sku, nama_produk, kategori, harga, harga_dasar, stok, gambar, id_guru_penjual) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$sku, $nama, $kategori, $harga, $harga_dasar, $stok, $gambar ?: 'default.png', $id_guru_penjual]);
            flash('msg', 'Produk baru berhasil ditambahkan!');
        }
    } catch (PDOException $e) {
        flash('msg', 'Gagal menyimpan produk: ' . $e->getMessage(), 'danger');
    }
    header('Location: produk.php'); exit;
}

if (isset($_GET['del'])) {
    $pdo->prepare("DELETE FROM tbl_produk WHERE id_produk=?")->execute([$_GET['del']]);
    flash('msg', 'Produk berhasil dihapus!', 'danger');
    header('Location: produk.php'); exit;
}

$produk = $pdo->query("SELECT p.*, g.nama as nama_guru FROM tbl_produk p LEFT JOIN tbl_guru g ON p.id_guru_penjual = g.id_guru ORDER BY p.id_produk DESC")->fetchAll();
$all_kategori = $pdo->query("SELECT * FROM tbl_kategori_produk ORDER BY nama_kategori ASC")->fetchAll();
$all_guru = $pdo->query("SELECT id_guru, nama FROM tbl_guru ORDER BY nama ASC")->fetchAll();

require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<div class="flex justify-between items-center mb-6">
    <h4 class="font-black text-xl italic tracking-widest uppercase">Daftar Produk Kantin</h4>
    <button onclick="openForm()" class="bg-emerald-600 hover:bg-emerald-500 px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-emerald-900/40 transition-all active:scale-95">
        <i class="fas fa-plus mr-2"></i>TAMBAH PRODUK
    </button>
</div>

<!-- Form CRUD (Inline) -->
<div id="formContainer" style="display: none;" class="glass rounded-3xl overflow-hidden border border-white/10 shadow-2xl mb-8">
    <div class="px-8 py-4 border-b border-white/5 flex justify-between items-center bg-white/5">
        <h4 id="formTitle" class="font-black text-lg italic uppercase tracking-widest">Tambah Produk</h4>
        <button onclick="closeForm()" class="text-slate-500 hover:text-white"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" enctype="multipart/form-data" id="form_produk" class="p-8 space-y-6">
        <input type="hidden" name="id" id="f_id">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div class="space-y-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">SKU / Kode (Barcode)</label>
                    <input type="text" name="sku" id="f_sku" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Nama Produk</label>
                    <input type="text" name="nama_produk" id="f_nama" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 outline-none transition-all">
                </div>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Kategori</label>
                        <select name="kategori" id="f_kategori" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-emerald-500 outline-none transition-all">
                            <?php foreach($all_kategori as $cat): ?>
                            <option value="<?= clean($cat['nama_kategori']) ?>"><?= clean($cat['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Stok</label>
                        <input type="number" name="stok" id="f_stok" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 outline-none transition-all">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Harga Jual (Rp)</label>
                        <input type="number" name="harga" id="f_harga" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 outline-none transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Pemilik / Penjual</label>
                        <select name="id_guru_penjual" id="f_penjual" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-xs focus:border-emerald-500 outline-none transition-all">
                            <option value="">Kantin (Utama)</option>
                            <?php foreach($all_guru as $g): ?>
                            <option value="<?= $g['id_guru'] ?>"><?= clean($g['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
            <div class="space-y-2">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Foto Produk</label>
                <input type="file" name="gambar" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2 text-sm focus:border-emerald-500 outline-none transition-all text-slate-500">
            </div>
            <button type="submit" name="save" class="py-4 bg-emerald-600 hover:bg-emerald-500 rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-emerald-900/40 transition-all active:scale-95">SIMPAN DATA</button>
        </div>
    </form>
</div>

<div class="glass rounded-3xl overflow-hidden border border-white/5">
    <table class="w-full text-left text-sm">
        <thead class="bg-white/5 text-slate-400 uppercase text-[10px] font-bold tracking-widest">
            <tr>
                <th class="px-6 py-4">Produk</th>
                <th class="px-6 py-4">Kategori</th>
                <th class="px-6 py-4">Harga</th>
                <th class="px-6 py-4">Stok</th>
                <th class="px-6 py-4">Owner</th>
                <th class="px-6 py-4 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            <?php foreach($produk as $p): ?>
            <tr class="hover:bg-white/[0.02] transition-colors">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-4">
                        <img src="../gambar/produk/<?= $p['gambar'] ?: 'default.png' ?>" class="w-10 h-10 rounded-lg object-cover bg-slate-800">
                        <div>
                            <p class="font-bold"><?= clean($p['nama_produk']) ?></p>
                            <p class="text-[10px] text-slate-500 font-mono"><?= clean($p['sku']) ?></p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4"><span class="px-2 py-0.5 rounded-lg bg-blue-500/10 text-blue-400 text-[10px] uppercase font-bold"><?= clean($p['kategori']) ?></span></td>
                <td class="px-6 py-4 font-bold text-emerald-400"><?= rupiah($p['harga']) ?></td>
                <td class="px-6 py-4">
                    <span class="font-bold <?= $p['stok'] <= 5 ? 'text-red-400' : 'text-slate-300' ?>"><?= $p['stok'] ?></span>
                </td>
                <td class="px-6 py-4">
                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full <?= $p['id_guru_penjual'] ? 'bg-purple-500/10 text-purple-400' : 'bg-slate-500/10 text-slate-500 italic' ?>">
                        <?= $p['nama_guru'] ?: 'Kantin' ?>
                    </span>
                </td>
                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                    <a href="cetak_barcode.php?id=<?= $p['id_produk'] ?>" target="_blank" class="text-amber-400 hover:text-amber-300 transition-colors" title="Cetak Barcode"><i class="fas fa-barcode"></i></a>
                    <button onclick='editProduk(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)' class="text-blue-400 hover:text-blue-300 transition-colors"><i class="fas fa-edit"></i></button>
                    <a href="?del=<?= $p['id_produk'] ?>" onclick="return confirm('Hapus produk ini?')" class="text-red-400 hover:text-red-300 transition-colors"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


<script>
function openForm() {
    document.getElementById('formContainer').style.display = 'block';
    document.getElementById('formTitle').innerText = 'Tambah Produk';
    document.getElementById('form_produk').reset();
    document.getElementById('f_id').value = '';
    document.getElementById('f_sku').focus();
}
function closeForm() {
    document.getElementById('formContainer').style.display = 'none';
}
function editProduk(p) {
    document.getElementById('formContainer').style.display = 'block';
    document.getElementById('formTitle').innerText = 'Edit Produk';
    document.getElementById('f_id').value = p.id_produk;
    document.getElementById('f_sku').value = p.sku;
    document.getElementById('f_nama').value = p.nama_produk;
    document.getElementById('f_kategori').value = p.kategori;
    document.getElementById('f_harga').value = p.harga;
    document.getElementById('f_stok').value = p.stok;
    document.getElementById('f_penjual').value = p.id_guru_penjual || '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
