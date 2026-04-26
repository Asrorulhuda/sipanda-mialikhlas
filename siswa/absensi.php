<?php
$page_title = 'Absensi Saya';
require_once __DIR__ . '/../config/init.php';
cek_role(['siswa']);
cek_fitur('absensi');
$id = $_SESSION['user_id'];
$bln = (int)($_GET['bln'] ?? date('m')); $thn = (int)($_GET['thn'] ?? date('Y'));
$stmt = $pdo->prepare("SELECT * FROM tbl_absensi_siswa WHERE id_siswa=? AND MONTH(tanggal)=? AND YEAR(tanggal)=? ORDER BY tanggal DESC"); $stmt->execute([$id,$bln,$thn]); $data = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_absensi_siswa WHERE id_siswa=? AND MONTH(tanggal)=? AND YEAR(tanggal)=? AND keterangan='Tepat Waktu'"); $stmt->execute([$id,$bln,$thn]); $hadir = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_absensi_siswa WHERE id_siswa=? AND MONTH(tanggal)=? AND YEAR(tanggal)=? AND keterangan='Terlambat'"); $stmt->execute([$id,$bln,$thn]); $telat = $stmt->fetchColumn();
require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<!-- Quick Stats & Actions -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="glass rounded-xl p-4 text-center bg-emerald-500/5 border-emerald-500/20"><p class="text-2xl font-bold text-emerald-400"><?= $hadir ?></p><p class="text-xs text-slate-400">Tepat Waktu</p></div>
    <div class="glass rounded-xl p-4 text-center bg-amber-500/5 border-amber-500/20"><p class="text-2xl font-bold text-amber-400"><?= $telat ?></p><p class="text-xs text-slate-400">Terlambat</p></div>
    <div class="glass rounded-xl p-4 text-center"><p class="text-2xl font-bold"><?= count($data) ?></p><p class="text-xs text-slate-400">Total Kehadiran</p></div>
    <button onclick="openModalIzin()" class="glass rounded-xl p-4 text-center border-blue-500/30 hover:bg-blue-500/10 transition-all group">
        <i class="fas fa-notes-medical text-blue-400 text-2xl mb-1 group-hover:scale-110 transition-transform"></i>
        <p class="text-xs font-bold text-blue-400">Ajukan Izin/Sakit</p>
    </button>
</div>

<div class="glass rounded-xl p-5 border border-white/5 shadow-2xl relative overflow-hidden">
    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl"></div>
    <div class="table-container"><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3 px-2">Tanggal</th><th class="pb-3">Masuk</th><th class="pb-3">Keluar</th><th class="pb-3">Status/Ket</th><th class="pb-3">Bukti</th></tr></thead>
        <tbody><?php foreach ($data as $r): 
            $colors = [
                'Tepat Waktu' => 'text-emerald-400',
                'Terlambat' => 'text-amber-400',
                'Sakit' => 'text-blue-400',
                'Izin' => 'text-blue-400'
            ];
            $status_color = $colors[$r['keterangan']] ?? 'text-red-400';
        ?>
        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
            <td class="py-3 px-2 font-medium"><?= tgl_indo($r['tanggal']) ?></td>
            <td class="font-mono text-xs"><?= $r['jam_masuk']?substr($r['jam_masuk'],0,5):'-' ?></td>
            <td class="font-mono text-xs"><?= $r['jam_keluar']?substr($r['jam_keluar'],0,5):'-' ?></td>
            <td>
                <div class="flex flex-col">
                    <span class="text-xs font-bold <?= $status_color ?>"><?= $r['keterangan'] ?></span>
                    <?php if($r['status_verifikasi'] == 'Pending'): ?><span class="text-[9px] text-amber-500 italic">Menunggu Verifikasi</span>
                    <?php elseif($r['status_verifikasi'] == 'Disetujui'): ?><span class="text-[9px] text-emerald-500"><i class="fas fa-check mr-1"></i>Terverifikasi</span>
                    <?php endif; ?>
                </div>
            </td>
            <td>
                <?php if($r['foto']): ?>
                    <button onclick="viewBukti('<?= $r['foto'] ?>')" class="text-blue-400 hover:text-white transition-colors"><i class="fas fa-image"></i></button>
                <?php else: ?>-<?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?></tbody>
    </table></div>
