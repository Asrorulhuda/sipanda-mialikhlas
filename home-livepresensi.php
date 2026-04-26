<?php
// Live Presensi - Real-time Attendance Display
require_once __DIR__ . '/config/koneksi.php';
$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
$nama = $setting['nama_sekolah'] ?? 'SIPANDA';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Presensi — <?= htmlspecialchars($nama) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%); }
        .glass { background: rgba(30,41,59,.7); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,.08); }
        @keyframes slideInRight { from { opacity:0; transform:translateX(100px); } to { opacity:1; transform:translateX(0); } }
        @keyframes pulse-glow { 0%,100% { box-shadow: 0 0 20px rgba(59,130,246,.3); } 50% { box-shadow: 0 0 40px rgba(59,130,246,.6); } }
        .slide-in { animation: slideInRight .5s ease forwards; }
        .pulse-glow { animation: pulse-glow 2s ease-in-out infinite; }
        .status-masuk { background: linear-gradient(135deg, rgba(16,185,129,.2), rgba(16,185,129,.05)); border-color: rgba(16,185,129,.3); }
        .status-keluar { background: linear-gradient(135deg, rgba(59,130,246,.2), rgba(59,130,246,.05)); border-color: rgba(59,130,246,.3); }
        .status-terlambat { background: linear-gradient(135deg, rgba(245,158,11,.2), rgba(245,158,11,.05)); border-color: rgba(245,158,11,.3); }
    </style>
</head>
<body class="text-white min-h-screen flex flex-col">

<!-- Header -->
<header class="glass border-b border-white/5 px-6 py-4">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-xl shadow-lg pulse-glow">S</div>
            <div>
                <h1 class="text-xl font-bold"><?= htmlspecialchars($nama) ?></h1>
                <p class="text-xs text-slate-400">Live Presensi — Sistem Informasi Pokok Pendidikan</p>
            </div>
        </div>
        <div class="text-right">
            <div id="liveClock" class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">--:--:--</div>
            <p id="liveDate" class="text-xs text-slate-400"></p>
        </div>
    </div>
</header>

<!-- Stats Bar -->
<div class="glass border-b border-white/5 px-6 py-3">
    <div class="max-w-7xl mx-auto grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="text-center">
            <p class="text-2xl font-bold text-emerald-400" id="statMasuk">0</p>
            <p class="text-[10px] text-slate-500 uppercase tracking-wider">Siswa Masuk</p>
        </div>
        <div class="text-center">
            <p class="text-2xl font-bold text-blue-400" id="statKeluar">0</p>
            <p class="text-[10px] text-slate-500 uppercase tracking-wider">Siswa Pulang</p>
        </div>
        <div class="text-center">
            <p class="text-2xl font-bold text-amber-400" id="statTelat">0</p>
            <p class="text-[10px] text-slate-500 uppercase tracking-wider">Terlambat</p>
        </div>
        <div class="text-center">
            <p class="text-2xl font-bold text-emerald-400" id="statGuruMasuk">0</p>
            <p class="text-[10px] text-slate-500 uppercase tracking-wider">Guru Masuk</p>
        </div>
        <div class="text-center">
            <p class="text-2xl font-bold text-slate-300" id="statTotal">0</p>
            <p class="text-[10px] text-slate-500 uppercase tracking-wider">Total Hari Ini</p>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="flex-1 p-6">
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Latest Activity (Main) -->
        <div class="lg:col-span-2">
            <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4"><i class="fas fa-stream mr-2"></i>Aktivitas Terbaru</h2>
            <div id="activityFeed" class="space-y-3" style="max-height: 70vh; overflow-y: auto;">
                <div class="text-center text-slate-500 py-10"><i class="fas fa-satellite-dish text-3xl mb-3 block animate-pulse"></i>Menunggu data presensi...</div>
            </div>
        </div>

        <!-- Right Panel: Guru Status -->
        <div>
            <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4"><i class="fas fa-chalkboard-teacher mr-2"></i>Status Guru</h2>
            <div id="guruFeed" class="space-y-2" style="max-height: 70vh; overflow-y: auto;">
                <div class="text-center text-slate-500 py-5"><i class="fas fa-spinner animate-spin"></i></div>
            </div>
        </div>
    </div>
