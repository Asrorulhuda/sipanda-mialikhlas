<?php
$page_title = 'Update Sistem';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);

// Ensure github_token column
try { $pdo->exec("ALTER TABLE tbl_setting ADD COLUMN github_token TEXT DEFAULT NULL"); } catch(Exception $e) {}
$s = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
$has_token = !empty($s['github_token']);

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<style>
    .update-hero { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%); position: relative; overflow: hidden; }
    .update-hero::before { content: ''; position: absolute; inset: 0; background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%234f46e5' fill-opacity='0.06'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }
    .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }
    @keyframes pulse-dot { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .5; transform: scale(1.5); } }
    .step-enter { animation: slideIn .3s ease-out forwards; opacity: 0; }
    @keyframes slideIn { to { opacity: 1; transform: translateY(0); } from { transform: translateY(10px); } }
    .shimmer { background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,.05) 50%, transparent 100%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
    @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
    .rotate-icon { animation: rotate 1s linear infinite; }
    @keyframes rotate { to { transform: rotate(360deg); } }
</style>

<!-- Hero Section -->
<div class="update-hero rounded-2xl p-6 md:p-8 mb-6 relative">
    <div class="relative z-10">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <i class="fas fa-cloud-download-alt text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">Update Center</h2>
                        <p class="text-xs text-slate-400">Kelola pembaruan sistem SIPANDA secara online</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Versi Terpasang</p>
                    <p class="text-2xl font-black text-white tracking-tight" id="currentVersion"><?= APP_VERSION ?></p>
                </div>
                <div class="w-px h-10 bg-white/10"></div>
                <div class="text-right">
                    <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">PHP</p>
                    <p class="text-sm font-mono text-slate-300"><?= PHP_VERSION ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Token Configuration -->
<div class="glass rounded-xl p-5 mb-6 border-l-4 <?= $has_token ? 'border-emerald-500/50' : 'border-amber-500/50' ?>" id="tokenSection">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold flex items-center gap-2">
            <i class="fas fa-key <?= $has_token ? 'text-emerald-400' : 'text-amber-400' ?>"></i>
            GitHub Access Token
            <?php if ($has_token): ?>
                <span class="text-[10px] bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded-full font-bold">TERHUBUNG</span>
            <?php else: ?>
                <span class="text-[10px] bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded-full font-bold">BELUM DIKONFIGURASI</span>
            <?php endif; ?>
        </h3>
        <button onclick="document.getElementById('tokenForm').classList.toggle('hidden')" class="text-xs text-blue-400 hover:text-blue-300 font-medium">
            <i class="fas fa-edit mr-1"></i><?= $has_token ? 'Ubah Token' : 'Setup Token' ?>
        </button>
    </div>
    
    <div id="tokenForm" class="<?= $has_token ? 'hidden' : '' ?>">
        <div class="bg-slate-800/50 rounded-lg p-4 mb-3 border border-white/5">
            <p class="text-xs text-slate-400 mb-2"><i class="fas fa-info-circle text-blue-400 mr-1"></i>Cara mendapatkan token:</p>
            <ol class="text-[11px] text-slate-500 space-y-1 ml-4 list-decimal">
                <li>Buka <a href="https://github.com/settings/tokens" target="_blank" class="text-blue-400 hover:underline">GitHub Settings → Personal Access Tokens</a></li>
                <li>Klik <strong>"Generate new token (classic)"</strong></li>
                <li>Beri nama (misal: <em>SIPANDA Update</em>), centang scope: <code class="bg-slate-700 px-1 rounded">repo</code></li>
                <li>Klik Generate, lalu salin token yang muncul</li>
            </ol>
        </div>
        <div class="flex gap-2">
            <input type="password" id="githubToken" placeholder="ghp_xxxxxxxxxxxxxxxxxxxx" 
                   class="flex-1 bg-slate-800/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm font-mono placeholder:italic placeholder:text-slate-600 focus:border-indigo-500/50 focus:outline-none transition-colors"
                   value="<?= $has_token ? '••••••••••••••••' : '' ?>">
            <button onclick="saveToken()" id="btnSaveToken" class="bg-indigo-600 hover:bg-indigo-500 px-5 py-2.5 rounded-lg text-sm font-medium transition-all flex items-center gap-2 whitespace-nowrap">
                <i class="fas fa-save"></i>Simpan
            </button>
        </div>
    </div>
</div>

