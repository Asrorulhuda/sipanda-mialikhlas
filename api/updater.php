<?php
/**
 * SIPANDA Online Updater API
 * Handles version checking, downloading, and applying updates from GitHub
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

// ─── Coba naikkan limit untuk proses update ───
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '600');
@set_time_limit(600);

// ─── Shutdown handler: tangkap FATAL ERROR agar response selalu JSON ───
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Bersihkan output buffer yang mungkin berisi HTML error
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        $pesan = $error['message'];
        // Terjemahkan error umum ke bahasa yang mudah dipahami
        if (stripos($pesan, 'memory') !== false || stripos($pesan, 'Allowed memory size') !== false) {
            $pesan = 'Memory limit hosting tidak cukup untuk proses update. Hubungi provider hosting untuk menaikkan memory_limit (minimal 256MB), atau update secara manual via FTP.';
        } elseif (stripos($pesan, 'Maximum execution time') !== false || stripos($pesan, 'max_execution_time') !== false) {
            $pesan = 'Proses update melebihi batas waktu hosting. Hubungi provider hosting untuk menaikkan max_execution_time, atau update secara manual via FTP.';
        } elseif (stripos($pesan, 'ZipArchive') !== false) {
            $pesan = 'Ekstensi PHP zip tidak tersedia di hosting. Hubungi provider hosting untuk mengaktifkan ekstensi zip.';
        } elseif (stripos($pesan, 'curl') !== false) {
            $pesan = 'Ekstensi PHP curl tidak tersedia di hosting. Hubungi provider hosting untuk mengaktifkan ekstensi curl.';
        }

        echo json_encode([
            'status'  => 'error',
            'message' => '⚠️ Fatal Error: ' . $pesan,
            'steps'   => [['step' => '❌ Fatal: ' . $error['message'], 'time' => date('H:i:s')]],
            'debug'   => [
                'file' => basename($error['file']),
                'line' => $error['line'],
                'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 1) . ' MB',
                'memory_limit' => ini_get('memory_limit'),
            ],
        ]);
    }
});

require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);
header('Content-Type: application/json');

function send_json_response($data) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ─── Ensure github_token column exists ───
try { $pdo->exec("ALTER TABLE tbl_setting ADD COLUMN github_token TEXT DEFAULT NULL"); } catch(Exception $e) {}

// ─── Constants ───
$REPO_OWNER  = defined('GITHUB_REPO_OWNER') ? GITHUB_REPO_OWNER : 'Asrorulhuda';
$REPO_NAME   = defined('GITHUB_REPO_NAME') ? GITHUB_REPO_NAME : 'sipanda';
$BRANCH      = defined('GITHUB_BRANCH') ? GITHUB_BRANCH : 'main';
$ROOT_DIR    = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR;
$TEMP_DIR    = $ROOT_DIR . 'database' . DIRECTORY_SEPARATOR . '_update_temp' . DIRECTORY_SEPARATOR;
$LOG_FILE    = $ROOT_DIR . 'database' . DIRECTORY_SEPARATOR . 'update_log.json';

// Paths/files that must NEVER be overwritten during update
$PROTECTED = [
    'config/koneksi.php',
    'config/license.key',
    'gambar/',
    'foto_siswa/',
    'cms_images/',
    '.htaccess',
    '.vscode/',
    '.git/',
];

$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
$github_token = $setting['github_token'] ?? '';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ─── Helper: GitHub HTTP request ───
function gh_request($url, $token, $binary = false) {
    if (!function_exists('curl_init')) {
        return ['body' => '', 'code' => 0, 'error' => 'Ekstensi cURL tidak tersedia di hosting ini.'];
    }
    $ch = curl_init($url);
    $headers = ['User-Agent: SIPANDA-Updater/2.0'];
    if ($token) $headers[] = "Authorization: token $token";
    if (!$binary) {
        $headers[] = "Accept: application/vnd.github.v3+json";
    }

    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 300,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    return ['body' => $body, 'code' => $code, 'error' => $error];
}

// ─── Helper: Download file dari GitHub langsung ke disk (hemat memory) ───
function gh_download_to_file($url, $token, $dest_path) {
    if (!function_exists('curl_init')) {
        return ['success' => false, 'error' => 'Ekstensi cURL tidak tersedia.', 'code' => 0];
    }
    $fp = fopen($dest_path, 'wb');
    if (!$fp) {
        return ['success' => false, 'error' => 'Gagal membuat file: ' . $dest_path, 'code' => 0];
    }

    $ch = curl_init($url);
    $headers = ['User-Agent: SIPANDA-Updater/2.0'];
    if ($token) $headers[] = "Authorization: token $token";

    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_FILE           => $fp,  // Stream langsung ke file, bukan ke memory!
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 600,
    ]);

    curl_exec($ch);
    $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $size  = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
    curl_close($ch);
    fclose($fp);

    if ($code !== 200) {
        @unlink($dest_path);
        return ['success' => false, 'error' => 'HTTP ' . $code . ($error ? " — $error" : ''), 'code' => $code];
    }

    return ['success' => true, 'size' => $size, 'code' => $code];
}

// ─── Helper: recursive copy with exclusions ───
function copy_recursive($src, $dst, $protected, $root_src, $root_dst) {
    $dir = opendir($src);
    if (!is_dir($dst)) @mkdir($dst, 0755, true);
    $count = 0;
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') continue;
        $srcPath = $src . DIRECTORY_SEPARATOR . $file;
        $dstPath = $dst . DIRECTORY_SEPARATOR . $file;

        // Check if this path is protected
        $rel = str_replace($root_src, '', $srcPath);
        $rel = str_replace('\\', '/', $rel);
        $rel = ltrim($rel, '/'); // <-- FIX: Mencegah error double slash
        $skip = false;
        foreach ($protected as $p) {
            if (strpos($rel, $p) === 0 || $rel === rtrim($p, '/')) {
                $skip = true;
                break;
            }
        }
        if ($skip) continue;

        if (is_dir($srcPath)) {
            $count += copy_recursive($srcPath, $dstPath, $protected, $root_src, $root_dst);
        } else {
            if (copy($srcPath, $dstPath)) $count++;
        }
    }
    closedir($dir);
    return $count;
}

// ─── Helper: recursive delete directory ───
function delete_dir($dir) {
    if (!is_dir($dir)) return;
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? @rmdir($item->getRealPath()) : @unlink($item->getRealPath());
    }
    @rmdir($dir);
}

// ─── Helper: Append update log ───
function append_log($file, $entry) {
    $log = [];
    if (file_exists($file)) $log = json_decode(file_get_contents($file), true) ?: [];
    array_unshift($log, $entry);
    // Keep only last 50 entries
    $log = array_slice($log, 0, 50);
    file_put_contents($file, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ─── Helper: Backup database ───
function backup_database($pdo, $root) {
    $file = 'backup_before_update_' . date('Y-m-d_His') . '.sql';
    $path = $root . 'database' . DIRECTORY_SEPARATOR . $file;
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $sql = "-- SIPANDA Auto-Backup Before Update " . date('Y-m-d H:i:s') . "\n\n";
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
    return $file;
}

// ═══════════════════════════════════════════════
//  ACTION ROUTER
// ═══════════════════════════════════════════════

switch ($action) {

    // ─── CHECK FOR UPDATES ───
    case 'check':
        if (!$github_token) {
            send_json_response(['status' => 'error', 'message' => 'GitHub Token belum dikonfigurasi. Silakan isi token terlebih dahulu.']);

        }
        $url = "https://raw.githubusercontent.com/$REPO_OWNER/$REPO_NAME/$BRANCH/version.json";
        $res = gh_request($url, $github_token);

        if ($res['code'] !== 200) {
            $msg = $res['code'] == 404 ? 'File version.json tidak ditemukan di repository.' :
                  ($res['code'] == 401 ? 'Token GitHub tidak valid atau sudah expired.' :
                  'Gagal terhubung ke server. HTTP ' . $res['code']);
            send_json_response(['status' => 'error', 'message' => $msg]);

        }

        $remote = json_decode($res['body'], true);
        if (!$remote || !isset($remote['version'])) {
            send_json_response(['status' => 'error', 'message' => 'Format version.json tidak valid.']);

        }

        $local_v  = str_replace('v', '', APP_VERSION);
        $remote_v = str_replace('v', '', $remote['version']);

        send_json_response([
            'status'         => 'success',
            'local_version'  => APP_VERSION,
            'remote_version' => 'v' . $remote['version'],
            'has_update'     => version_compare($remote_v, $local_v, '>'),
            'changelog'      => $remote['changelog'] ?? [],
            'released'       => $remote['released'] ?? '',
            'critical'       => $remote['critical'] ?? false,
            'min_php'        => $remote['min_php'] ?? '7.4',
            'db_migrations'  => $remote['db_migrations'] ?? [],
        ]);
        break;

    // ─── APPLY UPDATE ───
    case 'update':
        if (!$github_token) {
            send_json_response(['status' => 'error', 'message' => 'GitHub Token belum dikonfigurasi.']);

        }

        set_time_limit(600);
        $steps = [];
        $success = true;
        $error_msg = '';
        $remote_version = '';

        try {
            // Step 1: Fetch version info
            $steps[] = ['step' => 'Mengambil informasi versi...', 'time' => date('H:i:s')];
            $url = "https://raw.githubusercontent.com/$REPO_OWNER/$REPO_NAME/$BRANCH/version.json";
            $res = gh_request($url, $github_token);
            if ($res['code'] !== 200) throw new Exception('Gagal mengambil version.json (HTTP ' . $res['code'] . ')');
            $remote = json_decode($res['body'], true);
            if (!$remote) throw new Exception('Format version.json tidak valid');
            $remote_version = 'v' . $remote['version'];
            $steps[] = ['step' => "Versi terbaru: $remote_version", 'time' => date('H:i:s')];

            // Step 2: Check PHP version
            $min_php = $remote['min_php'] ?? '7.4';
            if (version_compare(PHP_VERSION, $min_php, '<')) {
                throw new Exception("PHP $min_php atau lebih baru diperlukan. Versi Anda: " . PHP_VERSION);
            }

            // Step 3: Auto backup database
            $steps[] = ['step' => 'Membuat backup database otomatis...', 'time' => date('H:i:s')];
            $backup_file = backup_database($pdo, $ROOT_DIR);
            $steps[] = ['step' => "Backup tersimpan: $backup_file", 'time' => date('H:i:s')];

            // Step 4: Pre-flight checks
            $steps[] = ['step' => 'Memeriksa kebutuhan sistem...', 'time' => date('H:i:s')];
            if (!class_exists('ZipArchive')) {
                throw new Exception('Ekstensi PHP zip tidak tersedia. Hubungi provider hosting untuk mengaktifkan ekstensi zip.');
            }
            if (!function_exists('curl_init')) {
                throw new Exception('Ekstensi PHP cURL tidak tersedia. Hubungi provider hosting untuk mengaktifkan ekstensi curl.');
            }
            // Siapkan temp dir dulu agar bisa cek writable
            if (is_dir($TEMP_DIR)) delete_dir($TEMP_DIR);
            @mkdir($TEMP_DIR, 0755, true);
            if (!is_dir($TEMP_DIR) || !is_writable($TEMP_DIR)) {
                throw new Exception('Folder sementara tidak bisa dibuat/ditulis: database/_update_temp/. Periksa permission folder database/.');
            }
            $steps[] = ['step' => 'Sistem siap ✓ (curl, zip, writable)', 'time' => date('H:i:s')];

            // Step 5: Download ZIP from GitHub — langsung ke file (stream, hemat memory)
            $steps[] = ['step' => 'Mengunduh paket update dari GitHub...', 'time' => date('H:i:s')];
            $zip_url = "https://api.github.com/repos/$REPO_OWNER/$REPO_NAME/zipball/$BRANCH";
            $zip_path = $TEMP_DIR . 'update.zip';
            $dl_result = gh_download_to_file($zip_url, $github_token, $zip_path);
            if (!$dl_result['success']) {
                throw new Exception('Gagal mengunduh ZIP: ' . $dl_result['error']);
            }
            $zip_size = filesize($zip_path);
            if ($zip_size < 1000) {
                $content_preview = file_get_contents($zip_path, false, null, 0, 200);
                @unlink($zip_path);
                throw new Exception('File ZIP terlalu kecil (' . $zip_size . ' bytes), mungkin error: ' . $content_preview);
            }
            $steps[] = ['step' => 'Unduhan selesai (' . number_format($zip_size / 1024, 1) . ' KB) — Memory: ' . round(memory_get_usage(true)/1024/1024,1) . 'MB', 'time' => date('H:i:s')];

            // Step 6: Extract ZIP
            $steps[] = ['step' => 'Mengekstrak file update...', 'time' => date('H:i:s')];

            $zip = new ZipArchive();
            $zip_open_result = $zip->open($zip_path);
            if ($zip_open_result !== TRUE) {
                throw new Exception('Gagal membuka file ZIP (error code: ' . $zip_open_result . ')');
            }
            $zip->extractTo($TEMP_DIR);
            $zip->close();
            @unlink($zip_path); // hapus ZIP setelah extract untuk hemat disk

            // Find extracted folder (GitHub ZIPs have a root folder like "Asrorulhuda-sipanda-abc1234/")
            $extracted_dirs = glob($TEMP_DIR . '*', GLOB_ONLYDIR);
            if (empty($extracted_dirs)) throw new Exception('Tidak ditemukan folder di dalam ZIP');
            $src_dir = $extracted_dirs[0] . DIRECTORY_SEPARATOR;
            $steps[] = ['step' => 'Ekstraksi selesai', 'time' => date('H:i:s')];

            // Step 6: Copy files (skip protected)
            $steps[] = ['step' => 'Menyalin file baru (skip file konfigurasi lokal)...', 'time' => date('H:i:s')];
            $copied = copy_recursive($src_dir, $ROOT_DIR, $PROTECTED, $src_dir, $ROOT_DIR);
            $steps[] = ['step' => "$copied file berhasil diperbarui", 'time' => date('H:i:s')];

            // Step 7: Run DB migrations if any
            // db_migrations bisa berupa query SQL langsung ATAU nama file di folder database/
            $migrations = $remote['db_migrations'] ?? [];
            if (!empty($migrations)) {
                $steps[] = ['step' => 'Menjalankan migrasi database...', 'time' => date('H:i:s')];
                $mig_ok = 0;
                $mig_fail = 0;
                foreach ($migrations as $mig_item) {
                    try {
                        // Cek apakah ini file SQL — cari di beberapa lokasi
                        $mig_path = null;
                        $possible_paths = [
                            $ROOT_DIR . 'database' . DIRECTORY_SEPARATOR . $mig_item,
                            $ROOT_DIR . 'database' . DIRECTORY_SEPARATOR . 'update migration' . DIRECTORY_SEPARATOR . $mig_item,
                        ];
                        foreach ($possible_paths as $pp) {
                            if (file_exists($pp)) { $mig_path = $pp; break; }
                        }

                        if ($mig_path) {
                            // File-based migration — split by semicolon untuk multi-statement
                            $sql_content = file_get_contents($mig_path);
                            // Hapus komentar SQL (-- ...) 
                            $sql_content = preg_replace('/^\s*--.*$/m', '', $sql_content);
                            // Split berdasarkan semicolon
                            $statements = array_filter(array_map('trim', explode(';', $sql_content)));
                            $file_ok = 0;
                            $file_fail = 0;
                            foreach ($statements as $stmt) {
                                if (empty($stmt)) continue;
                                try {
                                    $pdo->exec($stmt);
                                    $file_ok++;
                                } catch (Exception $stmt_e) {
                                    $err = $stmt_e->getMessage();
                                    if (strpos($err, 'Duplicate') !== false || strpos($err, 'already exists') !== false) {
                                        $file_ok++; // sudah pernah dijalankan
                                    } else {
                                        $short = strlen($stmt) > 50 ? substr($stmt, 0, 50) . '...' : $stmt;
                                        $steps[] = ['step' => "⚠️ SQL gagal: $short — $err", 'time' => date('H:i:s')];
                                        $file_fail++;
                                    }
                                }
                            }
                            $steps[] = ['step' => "Migrasi file $mig_item: $file_ok OK" . ($file_fail > 0 ? ", $file_fail gagal" : ""), 'time' => date('H:i:s')];
                            $mig_ok++;
                        } else {
                            // Direct SQL query (dari Version Manager)
                            $pdo->exec($mig_item);
                            $short = strlen($mig_item) > 60 ? substr($mig_item, 0, 60) . '...' : $mig_item;
                            $steps[] = ['step' => "SQL: $short ✓", 'time' => date('H:i:s')];
                            $mig_ok++;
                        }
                    } catch (Exception $mig_e) {
                        // Skip jika migrasi sudah pernah dijalankan (duplicate column, table exists, dll)
                        $err_msg = $mig_e->getMessage();
                        if (strpos($err_msg, 'Duplicate') !== false || strpos($err_msg, 'already exists') !== false) {
                            $steps[] = ['step' => "Migrasi sudah diterapkan (skip)", 'time' => date('H:i:s')];
                            $mig_ok++;
                        } else {
                            $short = strlen($mig_item) > 50 ? substr($mig_item, 0, 50) . '...' : $mig_item;
                            $steps[] = ['step' => "⚠️ Migrasi gagal: $short — " . $err_msg, 'time' => date('H:i:s')];
                            $mig_fail++;
                        }
                    }
                }
                $steps[] = ['step' => "Migrasi selesai: $mig_ok berhasil" . ($mig_fail > 0 ? ", $mig_fail gagal" : ""), 'time' => date('H:i:s')];
            }

            // Step 8: Update APP_VERSION in koneksi.php
            $steps[] = ['step' => 'Memperbarui nomor versi aplikasi...', 'time' => date('H:i:s')];
            $koneksi_path = $ROOT_DIR . 'config' . DIRECTORY_SEPARATOR . 'koneksi.php';
            $koneksi_content = file_get_contents($koneksi_path);
            $koneksi_content = preg_replace(
                "/define\('APP_VERSION',\s*'[^']*'\)/",
                "define('APP_VERSION', '$remote_version')",
                $koneksi_content
            );
            file_put_contents($koneksi_path, $koneksi_content);

            // Step 9: Cleanup
            $steps[] = ['step' => 'Membersihkan file sementara...', 'time' => date('H:i:s')];
            delete_dir($TEMP_DIR);

            $steps[] = ['step' => "✅ Update ke $remote_version berhasil!", 'time' => date('H:i:s')];

        } catch (Exception $e) {
            $success = false;
            $error_msg = $e->getMessage();
            $steps[] = ['step' => '❌ Error: ' . $error_msg, 'time' => date('H:i:s')];
            // Cleanup on error too
            if (is_dir($TEMP_DIR)) delete_dir($TEMP_DIR);
        }

        // Log the update
        append_log($LOG_FILE, [
            'from'      => APP_VERSION,
            'to'        => $remote_version ?: '?',
            'date'      => date('Y-m-d H:i:s'),
            'status'    => $success ? 'success' : 'failed',
            'error'     => $error_msg,
            'steps'     => $steps,
            'backup'    => $backup_file ?? null,
            'by'        => $_SESSION['nama'] ?? 'Admin',
        ]);

        send_json_response([
            'status'  => $success ? 'success' : 'error',
            'message' => $success ? "Berhasil update ke $remote_version" : $error_msg,
            'steps'   => $steps,
            'version' => $remote_version,
        ]);
        break;

    // ─── SAVE GITHUB TOKEN ───
    case 'save_token':
        $token = trim($_POST['token'] ?? '');
        if (!$token) {
            send_json_response(['status' => 'error', 'message' => 'Token tidak boleh kosong']);

        }
        // Validate token by calling GitHub API
        $test = gh_request("https://api.github.com/repos/$REPO_OWNER/$REPO_NAME", $token);
        if ($test['code'] !== 200) {
            send_json_response(['status' => 'error', 'message' => 'Token tidak valid atau tidak memiliki akses ke repository. HTTP ' . $test['code']]);

        }
        $pdo->prepare("UPDATE tbl_setting SET github_token=? WHERE id=1")->execute([$token]);
        send_json_response(['status' => 'success', 'message' => 'Token berhasil disimpan dan terverifikasi ✅']);
        break;

    // ─── GET UPDATE LOG ───
    case 'log':
        $log = [];
        if (file_exists($LOG_FILE)) $log = json_decode(file_get_contents($LOG_FILE), true) ?: [];
        send_json_response(['status' => 'success', 'log' => $log]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Action tidak valid']);
}
