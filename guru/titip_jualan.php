<?php
$page_title = 'Titip Jualan Kantin';
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
cek_fitur('ekantin');

$id_guru = $_SESSION['user_id'];
$s = $pdo->query("SELECT fee_titipan FROM tbl_setting WHERE id=1")->fetch();
$fee_persen = $s['fee_titipan'] ?? 10;

// Handle Add Product
if (isset($_POST['tambah'])) {
    $nama = clean($_POST['nama']);
    $kategori = clean($_POST['kategori']);
    $harga_jual = (float)$_POST['harga_jual'];
    $stok = (int)$_POST['stok'];
    $sku = clean($_POST['sku'] ?: 'GR-'.time());
    
    // Calculate basic price for Guru
    $fee_nominal = ($harga_jual * $fee_persen) / 100;
    $harga_dasar = $harga_jual - $fee_nominal;
    
    $gambar = upload_file('gambar', 'gambar/produk', ['jpg','jpeg','png']);
    
    $pdo->prepare("INSERT INTO tbl_produk (sku, nama_produk, kategori, harga, harga_dasar, stok, gambar, id_guru_penjual) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$sku, $nama, $kategori, $harga_jual, $harga_dasar, $stok, $gambar, $id_guru]);
        
    flash('msg', 'Produk titipan berhasil ditambahkan!');
    header('Location: titip_jualan.php'); exit;
}

// Handle Update Stok
if (isset($_POST['update_stok'])) {
    $id_p = $_POST['id_produk'];
    $stok = (int)$_POST['stok'];
    $pdo->prepare("UPDATE tbl_produk SET stok = ? WHERE id_produk = ? AND id_guru_penjual = ?")
        ->execute([$stok, $id_p, $id_guru]);
    flash('msg', 'Stok berhasil diperbarui!');
    header('Location: titip_jualan.php'); exit;
}

// Handle Delete
if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_produk WHERE id_produk = ? AND id_guru_penjual = ?")
        ->execute([$_GET['hapus'], $id_guru]);
    flash('msg', 'Produk berhasil dihapus!');
    header('Location: titip_jualan.php'); exit;
}

$all_kategori = $pdo->query("SELECT * FROM tbl_kategori_produk ORDER BY nama_kategori ASC")->fetchAll();
$produk_saya = $pdo->prepare("SELECT * FROM tbl_produk WHERE id_guru_penjual = ? ORDER BY id_produk DESC");
$produk_saya->execute([$id_guru]);
$produk_saya = $produk_saya->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h4 class="font-bold text-lg">Titip Jualan Saya</h4>
        <p class="text-xs text-slate-500">Kelola produk Anda yang dititipkan di Kantin Sekolah</p>
    </div>
    <button onclick="openModal('modal_tambah')" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-plus mr-2"></i>Tambah Produk
    </button>
</div>

<?= alert_flash('msg') ?>

<!-- Info Box -->
<div class="glass rounded-2xl p-5 mb-6 border-l-4 border-blue-500">
    <div class="flex items-start gap-4">
        <div class="w-10 h-10 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-400">
            <i class="fas fa-info-circle"></i>
        </div>
        <div>
            <h5 class="font-bold text-sm mb-1">Ketentuan Titip Jualan</h5>
            <p class="text-xs text-slate-400 leading-relaxed">
                Biaya operasional kantin adalah sebesar <span class="text-blue-400 font-bold"><?= $fee_persen ?>%</span> dari harga jual. 
                Harga yang Anda input adalah harga jual ke siswa. Saldo yang masuk ke Anda adalah harga jual dikurangi fee <?= $fee_persen ?>%.
            </p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
    <?php foreach ($produk_saya as $p): 
        $fee_nominal = ($p['harga'] * $fee_persen) / 100;
        $net = $p['harga'] - $fee_nominal;
    ?>
    <div class="glass rounded-2xl overflow-hidden group border border-white/5 hover:border-blue-500/30 transition-all">
        <div class="aspect-video bg-slate-800 relative">
            <?php if ($p['gambar']): ?>
                <img src="../gambar/produk/<?= $p['gambar'] ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-slate-700">
                    <i class="fas fa-utensils text-4xl"></i>
                </div>
            <?php endif; ?>
            <div class="absolute top-2 right-2 flex gap-1">
                <a href="../admin/cetak_barcode.php?id=<?= $p['id_produk'] ?>" target="_blank" class="w-8 h-8 rounded-lg bg-amber-500/60 backdrop-blur-md flex items-center justify-center text-white hover:bg-amber-500 transition-all" title="Cetak Barcode"><i class="fas fa-barcode text-xs"></i></a>
                <button onclick="editStok(<?= $p['id_produk'] ?>, <?= $p['stok'] ?>)" class="w-8 h-8 rounded-lg bg-black/60 backdrop-blur-md flex items-center justify-center text-slate-200 hover:text-white"><i class="fas fa-edit text-xs"></i></button>
                <button onclick="confirmDelete('titip_jualan.php?hapus=<?= $p['id_produk'] ?>', '<?= clean($p['nama_produk']) ?>')" class="w-8 h-8 rounded-lg bg-red-500/60 backdrop-blur-md flex items-center justify-center text-white"><i class="fas fa-trash text-xs"></i></button>
            </div>
            <div class="absolute bottom-2 left-2 bg-blue-600 px-2 py-1 rounded text-[10px] font-bold text-white uppercase">
                <?= clean($p['kategori']) ?>
            </div>
        </div>
        <div class="p-4">
            <h5 class="font-bold text-sm truncate mb-1"><?= clean($p['nama_produk']) ?></h5>
            <div class="flex justify-between items-end mb-4">
                <div>
                    <p class="text-[9px] text-slate-500 uppercase font-bold">Harga Jual</p>
                    <p class="text-sm font-black text-white"><?= rupiah($p['harga']) ?></p>
                </div>
                <div class="text-right">
                    <p class="text-[9px] text-emerald-500 uppercase font-bold italic">Hasil Bersih</p>
                    <p class="text-sm font-black text-emerald-400"><?= rupiah($net) ?></p>
                </div>
            </div>
            <div class="flex items-center justify-between text-xs pt-3 border-t border-white/5">
                <span class="text-slate-500">Stok Kantin:</span>
                <span class="font-bold <?= $p['stok'] <= 5 ? 'text-red-400' : 'text-slate-200' ?>"><?= $p['stok'] ?> Pcs</span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal Tambah -->
