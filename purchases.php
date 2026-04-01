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
$v_internal_id = $user['id'];

if ($user['status'] === 'suspended') {
    session_destroy();
    header('Location: login.php?err=account_suspended');
    exit;
}

require_once 'includes/header.php';

// Fetch all transactions
$query = "
    (SELECT 'Online' as type, id, gateway as method, amount, currency, status, transaction_ref, is_disputed, created_at FROM online_transactions WHERE visitor_id = ?)
    UNION ALL
    (SELECT 'Manual' as type, id, 'Bank Transfer' as method, amount, currency, status, id as transaction_ref, is_disputed, created_at FROM payment_notifications WHERE visitor_id = ?)
    ORDER BY created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $v_internal_id, $v_internal_id);
$stmt->execute();
$purchases = $stmt->get_result();
?>

<div class="max-w-4xl mx-auto px-6 py-12">
    <div class="flex items-center justify-between mb-10">
        <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase italic tracking-tight">Purchase History</h1>
            <p class="text-slate-500">Track your credit top-ups and subscription status.</p>
        </div>
        <a href="dashboard.php" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-600 dark:text-slate-400 font-bold text-sm hover:bg-slate-200 transition-all">
            <i class="fas fa-arrow-left mr-2"></i> Dashboard
        </a>
    </div>

    <div class="space-y-4">
        <?php if ($purchases->num_rows > 0): ?>
            <?php while($p = $purchases->fetch_assoc()): ?>
                <div class="bg-white dark:bg-slate-800 border <?php echo $p['is_disputed'] ? 'border-red-200 dark:border-red-900/50 bg-red-50/30' : 'border-slate-100 dark:border-slate-700'; ?> p-6 rounded-[2rem] shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4 group hover:border-emerald-500 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-2xl flex items-center justify-center <?php echo $p['type'] == 'Online' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'; ?>">
                            <i class="fas <?php echo $p['type'] == 'Online' ? 'fa-globe' : 'fa-university'; ?> text-lg"></i>
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 dark:text-white"><?php echo ucfirst($p['method']); ?></div>
                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest"><?php echo $p['type']; ?> Payment</div>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="text-[10px] font-mono text-slate-400">Ref: <?php echo $p['transaction_ref']; ?></span>
                                <button onclick="copyRef('<?php echo $p['transaction_ref']; ?>')" class="text-[10px] text-emerald-600 font-bold hover:underline">Copy</button>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between md:justify-end gap-6">
                        <div class="text-right">
                            <div class="font-black text-slate-900 dark:text-white italic"><?php echo $p['currency']; ?> <?php echo number_format($p['amount'], 2); ?></div>
                            <div class="flex items-center justify-end gap-2 mt-1">
                                <span class="text-[10px] font-bold <?php echo $p['status'] == 'success' || $p['status'] == 'approved' ? 'text-emerald-500' : ($p['status'] == 'pending' ? 'text-amber-500' : 'text-red-500'); ?> uppercase tracking-widest">
                                    <?php echo $p['status']; ?>
                                </span>
                                <span class="text-[10px] text-slate-300">•</span>
                                <span class="text-[10px] text-slate-400 font-medium"><?php echo date('M j, Y', strtotime($p['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if (!$p['is_disputed'] && ($p['status'] == 'pending' || $p['status'] == 'failed')): ?>
                                <button onclick="reportIssue('<?php echo $p['type']; ?>', <?php echo $p['id']; ?>)" class="px-4 py-2 bg-red-50 text-red-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-100 transition-all">Report Issue</button>
                            <?php elseif ($p['is_disputed']): ?>
                                <span class="px-4 py-2 bg-red-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest">Issue Reported</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="p-12 bg-slate-50 dark:bg-slate-900/50 rounded-[3rem] border-2 border-dashed border-slate-200 dark:border-slate-800 text-center">
                <div class="h-16 w-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
                    <i class="fas fa-receipt text-2xl"></i>
                </div>
                <h3 class="font-bold text-slate-900 dark:text-white mb-1">No transactions found</h3>
                <p class="text-sm text-slate-500 mb-6">You haven't made any purchases yet.</p>
                <button onclick="showSubscribeModal()" class="px-6 py-3 bg-emerald-600 text-white rounded-xl font-bold shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 transition-all">
                    Buy Credits Now
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function copyRef(ref) {
    navigator.clipboard.writeText(ref).then(() => {
        alert('Reference copied to clipboard!');
    });
}

function reportIssue(type, id) {
    const reason = prompt("Please describe the issue (e.g., 'I was debited but not credited'):");
    if (reason && reason.trim().length > 0) {
        const formData = new FormData();
        formData.append('type', type);
        formData.append('id', id);
        formData.append('reason', reason);

        fetch('api/report_issue.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                location.reload();
            }
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
