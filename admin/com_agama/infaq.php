<?php
$page_title = 'Rekap Infaq & Zakat';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin', 'waka_keagamaan']);
cek_fitur('agama');

if (isset($_POST['simpan'])) {
    $pdo->prepare("INSERT INTO tbl_agama_infaq (tanggal, total_penerimaan, keterangan) VALUES (?,?,?)")
        ->execute([$_POST['tanggal'], $_POST['total'], $_POST['keterangan']]);
    flash('msg', 'Penerimaan infaq berhasil dicatat!'); header('Location: infaq.php'); exit;
}

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_agama_infaq WHERE id=?")->execute([$_GET['hapus']]);
    flash('msg', 'Data infaq dihapus!', 'warning'); header('Location: infaq.php'); exit;
}

$data = $pdo->query("SELECT * FROM tbl_agama_infaq ORDER BY tanggal DESC")->fetchAll();
$total_infaq = $pdo->query("SELECT SUM(total_penerimaan) as sum_total FROM tbl_agama_infaq")->fetch()['sum_total'] ?? 0;

require_once __DIR__ . '/../../template/header.php';
require_once __DIR__ . '/../../template/sidebar.php';
require_once __DIR__ . '/../../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="glass rounded-xl p-5 border border-white/5 bg-gradient-to-br from-teal-500/20 to-transparent">
        <h4 class="text-sm font-bold text-teal-400 uppercase tracking-wider mb-2">Total Infaq Terkumpul</h4>
        <div class="text-3xl font-bold font-mono text-white"><?= rupiah($total_infaq) ?></div>
        <p class="text-xs text-slate-400 mt-2">Seluruh dana amal terhimpun dari sistem.</p>
    </div>
</div>

<button onclick="document.getElementById('frm').classList.toggle('hidden')" class="bg-teal-600 hover:bg-teal-500 px-4 py-2 rounded-lg text-sm font-medium mb-4 shadow-lg shadow-teal-500/20"><i class="fas fa-hand-holding-usd mr-1"></i>Input Penerimaan Baru</button>

<div id="frm" class="hidden glass rounded-xl p-5 mb-6 border border-white/5 border-t-teal-500 border-t-4 shadow-2xl">
    <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div><label class="block text-xs text-slate-400 mb-1">Tanggal Koleksi</label><input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-teal-500 focus:outline-none"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Total Penerimaan (Rp)</label><input type="number" name="total" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-teal-500 focus:outline-none"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Keterangan / Sumber</label><input type="text" name="keterangan" required placeholder="Misal: Infaq Jumat Kelas 10-12" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-teal-500 focus:outline-none"></div>
        <div class="md:col-span-3 pt-2">
            <button type="submit" name="simpan" class="bg-teal-600 hover:bg-teal-500 px-8 py-2.5 rounded-lg text-sm font-bold"><i class="fas fa-save mr-1"></i>Catat Infaq</button>
        </div>
    </form>
</div>

<div class="glass rounded-xl p-5 border border-white/5"><div class="table-container"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3 w-40">Tanggal</th><th class="pb-3 w-48 text-right">Total Pemasukan</th><th class="pb-3 pl-6">Keterangan / Sumber Koleksi</th><th class="pb-3 text-right">Aksi</th></tr></thead>
    <tbody><?php foreach ($data as $r): ?>
    <tr class="border-b border-white/5 hover:bg-white/5">
        <td class="py-3 text-slate-300"><i class="far fa-calendar-check mr-2 text-teal-400"></i><?= tgl_indo($r['tanggal']) ?></td>
        <td class="text-right font-mono text-emerald-400 font-bold tracking-wide">+ <?= rupiah($r['total_penerimaan']) ?></td>
        <td class="pl-6 font-medium"><?= clean($r['keterangan']) ?></td>
        <td class="text-right">
            <button onclick="confirmDelete('?hapus=<?= $r['id'] ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 hover:bg-red-600/40 text-[10px]"><i class="fas fa-trash"></i></button>
        </td>
    </tr>
    <?php endforeach; if(!$data) echo '<tr><td colspan="4" class="text-center py-8 text-slate-400"><i class="fas fa-donate text-3xl mb-2 opacity-50 block"></i>Belum ada data infaq tercatat.</td></tr>'; ?>
    </tbody>
</table></div></div>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
