<?php
// Template Header - SIPANDA v2.0.1
$page_title = $page_title ?? 'SIPANDA';
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#020617">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="<?= BASE_URL ?>manifest.json">
    <title><?= clean($page_title) ?> — <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    navy: { 50: '#f0f4fa', 100: '#e1e9f4', 200: '#c8d7ea', 300: '#a3bdda', 400: '#779fc6', 500: '#5684b3', 600: '#436994', 700: '#365478', 800: '#2f4764', 900: '#2a3d54', 950: '#0a1628' },
                    gold: { 50: '#fcfaf2', 100: '#f7efe1', 200: '#f0dbbe', 300: '#e7c191', 400: '#dea160', 500: '#d78839', 600: '#ca6e2c', 700: '#a85325', 800: '#864324', 900: '#6c3821', 950: '#3a1b0e' }
                },
                fontFamily: { sans: ['Inter', 'sans-serif'] },
                animation: {
                    'fade-in': 'fadeIn .5s ease-out',
                    'slide-in': 'slideIn .3s ease-out',
                    'count-up': 'countUp 1s ease-out',
                },
                keyframes: {
                    fadeIn: { '0%': { opacity: '0', transform: 'translateY(10px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    slideIn: { '0%': { transform: 'translateX(-100%)' }, '100%': { transform: 'translateX(0)' } },
                }
            }
        }
    }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Interactive Feedback Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .glass { background: rgba(30,41,59,.7); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,.08); }
        .glass-light { background: rgba(51,65,85,.5); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,.06); }
        .sidebar-link.active { background: linear-gradient(135deg, rgba(59,130,246,.3), rgba(59,130,246,.1)); border-left: 3px solid #3b82f6; }
        .sidebar-link:hover { background: rgba(255,255,255,.05); }
        .stat-card { transition: all .3s; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,.3); }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
        .table-container { overflow-x: auto; }
        .table-container table { min-width: 600px; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
        }
        /* PWA Native App Styles */
        @media (display-mode: standalone) {
            .sidebar, #sidebarOverlay { display: none !important; }
            main { padding-bottom: 70px; /* space for bottom nav */ padding-top: env(safe-area-inset-top); }
            body { overscroll-behavior-y: none; user-select: none; -webkit-user-select: none; }
            .table-container { -webkit-overflow-scrolling: touch; }
            ::-webkit-scrollbar { display: none; } /* Hide scrollbar for native feel */
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 font-sans min-h-screen relative overflow-x-hidden">
    
    <!-- PWA Modern Interactive Preloader -->
    <div id="pwa-preloader" class="fixed inset-0 z-[9999] bg-slate-950 flex flex-col items-center justify-center transition-opacity duration-700 ease-in-out">
        <div class="relative flex items-center justify-center">
            <!-- Ripple Effects -->
            <div class="absolute inset-[-20px] rounded-full border-2 border-blue-500/20 animate-[ping_2s_cubic-bezier(0,0,0.2,1)_infinite]"></div>
            <div class="absolute inset-[-40px] rounded-full border-2 border-purple-500/20 animate-[ping_2.5s_cubic-bezier(0,0,0.2,1)_infinite]"></div>
            <div class="absolute inset-0 rounded-full bg-blue-500/10 blur-xl animate-pulse"></div>
            
            <!-- Floating Logo -->
            <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-[0_0_40px_rgba(59,130,246,0.6)] relative z-10 animate-bounce cursor-pointer hover:scale-105 transition-transform" style="animation-duration: 2s;">
                <span class="text-4xl font-extrabold text-white">S</span>
            </div>
        </div>
        
        <!-- Loading Text -->
        <div class="mt-12 flex flex-col items-center">
            <h1 class="text-2xl font-black bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-400 bg-clip-text text-transparent tracking-[0.3em] uppercase">SIPANDA</h1>
            
            <!-- Progress Bar -->
            <div class="w-48 h-1 bg-slate-800 rounded-full mt-4 overflow-hidden relative">
                <div class="absolute top-0 left-0 h-full w-full bg-gradient-to-r from-blue-500 to-purple-500 rounded-full" style="animation: loadingBar 1.5s ease-in-out infinite;"></div>
            </div>
            
            <p class="text-slate-500 text-[10px] mt-3 tracking-[0.2em] font-medium uppercase animate-pulse">Memuat Komponen Sistem...</p>
        </div>
        <style>
            @keyframes loadingBar {
                0% { transform: translateX(-100%); }
                50% { transform: translateX(0); }
                100% { transform: translateX(100%); }
            }
        </style>
    </div>

    <script>
        // Smooth vanish on load
        window.addEventListener('load', function() {
            setTimeout(() => {
                const loader = document.getElementById('pwa-preloader');
                if(loader) {
                    loader.style.opacity = '0';
                    loader.style.pointerEvents = 'none';
                    setTimeout(() => loader.remove(), 700);
                }
            }, 500); // Tahan sebentar untuk memastikan efek visual
        });
    </script>

<div class="flex min-h-screen">