</div>

<!-- Modal Ajukan Izin/Sakit -->
<div id="modalIzin" class="fixed inset-0 z-[100] flex items-center justify-center px-4 hidden">
    <div class="absolute inset-0 bg-black/90 backdrop-blur-md" onclick="closeModalIzin()"></div>
    <div class="glass w-full max-w-lg relative z-10 p-8 rounded-[2rem] border border-white/10 shadow-2xl animate-zoom-in max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-8">
            <h3 class="text-xl font-bold text-white">Form Pengajuan Izin/Sakit</h3>
            <button onclick="closeModalIzin()" class="text-slate-400 hover:text-white"><i class="fas fa-times"></i></button>
        </div>

        <form id="formIzin" action="../api/proses_izin_siswa.php" method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <button type="button" onclick="setType('Sakit')" id="btnSakit" class="p-4 rounded-2xl border-2 border-white/5 bg-white/5 text-center transition-all">
                    <i class="fas fa-bed text-2xl mb-2 text-rose-400"></i>
                    <p class="text-xs font-bold">SAKIT</p>
                </button>
                <button type="button" onclick="setType('Izin')" id="btnIzin" class="p-4 rounded-2xl border-2 border-white/5 bg-white/5 text-center transition-all">
                    <i class="fas fa-envelope-open-text text-2xl mb-2 text-blue-400"></i>
                    <p class="text-xs font-bold">IZIN</p>
                </button>
                <input type="hidden" name="keterangan" id="inputKeterangan" required>
            </div>

            <!-- Lokasi Tracking -->
            <div id="locStatus" class="p-3 bg-white/5 border border-white/10 rounded-xl flex items-center gap-3">
                <i class="fas fa-map-marker-alt text-blue-400 animate-pulse"></i>
                <p class="text-[10px] text-slate-400 italic">Mendeteksi lokasi...</p>
                <input type="hidden" name="lat" id="lat">
                <input type="hidden" name="lng" id="lng">
            </div>

            <!-- Kamera Proof -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest">Ambil Foto Selfie Bukti</label>
                <div id="cameraArea" class="relative rounded-2xl overflow-hidden border-2 border-white/10 bg-black aspect-video flex items-center justify-center">
                    <video id="video" autoplay playsinline class="w-full h-full object-cover"></video>
                    <canvas id="canvas" class="hidden absolute inset-0 w-full h-full object-cover"></canvas>
                    <div id="snapFeedback" class="absolute inset-0 bg-white opacity-0 transition-opacity pointer-events-none"></div>
                    <button type="button" onclick="takeSnapshot()" id="btnCapture" class="absolute bottom-4 left-1/2 -translate-x-1/2 w-14 h-14 rounded-full border-4 border-white bg-red-500 shadow-xl active:scale-90 transition-all"></button>
                </div>
                <input type="hidden" name="foto_selfie" id="foto_selfie" required>
            </div>

            <!-- Surat Dokter (Conditional) -->
            <div id="areaSuratDokter" class="hidden animate-fade-in">
                <label class="block text-xs font-bold text-amber-500 uppercase tracking-widest mb-2 flex items-center gap-2">
                    <i class="fas fa-file-medical"></i> Unggah Surat Dokter (Wajib >3 Hari)
                </label>
                <input type="file" name="surat_dokter" id="surat_dokter" accept="image/*,.pdf" class="w-full bg-slate-900 border border-amber-500/30 rounded-xl px-4 py-3 text-xs text-white file:mr-3 file:bg-amber-500 file:text-white file:border-0 file:rounded file:px-3 file:py-1">
            </div>

            <textarea name="alasan" placeholder="Tuliskan alasan/keterangan..." required class="w-full bg-slate-900 border border-white/10 rounded-2xl px-4 py-4 text-sm text-white focus:border-blue-500 focus:outline-none min-h-[100px]"></textarea>

            <button type="submit" id="btnSubmit" class="w-full bg-blue-600 hover:bg-blue-500 py-4 rounded-2xl text-sm font-extrabold shadow-xl shadow-blue-600/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                Kirim Pengajuan
            </button>
        </form>
    </div>
