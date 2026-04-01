<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if admin is logged in
require_once '../includes/session_helper.php';
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$clientId = $_POST['client_id'] ?? '';
$secret = $_POST['secret'] ?? '';
$mode = $_POST['mode'] ?? 'sandbox';

if (empty($clientId) || empty($secret)) {
    echo json_encode(['success' => false, 'message' => 'Client ID and Secret are required']);
    exit;
}

$api_url = ($mode === 'live') ? "https://api-m.paypal.com" : "https://api-m.sandbox.paypal.com";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$api_url/v1/oauth2/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
curl_setopt($ch, CURLOPT_USERPWD, $clientId . ":" . $secret);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(['success' => false, 'message' => "Connection Error: $error"]);
    exit;
}

$data = json_decode($response, true);

if ($httpCode === 200 && isset($data['access_token'])) {
    echo json_encode([
        'success' => true,
        'message' => 'PayPal connection successful! Credentials are valid.',
        'app_id' => $data['app_id'] ?? 'N/A'
    ]);
} else {
    $errorMsg = $data['error_description'] ?? $data['message'] ?? 'Invalid credentials or API error';
    echo json_encode([
        'success' => false,
        'message' => "PayPal Error ($httpCode): $errorMsg"
    ]);
}
?>
