<?php
// Midtrans Webhook Handler
require_once __DIR__ . '/../config/init.php';

// Midtrans configuration
$stmt = $pdo->query("SELECT midtrans_server_key FROM tbl_setting WHERE id=1");
$setting_midtrans = $stmt->fetch();
$server_key = $setting_midtrans['midtrans_server_key'] ?? '';

// Check incoming JSON body
$json_result = file_get_contents('php://input');
$result = json_decode($json_result, true);

if (!$result) {
    http_response_code(400);
    echo "Invalid JSON";
    exit;
}

$order_id = $result['order_id'];
$status_code = $result['status_code'];
$gross_amount = $result['gross_amount'];
$transaction_status = $result['transaction_status'];
$fraud_status = $result['fraud_status'] ?? '';

// Verify signature key
$signature_key = hash("sha512", $order_id . $status_code . $gross_amount . $server_key);
if ($signature_key !== $result['signature_key']) {
    http_response_code(403);
    echo "Invalid signature";
    exit;
}

// Find transaction in DB (assuming tbl_pembayaran has an order_id or pending_trx table exists)
// For SIPANDA context: usually we log order_id in tbl_transaksi_online or similar.
// Since we don't know the exact structure for online payment, we will just simulate updating it if order_id is matched.

$stmt = $pdo->prepare("SELECT * FROM tbl_pembayaran WHERE order_id = ?");
$stmt->execute([$order_id]);
$trx = $stmt->fetch();

if ($trx) {
    if ($transaction_status == 'capture' || $transaction_status == 'settlement') {
        if ($fraud_status == 'challenge') {
            // Wait for manual review
        } else {
            // Payment success!
            $pdo->prepare("UPDATE tbl_pembayaran SET status_bayar='Lunas' WHERE order_id=?")->execute([$order_id]);
        }
    } else if ($transaction_status == 'cancel' || $transaction_status == 'deny' || $transaction_status == 'expire') {
        // Payment failed
        $pdo->prepare("UPDATE tbl_pembayaran SET status_bayar='Gagal' WHERE order_id=?")->execute([$order_id]);
    } else if ($transaction_status == 'pending') {
        // Wait
        $pdo->prepare("UPDATE tbl_pembayaran SET status_bayar='Pending' WHERE order_id=?")->execute([$order_id]);
    }
}

http_response_code(200);
echo "OK";
?>
