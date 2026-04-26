<?php
require_once __DIR__ . '/config/init.php';

$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
$ta_list = $pdo->query("SELECT * FROM tbl_tahun_ajaran ORDER BY id_ta DESC")->fetchAll();
$ta_aktif_def = get_ta_aktif($pdo);

$id_siswa_post = (int)($_POST['id_siswa'] ?? 0);
$sel_ta_public = (int)($_POST['sel_ta'] ?? ($_GET['ta'] ?? ($ta_aktif_def['id_ta'] ?? 0)));
$siswa = null;
$tunggakan = [];
$lunas = [];
$total_tunggakan = 0;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id_siswa_post > 0) {
    // Cari siswa
    $stmt = $pdo->prepare("SELECT s.*, k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas = k.id_kelas WHERE s.id_siswa = ? AND s.status = 'Aktif'");
    $stmt->execute([$id_siswa_post]);
    $siswa = $stmt->fetch();

    if ($siswa) {
        $id_siswa = $siswa['id_siswa'];

        // Resolve selected TA
        $ta = null;
        if ($sel_ta_public) {
            $st = $pdo->prepare("SELECT * FROM tbl_tahun_ajaran WHERE id_ta=?");
            $st->execute([$sel_ta_public]);
            $ta = $st->fetch();
        }
        if (!$ta) $ta = $ta_aktif_def;

        // Get SPP and other bulanan - filtered by TA
        $id_ta_f = $ta['id_ta'] ?? 0;
        $stmt_b = $pdo->prepare("SELECT * FROM tbl_jenis_bayar WHERE tipe='Bulanan' AND id_ta=? ORDER BY nama_jenis");
        $stmt_b->execute([$id_ta_f]);
        $jenis_bulanan = $stmt_b->fetchAll();

        // Get Bebas - filtered by TA
        $stmt_be = $pdo->prepare("SELECT * FROM tbl_jenis_bayar WHERE tipe='Bebas' AND id_ta=? ORDER BY nama_jenis");
        $stmt_be->execute([$id_ta_f]);
        $jenis_bebas = $stmt_be->fetchAll();

        // Calculate academic year dates from selected TA
        if ($ta && $ta['tgl_mulai'] && $ta['tgl_selesai']) {
            $ta_start = (int)date('Y', strtotime($ta['tgl_mulai']));
            $ta_end = (int)date('Y', strtotime($ta['tgl_selesai']));
            $ta_label = $ta['tahun'];
        } else {
            $current_m = (int)date('n');
            $current_y_num = (int)date('Y');
            if ($current_m < 7) {
                $ta_start = $current_y_num - 1;
                $ta_end = $current_y_num;
            } else {
                $ta_start = $current_y_num;
                $ta_end = $current_y_num + 1;
            }
            $ta_label = $ta_start . '/' . $ta_end;
        }

        $academic_months = [
            ['m' => 7, 'y' => $ta_start, 'name' => 'Juli'],
            ['m' => 8, 'y' => $ta_start, 'name' => 'Agustus'],
            ['m' => 9, 'y' => $ta_start, 'name' => 'September'],
            ['m' => 10, 'y' => $ta_start, 'name' => 'Oktober'],
            ['m' => 11, 'y' => $ta_start, 'name' => 'November'],
            ['m' => 12, 'y' => $ta_start, 'name' => 'Desember'],
            ['m' => 1, 'y' => $ta_end, 'name' => 'Januari'],
            ['m' => 2, 'y' => $ta_end, 'name' => 'Februari'],
            ['m' => 3, 'y' => $ta_end, 'name' => 'Maret'],
            ['m' => 4, 'y' => $ta_end, 'name' => 'April'],
            ['m' => 5, 'y' => $ta_end, 'name' => 'Mei'],
            ['m' => 6, 'y' => $ta_end, 'name' => 'Juni']
        ];

        foreach ($jenis_bulanan as $jb) {
            $tarif = get_tarif($pdo, $id_siswa, $jb['id_jenis']);
            if ($tarif <= 0) continue;

            // Memeriksa genap 1 Tahun Ajaran (Juli - Juni)
            foreach ($academic_months as $am) {
                $paid = $pdo->prepare("SELECT 1 FROM tbl_pembayaran WHERE id_siswa=? AND id_jenis=? AND bulan=? AND tahun=?");
                $paid->execute([$id_siswa, $jb['id_jenis'], $am['m'], $am['y']]);
                if (!$paid->fetch()) {
                    $tunggakan[] = [
                        'jenis' => $jb['nama_jenis'] . " (" . $am['name'] . " " . $am['y'] . ")",
                        'nominal' => $tarif
                    ];
                    $total_tunggakan += $tarif;
                } else {
                    $lunas[] = [
                        'jenis' => $jb['nama_jenis'] . " (" . $am['name'] . " " . $am['y'] . ")",
                        'nominal' => $tarif
                    ];
                }
            }
        }

        foreach ($jenis_bebas as $jb) {
            $tarif = get_tarif($pdo, $id_siswa, $jb['id_jenis']);
            if ($tarif <= 0) continue;

            $tb_stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran_bebas WHERE id_siswa=? AND id_jenis=?");
            $tb_stmt->execute([$id_siswa, $jb['id_jenis']]);
            $total_bayar = $tb_stmt->fetchColumn();
            $sisa = $tarif - $total_bayar;

            if ($sisa > 0) {
                $tunggakan[] = [
                    'jenis' => $jb['nama_jenis'],
                    'nominal' => $sisa
                ];
                $total_tunggakan += $sisa;
            }
            if ($total_bayar > 0) {
                $status_lunas = $sisa > 0 ? '(Sebagian)' : '(Lunas)';
                $lunas[] = [
                    'jenis' => $jb['nama_jenis'] . " " . $status_lunas,
                    'nominal' => $total_bayar
                ];
            }
        }
    } else {
        $error = "Data siswa tidak ditemukan. Pastikan NISN dan NIS yang dimasukkan benar.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Tagihan & Tunggakan Siswa - <?= clean($setting['nama_sekolah'] ?? 'SIPANDA') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { 50: '#f0f4fa', 100: '#e1e9f4', 200: '#c8d7ea', 300: '#a3bdda', 400: '#779fc6', 500: '#5684b3', 600: '#436994', 700: '#365478', 800: '#2f4764', 900: '#2a3d54', 950: '#0a1628' },
                        gold: { 50: '#fcfaf2', 100: '#f7efe1', 200: '#f0dbbe', 300: '#e7c191', 400: '#dea160', 500: '#d78839', 600: '#ca6e2c', 700: '#a85325', 800: '#864324', 900: '#6c3821', 950: '#3a1b0e' }
                    },
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'], }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .ts-wrapper { width: 100%; border: none !important; }
        .ts-control { border-radius: 0.75rem !important; padding: 1rem 1rem !important; border: 1px solid #e2e8f0 !important; background-color: #f8fafc !important; font-size: 0.875rem !important; font-weight: 700 !important; font-family: 'Plus Jakarta Sans', sans-serif !important; box-shadow: none !important; cursor: text !important;}
        .ts-control.focus { border-color: #3b82f6 !important; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1) !important; background-color: #ffffff !important; }
        .ts-dropdown { border-radius: 0.75rem !important; border: 1px solid #e2e8f0 !important; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important; font-family: 'Plus Jakarta Sans', sans-serif !important; border-top: none !important; margin-top: 4px !important; z-index: 50 !important; }
        .ts-dropdown .option { padding: 0.75rem 1rem !important; font-size: 0.875rem !important; border-bottom: 1px solid #f1f5f9; }
        .ts-dropdown .option:last-child { border-bottom: none; }
        .ts-dropdown .active { background-color: #f0fdf4 !important; color: #166534 !important; font-weight: bold; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col items-center justify-center py-12 px-4 selection:bg-gold-500 selection:text-white relative">
    
    <!-- Decor -->
    <div class="fixed top-0 left-0 w-full h-96 bg-navy-950 rounded-b-[4rem] skew-y-2 -translate-y-10 z-0"></div>

    <div class="z-10 w-full max-w-lg">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-white shadow-xl mx-auto flex items-center justify-center font-bold text-navy-950 text-2xl mb-4 p-2">
                <?php if (!empty($setting['logo_web'])): ?>
                    <img src="<?= BASE_URL ?>gambar/<?= $setting['logo_web'] ?>" class="w-full h-full object-contain">
                <?php else: ?>
                    <i class="fas fa-school text-gold-500"></i>
                <?php endif; ?>
            </div>
            <h1 class="text-3xl font-black text-white tracking-tight mb-2">Cek Tagihan Publik</h1>
            <p class="text-blue-100 text-sm">Pantau informasi administrasi & tunggakan secara transparan tanpa log in.</p>
        </div>

        <div class="bg-white rounded-[2rem] shadow-2xl overflow-hidden border border-slate-100">
            <div class="p-8">
                <?php if ($error): ?>
                    <div class="bg-rose-50 text-rose-600 p-4 rounded-xl text-sm font-bold flex items-center gap-3 mb-6">
                        <i class="fas fa-exclamation-circle text-lg"></i>
                        <span><?= $error ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!$siswa): 
                    // Ambil Data Kelas dan Siswa
                    $kelas_all = $pdo->query("SELECT id_kelas, nama_kelas FROM tbl_kelas ORDER BY nama_kelas ASC")->fetchAll();
                    $siswa_all = $pdo->query("SELECT id_siswa, id_kelas, nama FROM tbl_siswa WHERE status='Aktif' ORDER BY nama ASC")->fetchAll();

                    $siswa_grouped = [];
                    $siswa_flat = [];
                    foreach ($siswa_all as $s) {
                        $item = ['id' => $s['id_siswa'], 'nama' => clean($s['nama'])];
                        $siswa_grouped[$s['id_kelas']][] = $item;
                        $siswa_flat[] = $item;
                    }
                ?>
                    <!-- Search Form -->
                    <form method="POST" class="space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">1. Pilih Tahun Ajaran</label>
                            <div class="relative">
                                <i class="fas fa-calendar-alt absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10 pointer-events-none"></i>
                                <select name="sel_ta" class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-12 pr-4 py-4 text-sm font-bold text-navy-950 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:outline-none appearance-none transition-all cursor-pointer">
                                    <?php foreach ($ta_list as $tl): ?>
                                    <option value="<?= $tl['id_ta'] ?>" <?= $sel_ta_public==$tl['id_ta']?'selected':'' ?>><?= clean($tl['tahun']) ?> <?= $tl['status']=='aktif'?'(Aktif)':'' ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs z-10"></i>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">2. Filter Kelas (Opsional)</label>
                            <div class="relative">
                                <i class="fas fa-door-open absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10 pointer-events-none"></i>
                                <select id="select-kelas" class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-12 pr-4 py-4 text-sm font-bold text-navy-950 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:outline-none appearance-none transition-all cursor-pointer">
                                    <option value="">-- Tampilkan Semua Kelas --</option>
                                    <?php foreach ($kelas_all as $k): ?>
                                        <option value="<?= $k['id_kelas'] ?>"><?= clean($k['nama_kelas']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs z-10"></i>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">3. Cari Nama Siswa</label>
                            <select id="select-siswa" name="id_siswa" required placeholder="Ketik nama siswa...">
                                <option value="">-- Ketik Nama Siswa... --</option>
                                <?php foreach ($siswa_flat as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= $s['nama'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-navy-950 hover:bg-navy-800 text-white font-black py-4 rounded-xl shadow-xl shadow-navy-950/20 transition-all hover:-translate-y-0.5 mt-2">
                            <i class="fas fa-search mr-2 text-gold-400"></i> Tampilkan Tagihan
                        </button>
                    </form>

                    <!-- Script Autocomplete TomSelect -->
                    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
                    <script>
                        const siswaGrouped = <?= json_encode($siswa_grouped) ?>;
                        const siswaFlat = <?= json_encode($siswa_flat) ?>;
                        const selectSiswa = document.getElementById('select-siswa');
                        const selectKelas = document.getElementById('select-kelas');

                        let tsSiswa = new TomSelect(selectSiswa, {
                            create: false,
                            sortField: { field: "text", direction: "asc" },
                            placeholder: "Ketik nama siswa untuk mencari..."
                        });

                        selectKelas.addEventListener('change', function() {
                            const classId = this.value;
                            tsSiswa.clear();
                            tsSiswa.clearOptions();
                            
                            if (classId && siswaGrouped[classId]) {
                                siswaGrouped[classId].forEach(s => {
                                    tsSiswa.addOption({value: s.id, text: s.nama});
                                });
                            } else {
                                // Show all if no class selected
                                siswaFlat.forEach(s => {
                                    tsSiswa.addOption({value: s.id, text: s.nama});
                                });
                            }
                            tsSiswa.refreshOptions(false);
                        });
                    </script>
                <?php else: ?>
                    <!-- Results -->
                    <div class="border-b border-slate-100 pb-6 mb-6">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 text-xl border-2 border-gold-400 overflow-hidden">
                                <img src="<?= BASE_URL ?>foto_siswa/<?= $siswa['foto'] ?>" onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?= urlencode($siswa['nama']) ?>&background=random&color=fff&bold=true';" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1">
                                <h3 class="font-black text-navy-950 text-lg uppercase"><?= clean($siswa['nama']) ?></h3>
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest"><?= clean($siswa['nama_kelas'] ?? 'Belum ada kelas') ?> • <?= clean($siswa['kategori']) ?></p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-2 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3">
                            <i class="fas fa-calendar-check text-blue-500"></i>
                            <span class="text-xs font-bold text-blue-800">Tahun Ajaran: <?= clean($ta_label) ?></span>
                            <span class="text-[10px] text-blue-500 ml-auto">Jul <?= $ta_start ?> — Jun <?= $ta_end ?></span>
                        </div>
                    </div>

                    <?php if (empty($tunggakan) && empty($lunas)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-search text-4xl text-slate-200 mb-4"></i>
                            <h4 class="font-black text-navy-950 text-xl mb-2">Tidak Ada Data Tagihan</h4>
                            <p class="text-sm text-slate-500">Siswa ini tidak memiliki tagihan aktif di tahun ini.</p>
                        </div>
                    <?php else: ?>
                        <?php if (empty($tunggakan)): ?>
                            <div class="text-center py-8 mb-6">
                                <div class="w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4 text-emerald-500 text-4xl">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h4 class="font-black text-navy-950 text-xl mb-2">Semua Tagihan Lunas!</h4>
                                <p class="text-sm text-slate-500">Terima kasih, tidak ada tunggakan pembayaran yang tercatat hingga bulan ini.</p>
                            </div>
                        <?php else: ?>
                            <div class="bg-rose-50 rounded-2xl p-6 mb-6 text-center border-2 border-rose-500/20">
                                <p class="text-xs font-bold text-rose-500 uppercase tracking-widest mb-1">Total Tunggakan (Sisa)</p>
                                <p class="text-3xl font-black text-rose-600"><?= rupiah($total_tunggakan) ?></p>
                            </div>

                            <h4 class="font-bold text-navy-950 mt-6 mb-3 uppercase tracking-widest text-xs"><i class="fas fa-exclamation-circle text-rose-500 mr-2"></i>Daftar Tunggakan Aktif</h4>
                            <ul class="space-y-3 mb-6">
                                <?php foreach ($tunggakan as $t): ?>
                                <li class="flex items-center justify-between p-4 rounded-xl border border-slate-100 bg-slate-50 hover:border-slate-200 transition-colors">
                                    <span class="text-xs font-bold text-navy-950"><?= clean($t['jenis']) ?></span>
                                    <span class="text-sm font-black text-rose-500"><?= rupiah($t['nominal']) ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex gap-3 text-sm text-blue-800 mb-8">
                                <i class="fas fa-info-circle mt-0.5"></i>
                                <p>Untuk melakukan pembayaran, silakan hubungi bagian Tata Usaha sekolah atau login ke portal siswa untuk bayar online via Payment Gateway.</p>
                            </div>
                        <?php endif; ?>

                        <!-- Riwayat Terbayar / Lunas -->
                        <?php if (!empty($lunas)): ?>
                            <h4 class="font-bold text-emerald-600 mt-8 mb-3 uppercase tracking-widest text-xs border-t border-slate-100 pt-6"><i class="fas fa-check-circle mr-2"></i>Telah Dibayar (Lunas/Dicicil)</h4>
                            <ul class="space-y-3 mb-4">
                                <?php foreach ($lunas as $l): ?>
                                <li class="flex items-center justify-between p-4 rounded-xl border border-emerald-100 bg-emerald-50/50 hover:bg-emerald-50 transition-colors">
                                    <span class="text-xs font-bold text-emerald-900"><?= clean($l['jenis']) ?></span>
                                    <span class="text-sm font-black text-emerald-600"><?= rupiah($l['nominal']) ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <p class="text-[10px] text-slate-400 text-center italic">* Data pembayaran ini adalah rekapitulasi riwayat sistem Kasir dan Gerbang Pembayaran.</p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="mt-8 text-center border-t border-slate-100 pt-6">
                        <a href="cek_tagihan_public.php" class="text-sm font-bold text-slate-500 hover:text-navy-950 transition-colors"><i class="fas fa-arrow-left mr-2"></i>Cek Siswa Lain</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="bg-slate-50 p-6 text-center border-t border-slate-100">
                <a href="index.php" class="text-xs font-bold text-slate-400 hover:text-blue-600 uppercase tracking-widest"><i class="fas fa-home mr-1 text-gold-500"></i> Kembali ke Beranda Utama</a>
            </div>
        </div>
    </div>
</body>
</html>
