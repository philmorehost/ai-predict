<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
ensureDatabaseTablesExist($conn);
require_once '../includes/session_helper.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'register') {
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $full_name = isset($_POST['full_name']) ? sanitize($_POST['full_name']) : '';
    $username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
    $password = $_POST['password'] ?? '';
    $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';

    $missing = [];
    if (empty($email)) $missing[] = "Email";
    if (empty($password)) $missing[] = "Password";
    if (empty($full_name)) $missing[] = "Full Name";
    if (empty($username)) $missing[] = "Username";

    if (!empty($missing)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields: ' . implode(', ', $missing)]);
        exit;
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM visitors WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already registered.']);
        exit;
    }

    // Check if username exists
    $stmt = $conn->prepare("SELECT id FROM visitors WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already taken.']);
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $user_id = 'SP-' . strtoupper(substr(md5(uniqid()), 0, 8));

    $stmt = $conn->prepare("INSERT INTO visitors (user_id, email, full_name, password_hash, phone, username) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $user_id, $email, $full_name, $password_hash, $phone, $username);

    if ($stmt->execute()) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['email'] = $email;
        $_SESSION['full_name'] = $full_name;
        echo json_encode(['success' => true, 'message' => 'Registration successful!', 'user_id' => $user_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $conn->error]);
    }
}

if ($action === 'login') {
    $identifier = isset($_POST['identifier']) ? sanitize($_POST['identifier']) : ''; // email or user_id
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM visitors WHERE email = ? OR user_id = ? OR username = ?");
    $stmt->bind_param("sss", $identifier, $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if ($user['status'] === 'suspended') {
            echo json_encode(['success' => false, 'message' => 'Your account has been suspended. Please contact support.']);
            exit;
        }
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            echo json_encode(['success' => true, 'message' => 'Login successful!', 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
}

if ($action === 'stop_impersonating') {
    unset($_SESSION['admin_impersonating']);
    unset($_SESSION['user_id']);
    unset($_SESSION['email']);
    unset($_SESSION['full_name']);
    header("Location: ../admin/users.php");
    exit;
}