</div>

<script>
// Live Clock
function updateClock() {
    const now = new Date();
    document.getElementById('liveClock').textContent = now.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
    document.getElementById('liveDate').textContent = now.toLocaleDateString('id-ID', {weekday:'long', year:'numeric', month:'long', day:'numeric'});
}
setInterval(updateClock, 1000);
updateClock();

let lastId = 0;
let lastGuruId = 0;

function fetchData() {
    fetch('<?= BASE_URL ?>api/live_presensi.php?last_id=' + lastId + '&last_guru_id=' + lastGuruId)
        .then(r => r.json())
        .then(data => {
            // Update stats
            document.getElementById('statMasuk').textContent = data.stats.siswa_masuk;
            document.getElementById('statKeluar').textContent = data.stats.siswa_keluar;
            document.getElementById('statTelat').textContent = data.stats.siswa_telat;
            document.getElementById('statGuruMasuk').textContent = data.stats.guru_masuk;
            document.getElementById('statTotal').textContent = data.stats.total;

            // Update siswa feed
            if (data.siswa.length > 0) {
                const feed = document.getElementById('activityFeed');
                if (lastId === 0) feed.innerHTML = '';
                
                data.siswa.reverse().forEach(s => {
                    const statusClass = s.keterangan === 'Terlambat' ? 'status-terlambat' : (s.status === 'COMPLETE' ? 'status-keluar' : 'status-masuk');
                    const icon = s.status === 'COMPLETE' ? 'fa-sign-out-alt text-blue-400' : 'fa-sign-in-alt text-emerald-400';
                    const badge = s.keterangan === 'Terlambat' ? '<span class="text-xs px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400">Terlambat</span>' : 
                                  s.status === 'COMPLETE' ? '<span class="text-xs px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-400">Pulang</span>' :
                                  '<span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400">Masuk</span>';
                    
                    const html = `<div class="glass ${statusClass} rounded-xl p-4 slide-in border">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">${s.nama.charAt(0).toUpperCase()}</div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2"><span class="font-semibold">${s.nama}</span>${badge}</div>
                                <p class="text-xs text-slate-400">${s.kelas || '-'} · <i class="fas ${icon} mr-1"></i>${s.jam}</p>
                            </div>
                            <div class="text-right"><span class="text-xs text-slate-500">${s.jam}</span></div>
                        </div>
                    </div>`;
                    feed.insertAdjacentHTML('afterbegin', html);
                    lastId = Math.max(lastId, s.id);
                });

                // Keep max 30 items
                while (feed.children.length > 30) feed.removeChild(feed.lastChild);
            }

            // Update guru feed
            if (data.guru.length > 0) {
                const guruFeed = document.getElementById('guruFeed');
                if (lastGuruId === 0) guruFeed.innerHTML = '';

                data.guru.forEach(g => {
                    const existing = document.getElementById('guru-' + g.id_guru);
                    const html = `<div id="guru-${g.id_guru}" class="glass rounded-lg p-3 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-xs font-bold">${g.nama.charAt(0)}</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">${g.nama}</p>
                            <p class="text-xs text-slate-500">${g.jam_masuk}${g.jam_keluar ? ' → '+g.jam_keluar : ''}</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full ${g.status==='COMPLETE' ? 'bg-blue-500/20 text-blue-400' : 'bg-emerald-500/20 text-emerald-400'}">${g.status==='COMPLETE'?'Pulang':'Hadir'}</span>
                    </div>`;
                    if (existing) existing.outerHTML = html;
                    else guruFeed.insertAdjacentHTML('afterbegin', html);
                    lastGuruId = Math.max(lastGuruId, g.id);
                });
            }
        })
        .catch(err => console.error('Fetch error:', err));
}

// Poll every 3 seconds
fetchData();
setInterval(fetchData, 3000);
</script>
</body>
</html>
