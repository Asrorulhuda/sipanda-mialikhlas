<?php
$page_title = 'Absensi Guru';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin','kepsek']);
cek_fitur('absensi');

$tgl = $_GET['tgl'] ?? date('Y-m-d');
$stmt = $pdo->prepare("SELECT a.*, g.nama, g.nip FROM tbl_absensi_guru a JOIN tbl_guru g ON a.id_guru=g.id_guru WHERE a.tanggal=? ORDER BY a.jam_masuk");
$stmt->execute([$tgl]);
$data = $stmt->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="glass rounded-xl p-5 mb-6">
    <div class="flex flex-wrap gap-3 items-end justify-between">
        <form method="GET" class="flex gap-3 items-end">
            <div>
                <label class="block text-xs text-slate-400 mb-1">Tanggal</label>
                <input type="date" name="tgl" value="<?= $tgl ?>" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm text-white" onchange="this.form.submit()">
            </div>
        </form>
    </div>
</div>

<div class="glass rounded-xl p-5 border border-white/5 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-64 h-64 bg-purple-500/5 rounded-full blur-3xl"></div>
    <div class="flex items-center justify-between mb-4 relative z-10">
        <h3 class="text-sm font-bold text-white"><i class="fas fa-chalkboard-teacher mr-2 text-purple-400"></i>Data Kehadiran Guru - <?= tgl_indo($tgl) ?></h3>
        <span class="text-xs px-2 py-1 rounded bg-purple-600/20 text-purple-400 font-bold"><?= count($data) ?> Guru</span>
    </div>

    <div class="table-container relative z-10">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="text-slate-400 border-b border-white/10 italic font-medium">
                    <th class="pb-3 px-2">#</th>
                    <th class="pb-3">Guru</th>
                    <th class="pb-3 text-center">Masuk/Keluar</th>
                    <th class="pb-3 text-center">Metode</th>
                    <th class="pb-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($data as $i => $r): ?>
                <tr class="hover:bg-white/5 transition-all group">
                    <td class="py-3 px-2 text-slate-500"><?= $i+1 ?></td>
                    <td class="font-bold text-white">
                        <?= clean($r['nama']) ?>
                        <p class="text-[9px] text-slate-500 font-normal"><?= clean($r['nip']) ?></p>
                    </td>
                    <td class="text-center">
                        <div class="flex flex-col">
                            <span class="font-mono text-[10px] text-emerald-400"><?= $r['jam_masuk']?substr($r['jam_masuk'],0,5):'--:--' ?></span>
                            <span class="font-mono text-[10px] text-amber-400"><?= $r['jam_keluar']?substr($r['jam_keluar'],0,5):'--:--' ?></span>
                        </div>
                    </td>
                    <td class="text-center">
                        <?php 
                        $metode = $r['metode'] ?: 'RFID';
                        $m_color = ($metode == 'RFID') ? 'bg-purple-500/20 text-purple-400' : 'bg-slate-500/20 text-slate-400';
                        ?>
                        <span class="text-[9px] px-1.5 py-0.5 rounded font-bold <?= $m_color ?>"><?= $metode ?></span>
                    </td>
                    <td>
                        <?php 
                        $status = $r['status'];
                        $s_label = 'Masuk';
                        $s_class = 'text-amber-400';
                        if ($status == 'COMPLETE') { $s_label = 'Selesai'; $s_class = 'text-emerald-400'; }
                        elseif ($status == 'Izin') { $s_label = 'Izin'; $s_class = 'text-sky-400'; }
                        elseif ($status == 'Sakit') { $s_label = 'Sakit'; $s_class = 'text-rose-400'; }
                        ?>
                        <span class="text-xs font-bold <?= $s_class ?>"><?= $s_label ?></span>
                        <p class="text-[10px] text-slate-500 italic"><?= clean($r['keterangan']) ?></p>
                    </td>
                </tr>
                <?php endforeach; if (!$data): ?>
                <tr><td colspan="5" class="py-6 text-center text-slate-500 italic">Belum ada data kehadiran guru hari ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
