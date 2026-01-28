<?php
require_once 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$now = date('Y-m-d H:i:s');
$stmt = $conn->prepare("SELECT n.*, c.name as category_name FROM news n LEFT JOIN news_categories c ON n.category_id = c.id WHERE n.id = ? AND (n.published_at IS NULL OR n.published_at <= ?)");
$stmt->bind_param("is", $id, $now);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    header('Location: news.php');
    exit;
}

// Increment views
$conn->query("UPDATE news SET views = views + 1 WHERE id = $id");

$header_ad = getAd($conn, 'header_top');
$sidebar_ad = getAd($conn, 'mid_content');

// Fetch related news
$related_res = $conn->query("SELECT * FROM news WHERE category_id = {$item['category_id']} AND id != $id AND (published_at IS NULL OR published_at <= '$now') ORDER BY published_at DESC, created_at DESC LIMIT 3");
?>

<div class="max-w-6xl mx-auto px-6 py-12">
    <!-- Ad Slot -->
    <?php if ($header_ad): ?>
        <div class="mb-12 flex justify-center"><?php echo $header_ad; ?></div>
    <?php endif; ?>

    <!-- Breadcrumbs -->
    <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-400 mb-8">
        <a href="index.php" class="hover:text-emerald-600">Home</a>
        <i class="fas fa-chevron-right text-[8px]"></i>
        <a href="news.php" class="hover:text-emerald-600">News</a>
        <i class="fas fa-chevron-right text-[8px]"></i>
        <span class="text-emerald-600"><?php echo $item['category_name']; ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- Article Content -->
        <article class="lg:col-span-2 space-y-8">
            <header class="space-y-6">
                <span class="bg-emerald-600 text-white text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-lg inline-block"><?php echo $item['category_name']; ?></span>
                <h1 class="text-3xl md:text-5xl font-black text-slate-900 dark:text-white leading-tight tracking-tighter"><?php echo $item['title']; ?></h1>

                <div class="flex flex-wrap items-center gap-6 border-y border-slate-100 dark:border-slate-800 py-4">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 bg-slate-200 dark:bg-slate-700 rounded-full flex items-center justify-center text-xs font-black text-slate-500"><?php echo substr($item['source'], 0, 1); ?></div>
                        <div class="text-[10px] font-black uppercase tracking-widest">
                            <p class="text-slate-400">Published by</p>
                            <p class="text-slate-900 dark:text-white"><?php echo $item['source']; ?></p>
                        </div>
                    </div>
                    <div class="text-[10px] font-black uppercase tracking-widest">
                        <p class="text-slate-400">Date</p>
                        <p class="text-slate-900 dark:text-white"><?php echo date('M j, Y', strtotime($item['created_at'])); ?></p>
                    </div>
                    <div class="text-[10px] font-black uppercase tracking-widest">
                        <p class="text-slate-400">Views</p>
                        <p class="text-slate-900 dark:text-white"><?php echo number_format($item['views']); ?></p>
                    </div>
                    <div class="ml-auto flex gap-2">
                        <button class="h-8 w-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs hover:scale-110 transition-all"><i class="fab fa-facebook-f"></i></button>
                        <button class="h-8 w-8 rounded-full bg-sky-400 text-white flex items-center justify-center text-xs hover:scale-110 transition-all"><i class="fab fa-twitter"></i></button>
                        <button class="h-8 w-8 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs hover:scale-110 transition-all"><i class="fab fa-whatsapp"></i></button>
                    </div>
                </div>
            </header>

            <?php if ($item['image_url']): ?>
            <div class="rounded-[2.5rem] overflow-hidden shadow-xl border border-slate-100 dark:border-slate-800">
                <img src="<?php echo $item['image_url']; ?>" class="w-full h-auto" alt="<?php echo $item['title']; ?>">
            </div>
            <?php endif; ?>

            <div class="prose prose-lg dark:prose-invert max-w-none prose-headings:font-black prose-headings:tracking-tighter prose-headings:italic prose-a:text-emerald-600 dark:text-slate-300 leading-relaxed font-medium">
                <?php echo $item['content']; ?>
            </div>

            <!-- Tags Placeholder -->
            <div class="pt-8 flex flex-wrap gap-2 border-t border-slate-100 dark:border-slate-800">
                <span class="text-[10px] font-black uppercase text-slate-400 mr-2 py-1">Tags:</span>
                <a href="#" class="bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-lg text-[10px] font-bold text-slate-600 dark:text-slate-400 hover:bg-emerald-600 hover:text-white transition-all">Football</a>
                <a href="#" class="bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-lg text-[10px] font-bold text-slate-600 dark:text-slate-400 hover:bg-emerald-600 hover:text-white transition-all">Analysis</a>
                <a href="#" class="bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-lg text-[10px] font-bold text-slate-600 dark:text-slate-400 hover:bg-emerald-600 hover:text-white transition-all">Match Day</a>
            </div>

            <!-- Related Posts -->
            <?php if ($related_res->num_rows > 0): ?>
            <div class="pt-16 space-y-8">
                <div class="flex items-center gap-4">
                    <h3 class="text-xl font-black uppercase tracking-tighter text-slate-900 dark:text-white shrink-0 italic">You Might Also Like</h3>
                    <div class="h-px bg-slate-100 dark:bg-slate-800 flex-1"></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <?php while($rel = $related_res->fetch_assoc()): ?>
                    <a href="news_details.php?id=<?php echo $rel['id']; ?>" class="group">
                        <div class="aspect-video rounded-[2rem] overflow-hidden mb-4 relative">
                            <img src="<?php echo $rel['image_url']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                        </div>
                        <h4 class="font-black text-slate-900 dark:text-white group-hover:text-emerald-600 transition-colors leading-tight line-clamp-2"><?php echo $rel['title']; ?></h4>
                        <p class="text-[10px] font-bold text-slate-400 mt-2"><?php echo date('M j, Y', strtotime($rel['created_at'])); ?></p>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
        </article>

        <!-- Sidebar -->
        <aside class="space-y-12">
            <!-- Author Card -->
            <div class="bg-slate-900 text-white p-8 rounded-[2.5rem] shadow-xl relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/10 blur-3xl -mr-16 -mt-16"></div>
                <div class="relative z-10 text-center">
                    <div class="h-20 w-20 bg-emerald-600 rounded-3xl mx-auto mb-4 flex items-center justify-center text-3xl font-black rotate-6 group-hover:rotate-0 transition-transform"><?php echo substr($item['source'], 0, 1); ?></div>
                    <h4 class="text-xl font-black mb-1"><?php echo $item['source']; ?></h4>
                    <p class="text-[10px] text-emerald-500 font-black uppercase tracking-widest mb-6">Expert Contributor</p>
                    <p class="text-slate-400 text-xs leading-relaxed mb-6">Professional sports analyst and AI data strategist specializing in European football leagues and tactical forecasting.</p>
                    <div class="flex justify-center gap-4">
                        <a href="#" class="text-white/40 hover:text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white/40 hover:text-white"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>

            <!-- Ad Slot -->
            <?php if ($sidebar_ad): ?>
                <div class="flex justify-center"><?php echo $sidebar_ad; ?></div>
            <?php endif; ?>

            <!-- Recent Sidebar -->
            <div class="bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-700">
                <h4 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6 border-b border-slate-50 dark:border-slate-700 pb-2">Latest News</h4>
                <div class="space-y-6">
                    <?php
                    $recent_sidebar = $conn->query("SELECT * FROM news WHERE id != $id ORDER BY created_at DESC LIMIT 4");
                    while($rs = $recent_sidebar->fetch_assoc()):
                    ?>
                    <div class="flex gap-4 items-center group">
                        <div class="h-16 w-16 rounded-2xl overflow-hidden shrink-0">
                            <img src="<?php echo $rs['image_url']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="space-y-1">
                            <a href="news_details.php?id=<?php echo $rs['id']; ?>" class="text-xs font-black text-slate-800 dark:text-slate-200 group-hover:text-emerald-600 transition-all line-clamp-2"><?php echo $rs['title']; ?></a>
                            <div class="text-[9px] font-bold text-slate-400 uppercase"><?php echo date('M j', strtotime($rs['created_at'])); ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
