<?php
// Script untuk membersihkan .git yang tercemar dan backup-nya
function forceDeleteDir($dirPath) {
    if (!is_dir($dirPath)) return false;
    $it = new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        @chmod($file->getRealPath(), 0777);
        if ($file->isDir()) {
            @rmdir($file->getRealPath());
        } else {
            @unlink($file->getRealPath());
        }
    }
    @chmod($dirPath, 0777);
    @rmdir($dirPath);
    return !is_dir($dirPath);
}

$builds_dir = "c:\\xampp\\htdocs\\sipanda_builds";
$results = [];

// 1. Hapus .git di MI_AL_IKHLAS
$git_dir = $builds_dir . "\\MI_AL_IKHLAS\\.git";
if (is_dir($git_dir)) {
    $ok = forceDeleteDir($git_dir);
    $results[] = ".git in MI_AL_IKHLAS: " . ($ok ? "DELETED" : "FAILED");
} else {
    $results[] = ".git in MI_AL_IKHLAS: not found";
}

// 2. Hapus _git_backup_MI_AL_IKHLAS jika ada
$backup_dir = $builds_dir . "\\_git_backup_MI_AL_IKHLAS";
if (is_dir($backup_dir)) {
    $ok = forceDeleteDir($backup_dir);
    $results[] = "_git_backup_MI_AL_IKHLAS: " . ($ok ? "DELETED" : "FAILED");
} else {
    $results[] = "_git_backup_MI_AL_IKHLAS: not found";
}

// 3. Cari semua folder _git_backup_* lainnya
$scan = glob($builds_dir . "/_git_backup_*");
foreach ($scan as $dir) {
    if (is_dir($dir)) {
        $ok = forceDeleteDir($dir);
        $results[] = basename($dir) . ": " . ($ok ? "DELETED" : "FAILED");
    }
}

echo "=== Git Cleanup Results ===\n";
foreach ($results as $r) echo "  " . $r . "\n";
echo "Done!\n";
