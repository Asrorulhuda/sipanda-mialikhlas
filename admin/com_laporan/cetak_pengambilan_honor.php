<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../config/cetak_helper.php';
cek_role(['admin','kepsek']);

$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

$stmt = $pdo->prepare("SELECT h.*, g.nama, g.tugas_tambahan 
                       FROM tbl_payroll_history h 
                       JOIN tbl_guru g ON h.id_guru = g.id_guru 
                       WHERE h.bulan = ? AND h.tahun = ?
                       ORDER BY g.nama");
$stmt->execute([$bulan, $tahun]);
$data = $stmt->fetchAll();

$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();

// Prepare attendance data
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
$start_date = "$tahun-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-01";
$end_date = "$tahun-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($days_in_month, 2, '0', STR_PAD_LEFT);

$stmt_att = $pdo->prepare("SELECT id_guru, tanggal, status FROM tbl_absensi_guru WHERE tanggal BETWEEN ? AND ?");
$stmt_att->execute([$start_date, $end_date]);
$att_raw = $stmt_att->fetchAll();
$attendance = [];
foreach ($att_raw as $row) {
    if (!$row['id_guru']) continue;
    $day = (int)date('d', strtotime($row['tanggal']));
    $attendance[$row['id_guru']][$day] = $row['status'];
}

$q_sch = $pdo->query("SELECT hari FROM tbl_setting_absen_guru WHERE jam_masuk IS NOT NULL");
$teacher_work_days = $q_sch->fetchAll(PDO::FETCH_COLUMN);

$stmt_hol = $pdo->prepare("SELECT tanggal FROM tbl_hari_libur WHERE tanggal BETWEEN ? AND ?");
$stmt_hol->execute([$start_date, $end_date]);
$holidays_arr = $stmt_hol->fetchAll(PDO::FETCH_COLUMN);


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Format Pengambilan Honor</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            font-size: 11px; 
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
        }
        .header img {
            width: 60px;
            position: absolute;
            left: 20px;
            top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 8px;
        }
        th {
            background-color: #a9d08e; /* Green matching the image */
            text-align: center;
            vertical-align: middle;
        }
        .no-col { width: 30px; text-align: center; }
        .nama-col { width: 200px; }
        .tugas-col { width: 150px; }
        .honor-col { width: 120px; text-align: right; background-color: #8faadc; /* Blue */ }
        .absen-col { width: 80px; text-align: center; background-color: #ffd966; /* Yellow */ }
        .lalu-col { width: 80px; text-align: center; background-color: #ffd966; /* Yellow */ }
        .ttd-col { width: 150px; }
        
        .honor-val { text-align: right; background-color: #8faadc; }
        .absen-val { text-align: center; background-color: #ffd966; }
        .lalu-val { text-align: center; background-color: #ffd966; }
        
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <img src="<?= BASE_URL ?>gambar/<?= $setting['logo_kiri'] ?? $setting['logo_web'] ?? 'logo.png' ?>" alt="Logo">
        <div>FORMAT PENGAMBILAN HONOR</div>
        <div><?= urlencode($setting['nama_yayasan'] ?? '') ? urldecode($setting['nama_yayasan']) : 'YAYASAN PENDIDIKAN ISLAM' ?></div>
        <div>BULAN <?= strtoupper(bulan_indo($bulan)) ?> <?= $tahun ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="no-col">NO</th>
                <th class="nama-col">NAMA GURU</th>
                <th class="tugas-col">TUGAS</th>
                <th class="honor-col" style="background-color: #a9d08e;">HONOR</th>
                <th class="absen-col" style="background-color: #a9d08e;">TGL TDK<br>HADIR</th>
                <th class="lalu-col" style="background-color: #a9d08e;">BULAN<br>LALU</th>
                <th class="ttd-col">TANDA TANGAN</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($data as $r): 
                // Find absent dates
                $absent_dates = ['S' => [], 'I' => [], 'A' => []];
                $today = date('Y-m-d');
                for ($d = 1; $d <= $days_in_month; $d++) {
                    $date_str = "$tahun-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
                    $day_name_id = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w', strtotime($date_str))];
                    $is_holiday = in_array($date_str, $holidays_arr) || !in_array($day_name_id, $teacher_work_days);
                    
                    $status = $attendance[$r['id_guru']][$d] ?? null;
                    $symbol = '';
                    if ($status) {
                        if ($status == 'Sakit') $symbol = 'S';
                        elseif ($status == 'Izin') $symbol = 'I';
                    } else {
                        if (!$is_holiday && $date_str < $today) $symbol = 'A';
                    }
                    if ($symbol) $absent_dates[$symbol][] = $d;
                }

                $tdk_hadir_str = '';
                if (!empty($absent_dates['S'])) $tdk_hadir_str .= "S : " . implode(', ', $absent_dates['S']) . "<br>";
                if (!empty($absent_dates['I'])) $tdk_hadir_str .= "I : " . implode(', ', $absent_dates['I']) . "<br>";
                if (!empty($absent_dates['A'])) $tdk_hadir_str .= "A : " . implode(', ', $absent_dates['A']) . "<br>";
                if ($tdk_hadir_str == '') $tdk_hadir_str = '0';
                
                // Pengecekan ada selisih atau penerimaan bulan lalu jika perlu
                $penerimaan_lalu = $r['total_diterima'] - $r['selisih_bulan_lalu'];
            ?>
            <tr>
                <td class="no-col"><?= $no++ ?></td>
                <td><?= clean($r['nama']) ?></td>
                <td><?= clean($r['tugas_tambahan'] ?: '-') ?></td>
                <td class="honor-val">Rp <?= number_format($r['total_diterima'], 0, ',', '.') ?></td>
                <td class="absen-val"><?= $tdk_hadir_str ?></td>
                <td class="lalu-val"></td>
                <td>
                    <?php if ($no % 2 == 0): ?>
                        <span style="display:inline-block; margin-top:5px; margin-left:20px;"><?= ($no-1) ?> ....................</span>
                    <?php else: ?>
                        <span style="display:inline-block; margin-top:5px;"><?= ($no-1) ?> ....................</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($data)): ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px;">Belum ada data pengambilan honor (Payroll belum di-generate atau disetujui).</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
