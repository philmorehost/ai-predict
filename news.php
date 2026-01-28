<?php
require_once 'includes/header.php';

if (!$settings['news_enabled']) {
    header('Location: index.php');
    exit;
}

$now = date('Y-m-d H:i:s');

// Fetch Featured News (Top 3)
$featured_news = $conn->query("SELECT n.*, c.name as category_name FROM news n LEFT JOIN news_categories c ON n.category_id = c.id WHERE n.is_featured = 1 AND (n.published_at IS NULL OR n.published_at <= '$now') ORDER BY n.published_at DESC, n.created_at DESC LIMIT 3");

// Fetch Trending News (Tickers)
$trending_news = $conn->query("SELECT title, id, slug FROM news WHERE is_trending = 1 AND (published_at IS NULL OR published_at <= '$now') ORDER BY published_at DESC, created_at DESC LIMIT 5");

// Fetch all categories with news count
$categories_res = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM news WHERE category_id = c.id AND (published_at IS NULL OR published_at <= '$now')) as news_count FROM news_categories c HAVING news_count > 0 ORDER BY name ASC");
$categories = [];
while($cat = $categories_res->fetch_assoc()) $categories[] = $cat;

// Fetch Recent News (Paginated)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;
$recent_news = $conn->query("SELECT n.*, c.name as category_name FROM news n LEFT JOIN news_categories c ON n.category_id = c.id WHERE (n.published_at IS NULL OR n.published_at <= '$now') ORDER BY n.published_at DESC, n.created_at DESC LIMIT $limit OFFSET $offset");

$total_news = $conn->query("SELECT COUNT(*) FROM news WHERE (published_at IS NULL OR published_at <= '$now')")->fetch_row()[0];
$total_pages = ceil($total_news / $limit);

$header_ad = getAd($conn, 'header_top');
?>

<!-- Trending Bar -->
<?php if ($trending_news->num_rows > 0): ?>
<div class="bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 py-3 overflow-hidden">
    <div class="max-w-6xl mx-auto px-6 flex items-center gap-4">
        <span class="bg-red-600 text-white text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded flex items-center gap-2 shrink-0">
            <span class="relative flex h-2 w-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
            </span>
            Trending
        </span>
        <div class="flex-1 overflow-hidden whitespace-nowrap relative">
            <div class="inline-block animate-[scroll_30s_linear_infinite] hover:pause">
                <?php while($tn = $trending_news->fetch_assoc()): ?>
                    <a href="/news/<?php echo $tn['slug']; ?>" class="text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-emerald-600 mr-12 inline-flex items-center gap-2 italic">
                        <i class="fas fa-bolt text-yellow-500 text-[10px]"></i>
                        <?php echo $tn['title']; ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>
<style>
@keyframes scroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.hover\:pause:hover { animation-play-state: paused; }
</style>
<?php endif; ?>

