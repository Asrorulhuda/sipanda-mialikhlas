<?php
$page_title = 'Unit Kesehatan Sekolah (UKS)';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin', 'waka_uks']);
cek_fitur('uks');

// Get Stats
$stats = [
    'menunggu' => $pdo->query("SELECT COUNT(*) FROM tbl_uks_kunjungan WHERE status='Menunggu' AND tipe='Kunjungan' AND tanggal=CURDATE()")->fetchColumn(),
    'minta_obat' => $pdo->query("SELECT COUNT(*) FROM tbl_uks_kunjungan WHERE status='Menunggu' AND tipe='Minta Obat'")->fetchColumn(),
    'dirawat' => $pdo->query("SELECT COUNT(*) FROM tbl_uks_kunjungan WHERE status='Dirawat' AND tanggal=CURDATE()")->fetchColumn(),
    'selesai' => $pdo->query("SELECT COUNT(*) FROM tbl_uks_kunjungan WHERE status IN ('Kembali ke Kelas', 'Pulang', 'Disetujui') AND tanggal=CURDATE()")->fetchColumn(),
];

// SQL for Active Visits (Siswa & Guru)
$sql_visits = "
    (SELECT k.*, s.nama as nama_lengkap, 'Siswa' as role_label, NULL as sub_info 
     FROM tbl_uks_kunjungan k 
     JOIN tbl_siswa s ON k.id_user = s.id_siswa 
     WHERE k.role_user = 'siswa' AND k.tipe = 'Kunjungan' AND k.status NOT IN ('Kembali ke Kelas', 'Pulang', 'Rujukan'))
    UNION ALL
    (SELECT k.*, g.nama as nama_lengkap, 'Guru' as role_label, g.tugas_tambahan as sub_info 
     FROM tbl_uks_kunjungan k 
     JOIN tbl_guru g ON k.id_user = g.id_guru 
     WHERE k.role_user = 'guru' AND k.tipe = 'Kunjungan' AND k.status NOT IN ('Kembali ke Kelas', 'Pulang', 'Rujukan'))
    ORDER BY created_at ASC
";
$active_visits = $pdo->query($sql_visits)->fetchAll();

// SQL for Medicine Requests
$sql_requests = "
    (SELECT k.*, s.nama as nama_lengkap, 'Siswa' as role_label 
     FROM tbl_uks_kunjungan k 
     JOIN tbl_siswa s ON k.id_user = s.id_siswa 
     WHERE k.role_user = 'siswa' AND k.tipe = 'Minta Obat' AND k.status = 'Menunggu')
    UNION ALL
    (SELECT k.*, g.nama as nama_lengkap, 'Guru' as role_label 
     FROM tbl_uks_kunjungan k 
     JOIN tbl_guru g ON k.id_user = g.id_guru 
     WHERE k.role_user = 'guru' AND k.tipe = 'Minta Obat' AND k.status = 'Menunggu')
    ORDER BY created_at ASC
";
$medicine_requests = $pdo->query($sql_requests)->fetchAll();

