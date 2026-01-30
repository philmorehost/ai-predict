<?php
require_once 'header.php';

if (isset($_GET['approve']) || isset($_GET['approve_online'])) {
    $id = (int)($_GET['approve'] ?? $_GET['approve_online']);
    $table = isset($_GET['approve']) ? 'payment_notifications' : 'online_transactions';
    $status_col = isset($_GET['approve']) ? 'approved' : 'success';

    $stmt = $conn->prepare("SELECT t.*, v.credits as v_credits FROM $table t JOIN visitors v ON t.visitor_id = v.id WHERE t.id = ? AND t.status = 'pending'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $t = $stmt->get_result()->fetch_assoc();

    if ($t) {
        // Get package credits
        $pkg_stmt = $conn->prepare("SELECT credits FROM credit_packages WHERE id = ?");
        $pkg_stmt->bind_param("i", $t['package_id']);
        $pkg_stmt->execute();
        $pkg = $pkg_stmt->get_result()->fetch_assoc();

        if ($pkg) {
            $conn->begin_transaction();
            try {
                $new_credits = $t['v_credits'] + $pkg['credits'];
                $conn->query("UPDATE visitors SET credits = $new_credits WHERE id = {$t['visitor_id']}");
                $conn->query("UPDATE $table SET status = '$status_col', is_disputed = 0 WHERE id = $id");
                $conn->commit();
                $success = "Payment approved and credits added!";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Failed to approve payment: " . $e->getMessage();
            }
        }
    }
}

if (isset($_GET['cancel']) || isset($_GET['cancel_online'])) {
    $id = (int)($_GET['cancel'] ?? $_GET['cancel_online']);
    $table = isset($_GET['cancel']) ? 'payment_notifications' : 'online_transactions';
    $status_col = isset($_GET['cancel']) ? 'cancelled' : 'failed';

    $conn->query("UPDATE $table SET status = '$status_col', is_disputed = 0 WHERE id = $id");
    $success = "Payment cancelled!";
}

// Prune proofs older than 1 month (DB and File)
$oneMonthAgo = date('Y-m-d H:i:s', strtotime('-1 month'));
$to_delete = $conn->query("SELECT proof_file FROM payment_notifications WHERE created_at < '$oneMonthAgo' AND proof_file IS NOT NULL");
while($row = $to_delete->fetch_assoc()) {
    @unlink('../' . $row['proof_file']);
}
$conn->query("UPDATE payment_notifications SET proof_file = NULL WHERE created_at < '$oneMonthAgo'");

$payments = $conn->query("SELECT pn.*, v.user_id, v.email, cp.name as package_name FROM payment_notifications pn JOIN visitors v ON pn.visitor_id = v.id JOIN credit_packages cp ON pn.package_id = cp.id ORDER BY pn.is_disputed DESC, pn.created_at DESC");
$online_payments = $conn->query("SELECT ot.*, v.user_id, v.email, cp.name as package_name FROM online_transactions ot JOIN visitors v ON ot.visitor_id = v.id JOIN credit_packages cp ON ot.package_id = cp.id ORDER BY ot.is_disputed DESC, ot.created_at DESC LIMIT 50");
?>

<h2 class="text-2xl font-black text-slate-900 mb-8">Payment Notifications (Manual)</h2>

<?php if (isset($success)): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 p-4 rounded-xl text-sm mb-6">
        <p><?php echo $success; ?></p>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="bg-red-50 border border-red-200 text-red-600 p-4 rounded-xl text-sm mb-6">
        <p><?php echo $error; ?></p>
    </div>
<?php endif; ?>

<div class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm">
    <table class="w-full text-left">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-100">
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Visitor</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Package</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Amount</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Proof</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
            <?php while($p = $payments->fetch_assoc()): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="font-bold text-slate-800"><?php echo $p['user_id']; ?></div>
                            <?php if ($p['is_disputed']): ?>
                                <span class="bg-red-600 text-white text-[8px] font-black px-1.5 py-0.5 rounded uppercase animate-pulse" title="<?php echo htmlspecialchars($p['dispute_reason']); ?>">DISPUTED</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-slate-500"><?php echo $p['email']; ?></div>
                        <?php if ($p['is_disputed']): ?>
                            <div class="mt-1 text-[10px] text-red-600 font-bold italic">"<?php echo $p['dispute_reason']; ?>"</div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-slate-600"><?php echo $p['package_name']; ?></td>
                    <td class="px-6 py-4 text-sm font-bold text-slate-800"><?php echo $p['currency']; ?> <?php echo number_format($p['amount'], 2); ?></td>
                    <td class="px-6 py-4">
                        <?php if ($p['proof_file']): ?>
                            <a href="../<?php echo $p['proof_file']; ?>" target="_blank" class="text-emerald-600 hover:underline text-xs">View Proof</a>
                        <?php else: ?>
                            <span class="text-slate-300 text-xs italic">Deleted/Empty</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-xs font-bold uppercase">
                        <span class="px-2 py-1 rounded-full <?php echo $p['status'] == 'pending' ? 'bg-amber-100 text-amber-600' : ($p['status'] == 'approved' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600'); ?>">
                            <?php echo $p['status']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-xs text-slate-500"><?php echo date('M j, Y', strtotime($p['created_at'])); ?></td>
                    <td class="px-6 py-4 text-right">
                        <?php if ($p['status'] == 'pending'): ?>
                            <a href="?approve=<?php echo $p['id']; ?>" class="text-emerald-600 hover:text-emerald-500 font-bold text-xs mr-4">Approve</a>
                            <a href="?cancel=<?php echo $p['id']; ?>" onclick="return confirm('Cancel this payment?')" class="text-red-500 hover:text-red-400 font-bold text-xs">Cancel</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<h2 class="text-2xl font-black text-slate-900 my-12">Online Transactions (Automatic)</h2>

<div class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm">
    <table class="w-full text-left">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-100">
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Visitor</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Package</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Amount</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Gateway</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Our Ref</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">API Ref</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
            <?php while($p = $online_payments->fetch_assoc()): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="font-bold text-slate-800"><?php echo $p['user_id']; ?></div>
                            <?php if ($p['is_disputed']): ?>
                                <span class="bg-red-600 text-white text-[8px] font-black px-1.5 py-0.5 rounded uppercase animate-pulse" title="<?php echo htmlspecialchars($p['dispute_reason']); ?>">DISPUTED</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-slate-500"><?php echo $p['email']; ?></div>
                        <?php if ($p['is_disputed']): ?>
                            <div class="mt-1 text-[10px] text-red-600 font-bold italic">"<?php echo $p['dispute_reason']; ?>"</div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-slate-600"><?php echo $p['package_name']; ?></td>
                    <td class="px-6 py-4 text-sm font-bold text-slate-800"><?php echo $p['currency']; ?> <?php echo number_format($p['amount'], 2); ?></td>
                    <td class="px-6 py-4 text-xs font-bold text-slate-500 uppercase"><?php echo $p['gateway']; ?></td>
                    <td class="px-6 py-4 text-[10px] font-mono text-slate-400"><?php echo $p['transaction_ref']; ?></td>
                    <td class="px-6 py-4 text-[10px] font-mono text-slate-400"><?php echo $p['api_ref'] ?: '-'; ?></td>
                    <td class="px-6 py-4 text-xs font-bold uppercase">
                        <span class="px-2 py-1 rounded-full <?php echo $p['status'] == 'pending' ? 'bg-amber-100 text-amber-600' : ($p['status'] == 'success' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600'); ?>">
                            <?php echo $p['status']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-xs text-slate-500"><?php echo date('M j, Y H:i', strtotime($p['created_at'])); ?></td>
                    <td class="px-6 py-4 text-right">
                        <?php if ($p['status'] == 'pending'): ?>
                            <a href="?approve_online=<?php echo $p['id']; ?>" class="text-emerald-600 hover:text-emerald-500 font-bold text-xs mr-4">Approve</a>
                            <a href="?cancel_online=<?php echo $p['id']; ?>" onclick="return confirm('Cancel this payment?')" class="text-red-500 hover:text-red-400 font-bold text-xs">Cancel</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once 'footer.php'; ?>
