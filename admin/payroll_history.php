<?php
$page_title = 'Riwayat Payroll';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);
cek_fitur('payroll');

$bulan = $_GET['bulan'] ?? date('n');
$tahun = $_GET['tahun'] ?? date('Y');

if (isset($_POST['update_status'])) {
    $pdo->prepare("UPDATE tbl_payroll_history SET status=?, tanggal_bayar=? WHERE id_payroll=?")
        ->execute([$_POST['status'], date('Y-m-d H:i:s'), $_POST['id_payroll']]);
    flash('msg', 'Status pembayaran diperbarui!');
    header("Location: payroll_history.php?bulan=$bulan&tahun=$tahun"); exit;
}

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_payroll_history WHERE id_payroll=?")->execute([$_GET['hapus']]);
    flash('msg', 'Data payroll dihapus!', 'warning');
    header("Location: payroll_history.php?bulan=$bulan&tahun=$tahun"); exit;
}

$stmt = $pdo->prepare("SELECT h.*, g.nama, g.nip FROM tbl_payroll_history h JOIN tbl_guru g ON h.id_guru = g.id_guru WHERE h.bulan=? AND h.tahun=? ORDER BY g.nama");
$stmt->execute([$bulan, $tahun]);
$data = $stmt->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex flex-col md:flex-row items-center justify-between mb-8 gap-4">
    <div>
        <h2 class="text-2xl font-black italic tracking-tighter uppercase text-white"><i class="fas fa-history mr-2 text-indigo-500"></i>Riwayat Payroll</h2>
        <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Kelola status pembayaran dan cetak slip gaji guru</p>
    </div>
    <div class="flex gap-2">
        <a href="com_laporan/cetak_pengambilan_honor.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" target="_blank" class="bg-emerald-600 hover:bg-emerald-500 px-6 py-2.5 rounded-xl text-xs font-bold text-white shadow-lg transition-all">
            <i class="fas fa-print mr-2"></i>Format Pengambilan
        </a>
        <a href="payroll_generate.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" class="bg-indigo-600 hover:bg-indigo-500 px-6 py-2.5 rounded-xl text-xs font-bold text-white shadow-lg transition-all">
            <i class="fas fa-plus mr-2"></i>Generate Lagi
        </a>
    </div>
</div>

<?= alert_flash('msg') ?>

<div class="glass rounded-3xl p-6 border border-white/5 mb-8">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Pilih Bulan</label>
            <select name="bulan" class="bg-slate-900 border border-white/10 rounded-xl px-4 py-2 text-sm text-white">
                <?php for($i=1;$i<=12;$i++): ?>
                <option value="<?= $i ?>" <?= $bulan == $i ? 'selected' : '' ?>><?= bulan_indo($i) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Pilih Tahun</label>
            <select name="tahun" class="bg-slate-900 border border-white/10 rounded-xl px-4 py-2 text-sm text-white font-mono">
                <?php $y = date('Y'); for($i=$y-2;$i<=$y+1;$i++): ?>
                <option value="<?= $i ?>" <?= $tahun == $i ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="submit" class="bg-slate-700 hover:bg-slate-600 px-6 py-2 rounded-xl text-xs font-bold text-white transition-all">Filter</button>
    </form>
</div>

