<?php
$page_title = 'Buat RPP Baru';
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
cek_fitur('ai_rpp');

$id_guru = $_SESSION['user_id'];

// Ambil mapel & kelas guru dari jadwal
$stmt = $pdo->prepare("SELECT DISTINCT m.* FROM tbl_jadwal j JOIN tbl_mapel m ON j.id_mapel=m.id_mapel WHERE j.id_guru=? ORDER BY m.nama_mapel");
$stmt->execute([$id_guru]);
$mapel_list = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT DISTINCT k.* FROM tbl_jadwal j JOIN tbl_kelas k ON j.id_kelas=k.id_kelas WHERE j.id_guru=? ORDER BY k.nama_kelas");
$stmt->execute([$id_guru]);
$kelas_list = $stmt->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     CSS — Animasi & Komponen Khusus RPP Generator
     ═══════════════════════════════════════════════════════════════════════════ -->
<style>
    @keyframes scanner-move {
        0%, 100% { transform: translateY(0) }
        50% { transform: translateY(220px) }
    }
    @keyframes pulse-ring {
        0% { transform: scale(.95); opacity: 1 }
        100% { transform: scale(1.3); opacity: 0 }
    }
    @keyframes float-dot {
        0%, 100% { transform: translateY(0); opacity: .4 }
        50% { transform: translateY(-8px); opacity: 1 }
    }
    @keyframes fade-in-up {
        from { opacity: 0; transform: translateY(16px) }
        to { opacity: 1; transform: translateY(0) }
    }
    @keyframes step-done {
        from { transform: scale(.5); opacity: 0 }
        to { transform: scale(1); opacity: 1 }
    }

    .ai-glow {
        border-color: rgba(139, 92, 246, .6) !important;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, .15) !important;
        transition: all .3s;
    }
    .field-error {
        border-color: rgba(239, 68, 68, .7) !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, .1) !important;
    }
    .result-section {
        animation: fade-in-up .5s ease forwards;
        opacity: 0;
    }
    .step-check {
        animation: step-done .3s ease forwards;
    }
    [x-cloak] { display: none !important }

    /* Tombol regenerate per section */
    .regen-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 10px;
        padding: 4px 10px;
        border-radius: 8px;
        cursor: pointer;
        background: rgba(139, 92, 246, .12);
        color: #a78bfa;
        border: 1px solid rgba(139, 92, 246, .25);
        transition: all .2s;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .regen-btn:hover {
        background: rgba(139, 92, 246, .25);
        color: #c4b5fd;
    }
    .regen-btn:disabled {
        opacity: .4;
        cursor: not-allowed;
    }

    .warning-banner {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 10px;
        background: rgba(245, 158, 11, .1);
        border: 1px solid rgba(245, 158, 11, .25);
        font-size: 11px;
        color: #fbbf24;
        margin-bottom: 10px;
    }
</style>

<!-- ═══════════════════════════════════════════════════════════════════════════
     HEADER HALAMAN
     ═══════════════════════════════════════════════════════════════════════════ -->
