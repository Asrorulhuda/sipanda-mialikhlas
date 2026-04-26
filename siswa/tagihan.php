<?php
$page_title = 'Tagihan & Pembayaran Online';
require_once __DIR__ . '/../config/init.php';
cek_role(['siswa']);

$id_siswa = (int)$_SESSION['user_id'];
$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();

// Get Student Info
$stmt = $pdo->prepare("SELECT s.*, k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas = k.id_kelas WHERE s.id_siswa = ?");
$stmt->execute([$id_siswa]);
$siswa = $stmt->fetch();

// Get TA list
$ta_aktif = get_ta_aktif($pdo);
$ta_list = $pdo->query("SELECT * FROM tbl_tahun_ajaran ORDER BY id_ta DESC")->fetchAll();

$sel_ta = isset($_GET['ta']) ? (int)$_GET['ta'] : ($ta_aktif['id_ta'] ?? 0);
$ta = null;
if ($sel_ta) {
    $st = $pdo->prepare("SELECT * FROM tbl_tahun_ajaran WHERE id_ta=?");
    $st->execute([$sel_ta]);
    $ta = $st->fetch();
}
if (!$ta) $ta = $ta_aktif;
$id_ta_filter = $ta['id_ta'] ?? 0;

// Get Monthly Fee Types (Jenis Bayar)
$stmt_jb = $pdo->prepare("SELECT * FROM tbl_jenis_bayar WHERE tipe='Bulanan' AND id_ta=? ORDER BY nama_jenis");
$stmt_jb->execute([$id_ta_filter]);
$jenis_bulanan = $stmt_jb->fetchAll();

// Get One-time/Bebas Fee Types
$stmt_jb_bebas = $pdo->prepare("SELECT * FROM tbl_jenis_bayar WHERE tipe='Bebas' AND id_ta=? ORDER BY nama_jenis");
$stmt_jb_bebas->execute([$id_ta_filter]);
$jenis_bebas = $stmt_jb_bebas->fetchAll();

// Academic Year months (Juli - Juni) based on TA aktif
if ($ta && $ta['tgl_mulai'] && $ta['tgl_selesai']) {
    $ta_start_y = (int)date('Y', strtotime($ta['tgl_mulai']));
    $ta_end_y = (int)date('Y', strtotime($ta['tgl_selesai']));
} else {
    $cm = (int)date('n');
    $ta_start_y = $cm >= 7 ? (int)date('Y') : (int)date('Y') - 1;
    $ta_end_y = $ta_start_y + 1;
}
$academic_months = [
    ['m' => 7, 'y' => $ta_start_y, 'short' => 'Jul'],
    ['m' => 8, 'y' => $ta_start_y, 'short' => 'Agu'],
    ['m' => 9, 'y' => $ta_start_y, 'short' => 'Sep'],
    ['m' => 10, 'y' => $ta_start_y, 'short' => 'Okt'],
    ['m' => 11, 'y' => $ta_start_y, 'short' => 'Nov'],
    ['m' => 12, 'y' => $ta_start_y, 'short' => 'Des'],
    ['m' => 1, 'y' => $ta_end_y, 'short' => 'Jan'],
    ['m' => 2, 'y' => $ta_end_y, 'short' => 'Feb'],
    ['m' => 3, 'y' => $ta_end_y, 'short' => 'Mar'],
    ['m' => 4, 'y' => $ta_end_y, 'short' => 'Apr'],
    ['m' => 5, 'y' => $ta_end_y, 'short' => 'Mei'],
    ['m' => 6, 'y' => $ta_end_y, 'short' => 'Jun'],
];

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<?php 
// Determine Snap JS URL
$is_sandbox = (strpos($setting['midtrans_client_key'], 'SB-') === 0);
$snap_js_url = $is_sandbox ? "https://app.sandbox.midtrans.com/snap/snap.js" : "https://app.midtrans.com/snap/snap.js";
?>
<script src="<?= $snap_js_url ?>" data-client-key="<?= $setting['midtrans_client_key'] ?>"></script>

<?php 
    $active_gateways = [];
    if ($setting['is_active_midtrans']) $active_gateways[] = 'Midtrans';
    if ($setting['is_active_tripay']) $active_gateways[] = 'Tripay';
    if ($setting['is_active_xendit']) $active_gateways[] = 'Xendit';
    $total_active = count($active_gateways);
