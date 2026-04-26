<?php
$page_title = 'Jadwal Pelajaran';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin', 'waka_kurikulum']);
cek_fitur('akademik');

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_jadwal WHERE id_jadwal=?")->execute([$_GET['hapus']]);
    flash('msg', 'Dihapus!', 'warning');
    header('Location: jadwal.php?kelas=' . ($_GET['kelas'] ?? ''));
    exit;
}

$kelas_list = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();
$mapel_list = $pdo->query("SELECT * FROM tbl_mapel ORDER BY nama_mapel")->fetchAll();
$guru_list = $pdo->query("SELECT * FROM tbl_guru WHERE status='Aktif' ORDER BY nama")->fetchAll();
$jam_list = $pdo->query("SELECT * FROM tbl_jam ORDER BY jam_mulai")->fetchAll();
$sel_kelas = (int) ($_GET['kelas'] ?? ($kelas_list[0]['id_kelas'] ?? 0));
$hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

$jadwal = [];
if ($sel_kelas) {
    $stmt = $pdo->prepare("SELECT j.*,m.nama_mapel,g.nama as nama_guru,jm.nama_jam,jm.jam_mulai,jm.jam_selesai FROM tbl_jadwal j JOIN tbl_mapel m ON j.id_mapel=m.id_mapel LEFT JOIN tbl_guru g ON j.id_guru=g.id_guru JOIN tbl_jam jm ON j.id_jam=jm.id_jam WHERE j.id_kelas=?");
    $stmt->execute([$sel_kelas]);
    $jadwal_raw = $stmt->fetchAll();
    foreach ($jadwal_raw as $j) {
        $jadwal[$j['hari']][$j['id_jam']] = $j;
    }
}

require_once __DIR__ . '/../../template/header.php';
require_once __DIR__ . '/../../template/sidebar.php';
require_once __DIR__ . '/../../template/topbar.php';
?>

