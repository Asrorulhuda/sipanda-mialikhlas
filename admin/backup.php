<?php
$page_title = 'Backup & Restore Database';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);

if (isset($_POST['backup'])) {
    $file = 'backup_sipanda2_' . date('Y-m-d_His') . '.sql';
    $path = __DIR__ . '/../database/' . $file;
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $sql = "-- SIPANDA Backup " . date('Y-m-d H:i:s') . "\n\n";
    foreach ($tables as $table) {
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        $sql .= $create['Create Table'] . ";\n\n";
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll();
        foreach ($rows as $row) {
            $vals = array_map(function($v) use ($pdo) { return $v === null ? 'NULL' : $pdo->quote($v); }, $row);
            $sql .= "INSERT INTO `$table` VALUES(" . implode(',', $vals) . ");\n";
        }
        $sql .= "\n";
    }
    file_put_contents($path, $sql);
    flash('msg', "Backup berhasil: $file");
    header('Location: backup.php'); exit;
}

if (isset($_POST['restore']) && isset($_FILES['sql_file'])) {
    $content = file_get_contents($_FILES['sql_file']['tmp_name']);
    $pdo->exec($content);
    flash('msg', 'Restore berhasil!');
    header('Location: backup.php'); exit;
}

$backups = glob(__DIR__ . '/../database/backup_*.sql');
rsort($backups);

require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="glass rounded-xl p-5">
        <h4 class="font-semibold text-sm mb-3"><i class="fas fa-download text-blue-400 mr-2"></i>Backup Database</h4>
        <form method="POST"><button type="submit" name="backup" class="bg-blue-600 hover:bg-blue-500 px-6 py-2 rounded-lg text-sm font-medium w-full"><i class="fas fa-database mr-1"></i>Backup Sekarang</button></form>
    </div>
    <div class="glass rounded-xl p-5">
        <h4 class="font-semibold text-sm mb-3"><i class="fas fa-upload text-amber-400 mr-2"></i>Restore Database</h4>
        <form method="POST" enctype="multipart/form-data" class="space-y-3">
            <input type="file" name="sql_file" accept=".sql" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-1.5 text-sm">
            <button type="submit" name="restore" class="bg-amber-600 hover:bg-amber-500 px-6 py-2 rounded-lg text-sm font-medium w-full" onclick="return confirm('Yakin restore? Data saat ini akan tertimpa!')"><i class="fas fa-upload mr-1"></i>Restore</button>
        </form>
    </div>
</div>

<div class="glass rounded-xl p-5">
    <h4 class="font-semibold text-sm mb-3"><i class="fas fa-history text-emerald-400 mr-2"></i>File Backup</h4>
    <?php foreach ($backups as $b):
        $name = basename($b); $size = number_format(filesize($b)/1024, 1); ?>
    <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg mb-2">
        <div><p class="text-sm font-medium"><?= $name ?></p><p class="text-xs text-slate-400"><?= $size ?> KB</p></div>
        <a href="<?= BASE_URL ?>database/<?= $name ?>" download class="bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600/40 px-3 py-1 rounded text-xs"><i class="fas fa-download mr-1"></i>Download</a>
    </div>
    <?php endforeach; ?>
    <?php if (empty($backups)): ?><p class="text-slate-400 text-sm">Belum ada file backup.</p><?php endif; ?>
</div>
<?php require_once __DIR__ . '/../template/footer.php'; ?>
