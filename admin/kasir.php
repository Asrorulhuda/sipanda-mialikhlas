<?php
$page_title = 'E-Kantin POS';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin','bendahara','kasir']);
cek_fitur('ekantin');

// Handle Transaction
if (isset($_POST['proses_bayar'])) {
    $items = json_decode($_POST['items_json'], true);
    $cara_bayar = $_POST['cara_bayar']; // 'Cash' atau 'RFID'
    $rfid_uid = $_POST['rfid_uid'] ?? '';
    $id_user = $_POST['customer_id'] ?? NULL;
    $role = $_POST['customer_role'] ?? NULL;
    $total = 0;

    foreach ($items as $item) $total += $item['harga'] * $item['qty'];

    if ($cara_bayar == 'RFID') {
        if (empty($rfid_uid)) { flash('msg', 'RFID tidak boleh kosong untuk pembayaran RFID', 'danger'); header('Location: kasir.php'); exit; }
        
        // Cek Siswa
        $siswa = $pdo->prepare("SELECT id_siswa, nama, saldo_jajan, limit_jajan_harian FROM tbl_siswa WHERE rfid_uid=? OR uuid_kartu=?");
        $siswa->execute([$rfid_uid, $rfid_uid]); $s = $siswa->fetch();
        
        if ($s) {
            // Check Limit
            if ($s['limit_jajan_harian'] > 0) {
                $q_spent = $pdo->prepare("SELECT SUM(total) as total_spent FROM tbl_order WHERE id_siswa=? AND DATE(tanggal) = CURDATE()");
                $q_spent->execute([$s['id_siswa']]);
                $spent = $q_spent->fetch()['total_spent'] ?? 0;
                if ($spent + $total > $s['limit_jajan_harian']) {
                    flash('msg', 'Limit harian tercapai! Sisa limit: '.rupiah($s['limit_jajan_harian'] - $spent), 'danger');
                    header('Location: kasir.php'); exit;
                }
            }
            if ($s['saldo_jajan'] < $total) {
                flash('msg', 'Saldo jajan tidak cukup! Saldo: '.rupiah($s['saldo_jajan']), 'danger');
                header('Location: kasir.php'); exit;
            }
            // Update Balance
            $pdo->prepare("UPDATE tbl_siswa SET saldo_jajan = saldo_jajan - ? WHERE id_siswa = ?")->execute([$total, $s['id_siswa']]);
            $id_user = $s['id_siswa']; $role = 'siswa';
        } else {
            // Cek Guru
            $guru = $pdo->prepare("SELECT id_guru, nama, saldo_jajan FROM tbl_guru WHERE rfid_uid=? OR uuid_kartu=?");
            $guru->execute([$rfid_uid, $rfid_uid]); $g = $guru->fetch();
            if ($g) {
                if ($g['saldo_jajan'] < $total) {
                    flash('msg', 'Saldo jajan tidak cukup!', 'danger');
                    header('Location: kasir.php'); exit;
                }
                $pdo->prepare("UPDATE tbl_guru SET saldo_jajan = saldo_jajan - ? WHERE id_guru = ?")->execute([$total, $g['id_guru']]);
                $id_user = $g['id_guru']; $role = 'guru';
            } else {
                flash('msg', 'Kartu RFID tidak terdaftar!', 'danger');
                header('Location: kasir.php'); exit;
            }
        }
    }

    // Record Order
    $kode_order = 'ORD-'.time();
    $target_siswa_id = ($role??'')=='siswa' ? $id_user : NULL;
    $pdo->prepare("INSERT INTO tbl_order (kode_order, id_siswa, total, cara_bayar, tanggal) VALUES (?, ?, ?, ?, NOW())")
        ->execute([$kode_order, $target_siswa_id, $total, $cara_bayar]);
    $id_order = $pdo->lastInsertId();
    $item_list_str = "";

    foreach ($items as $item) {
        $sub = $item['harga'] * $item['qty'];
        $item_list_str .= "• {$item['qty']} x {$item['nama']} - ".rupiah($sub)."\n";
        
        $pdo->prepare("INSERT INTO tbl_order_detail (id_order, id_produk, qty, harga, subtotal) VALUES (?, ?, ?, ?, ?)")
            ->execute([$id_order, $item['id']??NULL, $item['qty'], $item['harga'], $sub]);
        
        // Record legacy summary for display
        $pdo->prepare("INSERT INTO tbl_pos_transaksi (nama_item, harga, qty, total, tanggal, kasir) VALUES (?, ?, ?, ?, NOW(), ?)")
            ->execute([$item['nama'], $item['harga'], $item['qty'], $sub, clean($_SESSION['nama'])]);
            
        // Reduce stock if product exists
        if (isset($item['id'])) {
            // Get product owner and base price
            $p_info = $pdo->prepare("SELECT id_guru_penjual, harga_dasar FROM tbl_produk WHERE id_produk = ?");
            $p_info->execute([$item['id']]);
            $pi = $p_info->fetch();

            if ($pi && $pi['id_guru_penjual']) {
                $pendapatan_guru = $pi['harga_dasar'] * $item['qty'];
                $pdo->prepare("UPDATE tbl_guru SET saldo_penjual = saldo_penjual + ? WHERE id_guru = ?")
                    ->execute([$pendapatan_guru, $pi['id_guru_penjual']]);
            }

            $pdo->prepare("UPDATE tbl_produk SET stok = stok - ? WHERE id_produk = ?")->execute([$item['qty'], $item['id']]);
        }
    }

    // Notification Logic (WA & PWA)
    if (isset($id_user)) {
        $msg_wa = "🏫 *STRUK BELANJA E-KANTIN*\n\n";
        $msg_wa .= "*Rincian Belanja:*\n" . $item_list_str;
        $msg_wa .= "\n*Total: ".rupiah($total)."*";
        $msg_wa .= "\nTanggal: ".date('d/M/Y H:i');
        
        $msg_pwa = "Berhasil belanja senilai ".rupiah($total)." di E-Kantin.";
        
        if ($role == 'siswa') {
            $u = $pdo->prepare("SELECT nama, no_hp_ortu, saldo_jajan FROM tbl_siswa WHERE id_siswa=?");
            $u->execute([$id_user]); $ud = $u->fetch();
            $target_wa = $ud['no_hp_ortu'];
            $msg_wa .= "\n\nAnanda: *{$ud['nama']}*\nSisa Saldo: *".rupiah($ud['saldo_jajan'])."*";
        } else {
            $u = $pdo->prepare("SELECT nama, no_hp, saldo_jajan FROM tbl_guru WHERE id_guru=?");
            $u->execute([$id_user]); $ud = $u->fetch();
            $target_wa = $ud['no_hp'];
            $msg_wa .= "\n\nBapak/Ibu: *{$ud['nama']}*\nSisa Saldo: *".rupiah($ud['saldo_jajan'])."*";
        }

        // WA Notif
        require_once __DIR__ . '/../api/wa_helper.php';
        if (!empty($target_wa)) send_wa($target_wa, $msg_wa);

        // PWA Notif
        $pdo->prepare("INSERT INTO tbl_notifikasi_pwa (id_user, role, judul, pesan, is_read) VALUES (?, ?, ?, ?, 0)")
            ->execute([$id_user, $role, 'Belanja E-Kantin Berhasil', $msg_pwa]);
    }

    flash('msg', 'Transaksi Berhasil! Total: '.rupiah($total), 'success');
    header('Location: kasir.php'); exit;
}

