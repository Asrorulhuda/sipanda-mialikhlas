<?php
$page_title = 'Guru BK';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);
$data = $pdo->query("SELECT * FROM tbl_guru WHERE is_bk=1 ORDER BY nama")->fetchAll();
require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>
<div class="glass rounded-xl p-5">
    <p class="text-sm text-slate-400 mb-4">Guru yang terdaftar sebagai BK. Kelola di menu <a href="guru.php" class="text-blue-400 underline">Data Guru</a> (centang "Guru BK").</p>
    <div class="table-container"><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">Nama</th><th class="pb-3">NIP</th><th class="pb-3">Status</th></tr></thead>
        <tbody>
        <?php foreach ($data as $i => $r): ?>
        <tr class="border-b border-white/5 hover:bg-white/5"><td class="py-2.5"><?= $i+1 ?></td><td class="font-medium"><?= clean($r['nama']) ?></td><td class="font-mono text-xs"><?= clean($r['nip']) ?></td><td><span class="px-2 py-0.5 rounded-full text-xs bg-emerald-500/20 text-emerald-400"><?= $r['status'] ?></span></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php require_once __DIR__ . '/../template/footer.php'; ?>
