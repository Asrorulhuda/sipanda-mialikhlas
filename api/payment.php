<?php
// SIPANDA Multi-Gateway Payment API (Midtrans, Tripay, Xendit)
header('Content-Type: application/json');
require_once __DIR__ . '/../config/init.php';

$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
$action = $_GET['action'] ?? '';
// Default to Midtrans if not specified, but will be validated later
$gateway_req = $_POST['gateway'] ?? $_GET['gateway'] ?? ($setting['is_active_midtrans'] ? 'Midtrans' : ($setting['is_active_tripay'] ? 'Tripay' : 'Xendit'));

switch ($action) {
    case 'create':
    case 'create_topup':
        // Cek fitur keuangan/ekantin aktif (hanya untuk create, bukan callback)
        if ($action === 'create_topup' && !fitur_aktif('ekantin')) {
            echo json_encode(['status' => 'error', 'message' => 'Fitur E-Kantin tidak tersedia.']);
            exit;
        }
        $is_topup = ($action === 'create_topup');
        $items_raw = $_POST['items'] ?? '';
        $items_decoded = $items_raw ? json_decode($items_raw, true) : []; 
        
        // 1. Get User/Siswa Data
        $id_siswa = (int)$_POST['id_siswa'];
        $siswa = $pdo->prepare("SELECT * FROM tbl_siswa WHERE id_siswa=?");
        $siswa->execute([$id_siswa]);
        $s = $siswa->fetch();
        if (!$s) { echo json_encode(['status'=>'error','message'=>'Siswa tidak ditemukan']); exit; }

        // 2. Prepare Order ID and Gross Amount
        $order_id = ($is_topup ? 'TOPUP-' : 'INV-') . time() . rand(100,999) . '-' . $s['id_siswa'];
        $gross_amount = 0;
        $description = "";
        $gateway_items = [];

        if ($is_topup) {
            $jumlah = (int)$_POST['jumlah'];
            $gross_amount = $jumlah;
            $description = "Top-up Saldo Jajan: " . $s['nama'];
            $gateway_items[] = ['id' => 'TOPUP', 'price' => $gross_amount, 'quantity' => 1, 'name' => $description];
        } else if (!empty($items_decoded)) {
            // BULK PAYMENT (CART)
            foreach ($items_decoded as $it) {
                $nom = (int)$it['jumlah'];
                $gross_amount += $nom;
                $bulan_name = $it['bulan'] > 0 ? bulan_indo($it['bulan']) : '';
                $item_name = $it['nama_jenis'] . ($bulan_name ? " ($bulan_name)" : "");
                
                $gateway_items[] = [
                    'id' => $it['id_jenis'] . '-' . $it['bulan'],
                    'price' => $nom,
                    'quantity' => 1,
                    'name' => $item_name
                ];

                // Save to tbl_payment_items
                $pdo->prepare("INSERT INTO tbl_payment_items (order_id, id_jenis, bulan, tahun, jumlah, tipe) VALUES (?, ?, ?, ?, ?, ?)")
                    ->execute([$order_id, $it['id_jenis'], $it['bulan'], $it['tahun'] ?? date('Y'), $nom, $it['tipe']]);
            }
            $description = "Pembayaran Tagihan Multiple: " . $s['nama'];
            $gross_amount += (int)($setting['biaya_admin'] ?? 0);
        } else {
            // SINGLE PAYMENT (FALLBACK)
            $id_jenis = (int)$_POST['id_jenis'];
            $bulan = (int)($_POST['bulan'] ?? 0);
            $jumlah = (int)$_POST['jumlah'];
            $gross_amount = $jumlah + (int)($setting['biaya_admin'] ?? 0);
            
            // Fetch Real Name
            $jb_data = $pdo->prepare("SELECT nama_jenis FROM tbl_jenis_bayar WHERE id_jenis=?");
            $jb_data->execute([$id_jenis]);
            $nama_jenis_real = $jb_data->fetchColumn() ?: 'Tagihan';
            $bulan_name = $bulan > 0 ? " (".bulan_indo($bulan).")" : "";
            $item_name_full = $nama_jenis_real . $bulan_name;

            $description = "Pembayaran " . $item_name_full . ": " . $s['nama'];
            $gateway_items[] = ['id' => 'FEES-'.$id_jenis, 'price' => $jumlah, 'quantity' => 1, 'name' => $item_name_full];
        }

        if ($setting['biaya_admin'] > 0 && !$is_topup) {
            $gateway_items[] = ['id' => 'ADM', 'price' => (int)$setting['biaya_admin'], 'quantity' => 1, 'name' => 'Biaya Layanan/Admin'];
        }

        // 3. Validate if gateway is active
        $is_active = false;
        if ($gateway_req === 'Midtrans') $is_active = (bool)$setting['is_active_midtrans'];
        elseif ($gateway_req === 'Tripay') $is_active = (bool)$setting['is_active_tripay'];
        elseif ($gateway_req === 'Xendit') $is_active = (bool)$setting['is_active_xendit'];

        if (!$is_active) { echo json_encode(['status'=>'error','message'=>'Gateway '.$gateway_req.' tidak aktif']); exit; }

        // 4. Log to Main Table
        if ($is_topup) {
            $pdo->prepare("INSERT INTO tbl_topup (order_id, id_user, role, nominal, method, status) VALUES (?, ?, 'siswa', ?, ?, 'pending')")
                ->execute([$order_id, $id_siswa, $gross_amount, $gateway_req]);
        } else {
            // For bulk, we store 0 in id_jenis and bulan in main tbl_payment to signal multi-item
            $id_jenis_main = empty($items_decoded) ? (int)$_POST['id_jenis'] : 0;
            $bulan_main = empty($items_decoded) ? (int)($_POST['bulan'] ?? 0) : 0;
            $pdo->prepare("INSERT INTO tbl_payment (order_id, id_siswa, id_jenis, bulan, gross_amount, status, payment_type) VALUES (?,?,?,?,?,?,?)")
                ->execute([$order_id, $id_siswa, $id_jenis_main, $bulan_main, $gross_amount, 'pending', $gateway_req]);
        }

        // 5. Gateway Logic
        $result_json = ['status'=>'error', 'message'=>'Gateway Logic Failed'];

        if ($gateway_req === 'Midtrans') {
            $params = [
                'transaction_details' => ['order_id' => $order_id, 'gross_amount' => $gross_amount],
                'customer_details' => [
                    'first_name' => $s['nama'], 
                    'email' => ($s['email'] ?? '') ?: 'noreply@school.id', 
                    'phone' => ($s['no_hp_siswa'] ?? '') ?: ($s['no_hp_ortu'] ?? '')
                ],
                'item_details' => $gateway_items
            ];

            $auth = base64_encode($setting['midtrans_server_key'] . ':');
            $url = $setting['midtrans_snap_url'] ?: "https://app.sandbox.midtrans.com/snap/v1/transactions";
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ["Content-Type: application/json", "Accept: application/json", "Authorization: Basic $auth"],
                CURLOPT_POSTFIELDS => json_encode($params)
            ]);
            $raw_res = curl_exec($ch); $res = json_decode($raw_res, true); 
            $curl_error = curl_error($ch);
            
            file_put_contents(__DIR__ . '/payment_debug.log', date('Y-m-d H:i:s') . " - CREATE - MIDTRANS - REQ: " . json_encode($params) . " - RES: " . $raw_res . " - ERR: " . $curl_error . "\n", FILE_APPEND);

            if (isset($res['token'])) {
                if (!$is_topup) $pdo->prepare("UPDATE tbl_payment SET snap_token=? WHERE order_id=?")->execute([$res['token'], $order_id]);
                $result_json = ['status'=>'ok', 'gateway'=>'Midtrans', 'token'=>$res['token'], 'order_id'=>$order_id];
            } else {
                $msg = $res['error_messages'][0] ?? $res['message'] ?? 'Unknown Midtrans Error';
                if ($curl_error) $msg = "CURL Error: " . $curl_error;
                $result_json = ['status'=>'error', 'message'=>'Midtrans Error: '.$msg];
            }

        } elseif ($gateway_req === 'Tripay') {
            $tripay_items = [];
            foreach ($gateway_items as $gi) {
                $tripay_items[] = ['sku' => $gi['id'], 'name' => $gi['name'], 'price' => $gi['price'], 'quantity' => 1];
            }

            $params = [
                'method'         => 'QRIS', 
                'merchant_ref'   => $order_id,
                'amount'         => $gross_amount,
                'customer_name'  => $s['nama'],
                'customer_email' => ($s['email'] ?? '') ?: 'noreply@school.id',
                'customer_phone' => ($s['no_hp_siswa'] ?? '') ?: ($s['no_hp_ortu'] ?? ''),
                'order_items'    => $tripay_items,
                'signature'      => hash_hmac('sha256', $setting['tripay_merchant_code'].$order_id.$gross_amount, $setting['tripay_private_key'])
            ];

            $url = (strpos($setting['tripay_api_key'], 'DEV-') === 0) ? "https://tripay.co.id/api-sandbox/transaction/create" : "https://tripay.co.id/api/transaction/create";
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $setting['tripay_api_key']],
                CURLOPT_POSTFIELDS => http_build_query($params)
            ]);
            $raw_res = curl_exec($ch);
            $res = json_decode($raw_res, true);
            file_put_contents(__DIR__ . '/payment_debug.log', date('Y-m-d H:i:s') . " - CREATE - TRIPAY - REQ: " . json_encode($params) . " - RES: " . $raw_res . "\n", FILE_APPEND);
            
            if ($res['success']) {
                $result_json = ['status'=>'ok', 'gateway'=>'Tripay', 'checkout_url'=>$res['data']['checkout_url'], 'order_id'=>$order_id];
            } else {
                $result_json = ['status'=>'error', 'message'=>'Tripay Error: '.$res['message']];
            }

        } elseif ($gateway_req === 'Xendit') {
            $params = [
                'external_id' => $order_id, 'amount' => $gross_amount,
                'payer_email' => ($s['email'] ?? '') ?: 'noreply@school.id', 'description' => $description
            ];
            $auth = base64_encode($setting['xendit_api_key'] . ':');
            $ch = curl_init("https://api.xendit.co/v2/invoices");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ["Content-Type: application/json", "Authorization: Basic $auth"],
                CURLOPT_POSTFIELDS => json_encode($params)
            ]);
            $raw_res = curl_exec($ch);
            $res = json_decode($raw_res, true);
            file_put_contents(__DIR__ . '/payment_debug.log', date('Y-m-d H:i:s') . " - CREATE - XENDIT - REQ: " . json_encode($params) . " - RES: " . $raw_res . "\n", FILE_APPEND);
            
            if (isset($res['invoice_url'])) {
                $result_json = ['status'=>'ok', 'gateway'=>'Xendit', 'checkout_url'=>$res['invoice_url'], 'order_id'=>$order_id];
            } else {
                $result_json = ['status'=>'error', 'message'=>'Xendit Error: '.($res['message']??'Unknown')];
            }
        }
        echo json_encode($result_json);
        break;



    case 'callback':
        $raw = file_get_contents('php://input');
        // LOG EVERY CALL (MOVE TO TOP FOR RELIABILITY)
        file_put_contents(__DIR__ . '/payment_log.txt', date('Y-m-d H:i:s') . " - " . $raw . "\n", FILE_APPEND);
        
        $json = json_decode($raw, true);
        
        // Detect Source & Logic
        $order_id = null; $status = null; $payment_id = null; $gross = 0;

        if (isset($json['transaction_status'])) { // Midtrans
            $order_id = $json['order_id']; 
            $status = $json['transaction_status']; 
            $payment_id = $json['transaction_id']; 
            $gross = $json['gross_amount'];
            // MAP Midway statuses to settlement
            if ($status == 'capture' || $status == 'settlement') $status = 'settlement';
        } elseif (isset($json['external_id'])) { // Xendit
            $order_id = $json['external_id']; $status = ($json['status'] == 'PAID' ? 'settlement' : 'pending'); $payment_id = $json['id']; $gross = $json['amount'];
        } elseif (isset($json['merchant_ref'])) { // Tripay
            $order_id = $json['merchant_ref']; $status = ($json['status'] == 'PAID' ? 'settlement' : 'pending'); $payment_id = $json['reference']; $gross = $json['total_amount'];
        }

        if ($order_id && $status == 'settlement') {
            // Check if topup
            if (strpos($order_id, 'TOPUP-') === 0) {
                $stmt = $pdo->prepare("SELECT * FROM tbl_topup WHERE order_id=?");
                $stmt->execute([$order_id]);
                $topup = $stmt->fetch();
                if ($topup && $topup['status'] !== 'settlement') {
                    $pdo->prepare("UPDATE tbl_topup SET status='settlement', response_gateway=? WHERE order_id=?")->execute([$raw, $order_id]);
                    $pdo->prepare("UPDATE tbl_siswa SET saldo = saldo + ? WHERE id_siswa=?")->execute([$topup['nominal'], $topup['id_user']]);
                    
                    // WA NOTIF TOPUP
                    require_once __DIR__ . '/wa_helper.php';
                    wa_notif_tabungan($topup['id_user'], 'Setor', $topup['nominal'], "Top-up via Gateway", 0); 
                }
            } else {
                // BILLING PAYMENT
                $stmt = $pdo->prepare("SELECT * FROM tbl_payment WHERE order_id=?");
                $stmt->execute([$order_id]);
                $pay = $stmt->fetch();

                if ($pay && $pay['status'] !== 'settlement') {
                    $pdo->prepare("UPDATE tbl_payment SET status='settlement', transaction_time=NOW() WHERE order_id=?")->execute([$order_id]);
                    
                    // Get Active TA
                    $ta = get_ta_aktif($pdo);
                    $id_ta = $ta['id_ta'] ?? null;

                    // Route to Financial Tables
                    $items_stmt = $pdo->prepare("SELECT * FROM tbl_payment_items WHERE order_id=?");
                    $items_stmt->execute([$order_id]);
                    $bulk_items = $items_stmt->fetchAll();

                    $wa_items_summary = [];

                    if (!empty($bulk_items)) {
                        foreach ($bulk_items as $it) {
                            $bulan_name = $it['bulan'] > 0 ? bulan_indo($it['bulan']) : '';
                            $wa_items_summary[] = $it['nama_jenis'] . ($bulan_name ? " ($bulan_name)" : "");

                            if ($it['tipe'] == 'Bulanan') {
                                $pdo->prepare("INSERT INTO tbl_pembayaran (id_siswa,id_jenis,bulan,tahun,jumlah_bayar,cara_bayar,teller,keterangan,id_ta) VALUES (?,?,?,?,?,'Online','Gateway',?,?)")
                                    ->execute([$pay['id_siswa'], $it['id_jenis'], $it['bulan'], $it['tahun'], $it['jumlah'], "INV:$order_id", $id_ta]);
                            } else {
                                $pdo->prepare("INSERT INTO tbl_pembayaran_bebas (id_siswa,id_jenis,jumlah_bayar,cara_bayar,teller,keterangan) VALUES (?,?,?, 'Online','Gateway',?)")
                                    ->execute([$pay['id_siswa'], $it['id_jenis'], $it['jumlah'], "INV:$order_id"]);
                            }
                        }
                    } else {
                        // Fallback Single Item
                        $jb = $pdo->query("SELECT tipe, nama_jenis FROM tbl_jenis_bayar WHERE id_jenis=".$pay['id_jenis'])->fetch();
                        $wa_items_summary[] = $jb['nama_jenis'] . ($pay['bulan'] ? " (".bulan_indo($pay['bulan']).")" : "");
                        
                        if ($jb['tipe'] == 'Bulanan') {
                            $pdo->prepare("INSERT INTO tbl_pembayaran (id_siswa,id_jenis,bulan,tahun,jumlah_bayar,cara_bayar,teller,keterangan,id_ta) VALUES (?,?,?,?,?,'Online','Gateway',?,?)")
                                ->execute([$pay['id_siswa'], $pay['id_jenis'], $pay['bulan'], date('Y'), $pay['gross_amount'], "INV:$order_id", $id_ta]);
                        } else {
                            $pdo->prepare("INSERT INTO tbl_pembayaran_bebas (id_siswa,id_jenis,jumlah_bayar,cara_bayar,teller,keterangan) VALUES (?,?,?, 'Online','Gateway',?)")
                                ->execute([$pay['id_siswa'], $pay['id_jenis'], $pay['gross_amount'], "INV:$order_id"]);
                        }
                    }

                    // SEND WA NOTIFICATION
                    require_once __DIR__ . '/wa_helper.php';
                    wa_notif_pembayaran($pay['id_siswa'], $pay['gross_amount'], implode(', ', $wa_items_summary), 'Gateway Online', date('Y-m-d'));
                }
            }
        }
        echo json_encode(['status'=>'ok']);
        break;

    default:
        echo json_encode(['status'=>'error','message'=>'Action not recognized']);
        break;
}
