<?php
require_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $primary_color = $_POST['primary_color'];
    $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE settings SET primary_color = ?, dark_mode = ? WHERE id = 1");
    $stmt->bind_param("si", $primary_color, $dark_mode);

    if ($stmt->execute()) {
        $success = "Theme settings updated!";
        $settings = getSettings($conn);
    }
}
?>

<div class="max-w-2xl">
    <?php if (isset($success)): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 p-4 rounded-xl text-sm mb-6 flex items-center gap-3">
            <i class="fas fa-check-circle"></i>
            <p><?php echo $success; ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-3xl border border-slate-100 shadow-sm p-8 space-y-8">
        <div>
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Primary Site Color</label>
            <div class="flex items-center gap-6">
                <input type="color" name="primary_color" value="<?php echo $settings['primary_color']; ?>" class="h-20 w-20 rounded-2xl border-none cursor-pointer p-0 overflow-hidden">
                <div>
                    <p class="font-bold text-slate-900"><?php echo strtoupper($settings['primary_color']); ?></p>
                    <p class="text-xs text-slate-400">This color will be used for buttons, icons, and accents across the site.</p>
                </div>
            </div>
        </div>

        <div class="pt-8 border-t border-slate-50 flex justify-between items-center">
            <div>
                <h3 class="font-black text-slate-900 uppercase tracking-tight">Force Dark Mode</h3>
                <p class="text-xs text-slate-400 mt-1">Make the site dark for all visitors by default</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="dark_mode" class="sr-only peer" <?php echo $settings['dark_mode'] ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-slate-900"></div>
            </label>
        </div>

        <button type="submit" class="w-full py-4 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition-all shadow-lg shadow-slate-900/10">
            Apply Theme Changes
        </button>
    </form>
</div>

<?php require_once 'footer.php'; ?>
