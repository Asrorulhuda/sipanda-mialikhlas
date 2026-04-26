<?php
$page_title = 'Ekstrakurikuler Saya';
require_once __DIR__ . '/../config/init.php';
cek_role(['siswa']);
cek_fitur('kesiswaan');

$id_siswa = $_SESSION['user_id'];

// Ambil ekskul yang diikuti siswa
$stmt = $pdo->prepare("SELECT e.*, ea.id as id_anggota FROM tbl_eskul_anggota ea JOIN tbl_eskul e ON ea.id_eskul=e.id_eskul WHERE ea.id_siswa=? ORDER BY e.nama_eskul");
$stmt->execute([$id_siswa]);
$my_eskul = $stmt->fetchAll();

// Semua ekskul untuk dilihat-lihat
$semua = $pdo->query("SELECT * FROM tbl_eskul ORDER BY nama_eskul")->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="mb-6">
    <h2 class="text-xl font-bold bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent mb-2">Ekstrakurikuler Saya</h2>
    <p class="text-sm text-slate-400">Daftar ekstrakurikuler yang kamu ikuti tahun ini.</p>
</div>

<?php if ($my_eskul): ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    <?php foreach ($my_eskul as $e): ?>
    <div class="glass flex flex-col items-center text-center rounded-xl p-6 border-t-4 border-blue-500 hover:bg-white/5 transition-all">
        <div class="w-16 h-16 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center text-3xl mb-4 shadow-lg shadow-blue-500/20">
            <i class="fas fa-futbol"></i>
        </div>
        <h3 class="font-bold text-lg text-white mb-1"><?= clean($e['nama_eskul']) ?></h3>
        <p class="text-xs text-slate-400 mb-4"><?= clean($e['hari']) ?> · <?= clean($e['jam']) ?></p>
        <div class="w-full bg-slate-800/50 rounded-lg py-2 mt-auto">
            <span class="text-xs text-amber-400"><i class="fas fa-user-tie mr-1"></i> Pembina: <?= clean($e['pembina']) ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="glass rounded-xl p-8 text-center text-slate-400 mb-8 border border-white/5">
    <i class="fas fa-running text-4xl mb-3 opacity-50"></i>
    <p class="font-medium text-slate-300">Kamu belum mengikuti ekstrakurikuler.</p>
    <p class="text-xs mt-1">Silakan hubungi pembina untuk mendaftar ekstrkurikuler.</p>
</div>
<?php endif; ?>

<div class="mb-4 mt-8">
    <h2 class="text-lg font-bold text-white mb-1 flex items-center gap-2"><i class="fas fa-list text-purple-400"></i> Daftar Ekstrakurikuler Sekolah</h2>
</div>
<div class="glass rounded-xl p-5 border border-white/5">
    <div class="table-container">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-400 border-b border-white/10">
                    <th class="pb-3 px-2">#</th>
                    <th class="pb-3 px-2">Nama Ekstrakurikuler</th>
                    <th class="pb-3 px-2">Jadwal</th>
                    <th class="pb-3 px-2">Pembina</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($semua as $i => $s): 
                    $is_member = in_array($s['id_eskul'], array_column($my_eskul, 'id_eskul'));
                ?>
                <tr class="border-b border-white/5 hover:bg-white/5 <?= $is_member ? 'bg-blue-500/10' : '' ?>">
                    <td class="py-3 px-2"><?= $i+1 ?></td>
                    <td class="font-bold text-blue-400 px-2 flex items-center gap-2">
                        <?= clean($s['nama_eskul']) ?>
                        <?php if($is_member): ?><span class="text-[9px] px-1.5 py-0.5 bg-blue-500 text-white rounded uppercase tracking-wider">Diikuti</span><?php endif; ?>
                    </td>
                    <td class="px-2 text-slate-300"><i class="far fa-calendar-alt text-slate-500 mr-1"></i><?= clean($s['hari']) ?> · <?= clean($s['jam']) ?></td>
                    <td class="px-2 text-slate-400"><?= clean($s['pembina']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