$produk = $pdo->query("SELECT * FROM tbl_produk WHERE stok > 0 ORDER BY nama_produk ASC")->fetchAll();
$all_kategori = $pdo->query("SELECT * FROM tbl_kategori_produk ORDER BY nama_kategori ASC")->fetchAll();
$riwayat = $pdo->query("SELECT * FROM tbl_pos_transaksi ORDER BY id DESC LIMIT 15")->fetchAll();
$all_customers = $pdo->query("SELECT id_siswa as id, nama, 'siswa' as role FROM tbl_siswa UNION SELECT id_guru as id, nama, 'guru' as role FROM tbl_guru ORDER BY nama ASC")->fetchAll();

// Count products per category
$category_counts = [];
foreach ($produk as $p) {
    if (!isset($category_counts[$p['kategori']])) $category_counts[$p['kategori']] = 0;
    $category_counts[$p['kategori']]++;
}
$total_produk = count($produk);

require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<div id="loading_overlay" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-[9999] hidden flex items-center justify-center">
    <div class="text-center">
        <div class="w-16 h-16 border-4 border-emerald-500/20 border-t-emerald-500 rounded-full animate-spin mx-auto mb-4"></div>
        <p class="text-white font-black italic tracking-widest animate-pulse">MEMPROSES TRANSAKSI...</p>
        <p class="text-slate-500 text-xs mt-2">Mohon tunggu sebentar, jangan tutup halaman ini.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Cari Produk -->
    <div class="lg:col-span-2 space-y-6">
        <div class="glass rounded-2xl p-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h4 class="font-bold text-lg">Pilih Produk</h4>
                    <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Pilih kategori atau scan barcode</p>
                </div>
                <div class="flex gap-2 w-full md:w-auto">
                    <div class="relative flex-1 md:w-48">
                        <i class="fas fa-barcode absolute left-3 top-1/2 -translate-y-1/2 text-emerald-500 text-xs"></i>
                        <input type="text" id="barcode_scan" placeholder="Scan Barcode..." class="w-full bg-emerald-500/10 border border-emerald-500/30 rounded-xl pl-9 pr-4 py-2 text-sm focus:border-emerald-500 outline-none transition-all placeholder:text-emerald-700/50" autofocus>
                    </div>
                    <div class="relative flex-1 md:w-64">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="search_produk" placeholder="Cari nama..." class="w-full bg-slate-800/50 border border-white/10 rounded-xl pl-9 pr-4 py-2 text-sm focus:border-blue-500/50 outline-none transition-all">
                    </div>
                </div>
            </div>

            <!-- Category Tabs -->
            <div class="flex gap-2 overflow-x-auto pb-4 mb-6 no-scrollbar">
                <button onclick="filterCategory('All', this)" class="category-tab px-4 py-2 rounded-xl text-xs font-bold transition-all bg-emerald-600 text-white border border-emerald-500/50 whitespace-nowrap">
                    SEMUA <span class="ml-1 opacity-60"><?= $total_produk ?></span>
                </button>
                <?php foreach($all_kategori as $cat): 
                    $count = $category_counts[$cat['nama_kategori']] ?? 0;
                    if ($count == 0) continue; 
                ?>
                <button onclick="filterCategory('<?= clean($cat['nama_kategori']) ?>', this)" class="category-tab px-4 py-2 rounded-xl text-xs font-bold transition-all bg-white/5 text-slate-400 border border-white/5 hover:bg-white/10 whitespace-nowrap uppercase">
                    <?= clean($cat['nama_kategori']) ?> <span class="ml-1 opacity-60"><?= $count ?></span>
                </button>
                <?php endforeach; ?>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                <?php foreach ($produk as $p): ?>
                <div onclick="addToCart(<?= htmlspecialchars(json_encode(['id'=>$p['id_produk'], 'nama'=>$p['nama_produk'], 'harga'=>(float)$p['harga']])) ?>)" class="product-card group bg-white/5 border border-white/5 hover:border-emerald-500/30 rounded-2xl p-4 transition-all cursor-pointer hover:bg-emerald-500/5" data-category="<?= clean($p['kategori']) ?>">
                    <div class="aspect-square bg-slate-800 rounded-xl mb-3 flex items-center justify-center overflow-hidden">
                        <?php if ($p['gambar']): ?>
                            <img src="../gambar/produk/<?= $p['gambar'] ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-utensils text-slate-600 text-2xl group-hover:scale-110 transition-transform"></i>
                        <?php endif; ?>
                    </div>
                    <h5 class="text-sm font-semibold truncate mb-1"><?= clean($p['nama_produk']) ?></h5>
                    <p class="text-xs text-slate-400 mb-2"><?= clean($p['kategori']) ?> • Stok: <?= $p['stok'] ?></p>
                    <p class="text-emerald-400 font-bold"><?= rupiah($p['harga']) ?></p>
                </div>
                <?php endforeach; ?>
                
                <!-- Manual Item -->
                <div onclick="addManual()" class="group bg-white/5 border border-white/5 border-dashed hover:border-blue-500/30 rounded-2xl p-4 transition-all cursor-pointer hover:bg-blue-500/5 flex flex-col items-center justify-center text-center">
                    <div class="w-12 h-12 rounded-full bg-blue-500/10 flex items-center justify-center mb-3">
                        <i class="fas fa-plus text-blue-400"></i>
                    </div>
                    <span class="text-xs font-medium text-slate-300">Item Manual</span>
                </div>
            </div>
        </div>

        <!-- Riwayat Singkat -->
        <div class="glass rounded-2xl p-6">
            <h4 class="font-bold text-sm mb-4"><i class="fas fa-history text-slate-400 mr-2"></i>Transaksi Terakhir</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-slate-400 border-b border-white/5"><th class="pb-3">Waktu</th><th class="pb-3">Item</th><th class="pb-3">Total</th><th class="pb-3">Kasir</th></tr></thead>
                    <tbody><?php foreach ($riwayat as $r): ?>
                    <tr class="border-b border-white/5"><td class="py-3 text-xs"><?= date('H:i', strtotime($r['tanggal'])) ?></td><td><?= clean($r['nama_item']) ?></td><td class="font-bold"><?= rupiah($r['total']) ?></td><td class="text-xs text-slate-400"><?= clean($r['kasir']) ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Checkout -->
    <div class="space-y-6">
        <div class="glass rounded-2xl p-6 sticky top-6">
            <div class="flex items-center justify-between mb-6">
                <h4 class="font-bold text-lg">Keranjang</h4>
                <button onclick="clearCart()" class="text-xs text-red-400 hover:text-red-300 transition-colors">Hapus Semua</button>
            </div>

            <div id="cart_items" class="space-y-3 mb-6 min-h-[150px] max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                <!-- Cart loaded via JS -->
                <div class="text-center py-10 text-slate-500"><i class="fas fa-shopping-basket text-3xl mb-3 block opacity-20"></i><span class="text-xs">Keranjang masih kosong</span></div>
            </div>

            <div class="border-t border-white/10 pt-4 space-y-3 mb-6">
                <div class="flex justify-between text-slate-400 text-sm"><span>Subtotal</span><span id="subtotal_display">Rp 0</span></div>
                <div class="flex justify-between items-center"><span class="font-bold">TOTAL</span><span id="total_display" class="text-2xl font-black text-emerald-400 italic">Rp 0</span></div>
            </div>

            <form method="POST" id="form_bayar">
                <input type="hidden" name="items_json" id="items_json">
                
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Metode Pembayaran</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="cara_bayar" value="Cash" checked class="peer hidden">
                            <div class="p-3 text-center rounded-xl bg-white/5 border border-white/5 peer-checked:bg-blue-500/10 peer-checked:border-blue-500/50 transition-all">
                                <i class="fas fa-money-bill-wave mb-1 block"></i><span class="text-xs font-bold">Tunai</span>
                            </div>
                        </label>
                        <label class="cursor-pointer" onclick="focusRFID()">
                            <input type="radio" name="cara_bayar" value="RFID" class="peer hidden">
                            <div class="p-3 text-center rounded-xl bg-white/5 border border-white/5 peer-checked:bg-emerald-500/10 peer-checked:border-emerald-500/50 transition-all">
                                <i class="fas fa-id-card mb-1 block"></i><span class="text-xs font-bold">RFID Card</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div id="cash_customer_section" class="mb-4">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Pelanggan (Opsional)</label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-[10px]"></i>
                        <input type="text" id="cash_customer_search" list="customer_list" placeholder="Cari nama siswa/guru..." class="w-full bg-white/5 border border-white/10 rounded-xl pl-8 pr-4 py-2 text-xs focus:border-blue-500 outline-none transition-all">
                        <datalist id="customer_list">
                            <?php foreach($all_customers as $c): ?>
                            <option value="<?= clean($c['nama']) ?>" data-id="<?= $c['id'] ?>" data-role="<?= $c['role'] ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <input type="hidden" name="customer_id" id="customer_id">
                    <input type="hidden" name="customer_role" id="customer_role">
                </div>

                <div id="rfid_section" class="mb-6 hidden animate-bounce-subtle">
                    <label class="block text-xs font-bold text-emerald-400 mb-2 italic"><i class="fas fa-wifi rotate-90 mr-1"></i>Tap Kartu RFID Sekarang</label>
                    <input type="text" name="rfid_uid" id="rfid_uid" placeholder="Scanning..." class="w-full bg-emerald-500/10 border border-emerald-500/30 rounded-xl px-4 py-3 text-center font-mono focus:border-emerald-500 outline-none mb-3" autocomplete="off">
                    
                    <!-- RFID Owner Info Card -->
                    <div id="rfid_info" class="hidden animate-fade-in p-4 bg-emerald-500/5 border border-emerald-500/20 rounded-2xl">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-400 text-xs">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p id="rfid_nama" class="text-sm font-black text-white truncate">-</p>
                                <p id="rfid_status" class="text-[10px] text-emerald-500 font-bold uppercase tracking-widest">-</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[9px] text-slate-500 uppercase font-black">Saldo</p>
                                <p id="rfid_saldo" class="text-xs font-black text-emerald-400">-</p>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" name="proses_bayar" onclick="return validateCheckout()" class="w-full py-4 bg-emerald-600 hover:bg-emerald-500 rounded-2xl font-bold shadow-lg shadow-emerald-900/20 transition-all active:scale-95 disabled:opacity-50 disabled:grayscale" id="btn_submit" disabled>
                    <i class="fas fa-check-circle mr-2"></i>Selesaikan Transaksi
                </button>
            </form>
        </div>
    </div>