<!-- Update Check & Apply -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Check Update Card -->
    <div class="lg:col-span-2">
        <div class="glass rounded-xl p-5 h-full">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-sm font-semibold flex items-center gap-2">
                    <i class="fas fa-sync-alt text-blue-400"></i>Pembaruan Sistem
                </h3>
                <button onclick="checkUpdate()" id="btnCheck" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-xs font-medium transition-all flex items-center gap-2" <?= !$has_token ? 'disabled' : '' ?>>
                    <i class="fas fa-search" id="iconCheck"></i>Cek Update
                </button>
            </div>

            <!-- Result Area -->
            <div id="updateResult" class="hidden">
                <!-- Filled by JS -->
            </div>

            <!-- No Check Yet -->
            <div id="noCheckYet" class="text-center py-10">
                <div class="w-16 h-16 mx-auto rounded-full bg-slate-800/80 flex items-center justify-center mb-4">
                    <i class="fas fa-cloud text-3xl text-slate-600"></i>
                </div>
                <p class="text-sm text-slate-400">Klik <strong>"Cek Update"</strong> untuk memeriksa pembaruan terbaru</p>
                <p class="text-[10px] text-slate-600 mt-1">Sumber: github.com/Asrorulhuda/sipanda</p>
            </div>
        </div>
    </div>

    <!-- Info Card -->
    <div>
        <div class="glass rounded-xl p-5 h-full">
            <h3 class="text-sm font-semibold flex items-center gap-2 mb-4">
                <i class="fas fa-shield-alt text-emerald-400"></i>Keamanan Update
            </h3>
            <div class="space-y-3">
                <div class="flex items-start gap-3 p-3 rounded-lg bg-emerald-500/5 border border-emerald-500/10">
                    <i class="fas fa-database text-emerald-400 mt-0.5 text-xs"></i>
                    <div><p class="text-xs font-medium text-emerald-300">Auto-Backup DB</p><p class="text-[10px] text-slate-500">Database otomatis di-backup sebelum setiap update</p></div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-lg bg-blue-500/5 border border-blue-500/10">
                    <i class="fas fa-lock text-blue-400 mt-0.5 text-xs"></i>
                    <div><p class="text-xs font-medium text-blue-300">Config Aman</p><p class="text-[10px] text-slate-500">File konfigurasi lokal (koneksi.php) tidak akan ditimpa</p></div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-lg bg-purple-500/5 border border-purple-500/10">
                    <i class="fas fa-images text-purple-400 mt-0.5 text-xs"></i>
                    <div><p class="text-xs font-medium text-purple-300">Data Lokal Aman</p><p class="text-[10px] text-slate-500">Foto, logo, gambar CMS tidak akan terhapus</p></div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-lg bg-amber-500/5 border border-amber-500/10">
                    <i class="fas fa-code-branch text-amber-400 mt-0.5 text-xs"></i>
                    <div><p class="text-xs font-medium text-amber-300">Migrasi DB Otomatis</p><p class="text-[10px] text-slate-500">Perubahan struktur database dijalankan otomatis</p></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Progress Modal -->
<div id="updateModal" class="fixed inset-0 z-[9999] hidden">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-white/10 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="p-6 border-b border-white/5 bg-gradient-to-r from-indigo-600/20 to-purple-600/20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center">
                        <i class="fas fa-cog rotate-icon text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-white" id="modalTitle">Memproses Update...</h3>
                        <p class="text-xs text-slate-400" id="modalSub">Jangan tutup halaman ini</p>
                    </div>
                </div>
            </div>
            <!-- Progress -->
            <div class="p-6">
                <div class="w-full bg-slate-800 rounded-full h-2 mb-5 overflow-hidden">
                    <div id="progressBar" class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-500 shimmer" style="width: 5%"></div>
                </div>
                <div id="updateSteps" class="space-y-2 max-h-60 overflow-y-auto pr-1 scrollbar-thin">
                    <!-- Steps injected by JS -->
                </div>
            </div>
            <!-- Close Button (hidden initially) -->
            <div id="modalFooter" class="p-4 border-t border-white/5 hidden">
                <button onclick="closeModal()" class="w-full py-2.5 rounded-lg text-sm font-medium transition-all" id="btnCloseModal">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Update History -->
<div class="glass rounded-xl p-5">
    <h3 class="text-sm font-semibold flex items-center gap-2 mb-4">
        <i class="fas fa-history text-slate-400"></i>Riwayat Update
    </h3>
    <div id="updateHistory">
        <div class="text-center py-6">
            <div class="w-8 h-8 mx-auto rounded-full border-2 border-slate-700 border-t-blue-500 animate-spin mb-3"></div>
            <p class="text-xs text-slate-500">Memuat riwayat...</p>
        </div>
    </div>
</div>

<script>
const API = '<?= BASE_URL ?>api/updater.php';

