<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/GeminiClient.php';
require_once '../includes/DeepSeekClient.php';

header('Content-Type: application/json');

// Check if admin is logged in (simple check)
require_once '../includes/session_helper.php';
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$apiKey = $_POST['key'] ?? '';
$model = $_POST['model'] ?? 'gemini-1.5-flash';
$provider = $_POST['provider'] ?? 'gemini';
$baseUrl = $_POST['base_url'] ?? '';

if (empty($apiKey)) {
    echo json_encode(['success' => false, 'message' => 'API Key is missing']);
    exit;
}

if ($provider === 'deepseek') {
    try {
        $client = new DeepSeekClient($apiKey, $baseUrl);
        $result = $client->getPrediction("Team A", "Team B", $model);
        echo json_encode(['success' => true, 'provider' => 'deepseek', 'message' => 'DeepSeek connection successful! AI responded correctly.', 'data' => $result]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'provider' => 'deepseek', 'message' => $e->getMessage()]);
    }
} else {
    try {
        $client = new GeminiClient($apiKey);
        $result = $client->getPrediction("Team A", "Team B", $model);
        echo json_encode(['success' => true, 'provider' => 'gemini', 'message' => 'Gemini connection successful! AI responded correctly.', 'data' => $result]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'provider' => 'gemini', 'message' => $e->getMessage()]);
    }
}
?>