</div>

<script>
let cart = [];

function addToCart(produk) {
    const existing = cart.find(item => item.id === produk.id && produk.id !== null);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({...produk, qty: 1});
    }
    renderCart();
}

function addManual() {
    const nama = prompt("Nama Item:");
    const harga = parseInt(prompt("Harga:"));
    if (nama && harga) {
        cart.push({id: null, nama, harga, qty: 1});
        renderCart();
    }
}

function removeItem(index) {
    cart.splice(index, 1);
    renderCart();
}

function updateQty(index, delta) {
    cart[index].qty += delta;
    if (cart[index].qty <= 0) cart.splice(index, 1);
    renderCart();
}

function clearCart() {
    if (confirm('Kosongkan keranjang?')) {
        cart = [];
        renderCart();
    }
}

function renderCart() {
    const container = document.getElementById('cart_items');
    if (cart.length === 0) {
        container.innerHTML = `<div class="text-center py-10 text-slate-500"><i class="fas fa-shopping-basket text-3xl mb-3 block opacity-20"></i><span class="text-xs">Keranjang masih kosong</span></div>`;
        document.getElementById('total_display').textContent = 'Rp 0';
        document.getElementById('subtotal_display').textContent = 'Rp 0';
        document.getElementById('btn_submit').disabled = true;
        return;
    }

    let html = '', total = 0;
    cart.forEach((item, i) => {
        const sub = item.harga * item.qty;
        total += sub;
        html += `
        <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl border border-white/5">
            <div class="flex-1 min-w-0 pr-2">
                <p class="text-xs font-bold truncate">${item.nama}</p>
                <p class="text-[10px] text-slate-400">${item.harga.toLocaleString('id-ID', {style:'currency', currency:'IDR'})}</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center bg-slate-800 rounded-lg px-2 py-1">
                    <button onclick="updateQty(${i}, -1)" class="text-slate-400 hover:text-white px-1 text-xs"><i class="fas fa-minus"></i></button>
                    <span class="text-xs font-bold w-6 text-center">${item.qty}</span>
                    <button onclick="updateQty(${i}, 1)" class="text-slate-400 hover:text-white px-1 text-xs"><i class="fas fa-plus"></i></button>
                </div>
                <span class="text-xs font-black text-emerald-400 w-20 text-right">${sub.toLocaleString('id-ID', {style:'currency', currency:'IDR'})}</span>
            </div>
        </div>`;
    });
    container.innerHTML = html;
    document.getElementById('total_display').textContent = total.toLocaleString('id-ID', {style:'currency', currency:'IDR'});
    document.getElementById('subtotal_display').textContent = total.toLocaleString('id-ID', {style:'currency', currency:'IDR'});
    document.getElementById('btn_submit').disabled = false;
    document.getElementById('items_json').value = JSON.stringify(cart);
}