// ─── Check for Updates ───
async function checkUpdate() {
    const btn = document.getElementById('btnCheck');
    const icon = document.getElementById('iconCheck');
    btn.disabled = true;
    icon.className = 'fas fa-spinner fa-spin';

    try {
        const res = await fetch(API + '?action=check');
        const data = await res.json();

        document.getElementById('noCheckYet').classList.add('hidden');
        document.getElementById('updateResult').classList.remove('hidden');

        if (data.status === 'error') {
            document.getElementById('updateResult').innerHTML = `
                <div class="flex items-center gap-3 p-4 rounded-xl bg-red-500/10 border border-red-500/20">
                    <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
                    <div><p class="text-sm font-medium text-red-300">${data.message}</p></div>
                </div>`;
            return;
        }

        if (data.has_update) {
            const changelog = (data.changelog || []).map(c => `<li class="flex items-start gap-2"><i class="fas fa-check text-emerald-400 mt-0.5 text-[10px]"></i><span>${c}</span></li>`).join('');
            document.getElementById('updateResult').innerHTML = `
                <div class="rounded-xl border border-indigo-500/20 overflow-hidden">
                    <div class="p-4 bg-gradient-to-r from-indigo-600/10 to-purple-600/10 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full bg-indigo-400 pulse-dot"></div>
                            <div>
                                <p class="text-sm font-bold text-white">Update Tersedia!</p>
                                <p class="text-[10px] text-slate-400">Dirilis: ${data.released || '-'}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="px-2 py-1 rounded bg-slate-700 text-slate-300 font-mono text-xs">${data.local_version}</span>
                            <i class="fas fa-arrow-right text-indigo-400 text-xs"></i>
                            <span class="px-2 py-1 rounded bg-indigo-600/30 text-indigo-300 font-mono text-xs font-bold">${data.remote_version}</span>
                        </div>
                    </div>
                    ${data.critical ? '<div class="px-4 py-2 bg-red-500/10 border-y border-red-500/10 text-xs text-red-400 font-medium"><i class="fas fa-fire mr-1"></i>Update kritis — sangat disarankan segera diupdate</div>' : ''}
                    <div class="p-4">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Changelog</p>
                        <ul class="space-y-1.5 text-xs text-slate-300 mb-4">${changelog}</ul>
                        <button onclick="startUpdate()" class="w-full py-3 rounded-xl text-sm font-bold bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 transition-all shadow-lg shadow-indigo-500/20 flex items-center justify-center gap-2">
                            <i class="fas fa-cloud-download-alt"></i>Update Sekarang ke ${data.remote_version}
                        </button>
                    </div>
                </div>`;
        } else {
            document.getElementById('updateResult').innerHTML = `
                <div class="text-center py-8">
                    <div class="w-16 h-16 mx-auto rounded-full bg-emerald-500/10 flex items-center justify-center mb-3">
                        <i class="fas fa-check-circle text-3xl text-emerald-400"></i>
                    </div>
                    <p class="text-sm font-bold text-emerald-300">Sistem sudah versi terbaru!</p>
                    <p class="text-xs text-slate-500 mt-1">${data.local_version} adalah versi terbaru yang tersedia</p>
                </div>`;
        }
    } catch(e) {
        document.getElementById('updateResult').innerHTML = `
            <div class="flex items-center gap-3 p-4 rounded-xl bg-red-500/10 border border-red-500/20">
                <i class="fas fa-wifi text-red-400 text-xl"></i>
                <div><p class="text-sm font-medium text-red-300">Gagal terhubung ke server</p><p class="text-[10px] text-slate-500">${e.message}</p></div>
            </div>`;
    } finally {
        btn.disabled = false;
        icon.className = 'fas fa-search';
    }
}

