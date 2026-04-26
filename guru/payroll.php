<?php
$page_title = 'Slip Gaji Saya';
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
cek_fitur('payroll');

$id_guru = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM tbl_payroll_history WHERE id_guru = ? ORDER BY tahun DESC, bulan DESC");
$stmt->execute([$id_guru]);
$data = $stmt->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex flex-col md:flex-row items-center justify-between mb-8 gap-4">
    <div>
        <h2 class="text-2xl font-black italic tracking-tighter uppercase text-white"><i class="fas fa-file-invoice-dollar mr-2 text-emerald-400"></i>Slip Gaji Saya</h2>
        <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Riwayat pembayaran gaji bulanan Anda</p>
    </div>
</div>

<div class="glass rounded-3xl p-6 border border-white/5 relative overflow-hidden">
    <div class="table-container">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="text-slate-500 border-b border-white/10 italic text-[10px] uppercase tracking-widest">
                    <th class="py-4 px-2">Periode</th>
                    <th class="py-4 text-center">Rekap Kehadiran</th>
                    <th class="py-4 text-center">Grand Total</th>
                    <th class="py-4 text-center">Status</th>
                    <th class="py-4 text-center">Tanggal Bayar</th>
                    <th class="py-4 text-right">Slip Gaji</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($data as $r): ?>
                <tr class="hover:bg-white/5 transition-all">
                    <td class="py-4 px-2 font-bold text-white uppercase italic">
                        <?= bulan_indo($r['bulan']) ?> <?= $r['tahun'] ?>
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
                    <td class="py-4 text-center">
                        <span class="text-emerald-400 font-black">Rp <?= number_format($r['total_diterima'], 0, ',', '.') ?></span>
                    </td>
                    <td class="py-4 text-center">
                        <?php if ($r['status'] == 'Draft'): ?>
                        <span class="bg-amber-500/10 text-amber-500 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border border-amber-500/20">Proses</span>
                        <?php else: ?>
                        <span class="bg-emerald-500/10 text-emerald-500 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border border-emerald-500/20">Lunas</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-4 text-center text-slate-400 font-mono text-[10px]">
                        <?= $r['tanggal_bayar'] ? date('d/m/Y H:i', strtotime($r['tanggal_bayar'])) : '-' ?>
                    </td>
                    <td class="py-4 text-right">
                        <a href="../admin/com_laporan/cetak_slip.php?id=<?= $r['id_payroll'] ?>" target="_blank" class="bg-white/5 hover:bg-emerald-600 text-white px-4 py-1.5 rounded-xl text-[10px] font-bold transition-all border border-white/10">
                            <i class="fas fa-download mr-1"></i> Cetak Slip
                        </a>
                    </td>
                </tr>
                <?php endforeach; if (!$data): ?>
                <tr><td colspan="6" class="py-20 text-center text-slate-600 italic">Belum ada data slip gaji tersedia.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
