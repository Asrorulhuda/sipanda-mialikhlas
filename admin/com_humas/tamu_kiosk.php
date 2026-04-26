<?php
$page_title = 'Digital Guestbook — Kiosk Humas';
require_once __DIR__ . '/../../config/init.php';

// Public access allowed
$s = $pdo->query("SELECT nama_sekolah, wa_tu, kepsek, wa_kepsek FROM tbl_setting WHERE id=1")->fetch();
$staff_list = $pdo->query("SELECT nama FROM tbl_guru WHERE status='Aktif' ORDER BY nama ASC")->fetchAll();

// Stats for indicators (Initial)
$stats_masuk = $pdo->query("SELECT COUNT(*) FROM tbl_humas_tamu WHERE status='Masuk' AND DATE(tanggal) = CURDATE()")->fetchColumn();
$stats_keluar = $pdo->query("SELECT COUNT(*) FROM tbl_humas_tamu WHERE status='Keluar' AND DATE(tanggal) = CURDATE()")->fetchColumn();

require_once __DIR__ . '/../../template/header.php';
?>

    <!-- Background Decor -->
    <div class="fixed top-0 left-0 w-full h-full -z-10 opacity-20 pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-rose-600 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-blue-600 rounded-full blur-[120px]"></div>
    </div>

    <div class="max-w-[1400px] mx-auto px-6 pt-12 pb-4">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-black tracking-tighter uppercase italic leading-none mb-3">DIGITAL GUESTBOOK</h1>
            <p class="text-xs text-slate-500 font-bold uppercase tracking-widest"><?= $s['nama_sekolah'] ?> — SIPANDA SMART ECOSYSTEM</p>
        </div>

        <?= alert_flash('msg_kiosk') ?>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-8 items-start">

            <!-- Left Sidebar -->
            <div class="xl:col-span-3 order-2 xl:order-1 animate-fade-in">
                <div class="glass h-[600px] rounded-[2.5rem] p-6 border border-emerald-500/10 flex flex-col">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
                        <h3 class="text-xs font-black uppercase tracking-[0.2em] text-emerald-400"><span id="count_masuk"><?= $stats_masuk ?></span> Tamu Didalam</h3>
                    </div>
                    <div id="list_masuk" class="flex-1 overflow-y-auto space-y-3 custom-scroll pr-1">
                        <!-- AJAX content -->
                        <p class="text-xs text-slate-600 italic">Memuat...</p>
                    </div>
                </div>
            </div>

            <!-- Middle Content: Main Monitor -->
            <div id="main_screen" class="xl:col-span-6 order-1 xl:order-2 space-y-8 animate-fade-in">
                <!-- Mode Selection / Tap Area -->
                <div class="glass rounded-[2.5rem] p-12 border border-white/5 shadow-2xl text-center relative overflow-hidden group">
                    <div class="relative z-10">
                        <div class="w-24 h-24 rounded-3xl bg-rose-600/20 flex items-center justify-center text-rose-500 mx-auto mb-8 animate-bounce-subtle">
                            <i class="fas fa-id-card text-4xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold mb-2">TEMPELKAN KTP / KARTU</h2>
                        <p class="text-slate-400 text-sm max-w-sm mx-auto">Silakan tempelkan E-KTP atau Kartu Tamu pada alat scanner untuk Check-In atau Check-Out otomatis.</p>
                    </div>
                    <!-- Hidden RFID Input -->
                    <input type="text" id="rfid_hidden" class="absolute inset-0 opacity-0 cursor-default" autofocus>
                    <div class="absolute inset-0 bg-white/5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                </div>

                <div class="flex items-center gap-4 py-4">
                    <div class="h-px flex-1 bg-white/5"></div>
                    <span class="text-[10px] font-black text-slate-600 uppercase tracking-[0.3em]">ATAU</span>
                    <div class="h-px flex-1 bg-white/5"></div>
                </div>

                <a href="tamu_registrasi.php" class="w-full py-6 glass rounded-2xl border border-white/5 hover:border-blue-500/30 transition-all group flex items-center justify-center gap-4 active:scale-95 no-underline">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-400 group-hover:bg-blue-500 group-hover:text-white transition-all">
                        <i class="fas fa-keyboard"></i>
                    </div>
                    <span class="font-bold text-lg">ISI SECARA MANUAL</span>
                </a>
            </div> <!-- Close Main Monitor -->

            <!-- Right Sidebar -->
            <div class="xl:col-span-3 order-3 animate-fade-in" style="animation-delay: 200ms;">
                <div class="glass h-[600px] rounded-[2.5rem] p-6 border border-white/5 flex flex-col">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-2 h-2 rounded-full bg-slate-500"></div>
                        <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-500"><span id="count_keluar"><?= $stats_keluar ?></span> Sudah Pulang</h3>
                    </div>
                    <div id="list_keluar" class="flex-1 overflow-y-auto space-y-3 custom-scroll pr-1">
                        <!-- AJAX content -->
                    </div>
                </div>
        </div> <!-- grid closure -->
    </div> <!-- container closure -->
    
    <!-- Processing Overlay -->
    <div id="loading" class="fixed inset-0 bg-black/90 backdrop-blur-md z-[100] hidden flex flex-col items-center justify-center transition-all duration-500">
        <div id="loader_spinner" class="w-24 h-24 border-4 border-rose-500 border-t-transparent rounded-full animate-spin mb-8"></div>
        <div id="loader_success" class="hidden scale-150 mb-10 text-emerald-500"><i class="fas fa-check-circle text-6xl"></i></div>
        <p id="loader_text" class="text-2xl font-black italic tracking-[0.2em] animate-pulse text-white uppercase">MEMPROSES DATA...</p>
        <p id="loader_subtext" class="text-slate-400 mt-4 font-bold tracking-widest hidden">SAMPAI JUMPA KEMBALI</p>
    </div>

    <script>
        const rfidInput = document.getElementById('rfid_hidden');
        
        // Polling Stats Update (Indicators)
        function updateStats() {
            fetch(`../../api/rfid.php?action=get_kiosk_stats`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('count_masuk').innerText = data.count_masuk;
                document.getElementById('count_keluar').innerText = data.count_keluar;
                
                // Update List Masuk
                let htmlIn = '';
                if(data.names_in.length > 0) {
                    data.names_in.forEach(n => {
                        htmlIn += `<div class="bg-white/5 rounded-xl p-3 border border-white/5 animate-fade-in"><p class="text-[11px] font-bold text-slate-200">${n}</p><p class="text-[9px] text-slate-500 uppercase mt-1">Check-in Today</p></div>`;
                    });
                } else { htmlIn = '<p class="text-xs text-slate-600 italic">Tidak ada tamu...</p>'; }
                document.getElementById('list_masuk').innerHTML = htmlIn;

                // Update List Keluar
                let htmlOut = '';
                if(data.names_out.length > 0) {
                    data.names_out.forEach(n => {
                        htmlOut += `<div class="bg-white/5 rounded-xl p-3 border border-white/5 opacity-60 animate-fade-in"><p class="text-[11px] font-bold text-slate-400">${n}</p><p class="text-[9px] text-slate-600 uppercase mt-1">Checked-out</p></div>`;
                    });
                } else { htmlOut = '<p class="text-xs text-slate-700 italic">Belum ada...</p>'; }
                document.getElementById('list_keluar').innerHTML = htmlOut;
            });
        }
        setInterval(updateStats, 10000); // Update sidebars every 10s
        updateStats(); // Initial load
        
        // Flush antrian lama saat monitor dibuka
        fetch(`../../api/rfid.php?action=clear_monitor`);

        // Always keep focus on rfid input
        document.addEventListener('click', () => {
            const el = document.activeElement;
            if(el.tagName !== 'INPUT' && el.tagName !== 'SELECT') {
                rfidInput.focus();
            }
        });

        rfidInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const rfid = this.value.trim();
                this.value = '';
                if(rfid.length >= 8) processRFID(rfid);
            }
        });

        function checkMonitor() {
            fetch(`../../api/rfid.php?action=cek_monitor`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'ok') {
                    if (data.event_type === 'checkout') {
                        showCheckoutSuccess(data.rfid, data.guest_name);
                    } else if (data.event_type === 'new_visit') {
                        window.location.href = `tamu_registrasi.php?rfid=${data.rfid}`;
                    } else {
                        // Fallback to old behavior if type missing
                        processRFID(data.rfid);
                    }
                }
            });
        }
        setInterval(checkMonitor, 2000);

        function processRFID(rfid) {
            const loading = document.getElementById('loading');
            if (loading) loading.classList.remove('hidden');
            
            fetch(`../../api/rfid.php?action=tamu_tap&rfid=${rfid}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'checkout') {
                    showCheckoutSuccess(rfid, data.nama);
                } else if (data.status === 'new_visit') {
                    if (loading) loading.classList.add('hidden');
                    window.location.href = `tamu_registrasi.php?rfid=${rfid}`;
                } else {
                    if (loading) loading.classList.add('hidden');
                }
            })
            .catch(err => {
                if (loading) loading.classList.add('hidden');
                console.error(err);
            });
        }

        function showCheckoutSuccess(rfid, name) {
            const loading = document.getElementById('loading');
            const spinner = document.getElementById('loader_spinner');
            const success = document.getElementById('loader_success');
            const lText = document.getElementById('loader_text');
            const lSub = document.getElementById('loader_subtext');

            if (loading) {
                loading.classList.remove('hidden');
                spinner.classList.add('hidden');
                success.classList.remove('hidden');
                lText.innerText = 'BERHASIL CHECK-OUT!';
                lText.classList.remove('animate-pulse');
                lText.classList.add('text-emerald-400');
                lSub.innerText = `SELAMAT JALAN, ${name.toUpperCase()}`;
                lSub.classList.remove('hidden');

                setTimeout(() => {
                    loading.classList.add('hidden');
                    // Reset
                    lText.classList.add('animate-pulse');
                    lText.classList.remove('text-emerald-400');
                    spinner.classList.remove('hidden');
                    success.classList.add('hidden');
                    updateStats(); 
                }, 3500);
            }
        }
    </script>

    <style>
        .animate-bounce-subtle { animation: bounce-subtle 2s infinite; }
        @keyframes bounce-subtle { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }
    </style>
</body>
</html>
