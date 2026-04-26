<?php
$page_title = 'Generate Soal AI';
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
cek_fitur('ai_soal');
$id_guru = $_SESSION['user_id'];

$mapel_stmt = $pdo->prepare("SELECT DISTINCT m.* FROM tbl_jadwal j JOIN tbl_mapel m ON j.id_mapel=m.id_mapel WHERE j.id_guru=? ORDER BY m.nama_mapel");
$mapel_stmt->execute([$id_guru]);
$mapel_list = $mapel_stmt->fetchAll();

$kelas_stmt = $pdo->prepare("SELECT DISTINCT k.* FROM tbl_jadwal j JOIN tbl_kelas k ON j.id_kelas=k.id_kelas WHERE j.id_guru=? ORDER BY k.nama_kelas");
$kelas_stmt->execute([$id_guru]);
$kelas_list = $kelas_stmt->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>
<style>
@keyframes scanner-move { 0%,100% { transform: translateY(0); } 50% { transform: translateY(220px); } }
@keyframes pulse-ring { 0% { transform: scale(0.95); opacity: 1; } 100% { transform: scale(1.3); opacity: 0; } }
@keyframes float-dot { 0%,100% { transform: translateY(0px); opacity: 0.4; } 50% { transform: translateY(-8px); opacity: 1; } }
@keyframes fade-in-up { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
.ai-glow { border-color: rgba(139,92,246,0.6) !important; box-shadow: 0 0 0 3px rgba(139,92,246,0.15) !important; }
.result-section { animation: fade-in-up 0.5s ease forwards; opacity: 0; }
</style>

<div class="mb-6 flex items-center gap-4">
    <a href="generator_soal.php" class="w-10 h-10 rounded-xl glass flex items-center justify-center text-slate-400 hover:text-white transition-all"><i class="fas fa-arrow-left"></i></a>
    <div>
        <h2 class="text-xl font-black italic uppercase tracking-widest">Generate Soal Baru ✦</h2>
        <p class="text-xs text-slate-400">Konfigurasi parameter, lalu biarkan AI menyusun soal dalam 1 klik.</p>
    </div>
</div>

<div x-data="soalGenerator()">

    <!-- LOADING OVERLAY -->
    <div x-show="isLoading" x-cloak class="fixed inset-0 z-[200] bg-black/85 backdrop-blur-md flex items-center justify-center p-6" style="display:none;">
        <div class="w-full max-w-sm">
            <div class="relative w-28 h-28 mx-auto mb-8">
                <div class="absolute inset-0 rounded-full border-2 border-purple-500/20 animate-[spin_8s_linear_infinite]"></div>
                <div class="absolute inset-3 rounded-full border border-dashed border-violet-400/30 animate-[spin_5s_linear_infinite_reverse]"></div>
                <div class="absolute inset-0 rounded-full" style="animation: pulse-ring 1.5s ease-out infinite;"><div class="absolute inset-0 rounded-full border border-purple-500/30"></div></div>
                <div class="absolute inset-0 flex items-center justify-center"><i class="fas fa-wand-magic-sparkles text-4xl text-violet-400"></i></div>
                <div class="absolute inset-0 overflow-hidden rounded-full"><div class="w-full h-0.5 bg-gradient-to-r from-transparent via-violet-400 to-transparent animate-[scanner-move_2s_ease-in-out_infinite]"></div></div>
            </div>
            <p class="text-center text-sm text-slate-300 mb-4" x-text="loadingMsg"></p>
            <div class="flex justify-center gap-2 mt-6">
                <div class="w-1.5 h-1.5 rounded-full bg-violet-400" style="animation: float-dot 1.2s ease-in-out infinite;"></div>
                <div class="w-1.5 h-1.5 rounded-full bg-violet-400" style="animation: float-dot 1.2s ease-in-out infinite; animation-delay: 0.3s;"></div>
                <div class="w-1.5 h-1.5 rounded-full bg-violet-400" style="animation: float-dot 1.2s ease-in-out infinite; animation-delay: 0.6s;"></div>
            </div>
        </div>
    </div>

    <!-- FORM CONFIG -->
    <div x-show="!isDone" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- SIDEBAR CONFIG -->
        <div class="lg:col-span-1">
            <div class="glass rounded-2xl p-6 border border-white/5 sticky top-6 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-widest text-violet-400 flex items-center gap-2"><i class="fas fa-sliders-h"></i> Konfigurasi Soal</h3>

                <!-- Nama Ujian -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Nama Ujian <span class="text-red-400">*</span></label>
                    <input type="text" x-model="config.nama_ujian" placeholder="Contoh: UTS Matematika Gasal 2025" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-violet-500 focus:outline-none">
                </div>

                <!-- Tipe & Jenis Ujian -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Tipe Ujian</label>
                        <select x-model="config.tipe_ujian" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2.5 text-sm focus:border-violet-500 focus:outline-none">
                            <option>Tulis</option><option>Lisan</option><option>Praktik</option><option>Project</option><option>Proyek</option><option>Portofolio</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Jenis</label>
                        <select x-model="config.jenis_ujian" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2.5 text-sm focus:border-violet-500 focus:outline-none">
                            <option>UH</option><option>UTS</option><option>UAS</option><option>PAT</option><option>PTS</option><option>PAS</option><option>Lainnya</option>
                        </select>
                    </div>
                </div>

                <!-- Mapel & Kelas -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Mata Pelajaran <span class="text-red-400">*</span></label>
                    <select x-model="config.id_mapel" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-violet-500 focus:outline-none">
                        <option value="0">Pilih Mapel...</option>
                        <?php foreach ($mapel_list as $m): ?>
                        <option value="<?= $m['id_mapel'] ?>"><?= clean($m['nama_mapel']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Kelas <span class="text-red-400">*</span></label>
                    <select x-model="config.id_kelas" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-violet-500 focus:outline-none">
                        <option value="0">Pilih Kelas...</option>
                        <?php foreach ($kelas_list as $k): ?>
                        <option value="<?= $k['id_kelas'] ?>"><?= clean($k['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Semester & Tanggal & Durasi -->
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Semester</label>
                        <select x-model="config.semester" class="w-full bg-slate-900 border border-white/10 rounded-xl px-2 py-2.5 text-sm focus:border-violet-500 focus:outline-none">
                            <option>Ganjil</option><option>Genap</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Tanggal</label>
                        <input type="date" x-model="config.tanggal_ujian" class="w-full bg-slate-900 border border-white/10 rounded-xl px-2 py-2.5 text-sm focus:border-violet-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Durasi</label>
                        <div class="flex items-center gap-1">
                            <input type="number" x-model="config.durasi_menit" min="10" class="w-full bg-slate-900 border border-white/10 rounded-xl px-2 py-2.5 text-sm focus:border-violet-500 focus:outline-none">
                            <span class="text-[10px] text-slate-500">min</span>
                        </div>
                    </div>
                </div>

                <hr class="border-white/5">

                <!-- Jumlah Soal PG / Essay / Opsi -->
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Soal PG</label>
                        <input type="number" x-model="config.jml_pg" min="0" max="50" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2.5 text-sm focus:border-violet-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Soal Essay</label>
                        <input type="number" x-model="config.jml_essay" min="0" max="20" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2.5 text-sm focus:border-violet-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Opsi PG</label>
                        <select x-model="config.opsi_pg" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2.5 text-sm focus:border-violet-500 focus:outline-none">
                            <option value="3">3 (A-C)</option>
                            <option value="4">4 (A-D)</option>
                            <option value="5">5 (A-E)</option>
                        </select>
                    </div>
                </div>

                <!-- Tingkat & Berpikir -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Kesulitan</label>
                        <select x-model="config.tingkat" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2.5 text-sm focus:border-violet-500 focus:outline-none">
                            <option>Campuran</option><option>Mudah</option><option>Sedang</option><option>Sulit</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Berpikir</label>
                        <select x-model="config.berpikir" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2.5 text-sm focus:border-violet-500 focus:outline-none">
                            <option>Campuran</option><option>LOTS</option><option>HOTS</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="lg:col-span-2 space-y-5">
            <!-- Topik / Materi -->
            <div class="glass rounded-2xl p-6 border border-white/5">
                <h3 class="text-sm font-bold uppercase tracking-widest text-amber-400 flex items-center gap-2 mb-4"><i class="fas fa-book-open"></i> Topik & Capaian Pembelajaran</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Materi / Topik <span class="text-red-400">*</span></label>
                        <input type="text" x-model="config.topik" placeholder="Contoh: Persamaan Linear Satu Variabel" class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-amber-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Capaian Pembelajaran / CP <span class="text-slate-600">(opsional)</span></label>
                        <textarea x-model="config.cp" rows="3" placeholder="Contoh: Peserta didik mampu menganalisis dan menyelesaikan persamaan linear satu variabel..." class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-amber-500 focus:outline-none resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Petunjuk Pengerjaan <span class="text-slate-600">(opsional)</span></label>
                        <textarea x-model="config.petunjuk" rows="2" placeholder="Contoh: Kerjakan soal berikut dengan teliti. Pilih satu jawaban yang paling tepat..." class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-amber-500 focus:outline-none resize-none"></textarea>
                    </div>
                </div>
            </div>

            <!-- TOMBOL GENERATE -->
            <button type="button" @click="generate()" :disabled="isLoading" class="w-full relative overflow-hidden group bg-gradient-to-r from-violet-600 via-purple-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white rounded-2xl py-5 font-black italic uppercase tracking-widest text-sm shadow-2xl shadow-violet-500/25 transition-all hover:scale-[1.01] active:scale-[0.98] disabled:opacity-60 disabled:cursor-not-allowed">
                <span class="relative z-10 flex items-center justify-center gap-3">
                    <i class="fas fa-wand-magic-sparkles text-lg"></i>
                    Generate <span x-text="(parseInt(config.jml_pg)||0) + (parseInt(config.jml_essay)||0)"></span> Soal dengan AI
                    <i class="fas fa-arrow-right text-xs opacity-50 group-hover:translate-x-1 transition-transform"></i>
                </span>
                <div class="absolute inset-0 -translate-x-full group-hover:translate-x-full bg-gradient-to-r from-transparent via-white/10 to-transparent transition-transform duration-700 ease-in-out pointer-events-none"></div>
            </button>
            <p class="text-center text-[11px] text-slate-600">AI akan menyusun soal beserta kunci jawaban, pembahasan, dan kisi-kisi otomatis.</p>
        </div>
    </div>

    <!-- ═══════════ HASIL GENERATE ═══════════ -->
    <div x-show="isDone" x-cloak style="display:none;" class="space-y-5">
        <!-- Banner sukses -->
        <div class="flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl px-5 py-4 result-section">
            <i class="fas fa-circle-check text-emerald-400 text-xl flex-shrink-0"></i>
            <div class="flex-1">
                <p class="text-sm font-bold text-emerald-400">Soal berhasil digenerate!</p>
                <p class="text-[11px] text-slate-500">Review soal di bawah, lalu simpan ke Bank Soal & buat Paket Ujian.</p>
            </div>
            <button type="button" @click="reset()" class="flex-shrink-0 text-[10px] text-slate-500 hover:text-red-400 transition-colors px-3 py-1.5 rounded-lg border border-white/10 hover:border-red-500/30"><i class="fas fa-redo mr-1"></i>Ulang</button>
        </div>

        <!-- Config summary -->
        <div class="glass rounded-xl p-4 result-section" style="animation-delay:0.05s">
            <div class="flex flex-wrap gap-3 text-[10px]">
                <span class="px-2 py-1 rounded-full bg-violet-500/10 text-violet-400 font-bold" x-text="config.nama_ujian"></span>
                <span class="px-2 py-1 rounded-full bg-white/5 text-slate-400" x-text="config.tipe_ujian"></span>
                <span class="px-2 py-1 rounded-full bg-white/5 text-slate-400" x-text="config.jenis_ujian"></span>
                <span class="px-2 py-1 rounded-full bg-blue-500/10 text-blue-400" x-text="getMapelName()"></span>
                <span class="px-2 py-1 rounded-full bg-blue-500/10 text-blue-400" x-text="getKelasName()"></span>
                <span class="px-2 py-1 rounded-full bg-white/5 text-slate-400" x-text="config.durasi_menit + ' menit'"></span>
            </div>
        </div>

        <!-- Soal PG -->
        <template x-if="hasilSoal.soal_pg && hasilSoal.soal_pg.length > 0">
            <div class="glass rounded-2xl p-6 border border-white/5 result-section" style="animation-delay:0.1s">
                <h3 class="text-sm font-bold uppercase tracking-widest text-blue-400 mb-4 flex items-center gap-2">
                    <i class="fas fa-list-ol"></i> Soal Pilihan Ganda
                    <span class="text-[10px] font-normal bg-blue-500/10 text-blue-400 px-2 py-0.5 rounded-md" x-text="hasilSoal.soal_pg.length + ' soal'"></span>
                </h3>
                <div class="space-y-4">
                    <template x-for="(s, i) in hasilSoal.soal_pg" :key="i">
                        <div class="bg-black/20 rounded-xl p-4 border border-white/5">
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center text-sm font-bold" x-text="i+1"></span>
                                <div class="flex-1">
                                    <p class="text-sm font-medium mb-3" x-text="s.pertanyaan"></p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3">
                                        <template x-for="opt in getOpsiList(s)" :key="opt.key">
                                            <div class="text-xs px-3 py-2 rounded-lg" :class="s.jawaban === opt.key ? 'bg-emerald-500/20 text-emerald-400 font-semibold' : 'bg-white/5 text-slate-300'">
                                                <span class="font-bold mr-1" x-text="opt.key + '.'"></span> <span x-text="opt.val"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="text-[9px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400" x-text="'Jawaban: ' + s.jawaban"></span>
                                        <span class="text-[9px] px-2 py-0.5 rounded-full" :class="s.tingkat==='Mudah' ? 'bg-emerald-500/10 text-emerald-400' : (s.tingkat==='Sulit' ? 'bg-red-500/10 text-red-400' : 'bg-amber-500/10 text-amber-400')" x-text="s.tingkat"></span>
                                        <span class="text-[9px] px-2 py-0.5 rounded-full bg-violet-500/10 text-violet-400" x-text="s.taksonomi"></span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-2 italic" x-show="s.pembahasan"><i class="fas fa-lightbulb mr-1 text-amber-500"></i><span x-text="s.pembahasan"></span></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- Soal Essay -->
        <template x-if="hasilSoal.soal_essay && hasilSoal.soal_essay.length > 0">
            <div class="glass rounded-2xl p-6 border border-white/5 result-section" style="animation-delay:0.2s">
                <h3 class="text-sm font-bold uppercase tracking-widest text-emerald-400 mb-4 flex items-center gap-2">
                    <i class="fas fa-pen-fancy"></i> Soal Essay
                    <span class="text-[10px] font-normal bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded-md" x-text="hasilSoal.soal_essay.length + ' soal'"></span>
                </h3>
                <div class="space-y-4">
                    <template x-for="(s, i) in hasilSoal.soal_essay" :key="i">
                        <div class="bg-black/20 rounded-xl p-4 border border-white/5">
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-sm font-bold" x-text="i+1"></span>
                                <div class="flex-1">
                                    <p class="text-sm font-medium mb-2" x-text="s.pertanyaan"></p>
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        <span class="text-[9px] px-2 py-0.5 rounded-full" :class="s.tingkat==='Mudah' ? 'bg-emerald-500/10 text-emerald-400' : (s.tingkat==='Sulit' ? 'bg-red-500/10 text-red-400' : 'bg-amber-500/10 text-amber-400')" x-text="s.tingkat"></span>
                                        <span class="text-[9px] px-2 py-0.5 rounded-full bg-violet-500/10 text-violet-400" x-text="s.taksonomi"></span>
                                    </div>
                                    <details class="text-xs text-slate-400">
                                        <summary class="cursor-pointer text-amber-400 hover:text-amber-300"><i class="fas fa-key mr-1"></i>Lihat Kunci & Pembahasan</summary>
                                        <div class="mt-2 pl-4 border-l-2 border-amber-500/30 space-y-1">
                                            <p><b class="text-emerald-400">Jawaban:</b> <span x-text="s.jawaban"></span></p>
                                            <p x-show="s.pembahasan"><b class="text-amber-400">Pembahasan:</b> <span x-text="s.pembahasan"></span></p>
                                        </div>
                                    </details>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- ACTION BUTTONS -->
        <div class="glass rounded-2xl p-6 border border-white/5 result-section" style="animation-delay:0.3s">
            <h3 class="text-sm font-bold uppercase tracking-widest text-white mb-4"><i class="fas fa-save mr-2"></i>Simpan & Buat Paket</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button @click="simpanDanBuatPaket()" :disabled="isSaving" class="flex items-center justify-center gap-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white py-4 rounded-xl text-sm font-black italic uppercase tracking-widest shadow-lg shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-60">
                    <i class="fas fa-save"></i> Simpan ke Bank & Buat Paket
                </button>
                <button @click="simpanBankSaja()" :disabled="isSaving" class="flex items-center justify-center gap-2 bg-white/5 hover:bg-white/10 text-slate-300 py-4 rounded-xl text-sm font-bold transition-all border border-white/10">
                    <i class="fas fa-database"></i> Simpan ke Bank Saja
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('soalGenerator', () => ({
        config: {
            nama_ujian: '', tipe_ujian: 'Tulis', jenis_ujian: 'UH',
            id_mapel: '0', id_kelas: '0', semester: 'Ganjil',
            tanggal_ujian: '', durasi_menit: 60,
            topik: '', cp: '', petunjuk: '',
            jml_pg: 10, jml_essay: 5, opsi_pg: '5',
            tingkat: 'Campuran', berpikir: 'Campuran'
        },
        isLoading: false, isDone: false, isSaving: false,
        loadingMsg: '', hasilSoal: {},

        getMapelName() {
            const el = document.querySelector('select[x-model="config.id_mapel"]');
            return el ? el.options[el.selectedIndex]?.text || '-' : '-';
        },
        getKelasName() {
            const el = document.querySelector('select[x-model="config.id_kelas"]');
            return el ? el.options[el.selectedIndex]?.text || '-' : '-';
        },
        getOpsiList(s) {
            const list = [];
            if (s.opsi_a) list.push({key:'A', val:s.opsi_a});
            if (s.opsi_b) list.push({key:'B', val:s.opsi_b});
            if (s.opsi_c) list.push({key:'C', val:s.opsi_c});
            if (s.opsi_d) list.push({key:'D', val:s.opsi_d});
            if (s.opsi_e) list.push({key:'E', val:s.opsi_e});
            return list;
        },
        validate() {
            const errs = [];
            if (!this.config.nama_ujian.trim()) errs.push('Nama Ujian');
            if (this.config.id_mapel === '0') errs.push('Mata Pelajaran');
            if (this.config.id_kelas === '0') errs.push('Kelas');
            if (!this.config.topik.trim()) errs.push('Topik/Materi');
            if ((parseInt(this.config.jml_pg)||0) + (parseInt(this.config.jml_essay)||0) < 1) errs.push('Minimal 1 soal');
            return errs;
        },
        async generate() {
            const errs = this.validate();
            if (errs.length > 0) {
                Swal.fire({ title: 'Belum Lengkap', html: 'Isi: <br>• ' + errs.join('<br>• '), icon: 'warning', confirmButtonColor: '#7c3aed' });
                return;
            }
            this.isLoading = true;
            this.loadingMsg = 'Menghubungkan ke AI...';
            const fd = new FormData();
            fd.append('action', 'generate');
            fd.append('mapel', this.getMapelName());
            fd.append('kelas', this.getKelasName());
            fd.append('topik', this.config.topik);
            fd.append('cp', this.config.cp);
            fd.append('jml_pg', this.config.jml_pg);
            fd.append('jml_essay', this.config.jml_essay);
            fd.append('opsi_pg', this.config.opsi_pg);
            fd.append('tingkat', this.config.tingkat);
            fd.append('berpikir', this.config.berpikir);

            setTimeout(() => { this.loadingMsg = 'AI sedang menyusun soal...'; }, 2000);
            setTimeout(() => { this.loadingMsg = 'Membuat kunci jawaban & pembahasan...'; }, 5000);
            setTimeout(() => { this.loadingMsg = 'Menyusun kisi-kisi...'; }, 8000);

            try {
                const res = await fetch('<?= BASE_URL ?>api/soal_ai.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status !== 'success') throw new Error(data.message || 'Gagal generate soal.');
                this.hasilSoal = data.data;
                this.isDone = true;
            } catch (e) {
                Swal.fire({ title: 'Gagal', text: e.message, icon: 'error', confirmButtonColor: '#7c3aed' });
            }
            this.isLoading = false;
        },
        buildSoalArray() {
            const arr = [];
            (this.hasilSoal.soal_pg || []).forEach(s => {
                arr.push({ ...s, tipe_soal: 'PG' });
            });
            (this.hasilSoal.soal_essay || []).forEach(s => {
                arr.push({ ...s, tipe_soal: 'Essay' });
            });
            return arr;
        },
        async simpanBankSaja() {
            this.isSaving = true;
            const fd = new FormData();
            fd.append('action', 'simpan_bank');
            fd.append('soal', JSON.stringify(this.buildSoalArray()));
            fd.append('id_mapel', this.config.id_mapel);
            fd.append('id_kelas', this.config.id_kelas);
            fd.append('topik', this.config.topik);
            fd.append('kompetensi', this.config.cp);
            try {
                const res = await fetch('<?= BASE_URL ?>api/soal_ai.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status !== 'success') throw new Error(data.message);
                Swal.fire({ title: 'Berhasil!', text: data.message, icon: 'success', confirmButtonColor: '#7c3aed' })
                .then(() => window.location.href = 'generator_soal.php');
            } catch (e) {
                Swal.fire({ title: 'Gagal', text: e.message, icon: 'error', confirmButtonColor: '#7c3aed' });
            }
            this.isSaving = false;
        },
        async simpanDanBuatPaket() {
            this.isSaving = true;
            // 1. Simpan ke bank
            const fd = new FormData();
            fd.append('action', 'simpan_bank');
            fd.append('soal', JSON.stringify(this.buildSoalArray()));
            fd.append('id_mapel', this.config.id_mapel);
            fd.append('id_kelas', this.config.id_kelas);
            fd.append('topik', this.config.topik);
            fd.append('kompetensi', this.config.cp);
            try {
                const res1 = await fetch('<?= BASE_URL ?>api/soal_ai.php', { method: 'POST', body: fd });
                const d1 = await res1.json();
                if (d1.status !== 'success') throw new Error(d1.message);

                // 2. Get IDs of newly inserted soal
                const res_ids = await fetch('<?= BASE_URL ?>api/soal_ai.php', {
                    method: 'POST',
                    body: (() => { const f = new FormData(); f.append('action', 'get_latest_ids'); f.append('count', d1.count); return f; })()
                });
                // Fallback: just get latest N soal IDs via separate query
                // We'll use a trick: query bank soal for this guru, latest N
                const fd2 = new FormData();
                fd2.append('action', 'simpan_paket');
                fd2.append('nama_ujian', this.config.nama_ujian);
                fd2.append('tipe_ujian', this.config.tipe_ujian);
                fd2.append('jenis_ujian', this.config.jenis_ujian);
                fd2.append('id_mapel', this.config.id_mapel);
                fd2.append('id_kelas', this.config.id_kelas);
                fd2.append('semester', this.config.semester);
                fd2.append('tanggal_ujian', this.config.tanggal_ujian);
                fd2.append('durasi_menit', this.config.durasi_menit);
                fd2.append('jumlah_opsi_pg', this.config.opsi_pg);
                fd2.append('petunjuk_umum', this.config.petunjuk);
                fd2.append('topik', this.config.topik);
                fd2.append('auto_latest', d1.count); // special flag
                fd2.append('soal_ids', '[]');

                const res2 = await fetch('<?= BASE_URL ?>api/soal_ai.php', { method: 'POST', body: fd2 });
                const d2 = await res2.json();
                if (d2.status !== 'success') throw new Error(d2.message);

                Swal.fire({ title: 'Berhasil!', text: 'Soal disimpan & Paket Ujian dibuat!', icon: 'success', confirmButtonColor: '#7c3aed' })
                .then(() => window.location.href = 'paket_ujian_detail.php?id=' + d2.id_paket);
            } catch (e) {
                Swal.fire({ title: 'Gagal', text: e.message, icon: 'error', confirmButtonColor: '#7c3aed' });
            }
            this.isSaving = false;
        },
        reset() {
            this.isDone = false;
            this.hasilSoal = {};
        }
    }));
});
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