<?= alert_flash('msg') ?>
<style>
    .jadwal-grid th,
    .jadwal-grid td {
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .dropzone.drag-over {
        background: rgba(59, 130, 246, 0.2) !important;
        border: 2px dashed #3b82f6 !important;
    }

    .jadwal-item.drag-over-guru {
        background: rgba(16, 185, 129, 0.2) !important;
        border: 2px dashed #10b981 !important;
        transform: scale(1.02);
    }

    .drag-chip {
        cursor: grab;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .drag-chip:active {
        cursor: grabbing;
        transform: scale(1.05);
        z-index: 10;
    }

    .dragging {
        opacity: 0.5;
    }

    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>

<!-- TABS KELAS -->
<div class="glass rounded-xl mb-6 overflow-hidden border border-white/10 shadow-lg">
    <div class="bg-white/5 border-b border-white/10 px-5 py-3 flex items-center justify-between">
        <h3 class="font-bold text-sm text-white uppercase tracking-wider"><i
                class="fas fa-layer-group mr-2 text-blue-400"></i> Navigasi Kelas</h3>
    </div>
    <div class="p-3 flex overflow-x-auto hide-scrollbar gap-2 scroll-smooth bg-black/20">
        <?php foreach ($kelas_list as $k): ?>
            <a href="?kelas=<?= $k['id_kelas'] ?>"
                class="whitespace-nowrap px-4 py-2 rounded-lg text-sm font-semibold transition-all <?= $sel_kelas == $k['id_kelas'] ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/40 ring-2 ring-blue-400 ring-offset-2 ring-offset-[#0f172a]' : 'bg-white/5 text-slate-400 hover:bg-white/10 hover:text-white border border-white/5' ?>">
                <?= clean($k['nama_kelas']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($sel_kelas): ?>
    <!-- BANK DRAG & DROP -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
        <!-- BANK MAPEL -->
        <div class="glass rounded-xl border border-indigo-500/20 flex flex-col h-64 shadow-[0_0_15px_rgba(99,102,241,0.1)]">
            <div class="bg-indigo-600/20 border-b border-indigo-500/20 px-4 py-3 flex items-center justify-between">
                <h3 class="font-bold text-sm text-indigo-300"><i class="fas fa-book mr-2"></i> 1. Keranjang Mata Pelajaran
                </h3>
                <span class="text-xs bg-indigo-500/30 text-indigo-200 px-2 py-0.5 rounded-full">Tarik ke kotak kosong</span>
            </div>
            <div class="p-4 flex flex-wrap gap-2 overflow-y-auto hide-scrollbar content-start">
                <?php foreach ($mapel_list as $m): ?>
                    <div class="drag-chip bg-slate-800 border border-indigo-500/30 text-slate-200 px-3 py-1.5 rounded-lg text-xs hover:bg-indigo-600/40 transition-colors shadow-sm"
                        draggable="true" data-type="mapel" data-id="<?= $m['id_mapel'] ?>">
                        <i class="fas fa-grip-vertical opacity-30 mr-1"></i> <?= clean($m['nama_mapel']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- BANK GURU -->
        <div
            class="glass rounded-xl border border-emerald-500/20 flex flex-col h-64 shadow-[0_0_15px_rgba(16,185,129,0.1)]">
            <div class="bg-emerald-600/20 border-b border-emerald-500/20 px-4 py-3 flex items-center justify-between">
                <h3 class="font-bold text-sm text-emerald-300"><i class="fas fa-user-tie mr-2"></i> 2. Keranjang Guru</h3>
                <span class="text-xs bg-emerald-500/30 text-emerald-200 px-2 py-0.5 rounded-full">Tarik ke mapel yang sudah
                    ada</span>
            </div>
            <div class="p-4 flex flex-wrap gap-2 overflow-y-auto hide-scrollbar content-start">
                <?php foreach ($guru_list as $g): ?>
                    <div class="drag-chip bg-slate-800 border border-emerald-500/30 text-slate-200 px-3 py-1.5 rounded-lg text-xs hover:bg-emerald-600/40 transition-colors shadow-sm"
                        draggable="true" data-type="guru" data-id="<?= $g['id_guru'] ?>">
                        <i class="fas fa-grip-vertical opacity-30 mr-1"></i> <?= clean($g['nama']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- KALENDER JADWAL -->
    <div class="glass rounded-xl overflow-hidden shadow-2xl mb-10 border border-white/10 relative">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-600/5 to-purple-600/5 pointer-events-none"></div>
        <div class="bg-blue-600/20 px-5 py-4 border-b border-white/10 flex justify-between items-center relative z-10">
            <h3 class="font-bold text-lg text-white"><i class="fas fa-calendar-alt mr-2"></i> Papan Jadwal Pelajaran</h3>
            <div class="flex items-center gap-3">
                <span class="text-xs bg-black/40 text-blue-300 px-3 py-1.5 rounded-full border border-blue-500/30">
                    <i class="fas fa-magic mr-1"></i> Auto-Save Aktif
                </span>
                <select id="cetak_semester"
                    class="bg-black/40 text-white text-xs border border-blue-500/30 rounded-lg px-2 py-1.5">
                    <option value="1">Semester 1</option>
                    <option value="2">Semester 2</option>
                </select>
                <select id="cetak_kurikulum"
                    class="bg-black/40 text-white text-xs border border-blue-500/30 rounded-lg px-2 py-1.5">
                    <option value="Kurikulum Merdeka">Kurikulum Merdeka</option>
                    <option value="Kurikulum Berbasis Cinta (KBC)">Kurikulum Berbasis Cinta (KBC)</option>
                    <option value="Kurikulum 2013">Kurikulum 2013</option>
                    <option value="KTSP">KTSP</option>
                </select>
                <button onclick="cetakRekap()"
                    class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-4 py-1.5 rounded-lg shadow-lg transition-colors flex items-center gap-2 border border-emerald-400/30">
                    <i class="fas fa-print"></i> Rekap Guru
                </button>
                <button onclick="cetakJadwal()"
                    class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-4 py-1.5 rounded-lg shadow-lg transition-colors flex items-center gap-2 border border-blue-400/30">
                    <i class="fas fa-print"></i> Cetak PDF Kelas
                </button>
            </div>
        </div>
        <script>
            function cetakRekap() {
                const smt = document.getElementById('cetak_semester').value;
                const kur = encodeURIComponent(document.getElementById('cetak_kurikulum').value);
                window.open('cetak_rekap_guru.php?semester=' + smt + '&kurikulum=' + kur, '_blank');
            }
            function cetakJadwal() {
                const smt = document.getElementById('cetak_semester').value;
                const kur = encodeURIComponent(document.getElementById('cetak_kurikulum').value);
                window.open('cetak_jadwal.php?kelas=<?= $sel_kelas ?>&semester=' + smt + '&kurikulum=' + kur, '_blank');
            }
        </script>

        <div class="overflow-x-auto p-5 relative z-10">
            <table
                class="w-full text-sm text-left jadwal-grid border-collapse shadow-lg bg-black/20 rounded-xl overflow-hidden">
                <thead class="text-xs text-slate-300 uppercase bg-black/40 text-center">
                    <tr>
                        <th class="px-4 py-4 w-28 text-blue-300"><i class="far fa-clock mr-1"></i> Waktu</th>
                        <?php foreach ($hari_list as $h): ?>
                            <th class="px-4 py-4 w-48 font-bold tracking-wider"><?= $h ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($jam_list as $jam): ?>
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="px-4 py-4 text-center bg-black/30 border-r border-white/5">
                                <div class="font-bold text-slate-200 text-base"><?= $jam['nama_jam'] ?></div>
                                <div
                                    class="text-[10px] text-slate-400 font-mono mt-1 bg-white/5 inline-block px-2 py-0.5 rounded">
                                    <?= substr($jam['jam_mulai'], 0, 5) ?> - <?= substr($jam['jam_selesai'], 0, 5) ?></div>
                            </td>

                            <?php foreach ($hari_list as $h): ?>
                                <td class="p-2 align-top dropzone relative transition-all duration-300" data-hari="<?= $h ?>"
                                    data-jam="<?= $jam['id_jam'] ?>">
                                    <?php
                                    $item = $jadwal[$h][$jam['id_jam']] ?? null;
                                    if ($item):
                                        $hash = md5($item['nama_mapel']);
                                        $colors = [
                                            'bg-indigo-500/20 text-indigo-200 border-indigo-500/40',
                                            'bg-emerald-500/20 text-emerald-200 border-emerald-500/40',
                                            'bg-rose-500/20 text-rose-200 border-rose-500/40',
                                            'bg-amber-500/20 text-amber-200 border-amber-500/40',
                                            'bg-cyan-500/20 text-cyan-200 border-cyan-500/40',
                                            'bg-violet-500/20 text-violet-200 border-violet-500/40'
                                        ];
                                        $colorIndex = hexdec(substr($hash, 0, 1)) % count($colors);
                                        $colorClass = $colors[$colorIndex];

                                        $isGuruEmpty = empty($item['id_guru']);
                                        ?>
                                        <div class="jadwal-item drag-chip border rounded-xl p-3 h-24 flex flex-col justify-between <?= $colorClass ?> backdrop-blur-sm shadow-sm"
                                            draggable="true" data-type="jadwal" data-id="<?= $item['id_jadwal'] ?>">
                                            <div class="flex justify-between items-start mb-1 gap-2">
                                                <span class="font-bold text-sm leading-tight text-white drop-shadow-md line-clamp-2"
                                                    title="<?= clean($item['nama_mapel']) ?>"><?= clean($item['nama_mapel']) ?></span>
                                                <button type="button"
                                                    onclick="confirmDelete('?hapus=<?= $item['id_jadwal'] ?>&kelas=<?= $sel_kelas ?>')"
                                                    class="text-slate-400 hover:text-red-400 hover:bg-red-500/20 rounded p-1 opacity-0 group-hover:opacity-100 transition-all -mt-1 -mr-1 flex-shrink-0"><i
                                                        class="fas fa-trash-alt text-xs"></i></button>
                                            </div>
                                            <div
                                                class="text-xs flex items-center gap-1.5 mt-auto p-1.5 rounded-lg border <?= $isGuruEmpty ? 'text-rose-300 border-rose-500/30 bg-rose-500/10' : 'text-emerald-300 border-emerald-500/30 bg-emerald-500/10' ?>">
                                                <?php if ($isGuruEmpty): ?>
                                                    <i class="fas fa-exclamation-circle animate-pulse"></i>
                                                    <span class="truncate italic font-medium">Tarik Guru!</span>
                                                <?php else: ?>
                                                    <i class="fas fa-check-circle"></i>
                                                    <span class="truncate font-medium"
                                                        title="<?= clean($item['nama_guru']) ?>"><?= clean($item['nama_guru']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div
                                            class="h-24 w-full rounded-xl border border-dashed border-white/10 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity pointer-events-none bg-white/[0.02]">
                                            <span class="text-xs text-slate-500 font-medium"><i
                                                    class="fas fa-plus mb-1 block text-center"></i> Kosong</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const draggables = document.querySelectorAll('.drag-chip');
            const dropzones = document.querySelectorAll('.dropzone');
            const idKelas = <?= $sel_kelas ?>;

            let draggedData = null;

            draggables.forEach(item => {
                item.addEventListener('dragstart', function (e) {
                    draggedData = {
                        type: this.dataset.type,
                        id: this.dataset.id
                    };
                    setTimeout(() => this.classList.add('dragging'), 0);
                    e.dataTransfer.setData('text/plain', JSON.stringify(draggedData));
                    e.dataTransfer.effectAllowed = 'move';
                });

                item.addEventListener('dragend', function () {
                    draggedData = null;
                    this.classList.remove('dragging');
                });
            });

            dropzones.forEach(zone => {
                zone.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';

                    if (draggedData && draggedData.type === 'guru') {
                        const child = this.querySelector('.jadwal-item');
                        if (child) child.classList.add('drag-over-guru');
                    } else {
                        this.classList.add('drag-over');
                    }
                });

                zone.addEventListener('dragleave', function () {
                    this.classList.remove('drag-over');
                    const child = this.querySelector('.jadwal-item');
                    if (child) child.classList.remove('drag-over-guru');
                });

                zone.addEventListener('drop', function (e) {
                    e.preventDefault();
                    this.classList.remove('drag-over');
                    const child = this.querySelector('.jadwal-item');
                    if (child) child.classList.remove('drag-over-guru');

                    try {
                        const dataStr = e.dataTransfer.getData('text/plain');
                        if (!dataStr) return;
                        const data = JSON.parse(dataStr);

                        const targetHari = this.dataset.hari;
                        const targetJam = this.dataset.jam;
                        const formData = new FormData();
                        formData.append('id_kelas', idKelas);

                        if (data.type === 'jadwal') {
                            const parentZone = document.querySelector(`.jadwal-item[data-id="${data.id}"]`).closest('.dropzone');
                            if (parentZone && parentZone.dataset.hari === targetHari && parentZone.dataset.jam === targetJam) return;

                            formData.append('action', 'move');
                            formData.append('id_jadwal', data.id);
                            formData.append('hari', targetHari);
                            formData.append('id_jam', targetJam);
                        }
                        else if (data.type === 'mapel') {
                            formData.append('action', 'add_mapel');
                            formData.append('id_mapel', data.id);
                            formData.append('hari', targetHari);
                            formData.append('id_jam', targetJam);
                        }
                        else if (data.type === 'guru') {
                            if (!child) {
                                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Tarik guru ke atas jadwal mata pelajaran yang sudah ada di kalender.', background: '#1e293b', color: '#fff' });
                                return;
                            }
                            formData.append('action', 'assign_guru');
                            formData.append('id_guru', data.id);
                            formData.append('id_jadwal', child.dataset.id);
                        }

                        Swal.fire({
                            title: 'Menyimpan...',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); },
                            background: '#1e293b',
                            color: '#fff'
                        });

                        fetch('jadwal_action.php', { method: 'POST', body: formData })
                            .then(res => res.json())
                            .then(resData => {
                                if (resData.success) {
                                    Swal.fire({
                                        icon: 'success', title: 'Berhasil!', text: resData.message, timer: 800, showConfirmButton: false, background: '#1e293b', color: '#fff'
                                    }).then(() => window.location.reload());
                                } else {
                                    Swal.fire({ icon: 'error', title: 'Gagal / Bentrok!', text: resData.message, background: '#1e293b', color: '#fff' });
                                }
                            })
                            .catch(err => Swal.fire({ icon: 'error', title: 'Error', text: 'Koneksi ke server gagal.', background: '#1e293b', color: '#fff' }));

                    } catch (e) { }
                });
            });
        });
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../template/footer.php'; ?>