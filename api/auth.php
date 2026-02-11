<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/UsageTracker.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $user_id = $_GET['user_id'] ?? '';
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your User ID.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM visitors WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $visitor = $stmt->get_result()->fetch_assoc();

    if ($visitor) {
        require_once '../includes/session_helper.php';
        $_SESSION['user_id'] = $visitor['user_id'];
        $_SESSION['email'] = $visitor['email'];
        $_SESSION['full_name'] = $visitor['full_name'];
        echo json_encode(['success' => true, 'visitor' => $visitor]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User ID not found.']);
    }
}

if ($action === 'reset') {
    $email = $_GET['email'] ?? '';
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your registered email.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT user_id FROM visitors WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $visitor = $stmt->get_result()->fetch_assoc();

    if ($visitor) {
        $to = $email;
        $subject = "Visitor ID Recovery - SurePredictor";
        $msg = "Your Visitor ID is: " . $visitor['user_id'] . "\n\nUse this ID to access your premium features and past predictions.";
        $headers = "From: noreply@surepredictor.com";
        @mail($to, $subject, $msg, $headers);

        echo json_encode(['success' => true, 'message' => "Your User ID has been sent to your email. Check your inbox."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Email not found.']);
    }
}

if ($action === 'update_profile') {
    $user_id = $_POST['user_id'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $full_name = $_POST['full_name'] ?? '';

    if (empty($user_id)) exit;

    $stmt = $conn->prepare("UPDATE visitors SET email = ?, phone = ?, full_name = ? WHERE user_id = ?");
    $stmt->bind_param("ssss", $email, $phone, $full_name, $user_id);
    if ($stmt->execute()) {
        require_once '../includes/session_helper.php';
        $_SESSION['email'] = $email;
        $_SESSION['full_name'] = $full_name;
        echo json_encode(['success' => true, 'message' => 'Profile updated!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed.']);
    }
}
?>
