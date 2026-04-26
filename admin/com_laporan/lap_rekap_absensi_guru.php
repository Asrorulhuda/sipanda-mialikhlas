<?php
$page_title = 'Rekap Absensi Guru Bulanan';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','kepsek']);
cek_fitur('laporan');

$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$thn = isset($_GET['thn']) ? (int)$_GET['thn'] : (int)date('Y');

$teachers = $pdo->query("SELECT id_guru, nama, nip FROM tbl_guru WHERE status='Aktif' ORDER BY nama")->fetchAll();
$attendance = [];
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $bulan, $thn);

$start_date = "$thn-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-01";
$end_date = "$thn-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-$days_in_month";

// Get attendance for the month
$stmt = $pdo->prepare("SELECT id_guru, tanggal, jam_masuk, jam_keluar, status, keterangan FROM tbl_absensi_guru WHERE tanggal BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$att_raw = $stmt->fetchAll();

// Map to matrix [id_guru][day]
foreach ($att_raw as $row) {
    if (!$row['id_guru']) continue;
    $day = (int)date('d', strtotime($row['tanggal']));
    $attendance[$row['id_guru']][$day] = $row;
}

// Get GTK Schedule
$q_sch = $pdo->query("SELECT hari FROM tbl_setting_absen_guru WHERE jam_masuk IS NOT NULL");
$teacher_work_days = $q_sch->fetchAll(PDO::FETCH_COLUMN);

// Get national holidays for this month
$stmt = $pdo->prepare("SELECT tanggal, keterangan FROM tbl_hari_libur WHERE tanggal BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$holidays_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
$holidays_array = [];
foreach($holidays_raw as $h) {
    $holidays_array[$h['tanggal']] = $h['keterangan'];
}

require_once __DIR__ . '/../../template/header.php';
require_once __DIR__ . '/../../template/sidebar.php';
require_once __DIR__ . '/../../template/topbar.php';
?>

<div class="glass rounded-xl p-5 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
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
        
        <a href="cetak_rekap_absensi_guru.php?bulan=<?= $bulan ?>&thn=<?= $thn ?>" target="_blank" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg text-sm transition-all ml-auto shadow-lg shadow-purple-600/20"><i class="fas fa-print mr-2"></i>Cetak Rekap</a>
    </form>
</div>

<div class="glass rounded-xl p-5 border border-white/5 relative overflow-hidden">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold text-white uppercase italic tracking-widest"><i class="fas fa-chalkboard-teacher mr-2 text-blue-400"></i>Rekap Absensi Guru - <?= bulan_indo($bulan) ?> <?= $thn ?></h3>
        <div class="flex gap-4 text-[10px] font-bold uppercase italic">
            <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> <span class="text-slate-400">H: Hadir</span></div>
            <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500"></span> <span class="text-slate-400">S: Sakit</span></div>
            <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-500"></span> <span class="text-slate-400">I: Izin</span></div>
            <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span> <span class="text-slate-400">A: Alpha</span></div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-[10px] border-collapse min-w-[1000px]">
            <thead>
                <tr class="text-slate-500 border-b border-white/10">
                    <th class="py-2 px-1 text-left sticky left-0 bg-slate-900 border-r border-white/10" style="width: 30px;">No</th>
                    <th class="py-2 px-2 text-left sticky left-[30px] bg-slate-900 border-r border-white/10" style="width: 180px;">Nama Guru</th>
                    <?php for($d=1; $d<=$days_in_month; $d++): 
                        $date_str = "$thn-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
                        $day_num = date('w', strtotime($date_str)); // 0 (Sunday) to 6 (Saturday)
                        
                        $day_name_id = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][$day_num];
                        
                        $is_holiday = false;
                        $holiday_name = "";
                        if (isset($holidays_array[$date_str])) {
                            $is_holiday = true;
                            $holiday_name = $holidays_array[$date_str];
                        } elseif (!in_array($day_name_id, $teacher_work_days)) {
                            $is_holiday = true;
                            $holiday_name = "Bukan Hari Kerja (Libur)";
                        }
                    ?>
                    <th class="py-2 text-center <?= $is_holiday ? 'bg-rose-500/10 text-rose-400 cursor-help' : '' ?>" <?= $is_holiday ? 'title="'.clean($holiday_name).'"' : '' ?>><?= $d ?></th>
                    <?php endfor; ?>
                    <th class="py-2 px-1 text-center bg-emerald-600/20 text-emerald-400 border-l border-white/10">H</th>
                    <th class="py-2 px-1 text-center bg-blue-600/20 text-blue-400 border-l border-white/10">S</th>
                    <th class="py-2 px-1 text-center bg-purple-600/20 text-purple-400 border-l border-white/10">I</th>
                    <th class="py-2 px-1 text-center bg-rose-600/20 text-rose-400 border-l border-white/10">A</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($teachers as $i => $s): 
                    $count_h = 0; $count_s = 0; $count_i = 0; $count_a = 0;
                ?>
                <tr class="hover:bg-white/5">
                    <td class="py-2 px-1 text-slate-500 sticky left-0 bg-slate-900 border-r border-white/10"><?= $i+1 ?></td>
                    <td class="py-2 px-2 font-bold text-white sticky left-[30px] bg-slate-900 border-r border-white/10"><?= clean($s['nama']) ?></td>
                    <?php for($d=1; $d<=$days_in_month; $d++): 
                        $date_str = "$thn-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
                        $day_name_id = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w', strtotime($date_str))];
                        
                        $is_holiday = false;
                        if (isset($holidays_array[$date_str]) || !in_array($day_name_id, $teacher_work_days)) {
                            $is_holiday = true;
                        }
                        
                        $att = $attendance[$s['id_guru']][$d] ?? null;
                        
                        // Symbol mapping for Guru
                        $symbol = '';
                        if ($att) {
                            if ($att['status'] == 'COMPLETE' || $att['status'] == 'IN' || $att['status'] == 'OUT') $symbol = 'H';
                            elseif ($att['status'] == 'Sakit') $symbol = 'S';
                            elseif ($att['status'] == 'Izin') $symbol = 'I';
                        } else {
                            if (!$is_holiday && $date_str < date('Y-m-d')) $symbol = 'A';
                        }

                        $bg = 'bg-slate-800 text-slate-500';
                        if($symbol == 'H') { $bg = 'bg-emerald-500/20 text-emerald-400'; $count_h++; }
                        elseif($symbol == 'S') { $bg = 'bg-blue-500/20 text-blue-400'; $count_s++; }
                        elseif($symbol == 'I') { $bg = 'bg-purple-500/20 text-purple-400'; $count_i++; }
                        elseif($symbol == 'A') { $bg = 'bg-rose-500/20 text-rose-400'; $count_a++; }
                        
                        if($is_holiday && $symbol == '') $bg = 'bg-rose-500/5 text-slate-700';
                        
                        $cell_title = '';
                        if ($att && $att['jam_masuk']) {
                            $cell_title = 'Masuk: '.$att['jam_masuk'].($att['jam_keluar'] ? ' | Pulang: '.$att['jam_keluar'] : '').($att['keterangan'] ? ' | Ket: '.$att['keterangan'] : '');
                        }
                    ?>
                    <td class="py-2 text-center p-0.5">
                        <div class="w-full flex flex-col items-center justify-center p-1 rounded <?= $bg ?>" title="<?= clean($cell_title) ?>">
                            <span class="font-black text-[9px]"><?= $symbol ?: '-' ?></span>
                            <?php if ($att && $att['jam_masuk'] && $symbol == 'H'): ?>
                                <span class="text-[7px] leading-tight opacity-70"><?= substr($att['jam_masuk'], 0, 5) ?></span>
                                <span class="text-[7px] leading-tight opacity-70"><?= $att['jam_keluar'] ? substr($att['jam_keluar'], 0, 5) : '--' ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <?php endfor; ?>
                    <td class="py-2 px-1 text-center font-bold text-emerald-400 bg-emerald-600/5 border-l border-white/10"><?= $count_h ?></td>
                    <td class="py-2 px-1 text-center font-bold text-blue-400 bg-blue-600/5 border-l border-white/10"><?= $count_s ?></td>
                    <td class="py-2 px-1 text-center font-bold text-purple-400 bg-purple-600/5 border-l border-white/10"><?= $count_i ?></td>
                    <td class="py-2 px-1 text-center font-bold text-rose-400 bg-rose-600/5 border-l border-white/10"><?= $count_a ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../template/footer.php'; ?>
