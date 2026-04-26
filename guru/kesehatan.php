<?php
$page_title = 'Kesehatan & Rekam Medis Guru';
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
cek_fitur('uks');
$id_guru = (int)$_SESSION['user_id'];

// Get History
$stmt = $pdo->prepare("SELECT * FROM tbl_uks_kunjungan WHERE id_user=? AND role_user='guru' ORDER BY created_at DESC");
$stmt->execute([$id_guru]);
$history = $stmt->fetchAll();

// Get Latest Physical Data
$fisik_stmt = $pdo->prepare("SELECT * FROM tbl_uks_fisik WHERE id_user=? AND role_user='guru' ORDER BY tanggal DESC LIMIT 1");
$fisik_stmt->execute([$id_guru]);
$latest_fisik = $fisik_stmt->fetch();

// Get Medicine Stock
$obats = $pdo->query("SELECT * FROM tbl_uks_obat ORDER BY nama_obat")->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-white flex items-center gap-2 font-black italic uppercase tracking-widest"><i class="fas fa-hand-holding-medical text-rose-500"></i> Layanan Kesehatan (Guru)</h2>
    <div class="flex gap-2 font-bold uppercase tracking-widest leading-none">
        <button onclick="document.getElementById('modalSakit').classList.remove('hidden')" class="bg-rose-600 hover:bg-rose-500 px-6 py-2.5 rounded-xl text-xs text-white shadow-lg shadow-rose-600/20 transition-all flex items-center gap-2"><i class="fas fa-plus"></i> Lapor Sakit / Minta Obat</button>
    </div>
</div>

