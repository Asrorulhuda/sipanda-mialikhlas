<?php
// Laporan template helper
function lap_header($pdo, $title) {
    $GLOBALS['page_title'] = $title;
    require_once __DIR__ . '/../../config/init.php';
    cek_role(['admin','bendahara','kepsek']);
    require_once __DIR__ . '/../../template/header.php';
    require_once __DIR__ . '/../../template/sidebar.php';
    require_once __DIR__ . '/../../template/topbar.php';
}