// ─── Start Update Process ───
async function startUpdate() {
    if (!confirm('⚠️ Yakin akan melanjutkan update?\n\nDatabase akan otomatis di-backup sebelum update dimulai.\nPastikan koneksi internet stabil.')) return;

    const modal = document.getElementById('updateModal');
    const steps = document.getElementById('updateSteps');
    const progress = document.getElementById('progressBar');
    const footer = document.getElementById('modalFooter');

    modal.classList.remove('hidden');
    steps.innerHTML = '';
    progress.style.width = '5%';
    footer.classList.add('hidden');

    // Simulate progress animation while waiting
    let pct = 5;
    const progressInterval = setInterval(() => {
        if (pct < 85) { pct += Math.random() * 8; progress.style.width = Math.min(pct, 85) + '%'; }
    }, 800);

    try {
        const res = await fetch(API + '?action=update', { method: 'POST' });
        
        // Baca response sebagai text dulu, baru parse JSON
        // Ini mencegah error "Unexpected end of JSON input" jika PHP crash
        const rawText = await res.text();
        clearInterval(progressInterval);

        let data;
        try {
            data = JSON.parse(rawText);
        } catch (parseErr) {
            // Response bukan JSON — PHP crash atau error HTML
            progress.style.width = '100%';
            progress.className = progress.className.replace('from-indigo-500 to-purple-500', 'from-red-500 to-orange-500');
            
            let errorDetail = 'Server mengembalikan response kosong atau tidak valid.';
            if (!rawText || rawText.trim() === '') {
                errorDetail = 'Response kosong — kemungkinan PHP crash karena memory limit, timeout, atau ekstensi tidak tersedia. Cek error log hosting Anda.';
            } else if (rawText.includes('Fatal error') || rawText.includes('Allowed memory')) {
                errorDetail = 'PHP Fatal Error terdeteksi: ' + rawText.substring(0, 300);
            } else if (rawText.includes('<br') || rawText.includes('<html')) {
                errorDetail = 'Server mengembalikan HTML (bukan JSON). Kemungkinan ada error PHP. Detail: ' + rawText.substring(0, 300);
            } else {
                errorDetail = 'Response tidak valid: ' + rawText.substring(0, 300);
            }

            steps.innerHTML = `
                <div class="text-xs text-red-400 space-y-2">
                    <div><i class="fas fa-times-circle mr-1"></i><strong>Server Error</strong></div>
                    <div class="text-red-300/80">${errorDetail}</div>
                    <div class="mt-2 p-2 rounded bg-slate-800/50 text-slate-400 font-mono text-[10px] max-h-24 overflow-auto">${rawText.substring(0, 500) || '(empty response)'}</div>
                    <div class="text-amber-400 mt-2"><i class="fas fa-lightbulb mr-1"></i>Tips: Cek menu Error Log di cPanel hosting, atau hubungi provider hosting.</div>
                </div>`;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-exclamation-triangle text-red-400 mr-2"></i>Update Gagal';
            document.getElementById('modalSub').textContent = 'PHP crash — response tidak valid';
            document.getElementById('btnCloseModal').className = 'w-full py-2.5 rounded-lg text-sm font-medium bg-slate-700 hover:bg-slate-600 text-white transition-all';
            document.getElementById('btnCloseModal').textContent = 'Tutup';
            document.querySelector('.rotate-icon')?.classList.remove('rotate-icon');
            footer.classList.remove('hidden');
            return;
        }

        // Show all steps
        if (data.steps) {
            steps.innerHTML = '';
            data.steps.forEach((s, i) => {
                setTimeout(() => {
                    const isError = s.step.includes('❌');
                    const isSuccess = s.step.includes('✅');
                    const icon = isError ? 'fa-times-circle text-red-400' : (isSuccess ? 'fa-check-circle text-emerald-400' : 'fa-check text-blue-400');
                    steps.innerHTML += `
                        <div class="flex items-start gap-2 text-xs step-enter">
                            <i class="fas ${icon} mt-0.5"></i>
                            <span class="${isError ? 'text-red-300' : (isSuccess ? 'text-emerald-300 font-bold' : 'text-slate-300')}">${s.step}</span>
                            <span class="ml-auto text-slate-600 text-[10px] whitespace-nowrap">${s.time}</span>
                        </div>`;
                    steps.scrollTop = steps.scrollHeight;
                }, i * 200);
            });
        }

        // Tampilkan debug info jika ada (dari shutdown handler)
        if (data.debug) {
            const dbg = data.debug;
            setTimeout(() => {
                steps.innerHTML += `
                    <div class="flex items-start gap-2 text-xs step-enter mt-2 p-2 rounded bg-slate-800/50 border border-white/5">
                        <i class="fas fa-bug text-amber-400 mt-0.5"></i>
                        <div class="text-slate-400">
                            <div>📄 File: ${dbg.file || '-'} · Line: ${dbg.line || '-'}</div>
                            <div>💾 Memory Peak: ${dbg.memory_peak || '-'} · Limit: ${dbg.memory_limit || '-'}</div>
                        </div>
                    </div>`;
                steps.scrollTop = steps.scrollHeight;
            }, (data.steps?.length || 0) * 200 + 100);
        }

        // Final state
        setTimeout(() => {
            progress.style.width = '100%';
            if (data.status === 'success') {
                progress.className = progress.className.replace('from-indigo-500 to-purple-500', 'from-emerald-500 to-teal-500');
                document.getElementById('modalTitle').textContent = '✅ Update Berhasil!';
                document.getElementById('modalSub').textContent = 'SIPANDA berhasil diperbarui ke ' + data.version;
                document.getElementById('btnCloseModal').className = 'w-full py-2.5 rounded-lg text-sm font-medium bg-emerald-600 hover:bg-emerald-500 text-white transition-all';
                document.getElementById('btnCloseModal').textContent = 'Muat Ulang Halaman';
                document.getElementById('btnCloseModal').onclick = () => location.reload();
            } else {
                progress.className = progress.className.replace('from-indigo-500 to-purple-500', 'from-red-500 to-orange-500');
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-exclamation-triangle text-red-400 mr-2"></i>Update Gagal';
                document.getElementById('modalSub').textContent = data.message;
                document.getElementById('btnCloseModal').className = 'w-full py-2.5 rounded-lg text-sm font-medium bg-slate-700 hover:bg-slate-600 text-white transition-all';
                document.getElementById('btnCloseModal').textContent = 'Tutup';
            }
            // stop the rotate animation
            document.querySelector('.rotate-icon')?.classList.remove('rotate-icon');
            footer.classList.remove('hidden');
            loadHistory();
        }, (data.steps?.length || 1) * 200 + 500);

    } catch(e) {
        clearInterval(progressInterval);
        progress.style.width = '100%';
        progress.className = progress.className.replace('from-indigo-500 to-purple-500', 'from-red-500 to-orange-500');
        steps.innerHTML = `
            <div class="text-xs text-red-400 space-y-1">
                <div><i class="fas fa-times-circle mr-1"></i>Error: ${e.message}</div>
                <div class="text-amber-400"><i class="fas fa-lightbulb mr-1"></i>Pastikan koneksi internet stabil dan hosting tidak sedang maintenance.</div>
            </div>`;
        document.getElementById('modalTitle').textContent = 'Update Gagal';
        document.getElementById('modalSub').textContent = 'Gagal terhubung ke server';
        document.getElementById('btnCloseModal').className = 'w-full py-2.5 rounded-lg text-sm font-medium bg-slate-700 hover:bg-slate-600 text-white transition-all';
        document.getElementById('btnCloseModal').textContent = 'Tutup';
        document.querySelector('.rotate-icon')?.classList.remove('rotate-icon');
        footer.classList.remove('hidden');
    }
}

