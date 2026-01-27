<?php
require_once 'header.php';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM prediction_cache WHERE id = $id");
    header('Location: history');
    exit;
}
?>

<div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-400 text-[10px] uppercase tracking-widest font-bold">
                    <th class="px-8 py-4">Matchup</th>
                    <th class="px-8 py-4">Main Outcome</th>
                    <th class="px-8 py-4">Over/Under</th>
                    <th class="px-8 py-4">Expected Score</th>
                    <th class="px-8 py-4">Date</th>
                    <th class="px-8 py-4">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php
                $history = $conn->query("SELECT * FROM prediction_cache ORDER BY created_at DESC");
                while ($row = $history->fetch_assoc()):
                    $data = json_decode($row['result_json'], true);
                ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-8 py-6">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-slate-900"><?php echo $row['home_team']; ?></span>
                            <span class="text-slate-300 italic text-xs">vs</span>
                            <span class="font-bold text-slate-900"><?php echo $row['away_team']; ?></span>
                        </div>
                    </td>
                    <td class="px-8 py-6">
                        <span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-xs font-black uppercase"><?php echo $data['mainPrediction']; ?></span>
                        <span class="text-slate-400 text-xs ml-2"><?php echo $data['mainProbability']; ?>%</span>
                    </td>
                    <td class="px-8 py-6 text-sm font-medium text-slate-600">
                        <?php echo $data['overUnderPrediction']; ?> (<?php echo $data['overUnderProbability']; ?>%)
                    </td>
                    <td class="px-8 py-6 font-black text-slate-900">
                        <?php echo $data['expectedScore']; ?>
                    </td>
                    <td class="px-8 py-6 text-sm text-slate-400">
                        <?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?>
                    </td>
                    <td class="px-8 py-6">
                        <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this prediction from cache?')" class="text-red-400 hover:text-red-600 transition-colors">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($history->num_rows == 0): ?>
                    <tr>
                        <td colspan="6" class="px-8 py-20 text-center text-slate-400">No predictions in cache.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
