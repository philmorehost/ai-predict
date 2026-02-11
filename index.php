<?php
if (!file_exists('includes/config.php')) {
    header('Location: install/');
    exit;
}
require_once 'includes/header.php';

$header_ads = getAdsByLocation($conn, 'header_text_link');
$body_text_ads = getAdsByLocation($conn, 'body_text_link');
$body_image_ads = getAdsByLocation($conn, 'body_image');

// Fetch last 10 predictions
$last_predictions = $conn->query("SELECT * FROM prediction_cache ORDER BY created_at DESC LIMIT 10");
?>

<div class="max-w-5xl mx-auto px-4 py-12 md:py-20">
    <!-- Ad Slot: Header Text Links -->
    <?php if ($header_ads->num_rows > 0): ?>
        <div class="mb-12 flex flex-wrap justify-center gap-6">
            <?php while($ad = $header_ads->fetch_assoc()): ?>
                <a href="<?php echo htmlspecialchars($ad['anchor_link']); ?>" target="_blank" class="text-emerald-600 font-bold hover:text-emerald-500 transition-all text-sm bg-emerald-50 dark:bg-emerald-900/20 px-4 py-2 rounded-full border border-emerald-100 dark:border-emerald-800/50">
                    <i class="fas fa-external-link-alt mr-2 text-[10px]"></i>
                    <?php echo htmlspecialchars($ad['anchor_text'] ?: 'Visit Partner'); ?>
                </a>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <header class="text-center mb-16 space-y-4">
        <h1 class="text-4xl md:text-6xl font-black tracking-tight text-slate-900 dark:text-white">
            <?php
                $name_parts = explode('.', $settings['site_name'] ?: 'surepredictor.com');
                echo $name_parts[0];
                if (isset($name_parts[1])) echo '<span class="text-emerald-600">.' . $name_parts[1] . '</span>';
            ?>
        </h1>
        <p class="text-slate-500 dark:text-slate-400 text-lg md:text-xl max-w-2xl mx-auto font-light">
            <?php echo $settings['site_description']; ?>
        </p>
    </header>

    <!-- Prediction Input Form -->
    <div id="main-prediction-box" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-8 rounded-3xl shadow-xl mb-12 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500/5 blur-[100px] -mr-32 -mt-32 rounded-full"></div>
        <div class="relative z-10">
            <?php include 'includes/prediction_form.php'; ?>
        </div>
    </div>

    <!-- Ad Slot: Body Image Ads (Beautiful Section) -->
    <?php if ($body_image_ads->num_rows > 0): ?>
        <div class="mb-16">
            <div class="flex items-center gap-4 mb-6">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Featured Partners</span>
                <div class="h-px bg-slate-100 dark:bg-slate-800 flex-1"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while($ad = $body_image_ads->fetch_assoc()): ?>
                    <a href="<?php echo htmlspecialchars($ad['anchor_link']); ?>" target="_blank" class="group relative overflow-hidden rounded-[2rem] border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-xl transition-all duration-500 bg-white dark:bg-slate-800">
                        <img src="<?php echo htmlspecialchars($ad['image_url']); ?>" alt="Ad" class="w-full h-auto block group-hover:scale-105 transition-transform duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-6">
                            <span class="text-white font-bold flex items-center gap-2">
                                Learn More <i class="fas fa-arrow-right text-xs"></i>
                            </span>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Ad Slot: Body Text Links -->
    <?php if ($body_text_ads->num_rows > 0): ?>
        <div class="mb-12 p-8 bg-slate-50 dark:bg-slate-900/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 flex flex-col items-center text-center">
            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">Recommended for you</h4>
            <div class="flex flex-wrap justify-center gap-4">
                <?php while($ad = $body_text_ads->fetch_assoc()): ?>
                    <a href="<?php echo htmlspecialchars($ad['anchor_link']); ?>" target="_blank" class="px-6 py-3 bg-white dark:bg-slate-800 rounded-2xl shadow-sm hover:shadow-md hover:border-emerald-500 border border-transparent transition-all text-slate-700 dark:text-slate-300 font-bold">
                        <?php echo htmlspecialchars($ad['anchor_text']); ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Last 10 Match Forecasts -->
    <?php if ($settings['history_enabled']): ?>
    <div class="mb-16">
        <div class="flex items-center gap-4 mb-8">
            <div class="h-px bg-slate-100 dark:bg-slate-800 flex-1"></div>
            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tighter italic">Last 10 Match Forecasts</h3>
            <div class="h-px bg-slate-100 dark:bg-slate-800 flex-1"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php while($pred = $last_predictions->fetch_assoc()): ?>
                <?php $p_data = json_decode($pred['result_json'], true); ?>
                <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-3xl p-6 shadow-sm flex items-center justify-between group hover:border-emerald-200 transition-all cursor-pointer">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-800 dark:text-white"><?php echo $pred['home_team']; ?></span>
                            <span class="text-[10px] text-slate-300 font-black italic">VS</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white"><?php echo $pred['away_team']; ?></span>
                        </div>
                        <div class="text-[10px] font-black text-emerald-600 uppercase tracking-widest"><?php echo $p_data['mainPrediction'] ?? 'Analyzed'; ?></div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter"><?php echo $p_data['expectedScore'] ?? '?-?'; ?></div>
                        <div class="text-[9px] text-slate-400 font-bold"><?php echo date('M j', strtotime($pred['created_at'])); ?></div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Results Section (Moved inside included form or handled there) -->

    <!-- Ad Slot: Result Footer Links (Handled in includes/footer.php mostly, but keeping slot here if needed) -->
</div>

<!-- Floating WhatsApp Icon -->
<?php if ($settings['whatsapp_number']): ?>
<a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $settings['whatsapp_number']); ?>?text=<?php echo urlencode($settings['whatsapp_text'] ?: 'Hello, I would like to inquire about advertisement placement.'); ?>" target="_blank" class="fixed bottom-8 right-8 z-[150] h-14 w-14 bg-[#25D366] text-white rounded-full shadow-2xl flex items-center justify-center text-2xl animate-bounce transition-all hover:scale-110">
    <i class="fab fa-whatsapp"></i>
    <div class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 rounded-full border-2 border-white animate-pulse"></div>
</a>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