document.querySelectorAll('input[name="cara_bayar"]').forEach(radio => {
    radio.addEventListener('change', (e) => {
        const rfidSection = document.getElementById('rfid_section');
        const cashSection = document.getElementById('cash_customer_section');
        if (e.target.value === 'RFID') {
            rfidSection.classList.remove('hidden');
            cashSection.classList.add('hidden');
            document.getElementById('rfid_uid').focus();
        } else {
            rfidSection.classList.add('hidden');
            cashSection.classList.remove('hidden');
            // Reset customer value to be safe
            document.getElementById('customer_id').value = '';
            document.getElementById('customer_role').value = '';
        }
    });
});

// Cash Customer Selection Logic
document.getElementById('cash_customer_search').addEventListener('input', function(e) {
    const val = this.value;
    const list = document.getElementById('customer_list');
    const options = list.options;
    
    for (let i = 0; i < options.length; i++) {
        if (options[i].value === val) {
            document.getElementById('customer_id').value = options[i].getAttribute('data-id');
            document.getElementById('customer_role').value = options[i].getAttribute('data-role');
            return;
        }
    }
    // If not matching any option, clear the hidden IDs (Anonymous mode)
    document.getElementById('customer_id').value = '';
    document.getElementById('customer_role').value = '';
});

function focusRFID() {
    const input = document.getElementById('rfid_uid');
    input.value = '';
    document.getElementById('rfid_info').classList.add('hidden');
    setTimeout(() => input.focus(), 150);
}

