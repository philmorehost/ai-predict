<?php
require_once 'header.php';

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=premium_visitors.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['User ID', 'Email', 'Phone', 'Credits', 'Total Predictions', 'Fingerprint', 'Joined Date']);
    $rows = $conn->query("SELECT user_id, email, phone, credits, total_predictions, fingerprint, created_at FROM visitors ORDER BY created_at DESC");
    while($row = $rows->fetch_assoc()) fputcsv($output, $row);
    exit;
}

$users = $conn->query("SELECT * FROM visitors ORDER BY created_at DESC");
?>

<div class="flex justify-between items-center mb-8">
    <h2 class="text-2xl font-black text-slate-900 tracking-tight">Premium Visitors</h2>
    <a href="?export=1" class="bg-slate-900 text-white px-6 py-2 rounded-xl font-bold text-sm shadow-lg shadow-slate-900/10 hover:bg-slate-800 transition-all flex items-center gap-2">
        <i class="fas fa-file-export"></i> Export CSV
    </a>
</div>

<div class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm">
    <table class="w-full text-left">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-100">
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">User ID</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Contact</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Usage</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Credits</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Device ID</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Joined</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
            <?php while($u = $users->fetch_assoc()): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-bold text-slate-800"><?php echo $u['user_id']; ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-slate-600"><?php echo $u['email']; ?></div>
                        <div class="text-xs text-slate-400"><?php echo $u['phone']; ?></div>
                    </td>
                    <td class="px-6 py-4 text-xs font-bold text-slate-600">
                        <?php echo $u['total_predictions']; ?> <span class="text-[9px] text-slate-400 uppercase tracking-tighter">Preds</span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-600">
                            <?php echo $u['credits']; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-[10px] font-mono text-slate-400">
                        <?php echo substr($u['fingerprint'], 0, 16); ?>...
                    </td>
                    <td class="px-6 py-4 text-xs text-slate-500">
                        <?php echo date('M j, Y', strtotime($u['created_at'])); ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once 'footer.php'; ?>
