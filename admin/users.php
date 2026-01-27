<?php
require_once 'auth.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_user'])) {
        $action = $_POST['action_type'];
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $username = sanitize($_POST['username']);
        $phone = sanitize($_POST['phone']);
        $credits = (float)$_POST['credits'];
        $status = $_POST['status'];
        $password = $_POST['password'];

        if ($action === 'create') {
            // Check if exists
            $check = $conn->prepare("SELECT id FROM visitors WHERE email = ? OR username = ?");
            $check->bind_param("ss", $email, $username);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = "Email or Username already exists!";
            } else {
                $user_id = 'SP-' . strtoupper(substr(md5(uniqid()), 0, 8));
                $password_hash = password_hash($password ?: '123456', PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO visitors (user_id, username, full_name, email, phone, credits, status, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssdss", $user_id, $username, $full_name, $email, $phone, $credits, $status, $password_hash);
                $stmt->execute();
                $success = "User created successfully!";
            }
        } else {
            $user_id = $_POST['user_id'];
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE visitors SET full_name = ?, email = ?, username = ?, phone = ?, credits = ?, status = ?, password_hash = ? WHERE user_id = ?");
                $stmt->bind_param("ssssdsss", $full_name, $email, $username, $phone, $credits, $status, $password_hash, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE visitors SET full_name = ?, email = ?, username = ?, phone = ?, credits = ?, status = ? WHERE user_id = ?");
                $stmt->bind_param("ssssdss", $full_name, $email, $username, $phone, $credits, $status, $user_id);
            }
            $stmt->execute();
            $success = "User updated successfully!";
        }
    }

    if (isset($_POST['impersonate'])) {
        $user_id = $_POST['user_id'];
        $stmt = $conn->prepare("SELECT * FROM visitors WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();

        if ($u) {
            $_SESSION['admin_impersonating'] = true;
            $_SESSION['user_id'] = $u['user_id'];
            $_SESSION['email'] = $u['email'];
            $_SESSION['full_name'] = $u['full_name'];
            header("Location: ../dashboard.php");
            exit;
        }
    }
}

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=premium_visitors.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['User ID', 'Username', 'Full Name', 'Email', 'Phone', 'Credits', 'Total Predictions', 'Status', 'Joined Date']);
    $rows = $conn->query("SELECT user_id, username, full_name, email, phone, credits, total_predictions, status, created_at FROM visitors ORDER BY created_at DESC");
    while($row = $rows->fetch_assoc()) fputcsv($output, $row);
    exit;
}

$users = $conn->query("SELECT * FROM visitors ORDER BY created_at DESC");
require_once 'header.php';
?>

<div class="flex justify-between items-center mb-8">
    <h2 class="text-2xl font-black text-slate-900 tracking-tight">Premium Visitors</h2>
    <div class="flex gap-4">
        <button onclick="openCreateModal()" class="bg-emerald-600 text-white px-6 py-2 rounded-xl font-bold text-sm shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 transition-all flex items-center gap-2">
            <i class="fas fa-plus"></i> Create User
        </button>
        <a href="?export=1" class="bg-white border border-slate-200 text-slate-600 px-6 py-2 rounded-xl font-bold text-sm hover:bg-slate-50 transition-all flex items-center gap-2">
            <i class="fas fa-file-export"></i> Export CSV
        </a>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 p-4 rounded-xl text-sm mb-6 flex items-center gap-3">
        <i class="fas fa-check-circle"></i>
        <p><?php echo $success; ?></p>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="bg-red-500/10 border border-red-500/20 text-red-600 p-4 rounded-xl text-sm mb-6 flex items-center gap-3">
        <i class="fas fa-exclamation-circle"></i>
        <p><?php echo $error; ?></p>
    </div>
<?php endif; ?>