</div>

<script>
    let video = document.getElementById('video');
    let canvas = document.getElementById('canvas');
    let stream = null;

    function openModalIzin() {
        document.getElementById('modalIzin').classList.remove('hidden');
        startCamera();
        detectLocation();
        checkSicknessDuration();
    }

    function closeModalIzin() {
        document.getElementById('modalIzin').classList.add('hidden');
        if(stream) stream.getTracks().forEach(t => t.stop());
    }

    function setType(type) {
        document.getElementById('inputKeterangan').value = type;
        document.getElementById('btnSakit').className = type === 'Sakit' ? 'p-4 rounded-2xl border-2 border-rose-500 bg-rose-500/10 text-center transition-all' : 'p-4 rounded-2xl border-2 border-white/5 bg-white/5 text-center transition-all';
        document.getElementById('btnIzin').className = type === 'Izin' ? 'p-4 rounded-2xl border-2 border-blue-500 bg-blue-500/10 text-center transition-all' : 'p-4 rounded-2xl border-2 border-white/5 bg-white/5 text-center transition-all';
        checkSicknessDuration();
    }

    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
            video.srcObject = stream;
        } catch(err) {
            alert('Akses kamera ditolak! Mohon beri izin untuk bukti presensi.');
        }
    }

    function takeSnapshot() {
        const snapFeedback = document.getElementById('snapFeedback');
        snapFeedback.style.opacity = '1';
        setTimeout(() => snapFeedback.style.opacity = '0', 100);

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        
        const dataUrl = canvas.toDataURL('image/jpeg', 0.7);
        document.getElementById('foto_selfie').value = dataUrl;
        
        video.classList.add('hidden');
        canvas.classList.remove('hidden');
        document.getElementById('btnCapture').innerHTML = '<i class="fas fa-undo text-white"></i>';
        document.getElementById('btnCapture').onclick = resetCamera;
    }

    function resetCamera() {
        video.classList.remove('hidden');
        canvas.classList.add('hidden');
        document.getElementById('foto_selfie').value = '';
        document.getElementById('btnCapture').innerHTML = '';
        document.getElementById('btnCapture').onclick = takeSnapshot;
    }

    function detectLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(pos => {
                document.getElementById('lat').value = pos.coords.latitude;
                document.getElementById('lng').value = pos.coords.longitude;
                document.getElementById('locStatus').innerHTML = '<i class="fas fa-check-circle text-emerald-500"></i><p class="text-[10px] text-emerald-400">Lokasi berhasil dikunci: ' + pos.coords.latitude.toFixed(4) + ', ' + pos.coords.longitude.toFixed(4) + '</p>';
            }, err => {
                document.getElementById('locStatus').innerHTML = '<i class="fas fa-exclamation-triangle text-rose-500"></i><p class="text-[10px] text-rose-400">Lokasi gagal dideteksi (Gunakan GPS).</p>';
            });
        }
    }

    function checkSicknessDuration() {
        const type = document.getElementById('inputKeterangan').value;
        if(type !== 'Sakit') {
            document.getElementById('areaSuratDokter').classList.add('hidden');
            document.getElementById('surat_dokter').required = false;
            return;
        }

        // Logic check durasi ke-3 hari (Simulasi via PHP nanti, sekarang fetch stats)
        fetch('../api/check_sickness_count.php')
        .then(res => res.json())
        .then(data => {
            if(data.count >= 2) { // Jika sudah 2 hari sakit sebelumnya, hari ini ke-3
                document.getElementById('areaSuratDokter').classList.remove('hidden');
                document.getElementById('surat_dokter').required = true;
                alert('Peringatan: Anda sudah mengajukan sakit selama ' + data.count + ' hari. Hari ini wajib menyertakan Surat Keterangan Dokter.');
            }
        });
    }
</script>
<?php require_once __DIR__ . '/../template/footer.php'; ?>