<div class="max-w-6xl mx-auto px-6 py-12">
    <!-- Ad Slot -->
    <?php if ($header_ad): ?>
        <div class="mb-12 flex justify-center"><?php echo $header_ad; ?></div>
    <?php endif; ?>

    <!-- Featured Section (Magazine Layout) -->
    <?php if ($featured_news->num_rows > 0): ?>
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-16">
        <?php
        $f1 = $featured_news->fetch_assoc();
        $f2 = $featured_news->fetch_assoc();
        $f3 = $featured_news->fetch_assoc();
        ?>

        <!-- Big Featured Card -->
        <div class="lg:col-span-2 relative group overflow-hidden rounded-[2.5rem] aspect-[4/3] lg:aspect-auto h-full">
            <img src="<?php echo $f1['image_url']; ?>" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/20 to-transparent"></div>
            <div class="absolute bottom-0 p-8 md:p-10">
                <span class="bg-emerald-600 text-white text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-lg mb-4 inline-block"><?php echo $f1['category_name']; ?></span>
                <a href="/news/<?php echo $f1['slug']; ?>">
                    <h2 class="text-2xl md:text-4xl font-black text-white leading-tight mb-4 hover:text-emerald-400 transition-colors"><?php echo $f1['title']; ?></h2>
                </a>
                <div class="flex items-center gap-4 text-slate-300 text-xs font-bold">
                    <span><i class="fas fa-user mr-2 text-emerald-500"></i><?php echo $f1['source']; ?></span>
                    <span><i class="fas fa-calendar-alt mr-2 text-emerald-500"></i><?php echo date('M j, Y', strtotime($f1['created_at'])); ?></span>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 grid grid-cols-1 gap-6">
            <?php if ($f2): ?>
            <div class="relative group overflow-hidden rounded-[2.5rem] aspect-video lg:aspect-auto">
                <img src="<?php echo $f2['image_url']; ?>" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent"></div>
                <div class="absolute bottom-0 p-8">
                    <span class="bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-lg mb-3 inline-block"><?php echo $f2['category_name']; ?></span>
                    <a href="/news/<?php echo $f2['slug']; ?>">
                        <h3 class="text-xl font-black text-white hover:text-emerald-400 transition-colors"><?php echo $f2['title']; ?></h3>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($f3): ?>
            <div class="relative group overflow-hidden rounded-[2.5rem] aspect-video lg:aspect-auto">
                <img src="<?php echo $f3['image_url']; ?>" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent"></div>
                <div class="absolute bottom-0 p-8">
                    <span class="bg-purple-600 text-white text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-lg mb-3 inline-block"><?php echo $f3['category_name']; ?></span>
                    <a href="/news/<?php echo $f3['slug']; ?>">
                        <h3 class="text-xl font-black text-white hover:text-emerald-400 transition-colors"><?php echo $f3['title']; ?></h3>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- Main News Column -->
        <div class="lg:col-span-2 space-y-16">

            <!-- Categories Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <?php foreach(array_slice($categories, 0, 4) as $cat): ?>
                <div class="space-y-6">
                    <div class="flex items-center justify-between border-b-2 border-slate-100 dark:border-slate-800 pb-2">
                        <h3 class="text-lg font-black uppercase tracking-tighter text-slate-900 dark:text-white"><?php echo $cat['name']; ?></h3>
                        <a href="#" class="text-[10px] font-black uppercase tracking-widest text-emerald-600 hover:underline">View All</a>
                    </div>
                    <?php
                    $cat_id = $cat['id'];
                    $cat_news = $conn->query("SELECT * FROM news WHERE category_id = $cat_id AND (published_at IS NULL OR published_at <= '$now') ORDER BY published_at DESC, created_at DESC LIMIT 3");
                    $top = $cat_news->fetch_assoc();
                    ?>
                    <?php if ($top): ?>
                    <div class="space-y-4 group">
                        <div class="aspect-video rounded-3xl overflow-hidden relative">
                            <img src="<?php echo $top['image_url']; ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </div>
                        <a href="/news/<?php echo $top['slug']; ?>">
                            <h4 class="font-black text-slate-900 dark:text-white group-hover:text-emerald-600 transition-colors leading-tight"><?php echo $top['title']; ?></h4>
                        </a>
                    </div>
                    <div class="space-y-4 pt-4">
                        <?php while($n = $cat_news->fetch_assoc()): ?>
                        <div class="flex gap-4 items-center group">
                            <div class="h-14 w-14 rounded-xl overflow-hidden shrink-0">
                                <img src="<?php echo $n['image_url']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            </div>
                            <a href="/news/<?php echo $n['slug']; ?>" class="text-xs font-bold text-slate-700 dark:text-slate-300 group-hover:text-emerald-600 transition-all line-clamp-2"><?php echo $n['title']; ?></a>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Recent News -->
            <div class="space-y-8">
                <div class="flex items-center gap-4">
                    <h3 class="text-xl font-black uppercase tracking-tighter text-slate-900 dark:text-white shrink-0">Recent Headlines</h3>
                    <div class="h-px bg-slate-100 dark:bg-slate-800 flex-1"></div>
                </div>

                <div class="grid grid-cols-1 gap-8">
                    <?php while($item = $recent_news->fetch_assoc()): ?>
                    <div class="flex flex-col md:flex-row gap-6 group">
                        <div class="md:w-1/3 aspect-[4/3] rounded-[2rem] overflow-hidden shrink-0">
                            <img src="<?php echo $item['image_url']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                        </div>
                        <div class="flex-1 flex flex-col justify-center py-2">
                            <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 mb-2"><?php echo $item['category_name']; ?></span>
                            <a href="/news/<?php echo $item['slug']; ?>">
                                <h4 class="text-xl font-black text-slate-900 dark:text-white mb-3 group-hover:text-emerald-600 transition-colors leading-tight"><?php echo $item['title']; ?></h4>
                            </a>
                            <p class="text-slate-500 dark:text-slate-400 text-sm line-clamp-2 mb-4 leading-relaxed"><?php echo strip_tags($item['content']); ?></p>
                            <div class="flex items-center gap-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                <span><i class="fas fa-clock mr-1"></i> <?php echo date('M j, Y', strtotime($item['created_at'])); ?></span>
                                <span><i class="fas fa-eye mr-1"></i> <?php echo number_format($item['views']); ?> Views</span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="flex justify-center gap-2 mt-12">
                    <?php for($i=1; $i<=$total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="h-10 w-10 flex items-center justify-center rounded-xl font-bold text-sm <?php echo $page == $i ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'; ?> transition-all"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <aside class="space-y-12">
            <!-- Search -->
            <div class="bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-700">
                <h4 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Search News</h4>
                <div class="relative">
                    <input type="text" placeholder="Keyword..." class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-3 px-5 outline-none focus:ring-2 focus:ring-emerald-500/20 text-sm">
                    <button class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400"><i class="fas fa-search"></i></button>
                </div>
            </div>

            <!-- Ad Slot -->
            <div class="bg-slate-900 rounded-[2.5rem] overflow-hidden min-h-[250px] flex items-center justify-center relative group">
                <div class="absolute inset-0 opacity-20 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
                <?php echo getAd($conn, 'mid_content'); ?>
            </div>

            <!-- Categories Widget -->
            <div class="bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-700">
                <h4 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6 border-b border-slate-50 dark:border-slate-700 pb-2">Categories</h4>
                <div class="space-y-2">
                    <?php foreach($categories as $cat): ?>
                    <a href="#" class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-all">
                        <span class="text-sm font-bold text-slate-600 dark:text-slate-300"><?php echo $cat['name']; ?></span>
                        <span class="bg-slate-100 dark:bg-slate-700 text-[10px] font-black px-2 py-1 rounded-lg text-slate-500"><?php echo $cat['news_count']; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Most Viewed News -->
            <div class="bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-700">
                <h4 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6 border-b border-slate-50 dark:border-slate-700 pb-2">Most Popular</h4>
                <div class="space-y-6">
                    <?php
                    $popular = $conn->query("SELECT * FROM news WHERE (published_at IS NULL OR published_at <= '$now') ORDER BY views DESC LIMIT 5");
                    $rank = 1;
                    while($p = $popular->fetch_assoc()):
                    ?>
                    <div class="flex gap-4 items-start group">
                        <span class="text-2xl font-black text-slate-100 dark:text-slate-700 italic group-hover:text-emerald-500 transition-colors">0<?php echo $rank++; ?></span>
                        <div class="space-y-1">
                            <a href="/news/<?php echo $p['slug']; ?>" class="text-xs font-black text-slate-800 dark:text-slate-200 group-hover:text-emerald-600 transition-all line-clamp-2"><?php echo $p['title']; ?></a>
                            <div class="text-[9px] font-bold text-slate-400 uppercase"><?php echo date('M j, Y', strtotime($p['created_at'])); ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
