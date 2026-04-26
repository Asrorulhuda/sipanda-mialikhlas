<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../config/cetak_helper.php';
cek_role(['admin','kepsek']);

$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$thn = isset($_GET['thn']) ? (int)$_GET['thn'] : (int)date('Y');

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $bulan, $thn);

// Get teachers
$teachers = $pdo->query("SELECT id_guru, nama, nip FROM tbl_guru WHERE status='Aktif' ORDER BY nama")->fetchAll();

// Get attendance
$start_date = "$thn-$bulan-01";
$end_date = "$thn-$bulan-$days_in_month";
$stmt = $pdo->prepare("SELECT id_guru, tanggal, jam_masuk, jam_keluar, status, keterangan FROM tbl_absensi_guru WHERE tanggal BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$att_raw = $stmt->fetchAll();

$attendance = [];
foreach ($att_raw as $row) {
    if (!$row['id_guru']) continue;
    $day = (int)date('d', strtotime($row['tanggal']));
    $attendance[$row['id_guru']][$day] = $row;
}

// Get GTK Schedule
$q_sch = $pdo->query("SELECT hari FROM tbl_setting_absen_guru WHERE jam_masuk IS NOT NULL");
$teacher_work_days = $q_sch->fetchAll(PDO::FETCH_COLUMN);

// Get national holidays
$stmt = $pdo->prepare("SELECT tanggal, keterangan FROM tbl_hari_libur WHERE tanggal BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$holidays_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
$holidays_array = [];
foreach($holidays_raw as $h) {
    $holidays_array[$h['tanggal']] = $h['keterangan'];
}

// Build Legend for Holidays
$holiday_legends = [];
for($dx=1; $dx<=$days_in_month; $dx++) {
    $date_str = "$thn-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($dx, 2, '0', STR_PAD_LEFT);
    $day_name_id = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w', strtotime($date_str))];
    if (isset($holidays_array[$date_str])) {
        $holiday_legends[] = "Tgl $dx (".$holidays_array[$date_str].")";
    } elseif (!in_array($day_name_id, $teacher_work_days)) {
        $holiday_legends[] = "Tgl $dx ($day_name_id/Libur)";
    }
}
$holiday_text = !empty($holiday_legends) ? implode(', ', $holiday_legends) : 'Tidak ada';

cetak_header('Rekap Absensi Guru - ' . bulan_indo($bulan) . ' ' . $thn, $setting);
?>

<style>
    @page { size: landscape; margin: 8mm; }
    body { max-width: 100% !important; padding: 0 !important; font-size: 8px !important; }
    .print-container { width: 100% !important; max-width: 100% !important; box-shadow: none !important; border: none !important; padding: 0 !important; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table th, table td { border: 1px solid #333; padding: 1px !important; font-size: 6.5px !important; text-align: center; overflow: hidden; }
    thead th { background-color: #f3f4f6; }
    .nama-head { text-align: left; padding-left: 3px !important; width: 140px; }
    .no-head { width: 22px; }
    .summary-head { width: 18px; font-weight: bold; }
    .symbol-h { font-weight: bold; color: #059669; }
    .symbol-s { font-weight: bold; color: #2563eb; }
    .symbol-i { font-weight: bold; color: #7c3aed; }
    .symbol-a { font-weight: bold; color: #dc2626; }
    .time-val { display: block; font-size: 5.5px; color: #666; line-height: 1; }
</style>

<div style="margin-bottom: 8px;">
    <p style="font-size: 10px; font-weight: bold; margin: 0;">Rekap Kehadiran Guru & Staf (GTK)</p>
    <p style="margin: 0; opacity: 0.8;">Bulan/Tahun: <?= bulan_indo($bulan) ?> / <?= $thn ?></p>
</div>

<table>
    <thead>
        <tr>
            <th class="no-head">No</th>
            <th class="nama-head">Nama Guru/Staf</th>
            <?php for($d=1; $d<=$days_in_month; $d++): 
                $date_str = "$thn-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
                $day_name_id = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w', strtotime($date_str))];
                $is_holiday = isset($holidays_array[$date_str]) || !in_array($day_name_id, $teacher_work_days);
            ?>
            <th style="<?= $is_holiday ? 'background-color:#fee2e2; color:#b91c1c;' : '' ?>"><?= $d ?></th>
            <?php endfor; ?>
            <th class="summary-head" style="background-color:#dcfce7; color:#15803d;">H</th>
            <th class="summary-head" style="background-color:#dbeafe; color:#1d4ed8;">S</th>
            <th class="summary-head" style="background-color:#f3e8ff; color:#7e22ce;">I</th>
            <th class="summary-head" style="background-color:#fee2e2; color:#b91c1c;">A</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($teachers as $i => $s): 
            $count_h = 0; $count_s = 0; $count_i = 0; $count_a = 0;
        ?>
        <tr>
            <td class="text-center"><?= $i+1 ?></td>
            <td class="nama-head" style="white-space: nowrap;"><?= clean($s['nama']) ?></td>
            <?php for($d=1; $d<=$days_in_month; $d++): 
                $date_str = "$thn-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
                $day_name_id = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w', strtotime($date_str))];
                $is_holiday = isset($holidays_array[$date_str]) || !in_array($day_name_id, $teacher_work_days);
                
                $att = $attendance[$s['id_guru']][$d] ?? null;
                $symbol = '';
                if ($att) {
                    if ($att['status'] == 'COMPLETE' || $att['status'] == 'IN' || $att['status'] == 'OUT') { $symbol = 'H'; $count_h++; }
                    elseif ($att['status'] == 'Sakit') { $symbol = 'S'; $count_s++; }
                    elseif ($att['status'] == 'Izin') { $symbol = 'I'; $count_i++; }
                } else {
                    if (!$is_holiday && $date_str < date('Y-m-d')) { $symbol = 'A'; $count_a++; }
                }

                $s_class = '';
                if($symbol == 'H') $s_class = 'symbol-h';
                elseif($symbol == 'S') $s_class = 'symbol-s';
                elseif($symbol == 'I') $s_class = 'symbol-i';
                elseif($symbol == 'A') $s_class = 'symbol-a';
            ?>
            <td style="<?= ($is_holiday && empty($symbol)) ? 'background-color:#fef2f2;' : '' ?>">
                <span class="<?= $s_class ?>"><?= $symbol ?: '-' ?></span>
                <?php if ($att && $att['jam_masuk'] && $symbol == 'H'): ?>
                    <span class="time-val"><?= substr($att['jam_masuk'], 0, 5) ?></span>
                    <span class="time-val"><?= $att['jam_keluar'] ? substr($att['jam_keluar'], 0, 5) : '--' ?></span>
                <?php endif; ?>
            </td>
            <?php endfor; ?>
            <td style="background-color:#f0fdf4; font-weight:bold; color:#15803d;"><?= $count_h ?></td>
            <td style="background-color:#eff6ff; font-weight:bold; color:#1d4ed8;"><?= $count_s ?></td>
            <td style="background-color:#faf5ff; font-weight:bold; color:#7e22ce;"><?= $count_i ?></td>
            <td style="background-color:#fef2f2; font-weight:bold; color:#b91c1c;"><?= $count_a ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div style="margin-top: 10px; display: flex; justify-content: space-between; font-size: 7px;">
    <div style="width: 75%;">
        <p><b>Keterangan:</b> H: Hadir, S: Sakit, I: Izin, A: Alpha. <i>(Baris kecil: Jam Masuk & Pulang)</i></p>
        <p style="margin-top: 3px; color: #b91c1c;"><b>Holidays/Minggu:</b> <?= $holiday_text ?></p>
    </div>
    <div style="text-align: center; width: 180px;">
        <p>Dicetak pada: <?= date('d/m/Y H:i') ?></p>
        <p>Kepala Sekolah / Admin,</p>
        <br><br><br>
        <p><b>( ____________________ )</b></p>
    </div>
</div>

<?php cetak_footer($setting); ?>
