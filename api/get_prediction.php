<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/GeminiClient.php';
require_once '../includes/DeepSeekClient.php';
require_once '../includes/UsageTracker.php';

header('Content-Type: application/json');

$home = $_GET['home'] ?? '';
$away = $_GET['away'] ?? '';

if (empty($home) || empty($away)) {
    echo json_encode(['error' => 'Missing team names']);
    exit;
}

$match_hash = md5(strtolower(trim($home)) . " vs " . strtolower(trim($away)));

// Check cache
$stmt = $conn->prepare("SELECT result_json, created_at FROM prediction_cache WHERE match_hash = ?");
$stmt->bind_param("s", $match_hash);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Cache for 24 hours
    if (strtotime($row['created_at']) > (time() - 86400)) {
        echo $row['result_json'];
        exit;
    } else {
        // Delete expired cache
        $conn->query("DELETE FROM prediction_cache WHERE match_hash = '$match_hash'");
    }
}

$settings = getSettings($conn);

// Check Subscription / Limits
$visitor_id = $_GET['visitor_id'] ?? '';
$visitor = null;
$identifier = UsageTracker::getVisitorIdentifier();
$tracker = new UsageTracker($conn, $identifier, $settings['free_limit']);

if (!empty($visitor_id)) {
    $stmt = $conn->prepare("SELECT * FROM visitors WHERE user_id = ?");
    $stmt->bind_param("s", $visitor_id);
    $stmt->execute();
    $visitor = $stmt->get_result()->fetch_assoc();
}

if ($visitor) {
    if ($visitor['credits'] < $settings['prediction_charge']) {
        echo json_encode(['error' => 'insufficient_credits', 'message' => 'Your credit balance is low. Please subscribe to continue.']);
        exit;
    }
} else {
    if (!$tracker->checkLimit()) {
        echo json_encode(['error' => 'limit_exceeded', 'message' => 'Daily free limit reached. Please subscribe for unlimited analysis.']);
        exit;
    }
}

$provider = $settings['ai_provider'] ?? 'gemini';

if ($provider === 'deepseek') {
    if (empty($settings['deepseek_api_key'])) {
        echo json_encode(['error' => 'DeepSeek API Key not configured']);
        exit;
    }
    $client = new DeepSeekClient($settings['deepseek_api_key'], $settings['deepseek_base_url']);
    $model = $settings['deepseek_model_prediction'] ?? 'deepseek-chat';
} else {
    if (empty($settings['gemini_api_key'])) {
        echo json_encode(['error' => 'Gemini API Key not configured']);
        exit;
    }
    $client = new GeminiClient($settings['gemini_api_key']);
    $model = $settings['gemini_model_prediction'] ?? 'gemini-1.5-pro';
}

try {
    $prediction = $client->getPrediction($home, $away, $model);
    $prediction_json = json_encode($prediction);

    // Debit credits or record usage
    if ($visitor) {
        $new_bal = $visitor['credits'] - $settings['prediction_charge'];
        $conn->query("UPDATE visitors SET credits = $new_bal, total_predictions = total_predictions + 1 WHERE id = {$visitor['id']}");
    } else {
        $tracker->recordUsage();
    }

    // Save to cache
    $stmt = $conn->prepare("INSERT INTO prediction_cache (match_hash, home_team, away_team, result_json) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $match_hash, $home, $away, $prediction_json);
    $stmt->execute();

    echo $prediction_json;
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