<!-- Stats Card BB/TB -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="glass p-5 rounded-2xl border-l-4 border-blue-500 shadow-xl">
        <div class="flex items-center justify-between mb-2">
            <i class="fas fa-weight-hanging text-blue-500 text-xl"></i>
            <span class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Berat Badan</span>
        </div>
        <h4 class="text-3xl font-black text-white"><?= $latest_fisik['berat'] ?? '0' ?> <span class="text-xs text-slate-500 uppercase font-bold italic">Kg</span></h4>
    </div>
    <div class="glass p-5 rounded-2xl border-l-4 border-purple-500 shadow-xl">
        <div class="flex items-center justify-between mb-2">
            <i class="fas fa-ruler-vertical text-purple-500 text-xl"></i>
            <span class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Tinggi Badan</span>
        </div>
        <h4 class="text-3xl font-black text-white"><?= $latest_fisik['tinggi'] ?? '0' ?> <span class="text-xs text-slate-500 uppercase font-bold italic">Cm</span></h4>
    </div>
    <div class="glass p-5 rounded-2xl border-l-4 border-emerald-500 shadow-xl">
        <div class="flex items-center justify-between mb-2">
            <i class="fas fa-heartbeat text-emerald-500 text-xl"></i>
            <span class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">BMI (IMT)</span>
        </div>
        <h4 class="text-3xl font-black text-white"><?= number_format($latest_fisik['bmi'] ?? 0, 1) ?></h4>
    </div>
    <div class="glass p-5 rounded-2xl border-l-4 border-amber-500 shadow-xl">
        <div class="flex items-center justify-between mb-2">
            <i class="fas fa-apple-alt text-amber-500 text-xl"></i>
            <span class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Status Gizi</span>
        </div>
        <h4 class="text-xl font-black text-white uppercase italic"><?= $latest_fisik['status_gizi'] ?? '-' ?></h4>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Activity History -->
    <div class="lg:col-span-2">
        <div class="p-5 border-b border-white/5 bg-white/3">
            <h3 class="font-bold text-white flex items-center gap-2 text-sm uppercase tracking-widest"><i class="fas fa-history text-slate-400"></i> Riwayat Kunjungan UKS</h3>
        </div>
        <div class="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 border-b border-white/5 bg-black/20">
                            <th class="p-4 font-bold uppercase tracking-widest text-[10px]">Waktu</th>
                            <th class="p-4 font-bold uppercase tracking-widest text-[10px]">Keluhan Aktif</th>
                            <th class="p-4 font-bold uppercase tracking-widest text-[10px]">Pemberian Obat & Tindakan</th>
                            <th class="p-4 font-bold uppercase tracking-widest text-[10px]">Status Laporan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($history as $h): ?>
                        <tr class="hover:bg-white/5 transition-all">
                            <td class="p-4">
                                <span class="block font-bold text-white"><?= tgl_indo($h['tanggal']) ?></span>
                                <span class="text-[10px] text-slate-500 font-mono italic"><?= date('H:i', strtotime($h['jam'])) ?> WIB</span>
                            </td>
                            <td class="p-4">
                                <p class="text-slate-300 italic max-w-sm"><?= clean($h['keluhan']) ?></p>
                                <?php if ($h['tipe'] == 'Minta Obat'): ?>
                                    <span class="text-[9px] px-1.5 py-0.5 bg-blue-500/20 text-blue-400 font-bold uppercase rounded mt-1 inline-block">REQUEST OBAT</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <?php if ($h['diagnosa'] || $h['tindakan']): ?>
                                    <p class="text-emerald-400 font-medium"><?= clean($h['diagnosa'] ?: '-') ?></p>
                                    <p class="text-[10px] text-slate-500 mt-1"><?= clean($h['tindakan'] ?: '-') ?></p>
                                <?php else: ?>
                                    <span class="text-slate-600 italic">Sedang diverifikasi petugas...</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <?php 
                                $class = 'bg-slate-800 text-slate-400';
                                if($h['status'] == 'Menunggu') $class = 'bg-amber-500/20 text-amber-400 animate-pulse';
                                if($h['status'] == 'Dirawat') $class = 'bg-blue-500/20 text-blue-400';
                                if($h['status'] == 'Kembali ke Kelas') $class = 'bg-emerald-500/20 text-emerald-400';
                                if($h['status'] == 'Pulang') $class = 'bg-rose-500/20 text-rose-400';
                                if($h['status'] == 'Disetujui') $class = 'bg-blue-600 text-white shadow-lg shadow-blue-600/30';
                                if($h['status'] == 'Ditolak') $class = 'bg-red-500/20 text-red-400';
                                ?>
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-tighter <?= $class ?>"><?= $h['status'] ?></span>
                            </td>
                        </tr>
                        <?php endforeach; if(!$history) echo '<tr><td colspan="4" class="p-10 text-center text-slate-500 italic">Belum ada riwayat kunjungan UKS.</td></tr>'; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- PENGUKURAN FISIK GURU -->
    <div class="glass p-6 rounded-2xl border border-white/5">
        <h3 class="font-bold text-white text-sm mb-4 border-b border-white/5 pb-2 uppercase tracking-widest"><i class="fas fa-weight-scale text-blue-400 mr-2"></i> Update Pengukuran</h3>
        <form id="formFisik" class="space-y-4">
            <input type="hidden" name="action" value="simpan_fisik">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-2">Berat (Kg)</label>
                    <input type="number" step="0.1" name="berat" required value="<?= $latest_fisik['berat'] ?? '' ?>" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-blue-500 focus:outline-none text-white font-bold">
                </div>
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-2">Tinggi (Cm)</label>
                    <input type="number" step="0.1" name="tinggi" required value="<?= $latest_fisik['tinggi'] ?? '' ?>" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-purple-500 focus:outline-none text-white font-bold">
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 py-3 rounded-xl text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition-all font-black uppercase tracking-widest italic">Update Data Fisik</button>
        </form>
    </div>

    <!-- STOK OBAT TERSEDIA GURU -->
    <div class="glass p-6 rounded-2xl border border-white/5 bg-emerald-500/5">
        <h3 class="font-bold text-white text-sm mb-4 border-b border-white/5 pb-2 uppercase tracking-widest flex items-center justify-between">
            <span><i class="fas fa-pills text-emerald-400 mr-2"></i> Inventori UKS</span>
            <span class="text-[9px] bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded-full font-black animate-pulse">LIVE</span>
        </h3>
        <div class="space-y-3 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
            <?php foreach ($obats as $o): 
                $stock_class = 'text-emerald-400';
                $status_label = 'Tersedia';
                if ($o['stok'] <= 0) {
                    $stock_class = 'text-rose-500';
                    $status_label = 'Habis';
                } elseif ($o['stok'] <= 5) {
                    $stock_class = 'text-amber-500';
                    $status_label = 'Terbatas';
                }
            ?>
            <div class="flex items-center justify-between p-3 rounded-xl bg-white/5 border border-white/5 hover:bg-white/10 transition-all">
                <div>
                    <p class="text-xs font-bold text-white leading-none mb-1"><?= clean($o['nama_obat']) ?></p>
                    <p class="text-[9px] text-slate-500 uppercase font-bold tracking-widest"><?= clean($o['satuan'] ?? 'Pcs') ?></p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-black <?= $stock_class ?>"><?= $o['stok'] ?> <span class="text-[8px] opacity-50 uppercase">Pcs</span></p>
                    <span class="text-[8px] font-black uppercase tracking-widest <?= $stock_class ?>"><?= $status_label ?></span>
                </div>
            </div>
            <?php endforeach; if(!$obats) echo '<p class="text-center text-slate-600 italic text-xs py-10">Data obat belum diinput.</p>'; ?>
        </div>
        <p class="text-[9px] text-slate-500 mt-4 italic font-medium leading-tight">*Inventori obat dipantau secara real-time untuk keperluan kedaruratan sekolah.</p>
    </div>