<div class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm">
    <table class="w-full text-left">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-100">
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">User / ID</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Contact</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Credits/Usage</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
            <?php while($u = $users->fetch_assoc()): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-bold text-slate-800"><?php echo htmlspecialchars($u['full_name'] ?: 'Guest'); ?></div>
                        <div class="text-[10px] font-mono text-slate-400 uppercase tracking-tight"><?php echo $u['user_id']; ?> (<?php echo htmlspecialchars($u['username']); ?>)</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-slate-600"><?php echo htmlspecialchars($u['email']); ?></div>
                        <div class="text-xs text-slate-400"><?php echo htmlspecialchars($u['phone']); ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-bold text-emerald-600"><?php echo number_format($u['credits'], 2); ?> <span class="text-[9px] text-slate-400 font-normal">Credits</span></div>
                        <div class="text-[10px] text-slate-400"><?php echo $u['total_predictions']; ?> Predictions</div>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($u['status'] === 'suspended'): ?>
                            <span class="px-2 py-1 bg-red-100 text-red-600 text-[9px] font-black uppercase rounded-lg">Suspended</span>
                        <?php else: ?>
                            <span class="px-2 py-1 bg-emerald-100 text-emerald-600 text-[9px] font-black uppercase rounded-lg">Active</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <form method="POST" class="inline">
                                <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                <button type="submit" name="impersonate" class="p-2 text-slate-400 hover:text-blue-600 transition-colors" title="Login as User">
                                    <i class="fas fa-sign-in-alt"></i>
                                </button>
                            </form>
                            <button onclick='editUser(<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8'); ?>)' class="p-2 text-slate-400 hover:text-emerald-600 transition-colors" title="Edit User">
                                <i class="fas fa-user-edit"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- User Modal -->
<div id="user-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] w-full max-w-lg overflow-hidden shadow-2xl animate-in zoom-in duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-xl font-black text-slate-900 uppercase" id="modal-title">User Details</h3>
            <button onclick="closeUserModal()" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-8 space-y-4">
            <input type="hidden" name="user_id" id="edit-user-id">
            <input type="hidden" name="action_type" id="edit-action-type" value="create">
            <input type="hidden" name="save_user" value="1">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-2">Full Name</label>
                    <input type="text" name="full_name" id="edit-full-name" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-2">Username</label>
                    <input type="text" name="username" id="edit-username" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-2">Email Address</label>
                    <input type="email" name="email" id="edit-email" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-2">Credits</label>
                    <input type="number" step="0.01" name="credits" id="edit-credits" required value="0.00" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-2">Phone Number</label>
                <input type="text" name="phone" id="edit-phone" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20">
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-2">Password <span id="pass-help" class="text-slate-400 lowercase">(leave blank to keep current)</span></label>
                <input type="password" name="password" id="edit-password" placeholder="••••••••" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20">
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-2">Account Status</label>
                <select name="status" id="edit-status" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20">
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>

            <button type="submit" class="w-full py-4 bg-slate-900 text-white font-black rounded-2xl shadow-xl hover:bg-slate-800 transition-all mt-4">Save User</button>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modal-title').innerText = 'Create New User';
    document.getElementById('edit-action-type').value = 'create';
    document.getElementById('edit-user-id').value = '';
    document.getElementById('edit-full-name').value = '';
    document.getElementById('edit-username').value = '';
    document.getElementById('edit-email').value = '';
    document.getElementById('edit-phone').value = '';
    document.getElementById('edit-credits').value = '0.00';
    document.getElementById('edit-status').value = 'active';
    document.getElementById('edit-password').required = true;
    document.getElementById('edit-password').placeholder = '';
    document.getElementById('pass-help').classList.add('hidden');
    document.getElementById('user-modal').classList.remove('hidden');
}

function editUser(user) {
    document.getElementById('modal-title').innerText = 'Edit User Details';
    document.getElementById('edit-action-type').value = 'edit';
    document.getElementById('edit-user-id').value = user.user_id;
    document.getElementById('edit-full-name').value = user.full_name;
    document.getElementById('edit-username').value = user.username;
    document.getElementById('edit-email').value = user.email;
    document.getElementById('edit-phone').value = user.phone;
    document.getElementById('edit-credits').value = user.credits;
    document.getElementById('edit-status').value = user.status;
    document.getElementById('edit-password').required = false;
    document.getElementById('edit-password').placeholder = '••••••••';
    document.getElementById('pass-help').classList.remove('hidden');
    document.getElementById('user-modal').classList.remove('hidden');
}

function closeUserModal() {
    document.getElementById('user-modal').classList.add('hidden');
}
</script>

<?php require_once 'footer.php'; ?>
