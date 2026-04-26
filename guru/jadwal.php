<?php
$page_title = 'Jadwal Mengajar';
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
cek_fitur('akademik');

$id_guru = $_SESSION['user_id'];

// Ambil data guru
$stmt = $pdo->prepare("SELECT * FROM tbl_guru WHERE id_guru = ?");
$stmt->execute([$id_guru]);
$guru = $stmt->fetch();

// Ambil jam & hari
$jam_list  = $pdo->query("SELECT * FROM tbl_jam ORDER BY jam_mulai")->fetchAll();
$hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

// Ambil jadwal guru ini
$jadwal = [];
$stmt = $pdo->prepare("
    SELECT j.*, m.nama_mapel, k.nama_kelas, jm.nama_jam, jm.jam_mulai, jm.jam_selesai
    FROM tbl_jadwal j
    JOIN tbl_mapel m ON j.id_mapel = m.id_mapel
    JOIN tbl_kelas k ON j.id_kelas = k.id_kelas
    JOIN tbl_jam jm ON j.id_jam = jm.id_jam
    WHERE j.id_guru = ?
    ORDER BY jm.jam_mulai
");
$stmt->execute([$id_guru]);
foreach ($stmt->fetchAll() as $j) {
    $jadwal[$j['hari']][$j['id_jam']] = $j;
}

// Hitung total jam mengajar
$total_jam = 0;
foreach ($jadwal as $hari => $jams) {
    $total_jam += count($jams);
}

// Hitung kelas unik
$kelas_unik = [];
foreach ($jadwal as $hari => $jams) {
    foreach ($jams as $j) {
        $kelas_unik[$j['id_kelas']] = $j['nama_kelas'];
    }
}

// Palet warna per kelas
$kelas_colors = ['#dbeafe','#dcfce7','#fef9c3','#fce7f3','#e0e7ff','#f3e8ff','#ccfbf1','#ffedd5','#fee2e2','#ecfeff','#fef3c7','#ede9fe'];
function kelas_color($name, $palette) { return $palette[abs(crc32($name)) % count($palette)]; }

// Hari ini
$hari_ini = ['Sunday'=>'','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'][date('l')] ?? '';

// Jadwal hari ini
$jadwal_hari_ini = $jadwal[$hari_ini] ?? [];

// Ambil wali kelas info
$wali_kelas = null;
if (!empty($guru['id_kelas_wali'])) {
    $stmt = $pdo->prepare("SELECT nama_kelas FROM tbl_kelas WHERE id_kelas = ?");
    $stmt->execute([$guru['id_kelas_wali']]);
    $wali_kelas = $stmt->fetchColumn();
}

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<!-- Header -->
<div class="glass rounded-2xl p-6 mb-6 border border-indigo-500/20 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-40 h-40 bg-indigo-500/5 rounded-full blur-3xl"></div>
    <div class="flex items-center justify-between relative z-10">
        <div>
            <h2 class="text-xl font-bold text-white flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600/30 border border-indigo-500/30 flex items-center justify-center">
                    <i class="fas fa-chalkboard-teacher text-indigo-400"></i>
                </div>
                Jadwal Mengajar Saya
            </h2>
            <p class="text-sm text-slate-400 mt-1"><?= clean($guru['nama'] ?? 'Guru') ?></p>
        </div>
        <div class="flex items-center gap-3">
            <?php if ($hari_ini): ?>
            <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl px-4 py-2 text-sm hidden md:block">
                <i class="fas fa-clock text-emerald-400 mr-1"></i>
                <span class="text-emerald-300 font-semibold">Hari ini: <?= $hari_ini ?></span>
            </div>
            <?php endif; ?>
            
            <a href="cetak_jadwal_guru.php" target="_blank"
               class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-lg transition-colors flex items-center gap-2 border border-indigo-400/30">
                <i class="fas fa-print"></i> Cetak Jadwal Saya
            </a>

            <?php if ($wali_kelas): ?>
            <a href="../admin/com_sease/cetak_jadwal.php?kelas=<?= $guru['id_kelas_wali'] ?>" target="_blank"
               class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-lg transition-colors flex items-center gap-2 border border-blue-400/30">
                <i class="fas fa-print"></i> Jadwal <?= clean($wali_kelas) ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="glass rounded-xl p-4 text-center border border-blue-500/20">
        <p class="text-2xl font-bold text-blue-400"><?= $total_jam ?></p>
        <p class="text-xs text-slate-400">Total Jam/Minggu</p>
    </div>
    <div class="glass rounded-xl p-4 text-center border border-purple-500/20">
        <p class="text-2xl font-bold text-purple-400"><?= count($kelas_unik) ?></p>
        <p class="text-xs text-slate-400">Kelas Diajar</p>
    </div>
    <div class="glass rounded-xl p-4 text-center border border-emerald-500/20">
        <p class="text-2xl font-bold text-emerald-400"><?= count($jadwal_hari_ini) ?></p>
        <p class="text-xs text-slate-400">Mengajar Hari Ini</p>
    </div>
</div>

<?php if (!empty($jadwal_hari_ini)): ?>
<!-- Jadwal Hari Ini -->
<div class="glass rounded-2xl p-5 mb-6 border border-emerald-500/20">
    <h3 class="font-bold text-sm text-emerald-300 mb-4 flex items-center gap-2">
        <i class="fas fa-bolt"></i> Jadwal Hari Ini — <?= $hari_ini ?>
    </h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <?php foreach ($jadwal_hari_ini as $j):
            $bg = kelas_color($j['nama_kelas'], $kelas_colors);
        ?>
        <div class="rounded-xl p-4 border border-white/10 relative overflow-hidden" style="background: <?= $bg ?>15;">
            <div class="absolute top-0 right-0 w-16 h-16 rounded-full blur-xl" style="background: <?= $bg ?>30;"></div>
            <div class="relative z-10">
                <div class="font-mono text-xs text-slate-400 mb-1">
                    <?= substr($j['jam_mulai'],0,5) ?> - <?= substr($j['jam_selesai'],0,5) ?>
                </div>
                <div class="font-bold text-white text-sm"><?= clean($j['nama_mapel']) ?></div>
                <div class="flex items-center gap-1.5 mt-2">
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold" style="background: <?= $bg ?>40; color: <?= $bg ?>;">
                        <?= clean($j['nama_kelas']) ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Grid Jadwal Lengkap -->
<div class="glass rounded-2xl overflow-hidden border border-white/10 shadow-2xl">
    <div class="bg-indigo-600/10 px-5 py-3 border-b border-white/10 flex items-center justify-between">
        <h3 class="font-bold text-sm text-indigo-300"><i class="fas fa-calendar-alt mr-2"></i> Jadwal Lengkap Mingguan</h3>
        <div class="flex gap-2">
            <?php foreach ($kelas_unik as $kid => $kname):
                $bg = kelas_color($kname, $kelas_colors);
            ?>
            <span class="text-[10px] px-2 py-1 rounded-full font-semibold" style="background: <?= $bg ?>30; color: <?= $bg ?>; border: 1px solid <?= $bg ?>50;">
                <?= clean($kname) ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-black/40 text-center">
                    <th class="px-4 py-4 text-indigo-300 text-xs font-bold uppercase tracking-wider border-r border-white/5 w-28">
                        <i class="far fa-clock mr-1"></i> Waktu
                    </th>
                    <?php foreach ($hari_list as $h): ?>
                    <th class="px-4 py-4 text-xs font-bold uppercase tracking-wider border-r border-white/5 <?= $h === $hari_ini ? 'text-emerald-300 bg-emerald-500/10' : 'text-slate-300' ?>">
                        <?= $h ?>
                        <?php if ($h === $hari_ini): ?><span class="ml-1 text-[9px] bg-emerald-500/30 text-emerald-200 px-1.5 py-0.5 rounded-full">Hari Ini</span><?php endif; ?>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($jam_list as $jam):
                    $is_istirahat = (stripos($jam['nama_jam'], 'istirahat') !== false);
                ?>
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-3 py-3 text-center bg-black/20 border-r border-white/5">
                        <div class="font-bold text-slate-200 text-xs"><?= $jam['nama_jam'] ?></div>
                        <div class="text-[10px] text-slate-500 font-mono mt-0.5"><?= substr($jam['jam_mulai'],0,5) ?> - <?= substr($jam['jam_selesai'],0,5) ?></div>
                    </td>

                    <?php if ($is_istirahat): ?>
                        <td colspan="6" class="px-4 py-3 text-center bg-amber-500/10 border-r border-white/5">
                            <span class="text-amber-300 font-bold text-xs italic tracking-widest"><i class="fas fa-coffee mr-2"></i>ISTIRAHAT</span>
                        </td>
                    <?php else: ?>
                        <?php foreach ($hari_list as $h):
                            $item = $jadwal[$h][$jam['id_jam']] ?? null;
                            $is_today = ($h === $hari_ini);
                        ?>
                        <td class="px-2 py-2 border-r border-white/5 <?= $is_today ? 'bg-emerald-500/5' : '' ?>">
                            <?php if ($item):
                                $bg = kelas_color($item['nama_kelas'], $kelas_colors);
                            ?>
                            <div class="rounded-lg px-2 py-2 text-center" style="background-color: <?= $bg ?>20; border: 1px solid <?= $bg ?>40;">
                                <div class="font-bold text-xs text-white leading-tight"><?= clean($item['nama_mapel']) ?></div>
                                <div class="text-[10px] mt-0.5 truncate font-semibold" style="color: <?= $bg ?>;" title="<?= clean($item['nama_kelas']) ?>">
                                    <i class="fas fa-door-open mr-0.5 text-[8px]"></i><?= clean($item['nama_kelas']) ?>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-slate-700 text-xs">-</div>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (empty($jadwal)): ?>
<div class="glass rounded-xl p-8 text-center text-slate-400 mt-6">
    <i class="fas fa-calendar-times text-4xl mb-4 block text-slate-600"></i>
    Belum ada jadwal mengajar yang ditetapkan untuk Anda.
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