<div class="mb-6 flex items-center gap-4">
    <a href="rpp.php" class="w-10 h-10 rounded-xl glass flex items-center justify-center text-slate-400 hover:text-white transition-all">
        <i class="fas fa-arrow-left"></i>
    </a>
    <div>
        <h2 class="text-xl font-bold italic uppercase tracking-widest">Buat RPP Baru ✦</h2>
        <p class="text-xs text-slate-400">Isi identitas & CP, lalu biarkan AI menyusun seluruh RPP dalam 1 klik.</p>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     ALPINE.JS — RPP Wizard Controller
     ═══════════════════════════════════════════════════════════════════════════ -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('rppWizard', () => ({
        // ── State ──
        type: 'Kurmer',
        isLoading: false,
        isDone: false,
        currentStep: '',
        steps: [],
        times: { pendahuluan: '', inti: '', penutup: '' },
        hasWarning: false,
        warningMsg: '',

        // ── Manual Selection State ──
        dplList: [
            'Keimanan dan Ketakwaan terhadap Tuhan YME',
            'Kewargaan',
            'Penalaran Kritis',
            'Kreativitas',
            'Kolaborasi',
            'Kemandirian',
            'Kesehatan',
            'Komunikasi'
        ],
        dplSelected: [],
        dplDropdownOpen: false,

        pcList: [
            'Cinta kepada Allah dan Rasul-Nya (Tuhan)',
            'Cinta kepada Ilmu (Pengetahuan)',
            'Cinta kepada Diri dan Sesama (Manusia)',
            'Cinta kepada Lingkungan (Alam Semesta)',
            'Cinta kepada Tanah Air (Bangsa dan Negeri)'
        ],
        pcSelected: [],
        pcDropdownOpen: false,

        pedagogisList: [
            'Pembelajaran Langsung (Direct Instruction)',
            'Pembelajaran Berbasis Inkuiri (IBL)',
            'Pembelajaran Berbasis Proyek (PjBL)',
            'Pembelajaran Berbasis Masalah (PBL)',
            'Pedagogi Kolaboratif',
            'Pedagogi Eksperiensial',
            'Pembelajaran Berdiferensiasi',
            'Pendekatan Konstruktivis',
            'Metode Studi Kasus & Demonstrasi',
            'Pembelajaran Berbasis STEM',
            'Scaffolding',
            'Gamifikasi',
            'Flipped Classroom',
            'Pembelajaran Berbasis Teknologi',
            'Metode Ceramah Interaktif'
        ],
        pedagogisSelected: [],
        pedagogisDropdownOpen: false,

        toggleDpl(item) {
            if (this.dplSelected.includes(item)) {
                this.dplSelected = this.dplSelected.filter(i => i !== item);
            } else {
                if (this.dplSelected.length < 3) this.dplSelected.push(item);
                else Swal.fire({ toast: true, position: 'top-end', text: 'Maks. 3 DPL', icon: 'warning', showConfirmButton: false, timer: 2000 });
            }
        },

        togglePc(item) {
            if (this.pcSelected.includes(item)) {
                this.pcSelected = this.pcSelected.filter(i => i !== item);
            } else {
                if (this.pcSelected.length < 3) this.pcSelected.push(item);
                else Swal.fire({ toast: true, position: 'top-end', text: 'Maks. 3 Panca Cinta', icon: 'warning', showConfirmButton: false, timer: 2000 });
            }
        },

        togglePedagogis(item) {
            if (this.pedagogisSelected.includes(item)) {
                this.pedagogisSelected = this.pedagogisSelected.filter(i => i !== item);
            } else {
                if (this.pedagogisSelected.length < 2) this.pedagogisSelected.push(item);
                else Swal.fire({ toast: true, position: 'top-end', text: 'Maks. 2 Model Pembelajaran', icon: 'warning', showConfirmButton: false, timer: 2000 });
            }
        },

        // ── Step labels per kurikulum ──
        stepLabels: {
            Kurmer: [
                { key: 'tujuan', label: 'Tujuan Pembelajaran (TP)' },
                { key: 'pendahuluan', label: 'Kegiatan Pendahuluan' },
                { key: 'inti', label: 'Kegiatan Inti' },
                { key: 'penutup', label: 'Kegiatan Penutup' },
                { key: 'penilaian', label: 'Penilaian & Asesmen' },
            ],
            KBC: [
                { key: 'tujuan', label: 'Tujuan Pembelajaran (TP)' },
                { key: 'kesiapan', label: 'Identifikasi Kesiapan Murid' },
                { key: 'pendahuluan', label: 'Kegiatan Pendahuluan' },
                { key: 'pedatti_dalami', label: 'Tahap Dalami (Joyful)' },
                { key: 'pedatti_terapkan', label: 'Tahap Terapkan (Meaningful)' },
                { key: 'pedatti_tularkan', label: 'Tahap Tularkan (Mindful)' },
                { key: 'penutup', label: 'Kegiatan Penutup' },
                { key: 'asesmen_awal', label: 'Asesmen Awal' },
                { key: 'asesmen_proses', label: 'Asesmen Proses' },
                { key: 'asesmen_akhir', label: 'Asesmen Akhir (Sumatif)' },
            ]
        },

        // ── Ambil data dari form ──
        getPayload() {
            const mapelEl = document.querySelector('select[name=id_mapel]');
            const kelasEl = document.querySelector('select[name=id_kelas]');
            return {
                action: 'full_generate',
                type: this.type,
                topic: document.querySelector('input[name=judul_rpp]').value.trim(),
                mapel: mapelEl.options[mapelEl.selectedIndex].text,
                kelas: kelasEl.options[kelasEl.selectedIndex].text,
                semester: document.querySelector('select[name=semester]').value,
                alokasi_jp: document.querySelector('input[name=alokasi_jp]').value.trim(),
                menit_per_jp: document.querySelector('input[name=menit_per_jp]').value.trim(),
                cp: document.querySelector('textarea[name=cp]').value.trim(),
                dpl: this.dplSelected.join(', '),
                panca_cinta: this.type === 'KBC' ? this.pcSelected.join(', ') : '',
                pedagogis: this.pedagogisSelected.join(', '),
            };
        },

        // ── Validasi form ──
        validate() {
            const checks = [
                { sel: 'select[name=id_mapel]', label: 'Mata Pelajaran', emptyVal: '0' },
                { sel: 'select[name=id_kelas]', label: 'Kelas', emptyVal: '0' },
                { sel: 'input[name=judul_rpp]', label: 'Materi Pokok' },
                { sel: 'input[name=alokasi_jp]', label: 'Alokasi JP' },
                { sel: 'input[name=menit_per_jp]', label: 'Durasi Menit/JP' },
                { sel: 'textarea[name=cp]', label: 'Capaian Pembelajaran (CP)' },
            ];
            
            document.querySelectorAll('.field-error').forEach(el => el.classList.remove('field-error'));

            let missing = [];
            checks.forEach(c => {
                const el = document.querySelector(c.sel);
                if (!el) return;
                const val = el.value.trim();
                if (!val || val === (c.emptyVal ?? '')) {
                    missing.push(c.label);
                    el.classList.add('field-error');
                    if (missing.length === 1) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            if (this.pedagogisSelected.length === 0) missing.push('Model Pembelajaran (Minimal 1)');
            if (this.dplSelected.length === 0) missing.push('Dimensi Profil Pelajar (Minimal 1)');
            if (this.type === 'KBC' && this.pcSelected.length === 0) missing.push('Pilih Panca Cinta (Minimal 1)');

            return missing;
        },

        // ── Generate RPP penuh ──
        async generateRPP() {
            const missing = this.validate();
            if (missing.length > 0) {
                Swal.fire({
                    title: 'Belum lengkap',
                    html: 'Mohon lengkapi:<ul style="text-align:left;margin-top:8px">'
                        + missing.map(m => `<li style="padding:2px 0">• ${m}</li>`).join('') + '</ul>',
                    icon: 'warning',
                    confirmButtonColor: '#7c3aed',
                });
                return;
            }

            this.isLoading = true;
            this.isDone = false;
            this.hasWarning = false;
            this.warningMsg = '';
            this.steps = this.stepLabels[this.type].map(s => ({ ...s, status: 'pending' }));
            this.currentStep = 'Menghubungkan ke AI...';

            const payload = this.getPayload();
            const fd = new FormData();
            Object.entries(payload).forEach(([k, v]) => fd.append(k, v));

            // Animasi step progress (paralel dengan fetch)
            const simulateSteps = async () => {
                for (let i = 0; i < this.steps.length; i++) {
                    this.steps[i].status = 'loading';
                    this.currentStep = 'Menyusun: ' + this.steps[i].label + '...';
                    await new Promise(r => setTimeout(r, 900 + Math.random() * 600));
                    this.steps[i].status = 'done';
                }
            };

            try {
                const [, res] = await Promise.all([
                    simulateSteps(),
                    fetch('../api/rpp_ai.php', { method: 'POST', body: fd })
                ]);
                this.steps.forEach(s => s.status = 'done');

                const data = await res.json();
                if (data.status !== 'success') throw new Error(data.message ?? 'Terjadi kesalahan AI.');

                this.currentStep = 'Selesai!';
                this.isLoading = false;
                this.isDone = true;

                // Warning token terpotong
                if (data.warning) {
                    this.hasWarning = true;
                    this.warningMsg = data.warning;
                }

                // Set waktu
                this.times.pendahuluan = data.data.waktu_pendahuluan || '';
                this.times.inti = data.data.waktu_inti || '';
                this.times.penutup = data.data.waktu_penutup || '';

                document.querySelector('input[name=waktu_pendahuluan]').value = this.times.pendahuluan;
                document.querySelector('input[name=waktu_inti]').value = this.times.inti;
                document.querySelector('input[name=waktu_penutup]').value = this.times.penutup;

                // Tunggu Alpine render semua template (x-if, x-show) sebelum isi field
                await this.$nextTick();
                await new Promise(r => requestAnimationFrame(() => setTimeout(r, 150)));

                console.log('[RPP] AI response keys:', Object.keys(data.data));
                console.log('[RPP] AI response data:', data.data);

                await this.fillAllFields(data.data);

            } catch (e) {
                this.isLoading = false;
                this.steps.forEach(s => { if (s.status !== 'done') s.status = 'error'; });
                Swal.fire({ title: 'Gagal', text: e.message, icon: 'error', confirmButtonColor: '#7c3aed' });
            }
        },

        // ── Isi semua textarea dari respons AI (CP TIDAK diisi — manual) ──
        async fillAllFields(ai) {
            const map = this.type === 'Kurmer'
                ? [
                    ['praktik_pedagogis', ai.praktik_pedagogis],
                    ['dimensi_dpl', ai.dimensi_dpl],
                    ['tujuan', ai.tujuan],
                    ['pendahuluan', ai.pendahuluan],
                    ['inti', ai.inti],
                    ['penutup', ai.penutup],
                    ['penilaian', ai.penilaian],
                ]
                : [
                    ['praktik_pedagogis', ai.praktik_pedagogis],
                    ['dimensi_dpl', ai.dimensi_dpl],
                    ['tujuan', ai.tujuan],
                    ['kesiapan', ai.kesiapan],
                    ['pendahuluan', ai.pendahuluan],
                    ['pedatti_dalami', ai.dalami],
                    ['pedatti_terapkan', ai.terapkan],
                    ['pedatti_tularkan', ai.tularkan],
                    ['penutup', ai.penutup],
                    ['asesmen_awal', ai.asesmen_awal],
                    ['asesmen_proses', ai.asesmen_proses],
                    ['asesmen_akhir', ai.asesmen_akhir],
                ];

            let skipped = [];
            for (let [name, text] of map) {
                if (!text) {
                    skipped.push(name + ' (no data)');
                    continue;
                }
                
                // Format if AI returned Array instead of String
                if (Array.isArray(text)) {
                    text = text.map(item => typeof item === 'string' ? item : JSON.stringify(item)).join('\n');
                } else if (typeof text === 'object') {
                    text = JSON.stringify(text, null, 2);
                } else {
                    text = String(text);
                }

                const el = document.querySelector(`textarea[name="${name}"]`);
                if (!el) {
                    skipped.push(name + ' (no element)');
                    continue;
                }
                await this.typeWrite(el, text);
            }
            if (skipped.length > 0) {
                console.warn('[RPP] Skipped fields:', skipped);
            }
        },

        // ── Efek typewriter ──
        typeWrite(el, text) {
            return new Promise(resolve => {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.classList.add('ai-glow');
                el.value = '';
                let i = 0;
                const speed = text.length > 600 ? 4 : 12;
                const timer = setInterval(() => {
                    el.value += text.charAt(i++);
                    el.scrollTop = el.scrollHeight;
                    if (i >= text.length) {
                        clearInterval(timer);
                        el.classList.remove('ai-glow');
                        resolve();
                    }
                }, speed);
            });
        },

        // ── Regenerate satu section ──
        async regenSection(sectionKey) {
            const el = document.querySelector(`textarea[name="${sectionKey}"]`);
            if (!el) return;

            const btn = document.querySelector(`[data-regen="${sectionKey}"]`);
            if (btn) btn.disabled = true;

            const payload = this.getPayload();
            payload.action = 'regen_section';
            payload.section = sectionKey;

            const fd = new FormData();
            Object.entries(payload).forEach(([k, v]) => fd.append(k, v));

            try {
                const res = await fetch('../api/rpp_ai.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status !== 'success') throw new Error(data.message ?? 'Gagal regenerate.');
                await this.typeWrite(el, data.text);
                if (data.warning) {
                    Swal.fire({ title: 'Perhatian', text: data.warning, icon: 'warning', confirmButtonColor: '#7c3aed' });
                }
            } catch (e) {
                Swal.fire({ title: 'Gagal', text: e.message, icon: 'error', confirmButtonColor: '#7c3aed' });
            } finally {
                if (btn) btn.disabled = false;
            }
        },

        // ── Submit form ──
        submitForm() {
            if (!this.isDone) {
                Swal.fire({ title: 'Perhatian', text: 'Generate RPP dengan AI terlebih dahulu.', icon: 'info', confirmButtonColor: '#7c3aed' });
                return;
            }
            document.getElementById('rppForm').submit();
        }
    }));
});
</script>

<!-- ═══════════════════════════════════════════════════════════════════════════
     ALPINE.JS ROOT — RPP Wizard
     ═══════════════════════════════════════════════════════════════════════════ -->
<div x-data="rppWizard">

    <!-- ══════════════════════════════════════════════════════════════════════
         LOADING OVERLAY — Animasi AI sedang bekerja
         ══════════════════════════════════════════════════════════════════════ -->
    <div x-show="isLoading" x-cloak class="fixed inset-0 z-[200] bg-black/85 backdrop-blur-md flex items-center justify-center p-6" style="display:none;">
        <div class="w-full max-w-sm">
            <!-- Orb animasi -->
            <div class="relative w-28 h-28 mx-auto mb-8">
                <div class="absolute inset-0 rounded-full border-2 border-purple-500/20 animate-[spin_8s_linear_infinite]"></div>
                <div class="absolute inset-3 rounded-full border border-dashed border-violet-400/30 animate-[spin_5s_linear_infinite_reverse]"></div>
                <div class="absolute inset-0 rounded-full" style="animation:pulse-ring 1.5s ease-out infinite">
                    <div class="absolute inset-0 rounded-full border border-purple-500/30"></div>
                </div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <i class="fas fa-wand-magic-sparkles text-4xl text-violet-400"></i>
                </div>
                <div class="absolute inset-0 overflow-hidden rounded-full">
                    <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-violet-400 to-transparent animate-[scanner-move_2s_ease-in-out_infinite]"></div>
                </div>
            </div>
            <p class="text-center text-sm text-slate-300 mb-6 min-h-[20px]" x-text="currentStep"></p>
            <div class="space-y-2.5">
                <template x-for="step in steps" :key="step.key">
                    <div class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all"
                         :class="{'bg-white/5': step.status==='loading', 'opacity-35': step.status==='pending'}">
                        <div class="w-5 h-5 flex-shrink-0 flex items-center justify-center">
                            <template x-if="step.status==='pending'"><div class="w-1.5 h-1.5 rounded-full bg-slate-600"></div></template>
                            <template x-if="step.status==='loading'"><i class="fas fa-spinner fa-spin text-violet-400 text-xs"></i></template>
                            <template x-if="step.status==='done'"><i class="fas fa-check text-emerald-400 text-xs step-check"></i></template>
                            <template x-if="step.status==='error'"><i class="fas fa-times text-red-400 text-xs"></i></template>
                        </div>
                        <span class="text-sm"
                              :class="{'text-violet-300 font-semibold':step.status==='loading', 'text-emerald-400':step.status==='done', 'text-slate-500':step.status==='pending', 'text-red-400':step.status==='error'}"
                              x-text="step.label"></span>
                    </div>
                </template>
            </div>
            <div class="flex justify-center gap-2 mt-8">
                <div class="w-1.5 h-1.5 rounded-full bg-violet-400" style="animation:float-dot 1.2s ease-in-out infinite;animation-delay:0s"></div>
                <div class="w-1.5 h-1.5 rounded-full bg-violet-400" style="animation:float-dot 1.2s ease-in-out infinite;animation-delay:.3s"></div>
                <div class="w-1.5 h-1.5 rounded-full bg-violet-400" style="animation:float-dot 1.2s ease-in-out infinite;animation-delay:.6s"></div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         FORM UTAMA
         ══════════════════════════════════════════════════════════════════════ -->
    <form id="rppForm" action="rpp_proses.php" method="POST" novalidate>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- ──────────────────────────────────────────────────────────────
                 SIDEBAR IDENTITAS (Kolom Kiri)
                 ────────────────────────────────────────────────────────────── -->
            <div class="lg:col-span-1">
                <div class="glass rounded-2xl p-6 border border-white/5 sticky top-6 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-blue-400 flex items-center gap-2">
                        <i class="fas fa-id-card"></i> Identitas RPP
                    </h3>

                    <!-- Tipe Kurikulum -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Tipe Kurikulum</label>
                        <div class="grid grid-cols-2 gap-2 bg-black/20 p-1 rounded-xl">
                            <label class="cursor-pointer">
                                <input type="radio" name="kurikulum_type" value="Kurmer" x-model="type" class="hidden peer">
                                <div class="px-3 py-2 text-center rounded-lg text-xs font-bold uppercase transition-all peer-checked:bg-blue-600 peer-checked:text-white text-slate-500">Kurmer</div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="kurikulum_type" value="KBC" x-model="type" class="hidden peer">
                                <div class="px-3 py-2 text-center rounded-lg text-xs font-bold uppercase transition-all peer-checked:bg-purple-600 peer-checked:text-white text-slate-500">KBC</div>
                            </label>
                        </div>
                    </div>

                    <!-- Mata Pelajaran -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Mata Pelajaran <span class="text-red-400">*</span></label>
                        <select name="id_mapel" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                            <option value="0">Pilih Mapel...</option>
                            <?php foreach ($mapel_list as $m): ?>
                                <option value="<?= $m['id_mapel'] ?>"><?= clean($m['nama_mapel']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Kelas -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Kelas <span class="text-red-400">*</span></label>
                        <select name="id_kelas" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                            <option value="0">Pilih Kelas...</option>
                            <?php foreach ($kelas_list as $k): ?>
                                <option value="<?= $k['id_kelas'] ?>"><?= clean($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Semester & Alokasi -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Semester <span class="text-red-400">*</span></label>
                            <select name="semester" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                                <option>Ganjil</option>
                                <option>Genap</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Alokasi <span class="text-red-400">*</span></label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="alokasi_jp" required min="1" max="20" placeholder="JP" class="w-1/3 bg-slate-900 border border-white/10 rounded-xl px-2 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                                <span class="text-[10px] text-slate-600">×</span>
                                <input type="number" name="menit_per_jp" required min="1" max="120" placeholder="Min" class="w-1/2 bg-slate-900 border border-white/10 rounded-xl px-2 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- Materi Pokok -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Materi Pokok <span class="text-red-400">*</span></label>
                        <input type="text" name="judul_rpp" required placeholder="Contoh: Ekosistem Laut" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                    </div>

                    <!-- Dropdown Manual: Praktik Pedagogis (Renamed to Model Pembelajaran) -->
                    <div class="relative" @click.away="pedagogisDropdownOpen = false">
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Model Pembelajaran <span class="text-xs text-emerald-400 font-normal normal-case">(Maks 2)</span> <span class="text-red-400">*</span></label>
                        <button type="button" @click="pedagogisDropdownOpen = !pedagogisDropdownOpen" class="w-full flex items-center justify-between bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:outline-none hover:border-emerald-500/50 transition-colors">
                            <span x-text="pedagogisSelected.length ? pedagogisSelected.length + ' Model Dipilih' : 'Pilih Model Pembelajaran...'" :class="pedagogisSelected.length ? 'text-white font-bold' : 'text-slate-500'"></span>
                            <i class="fas fa-chevron-down text-slate-500 text-xs transition-transform" :class="pedagogisDropdownOpen ? 'rotate-180' : ''"></i>
                        </button>
                        
                        <div x-show="pedagogisDropdownOpen" x-transition class="absolute z-[100] w-full left-0 mt-2 bg-slate-800 border border-white/10 rounded-xl shadow-2xl p-2 max-h-48 overflow-y-auto" style="display: none;">
                            <template x-for="item in pedagogisList" :key="item">
                                <label class="flex items-start gap-3 p-2 hover:bg-white/5 rounded-lg cursor-pointer transition-colors group">
                                    <input type="checkbox" :value="item" :checked="pedagogisSelected.includes(item)" @change="togglePedagogis(item)" class="mt-1 w-3.5 h-3.5 flex-shrink-0 cursor-pointer">
                                    <span class="text-[11px] text-slate-300 group-hover:text-white leading-relaxed" x-text="item"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Dropdown Manual: DPL -->
                    <div class="relative" @click.away="dplDropdownOpen = false">
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Dimensi Profil Pelajar <span class="text-xs text-fuchsia-400 font-normal normal-case">(Maks 3)</span> <span class="text-red-400">*</span></label>
                        <button type="button" @click="dplDropdownOpen = !dplDropdownOpen" class="w-full flex items-center justify-between bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:outline-none hover:border-fuchsia-500/50 transition-colors">
                            <span x-text="dplSelected.length ? dplSelected.length + ' DPL Dipilih' : 'Pilih DPL...'" :class="dplSelected.length ? 'text-white font-bold' : 'text-slate-500'"></span>
                            <i class="fas fa-chevron-down text-slate-500 text-xs transition-transform" :class="dplDropdownOpen ? 'rotate-180' : ''"></i>
                        </button>
                        
                        <div x-show="dplDropdownOpen" x-transition class="absolute z-[100] w-full left-0 mt-2 bg-slate-800 border border-white/10 rounded-xl shadow-2xl p-2 max-h-48 overflow-y-auto" style="display: none;">
                            <template x-for="item in dplList" :key="item">
                                <label class="flex items-start gap-3 p-2 hover:bg-white/5 rounded-lg cursor-pointer transition-colors group">
                                    <input type="checkbox" :value="item" :checked="dplSelected.includes(item)" @change="toggleDpl(item)" class="mt-1 w-3.5 h-3.5 flex-shrink-0 cursor-pointer">
                                    <span class="text-[11px] text-slate-300 group-hover:text-white leading-relaxed" x-text="item"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Dropdown Manual: Panca Cinta (KBC) -->
                    <div class="relative" x-show="type === 'KBC'" @click.away="pcDropdownOpen = false" style="display: none;">
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Nilai Panca Cinta <span class="text-xs text-purple-400 font-normal normal-case">(1-3)</span> <span class="text-red-400">*</span></label>
                        <button type="button" @click="pcDropdownOpen = !pcDropdownOpen" class="w-full flex items-center justify-between bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:outline-none hover:border-purple-500/50 transition-colors">
                            <span x-text="pcSelected.length ? pcSelected.length + ' Nilai Dipilih' : 'Pilih Panca Cinta...'" :class="pcSelected.length ? 'text-white font-bold' : 'text-slate-500'"></span>
                            <i class="fas fa-chevron-down text-slate-500 text-xs transition-transform" :class="pcDropdownOpen ? 'rotate-180' : ''"></i>
                        </button>
                        
                        <div x-show="pcDropdownOpen" x-transition class="absolute z-[100] w-full left-0 mt-2 bg-slate-800 border border-white/10 rounded-xl shadow-2xl p-2 max-h-48 overflow-y-auto" style="display: none;">
                            <template x-for="item in pcList" :key="item">
                                <label class="flex items-start gap-3 p-2 hover:bg-white/5 rounded-lg cursor-pointer transition-colors group">
                                    <input type="checkbox" :value="item" :checked="pcSelected.includes(item)" @change="togglePc(item)" class="mt-1 w-3.5 h-3.5 flex-shrink-0 cursor-pointer">
                                    <span class="text-[11px] text-slate-300 group-hover:text-white leading-relaxed" x-text="item"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ──────────────────────────────────────────────────────────────
                 KONTEN UTAMA (Kolom Kanan — 2 kolom)
                 ────────────────────────────────────────────────────────────── -->
            <div class="lg:col-span-2 space-y-5">



                <!-- ══════════════════════════════════════════════════════════
                     CP — Input Manual oleh Guru (patokan TP & seluruh RPP)
                     ══════════════════════════════════════════════════════════ -->
                <div class="glass rounded-2xl p-6 border border-amber-500/20 bg-gradient-to-br from-amber-500/[0.03] to-transparent">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-widest text-amber-400 flex items-center gap-2">
                                <i class="fas fa-book-open"></i> Capaian Pembelajaran (CP) <span class="text-red-400 text-xs">*</span>
                            </h3>
                            <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                Tulis CP secara manual. AI akan menggunakan CP ini sebagai <strong class="text-amber-400/80">patokan utama</strong> untuk menyusun RPP.
                            </p>
                        </div>
                        <span class="text-[9px] px-2 py-1 rounded-md bg-amber-500/10 text-amber-400 font-bold uppercase tracking-wider whitespace-nowrap">Input Guru</span>
                    </div>
                    <textarea name="cp" rows="4" required placeholder="Tuliskan Capaian Pembelajaran (CP) dari kurikulum nasional yang sesuai dengan fase, mapel, dan materi ini..."
                        class="w-full bg-slate-900/50 border border-amber-500/20 rounded-xl px-4 py-3 text-sm focus:border-amber-500 focus:outline-none resize-y placeholder:text-slate-600"></textarea>
                </div>

                <!-- ══════════════════════════════════════════════════════════
                     TOMBOL GENERATE AI
                     ══════════════════════════════════════════════════════════ -->
                <div x-show="!isDone">
                    <button type="button" @click="generateRPP()" :disabled="isLoading"
                        class="w-full relative overflow-hidden group bg-gradient-to-r from-violet-600 via-purple-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white rounded-2xl py-5 font-black italic uppercase tracking-widest text-sm shadow-2xl shadow-violet-500/25 transition-all hover:scale-[1.01] active:scale-[0.98] disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:scale-100">
                        <span class="relative z-10 flex items-center justify-center gap-3">
                            <i class="fas fa-wand-magic-sparkles text-lg"></i>
                            Generate RPP dengan AI
                            <i class="fas fa-arrow-right text-xs opacity-50 group-hover:translate-x-1 transition-transform"></i>
                        </span>
                        <div class="absolute inset-0 -translate-x-full group-hover:translate-x-full bg-gradient-to-r from-transparent via-white/10 to-transparent transition-transform duration-700 ease-in-out pointer-events-none"></div>
                    </button>
                    <p class="text-center text-[11px] text-slate-600 mt-2">AI akan menyusun TP, model, praktik pedagogis, DPL, langkah pembelajaran, dan asesmen berdasarkan CP yang Anda tulis.</p>
                </div>

                <!-- ══════════════════════════════════════════════════════════
                     HASIL RPP (muncul setelah generate)
                     ══════════════════════════════════════════════════════════ -->
                <div x-show="isDone" x-cloak style="display:none;" class="space-y-5">

                    <!-- Banner Sukses -->
                    <div class="flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl px-5 py-4 result-section">
                        <i class="fas fa-circle-check text-emerald-400 text-xl flex-shrink-0"></i>
                        <div>
                            <p class="text-sm font-bold text-emerald-400">RPP berhasil disusun!</p>
                            <p class="text-[11px] text-slate-500">Periksa dan sesuaikan jika perlu. Gunakan tombol <span class="text-violet-400">↻ Susun Ulang</span> untuk memperbaiki bagian tertentu.</p>
                        </div>
                        <button type="button" @click="isDone = false"
                            class="ml-auto flex-shrink-0 text-[10px] text-slate-500 hover:text-red-400 transition-colors px-3 py-1.5 rounded-lg border border-white/10 hover:border-red-500/30 whitespace-nowrap">
                            <i class="fas fa-redo mr-1"></i> Ulang
                        </button>
                    </div>

                    <!-- Warning token terpotong -->
                    <div x-show="hasWarning" class="warning-banner">
                        <i class="fas fa-triangle-exclamation flex-shrink-0"></i>
                        <span x-text="warningMsg"></span>
                    </div>

                    <!-- ── Kartu: Praktik Pedagogis, DPL ── -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Praktik Pedagogis (Renamed to Model Pembelajaran) -->
                        <div class="glass rounded-2xl p-5 border border-white/5 result-section" style="animation-delay:.05s">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-[11px] font-bold uppercase tracking-widest text-emerald-400 flex items-center gap-2">
                                    <i class="fas fa-chalkboard-teacher"></i> Model Pembelajaran
                                    <span class="text-[8px] font-normal bg-emerald-500/10 text-emerald-400 px-1.5 py-0.5 rounded">1-2</span>
                                </h3>
                                <button type="button" class="regen-btn" data-regen="praktik_pedagogis" @click="regenSection('praktik_pedagogis')"><i class="fas fa-rotate-right"></i></button>
                            </div>
                            <textarea name="praktik_pedagogis" rows="3" placeholder="AI akan menjabarkan model..."
                                class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-xs focus:border-emerald-500 focus:outline-none resize-y"></textarea>
                        </div>

                        <!-- Dimensi DPL -->
                        <div class="glass rounded-2xl p-5 border border-white/5 result-section" style="animation-delay:.1s">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-[11px] font-bold uppercase tracking-widest text-fuchsia-400 flex items-center gap-2">
                                    <i class="fas fa-user-graduate"></i> DPL / P3
                                    <span class="text-[8px] font-normal bg-fuchsia-500/10 text-fuchsia-400 px-1.5 py-0.5 rounded">maks 3</span>
                                </h3>
                                <button type="button" class="regen-btn" data-regen="dimensi_dpl" @click="regenSection('dimensi_dpl')"><i class="fas fa-rotate-right"></i></button>
                            </div>
                            <textarea name="dimensi_dpl" rows="3" placeholder="AI akan memilihkan dimensi profil lulusan..."
                                class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-xs focus:border-fuchsia-500 focus:outline-none resize-y"></textarea>
                        </div>
                    </div>

                    <!-- ── Tujuan Pembelajaran (TP) ── -->
                    <div class="glass rounded-2xl p-6 border border-white/5 result-section" style="animation-delay:.1s">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-bold uppercase tracking-widest text-emerald-400 flex items-center gap-2">
                                <i class="fas fa-bullseye"></i> Tujuan Pembelajaran (TP)
                                <span class="text-[10px] font-normal bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded-md">Dari CP</span>
                            </h3>
                            <button type="button" class="regen-btn" data-regen="tujuan" @click="regenSection('tujuan')">
                                <i class="fas fa-rotate-right"></i> Susun Ulang
                            </button>
                        </div>
                        <textarea name="tujuan" rows="4" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none"></textarea>
                    </div>

                    <!-- ══════════════════════════════════════════════════════
                         KURMER — Langkah Pembelajaran & Penilaian
                         ══════════════════════════════════════════════════════ -->
                    <template x-if="type === 'Kurmer'">
                        <div class="space-y-5">
                            <div class="glass rounded-2xl p-6 border border-white/5 result-section" style="animation-delay:.2s">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-bold uppercase tracking-widest text-blue-400">Langkah Pembelajaran</h3>
                                    <div class="flex gap-2">
                                        <span x-show="times.pendahuluan" x-text="'P: ' + times.pendahuluan" class="text-[9px] bg-blue-500/10 text-blue-400 px-2 py-1 rounded-md font-bold"></span>
                                        <span x-show="times.inti" x-text="'I: ' + times.inti" class="text-[9px] bg-indigo-500/10 text-indigo-400 px-2 py-1 rounded-md font-bold"></span>
                                        <span x-show="times.penutup" x-text="'Pn: ' + times.penutup" class="text-[9px] bg-sky-500/10 text-sky-400 px-2 py-1 rounded-md font-bold"></span>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500">Pendahuluan</label>
                                            <button type="button" class="regen-btn" data-regen="pendahuluan" @click="regenSection('pendahuluan')"><i class="fas fa-rotate-right"></i> Susun Ulang</button>
                                        </div>
                                        <textarea name="pendahuluan" rows="4" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none"></textarea>
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500">Inti</label>
                                            <button type="button" class="regen-btn" data-regen="inti" @click="regenSection('inti')"><i class="fas fa-rotate-right"></i> Susun Ulang</button>
                                        </div>
                                        <textarea name="inti" rows="7" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none"></textarea>
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500">Penutup</label>
                                            <button type="button" class="regen-btn" data-regen="penutup" @click="regenSection('penutup')"><i class="fas fa-rotate-right"></i> Susun Ulang</button>
                                        </div>
                                        <textarea name="penutup" rows="4" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="glass rounded-2xl p-6 border border-white/5 result-section" style="animation-delay:.3s">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-sm font-bold uppercase tracking-widest text-amber-400">Penilaian (Asesmen)</h3>
                                    <button type="button" class="regen-btn" data-regen="penilaian" @click="regenSection('penilaian')"><i class="fas fa-rotate-right"></i> Susun Ulang</button>
                                </div>
                                <textarea name="penilaian" rows="4" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none"></textarea>
                            </div>
                        </div>
                    </template>

                    <!-- ══════════════════════════════════════════════════════
                         KBC — PEDATTI & Asesmen
                         ══════════════════════════════════════════════════════ -->
                    <template x-if="type === 'KBC'">
                        <div class="space-y-5">
                            <!-- Kesiapan -->
                            <div class="glass rounded-2xl p-5 border border-white/5 result-section" style="animation-delay:.2s">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-[10px] font-bold uppercase tracking-widest text-sky-400">Identifikasi Kesiapan Murid</label>
                                    <button type="button" class="regen-btn" data-regen="kesiapan" @click="regenSection('kesiapan')"><i class="fas fa-rotate-right"></i> Ulang</button>
                                </div>
                                <textarea name="kesiapan" rows="5" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none"></textarea>
                            </div>

                            <!-- PEDATTI (Pendahuluan + Dalami + Terapkan + Tularkan + Penutup) -->
                            <div class="glass rounded-2xl p-6 border border-white/5 result-section" style="animation-delay:.3s">
                                <h3 class="text-sm font-bold uppercase tracking-widest text-pink-400 mb-4 flex items-center justify-between">
                                    <div class="flex items-center gap-2"><i class="fas fa-shoe-prints"></i> Pengalaman Belajar (PEDATTI)</div>
                                    <div class="flex gap-2">
                                        <span x-show="times.pendahuluan" x-text="'P: ' + times.pendahuluan" class="text-[9px] bg-pink-500/10 text-pink-400 px-2 py-1 rounded-md font-bold"></span>
                                        <span x-show="times.inti" x-text="'Inti: ' + times.inti" class="text-[9px] bg-pink-500/10 text-pink-400 px-2 py-1 rounded-md font-bold"></span>
                                        <span x-show="times.penutup" x-text="'Pn: ' + times.penutup" class="text-[9px] bg-pink-500/10 text-pink-400 px-2 py-1 rounded-md font-bold"></span>
                                    </div>
                                </h3>
                                <div class="space-y-4">
                                    <!-- Pendahuluan KBC -->
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500">Pendahuluan</label>
                                            <button type="button" class="regen-btn" data-regen="pendahuluan" @click="regenSection('pendahuluan')"><i class="fas fa-rotate-right"></i> Susun Ulang</button>
                                        </div>
                                        <textarea name="pendahuluan" rows="4" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none"></textarea>
                                    </div>
                                    <!-- Dalami -->
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 italic">Dalami (Inti 1)</label>
                                            <button type="button" class="regen-btn" data-regen="pedatti_dalami" @click="regenSection('pedatti_dalami')"><i class="fas fa-rotate-right"></i> Susun Ulang</button>
                                        </div>
                                        <textarea name="pedatti_dalami" rows="4" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none"></textarea>
                                    </div>
                                    <!-- Terapkan -->
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 italic">Terapkan (Inti 2)</label>
                                            <button type="button" class="regen-btn" data-regen="pedatti_terapkan" @click="regenSection('pedatti_terapkan')"><i class="fas fa-rotate-right"></i> Susun Ulang</button>
                                        </div>
                                        <textarea name="pedatti_terapkan" rows="4" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none"></textarea>
                                    </div>
                                    <!-- Tularkan -->
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 italic">Tularkan (Inti 3)</label>
                                            <button type="button" class="regen-btn" data-regen="pedatti_tularkan" @click="regenSection('pedatti_tularkan')"><i class="fas fa-rotate-right"></i> Susun Ulang</button>
                                        </div>
                                        <textarea name="pedatti_tularkan" rows="4" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none"></textarea>
                                    </div>
                                    <!-- Penutup KBC -->
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500">Penutup</label>
                                            <button type="button" class="regen-btn" data-regen="penutup" @click="regenSection('penutup')"><i class="fas fa-rotate-right"></i> Susun Ulang</button>
                                        </div>
                                        <textarea name="penutup" rows="4" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Asesmen KBC -->
                            <div class="glass rounded-2xl p-6 border border-white/5 result-section" style="animation-delay:.35s">
                                <h3 class="text-sm font-bold uppercase tracking-widest text-amber-400 mb-4">Asesmen KBC</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Asesmen Awal</label>
                                            <button type="button" class="regen-btn" data-regen="asesmen_awal" @click="regenSection('asesmen_awal')"><i class="fas fa-rotate-right"></i> Ulang</button>
                                        </div>
                                        <textarea name="asesmen_awal" rows="3" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-2 text-xs focus:outline-none"></textarea>
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Asesmen Proses</label>
                                            <button type="button" class="regen-btn" data-regen="asesmen_proses" @click="regenSection('asesmen_proses')"><i class="fas fa-rotate-right"></i> Ulang</button>
                                        </div>
                                        <textarea name="asesmen_proses" rows="3" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-2 text-xs focus:outline-none"></textarea>
                                    </div>
                                    <div class="md:col-span-2">
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Asesmen Akhir (Sumatif)</label>
                                            <button type="button" class="regen-btn" data-regen="asesmen_akhir" @click="regenSection('asesmen_akhir')"><i class="fas fa-rotate-right"></i> Ulang</button>
                                        </div>
                                        <textarea name="asesmen_akhir" rows="3" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-2 text-xs focus:outline-none"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- ══════════════════════════════════════════════════════
                         TOMBOL SIMPAN
                         ══════════════════════════════════════════════════════ -->
                    <div class="flex items-center justify-between gap-4 pt-2 result-section" style="animation-delay:.4s">
                        <a href="rpp.php" class="px-6 py-3 rounded-xl text-sm font-bold text-slate-400 hover:text-white transition-all">Batal</a>
                        <button type="button" @click="submitForm()"
                            class="flex items-center gap-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white px-10 py-3.5 rounded-xl text-sm font-black italic uppercase tracking-widest shadow-xl shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
                            <i class="fas fa-save"></i> Simpan &amp; Generate File
                        </button>
                    </div>

                </div><!-- /isDone -->
            </div><!-- /lg:col-span-2 -->
        </div><!-- /grid -->

        <input type="hidden" name="simpan" value="1">
        <input type="hidden" name="waktu_pendahuluan">
        <input type="hidden" name="waktu_inti">
        <input type="hidden" name="waktu_penutup">
    </form>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>