<?php
$page_title = 'Jadwal Pelajaran';
require_once __DIR__ . '/../config/init.php';
cek_role(['siswa']);
cek_fitur('akademik');

$id = $_SESSION['user_id'];

// Ambil kelas siswa
$stmt = $pdo->prepare("SELECT id_kelas FROM tbl_siswa WHERE id_siswa = ?");
$stmt->execute([$id]);
$id_kelas = $stmt->fetchColumn();

if (!$id_kelas) {
    require_once __DIR__ . '/../template/header.php';
    require_once __DIR__ . '/../template/sidebar.php';
    require_once __DIR__ . '/../template/topbar.php';
    echo '<div class="glass rounded-xl p-8 text-center text-slate-400"><i class="fas fa-exclamation-circle text-4xl mb-4 block text-amber-400"></i>Anda belum terdaftar di kelas manapun.</div>';
    require_once __DIR__ . '/../template/footer.php';
    exit;
}

// Ambil data kelas
$stmt = $pdo->prepare("SELECT * FROM tbl_kelas WHERE id_kelas = ?");
$stmt->execute([$id_kelas]);
$kelas = $stmt->fetch();

// Ambil jam & hari
$jam_list  = $pdo->query("SELECT * FROM tbl_jam ORDER BY jam_mulai")->fetchAll();
$hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

// Ambil jadwal kelas
$jadwal = [];
$stmt = $pdo->prepare("
    SELECT j.*, m.nama_mapel, g.nama AS nama_guru, jm.nama_jam, jm.jam_mulai, jm.jam_selesai
    FROM tbl_jadwal j
    JOIN tbl_mapel m ON j.id_mapel = m.id_mapel
    LEFT JOIN tbl_guru g ON j.id_guru = g.id_guru
    JOIN tbl_jam jm ON j.id_jam = jm.id_jam
    WHERE j.id_kelas = ?
    ORDER BY jm.jam_mulai
");
$stmt->execute([$id_kelas]);
foreach ($stmt->fetchAll() as $j) {
    $jadwal[$j['hari']][$j['id_jam']] = $j;
}

// Palet warna
$pastel = ['#dbeafe','#dcfce7','#fef9c3','#fce7f3','#e0e7ff','#f3e8ff','#ccfbf1','#ffedd5','#fee2e2','#ecfeff','#fef3c7','#ede9fe'];
function mapel_color($name, $palette) { return $palette[abs(crc32($name)) % count($palette)]; }

// Hari ini
$hari_ini = ['Sunday'=>'','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'][date('l')] ?? '';

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<!-- Header -->
<div class="glass rounded-2xl p-6 mb-6 border border-blue-500/20 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl"></div>
    <div class="flex items-center justify-between relative z-10">
        <div>
            <h2 class="text-xl font-bold text-white flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600/30 border border-blue-500/30 flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-blue-400"></i>
                </div>
                Jadwal Pelajaran
            </h2>
            <p class="text-sm text-slate-400 mt-1">Kelas <span class="text-blue-400 font-semibold"><?= clean($kelas['nama_kelas']) ?></span></p>
        </div>
        <?php if ($hari_ini): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl px-4 py-2 text-sm">
            <i class="fas fa-clock text-emerald-400 mr-1"></i>
            <span class="text-emerald-300 font-semibold">Hari ini: <?= $hari_ini ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Grid Jadwal -->
<div class="glass rounded-2xl overflow-hidden border border-white/10 shadow-2xl">
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-black/40 text-center">
                    <th class="px-4 py-4 text-blue-300 text-xs font-bold uppercase tracking-wider border-r border-white/5 w-28">
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
                                $bg = mapel_color($item['nama_mapel'], $pastel);
                            ?>
                            <div class="rounded-lg px-2 py-2 text-center" style="background-color: <?= $bg ?>20; border: 1px solid <?= $bg ?>40;">
                                <div class="font-bold text-xs text-white leading-tight"><?= clean($item['nama_mapel']) ?></div>
                                <?php if (!empty($item['nama_guru'])): ?>
                                <div class="text-[10px] text-slate-400 mt-0.5 truncate" title="<?= clean($item['nama_guru']) ?>">
                                    <i class="fas fa-user-tie mr-0.5 text-[8px]"></i><?= clean($item['nama_guru']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-slate-600 text-xs">-</div>
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

<?php require_once __DIR__ . '/../template/footer.php'; ?>
