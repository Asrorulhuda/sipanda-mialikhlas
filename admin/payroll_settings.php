<?php
$page_title = 'Pengaturan Payroll';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);
cek_fitur('payroll');

// Handle Settings Update
if (isset($_POST['update_rates'])) {
    foreach ($_POST['rates'] as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO tbl_payroll_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
    }
    flash('msg', 'Rate gaji berhasil diperbarui!');
    header('Location: payroll_settings.php'); exit;
}

$settings = $pdo->query("SELECT * FROM tbl_payroll_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// Get holidays from Setting Absen for preview
$holidays = $pdo->query("SELECT * FROM tbl_hari_libur ORDER BY tanggal DESC LIMIT 10")->fetchAll();

// Get jadwal guru for preview
$jadwal_guru = $pdo->query("SELECT hari FROM tbl_setting_absen_guru WHERE jam_masuk IS NOT NULL AND jam_masuk != ''")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex flex-col md:flex-row items-center justify-between mb-8 gap-4">
    <div>
        <h2 class="text-2xl font-black italic tracking-tighter uppercase text-white"><i class="fas fa-cogs mr-2 text-purple-500"></i>Pengaturan Payroll</h2>
        <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Konfigurasi nominal besaran gaji global</p>
    </div>
</div>

<?= alert_flash('msg') ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Rates Config -->
    <div class="lg:col-span-1">
        <div class="glass rounded-3xl p-6 border border-white/5 relative overflow-hidden h-full">
            <div class="absolute top-0 right-0 w-32 h-32 bg-purple-600/5 rounded-full blur-3xl -mr-16 -mt-16"></div>
            <h3 class="text-sm font-bold text-white mb-6 flex items-center"><i class="fas fa-coins mr-2 text-amber-400"></i>Konfigurasi Rate (Rp)</h3>
            <form method="POST" class="space-y-4 relative z-10">
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Rate per JTM</label>
                    <input type="number" name="rates[rate_jtm]" value="<?= $settings['rate_jtm'] ?? 0 ?>" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-purple-500 transition-all text-white font-mono">
                </div>
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Masa Kerja (per Tahun)</label>
                    <input type="number" name="rates[rate_masa_kerja]" value="<?= $settings['rate_masa_kerja'] ?? 0 ?>" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-purple-500 transition-all text-white font-mono">
                </div>
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Rate Kehadiran (per Hari)</label>
                    <input type="number" name="rates[rate_kehadiran]" value="<?= $settings['rate_kehadiran'] ?? 0 ?>" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-purple-500 transition-all text-white font-mono">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Kelebihan JTM</label>
                        <input type="number" name="rates[rate_kelebihan_jtm]" value="<?= $settings['rate_kelebihan_jtm'] ?? 0 ?>" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-purple-500 transition-all text-white font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Potongan JTM</label>
                        <input type="number" name="rates[rate_potongan_jtm]" value="<?= $settings['rate_potongan_jtm'] ?? 0 ?>" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-purple-500 transition-all text-white font-mono text-rose-400">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Potongan Absen</label>
                        <input type="number" name="rates[rate_potongan_alpha]" value="<?= $settings['rate_potongan_alpha'] ?? 0 ?>" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-purple-500 transition-all text-white font-mono text-rose-400">
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Besaran Skor Jbtn</label>
                        <input type="number" name="rates[rate_scoring]" value="<?= $settings['rate_scoring'] ?? 0 ?>" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-purple-500 transition-all text-white font-mono text-emerald-400">
                    </div>
                </div>
                <div class="pt-4">
                    <button type="submit" name="update_rates" class="w-full bg-purple-600 hover:bg-purple-500 py-3 rounded-2xl text-xs font-black uppercase tracking-widest text-white shadow-xl shadow-purple-600/20 transition-all">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Panel: Linked Data from Setting Absen -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Hari Kerja Guru -->
        <div class="glass rounded-3xl p-6 border border-white/5 relative overflow-hidden">
            <h3 class="text-sm font-bold text-white mb-4 flex items-center justify-between">
                <span><i class="fas fa-calendar-week mr-2 text-blue-400"></i>Hari Kerja Guru (dari Setting Absen)</span>
                <a href="setting_absen.php?tab=guru" class="text-[10px] bg-blue-600/20 hover:bg-blue-600/40 text-blue-400 px-3 py-1.5 rounded-lg border border-blue-500/20 transition-all">Kelola di Setting Absen →</a>
            </h3>
            <div class="flex flex-wrap gap-2">
                <?php 
                $all_days = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
                foreach ($all_days as $d): 
                    $active = in_array($d, $jadwal_guru);
                ?>
                <div class="px-4 py-2.5 rounded-xl text-xs font-bold <?= $active ? 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800/50 text-slate-600 border border-white/5 line-through' ?>">
                    <?= $d ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (empty($jadwal_guru)): ?>
            <p class="text-[10px] text-amber-400 italic mt-3"><i class="fas fa-exclamation-triangle mr-1"></i>Jadwal guru belum diset. Perhitungan hari kerja akan default ke Senin-Sabtu.</p>
            <?php endif; ?>
        </div>

        <!-- Tanggal Merah Preview -->
        <div class="glass rounded-3xl p-6 border border-white/5 relative overflow-hidden">
            <h3 class="text-sm font-bold text-white mb-4 flex items-center justify-between">
                <span><i class="fas fa-calendar-times mr-2 text-rose-500"></i>Tanggal Merah Terbaru (dari Setting Absen)</span>
                <a href="setting_absen.php?tab=libur" class="text-[10px] bg-rose-600/20 hover:bg-rose-600/40 text-rose-400 px-3 py-1.5 rounded-lg border border-rose-500/20 transition-all">Kelola di Setting Absen →</a>
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="text-slate-500 border-b border-white/10 italic text-[10px]">
                            <th class="pb-3 px-2">Tanggal</th>
                            <th class="pb-3">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($holidays as $h): ?>
                        <tr class="hover:bg-white/5 transition-all">
                            <td class="py-3 px-2 font-mono text-rose-400 text-xs"><?= tgl_indo($h['tanggal']) ?></td>
                            <td class="text-slate-300 text-xs"><?= clean($h['keterangan']) ?></td>
                        </tr>
                        <?php endforeach; if (!$holidays): ?>
                        <tr><td colspan="2" class="py-6 text-center text-slate-600 italic text-xs">Belum ada tanggal merah.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-[10px] text-slate-600 italic mt-3">Menampilkan 10 entri terbaru. Kelola selengkapnya di <b>Setting Absen > Tanggal Merah</b>.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