<div class="glass rounded-3xl p-6 border border-white/5 relative overflow-hidden">
    <div class="table-container">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="text-slate-500 border-b border-white/10 italic text-[10px] uppercase tracking-widest">
                    <th class="py-4 px-2">Guru</th>
                    <th class="py-4 text-center">Rekap Kehadiran</th>
                    <th class="py-4 text-center">Pokok (JTM+MK)</th>
                    <th class="py-4 text-center">Tunjangan</th>
                    <th class="py-4 text-center">Lebih/Potong JTM</th>
                    <th class="py-4 text-center">Total Diterima</th>
                    <th class="py-4 text-center">Selisih</th>
                    <th class="py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($data as $r): 
                    $total_gapok = $r['gapok_jtm'] + $r['gapok_masa_kerja'];
                    $total_tunjangan = $r['tunjangan_jabatan'] + $r['tunjangan_kehadiran'] + ($r['tunjangan_tetap'] ?? 0);
                    $total_var = $r['kelebihan_jtm'] - $r['potongan_jtm'];
                ?>
                <tr class="hover:bg-white/5 transition-all group">
                    <td class="py-4 px-2">
                        <div class="font-bold text-white"><?= clean($r['nama']) ?></div>
                        <div class="text-[9px] text-slate-500 uppercase tracking-widest font-mono"><?= $r['status'] ?></div>
                    </td>
                    <td class="py-3">
                        <div class="flex items-center justify-center gap-1.5">
                            <div class="flex flex-col items-center px-1.5 py-0.5 rounded bg-emerald-500/10">
                                <span class="text-[7px] text-emerald-500/60 font-bold">H</span>
                                <span class="text-xs font-black text-emerald-400"><?= $r['hari_hadir'] ?? 0 ?></span>
                            </div>
                            <div class="flex flex-col items-center px-1.5 py-0.5 rounded bg-blue-500/10">
                                <span class="text-[7px] text-blue-500/60 font-bold">S</span>
                                <span class="text-xs font-black text-blue-400"><?= $r['hari_sakit'] ?? 0 ?></span>
                            </div>
                            <div class="flex flex-col items-center px-1.5 py-0.5 rounded bg-purple-500/10">
                                <span class="text-[7px] text-purple-500/60 font-bold">I</span>
                                <span class="text-xs font-black text-purple-400"><?= $r['hari_izin'] ?? 0 ?></span>
                            </div>
                            <div class="flex flex-col items-center px-1.5 py-0.5 rounded bg-rose-500/10">
                                <span class="text-[7px] text-rose-500/60 font-bold">A</span>
                                <span class="text-xs font-black text-rose-400"><?= $r['hari_alpha'] ?? 0 ?></span>
                            </div>
                        </div>
                        <div class="text-[8px] text-slate-600 text-center mt-0.5"><?= $r['hari_kerja_efektif'] ?? 0 ?> Hari Efektif</div>
                    </td>
                    <td class="py-4 text-center font-mono text-xs text-slate-300">Rp <?= number_format($total_gapok, 0, ',', '.') ?></td>
                    <td class="py-4 text-center font-mono text-xs text-emerald-400">Rp <?= number_format($total_tunjangan, 0, ',', '.') ?></td>
                    <td class="py-4 text-center font-mono text-xs <?= $total_var >= 0 ? 'text-blue-400' : 'text-rose-400' ?>">
                        <?= $total_var >= 0 ? '+' : '' ?>Rp <?= number_format($total_var, 0, ',', '.') ?>
                    </td>
                    <td class="py-4 text-center font-black text-white">Rp <?= number_format($r['total_diterima'], 0, ',', '.') ?></td>
                    <td class="py-4 text-center">
                        <?php if ($r['selisih_bulan_lalu'] > 0): ?>
                            <span class="text-[10px] text-emerald-400 font-bold"><i class="fas fa-caret-up mr-1 text-[8px]"></i><?= number_format($r['selisih_bulan_lalu'],0,',','.') ?></span>
                        <?php elseif ($r['selisih_bulan_lalu'] < 0): ?>
                            <span class="text-[10px] text-rose-400 font-bold"><i class="fas fa-caret-down mr-1 text-[8px]"></i><?= number_format(abs($r['selisih_bulan_lalu']),0,',','.') ?></span>
                        <?php else: ?>
                            <span class="text-[9px] text-slate-600 font-mono">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <?php if ($r['status'] == 'Draft'): ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="id_payroll" value="<?= $r['id_payroll'] ?>">
                                <input type="hidden" name="status" value="Paid">
                                <button type="submit" name="update_status" class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1 rounded-lg text-[10px] font-bold shadow-lg shadow-emerald-600/20 transition-all">Bayar</button>
                            </form>
                            <?php endif; ?>
                            <a href="com_laporan/cetak_slip.php?id=<?= $r['id_payroll'] ?>" target="_blank" class="bg-slate-700 hover:bg-slate-600 text-white px-3 py-1 rounded-lg text-[10px] font-bold">Slip</a>
                            <button onclick="confirmDelete('?hapus=<?= $r['id_payroll'] ?>&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>')" class="text-slate-500 hover:text-rose-500 transition-colors px-2"><i class="fas fa-trash text-xs"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; if (!$data): ?>
                <tr><td colspan="8" class="py-12 text-center text-slate-600 italic">Belum ada data payroll untuk bulan ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