// Get Class List for Filter
$kelas_list = $pdo->query("SELECT id_kelas, nama_kelas FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();

// Get Medicine Stock for Modals
$obats = $pdo->query("SELECT * FROM tbl_uks_obat ORDER BY nama_obat")->fetchAll();

require_once __DIR__ . '/../../template/header.php';
require_once __DIR__ . '/../../template/sidebar.php';
require_once __DIR__ . '/../../template/topbar.php';
?>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <h2 class="text-2xl font-black text-white italic uppercase tracking-tighter flex items-center gap-3">
        <i class="fas fa-briefcase-medical text-emerald-500 animate-pulse"></i> 
        Management Module UKS
    </h2>
    <div class="flex gap-2 text-[10px] font-bold uppercase tracking-widest text-slate-500">
        <span class="px-4 py-2 rounded-xl bg-slate-800/50 border border-white/5"><?= tgl_indo(date('Y-m-d')) ?></span>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="glass p-5 rounded-2xl border-l-4 border-rose-500 shadow-xl relative overflow-hidden group">
        <div class="absolute -right-2 -bottom-2 text-rose-500/10 text-6xl group-hover:scale-110 transition-transform"><i class="fas fa-stethoscope"></i></div>
        <div class="relative z-10">
            <span class="text-[10px] uppercase font-bold text-slate-500 tracking-widest block mb-1">Antrean Sakit</span>
            <h4 class="text-3xl font-black text-white"><?= $stats['menunggu'] ?></h4>
            <div class="w-full bg-slate-800 h-1 mt-3 rounded-full overflow-hidden"><div class="bg-rose-500 h-full w-2/3"></div></div>
        </div>
    </div>
    <div class="glass p-5 rounded-2xl border-l-4 border-blue-500 shadow-xl relative overflow-hidden group">
        <div class="absolute -right-2 -bottom-2 text-blue-500/10 text-6xl group-hover:scale-110 transition-transform"><i class="fas fa-pills"></i></div>
        <div class="relative z-10">
            <span class="text-[10px] uppercase font-bold text-slate-500 tracking-widest block mb-1">Request Obat</span>
            <h4 class="text-3xl font-black text-white"><?= $stats['minta_obat'] ?></h4>
            <p class="text-[10px] text-blue-400 font-bold mt-1">Butuh Persetujuan</p>
        </div>
    </div>
    <div class="glass p-5 rounded-2xl border-l-4 border-emerald-500 shadow-xl">
        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-widest block mb-1">Sedang Dirawat</span>
        <h4 class="text-3xl font-black text-white"><?= $stats['dirawat'] ?></h4>
        <p class="text-[10px] text-emerald-400 font-bold mt-1 inline-flex items-center gap-1"><i class="fas fa-bed"></i> In-Progress</p>
    </div>
    <div class="glass p-5 rounded-2xl border-l-4 border-slate-500 shadow-xl">
        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-widest block mb-1">Total Selesai</span>
        <h4 class="text-3xl font-black text-white"><?= $stats['selesai'] ?></h4>
        <p class="text-[10px] text-slate-500 font-bold mt-1">Hari Ini</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
    <div class="xl:col-span-3 space-y-6">
        <!-- Tabs Command -->
        <div class="flex gap-4 border-b border-white/5 pb-1 mb-2">
            <button onclick="switchTab('tabSakit')" class="tab-btn active px-4 py-2 text-sm font-bold uppercase tracking-widest border-b-2 border-emerald-500 text-emerald-400 transition-all">Antrean Sakit (<?= count($active_visits) ?>)</button>
            <button onclick="switchTab('tabObat')" class="tab-btn px-4 py-2 text-sm font-bold uppercase tracking-widest border-b-2 border-transparent text-slate-500 hover:text-white transition-all">Request Obat (<?= count($medicine_requests) ?>)</button>
            <button onclick="switchTab('tabFisik')" class="tab-btn px-4 py-2 text-sm font-bold uppercase tracking-widest border-b-2 border-transparent text-slate-500 hover:text-white transition-all">Pantau Fisik (BB/TB)</button>
        </div>

        <!-- TAB SAKIT -->
        <div id="tabSakit" class="tab-content">
            <?php if ($active_visits): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($active_visits as $v): ?>
                <div class="glass p-5 rounded-2xl border border-white/5 hover:border-emerald-500/30 transition-all group relative overflow-hidden">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-white/10 flex items-center justify-center text-2xl font-black text-emerald-500 shadow-inner">
                            <?= strtoupper(substr($v['nama_lengkap'], 0, 1)) ?>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-white font-bold text-lg leading-none mb-1"><?= clean($v['nama_lengkap']) ?></h4>
                            <div class="flex gap-2">
                                <span class="text-[10px] uppercase font-bold text-slate-500"><?= $v['role_label'] ?></span>
                                <span class="text-[10px] text-slate-600 font-mono italic"><?= date('H:i', strtotime($v['jam'])) ?> WIB</span>
                            </div>
                        </div>
                        <?php if ($v['status'] == 'Menunggu'): ?>
                            <span class="px-3 py-1 rounded-lg bg-amber-500 text-black text-[10px] font-black uppercase tracking-widest animate-pulse">Waiting</span>
                        <?php else: ?>
                            <span class="px-3 py-1 rounded-lg bg-blue-500 text-white text-[10px] font-black uppercase tracking-widest">In Treatment</span>
                        <?php endif; ?>
                    </div>
                    <div class="bg-black/40 p-4 rounded-xl border border-white/5 mb-4 group-hover:bg-slate-900/40 transition-all">
                        <p class="text-xs text-slate-400 italic leading-relaxed"><i class="fas fa-comment-medical mr-2 text-slate-600"></i><?= clean($v['keluhan']) ?></p>
                    </div>
                    <div class="flex gap-2">
                        <?php if ($v['status'] === 'Menunggu'): ?>
                            <button onclick="updateStatus(<?= $v['id_kunjungan'] ?>, 'Dirawat')" class="flex-1 bg-emerald-600 hover:bg-emerald-500 py-2.5 rounded-xl text-xs font-black text-white uppercase italic tracking-widest shadow-lg shadow-emerald-600/20">Proses</button>
                        <?php else: ?>
                            <button onclick="finishTreatment(<?= $v['id_kunjungan'] ?>)" class="flex-1 bg-blue-600 hover:bg-blue-500 py-2.5 rounded-xl text-xs font-black text-white uppercase italic tracking-widest shadow-lg shadow-blue-600/20">Selesai</button>
                        <?php endif; ?>
                        <button onclick="deleteTreatment(<?= $v['id_kunjungan'] ?>)" class="w-10 bg-rose-500/10 text-rose-500 hover:bg-rose-600 hover:text-white rounded-xl transition-all border border-rose-500/20"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <div class="glass py-20 text-center rounded-3xl border border-dashed border-white/10">
                    <i class="fas fa-smile-beam text-5xl text-slate-700 mb-4 block"></i>
                    <h4 class="text-slate-400 font-bold mb-1">Semua Pasien Sudah Selesai!</h4>
                    <p class="text-xs text-slate-600">Tidak ada antrean laporan sakit masuk.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB REQUEST OBAT -->
        <div id="tabObat" class="tab-content hidden">
            <?php if ($medicine_requests): ?>
            <div class="space-y-4">
                <?php foreach ($medicine_requests as $mq): ?>
                <div class="glass p-5 rounded-2xl border border-white/5 flex flex-col md:flex-row items-center gap-6">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-500 border border-blue-500/20 flex items-center justify-center font-black"><?= strtoupper(substr($mq['nama_lengkap'], 0, 1)) ?></span>
                            <div>
                                <h4 class="text-white font-bold leading-none"><?= clean($mq['nama_lengkap']) ?></h4>
                                <span class="text-[10px] text-slate-500 uppercase font-black"><?= $mq['role_label'] ?> • Request Obat</span>
                            </div>
                        </div>
                        <p class="text-xs text-slate-400 bg-slate-900/50 p-3 rounded-lg italic border border-white/5"><?= clean($mq['keluhan']) ?></p>
                    </div>
                    <div class="flex gap-2 w-full md:w-auto">
                        <button onclick="approveObat(<?= $mq['id_kunjungan'] ?>)" class="flex-1 md:w-40 bg-blue-600 hover:bg-blue-500 py-3 rounded-xl text-xs font-black text-white uppercase italic tracking-widest shadow-lg shadow-blue-600/20">Setujui</button>
                        <button onclick="execAction({action:'update_status', id_kunjungan:<?= $mq['id_kunjungan'] ?>, status:'Ditolak'})" class="w-12 bg-rose-500/10 text-rose-500 hover:bg-rose-600 hover:text-white rounded-xl border border-rose-500/20 transition-all flex items-center justify-center"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <div class="glass py-20 text-center rounded-3xl border border-dashed border-white/10">
                    <i class="fas fa-pills text-5xl text-slate-700 mb-4 block"></i>
                    <h4 class="text-slate-400 font-bold mb-1">Tidak Ada Permintaan Obat</h4>
                    <p class="text-xs text-slate-600">Daftar permintaan dari rumah muncul di sini.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB FISIK -->
        <div id="tabFisik" class="tab-content hidden">
            <div class="glass rounded-2xl overflow-hidden border border-white/5">
                <div class="p-5 border-b border-white/5 bg-white/3 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <h3 class="font-bold text-white text-xs uppercase tracking-widest italic">Data Perkembangan Fisik Siswa</h3>
                    <div class="flex flex-wrap gap-2">
                        <select id="filterKelas" onchange="filterByKelas()" class="bg-slate-900 border border-white/10 rounded-lg px-3 py-1.5 text-[10px] uppercase font-bold tracking-widest focus:outline-none focus:border-emerald-500 text-slate-400">
                            <option value="">- Semua Kelas -</option>
                            <?php foreach ($kelas_list as $kl) {
                                $sel = ($_GET['id_kelas'] ?? '') == $kl['id_kelas'] ? 'selected' : '';
                                echo "<option value='{$kl['id_kelas']}' $sel>{$kl['nama_kelas']}</option>";
                            } ?>
                        </select>
                        <input type="text" id="searchFisik" onkeyup="searchTable()" placeholder="Cari Nama..." class="bg-slate-900 border border-white/10 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:border-emerald-500 text-white min-w-[200px]">
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="tableFisik">
                        <thead>
                            <tr class="text-left text-slate-500 bg-black/20 border-b border-white/10">
                                <th class="p-4 font-bold uppercase text-[9px] tracking-widest">Nama Lengkap</th>
                                <th class="p-4 font-bold uppercase text-[9px] tracking-widest">Kelas</th>
                                <th class="p-4 font-bold uppercase text-[9px] tracking-widest">BB (Kg)</th>
                                <th class="p-4 font-bold uppercase text-[9px] tracking-widest">TB (Cm)</th>
                                <th class="p-4 font-bold uppercase text-[9px] tracking-widest">BMI / Gizi</th>
                                <th class="p-4 font-bold uppercase text-[9px] tracking-widest">Update</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php 
                            $id_kls = (int)($_GET['id_kelas'] ?? 0);
                            $where_kls = $id_kls ? "WHERE s.id_kelas=$id_kls" : "";
                            $fisik_sql = "SELECT s.id_siswa, s.nama, kl.nama_kelas, f.berat, f.tinggi, f.bmi, f.status_gizi, f.tanggal 
                                          FROM tbl_siswa s 
                                          JOIN tbl_kelas kl ON s.id_kelas = kl.id_kelas
                                          LEFT JOIN (SELECT * FROM tbl_uks_fisik WHERE id_fisik IN (SELECT MAX(id_fisik) FROM tbl_uks_fisik GROUP BY id_user, role_user)) f ON s.id_siswa = f.id_user 
                                          $where_kls
                                          ORDER BY s.nama ASC";
                            $data_fisik = $pdo->query($fisik_sql)->fetchAll();
                            foreach ($data_fisik as $df):
                            ?>
                            <tr class="hover:bg-white/5 transition-all">
                                <td class="p-4">
                                    <span class="text-white font-bold block"><?= clean($df['nama']) ?></span>
                                    <span class="text-[9px] text-slate-500 font-mono italic"><?= $df['tanggal'] ? tgl_indo($df['tanggal']) : 'Belum diukur' ?></span>
                                </td>
                                <td class="p-4"><span class="px-2 py-1 rounded bg-slate-800 text-slate-400 text-[10px] font-bold"><?= $df['nama_kelas'] ?></span></td>
                                <td class="p-4 font-black text-blue-400"><?= $df['berat'] ?? '-' ?></td>
                                <td class="p-4 font-black text-purple-400"><?= $df['tinggi'] ?? '-' ?></td>
                                <td class="p-4">
                                    <span class="font-black text-emerald-400"><?= number_format($df['bmi'] ?? 0, 1) ?></span>
                                    <span class="block text-[10px] text-slate-500 uppercase"><?= $df['status_gizi'] ?? '-' ?></span>
                                </td>
                                <td class="p-4">
                                    <button onclick="showInputFisik(<?= $df['id_siswa'] ?>, '<?= clean($df['nama']) ?>')" class="p-2 rounded-lg bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500 hover:text-white transition-all"><i class="fas fa-edit"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="space-y-6">
        <div class="glass p-6 rounded-3xl border border-white/5 shadow-2xl relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-all"></div>
            <h3 class="text-white font-black italic uppercase tracking-widest text-sm mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <button onclick="showModalManual()" class="w-full bg-emerald-600 hover:bg-emerald-500 py-3 rounded-xl text-xs font-black text-white uppercase italic tracking-widest shadow-lg shadow-emerald-500/20 transition-all mb-1 border border-emerald-400/20">Kunjungan Manual</button>
                <a href="obat.php" class="flex items-center justify-between p-4 rounded-2xl bg-white/5 hover:bg-slate-800 transition-all group">
                    <span class="text-xs font-bold text-slate-300 uppercase tracking-widest">Inventori Obat</span>
                    <i class="fas fa-arrow-right text-emerald-500 group-hover:translate-x-1 transition-transform"></i>
                </a>
                <button onclick="location.reload()" class="w-full flex items-center justify-between p-4 rounded-2xl bg-white/5 hover:bg-blue-600/20 transition-all group border border-transparent hover:border-blue-500/30">
                    <span class="text-xs font-bold text-slate-300 uppercase tracking-widest">Refresh Antrean</span>
                    <i class="fas fa-sync text-blue-500 group-hover:rotate-180 transition-transform duration-700"></i>
                </button>
            </div>
        </div>

        <!-- Notification Feed -->
        <div class="glass p-6 rounded-3xl border border-white/5">
            <h3 class="text-white font-black italic uppercase tracking-widest text-sm mb-4 border-b border-white/10 pb-2">Feed Aktivitas</h3>
            <div class="space-y-4">
                <?php 
                $logs = $pdo->query("SELECT k.*, s.nama as nama_log FROM tbl_uks_kunjungan k JOIN tbl_siswa s ON k.id_user = s.id_siswa WHERE k.status != 'Menunggu' ORDER BY k.created_at DESC LIMIT 4")->fetchAll();
                foreach ($logs as $l):
                ?>
                <div class="flex gap-3 items-start border-b border-white/5 pb-3">
                    <div class="w-8 h-8 rounded-full bg-slate-800 border border-white/5 flex items-center justify-center text-[10px] text-slate-500"><i class="fas fa-user"></i></div>
                    <div class="flex-1">
                        <p class="text-[11px] text-white font-bold leading-tight"><?= clean($l['nama_log']) ?></p>
                        <p class="text-[9px] text-slate-500 italic mt-0.5"><?= $l['status'] ?> · <?= date('H:i', strtotime($l['jam'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Finish Treatment -->
<div id="modalFinish" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[999] hidden flex items-center justify-center p-4">
    <div class="glass w-full max-w-md rounded-2xl border border-white/10 shadow-2xl animate-zoom-in">
        <div class="p-6 border-b border-white/5 flex items-center justify-between">
            <h3 class="text-lg font-bold flex items-center gap-2 text-white italic uppercase"><i class="fas fa-check-circle text-emerald-500"></i>Selesaikan Penanganan</h3>
            <button onclick="closeModal('modalFinish')" class="text-slate-400 hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form id="formFinish" class="p-6 space-y-4">
            <input type="hidden" name="action" value="finish_treatment">
            <input type="hidden" name="id_kunjungan" id="finish_id">
            <div>
                <label class="block text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-2">Diagnosis / Keterangan UKS</label>
                <textarea name="diagnosa" required placeholder="Hasil pemeriksaan singkat..." class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none min-h-[80px] text-white"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-2">Pilih Obat</label>
                    <select name="id_obat" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none text-white">
                        <option value="">- Tanpa Obat -</option>
                        <?php foreach ($obats as $o) echo "<option value='{$o['id_obat']}'>{$o['nama_obat']} ({$o['stok']})</option>"; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-2">Jumlah</label>
                    <input type="number" name="jumlah_obat" value="1" min="1" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none text-white">
                </div>
            </div>
            <div>
                <label class="block text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-2">Status Akhir</label>
                <div class="flex gap-2">
                    <label class="flex-1">
                        <input type="radio" name="status" value="Kembali ke Kelas" checked class="hidden peer">
                        <div class="p-3 text-center rounded-xl bg-slate-900 border border-white/10 text-xs text-slate-400 peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:border-emerald-500 transition-all cursor-pointer font-bold uppercase italic">Kembali</div>
                    </label>
                    <label class="flex-1">
                        <input type="radio" name="status" value="Pulang" class="hidden peer">
                        <div class="p-3 text-center rounded-xl bg-slate-900 border border-white/10 text-xs text-slate-400 peer-checked:bg-amber-600 peer-checked:text-white peer-checked:border-amber-500 transition-all cursor-pointer font-bold uppercase italic">Pulang</div>
                    </label>
                    <label class="flex-1">
                        <input type="radio" name="status" value="Rujukan" class="hidden peer">
                        <div class="p-3 text-center rounded-xl bg-slate-900 border border-white/10 text-xs text-slate-400 peer-checked:bg-rose-600 peer-checked:text-white peer-checked:border-rose-500 transition-all cursor-pointer font-bold uppercase italic">Rujukan</div>
                    </label>
                </div>
            </div>
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 py-3 rounded-xl text-sm font-black text-white uppercase italic tracking-widest shadow-lg shadow-emerald-500/20 transition-all mt-4">Selesaikan & Simpan</button>
        </form>
    </div>
</div>

<!-- Modal Approval Obat -->
<div id="modalApproveObat" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[999] hidden flex items-center justify-center p-4">
    <div class="glass w-full max-w-md rounded-2xl border border-white/10 shadow-2xl animate-zoom-in">
        <div class="p-6 border-b border-white/5 flex items-center justify-between">
            <h3 class="text-lg font-black italic uppercase text-white"><i class="fas fa-pills text-blue-500 mr-2"></i> Konfirmasi Pemberian Obat</h3>
            <button onclick="closeModal('modalApproveObat')" class="text-slate-400 hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form id="formApproveObat" class="p-6 space-y-4">
            <input type="hidden" name="action" value="finish_treatment">
            <input type="hidden" name="id_kunjungan" id="approve_id">
            <input type="hidden" name="status" value="Disetujui">
            <p class="text-xs text-slate-400 mb-2 p-3 bg-blue-500/5 rounded-xl border border-blue-500/10 italic">Persetujuan ini akan secara otomatis mengurangi stok obat dan memberikan notifikasi kepada siswa/guru untuk pengambilan.</p>
            <div>
                <label class="block text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-2">Instruksi Pemakaian (Opsional)</label>
                <textarea name="diagnosa" placeholder="Cth: Diminum setelah makan 3x1..." class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-blue-500 focus:outline-none min-h-[80px] text-white"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-2">Pilih Stok Obat</label>
                    <select name="id_obat" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none text-white">
                        <option value="">- Pilih Obat -</option>
                        <?php foreach ($obats as $o) echo "<option value='{$o['id_obat']}'>{$o['nama_obat']} ({$o['stok']})</option>"; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-2">Jumlah</label>
                    <input type="number" name="jumlah_obat" value="1" min="1" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none text-white">
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 py-4 rounded-xl text-sm font-black text-white uppercase italic tracking-widest shadow-lg shadow-blue-600/30 transition-all mt-4">Kirim Konfirmasi & Kurangi Stok</button>
        </form>
    </div>
</div>

<!-- Modal Input Fisik Manual -->
<div id="modalFisikManual" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[999] hidden flex items-center justify-center p-4">
    <div class="glass w-full max-w-sm rounded-2xl border border-white/10 shadow-2xl animate-zoom-in">
        <div class="p-6 border-b border-white/5 flex items-center justify-between">
            <h3 class="text-sm font-black italic uppercase text-white"><i class="fas fa-weight text-emerald-500 mr-2"></i> Input Data Fisik</h3>
            <button onclick="closeModal('modalFisikManual')" class="text-slate-400 hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form id="formFisikManual" class="p-6 space-y-4">
            <input type="hidden" name="action" value="simpan_fisik">
            <input type="hidden" name="id_siswa_target" id="fisik_id_target">
            <h4 id="fisik_nama_target" class="text-white font-bold text-center text-lg mb-4"></h4>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-2">Berat (Kg)</label>
                    <input type="number" step="0.1" name="berat" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none text-white font-black">
                </div>
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-2">Tinggi (Cm)</label>
                    <input type="number" step="0.1" name="tinggi" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none text-white font-black">
                </div>
            </div>
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 py-3 rounded-xl text-sm font-black text-white uppercase italic tracking-widest shadow-lg shadow-emerald-500/20 transition-all mt-4">Simpan Hasil Penimbangan</button>
        </form>
    </div>
</div>

<!-- CSS Inline for Tabs -->
<style>
.tab-btn.active { border-color: #10b981; color: #10b981; }
</style>

<script>
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.add('hidden'));
    document.getElementById(tabId).classList.remove('hidden');
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('active', 'border-emerald-500', 'text-emerald-400');
        b.classList.add('border-transparent', 'text-slate-500');
    });
    const target = event.currentTarget;
    target.classList.add('active', 'border-emerald-500', 'text-emerald-400');
    target.classList.remove('border-transparent', 'text-slate-500');
}

function approveObat(id) {
    document.getElementById('approve_id').value = id;
    document.getElementById('modalApproveObat').classList.remove('hidden');
}

function showInputFisik(id, nama) {
    document.getElementById('fisik_id_target').value = id;
    document.getElementById('fisik_nama_target').innerText = nama;
    document.getElementById('modalFisikManual').classList.remove('hidden');
}

function showModalManual() {
    Swal.fire({
        title: 'KUNJUNGAN MANUAL',
        text: 'Layanan kunjungan manual diatur dari tab Antrean Sakit atau melalui integrasi RFID.',
        icon: 'info',
        confirmButtonText: 'KEREN'
    });
}

// Notification Alarm
<?php if ($stats['menunggu'] > 0 || $stats['minta_obat'] > 0): ?>
    window.onload = function() {
        Swal.fire({
            title: 'Notifikasi!',
            text: 'Ada <?= $stats['menunggu'] + $stats['minta_obat'] ?> antrean/permintaan baru!',
            icon: 'info',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true
        });
    }
<?php endif; ?>

function updateStatus(id, newStatus) { execAction({ action: 'update_status', id_kunjungan: id, status: newStatus }); }
function deleteTreatment(id) { 
    Swal.fire({ title: 'Hapus?', text: 'Data tidak bisa dipulihkan!', icon: 'warning', showCancelButton: true, confirmButtonText: 'Hapus' })
    .then(r => { if(r.isConfirmed) execAction({ action: 'delete_treatment', id_kunjungan: id }); });
}

function finishTreatment(id) {
    document.getElementById('finish_id').value = id;
    document.getElementById('modalFinish').classList.remove('hidden');
}

function execAction(formData) {
    fetch('handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(formData)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('Gagal!', data.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Gagal!', 'Terjadi kesalahan sistem.', 'error');
    });
}

document.getElementById('formFinish').onsubmit = function(e) {
    e.preventDefault();
    execAction(Object.fromEntries(new FormData(this)));
}
document.getElementById('formApproveObat').onsubmit = function(e) {
    e.preventDefault();
    execAction(Object.fromEntries(new FormData(this)));
}
document.getElementById('formFisikManual').onsubmit = function(e) {
    e.preventDefault();
    execAction(Object.fromEntries(new FormData(this)));
}

function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

function filterByKelas() {
    const id = document.getElementById('filterKelas').value;
    window.location.href = '?id_kelas=' + id + '#tabFisik';
}

function searchTable() {
    const input = document.getElementById('searchFisik').value.toUpperCase();
    const table = document.getElementById('tableFisik');
    const tr = table.getElementsByTagName('tr');
    for (let i = 1; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName('td')[0];
        if (td) {
            const textValue = td.textContent || td.innerText;
            tr[i].style.display = textValue.toUpperCase().indexOf(input) > -1 ? "" : "none";
        }
    }
}

// Keep tab active on reload
if (window.location.hash === '#tabFisik') switchTab('tabFisik');
</script>

<?php require_once __DIR__ . '/../../template/footer.php'; ?>
