<?php
require_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admins SET username = ?, password_hash = ? WHERE id = ?");
        $stmt->bind_param("ssi", $username, $password_hash, $_SESSION['admin_id']);
    } else {
        $stmt = $conn->prepare("UPDATE admins SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $username, $_SESSION['admin_id']);
    }

    if ($stmt->execute()) {
        $success = "Profile updated successfully!";
        $_SESSION['admin_user'] = $username;
    }
}

$admin = $conn->query("SELECT * FROM admins WHERE id = " . $_SESSION['admin_id'])->fetch_assoc();
?>

<div class="max-w-2xl">
    <?php if (isset($success)): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 p-4 rounded-xl text-sm mb-6 flex items-center gap-3">
            <i class="fas fa-check-circle"></i>
            <p><?php echo $success; ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-3xl border border-slate-100 shadow-sm p-8 space-y-6">
        <div>
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Username</label>
            <input type="text" name="username" value="<?php echo $admin['username']; ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-xl py-4 px-6 text-slate-900 focus:ring-2 focus:ring-emerald-500/30 outline-none transition-all">
        </div>

        <div>
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">New Password (leave blank to keep current)</label>
            <input type="password" name="password" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-4 px-6 text-slate-900 focus:ring-2 focus:ring-emerald-500/30 outline-none transition-all">
        </div>

        <div class="pt-4">
            <p class="text-[10px] text-slate-400 mb-6">Last Login: <?php echo $admin['last_login'] ?: 'Never'; ?></p>
            <button type="submit" class="w-full py-4 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition-all shadow-lg shadow-slate-900/10">
                Update Profile
            </button>
        </div>
    </form>
</div>

<?php require_once 'footer.php'; ?>
