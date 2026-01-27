<?php
require_once 'includes/header.php';

if (!$settings['news_enabled']) {
    header('Location: index.php');
    exit;
}

// Fetch news from DB
$news_items = $conn->query("SELECT * FROM news ORDER BY created_at DESC LIMIT 20");

$header_ad = getAd($conn, 'header_top');
$footer_ad = getAd($conn, 'result_footer');
?>

<div class="max-w-6xl mx-auto px-4 py-12 md:py-20">
    <!-- Ad Slot: Header Top -->
    <?php if ($header_ad): ?>
        <div class="mb-12 flex justify-center"><?php echo $header_ad; ?></div>
    <?php endif; ?>

    <header class="text-center mb-16">
        <h1 class="text-4xl md:text-6xl font-black tracking-tight text-slate-900 dark:text-white mb-4">Sport News</h1>
        <p class="text-slate-500 dark:text-slate-400 text-lg">Latest updates from the world of sports powered by AI.</p>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php while($item = $news_items->fetch_assoc()): ?>
            <a href="news_details.php?id=<?php echo $item['id']; ?>" class="block group">
                <article class="h-full bg-white dark:bg-slate-800 rounded-3xl overflow-hidden shadow-xl border border-slate-100 dark:border-slate-700 flex flex-col transition-all hover:scale-[1.02] hover:border-emerald-200 dark:hover:border-emerald-900">
                    <div class="h-48 overflow-hidden bg-slate-200 relative">
                        <img src="<?php echo $item['image_url']; ?>" alt="<?php echo $item['title']; ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    </div>
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-2 py-0.5 rounded"><?php echo $item['source']; ?></span>
                            <span class="text-[10px] text-slate-400"><?php echo date('M j, Y', strtotime($item['created_at'])); ?></span>
                        </div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white mb-4 leading-tight group-hover:text-emerald-600 transition-colors"><?php echo $item['title']; ?></h2>
                        <div class="text-slate-600 dark:text-slate-400 text-sm mb-6 line-clamp-3 leading-relaxed">
                            <?php echo strip_tags($item['content']); ?>
                        </div>
                        <div class="mt-auto pt-4 border-t border-slate-50 dark:border-slate-700/50 flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-400 italic"><?php echo $item['source']; ?></span>
                            <span class="text-emerald-600 text-xs font-black uppercase tracking-widest flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all">
                                Read More <i class="fas fa-arrow-right text-[10px]"></i>
                            </span>
                        </div>
                    </div>
                </article>
            </a>
        <?php endwhile; ?>
    </div>

    <?php if ($news_items->num_rows == 0): ?>
        <div class="text-center py-20 bg-white dark:bg-slate-800 rounded-3xl border-2 border-dashed border-slate-100 dark:border-slate-700">
             <i class="fas fa-newspaper text-5xl text-slate-100 dark:text-slate-700 mb-4"></i>
             <p class="text-slate-400">No news articles found. AI is gathering latest updates...</p>
        </div>
    <?php endif; ?>

    <!-- Ad Slot: Result Footer -->
    <?php if ($footer_ad): ?>
        <div class="mt-12 flex justify-center"><?php echo $footer_ad; ?></div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
