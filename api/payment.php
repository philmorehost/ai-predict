<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/UsageTracker.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'create_order') {
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $package_id = (int)$_POST['package_id'];
    $fingerprint = UsageTracker::getVisitorIdentifier();
    $ip = $_SERVER['REMOTE_ADDR'];

    // Check if visitor exists or create
    $stmt = $conn->prepare("SELECT * FROM visitors WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $visitor = $stmt->get_result()->fetch_assoc();

    if (!$visitor) {
        $user_id = 'SP-' . strtoupper(substr(md5(time() . $email), 0, 8));
        $stmt = $conn->prepare("INSERT INTO visitors (user_id, email, phone, fingerprint, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $user_id, $email, $phone, $fingerprint, $ip);
        $stmt->execute();
        $v_id = $conn->insert_id;

        // Welcome email
        $to = $email;
        $subject = "Welcome to SurePredictor!";
        $msg = "Thank you for joining us. Your unique Visitor ID is: $user_id\n\nYou can use this ID to track your analysis history and manage your credits.";
        $headers = "From: noreply@surepredictor.com";
        @mail($to, $subject, $msg, $headers);
    } else {
        $v_id = $visitor['id'];
        $user_id = $visitor['user_id'];
    }

    echo json_encode(['success' => true, 'visitor_id' => $v_id, 'user_id' => $user_id]);
}

if ($action === 'bank_transfer') {
    $v_id = (int)$_POST['v_id'];
    $package_id = (int)$_POST['package_id'];
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];

    $proof_file = null;
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === 0) {
        $target_dir = "../assets/proofs/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0755, true);

        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $ext = strtolower(pathinfo($_FILES["proof"]["name"], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $filename = "proof_" . time() . "_" . $v_id . "." . $ext;
            if (move_uploaded_file($_FILES["proof"]["tmp_name"], $target_dir . $filename)) {
                $proof_file = "assets/proofs/" . $filename;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF are allowed.']);
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO payment_notifications (visitor_id, package_id, amount, currency, proof_file) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iidss", $v_id, $package_id, $amount, $currency, $proof_file);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Payment notification submitted! Admin will review soon.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Submission failed.']);
    }
}

if ($action === 'verify_payment') {
    $ref = $_GET['ref'] ?? '';
    $v_id = (int)$_GET['v_id'];
    $pkg_id = (int)$_GET['pkg_id'];
    $provider = $_GET['provider'] ?? '';

    $settings = getSettings($conn);
    $verified = false;

    // Server-side verification with Secret Keys
    if ($provider === 'paystack') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.paystack.co/transaction/verify/" . rawurlencode($ref));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $settings['paystack_secret_key']]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if ($response && $response['status'] && $response['data']['status'] === 'success') $verified = true;
    } elseif ($provider === 'flutterwave') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.flutterwave.com/v3/transactions/" . rawurlencode($ref) . "/verify");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $settings['flutterwave_secret_key'], "Content-Type: application/json"]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if ($response && $response['status'] === 'success' && $response['data']['status'] === 'successful') $verified = true;
    }

    if (!$verified) {
        echo json_encode(['success' => false, 'message' => 'Payment verification failed.']);
        exit;
    }

    $pkg_stmt = $conn->prepare("SELECT credits FROM credit_packages WHERE id = ?");
    $pkg_stmt->bind_param("i", $pkg_id);
    $pkg_stmt->execute();
    $pkg = $pkg_stmt->get_result()->fetch_assoc();

    if ($pkg) {
        $conn->query("UPDATE visitors SET credits = credits + {$pkg['credits']} WHERE id = $v_id");
        echo json_encode(['success' => true, 'message' => 'Payment successful! Credits added.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Verification failed.']);
    }
}
?>
