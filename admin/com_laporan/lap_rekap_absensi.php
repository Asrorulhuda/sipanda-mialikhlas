<?php
$page_title = 'Rekap Absensi Bulanan';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','bendahara','kepsek','guru']);
cek_fitur('laporan');

$kelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : 0;
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$thn = isset($_GET['thn']) ? (int)$_GET['thn'] : (int)date('Y');

$kelas_list = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();

$students = [];
$attendance = [];
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $bulan, $thn);

if ($kelas) {
    // Get students
    $stmt = $pdo->prepare("SELECT id_siswa, nama, nisn FROM tbl_siswa WHERE id_kelas = ? AND status='Aktif' ORDER BY nama");
    $stmt->execute([$kelas]);
    $students = $stmt->fetchAll();

    // Get attendance for the month
    $start_date = "$thn-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-01";
    $end_date = "$thn-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-$days_in_month";
    
    $stmt = $pdo->prepare("SELECT id_siswa, tanggal, jam_masuk, jam_keluar, keterangan FROM tbl_absensi_siswa WHERE id_siswa IN (SELECT id_siswa FROM tbl_siswa WHERE id_kelas=?) AND tanggal BETWEEN ? AND ?");
    $stmt->execute([$kelas, $start_date, $end_date]);
    $att_raw = $stmt->fetchAll();

    // Map to matrix [id_siswa][day]
    foreach ($att_raw as $row) {
        $day = (int)date('d', strtotime($row['tanggal']));
        $attendance[$row['id_siswa']][$day] = $row;
    }

    // Get class schedule days
    $stmt = $pdo->prepare("SELECT hari FROM tbl_setting_absen_kelas WHERE id_kelas = ?");
    $stmt->execute([$kelas]);
    $class_schedule = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get national holidays for this month
    $stmt = $pdo->prepare("SELECT tanggal, keterangan FROM tbl_hari_libur WHERE tanggal BETWEEN ? AND ?");
    $stmt->execute([$start_date, $end_date]);
    $holidays_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $holidays_array = [];
    foreach($holidays_raw as $h) {
        $holidays_array[$h['tanggal']] = $h['keterangan'];
    }
}

require_once __DIR__ . '/../../template/header.php';
require_once __DIR__ . '/../../template/sidebar.php';
require_once __DIR__ . '/../../template/topbar.php';
?>

<div class="glass rounded-xl p-5 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-slate-400 mb-1">Kelas</label>
            <select name="kelas" required class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                <option value="">-- Pilih Kelas --</option>
                <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k['id_kelas'] ?>" <?= $kelas == $k['id_kelas'] ? 'selected' : '' ?>><?= clean($k['nama_kelas']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-400 mb-1">Bulan</label>
            <select name="bulan" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                <?php for ($m=1; $m<=12; $m++): ?>
                    <option value="<?= $m ?>" <?= $bulan == $m ? 'selected' : '' ?>><?= bulan_indo($m) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-400 mb-1">Tahun</label>
            <input type="number" name="thn" value="<?= $thn ?>" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm text-white w-24">
        </div>
        <button class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg text-sm transition-all shadow-lg shadow-blue-600/20"><i class="fas fa-filter mr-1"></i>Filter</button>
        
        <?php if ($kelas): ?>
        <a href="cetak_rekap_absensi.php?kelas=<?= $kelas ?>&bulan=<?= $bulan ?>&thn=<?= $thn ?>" target="_blank" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg text-sm transition-all ml-auto shadow-lg shadow-purple-600/20"><i class="fas fa-print mr-2"></i>Cetak</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($kelas): ?>
