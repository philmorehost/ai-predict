<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/session_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM visitors WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user['status'] === 'suspended') {
    session_destroy();
    header('Location: login.php?err=account_suspended');
    exit;
}

require_once 'includes/header.php';

// Fetch prediction history
$history = $conn->prepare("SELECT * FROM user_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$history->bind_param("s", $user_id);
$history->execute();
$history_res = $history->get_result();
?>

<?php if (isset($_SESSION['admin_impersonating'])): ?>
    <div class="bg-amber-600 text-white px-6 py-2 flex justify-between items-center sticky top-16 z-[140]">
        <div class="text-xs font-bold uppercase tracking-widest"><i class="fas fa-user-secret mr-2"></i> Impersonating User: <?php echo htmlspecialchars($user['full_name']); ?></div>
        <a href="api/user_auth.php?action=stop_impersonating" class="bg-white text-amber-600 px-3 py-1 rounded-lg text-[10px] font-black uppercase">Switch back to Admin</a>
    </div>
<?php endif; ?>

<div class="max-w-6xl mx-auto px-6 py-12">
    <!-- Welcome Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
        <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white">Hi, <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h1>
            <p class="text-slate-500">Welcome to your tactical command center.</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="showSubscribeModal()" class="px-6 py-3 bg-emerald-600 text-white rounded-2xl font-bold shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 transition-all">
                <i class="fas fa-wallet mr-2"></i> Top Up Credits
            </button>
            <button onclick="logout()" class="p-3 bg-slate-100 dark:bg-slate-800 rounded-2xl text-slate-400 hover:text-red-500 transition-all">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 p-8 rounded-[2.5rem] shadow-sm">
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Available Balance</div>
            <div class="text-4xl font-black text-emerald-600"><?php echo number_format($user['credits'], 2); ?> <span class="text-lg opacity-50">Credits</span></div>
        </div>
        <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 p-8 rounded-[2.5rem] shadow-sm">
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Predictions</div>
            <div class="text-4xl font-black text-slate-900 dark:text-white"><?php echo $user['total_predictions']; ?></div>
        </div>
        <div class="bg-slate-900 text-white p-8 rounded-[2.5rem] shadow-xl relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/10 blur-3xl -mr-16 -mt-16 group-hover:bg-emerald-500/20 transition-all"></div>
            <div class="relative z-10">
                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Account ID</div>
                <div class="text-2xl font-mono font-bold text-white mb-2"><?php echo $user['user_id']; ?></div>
                <p class="text-[10px] text-slate-500 italic">*Use this ID for bank transfer references</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- Main Prediction Area (Left 2/3) -->
        <div class="lg:col-span-2 space-y-12">
            <div id="prediction-card" class="bg-white dark:bg-slate-800 border-4 border-emerald-500/20 p-8 md:p-12 rounded-[3rem] shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500/5 blur-[100px] -mr-32 -mt-32 rounded-full"></div>
                <div class="relative z-10">
                    <div class="mb-8 text-center md:text-left">
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight italic">Generate AI Analysis</h2>
                        <p class="text-slate-500">Analyze any match in seconds with our advanced AI models.</p>
                    </div>

                    <?php include 'includes/prediction_form.php'; ?>
                </div>
            </div>

            <!-- Prediction History Placeholder -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">Your Recent Forecasts</h3>
                    <a href="#" class="text-xs font-bold text-emerald-600 hover:underline">View All</a>
                </div>

                <div id="user-history-container" class="grid grid-cols-1 gap-4">
                    <?php if ($history_res->num_rows > 0): ?>
                        <?php while ($row = $history_res->fetch_assoc()): ?>
                            <?php $data = json_decode($row['result_json'], true); ?>
                            <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 p-6 rounded-[2rem] shadow-sm flex items-center justify-between group hover:border-emerald-500 transition-all">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-sm font-bold text-slate-700 dark:text-white"><?php echo htmlspecialchars($row['home_team']); ?></span>
                                        <span class="text-[10px] text-slate-300 italic font-black">VS</span>
                                        <span class="text-sm font-bold text-slate-700 dark:text-white"><?php echo htmlspecialchars($row['away_team']); ?></span>
                                    </div>
                                    <div class="text-[10px] font-black text-emerald-600 uppercase tracking-widest"><?php echo $data['mainPrediction'] ?? 'Analyzed'; ?></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xl font-black text-slate-900 dark:text-white tracking-tighter"><?php echo $data['expectedScore'] ?? '?-?'; ?></div>
                                    <div class="text-[9px] text-slate-400 font-bold"><?php echo date('M j, Y', strtotime($row['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="p-8 bg-slate-50 dark:bg-slate-900/50 rounded-[2rem] border-2 border-dashed border-slate-200 dark:border-slate-800 text-center">
                            <p class="text-slate-400 text-sm">Your recent predictions will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar (Right 1/3) -->
        <div class="space-y-8">
            <!-- Profile Info Card -->
            <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 p-8 rounded-[2.5rem] shadow-sm">
                <h3 class="text-lg font-black text-slate-900 dark:text-white mb-6 uppercase tracking-tight">Profile Details</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Full Name</label>
                        <div class="text-sm font-bold text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars($user['full_name']); ?></div>
                    </div>
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Email Address</label>
                        <div class="text-sm font-bold text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Phone</label>
                        <div class="text-sm font-bold text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars($user['phone'] ?: 'Not set'); ?></div>
                    </div>
                    <button onclick="showProfile()" class="w-full mt-4 py-3 bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 rounded-xl font-bold text-xs hover:bg-slate-100 transition-all border border-slate-100 dark:border-slate-700">
                        <i class="fas fa-user-edit mr-2"></i> Edit Profile Info
                    </button>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-emerald-600 p-8 rounded-[2.5rem] shadow-xl text-white">
                <h3 class="text-lg font-black mb-6 uppercase tracking-tight">Quick Actions</h3>
                <div class="grid grid-cols-1 gap-3">
                    <a href="news.php" class="flex items-center justify-between p-4 bg-white/10 rounded-2xl hover:bg-white/20 transition-all">
                        <span class="font-bold text-sm">Sports News</span>
                        <i class="fas fa-newspaper"></i>
                    </a>
                    <a href="index.php#pricing" onclick="showSubscribeModal(); return false;" class="flex items-center justify-between p-4 bg-white/10 rounded-2xl hover:bg-white/20 transition-all">
                        <span class="font-bold text-sm">Buy Credits</span>
                        <i class="fas fa-plus-circle"></i>
                    </a>
                    <a href="purchases.php" class="flex items-center justify-between p-4 bg-white/10 rounded-2xl hover:bg-white/20 transition-all">
                        <span class="font-bold text-sm">Purchase History</span>
                        <i class="fas fa-history"></i>
                    </a>
                    <a href="https://wa.me/<?php echo $settings['whatsapp_number']; ?>" target="_blank" class="flex items-center justify-between p-4 bg-white/10 rounded-2xl hover:bg-white/20 transition-all">
                        <span class="font-bold text-sm">Support Chat</span>
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function logout() {
    fetch('api/user_auth?action=logout')
        .then(() => {
            localStorage.removeItem('visitor_id');
            window.location.href = 'login.php';
        });
}
</script>

<?php require_once 'includes/footer.php'; ?>
