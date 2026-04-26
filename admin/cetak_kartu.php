<?php
require_once __DIR__ . '/../config/init.php';
cek_role(['admin', 'kepsek']);

$type = $_GET['type'] ?? 'card'; // 'card' or 'qr'
$ids_str = $_GET['ids'] ?? '';
$id_single = $_GET['id'] ?? '';
$kelas = $_GET['kelas'] ?? '';

$where = "WHERE 1=0";
$params = [];

if ($id_single) {
    $where = "WHERE s.id_siswa = ?";
    $params = [$id_single];
} elseif ($ids_str) {
    $ids = explode(',', $ids_str);
    $where = "WHERE s.id_siswa IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
    $params = $ids;
} elseif ($kelas) {
    $where = "WHERE s.id_kelas = ?";
    $params = [$kelas];
}

$query = "SELECT s.*, k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas $where ORDER BY s.nama";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$data = $stmt->fetchAll();

if (!$data) die("Tidak ada data siswa yang dipilih!");

// Auto-generate rfid_uid if empty
$pdo->beginTransaction();
foreach ($data as &$r) {
    if (empty($r['rfid_uid'])) {
        $new_uid = 'QR-' . strtoupper(bin2hex(random_bytes(4)));
        $pdo->prepare("UPDATE tbl_siswa SET rfid_uid=? WHERE id_siswa=?")->execute([$new_uid, $r['id_siswa']]);
        $r['rfid_uid'] = $new_uid;
    }
}
$pdo->commit();

$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak <?= $type=='card'?'Kartu Pelajar':'QR Code' ?> SIPANDA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 10mm; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; padding: 20px; }
        
        /* Card Mode Styles */
        .card-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10mm; }
        .id-card { 
            width: 85.6mm; height: 54mm; background: #0f172a; color: white; 
            border-radius: 4mm; position: relative; overflow: hidden; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.1);
        }
        .card-bg-accent {
            position: absolute; top: -20%; right: -10%; width: 60%; height: 140%;
            background: linear-gradient(135deg, rgba(234,179,8,0.1) 0%, rgba(30,58,138,0.5) 100%);
            transform: rotate(15deg); z-index: 0;
        }
        .card-content { position: relative; z-index: 1; padding: 3mm 4mm; display: flex; flex-direction: column; height: 100%; }
        
        /* QR Mode Styles */
        .qr-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5mm; }
        .qr-sticker { 
            border: 1px dashed #cbd5e1; padding: 3mm; text-align: center; background: white;
            border-radius: 2mm; display: flex; flex-direction: column; align-items: center;
        }

        @media print {
            body { background: none; padding: 0; }
            .no-print { display: none !important; }
            .card-grid, .qr-grid { gap: 5mm; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="fixed top-4 right-4 no-print flex gap-2">
    <button onclick="window.print()" class="bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg hover:bg-blue-500 transition-all">Cetak Sekarang</button>
    <button onclick="window.close()" class="bg-slate-600 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg hover:bg-slate-500 transition-all">Tutup</button>
</div>

<?php if($type == 'card'): ?>
    <div class="card-grid">
        <?php foreach($data as $s): ?>
        <div class="id-card">
            <div class="card-bg-accent"></div>
            <div class="card-content">
                <!-- Header -->
                <div class="flex items-center gap-2 mb-2 border-b border-white/10 pb-1.5 px-1 font-serif">
                    <div class="w-8 h-8 flex-shrink-0">
                        <?php if(!empty($setting['logo_kiri'])): ?>
                            <img src="../gambar/<?= $setting['logo_kiri'] ?>" class="w-full h-full object-contain">
                        <?php else: ?>
                            <div class="w-7 h-7 bg-white rounded-lg flex items-center justify-center font-black text-navy-950 text-base">S</div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <?php if(!empty($setting['instansi_atas'])): ?>
                            <p class="text-[5px] font-bold uppercase tracking-tighter text-slate-400 mb-0.5 leading-none"><?= clean($setting['instansi_atas']) ?></p>
                        <?php endif; ?>
                        <h1 class="text-[9px] font-extrabold uppercase tracking-widest text-amber-400 leading-none mb-0.5 truncate"><?= clean($setting['nama_sekolah']) ?></h1>
                        <p class="text-[6px] text-slate-500 italic leading-none truncate"><?= clean($setting['alamat']) ?></p>
                    </div>
                </div>

                <!-- Body -->
                <div class="flex gap-3 flex-1 mt-1">
                    <!-- Photo -->
                    <div class="w-16 h-20 bg-slate-800 rounded-lg border border-white/10 overflow-hidden shadow-inner flex-shrink-0">
                        <img src="<?= BASE_URL ?>assets/uploads/foto_siswa/<?= $s['foto'] ?: 'default.png' ?>" class="w-full h-full object-cover">
                    </div>
                    
                    <!-- Info -->
                    <div class="flex-1 space-y-1.5">
                        <div>
                            <p class="text-[6px] text-slate-500 uppercase font-bold tracking-tighter">Nama Lengkap</p>
                            <p class="text-[10px] font-extrabold text-white truncate"><?= clean($s['nama']) ?></p>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <p class="text-[6px] text-slate-500 uppercase font-bold tracking-tighter">NISN / NIS</p>
                                <p class="text-[8px] font-bold text-blue-400"><?= clean($s['nisn']) ?> / <?= clean($s['nis']) ?></p>
                            </div>
                            <div>
                                <p class="text-[6px] text-slate-500 uppercase font-bold tracking-tighter">Kelas</p>
                                <p class="text-[8px] font-bold text-white"><?= clean($s['nama_kelas']) ?></p>
                            </div>
                        </div>
                        <div class="pt-1.5 flex items-end justify-between">
                            <div class="text-[5px] text-slate-500 leading-tight">
                                <p>Kartu ini adalah identitas resmi</p>
                                <p>siswa <?= clean($setting['nama_sekolah']) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- QR Code -->
                    <div class="w-14 h-14 bg-white p-1 rounded-lg flex-shrink-0 self-end mb-1">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($s['rfid_uid']) ?>" class="w-full h-full">
                    </div>
                </div>
                
                <!-- Footer Bar -->
                <div class="mt-auto pt-1 border-t border-white/5 flex justify-between items-center">
                    <p class="text-[5px] font-mono text-slate-500 uppercase tracking-widest">ID: <?= clean($s['rfid_uid']) ?></p>
                    <p class="text-[5px] font-bold text-gold-500 uppercase tracking-widest">Digital Presence Verified</p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

<?php else: ?>
    <!-- QR ONLY MODE -->
    <div class="qr-grid">
        <?php foreach($data as $s): ?>
        <div class="qr-sticker">
            <h4 class="text-[10px] font-extrabold truncate w-full mb-1"><?= clean($s['nama']) ?></h4>
            <p class="text-[8px] text-slate-500 mb-2"><?= clean($s['nama_kelas']) ?></p>
            <div class="w-24 h-24 border p-1 rounded-sm bg-white">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($s['rfid_uid']) ?>" class="w-full h-full">
            </div>
            <p class="text-[7px] font-mono text-slate-400 mt-2 uppercase"><?= clean($s['rfid_uid']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</body>
</html>
