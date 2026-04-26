<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../config/cetak_helper.php';
cek_role(['admin','bendahara','kepsek','guru']);

$kelas_id = isset($_GET['kelas']) ? (int)$_GET['kelas'] : 0;
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$thn = isset($_GET['thn']) ? (int)$_GET['thn'] : (int)date('Y');

if (!$kelas_id) die("Pilih kelas terlebih dahulu.");

$kelas = $pdo->query("SELECT * FROM tbl_kelas WHERE id_kelas = $kelas_id")->fetch();
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $bulan, $thn);

// Get students
$stmt = $pdo->prepare("SELECT id_siswa, nama, nisn FROM tbl_siswa WHERE id_kelas = ? AND status='Aktif' ORDER BY nama");
$stmt->execute([$kelas_id]);
$students = $stmt->fetchAll();

// Get attendance (Fetch both Masuk & Keluar)
$start_date = "$thn-$bulan-01";
$end_date = "$thn-$bulan-$days_in_month";
$stmt = $pdo->prepare("SELECT id_siswa, tanggal, jam_masuk, jam_keluar, keterangan FROM tbl_absensi_siswa WHERE id_siswa IN (SELECT id_siswa FROM tbl_siswa WHERE id_kelas=?) AND tanggal BETWEEN ? AND ?");
$stmt->execute([$kelas_id, $start_date, $end_date]);
$att_raw = $stmt->fetchAll();

$attendance = [];
foreach ($att_raw as $row) {
    $day = (int)date('d', strtotime($row['tanggal']));
    $attendance[$row['id_siswa']][$day] = $row;
}

// Get class schedule days
$stmt = $pdo->prepare("SELECT hari FROM tbl_setting_absen_kelas WHERE id_kelas = ?");
$stmt->execute([$kelas_id]);
$class_schedule = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get national holidays for this month
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
    } elseif (!in_array($day_name_id, $class_schedule)) {
        $holiday_legends[] = "Tgl $dx (Tidak ada jadwal)";
    }
}
$holiday_text = !empty($holiday_legends) ? implode(', ', $holiday_legends) : 'Tidak ada';

cetak_header('Rekap Absensi Bulanan - ' . bulan_indo($bulan) . ' ' . $thn, $setting);
?>

<style>
    @page { size: landscape; margin: 10mm; }
    body { max-width: 100% !important; padding: 0 !important; font-size: 9px !important; }
    .print-container { width: 100% !important; max-width: 100% !important; box-shadow: none !important; border: none !important; padding: 0 !important; }
    table { width: 100%; table-layout: fixed; }
    table th, table td { padding: 2px !important; font-size: 7px !important; word-wrap: break-word; }
    td div.cell-info { display: flex; flex-direction: column; align-items: center; justify-content: center; line-height: 1.1; }
    .time-val { font-size: 6px; color: #666; font-family: monospace; }
</style>

<div style="margin-bottom: 10px;">
    <table style="border: none; width: auto;">
        <tr><td style="border: none; padding: 0 10px 0 0;">Kelas</td><td style="border: none; padding: 0 10px;">: <b><?= clean($kelas['nama_kelas']) ?></b></td></tr>
        <tr><td style="border: none; padding: 0 10px 0 0;">Bulan/Tahun</td><td style="border: none; padding: 0 10px;">: <b><?= bulan_indo($bulan) ?> / <?= $thn ?></b></td></tr>
    </table>
</div>

<table>
    <thead>
        <tr>
            <th style="width: 25px;">No</th>
            <th style="width: 130px;">Nama Siswa</th>
            <?php for($d=1; $d<=$days_in_month; $d++): 
                $date_str = "$thn-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
                $day_name_id = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w', strtotime($date_str))];
                $is_holiday = !in_array($day_name_id, $class_schedule) || isset($holidays_array[$date_str]);
            ?>
            <th class="text-center <?= $is_holiday ? 'text-rose-400 border-rose-400' : '' ?>" <?= $is_holiday ? 'style="color:#e11d48;"' : '' ?>><?= $d ?></th>
            <?php endfor; ?>
            <th class="text-center" style="width: 15px;">H</th>
            <th class="text-center" style="width: 15px;">T</th>
            <th class="text-center" style="width: 15px;">S</th>
            <th class="text-center" style="width: 15px;">I</th>
            <th class="text-center" style="width: 15px;">A</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($students as $i => $s): 
            $count_h = 0; $count_t = 0; $count_s = 0; $count_i = 0; $count_a = 0;
        ?>
        <tr>
            <td class="text-center"><?= $i+1 ?></td>
            <td style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= clean($s['nama']) ?></td>
            <?php for($d=1; $d<=$days_in_month; $d++): 
                $date_str = "$thn-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
                $day_name_id = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w', strtotime($date_str))];
                $is_holiday = !in_array($day_name_id, $class_schedule) || isset($holidays_array[$date_str]);
                
                $att = $attendance[$s['id_siswa']][$d] ?? null;
                $symbol = get_absensi_symbol($att['keterangan'] ?? null, $is_holiday ? '2099-01-01' : $date_str);
                
                if($symbol == 'H') $count_h++;
                elseif($symbol == 'T') $count_t++;
                elseif($symbol == 'S') $count_s++;
                elseif($symbol == 'I') $count_i++;
                elseif($symbol == 'A') $count_a++;
                $color = '';
                if($symbol == 'S') $color = 'color: #2196f3;';
                if($symbol == 'I') $color = 'color: #9c27b0;';
                if($symbol == 'A') $color = 'color: #f44336;';
                if($symbol == 'T') $color = 'color: #ff9800;';
            ?>
            <td class="text-center">
                <div class="cell-info">
                    <b style="<?= $color ?>"><?= $symbol ?: '-' ?></b>
                    <?php if ($att): ?>
                        <span class="time-val"><?= $att['jam_masuk'] ? substr($att['jam_masuk'], 0, 5) : '--' ?></span>
                        <span class="time-val"><?= $att['jam_keluar'] ? substr($att['jam_keluar'], 0, 5) : '--' ?></span>
                    <?php endif; ?>
                </div>
            </td>
            <?php endfor; ?>
            <td class="text-center"><b><?= $count_h ?></b></td>
            <td class="text-center"><b><?= $count_t ?></b></td>
            <td class="text-center"><b><?= $count_s ?></b></td>
            <td class="text-center"><b><?= $count_i ?></b></td>
            <td class="text-center"><b><?= $count_a ?></b></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div style="margin-top: 15px; display: flex; justify-content: space-between; font-size: 8px;">
    <div style="width: 70%;">
        <p><b>Keterangan:</b> H: Hadir, T: Terlambat, S: Sakit, I: Izin, A: Alpha. <i>Baris kedua: Jam Masuk, Baris ketiga: Jam Pulang.</i></p>
        <p style="margin-top: 5px; color: #e11d48; line-height: 1.3;"><b>Tanggal Merah:</b> <?= $holiday_text ?></p>
    </div>
    <div style="text-align: center; width: 200px;">
        <p>Petugas Absensi,</p>
        <br><br>
        <p><b>( ____________________ )</b></p>
    </div>
</div>

<?php cetak_footer($setting); ?>