</div>

<!-- Modal Lapor Sakit -->
<div id="modalSakit" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[999] hidden flex items-center justify-center p-4">
    <div class="glass w-full max-w-md rounded-2xl border border-white/10 shadow-2xl overflow-hidden animate-zoom-in">
        <div class="p-6 border-b border-white/5 flex items-center justify-between">
            <h3 class="text-lg font-bold flex items-center gap-2 text-white italic font-black uppercase tracking-widest"><i class="fas fa-hand-holding-medical text-rose-500"></i> Lapor Kesehatan</h3>
            <button onclick="document.getElementById('modalSakit').classList.add('hidden')" class="text-slate-400 hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form id="formLaporSakit" class="p-6 space-y-4">
            <div class="flex gap-2 mb-4 bg-black/20 p-1 rounded-xl">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="action" value="lapor_sakit" checked class="hidden peer">
                    <div class="p-2 text-center rounded-lg text-[10px] font-bold uppercase transition-all peer-checked:bg-rose-600 peer-checked:text-white text-slate-500">Kunjungan/Sakit</div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="action" value="minta_obat" class="hidden peer">
                    <div class="p-2 text-center rounded-lg text-[10px] font-bold uppercase transition-all peer-checked:bg-blue-600 peer-checked:text-white text-slate-500">Minta Obat</div>
                </label>
            </div>
            <div>
                <label id="lblKeluhan" class="block text-xs text-slate-400 mb-2 uppercase tracking-widest font-bold">Keluhan / Gejala</label>
                <textarea name="keluhan" required placeholder="..." class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-rose-500 focus:outline-none min-h-[100px] text-white"></textarea>
            </div>
            <p class="text-[10px] text-slate-500 italic leading-relaxed">Laporan Anda akan langsung muncul di dashboard petgas UKS.</p>
            <div class="pt-2 flex gap-3">
                <button type="submit" class="w-full bg-rose-600 hover:bg-rose-500 px-6 py-4 rounded-xl text-sm font-bold shadow-lg shadow-rose-600/20 transition-all text-white font-black uppercase tracking-widest italic">Kirim Laporan</button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle Label Modal
document.querySelectorAll('input[name="action"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const lbl = document.getElementById('lblKeluhan');
        if (this.value === 'minta_obat') {
            lbl.innerText = 'Obat yang Dibutuhkan & Alasan';
            document.querySelector('#modalSakit textarea').placeholder = "Parasetamol untuk pusing, vitamin, dll...";
        } else {
            lbl.innerText = 'Keluhan / Gejala';
            document.querySelector('#modalSakit textarea').placeholder = "Cth: Pusing, demam tinggi, atau kelelahan...";
        }
    });
});

document.getElementById('formFisik').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    
    fetch('<?= BASE_URL ?>api/uks.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({ title: 'Berhasil!', text: data.message, icon: 'success', confirmButtonText: 'Gasspol!' }).then(() => location.reload());
        } else {
            Swal.fire('Gagal!', data.message, 'error');
        }
    })
    .finally(() => btn.disabled = false);
});

document.getElementById('formLaporSakit').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';

    fetch('<?= BASE_URL ?>api/uks.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire('Terkirim!', data.message, 'success').then(() => location.reload());
            document.getElementById('modalSakit').classList.add('hidden');
            this.reset();
        } else {
            Swal.fire('Gagal!', data.message, 'error');
        }
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Kirim Laporan';
    });
});
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
