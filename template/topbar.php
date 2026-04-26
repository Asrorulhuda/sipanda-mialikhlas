<?php // Topbar ?>
<main class="flex-1 flex flex-col min-h-screen overflow-x-hidden">
    <!-- Topbar -->
    <header class="glass sticky top-0 z-30 border-b border-white/5 pwa-topbar">
        <div class="flex items-center justify-between px-4 md:px-6 py-3">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="md:hidden text-slate-300 hover:text-white p-1 pwa-hidden"><i class="fas fa-bars text-xl"></i></button>
                <div class="pwa-back-btn hidden text-slate-300 hover:text-white p-1" onclick="history.back()"><i class="fas fa-arrow-left text-xl"></i></div>
                <div>
                    <h2 class="text-lg font-semibold pwa-title"><?= clean($page_title ?? 'Dashboard') ?></h2>
                    <p class="text-xs text-slate-500 pwa-subtitle"><?= clean($setting['nama_sekolah'] ?? 'Sekolah') ?></p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="hidden sm:block text-xs text-slate-400 pwa-hidden"><i class="fas fa-calendar-alt mr-1"></i><?= date('d M Y') ?></span>
                <a href="<?= BASE_URL ?>logout.php" class="glass-light px-3 py-1.5 rounded-lg text-xs text-slate-300 hover:text-red-400 transition-colors pwa-hidden">
                    <i class="fas fa-sign-out-alt mr-1"></i><span class="hidden sm:inline">Logout</span>
                </a>
                <!-- Notifications icon for PWA -->
                <button class="hidden pwa-notif text-slate-300"><i class="fas fa-bell text-xl"></i></button>
            </div>
        </div>
        <style>
            @media (display-mode: standalone) {
                .pwa-topbar { padding-top: env(safe-area-inset-top); background: rgba(10, 22, 40, 0.95); backdrop-filter: blur(10px); }
                .pwa-hidden { display: none !important; }
                .pwa-title { font-size: 1.125rem; }
                .pwa-subtitle { display: none; }
                .pwa-notif { display: block !important; }
                /* Show back button if not on dashboard */
                body:not(.page-dashboard) .pwa-back-btn { display: block !important; }
            }
        </style>
    </header>

    <!-- Content -->
    <div class="flex-1 p-4 md:p-6 animate-fade-in">
