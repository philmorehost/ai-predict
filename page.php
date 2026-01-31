<?php
$slug = isset($_GET['slug']) ? htmlspecialchars(strip_tags(trim($_GET['slug']))) : '';

if (empty($slug)) {
    header('Location: /');
    exit;
}

require_once 'includes/config.php';
require_once 'includes/functions.php';

$stmt = $conn->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published' LIMIT 1");
$stmt->bind_param("s", $slug);
$stmt->execute();
$page_data = $stmt->get_result()->fetch_assoc();

if (!$page_data) {
    header('Location: /');
    exit;
}

// Set dynamic SEO before including header
$custom_seo = [
    'title' => $page_data['meta_title'] ?: $page_data['title'],
    'description' => $page_data['meta_description'],
    'keywords' => $page_data['meta_keywords']
];

require_once 'includes/header.php';
?>

<div class="max-w-4xl mx-auto px-6 py-20">
    <div class="mb-12">
        <h1 class="text-4xl md:text-6xl font-black text-slate-900 dark:text-white uppercase tracking-tighter italic leading-tight"><?php echo $page_data['title']; ?></h1>
        <div class="h-2 w-20 bg-emerald-600 mt-6 rounded-full"></div>
    </div>

    <?php if ($page_data['image_url']): ?>
    <div class="mb-12 rounded-[3rem] overflow-hidden shadow-2xl border border-slate-100 dark:border-slate-800">
        <img src="<?php echo $page_data['image_url']; ?>" class="w-full h-auto object-cover max-h-[500px]" alt="<?php echo $page_data['title']; ?>">
    </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-slate-800 rounded-[3rem] p-8 md:p-16 shadow-xl border border-slate-100 dark:border-slate-800 prose prose-lg dark:prose-invert max-w-none prose-headings:font-black prose-headings:tracking-tighter prose-headings:italic prose-a:text-emerald-600 dark:text-slate-300 leading-relaxed font-medium">
        <?php echo $page_data['content']; ?>
    </div>

    <div class="mt-12 text-center">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Last Updated: <?php echo date('F j, Y', strtotime($page_data['updated_at'])); ?></p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
