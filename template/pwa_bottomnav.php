<?php
/**
 * PWA Bottom Navigation Bar
 * Hanya muncul ketika dibuka dalam mode standalone (Aplikasi Native)
 */
$role = $_SESSION['role'] ?? '';
$current = basename($_SERVER['PHP_SELF']);

// Tentukan menu navigasi bawah berdasarkan role
$bottom_nav = [];

if ($role === 'admin' || $role === 'kepsek') {
    $bottom_nav = [
        ['icon' => 'fa-home', 'label' => 'Home', 'url' => 'index-admin.php'],
        ['icon' => 'fa-wallet', 'label' => 'Bayar', 'url' => 'admin/pembayaran.php'],
        ['icon' => 'fa-users', 'label' => 'Siswa', 'url' => 'admin/siswa.php'],
        ['icon' => 'fa-chart-bar', 'label' => 'Laporan', 'url' => 'admin/com_laporan/kondisi_keuangan.php'],
    ];
    if ($role === 'kepsek') $bottom_nav[0]['url'] = 'index-kepsek.php';
} elseif ($role === 'guru') {
    $bottom_nav = [
        ['icon' => 'fa-home', 'label' => 'Home', 'url' => 'index-guru.php'],
        ['icon' => 'fa-calendar-alt', 'label' => 'Jadwal', 'url' => 'guru/jadwal.php'],
        ['icon' => 'fa-user-check', 'label' => 'Absensi', 'url' => 'guru/absensi.php'],
        ['icon' => 'fa-book', 'label' => 'Tugas', 'url' => 'guru/bahan_tugas.php'],
    ];
} elseif ($role === 'siswa') {
    $bottom_nav = [
        ['icon' => 'fa-home', 'label' => 'Home', 'url' => 'index-siswa.php'],
        ['icon' => 'fa-file-invoice-dollar', 'label' => 'Tagihan', 'url' => 'siswa/tagihan.php'],
        ['icon' => 'fa-user-check', 'label' => 'Absensi', 'url' => 'siswa/absensi.php'],
        ['icon' => 'fa-star', 'label' => 'Nilai', 'url' => 'siswa/raport.php'],
    ];
} elseif ($role === 'bendahara') {
    $bottom_nav = [
        ['icon' => 'fa-home', 'label' => 'Home', 'url' => 'index-bendahara.php'],
        ['icon' => 'fa-wallet', 'label' => 'Bayar', 'url' => 'admin/pembayaran.php'],
        ['icon' => 'fa-box-open', 'label' => 'Kas', 'url' => 'admin/com_kas/penerimaan.php'],
        ['icon' => 'fa-piggy-bank', 'label' => 'Tabungan', 'url' => 'admin/com_transaksi/transaksi.php'],
    ];
} else {
    // kasir, petugas, dll
    $bottom_nav = [
        ['icon' => 'fa-home', 'label' => 'Home', 'url' => 'index-' . $role . '.php'],
        ['icon' => 'fa-store', 'label' => 'POS', 'url' => 'admin/kasir.php'],
        ['icon' => 'fa-user', 'label' => 'Profil', 'url' => $role . '/profil.php'],
    ];
}

// Menu selalu jadi item terakhir
$bottom_nav[] = ['icon' => 'fa-bars', 'label' => 'Menu', 'url' => '#', 'onclick' => 'toggleSidebar()'];

?>
<style>
    /* Hanya muncul di mode PWA Standalone */
    .pwa-bottom-nav { display: none; }
    @media (display-mode: standalone) {
        .pwa-bottom-nav {
            display: flex;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 65px;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255,255,255,0.05);
            z-index: 40;
            padding-bottom: env(safe-area-inset-bottom); /* Dukungan iPhone X/11/12/etc */
            justify-content: space-around;
            align-items: center;
        }
        .pwa-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            height: 100%;
            color: #64748b; /* slate-500 */
            text-decoration: none;
            transition: all 0.2s;
        }
        .pwa-nav-item.active {
            color: #3b82f6; /* blue-500 */
        }
        .pwa-nav-item i {
            font-size: 1.25rem;
            margin-bottom: 2px;
            transition: transform 0.2s;
        }
        .pwa-nav-item.active i {
            transform: translateY(-2px);
        }
        .pwa-nav-item span {
            font-size: 0.65rem;
            font-weight: 500;
        }
        
        /* Ubah Sidebar menjadi Drawer dari Kanan di PWA */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            right: 0; /* Dari Kanan */
            left: auto;
            transform: translateX(100%) !important;
            z-index: 60;
            display: flex !important;
            width: 80%;
            max-width: 320px;
        }
        .sidebar.open {
            transform: translateX(0) !important;
        }
        #sidebarOverlay {
            z-index: 50;
            /* Make sure overlay is visible when sidebar is open */
        }
        .sidebar-link {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            font-size: 0.9rem;
        }
    }
</style>

<div class="pwa-bottom-nav">
    <?php foreach ($bottom_nav as $nav): 
        $isActive = ($current === basename($nav['url'])) || (strpos($_SERVER['REQUEST_URI'], $nav['url']) !== false && $nav['url'] !== '#');
        $activeClass = $isActive ? 'active' : '';
        $onclick = isset($nav['onclick']) ? 'onclick="' . $nav['onclick'] . '"; return false;' : '';
    ?>
    <a href="<?= $nav['url'] === '#' ? '#' : BASE_URL . $nav['url'] ?>" class="pwa-nav-item <?= $activeClass ?>" <?= $onclick ?>>
        <i class="fas <?= $nav['icon'] ?>"></i>
        <span><?= $nav['label'] ?></span>
    </a>
    <?php endforeach; ?>
</div>

<script>
    // Adjust bottom nav behavior
    if (window.matchMedia('(display-mode: standalone)').matches) {
        // Tweak toggleSidebar to open from right
        const originalToggle = window.toggleSidebar;
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar.classList.contains('open') || sidebar.style.transform === 'translateX(0px)') {
                sidebar.classList.remove('open');
                sidebar.style.transform = 'translateX(100%)';
                overlay.classList.add('hidden');
                overlay.style.display = 'none';
            } else {
                sidebar.classList.add('open');
                sidebar.style.transform = 'translateX(0)';
                overlay.classList.remove('hidden');
                overlay.style.display = 'block';
            }
        };
    }
</script>
