<?php
require_once 'includes/header.php';

if (!$settings['news_enabled']) {
    header('Location: index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM news WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    header("Location: news.php");
    exit;
}

$header_ad = getAd($conn, 'header_top');
$footer_ad = getAd($conn, 'result_footer');
?>

<div class="max-w-4xl mx-auto px-4 py-12 md:py-20">
    <!-- Ad Slot: Header Top -->
    <?php if ($header_ad): ?>
        <div class="mb-12 flex justify-center"><?php echo $header_ad; ?></div>
    <?php endif; ?>

    <a href="news.php" class="inline-flex items-center gap-2 text-slate-500 hover:text-emerald-600 font-bold text-sm mb-8 transition-all group">
        <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform"></i>
        Back to News
    </a>

    <article class="bg-white dark:bg-slate-800 rounded-[3rem] overflow-hidden shadow-2xl border border-slate-100 dark:border-slate-700">
        <div class="h-64 md:h-96 overflow-hidden bg-slate-200">
            <img src="<?php echo $item['image_url']; ?>" alt="<?php echo $item['title']; ?>" class="w-full h-full object-cover">
        </div>

        <div class="p-8 md:p-12">
            <div class="flex items-center gap-3 mb-6">
                <span class="text-xs font-black uppercase tracking-widest text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-3 py-1 rounded-lg"><?php echo $item['source']; ?></span>
                <span class="text-xs text-slate-400 font-medium"><?php echo date('M j, Y', strtotime($item['created_at'])); ?></span>
            </div>

            <h1 class="text-3xl md:text-5xl font-black text-slate-900 dark:text-white mb-8 leading-tight tracking-tighter italic uppercase"><?php echo $item['title']; ?></h1>

            <div class="prose dark:prose-invert max-w-none text-slate-600 dark:text-slate-400 leading-relaxed text-lg space-y-6">
                <?php echo nl2br($item['content']); ?>
            </div>

            <div class="mt-12 pt-8 border-t border-slate-50 dark:border-slate-700/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="text-sm font-bold text-slate-400 italic">
                    Credits to source: <?php echo $item['source']; ?>
                </div>
                <div class="flex gap-4">
                    <button onclick="window.print()" class="text-slate-400 hover:text-emerald-600 transition-all"><i class="fas fa-print"></i></button>
                    <button onclick="navigator.share({title: '<?php echo addslashes($item['title']); ?>', url: window.location.href})" class="text-slate-400 hover:text-emerald-600 transition-all"><i class="fas fa-share-nodes"></i></button>
                </div>
            </div>
        </div>
    </article>

    <!-- Ad Slot: Result Footer -->
    <?php if ($footer_ad): ?>
        <div class="mt-12 flex justify-center"><?php echo $footer_ad; ?></div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
