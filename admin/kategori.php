<?php
$page_title = 'Manajemen Kategori Produk';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin', 'kasir']);
cek_fitur('ekantin');

// Handle CRUD Actions
if (isset($_POST['save'])) {
    $id = $_POST['id'] ?? null;
    $nama = trim($_POST['nama_kategori']);
    
    try {
        if ($id) {
            $pdo->prepare("UPDATE tbl_kategori_produk SET nama_kategori=? WHERE id_kategori=?")->execute([$nama, $id]);
            flash('msg', 'Kategori berhasil diperbarui!');
        } else {
            $pdo->prepare("INSERT INTO tbl_kategori_produk (nama_kategori) VALUES (?)")->execute([$nama]);
            flash('msg', 'Kategori baru berhasil ditambahkan!');
        }
    } catch (PDOException $e) {
        flash('msg', 'Gagal menyimpan kategori: ' . $e->getMessage(), 'danger');
    }
    header('Location: kategori.php'); exit;
}

if (isset($_GET['del'])) {
    try {
        $pdo->prepare("DELETE FROM tbl_kategori_produk WHERE id_kategori=?")->execute([$_GET['del']]);
        flash('msg', 'Kategori berhasil dihapus!', 'success');
    } catch (PDOException $e) {
        flash('msg', 'Gagal hapus: Pastikan kategori tidak digunakan oleh produk apapun.', 'danger');
    }
    header('Location: kategori.php'); exit;
}

$kategori = $pdo->query("SELECT * FROM tbl_kategori_produk ORDER BY nama_kategori ASC")->fetchAll();

require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<div class="flex justify-between items-center mb-6">
    <h4 class="font-black text-xl italic tracking-widest uppercase">Kategori Produk Kantin</h4>
    <button onclick="openForm()" class="bg-indigo-600 hover:bg-indigo-500 px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-indigo-900/40 transition-all active:scale-95">
        <i class="fas fa-plus mr-2"></i>TAMBAH KATEGORI
    </button>
</div>

<!-- Form Inline -->
<div id="formContainer" style="display: none;" class="glass rounded-3xl overflow-hidden border border-white/10 shadow-2xl mb-8">
    <div class="px-8 py-4 border-b border-white/5 flex justify-between items-center bg-white/5">
        <h4 id="formTitle" class="font-black text-lg italic uppercase tracking-widest">Tambah Kategori</h4>
        <button onclick="closeForm()" class="text-slate-500 hover:text-white"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" id="form_kategori" class="p-8">
        <input type="hidden" name="id" id="f_id">
        <div class="flex gap-4 items-end">
            <div class="flex-1 space-y-2">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Nama Kategori</label>
                <input type="text" name="nama_kategori" id="f_nama" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-indigo-500 outline-none transition-all">
            </div>
            <button type="submit" name="save" class="px-8 py-3.5 bg-indigo-600 hover:bg-indigo-500 rounded-xl font-black text-xs uppercase tracking-widest transition-all">SIMPAN</button>
        </div>
    </form>
</div>

<div class="glass rounded-3xl overflow-hidden border border-white/5">
    <table class="w-full text-left text-sm">
        <thead class="bg-white/5 text-slate-400 uppercase text-[10px] font-bold tracking-widest">
            <tr>
                <th class="px-6 py-4">ID</th>
                <th class="px-6 py-4">Nama Kategori</th>
                <th class="px-6 py-4 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            <?php foreach($kategori as $k): ?>
            <tr class="hover:bg-white/[0.02] transition-colors">
                <td class="px-6 py-4 text-slate-500 font-mono text-xs"><?= $k['id_kategori'] ?></td>
                <td class="px-6 py-4 font-bold"><?= clean($k['nama_kategori']) ?></td>
                <td class="px-6 py-4 text-right space-x-2">
                    <button onclick='editKategori(<?= json_encode($k) ?>)' class="text-indigo-400 hover:text-indigo-300"><i class="fas fa-edit"></i></button>
                    <a href="?del=<?= $k['id_kategori'] ?>" onclick="return confirm('Hapus kategori ini?')" class="text-red-400 hover:text-red-300"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function openForm() {
    document.getElementById('formContainer').style.display = 'block';
    document.getElementById('formTitle').innerText = 'Tambah Kategori';
    document.getElementById('form_kategori').reset();
    document.getElementById('f_id').value = '';
    document.getElementById('f_nama').focus();
}
function closeForm() {
    document.getElementById('formContainer').style.display = 'none';
}
function editKategori(k) {
    document.getElementById('formContainer').style.display = 'block';
    document.getElementById('formTitle').innerText = 'Edit Kategori';
    document.getElementById('f_id').value = k.id_kategori;
    document.getElementById('f_nama').value = k.nama_kategori;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
