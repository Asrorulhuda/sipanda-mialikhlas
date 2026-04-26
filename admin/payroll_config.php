<?php
$page_title = 'Konfigurasi Gaji Guru';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);
cek_fitur('payroll');

// Handle Update
if (isset($_POST['update_config'])) {
    $id_guru = $_POST['id_guru'];
    
    // Update Join Date in tbl_guru
    $pdo->prepare("UPDATE tbl_guru SET tmt=? WHERE id_guru=?")->execute([$_POST['tmt'], $id_guru]);
    
    // Update Payroll Config
    $stmt = $pdo->prepare("INSERT INTO tbl_payroll_config (id_guru, jtm_jumlah, score_jabatan, score_wali_kelas, score_bid_pddk, score_manual, tunjangan_tetap, rate_jtm_guru, rate_kehadiran_guru) 
                          VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE 
                          jtm_jumlah=?, score_jabatan=?, score_wali_kelas=?, score_bid_pddk=?, score_manual=?, tunjangan_tetap=?, rate_jtm_guru=?, rate_kehadiran_guru=?");
    $stmt->execute([
        $id_guru, $_POST['jtm_jumlah'], $_POST['score_jabatan'], $_POST['score_wali_kelas'], $_POST['score_bid_pddk'], $_POST['score_manual'], $_POST['tunjangan_tetap'], $_POST['rate_jtm_guru'], $_POST['rate_kehadiran_guru'],
        $_POST['jtm_jumlah'], $_POST['score_jabatan'], $_POST['score_wali_kelas'], $_POST['score_bid_pddk'], $_POST['score_manual'], $_POST['tunjangan_tetap'], $_POST['rate_jtm_guru'], $_POST['rate_kehadiran_guru']
    ]);
    
    flash('msg', 'Konfigurasi gaji guru berhasil diperbarui!');
    header('Location: payroll_config.php'); exit;
}

$query = "SELECT g.id_guru, g.nama, g.nip, g.tmt, g.tugas_tambahan, c.jtm_jumlah, c.score_jabatan, c.score_wali_kelas, c.score_bid_pddk, c.score_manual, c.tunjangan_tetap, c.rate_jtm_guru, c.rate_kehadiran_guru 
          FROM tbl_guru g LEFT JOIN tbl_payroll_config c ON g.id_guru = c.id_guru 
          WHERE g.status='Aktif' ORDER BY g.nama";
$data = $pdo->query($query)->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex flex-col md:flex-row items-center justify-between mb-8 gap-4">
    <div>
        <h2 class="text-2xl font-black italic tracking-tighter uppercase text-white"><i class="fas fa-user-cog mr-2 text-blue-500"></i>Konfigurasi Gaji Guru</h2>
        <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Atur target JTM, rate khusus, dan skor jabatan guru</p>
    </div>
</div>

<?= alert_flash('msg') ?>

<div class="glass rounded-3xl p-6 border border-white/5 relative overflow-hidden">
    <div class="table-container">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="text-slate-500 border-b border-white/10 italic text-[10px] uppercase tracking-widest">
                    <th class="py-4 px-2">Guru</th>
                    <th class="py-4">Info Tugas & TMT</th>
                    <th class="py-4 text-center">Rate Khusus</th>
                    <th class="py-4 text-center">Skor Detail</th>
                    <th class="py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($data as $r): ?>
                <tr class="hover:bg-white/5 transition-all group">
                    <td class="py-4 px-2">
                        <div class="font-bold text-white"><?= clean($r['nama']) ?></div>
                        <div class="text-[9px] text-slate-500 uppercase tracking-widest font-mono"><?= clean($r['nip'] ?: 'N/A') ?></div>
                    </td>
                    <td class="py-4">
                        <div class="text-[10px] text-emerald-400 font-bold italic mb-1">TMT: <?= $r['tmt'] ? tgl_indo($r['tmt']) : '<span class="text-rose-400">Belum diatur</span>' ?></div>
                        <div class="text-[9px] text-slate-400 line-clamp-1 italic"><?= $r['tugas_tambahan'] ?: '<span class="opacity-30">Tugas tam. kosong</span>' ?></div>
                    </td>
                    <td class="py-4 text-center">
                        <?php if ($r['rate_jtm_guru'] > 0 || $r['rate_kehadiran_guru'] > 0): ?>
                        <div class="flex flex-col gap-1 items-center">
                            <span class="text-[9px] bg-amber-500/20 text-amber-500 px-2 py-0.5 rounded-lg border border-amber-500/10">JTM: <?= number_format($r['rate_jtm_guru'],0,',','.') ?></span>
                            <span class="text-[9px] bg-blue-500/20 text-blue-500 px-2 py-0.5 rounded-lg border border-blue-500/10">Hadir: <?= number_format($r['rate_kehadiran_guru'],0,',','.') ?></span>
                        </div>
                        <?php else: ?>
                        <span class="text-[9px] text-slate-600 italic">Default Global</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-4 text-center">
                        <div class="flex flex-col gap-1 items-center">
                            <div class="flex gap-1">
                                <span class="bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded text-[8px]" title="Jabatan"><?= $r['score_jabatan'] ?: 0 ?></span>
                                <span class="bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded text-[8px]" title="Wali Kelas"><?= $r['score_wali_kelas'] ?: 0 ?></span>
                                <span class="bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded text-[8px]" title="Bid.Pddk"><?= $r['score_bid_pddk'] ?: 0 ?></span>
                            </div>
                            <span class="text-[9px] font-black text-white">Score Manual: <?= $r['score_manual'] ?: 0 ?></span>
                        </div>
                    </td>
                    <td class="py-4 text-right">
                        <button onclick="openModalEdit(<?= htmlspecialchars(json_encode($r)) ?>)" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-1.5 rounded-xl text-[10px] font-bold transition-all shadow-lg shadow-blue-600/20">Edit Config</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit -->
<div id="modal-edit" class="fixed inset-0 z-[100] hidden items-center justify-center px-4 bg-slate-950/80 backdrop-blur-sm">
    <div class="glass w-full max-w-2xl rounded-3xl p-8 border border-white/10 relative">
        <h3 class="text-lg font-bold text-white mb-6 uppercase tracking-widest italic" id="modal-title">Edit Konfigurasi Gaji</h3>
        <form method="POST">
            <input type="hidden" name="id_guru" id="edit-id">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Group 1: Dasar -->
                <div class="space-y-4">
                    <h4 class="text-[10px] font-bold text-blue-400 uppercase tracking-widest border-b border-white/5 pb-2">Data Dasar</h4>
                    <div>
                        <label class="block text-[9px] text-slate-500 uppercase font-black mb-1">TMT Guru</label>
                        <input type="date" name="tmt" id="edit-tgl" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                    <div>
                        <label class="block text-[9px] text-slate-500 uppercase font-black mb-1">JTM Target per Bulan</label>
                        <input type="number" name="jtm_jumlah" id="edit-jtm" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                    <div>
                        <label class="block text-[9px] text-slate-500 uppercase font-black mb-1">Tunjangan Tetap (Rp)</label>
                        <input type="number" name="tunjangan_tetap" id="edit-tetap" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-emerald-400">
                    </div>
                </div>

                <!-- Group 2: Rate Khusus -->
                <div class="space-y-4">
                    <h4 class="text-[10px] font-bold text-amber-400 uppercase tracking-widest border-b border-white/5 pb-2">Rate Khusus (Langkahi jika Global)</h4>
                    <div>
                        <label class="block text-[9px] text-slate-500 uppercase font-black mb-1">Rate JTM Guru (Rp)</label>
                        <input type="number" name="rate_jtm_guru" id="edit-rate-jtm" placeholder="0" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-amber-300">
                    </div>
                    <div>
                        <label class="block text-[9px] text-slate-500 uppercase font-black mb-1">Rate Kehadiran Guru (Rp)</label>
                        <input type="number" name="rate_kehadiran_guru" id="edit-rate-hadir" placeholder="0" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-blue-300">
                    </div>
                </div>

                <!-- Group 3: Scoring -->
                <div class="space-y-4">
                    <h4 class="text-[10px] font-bold text-purple-400 uppercase tracking-widest border-b border-white/5 pb-2">Skoring Jabatan</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[9px] text-slate-500 uppercase font-black mb-1">Jabatan</label>
                            <input type="number" name="score_jabatan" id="edit-jabatan" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white">
                        </div>
                        <div>
                            <label class="block text-[9px] text-slate-500 uppercase font-black mb-1">Wali Kelas</label>
                            <input type="number" name="score_wali_kelas" id="edit-wk" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[9px] text-slate-500 uppercase font-black mb-1">Bid. Kependidikan</label>
                        <input type="number" name="score_bid_pddk" id="edit-bid" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                    <div>
                        <label class="block text-[9px] text-slate-500 uppercase font-black mb-1">Skor Manual Tambahan</label>
                        <input type="number" name="score_manual" id="edit-manual" class="w-full bg-slate-900 border border-white/20 rounded-xl px-3 py-2 text-xs text-white font-bold">
                    </div>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')" class="flex-1 bg-white/5 hover:bg-white/10 py-4 rounded-2xl text-[10px] font-black uppercase text-white transition-all">Batal</button>
                <button type="submit" name="update_config" class="flex-[2] bg-blue-600 hover:bg-blue-500 py-4 rounded-2xl text-[10px] font-black uppercase text-white shadow-xl shadow-blue-600/20 transition-all">Simpan Config</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModalEdit(data) {
    document.getElementById('modal-title').innerText = 'Config Gaji: ' + data.nama;
    document.getElementById('edit-id').value = data.id_guru;
    document.getElementById('edit-tgl').value = data.tmt || '';
    document.getElementById('edit-jtm').value = data.jtm_jumlah || 0;
    document.getElementById('edit-jabatan').value = data.score_jabatan || 0;
    document.getElementById('edit-wk').value = data.score_wali_kelas || 0;
    document.getElementById('edit-bid').value = data.score_bid_pddk || 0;
    document.getElementById('edit-manual').value = data.score_manual || 0;
    document.getElementById('edit-tetap').value = data.tunjangan_tetap || 0;
    document.getElementById('edit-rate-jtm').value = data.rate_jtm_guru || 0;
    document.getElementById('edit-rate-hadir').value = data.rate_kehadiran_guru || 0;
    document.getElementById('modal-edit').classList.remove('hidden');
    document.getElementById('modal-edit').classList.add('flex');
}
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
