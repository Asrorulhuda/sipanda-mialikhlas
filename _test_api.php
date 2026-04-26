<?php
// Test build_deploy.php endpoint
$url = 'http://localhost/sipanda2/api/build_deploy.php';

// Test 1: get_clients (tanpa session - harus Unauthorized)
$ch = curl_init($url . '?action=get_clients');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "=== Test get_clients (no session) ===\n";
echo "HTTP: $code\n";
echo "Response: " . substr($resp, 0, 500) . "\n\n";

// Check if response is valid JSON
$json = json_decode($resp, true);
if ($json) {
    echo "Valid JSON: YES\n";
    print_r($json);
} else {
    echo "Valid JSON: NO\n";
    echo "JSON error: " . json_last_error_msg() . "\n";
    echo "Raw (first 1000 chars):\n" . substr($resp, 0, 1000) . "\n";
}
