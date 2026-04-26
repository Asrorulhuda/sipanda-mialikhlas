<?php
$page_title = 'Generate Payroll';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);
cek_fitur('payroll');

$bulan = $_GET['bulan'] ?? date('n');
$tahun = $_GET['tahun'] ?? date('Y');

// Helper to calculate working days based on tbl_hari_libur + jadwal guru
function getWorkingDays($bulan, $tahun, $pdo) {
    $total_days = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
    
    // Get holidays from Setting Absen (tbl_hari_libur)
    $holidays = $pdo->prepare("SELECT tanggal FROM tbl_hari_libur WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
    $holidays->execute([$bulan, $tahun]);
    $holiday_dates = $holidays->fetchAll(PDO::FETCH_COLUMN);
    
    // Get teacher schedule days (only days with jam_masuk are working days)
    $hari_map = ['Senin'=>1,'Selasa'=>2,'Rabu'=>3,'Kamis'=>4,'Jumat'=>5,'Sabtu'=>6,'Minggu'=>7];
    $q = $pdo->query("SELECT hari FROM tbl_setting_absen_guru WHERE jam_masuk IS NOT NULL AND jam_masuk != ''");
    $active_days = [];
    foreach ($q->fetchAll() as $row) {
        if (isset($hari_map[$row['hari']])) {
            $active_days[] = $hari_map[$row['hari']];
        }
    }
    // Fallback: if no schedule configured, assume Mon-Sat (1-6)
    if (empty($active_days)) $active_days = [1,2,3,4,5,6];
    
    $workdays = 0;
    for ($d = 1; $d <= $total_days; $d++) {
        $time = mktime(0, 0, 0, $bulan, $d, $tahun);
        $date = date('Y-m-d', $time);
        $day_of_week = date('N', $time); // 1=Mon, 7=Sun
        
        if (in_array($day_of_week, $active_days) && !in_array($date, $holiday_dates)) {
            $workdays++;
        }
    }
    return $workdays;
}

// Helper: Get attendance breakdown matching lap_rekap_absensi_guru.php logic exactly
function getAttendanceBreakdown($id_guru, $bulan, $tahun, $pdo) {
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
    $start_date = "$tahun-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-01";
    $end_date = "$tahun-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-$days_in_month";
    
    // Fetch attendance records for this guru
    $stmt = $pdo->prepare("SELECT tanggal, status FROM tbl_absensi_guru WHERE id_guru = ? AND tanggal BETWEEN ? AND ?");
    $stmt->execute([$id_guru, $start_date, $end_date]);
    $att_raw = $stmt->fetchAll();
    $attendance = [];
    foreach ($att_raw as $row) {
        $day = (int)date('d', strtotime($row['tanggal']));
        $attendance[$day] = $row;
    }
    
    // Get teacher working schedule days
    $q_sch = $pdo->query("SELECT hari FROM tbl_setting_absen_guru WHERE jam_masuk IS NOT NULL");
    $teacher_work_days = $q_sch->fetchAll(PDO::FETCH_COLUMN);
    
    // Get holidays
    $stmt = $pdo->prepare("SELECT tanggal FROM tbl_hari_libur WHERE tanggal BETWEEN ? AND ?");
    $stmt->execute([$start_date, $end_date]);
    $holidays_array = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $count_h = 0; $count_s = 0; $count_i = 0; $count_a = 0;
    $today = date('Y-m-d');
    
    for ($d = 1; $d <= $days_in_month; $d++) {
        $date_str = "$tahun-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
        $day_name_id = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w', strtotime($date_str))];
        
        $is_holiday = in_array($date_str, $holidays_array) || !in_array($day_name_id, $teacher_work_days);
        
        $att = $attendance[$d] ?? null;
        $symbol = '';
        if ($att) {
            if (in_array($att['status'], ['COMPLETE', 'IN', 'OUT'])) $symbol = 'H';
            elseif ($att['status'] == 'Sakit') $symbol = 'S';
            elseif ($att['status'] == 'Izin') $symbol = 'I';
        } else {
            if (!$is_holiday && $date_str < $today) $symbol = 'A';
        }
        
        if ($symbol == 'H') $count_h++;
        elseif ($symbol == 'S') $count_s++;
        elseif ($symbol == 'I') $count_i++;
        elseif ($symbol == 'A') $count_a++;
    }
    
    return ['hadir' => $count_h, 'sakit' => $count_s, 'izin' => $count_i, 'alpha' => $count_a];
}


$total_workdays = getWorkingDays($bulan, $tahun, $pdo);
$settings = $pdo->query("SELECT * FROM tbl_payroll_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

if (isset($_POST['process_payroll'])) {
    $ids = $_POST['ids'] ?? [];
    foreach ($ids as $id_guru) {
        $cfg_stmt = $pdo->prepare("SELECT * FROM tbl_payroll_config WHERE id_guru = ?");
        $cfg_stmt->execute([$id_guru]);
        $cfg = $cfg_stmt->fetch();
        
        $guru_stmt = $pdo->prepare("SELECT tmt FROM tbl_guru WHERE id_guru = ?");
        $guru_stmt->execute([$id_guru]);
        $guru = $guru_stmt->fetch();

        // Attendance Count - using same logic as lap_rekap_absensi_guru.php
        $att_breakdown = getAttendanceBreakdown($id_guru, $bulan, $tahun, $pdo);
        $hadir = $att_breakdown['hadir'];
        $sakit = $att_breakdown['sakit'];
        $izin = $att_breakdown['izin'];
        $alpha = $att_breakdown['alpha'];

        // 1. Gaji Pokok
        $jam_kerja = (int)($_POST['jam_kerja'][$id_guru] ?? 0);
        $rate_jtm = ($cfg['rate_jtm_guru'] > 0) ? $cfg['rate_jtm_guru'] : ($settings['rate_jtm'] ?? 0);
        $gapok_jtm = $jam_kerja * $rate_jtm;

        $thn_kerja = 0;
        if ($guru['tmt']) {
            $diff = (new DateTime($guru['tmt']))->diff(new DateTime("$tahun-$bulan-01"));
            $thn_kerja = $diff->y;
        }
        $gapok_masa_kerja = $thn_kerja * ($settings['rate_masa_kerja'] ?? 0);

        // 2. Tunjangan
        $score_total = ($cfg['score_jabatan'] ?? 0) + ($cfg['score_wali_kelas'] ?? 0) + ($cfg['score_bid_pddk'] ?? 0) + ($cfg['score_manual'] ?? 0);
        $tunjangan_jabatan = $score_total * ($settings['rate_scoring'] ?? 0);
        
        $rate_hadir = ($cfg['rate_kehadiran_guru'] > 0) ? $cfg['rate_kehadiran_guru'] : ($settings['rate_kehadiran'] ?? 0);
        $tunjangan_kehadiran = $hadir * $rate_hadir;

        // 3. Kelebihan & Potongan JTM
        $jam_lebih = (int)($_POST['jam_lebih'][$id_guru] ?? 0);
        $kelebihan_jtm = $jam_lebih * ($settings['rate_kelebihan_jtm'] ?? 0);
        
        $jam_pot = (int)($_POST['jam_pot'][$id_guru] ?? 0);
        $potongan_jtm = $jam_pot * ($settings['rate_potongan_jtm'] ?? 0);

        // 4. Comparison with Previous Month
        $bln_lalu = ($bulan == 1) ? 12 : $bulan - 1;
        $thn_lalu = ($bulan == 1) ? $tahun - 1 : $tahun;
        $prev_stmt = $pdo->prepare("SELECT total_diterima FROM tbl_payroll_history WHERE id_guru=? AND bulan=? AND tahun=?");
        $prev_stmt->execute([$id_guru, $bln_lalu, $thn_lalu]);
        $penerimaan_lalu = $prev_stmt->fetchColumn() ?: 0;

        $total_sekarang = ($gapok_jtm + $gapok_masa_kerja + $tunjangan_jabatan + $tunjangan_kehadiran + $kelebihan_jtm + ($cfg['tunjangan_tetap'] ?? 0)) - $potongan_jtm;
        $selisih = $total_sekarang - $penerimaan_lalu;

        // Save
        $ins = $pdo->prepare("INSERT INTO tbl_payroll_history 
            (id_guru, bulan, tahun, gapok_jtm, gapok_masa_kerja, tunjangan_jabatan, tunjangan_kehadiran, potongan_jtm, total_diterima, 
             jam_kerja_jtm, kelebihan_jtm, pot_jtm_jam, rate_jtm_pilih, rate_kehadiran_pilih, score_total, 
             hari_hadir, hari_sakit, hari_izin, hari_alpha, hari_kerja_efektif,
             selisih_bulan_lalu, status) 
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE 
            gapok_jtm=VALUES(gapok_jtm), gapok_masa_kerja=VALUES(gapok_masa_kerja), tunjangan_jabatan=VALUES(tunjangan_jabatan), 
            tunjangan_kehadiran=VALUES(tunjangan_kehadiran), potongan_jtm=VALUES(potongan_jtm), total_diterima=VALUES(total_diterima),
            jam_kerja_jtm=VALUES(jam_kerja_jtm), kelebihan_jtm=VALUES(kelebihan_jtm), pot_jtm_jam=VALUES(pot_jtm_jam),
            rate_jtm_pilih=VALUES(rate_jtm_pilih), rate_kehadiran_pilih=VALUES(rate_kehadiran_pilih), score_total=VALUES(score_total),
            hari_hadir=VALUES(hari_hadir), hari_sakit=VALUES(hari_sakit), hari_izin=VALUES(hari_izin), hari_alpha=VALUES(hari_alpha),
            hari_kerja_efektif=VALUES(hari_kerja_efektif),
            selisih_bulan_lalu=VALUES(selisih_bulan_lalu)");
        $ins->execute([
            $id_guru, $bulan, $tahun, $gapok_jtm, $gapok_masa_kerja, $tunjangan_jabatan, $tunjangan_kehadiran, $potongan_jtm, $total_sekarang,
            $jam_kerja, $kelebihan_jtm, $jam_pot, $rate_jtm, $rate_hadir, $score_total, 
            $hadir, $sakit, $izin, $alpha, $total_workdays,
            $selisih, 'Draft'
        ]);
    }
    flash('msg', 'Payroll V2 berhasil di-generate!');
    header("Location: payroll_history.php?bulan=$bulan&tahun=$tahun"); exit;
}

$guru_list = $pdo->query("SELECT g.id_guru, g.nama, g.nip, g.tmt, c.rate_jtm_guru, c.rate_kehadiran_guru, c.jtm_jumlah 
                         FROM tbl_guru g LEFT JOIN tbl_payroll_config c ON g.id_guru = c.id_guru 
                         WHERE g.status='Aktif' ORDER BY g.nama")->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex flex-col md:flex-row items-center justify-between mb-8 gap-4">
    <div>
        <h2 class="text-2xl font-black italic tracking-tighter uppercase text-white"><i class="fas fa-calculator mr-2 text-emerald-500"></i>Generate Payroll V2</h2>
        <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Hitung gaji dengan rumus custom dan perbandingan bulan lalu</p>
    </div>
</div>

<?= alert_flash('msg') ?>

<div class="glass rounded-3xl p-6 border border-white/5 mb-8">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-[9px] text-slate-500 uppercase font-black mb-1.5">Bulan Pelaporan</label>
            <select name="bulan" class="bg-slate-900 border border-white/10 rounded-xl px-4 py-2 text-xs text-white">
                <?php for($i=1;$i<=12;$i++): ?>
                <option value="<?= $i ?>" <?= $bulan == $i ? 'selected' : '' ?>><?= bulan_indo($i) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label class="block text-[9px] text-slate-500 uppercase font-black mb-1.5">Tahun</label>
            <select name="tahun" class="bg-slate-900 border border-white/10 rounded-xl px-4 py-2 text-xs text-white">
                <?php $y = date('Y'); for($i=$y-2;$i<=$y+1;$i++): ?>
                <option value="<?= $i ?>" <?= $tahun == $i ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="submit" class="bg-slate-700 hover:bg-slate-600 px-6 py-2 rounded-xl text-xs font-bold text-white transition-all italic">Tampilkan Data</button>
        <div class="ml-auto text-right">
            <div class="text-[10px] text-slate-500 uppercase font-black tracking-widest">Hari Kerja Efektif</div>
            <div class="text-xl font-black text-white italic"><?= $total_workdays ?> <span class="text-xs font-normal text-slate-400 not-italic">Hari</span></div>
        </div>
    </form>
</div>

<form method="POST">
    <div class="glass rounded-3xl p-6 border border-white/5 relative overflow-hidden">
        <div class="table-container pt-2">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="text-slate-500 border-b border-white/10 italic text-[10px] uppercase tracking-widest">
                        <th class="py-4 px-2"><input type="checkbox" id="checkAll" class="rounded"></th>
                        <th class="py-4">Guru & Masa Kerja</th>
                        <th class="py-4 text-center">JTM (Jam)</th>
                        <th class="py-4 text-center">Lebih (Jam)</th>
                        <th class="py-4 text-center">Potong (Jam)</th>
                        <th class="py-4 text-center">Rekap Kehadiran Bulan Ini</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($guru_list as $g): 
                        $att_data = getAttendanceBreakdown($g['id_guru'], $bulan, $tahun, $pdo);
                        $hadir = $att_data['hadir'];
                        $sakit = $att_data['sakit'];
                        $izin = $att_data['izin'];
                        $alpha = $att_data['alpha'];
                        
                        $mk = 0;
                        if($g['tmt']) {
                            $mk = (new DateTime($g['tmt']))->diff(new DateTime("$tahun-$bulan-01"))->y;
                        }
                    ?>
                    <tr class="hover:bg-white/5 transition-all">
                        <td class="py-4 px-2"><input type="checkbox" name="ids[]" value="<?= $g['id_guru'] ?>" class="checkItem rounded"></td>
                        <td class="py-4">
                            <div class="font-bold text-white"><?= clean($g['nama']) ?></div>
                            <div class="text-[9px] text-emerald-500 font-bold italic"><?= $mk ?> Tahun Masa Kerja</div>
                        </td>
                        <td class="py-4 text-center">
                            <input type="number" name="jam_kerja[<?= $g['id_guru'] ?>]" value="<?= (int)($g['jtm_jumlah'] ?? 0) ?>" class="w-20 bg-slate-900 border border-white/10 rounded-lg px-2 py-1.5 text-xs text-white text-center font-mono">
                        </td>
                        <td class="py-4 text-center">
                            <input type="number" name="jam_lebih[<?= $g['id_guru'] ?>]" value="0" class="w-20 bg-slate-900 border border-white/10 rounded-lg px-2 py-1.5 text-xs text-amber-400 text-center font-mono">
                        </td>
                        <td class="py-4 text-center">
                            <input type="number" name="jam_pot[<?= $g['id_guru'] ?>]" value="0" class="w-20 bg-slate-900 border border-white/10 rounded-lg px-2 py-1.5 text-xs text-rose-400 text-center font-mono">
                        </td>
                        <td class="py-4">
                            <div class="flex items-center justify-center gap-2">
                                <div class="flex flex-col items-center px-2 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/20">
                                    <span class="text-[8px] text-emerald-500/70 font-bold uppercase">Hadir</span>
                                    <span class="text-sm font-black text-emerald-400"><?= $hadir ?></span>
                                </div>
                                <div class="flex flex-col items-center px-2 py-1 rounded-lg bg-blue-500/10 border border-blue-500/20">
                                    <span class="text-[8px] text-blue-500/70 font-bold uppercase">Sakit</span>
                                    <span class="text-sm font-black text-blue-400"><?= $sakit ?></span>
                                </div>
                                <div class="flex flex-col items-center px-2 py-1 rounded-lg bg-purple-500/10 border border-purple-500/20">
                                    <span class="text-[8px] text-purple-500/70 font-bold uppercase">Izin</span>
                                    <span class="text-sm font-black text-purple-400"><?= $izin ?></span>
                                </div>
                                <div class="flex flex-col items-center px-2 py-1 rounded-lg bg-rose-500/10 border border-rose-500/20">
                                    <span class="text-[8px] text-rose-500/70 font-bold uppercase">Alpha</span>
                                    <span class="text-sm font-black text-rose-400"><?= $alpha ?></span>
                                </div>
                            </div>
                            <div class="text-[9px] text-slate-500 italic text-center mt-1">Rate: <?= $g['rate_kehadiran_guru'] > 0 ? 'Custom' : 'Global' ?> | Efektif: <?= $total_workdays ?> Hari</div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-8 flex justify-end">
            <button type="submit" name="process_payroll" class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 px-10 py-4 rounded-2xl text-xs font-black uppercase text-white shadow-xl shadow-emerald-600/20 transition-all hover:-translate-y-1">
                <i class="fas fa-magic mr-2"></i>Generate Payroll V2
            </button>
        </div>
    </div>
</form>

<script>
document.getElementById('checkAll').onclick = function() {
    var checkboxes = document.getElementsByClassName('checkItem');
    for (var checkbox of checkboxes) {
        checkbox.checked = this.checked;
    }
}
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
