<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
ensureDatabaseTablesExist($conn);
require_once '../includes/session_helper.php';
ob_start();

if (isset($_SESSION['admin_id'])) {
    session_write_close();
    header('Location: dashboard.php');
    exit;
}
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!$conn) {
        $error = "Database connection error. Please check your config.php";
    } else {
        $stmt = $conn->prepare("SELECT id, password_hash FROM admins WHERE username = ?");
        if (!$stmt) {
            $error = "Database query preparation failed: " . $conn->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password_hash'])) {
                    // Avoid regenerate_id for now as it causes issues on some shared hosts
                    $_SESSION['admin_id'] = $row['id'];
                    $_SESSION['admin_user'] = $username;
                    $conn->query("UPDATE admins SET last_login = NOW() WHERE id = " . $row['id']);

                    header('Location: dashboard.php?login=success');
                    exit;
                } else {
                    $error = "Incorrect password for user '$username'";
                }
            } else {
                $error = "Admin user '$username' not found in database";
            }
        }
    }
}

// Check for session loss from auth.php
if (isset($_GET['err']) && $_GET['err'] == 'session_lost') {
    $error = "Your session expired or could not be established. Please check if your browser allows cookies for this domain.";

    // Add internal diagnostics
    if (!isset($_SESSION['persistence_test'])) {
        $error .= " [Diagnostic: Session Persistence Failed]";
    }
    if (empty($_COOKIE[session_name()])) {
        $error .= " [Diagnostic: Session Cookie Missing]";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - SurePredictor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-950 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-black text-white">SurePredictor <span class="text-emerald-500">Admin</span></h1>
            <p class="text-slate-400">Sign in to manage your platform</p>
        </div>

        <div class="bg-slate-900 rounded-3xl p-8 border border-slate-800 shadow-2xl">
            <?php if (isset($error)): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl text-sm mb-6 flex items-center gap-3">
                    <i class="fas fa-circle-exclamation"></i>
                    <p><?php echo $error; ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-widest">Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" name="username" required class="w-full bg-slate-800 border border-slate-700 rounded-xl py-4 pl-12 pr-4 text-white focus:ring-2 focus:ring-emerald-500/30 outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-widest">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" required class="w-full bg-slate-800 border border-slate-700 rounded-xl py-4 pl-12 pr-4 text-white focus:ring-2 focus:ring-emerald-500/30 outline-none transition-all">
                    </div>
                </div>

                <button type="submit" class="w-full py-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/20 transition-all active:scale-[0.98]">
                    Sign In
                </button>
            </form>
        </div>

        <div class="mt-8 text-center">
            <a href="../" class="text-slate-500 hover:text-emerald-500 text-sm transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Back to Homepage
            </a>
        </div>
    </div>
</body>
</html>