?>

<div class="flex items-center justify-between mb-8">
    <div>
        <h2 class="text-2xl font-black text-white italic uppercase tracking-tighter flex items-center gap-3">
            <span class="p-2 bg-emerald-500 rounded-xl shadow-lg shadow-emerald-500/20"><i class="fas fa-wallet text-white text-sm"></i></span>
            Keuangan & Tagihan
        </h2>
        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1 ml-11">Manajemen Pembayaran Siswa</p>
    </div>
    <div class="hidden md:flex px-4 py-2 glass rounded-2xl border border-white/10 items-center gap-3">
        <span class="text-[10px] text-slate-500 uppercase font-bold tracking-widest border-r border-white/10 pr-3">Gateway:</span>
        <div class="flex gap-2">
            <?php foreach($active_gateways as $ag): ?>
                <span class="text-[10px] font-black text-emerald-400 uppercase italic bg-emerald-500/10 px-2 py-0.5 rounded-lg border border-emerald-500/20"><?= $ag ?></span>
            <?php endforeach; if(empty($active_gateways)): ?>
                <span class="text-[10px] font-black text-rose-400 uppercase italic">Offline</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- TA Filter -->
<div class="glass rounded-xl p-4 mb-6 border border-white/5 flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center border border-blue-500/30">
            <i class="fas fa-calendar-alt text-blue-400"></i>
        </div>
        <div>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Filter Tagihan</p>
            <p class="text-sm font-bold text-white">Tahun Ajaran</p>
        </div>
    </div>
    <form method="GET" class="flex-1 max-w-sm">
        <select name="ta" onchange="this.form.submit()" class="w-full bg-slate-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm font-bold text-white focus:outline-none focus:border-blue-500 cursor-pointer transition-colors shadow-inner">
            <?php foreach ($ta_list as $tl): ?>
            <option value="<?= $tl['id_ta'] ?>" <?= ($ta['id_ta']??0)==$tl['id_ta']?'selected':'' ?>><?= clean($tl['tahun']) ?> <?= $tl['status']=='aktif'?'(Aktif)':'' ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-24">
    <!-- LEFT: BILLING LIST -->
    <div class="lg:col-span-2 space-y-8">
        
        <!-- TAGIHAN BULANAN -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-2"><i class="fas fa-calendar-check text-blue-400"></i> Iuran Bulanan (SPP & Rutin)</h3>
                <span class="text-[10px] text-slate-500 font-bold italic underline decoration-blue-500/30">Klik bulan untuk masukan keranjang</span>
            </div>
            <div class="glass rounded-xl p-3 flex items-center gap-2 border border-blue-500/20">
                <i class="fas fa-calendar-alt text-blue-400 text-xs"></i>
                <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest">TA <?= clean($ta['tahun'] ?? $ta_start_y.'/'.$ta_end_y) ?></span>
                <span class="text-[9px] text-slate-500 ml-auto">Jul <?= $ta_start_y ?> — Jun <?= $ta_end_y ?></span>
            </div>

            <?php foreach ($jenis_bulanan as $jb): 
                $tarif = get_tarif($pdo, $id_siswa, $jb['id_jenis']);
                if ($tarif <= 0) continue;
            ?>
            <div class="glass rounded-3xl overflow-hidden border border-white/5 shadow-2xl">
                <div class="p-5 bg-gradient-to-r from-blue-600/10 to-transparent border-b border-white/5 flex items-center justify-between">
                    <div>
                        <span class="font-black text-white text-sm uppercase tracking-wider italic"><?= clean($jb['nama_jenis']) ?></span>
                    </div>
                    <div class="px-3 py-1 bg-blue-500/20 border border-blue-500/30 rounded-xl">
                        <span class="text-[10px] font-black text-blue-400 uppercase italic tracking-widest"><?= rupiah($tarif) ?> <span class="text-[8px] opacity-50">/ Bln</span></span>
                    </div>
                </div>
                <div class="p-5 grid grid-cols-3 sm:grid-cols-6 gap-3">
                    <?php foreach ($academic_months as $am):
                        $bln = $am['m'];
                        $nama = $am['short'];
                        $thn_bln = $am['y'];
                        $paid = $pdo->prepare("SELECT 1 FROM tbl_pembayaran WHERE id_siswa=? AND id_jenis=? AND bulan=? AND tahun=?");
                        $paid->execute([$id_siswa, $jb['id_jenis'], $bln, $thn_bln]);
                        $ok = $paid->fetch();
                        $item_id = "BULANAN-{$jb['id_jenis']}-{$bln}-{$thn_bln}";
                    ?>
                    <div class="group relative">
                        <?php if ($ok): ?>
                        <div class="text-center p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 shadow-inner">
                            <p class="text-[9px] font-black text-emerald-500/50 mb-0.5 uppercase"><?= $nama ?></p>
                            <p class="text-[7px] text-emerald-500/30"><?= $thn_bln ?></p>
                            <i class="fas fa-check-double text-emerald-400 text-xs"></i>
                        </div>
                        <?php else: ?>
                        <label class="cursor-pointer">
                            <input type="checkbox" name="item_check" 
                                data-id="<?= $jb['id_jenis'] ?>" 
                                data-bulan="<?= $bln ?>" 
                                data-tahun="<?= $thn_bln ?>" 
                                data-jumlah="<?= $tarif ?>" 
                                data-nama="<?= clean($jb['nama_jenis']) ?>" 
                                data-tipe="Bulanan"
                                onchange="toggleItem(this)"
                                class="peer hidden item-checkbox" id="<?= $item_id ?>">
                            <div class="text-center p-3 rounded-2xl bg-slate-800/40 border border-white/5 transition-all duration-300 hover:border-blue-500/50 peer-checked:bg-blue-600 peer-checked:border-blue-400 peer-checked:shadow-[0_0_15px_rgba(59,130,246,0.3)]">
                                <p class="text-[9px] font-black text-white/40 mb-0.5 uppercase peer-checked:text-white"><?= $nama ?></p>
                                <p class="text-[7px] text-white/20"><?= $thn_bln ?></p>
                                <i class="fas fa-plus text-[10px] text-white/10 peer-checked:text-white transition-all"></i>
                            </div>
                        </label>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- TAGIHAN BEBAS -->
        <div class="space-y-4">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-2"><i class="fas fa-tags text-orange-400"></i> Tagihan Non-Rutin (Bebas)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($jenis_bebas as $jb): 
                    $tarif = get_tarif($pdo, $id_siswa, $jb['id_jenis']);
                    if ($tarif <= 0) continue;

                    $tb_stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran_bebas WHERE id_siswa=? AND id_jenis=?");
                    $tb_stmt->execute([$id_siswa, $jb['id_jenis']]);
                    $total_bayar = $tb_stmt->fetchColumn();
                    $sisa = $tarif - $total_bayar;
                    $item_id_bebas = "BEBAS-{$jb['id_jenis']}";
                ?>
                <div class="glass p-6 rounded-[2rem] border border-white/5 relative overflow-hidden group shadow-xl">
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-orange-500/10 rounded-full blur-3xl group-hover:bg-orange-500/20 transition-all"></div>
                    
                    <h4 class="font-black text-white mb-1 uppercase tracking-wider text-sm italic"><?= clean($jb['nama_jenis']) ?></h4>
                    <p class="text-[9px] text-slate-500 uppercase font-black tracking-widest mb-6">Total Tagihan: <?= rupiah($tarif) ?></p>
                    
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-[8px] text-slate-600 uppercase font-bold tracking-widest">Sisa Pembayaran</p>
                            <p class="text-xl font-black <?= $sisa > 0 ? 'text-rose-400' : 'text-emerald-400' ?>"><?= rupiah($sisa) ?></p>
                        </div>
                        <?php if ($sisa > 0): ?>
                        <label class="cursor-pointer">
                            <input type="checkbox" name="item_check" 
                                data-id="<?= $jb['id_jenis'] ?>" 
                                data-bulan="0" 
                                data-tahun="<?= date('Y') ?>" 
                                data-jumlah="<?= $sisa ?>" 
                                data-nama="<?= clean($jb['nama_jenis']) ?>" 
                                data-tipe="Bebas"
                                onchange="toggleItem(this)"
                                class="peer hidden item-checkbox">
                            <div class="bg-blue-600 px-6 py-3 rounded-2xl text-[10px] font-black text-white uppercase tracking-widest italic shadow-lg shadow-blue-600/20 transition-all peer-checked:bg-emerald-600 peer-checked:shadow-emerald-600/30">
                                <span class="peer-checked:hidden">Tambah Ke Keranjang</span>
                                <span class="hidden peer-checked:inline flex items-center gap-2"><i class="fas fa-check"></i> Siap Bayar</span>
                            </div>
                        </label>
                        <?php else: ?>
                            <span class="bg-emerald-500/10 text-emerald-400 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border border-emerald-500/20 italic">Sudah Lunas</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- RIGHT: HISTORY & INFO -->
    <div class="space-y-8">
        <!-- INFO PANEL -->
        <div class="glass p-8 rounded-[2.5rem] border border-blue-500/20 bg-gradient-to-br from-blue-600/10 to-transparent shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl -mr-10 -mt-10"></div>
            <h4 class="text-white font-black italic uppercase tracking-widest mb-6 flex items-center justify-between border-b border-white/5 pb-4">
                <span>Alur Pembayaran</span>
                <i class="fas fa-shield-alt text-blue-400 text-sm"></i>
            </h4>
            <ul class="space-y-6">
                <li class="flex gap-4">
                    <div class="w-8 h-8 rounded-xl bg-blue-500/20 flex-shrink-0 flex items-center justify-center text-[11px] font-black text-blue-400 border border-blue-500/30 shadow-lg">01</div>
                    <p class="text-[11px] text-slate-400 leading-relaxed font-bold uppercase tracking-tight">Centang bulan atau tagihan yang ingin Anda bayar sekaligus.</p>
                </li>
                <li class="flex gap-4">
                    <div class="w-8 h-8 rounded-xl bg-blue-500/20 flex-shrink-0 flex items-center justify-center text-[11px] font-black text-blue-400 border border-blue-500/30 shadow-lg">02</div>
                    <p class="text-[11px] text-slate-400 leading-relaxed font-bold uppercase tracking-tight">Pilih gerbang pembayaran yang aktif melalui tombol di bilah bawah.</p>
                </li>
                <li class="flex gap-4">
                    <div class="w-8 h-8 rounded-xl bg-blue-500/20 flex-shrink-0 flex items-center justify-center text-[11px] font-black text-blue-400 border border-blue-500/30 shadow-lg">03</div>
                    <p class="text-[11px] text-slate-400 leading-relaxed font-bold uppercase tracking-tight">Selesaikan di portal. Saldo dan bukti bayar akan terbit otomatis.</p>
                </li>
            </ul>
        </div>

        <!-- RECENT HISTORY -->
        <div class="glass rounded-[2rem] overflow-hidden border border-white/5 shadow-2xl">
            <div class="p-6 border-b border-white/5 bg-white/3 flex items-center justify-between">
                <h3 class="text-[10px] font-black text-white uppercase tracking-widest italic">Transaksi Terakhir</h3>
                <i class="fas fa-history text-slate-600 text-xs"></i>
            </div>
            <div class="p-0">
                <?php 
                $stmt = $pdo->prepare("
                    SELECT p.id_pembayaran as id, p.tanggal_bayar, p.jumlah_bayar, p.cara_bayar, j.nama_jenis, 'Bulanan' as tipe 
                    FROM tbl_pembayaran p 
                    JOIN tbl_jenis_bayar j ON p.id_jenis=j.id_jenis 
                    WHERE p.id_siswa=?
                    UNION ALL
                    SELECT pb.id_bebas as id, pb.tanggal_bayar, pb.jumlah_bayar, pb.cara_bayar, j.nama_jenis, 'Bebas' as tipe 
                    FROM tbl_pembayaran_bebas pb 
                    JOIN tbl_jenis_bayar j ON pb.id_jenis=j.id_jenis 
                    WHERE pb.id_siswa=?
                    ORDER BY tanggal_bayar DESC LIMIT 5
                ");
                $stmt->execute([$id_siswa, $id_siswa]);
                $riwayat = $stmt->fetchAll();
                foreach ($riwayat as $r):
                ?>
                <div class="p-5 border-b border-white/5 last:border-0 hover:bg-white/5 transition-all group relative">
                    <a href="../admin/cetak_kwitansi.php?<?= $r['tipe'] == 'Bulanan' ? 'id' : 'id_bebas' ?>=<?= $r['id'] ?>" target="_blank" class="absolute top-5 right-5 w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-400 opacity-0 group-hover:opacity-100 transition-all hover:bg-blue-500 hover:text-white" title="Cetak Kwitansi">
                        <i class="fas fa-print text-[10px]"></i>
                    </a>
                    <div class="flex justify-between items-start mb-2 mr-8">
                        <span class="text-[11px] font-black text-white uppercase tracking-tighter italic group-hover:text-blue-400 transition-colors"><?= clean($r['nama_jenis']) ?></span>
                        <span class="text-[11px] font-black text-emerald-400 italic"><?= rupiah($r['jumlah_bayar']) ?></span>
                    </div>
                    <div class="flex justify-between items-center px-2 py-1 bg-white/3 rounded-lg mr-8">
                        <span class="text-[8px] text-slate-500 font-black uppercase"><?= tgl_indo($r['tanggal_bayar']) ?></span>
                        <div class="flex items-center gap-1">
                            <div class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse"></div>
                            <span class="text-[8px] text-slate-400 font-black uppercase tracking-widest"><?= $r['cara_bayar'] ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; if(!$riwayat) echo '<div class="p-10 text-center text-slate-600 italic text-[10px] font-bold uppercase tracking-widest">Belum ada riwayat pembayaran.</div>'; ?>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     FLOATING CART BAR (GLASSMORPHISM)
     ========================================================================= -->
<div id="cart-bar" class="fixed bottom-8 left-1/2 -translate-x-1/2 w-[90%] md:w-[700px] bg-slate-900/80 backdrop-blur-2xl border border-white/10 rounded-[2.5rem] p-4 z-[9990] shadow-[0_20px_50px_rgba(0,0,0,0.5)] flex items-center justify-between transition-all duration-500 translate-y-40 opacity-0 pointer-events-none">
    <div class="flex items-center gap-6 pl-4">
        <div class="relative">
            <div class="w-14 h-14 bg-blue-600 rounded-2xl rotate-3 flex items-center justify-center shadow-lg shadow-blue-500/20 border border-white/10">
                <i class="fas fa-shopping-basket text-white text-xl"></i>
            </div>
            <div id="item-count" class="absolute -top-2 -right-2 w-7 h-7 bg-rose-500 rounded-full flex items-center justify-center text-[10px] font-bold text-white border-2 border-slate-900 shadow-lg">0</div>
        </div>
        <div>
            <p class="text-[8px] text-slate-500 font-bold uppercase tracking-[0.2em] mb-1">Total Siap Bayar</p>
            <h3 id="total-text" class="text-2xl font-black text-white italic leading-tight">Rp 0</h3>
        </div>
    </div>

    <div class="flex items-center gap-2 pr-2">
        <button onclick="clearCart()" class="w-12 h-12 rounded-2xl bg-white/5 border border-white/5 flex items-center justify-center text-slate-500 hover:bg-rose-500/10 hover:text-rose-400 transition-all font-black text-xs">
            <i class="fas fa-trash-alt"></i>
        </button>
        <button onclick="checkoutCart()" class="px-8 py-4 bg-emerald-600 hover:bg-emerald-500 rounded-[1.5rem] font-black text-xs text-white uppercase italic tracking-widest shadow-xl shadow-emerald-500/20 transition-all active:scale-95 flex items-center gap-3">
            <span>BAYAR SEKARANG</span>
            <i class="fas fa-chevron-right text-[10px] animate-bounce-x"></i>
        </button>
    </div>
</div>

<!-- LOADING OVERLAY -->
<div id="pay-loader" class="hidden fixed inset-0 bg-slate-900/90 backdrop-blur-md z-[9999] flex-col items-center justify-center">
    <div class="relative">
        <i class="fas fa-spinner fa-spin text-6xl text-blue-500 mb-8 drop-shadow-[0_0_20px_rgba(59,130,246,0.3)]"></i>
    </div>
    <h3 class="text-white font-black italic uppercase tracking-[0.5em] animate-pulse text-lg">PROSES TRANSAKSI...</h3>
    <p class="text-slate-500 text-[10px] mt-4 uppercase font-black tracking-widest border-t border-white/5 pt-4">Jangan tutup atau segarkan halaman ini</p>
</div>

<script>
let cartItems = [];

function toggleItem(el) {
    const item = {
        id_jenis: el.dataset.id,
        bulan: el.dataset.bulan,
        tahun: el.dataset.tahun,
        jumlah: parseInt(el.dataset.jumlah),
        nama_jenis: el.dataset.nama,
        tipe: el.dataset.tipe,
        domId: el.id
    };

    if (el.checked) {
        cartItems.push(item);
    } else {
        cartItems = cartItems.filter(i => !(i.id_jenis === item.id_jenis && i.bulan === item.bulan));
    }
    
    updateCartUI();
}

function updateCartUI() {
    const bar = document.getElementById('cart-bar');
    const totalText = document.getElementById('total-text');
    const countText = document.getElementById('item-count');

    const total = cartItems.reduce((acc, curr) => acc + curr.jumlah, 0);

    if (cartItems.length > 0) {
        bar.classList.remove('translate-y-40', 'opacity-0', 'pointer-events-none');
        bar.classList.add('translate-y-0', 'opacity-100', 'pointer-events-auto');
    } else {
        bar.classList.remove('translate-y-0', 'opacity-100', 'pointer-events-auto');
        bar.classList.add('translate-y-40', 'opacity-0', 'pointer-events-none');
    }

    countText.innerText = cartItems.length;
    totalText.innerText = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(total);
}

function clearCart() {
    document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
    cartItems = [];
    updateCartUI();
}

async function checkoutCart() {
    const totalActive = <?= $total_active ?>;
    const activeGateways = <?= json_encode($active_gateways) ?>;

    if (totalActive === 0) {
        Swal.fire('Oops!', 'Pembayaran online sedang nonaktif.', 'warning');
        return;
    }

    let selectedGateway = activeGateways[0];

    if (totalActive > 1) {
        const { value: gateway } = await Swal.fire({
            title: 'PILIH METODE BAYAR',
            input: 'select',
            inputOptions: {
                'Midtrans': 'MIDTRANS (QRIS, VA, GOPAY)',
                'Tripay': 'TRIPAY (QRIS, VA Retail)',
                'Xendit': 'XENDIT (VA & Invoice)'
            },
            inputPlaceholder: 'Pilih Gateway...',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#1e293b',
            background: '#0f172a',
            color: '#fff',
            customClass: {
                popup: 'rounded-[2rem] border border-white/10 glass',
                input: 'bg-slate-800 border-white/10 text-white rounded-xl'
            }
        });
        if (!gateway) return;
        selectedGateway = gateway;
    }

    const loader = document.getElementById('pay-loader');
    loader.classList.remove('hidden');
    loader.classList.add('flex');

    const formData = new FormData();
    formData.append('id_siswa', '<?= $id_siswa ?>');
    formData.append('items', JSON.stringify(cartItems));
    formData.append('gateway', selectedGateway);

    try {
        const response = await fetch('<?= BASE_URL ?>api/payment.php?action=create', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.status === 'ok') {
            if (data.gateway === 'Midtrans') {
                snap.pay(data.token, {
                    onSuccess: function(result) { location.reload(); },
                    onPending: function(result) { location.reload(); },
                    onError: function(result) { Swal.fire("Gagal", "Pembayaran gagal!", "error").then(() => location.reload()); },
                    onClose: function() { location.reload(); }
                });
            } else {
                window.open(data.checkout_url, '_blank');
                Swal.fire({
                    title: 'Portal Dibuka',
                    text: 'Silakan selesaikan pembayaran di tab baru. Klik tombol di bawah jika sudah selesai.',
                    icon: 'info',
                    confirmButtonText: 'SAYA SUDAH BAYAR',
                    confirmButtonColor: '#10b981',
                    background: '#0f172a',
                    color: '#fff',
                    customClass: { popup: 'rounded-[2rem] border border-white/10 glass' }
                }).then(() => location.reload());
            }
        } else {
            Swal.fire('Gagal!', data.message, 'error');
        }
    } catch (error) {
        Swal.fire('Error!', error.message || 'Server error.', 'error');
    } finally {
        loader.classList.add('hidden');
        loader.classList.remove('flex');
    }
}
</script>

<style>
@keyframes bounce-x {
    0%, 100% { transform: translateX(0); }
    50% { transform: translateX(5px); }
}
.animate-bounce-x {
    animation: bounce-x 1s infinite;
}
</style>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
