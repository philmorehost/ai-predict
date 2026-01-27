<?php
require_once 'header.php';

// Stats
$total_predictions = $conn->query("SELECT COUNT(*) FROM prediction_cache")->fetch_row()[0];
$today_predictions = $conn->query("SELECT COUNT(*) FROM prediction_cache WHERE DATE(created_at) = CURDATE()")->fetch_row()[0];
$total_admins = $conn->query("SELECT COUNT(*) FROM admins")->fetch_row()[0];
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
    <!-- Stat Card 1 -->
    <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 transform translate-x-4 -translate-y-4 group-hover:translate-x-0 group-hover:translate-y-0 transition-all duration-500">
            <i class="fas fa-chart-line text-8xl text-emerald-600"></i>
        </div>
        <p class="text-slate-500 font-bold text-xs uppercase tracking-widest mb-1">Total Predictions</p>
        <h3 class="text-4xl font-black text-slate-900"><?php echo $total_predictions; ?></h3>
        <p class="text-emerald-600 text-xs mt-4 font-bold"><i class="fas fa-clock mr-1"></i> Lifetime cached</p>
    </div>

    <!-- Stat Card 2 -->
    <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 transform translate-x-4 -translate-y-4 group-hover:translate-x-0 group-hover:translate-y-0 transition-all duration-500">
            <i class="fas fa-bolt text-8xl text-amber-500"></i>
        </div>
        <p class="text-slate-500 font-bold text-xs uppercase tracking-widest mb-1">Generated Today</p>
        <h3 class="text-4xl font-black text-slate-900"><?php echo $today_predictions; ?></h3>
        <p class="text-amber-600 text-xs mt-4 font-bold"><i class="fas fa-bolt mr-1"></i> New insights today</p>
    </div>

    <!-- Stat Card 3 -->
    <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 transform translate-x-4 -translate-y-4 group-hover:translate-x-0 group-hover:translate-y-0 transition-all duration-500">
            <i class="fas fa-users text-8xl text-blue-600"></i>
        </div>
        <p class="text-slate-500 font-bold text-xs uppercase tracking-widest mb-1">Admin Users</p>
        <h3 class="text-4xl font-black text-slate-900"><?php echo $total_admins; ?></h3>
        <p class="text-blue-600 text-xs mt-4 font-bold"><i class="fas fa-shield-alt mr-1"></i> Access control</p>
    </div>
</div>

<div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="p-8 border-b border-slate-50 flex justify-between items-center">
        <h3 class="text-xl font-black text-slate-900">Recent Prediction History</h3>
        <a href="history" class="text-emerald-600 font-bold text-sm hover:underline">View All</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-400 text-[10px] uppercase tracking-widest font-bold">
                    <th class="px-8 py-4">Matchup</th>
                    <th class="px-8 py-4">Main Outcome</th>
                    <th class="px-8 py-4">Over/Under</th>
                    <th class="px-8 py-4">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php
                $history = $conn->query("SELECT * FROM prediction_cache ORDER BY created_at DESC LIMIT 5");
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
                        <?php echo $data['overUnderPrediction']; ?>
                    </td>
                    <td class="px-8 py-6 text-sm text-slate-400">
                        <?php echo date('M d, H:i', strtotime($row['created_at'])); ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($history->num_rows == 0): ?>
                    <tr>
                        <td colspan="4" class="px-8 py-20 text-center text-slate-400">No predictions generated yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