function closeModal() {
    document.getElementById('updateModal').classList.add('hidden');
}

// ─── Save GitHub Token ───
async function saveToken() {
    const token = document.getElementById('githubToken').value.trim();
    if (!token || token.includes('•')) { alert('Masukkan token GitHub yang valid'); return; }

    const btn = document.getElementById('btnSaveToken');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>Menyimpan...';

    try {
        const fd = new FormData();
        fd.append('action', 'save_token');
        fd.append('token', token);
        const res = await fetch(API, { method: 'POST', body: fd });
        const data = await res.json();

        if (data.status === 'success') {
            alert(data.message);
            location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    } catch(e) {
        alert('Error: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i>Simpan';
    }
}

// ─── Load Update History ───
async function loadHistory() {
    try {
        const res = await fetch(API + '?action=log');
        const data = await res.json();
        const el = document.getElementById('updateHistory');

        if (!data.log || data.log.length === 0) {
            el.innerHTML = '<p class="text-sm text-slate-500 text-center py-4">Belum ada riwayat update.</p>';
            return;
        }

        let html = '<div class="space-y-2">';
        data.log.forEach(l => {
            const ok = l.status === 'success';
            html += `
                <div class="flex items-center justify-between p-3 rounded-lg bg-white/5 border border-white/5 hover:border-white/10 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg ${ok ? 'bg-emerald-500/20' : 'bg-red-500/20'} flex items-center justify-center">
                            <i class="fas ${ok ? 'fa-check text-emerald-400' : 'fa-times text-red-400'} text-xs"></i>
                        </div>
                        <div>
                            <p class="text-xs font-medium">${l.from} <i class="fas fa-arrow-right text-[8px] text-slate-600 mx-1"></i> ${l.to}</p>
                            <p class="text-[10px] text-slate-500">${l.date} · oleh ${l.by || 'Admin'}</p>
                        </div>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold ${ok ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'}">${ok ? 'Berhasil' : 'Gagal'}</span>
                </div>`;
        });
        html += '</div>';
        el.innerHTML = html;
    } catch(e) {
        document.getElementById('updateHistory').innerHTML = '<p class="text-xs text-slate-500 text-center py-4">Gagal memuat riwayat</p>';
    }
}

// Load history on page load
loadHistory();
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
