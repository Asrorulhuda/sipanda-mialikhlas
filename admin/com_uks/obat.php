<?php
$page_title = 'Inventori Obat UKS';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin', 'waka_uks']);
cek_fitur('uks');

if (isset($_POST['tambah'])) {
    $stmt = $pdo->prepare("INSERT INTO tbl_uks_obat (nama_obat, stok, satuan, exp_date) VALUES (?,?,?,?)");
    $stmt->execute([$_POST['nama_obat'], (int)$_POST['stok'], $_POST['satuan'], $_POST['exp_date']]);
    flash('msg', 'Obat berhasil ditambahkan!'); header('Location: obat.php'); exit;
}

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_uks_obat WHERE id_obat=?")->execute([$_GET['hapus']]);
    flash('msg', 'Data dihapus!', 'warning'); header('Location: obat.php'); exit;
}

$data = $pdo->query("SELECT * FROM tbl_uks_obat ORDER BY nama_obat ASC")->fetchAll();

require_once __DIR__ . '/../../template/header.php';
require_once __DIR__ . '/../../template/sidebar.php';
require_once __DIR__ . '/../../template/topbar.php';
?>

<?= alert_flash('msg') ?>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <h2 class="text-xl font-bold text-white flex items-center gap-2 font-black italic uppercase tracking-widest"><i class="fas fa-pills text-rose-500"></i> Inventori Obat UKS</h2>
    <button onclick="document.getElementById('modalObat').classList.toggle('hidden')" class="bg-rose-600 hover:bg-rose-500 px-6 py-2.5 rounded-xl text-sm font-bold text-white shadow-lg shadow-rose-600/20 transition-all"><i class="fas fa-plus mr-2"></i>Tambah Stok Obat</button>
</div>

<div id="modalObat" class="hidden glass rounded-2xl p-6 mb-8 border border-white/10 animate-zoom-in">
    <h3 class="text-white font-bold mb-4 flex items-center gap-2"><i class="fas fa-plus-circle text-rose-500"></i> Registrasi Obat Baru</h3>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
        <div><label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Nama Obat</label><input type="text" name="nama_obat" required placeholder="Parasetamol" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-rose-500 focus:outline-none text-white"></div>
        <div><label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Stok Awal</label><input type="number" name="stok" required value="0" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-rose-500 focus:outline-none text-white"></div>
        <div><label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Satuan</label><select name="satuan" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-rose-500 focus:outline-none text-white"><option>Pcs</option><option>Tablet</option><option>Botol</option><option>Sachet</option></select></div>
        <div><label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Kadaluarsa</label><input type="date" name="exp_date" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-rose-500 focus:outline-none text-white"></div>
        <div class="lg:col-span-4 flex justify-end gap-3 mt-2">
            <button type="button" onclick="document.getElementById('modalObat').classList.add('hidden')" class="px-6 py-2.5 rounded-xl bg-slate-800 text-sm font-bold text-slate-400 hover:bg-slate-700 transition-all">Batal</button>
            <button type="submit" name="tambah" class="bg-rose-600 hover:bg-rose-500 px-8 py-2.5 rounded-xl text-sm font-bold text-white shadow-xl shadow-rose-600/30 transition-all">Simpan Data Obat</button>
        </div>
    </form>
</div>

<div class="glass rounded-3xl overflow-hidden border border-white/5">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-slate-500 border-b border-white/5 bg-white/3">
                <th class="p-5 font-bold uppercase tracking-widest text-[10px]">Nama Obat</th>
                <th class="p-5 font-bold uppercase tracking-widest text-[10px]">Stok</th>
                <th class="p-5 font-bold uppercase tracking-widest text-[10px]">Tgl Kadaluarsa</th>
                <th class="p-5 font-bold uppercase tracking-widest text-[10px] text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            <?php foreach ($data as $r): 
                $is_exp_near = strtotime($r['exp_date']) < strtotime('+3 months');
                $is_low_stock = $r['stok'] <= 5;
            ?>
            <tr class="hover:bg-white/5 transition-all">
                <td class="p-5">
                    <span class="block font-bold text-white text-base"><?= clean($r['nama_obat']) ?></span>
                </th>
                <td class="p-5">
                    <span class="inline-flex items-center gap-2 font-black text-xl <?= $is_low_stock ? 'text-rose-500 animate-pulse' : 'text-emerald-400' ?>">
                        <?= $r['stok'] ?> <span class="text-[10px] font-normal text-slate-500 italic uppercase"><?= $r['satuan'] ?></span>
                    </span>
                    <?php if ($is_low_stock): ?><span class="block text-[10px] text-rose-500/70 font-bold mt-1">Stok Menipis!</span><?php endif; ?>
                </td>
                <td class="p-5 text-slate-300 font-mono">
                    <span class="<?= $is_exp_near ? 'text-amber-500' : '' ?>"><?= date('d M Y', strtotime($r['exp_date'])) ?></span>
                    <?php if ($is_exp_near): ?><i class="fas fa-exclamation-triangle text-amber-500 ml-2 animate-bounce"></i><?php endif; ?>
                </td>
                <td class="p-5 text-right">
                    <button class="p-2.5 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500 hover:text-white transition-all mr-2"><i class="fas fa-edit"></i></button>
                    <a href="?hapus=<?= $r['id_obat'] ?>" onclick="return confirm('Hapus data obat ini?')" class="p-2.5 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all"><i class="fas fa-trash-alt"></i></a>
                </td>
            </tr>
            <?php endforeach; if(!$data) echo '<tr><td colspan="4" class="p-10 text-center text-slate-500 italic">Belum ada stok obat diinventarisir.</td></tr>'; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../template/footer.php'; ?>