<div class="glass rounded-xl p-5 border border-white/5 relative overflow-hidden">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold text-white"><i class="fas fa-calendar-alt mr-2 text-blue-400"></i>Rekap Absensi - <?= bulan_indo($bulan) ?> <?= $thn ?></h3>
        <div class="flex gap-4 text-[10px]">
            <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> <span class="text-slate-400">H: Hadir</span></div>
            <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500"></span> <span class="text-slate-400">T: Terlambat</span></div>
            <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500"></span> <span class="text-slate-400">S: Sakit</span></div>
            <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-500"></span> <span class="text-slate-400">I: Izin</span></div>
            <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span> <span class="text-slate-400">A: Alpha</span></div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-[10px] border-collapse min-w-[800px]">
            <thead>
                <tr class="text-slate-500 border-b border-white/10">
                    <th class="py-2 px-1 text-left sticky left-0 bg-slate-900 border-r border-white/10" style="width: 30px;">No</th>
                    <th class="py-2 px-2 text-left sticky left-[30px] bg-slate-900 border-r border-white/10" style="width: 150px;">Nama</th>
                    <?php for($d=1; $d<=$days_in_month; $d++): 
                        $date_str = "$thn-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
                        $day_name_id = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w', strtotime($date_str))];
                        
                        $is_holiday = false;
                        $holiday_name = "";
                        if (isset($holidays_array[$date_str])) {
                            $is_holiday = true;
                            $holiday_name = $holidays_array[$date_str];
                        } elseif (!in_array($day_name_id, $class_schedule)) {
                            $is_holiday = true;
                            $holiday_name = "Tidak ada jadwal";
                        }
                    ?>
                    <th class="py-2 text-center <?= $is_holiday ? 'bg-rose-500/10 text-rose-400 cursor-help' : '' ?>" <?= $is_holiday ? 'title="'.clean($holiday_name).'"' : '' ?>><?= $d ?></th>
                    <?php endfor; ?>
                    <th class="py-2 px-1 text-center bg-emerald-600/20 text-emerald-400 border-l border-white/10">H</th>
                    <th class="py-2 px-1 text-center bg-amber-600/20 text-amber-400 border-l border-white/10">T</th>
                    <th class="py-2 px-1 text-center bg-blue-600/20 text-blue-400 border-l border-white/10">S</th>
                    <th class="py-2 px-1 text-center bg-purple-600/20 text-purple-400 border-l border-white/10">I</th>
                    <th class="py-2 px-1 text-center bg-rose-600/20 text-rose-400 border-l border-white/10">A</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($students as $i => $s): 
                    $count_h = 0; $count_t = 0; $count_s = 0; $count_i = 0; $count_a = 0;
                ?>
                <tr class="hover:bg-white/5">
                    <td class="py-2 px-1 text-slate-500 sticky left-0 bg-slate-900 border-r border-white/10"><?= $i+1 ?></td>
                    <td class="py-2 px-2 font-medium text-white sticky left-[30px] bg-slate-900 border-r border-white/10"><?= clean($s['nama']) ?></td>
                    <?php for($d=1; $d<=$days_in_month; $d++): 
                        $date_str = "$thn-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
                        $day_name_id = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w', strtotime($date_str))];
                        
                        $is_holiday = false;
                        $holiday_name = "";
                        if (isset($holidays_array[$date_str])) {
                            $is_holiday = true;
                            $holiday_name = $holidays_array[$date_str];
                        } elseif (!in_array($day_name_id, $class_schedule)) {
                            $is_holiday = true;
                            $holiday_name = "Tidak ada jadwal";
                        }
                        
                        $att = $attendance[$s['id_siswa']][$d] ?? null;
                        $symbol = get_absensi_symbol($att['keterangan'] ?? null, $is_holiday ? '2099-01-01' : $date_str);
                        $bg = 'bg-slate-800 text-slate-500';
                        if($symbol == 'H') { $bg = 'bg-emerald-500/20 text-emerald-400'; $count_h++; }
                        elseif($symbol == 'T') { $bg = 'bg-amber-500/20 text-amber-400'; $count_t++; }
                        elseif($symbol == 'S') { $bg = 'bg-blue-500/20 text-blue-400'; $count_s++; }
                        elseif($symbol == 'I') { $bg = 'bg-purple-500/20 text-purple-400'; $count_i++; }
                        elseif($symbol == 'A') { $bg = 'bg-rose-500/20 text-rose-400'; $count_a++; }
                        
                        if($is_holiday && $symbol == '') $bg = 'bg-rose-500/5 text-slate-700'; // Background for holiday
                        
                        $cell_title = '';
                        if ($is_holiday) {
                            $cell_title = $holiday_name;
                        } elseif ($att && $att['jam_masuk']) {
                            $cell_title = 'Masuk: '.$att['jam_masuk'].' | Pulang: '.$att['jam_keluar'];
                        }
                    ?>
                    <td class="py-2 text-center p-0.5">
                        <div class="w-full flex flex-col items-center justify-center p-1 rounded <?= $bg ?>" title="<?= clean($cell_title) ?>">
                            <span class="font-bold text-[9px]"><?= $symbol ?: '-' ?></span>
                            <?php if ($att): ?>
                                <span class="text-[7px] leading-tight opacity-70"><?= $att['jam_masuk'] ? substr($att['jam_masuk'], 0, 5) : '--' ?></span>
                                <span class="text-[7px] leading-tight opacity-70"><?= $att['jam_keluar'] ? substr($att['jam_keluar'], 0, 5) : '--' ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <?php endfor; ?>
                    <td class="py-2 px-1 text-center font-bold text-emerald-400 bg-emerald-600/5 border-l border-white/10"><?= $count_h ?></td>
                    <td class="py-2 px-1 text-center font-bold text-amber-400 bg-amber-600/5 border-l border-white/10"><?= $count_t ?></td>
                    <td class="py-2 px-1 text-center font-bold text-blue-400 bg-blue-600/5 border-l border-white/10"><?= $count_s ?></td>
                    <td class="py-2 px-1 text-center font-bold text-purple-400 bg-purple-600/5 border-l border-white/10"><?= $count_i ?></td>
                    <td class="py-2 px-1 text-center font-bold text-rose-400 bg-rose-600/5 border-l border-white/10"><?= $count_a ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
    <div class="glass rounded-xl p-10 text-center">
        <div class="w-16 h-16 bg-blue-600/20 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-400 text-2xl">
            <i class="fas fa-info-circle"></i>
        </div>
        <h4 class="text-white font-bold mb-1">Pilih Kelas Terlebih Dahulu</h4>
        <p class="text-sm text-slate-400">Gunakan filter di atas untuk menampilkan rekap absensi bulanan.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../template/footer.php'; ?>
