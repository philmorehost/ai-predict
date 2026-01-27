<?php
require_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meta_title = $_POST['meta_title'];
    $meta_description = $_POST['meta_description'];

    $stmt = $conn->prepare("UPDATE seo_settings SET meta_title = ?, meta_description = ? WHERE id = 1");
    $stmt->bind_param("ss", $meta_title, $meta_description);

    if ($stmt->execute()) {
        $success = "SEO settings updated!";
    }
}

$seo = getSeoSettings($conn);
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
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Meta Title</label>
            <input type="text" name="meta_title" value="<?php echo $seo['meta_title']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-4 px-6 text-slate-900 focus:ring-2 focus:ring-emerald-500/30 outline-none transition-all">
            <p class="text-[10px] text-slate-400 mt-2">Optimal length: 50-60 characters</p>
        </div>

        <div>
            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Meta Description</label>
            <textarea name="meta_description" rows="5" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-4 px-6 text-slate-900 focus:ring-2 focus:ring-emerald-500/30 outline-none transition-all"><?php echo $seo['meta_description']; ?></textarea>
            <p class="text-[10px] text-slate-400 mt-2">Optimal length: 150-160 characters</p>
        </div>

        <button type="submit" class="w-full py-4 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition-all shadow-lg shadow-slate-900/10">
            Save SEO Settings
        </button>
    </form>
</div>

<?php require_once 'footer.php'; ?>
