<?php
// Unified Top-up Webhook Handler (Tripay, Midtrans, Xendit)
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/wa_helper.php';

$json_result = file_get_contents('php://input');
$result = json_decode($json_result, true);

if (!$result) {
    http_response_code(400); die("Invalid JSON");
}

// 1. Detect Provider
$provider = '';
if (isset($result['transaction_status'])) $provider = 'midtrans';
elseif (isset($result['reference'])) $provider = 'tripay';
elseif (isset($result['event'])) $provider = 'xendit';

if (!$provider) {
    http_response_code(400); die("Unknown Provider");
}

$order_id = '';
$status = '';
$amount = 0;

// 2. Extract Data per Provider
if ($provider == 'midtrans') {
    $order_id = $result['order_id'];
    $amount = $result['gross_amount'];
    if ($result['transaction_status'] == 'settlement' || $result['transaction_status'] == 'capture') {
        $status = 'success';
    } elseif (in_array($result['transaction_status'], ['deny', 'expire', 'cancel'])) {
        $status = 'failed';
    }
} elseif ($provider == 'tripay') {
    $order_id = $result['merchant_ref'];
    if ($result['status'] == 'PAID') $status = 'success';
} elseif ($provider == 'xendit') {
    $order_id = $result['external_id'];
    if ($result['status'] == 'PAID' || $result['event'] == 'invoice.paid') $status = 'success';
}

if ($status == 'success' && $order_id) {
    // Find top-up record
    $stmt = $pdo->prepare("SELECT * FROM tbl_topup WHERE order_id = ? AND status = 'pending'");
    $stmt->execute([$order_id]);
    $topup = $stmt->fetch();

    if ($topup) {
        // Update top-up status
        $pdo->prepare("UPDATE tbl_topup SET status = 'success' WHERE id = ?")->execute([$topup['id']]);

        // Add Saldo Jajan
        if ($topup['role'] == 'siswa') {
            $pdo->prepare("UPDATE tbl_siswa SET saldo_jajan = saldo_jajan + ? WHERE id_siswa = ?")
                ->execute([$topup['nominal'], $topup['id_user']]);
            
            // WA Notif
            $siswa = $pdo->prepare("SELECT * FROM tbl_siswa WHERE id_siswa = ?");
            $siswa->execute([$topup['id_user']]); $s = $siswa->fetch();
            if (!empty($s['no_hp_ortu'])) {
                send_wa($s['no_hp_ortu'], "🍱 *Top-up E-Kantin Berhasil*\n\nSaldo Jajan an. *{$s['nama']}* telah bertambah sebesar *Rp ".number_format($topup['nominal'], 0, ',', '.')."*. \n\nSisa saldo: *Rp ".number_format($s['saldo_jajan'] + $topup['nominal'], 0, ',', '.')."*.");
            }
        } else {
            $pdo->prepare("UPDATE tbl_guru SET saldo_jajan = saldo_jajan + ? WHERE id_guru = ?")
                ->execute([$topup['nominal'], $topup['id_user']]);
            
            $guru = $pdo->prepare("SELECT * FROM tbl_guru WHERE id_guru = ?");
            $guru->execute([$topup['id_user']]); $g = $guru->fetch();
            if (!empty($g['no_hp'])) {
                send_wa($g['no_hp'], "🍱 *Top-up E-Kantin Berhasil*\n\nSaldo Jajan Bapak/Ibu *{$g['nama']}* telah bertambah sebesar *Rp ".number_format($topup['nominal'], 0, ',', '.')."*. \n\nSisa saldo: *Rp ".number_format($g['saldo_jajan'] + $topup['nominal'], 0, ',', '.')."*.");
            }
        }
        echo "Top-up Processed";
    } else {
        echo "Record not found or already processed";
    }
}

http_response_code(200);
echo "OK";
