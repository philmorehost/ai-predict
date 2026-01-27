<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/GeminiClient.php';
require_once '../includes/DeepSeekClient.php';

header('Content-Type: application/json');

$q = $_GET['q'] ?? '';

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$settings = getSettings($conn);
$provider = $settings['ai_provider'] ?? 'gemini';

if ($provider === 'deepseek') {
    if (empty($settings['deepseek_api_key'])) {
        echo json_encode([]);
        exit;
    }
    $client = new DeepSeekClient($settings['deepseek_api_key'], $settings['deepseek_base_url']);
    $model = $settings['deepseek_model_suggestion'] ?? 'deepseek-chat';
} else {
    if (empty($settings['gemini_api_key'])) {
        echo json_encode([]);
        exit;
    }
    $client = new GeminiClient($settings['gemini_api_key']);
    $model = $settings['gemini_model_suggestion'] ?? 'gemini-1.5-flash';
}

try {
    $results = $client->getTeamSuggestions($q, $model);
    echo json_encode($results);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