// RFID Realtime Lookup
['input', 'blur'].forEach(evt => {
    document.getElementById('rfid_uid').addEventListener(evt, function(e) {
        const rfid = this.value.trim();
        if (rfid.length >= 8) { // RFID UIDs are usually 8-10 chars
            fetch(`../api/rfid.php?action=cek_saldo&rfid=${rfid}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'ok') {
                    const info = document.getElementById('rfid_info');
                    document.getElementById('rfid_nama').textContent = data.nama;
                    document.getElementById('rfid_status').textContent = data.role === 'siswa' ? 'Siswa SIPANDA' : 'Guru / Staf';
                    document.getElementById('rfid_saldo').textContent = new Intl.NumberFormat('id-ID', {style:'currency', currency:'IDR', maximumFractionDigits:0}).format(data.saldo);
                    info.classList.remove('hidden');
                    
                    // Visual feedback success
                    this.classList.remove('border-emerald-500/30', 'bg-emerald-500/10');
                    this.classList.add('border-emerald-400', 'bg-emerald-400/20');
                } else {
                    document.getElementById('rfid_info').classList.add('hidden');
                }
            });
        } else {
            document.getElementById('rfid_info').classList.add('hidden');
            this.classList.add('border-emerald-500/30', 'bg-emerald-500/10');
            this.classList.remove('border-emerald-400', 'bg-emerald-400/20');
        }
    });
});

// Barcode Scanner Logic
const allProducts = <?= json_encode($produk) ?>;
const barcodeInput = document.getElementById('barcode_scan');

barcodeInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const sku = this.value.trim();
        if (sku) {
            const p = allProducts.find(item => item.sku === sku);
            if (p) {
                addToCart({id: p.id_produk, nama: p.nama_produk, harga: parseFloat(p.harga)});
                // Notify user
                this.classList.add('bg-emerald-500/30');
                setTimeout(() => this.classList.remove('bg-emerald-500/30'), 300);
            } else {
                this.classList.add('bg-red-500/30');
                setTimeout(() => this.classList.remove('bg-red-500/30'), 300);
            }
            this.value = '';
        }
    }
});

// Filter Category Tab
function filterCategory(cat, btn) {
    // UI Update
    document.querySelectorAll('.category-tab').forEach(t => {
        t.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-500/50');
        t.classList.add('bg-white/5', 'text-slate-400', 'border-white/5');
    });
    btn.classList.add('bg-emerald-600', 'text-white', 'border-emerald-500/50');
    btn.classList.remove('bg-white/5', 'text-slate-400', 'border-white/5');

    // Filter Logic
    document.querySelectorAll('.product-card').forEach(card => {
        if (cat === 'All') {
            card.style.display = 'block';
        } else {
            card.style.display = card.getAttribute('data-category') === cat ? 'block' : 'none';
        }
    });

    // Reset Search
    document.getElementById('search_produk').value = '';
}

// Search Filter
document.getElementById('search_produk').addEventListener('input', function(e) {
    const q = e.target.value.toLowerCase();
    document.querySelectorAll('.product-card').forEach(card => {
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(q) ? 'block' : 'none';
    });
    
    // Reset Category Tabs to All
    if (q) {
        const allBtn = document.querySelector('.category-tab');
        if (allBtn && !allBtn.classList.contains('bg-emerald-600')) {
             // We don't want to trigger full filter, just visually reset if searching
        }
    }
});

function validateCheckout() {
    if (cart.length === 0) return false;
    const method = document.querySelector('input[name="cara_bayar"]:checked').value;
    if (method === 'RFID' && !document.getElementById('rfid_uid').value) {
        alert('Silakan scan kartu RFID dulu!');
        return false;
    }
    if (confirm('Lanjutkan transaksi senilai ' + document.getElementById('total_display').textContent + '?')) {
        document.getElementById('loading_overlay').classList.remove('hidden');
        document.getElementById('loading_overlay').classList.add('flex');
        return true;
    }
    return false;
}
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
.animate-bounce-subtle { animation: bounce-subtle 2s infinite; }
@keyframes bounce-subtle { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-3px); } }
</style>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
