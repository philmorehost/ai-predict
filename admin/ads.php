<?php
require_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['ads'] as $id => $data) {
        $ad_code = $data['code'];
        $is_active = isset($data['active']) ? 1 : 0;

        $stmt = $conn->prepare("UPDATE ads SET ad_code = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("sii", $ad_code, $is_active, $id);
        $stmt->execute();
    }
    $success = "Ads updated successfully!";
}

$ads = $conn->query("SELECT * FROM ads");
$settings = getSettings($conn);
?>

<div class="max-w-4xl">
    <div class="bg-amber-50 border border-amber-200 text-amber-800 p-6 rounded-3xl mb-8 flex items-center justify-between">
        <div>
            <h3 class="font-black uppercase text-xs tracking-widest mb-1">Global Ad Expiry</h3>
            <p class="text-sm">Currently set to: <span class="font-bold"><?php echo $settings['ad_expiry_date'] ?: 'No Expiry'; ?></span></p>
        </div>
        <a href="settings" class="px-4 py-2 bg-amber-600 text-white rounded-xl font-bold text-xs">Change in Settings</a>
    </div>
    <?php if (isset($success)): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 p-4 rounded-xl text-sm mb-6 flex items-center gap-3">
            <i class="fas fa-check-circle"></i>
            <p><?php echo $success; ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-8">
        <?php while ($ad = $ads->fetch_assoc()): ?>
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-8">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="font-black text-slate-900 uppercase tracking-tight"><?php echo str_replace('_', ' ', $ad['slot_name']); ?></h3>
                        <p class="text-xs text-slate-400 mt-1">Global placement for this slot</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ads[<?php echo $ad['id']; ?>][active]" class="sr-only peer" <?php echo $ad['is_active'] ? 'checked' : ''; ?>>
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>
                <textarea name="ads[<?php echo $ad['id']; ?>][code]" rows="5" placeholder="Paste your ad code here (HTML, Google Ads, Image tags...)" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-4 px-6 text-slate-900 font-mono text-sm focus:ring-2 focus:ring-emerald-500/30 outline-none transition-all"><?php echo $ad['ad_code']; ?></textarea>
            </div>
        <?php endwhile; ?>

        <button type="submit" class="w-full py-4 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition-all shadow-lg shadow-slate-900/10">
            Update Ad Placements
        </button>
    </form>
</div>

<?php require_once 'footer.php'; ?>