<div id="modal_tambah" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
    <div class="glass w-full max-w-lg rounded-2xl overflow-hidden animate-fade-in">
        <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center">
            <h4 class="font-bold italic">TAMBAH PRODUK TITIPAN</h4>
            <button onclick="closeModal('modal_tambah')" class="text-slate-400 hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs text-slate-400 mb-1 font-bold italic">Nama Produk</label>
                    <input type="text" name="nama" required placeholder="Misal: Nasi Kuning Bu Guru" class="w-full bg-slate-800 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1 font-bold italic">Kategori</label>
                    <select name="kategori" required class="w-full bg-slate-800 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-blue-500 outline-none">
                        <?php foreach($all_kategori as $cat): ?>
                            <option value="<?= clean($cat['nama_kategori']) ?>"><?= clean($cat['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1 font-bold italic">SKU / Barcode (Opsional)</label>
                    <input type="text" name="sku" placeholder="Auto Generate" class="w-full bg-slate-800 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1 font-bold italic">Harga Jual (Prc Siswa)</label>
                    <input type="number" name="harga_jual" id="input_harga" required oninput="calcNet()" class="w-full bg-slate-800 border border-white/10 rounded-xl px-4 py-2.5 text-sm font-black text-blue-400 outline-none">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1 font-bold italic italic">Terima Bersih (Est)</label>
                    <div id="net_display" class="w-full bg-slate-900 border border-dashed border-emerald-500/30 rounded-xl px-4 py-2.5 text-sm font-black text-emerald-500">Rp 0</div>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs text-slate-400 mb-1 font-bold italic italic">Stok Awal</label>
                    <input type="number" name="stok" required value="10" class="w-full bg-slate-800 border border-white/10 rounded-xl px-4 py-2.5 text-sm outline-none">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs text-slate-400 mb-1 font-bold italic italic">Foto Produk</label>
                    <input type="file" name="gambar" accept="image/*" class="w-full bg-slate-800 border border-white/10 rounded-xl px-4 py-2 text-sm outline-none">
                </div>
            </div>
            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeModal('modal_tambah')" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-400 hover:text-white transition-colors">Batal</button>
                <button type="submit" name="tambah" class="bg-blue-600 hover:bg-blue-500 px-8 py-2.5 rounded-xl text-sm font-bold text-white shadow-lg shadow-blue-500/20 transition-all active:scale-95">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Stok -->
<div id="modal_stok" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
    <div class="glass w-full max-w-xs rounded-2xl overflow-hidden animate-fade-in">
        <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center">
            <h4 class="font-bold italic">UPDATE STOK</h4>
            <button onclick="closeModal('modal_stok')" class="text-slate-400 hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="id_produk" id="edit_id">
            <div>
                <label class="block text-xs text-slate-400 mb-2 font-bold italic italic">Jumlah Stok Saat Ini</label>
                <input type="number" name="stok" id="edit_stok_val" required class="w-full bg-slate-800 border border-blue-500/30 rounded-xl px-4 py-4 text-center text-2xl font-black text-white outline-none">
            </div>
            <button type="submit" name="update_stok" class="w-full bg-blue-600 hover:bg-blue-500 py-3 rounded-xl text-sm font-bold text-white shadow-lg shadow-blue-500/20 transition-all active:scale-95">Update Stok</button>
        </form>
    </div>
</div>

<script>
const FEE_PERSEN = <?= $fee_persen ?>;

function calcNet() {
    const harga = document.getElementById('input_harga').value || 0;
    const fee = (harga * FEE_PERSEN) / 100;
    const net = harga - fee;
    document.getElementById('net_display').textContent = new Intl.NumberFormat('id-ID', {style:'currency', currency:'IDR', maximumFractionDigits:0}).format(net);
}

function editStok(id, stok) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_stok_val').value = stok;
    openModal('modal_stok');
}
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
