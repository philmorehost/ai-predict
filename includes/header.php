<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'session_helper.php';

// Ensure tables exist on every page load to catch updates and handle fresh installs
ensureDatabaseTablesExist($conn);

$settings = getSettings($conn);
$seo = getSeoSettings($conn);

$primary_color = $settings['primary_color'] ?? '#059669';
$site_name = $settings['site_name'] ?? 'SurePredictor';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $settings['dark_mode'] ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="/">
    <title><?php echo $seo['meta_title'] ?: $site_name; ?></title>
    <meta name="description" content="<?php echo $seo['meta_description']; ?>">

    <!-- PWA -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="<?php echo $primary_color; ?>">
    <link rel="apple-touch-icon" href="<?php echo $settings['site_icon'] ?: 'assets/img/icon-192.png'; ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '<?php echo $primary_color; ?>',
                        'primary-dark': '<?php echo $primary_color; ?>e6', // approximate
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://checkout.flutterwave.com/v3.js"></script>
    <script src="https://merchant.beewave.ng/checkout.min.js"></script>
    <style>
        @keyframes roll {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-roll {
            animation: roll 1s ease-in-out;
        }
        .bg-primary { background-color: <?php echo $primary_color; ?>; }
        .text-primary { color: <?php echo $primary_color; ?>; }
        .border-primary { border-color: <?php echo $primary_color; ?>; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 transition-colors duration-300">

<?php require_once 'user_components.php'; ?>

<!-- Navigation -->
<nav class="sticky top-0 bg-white dark:bg-slate-900 z-[150] border-b border-slate-100 dark:border-slate-800">
    <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
        <a href="/" class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-3">
             <?php if ($settings['site_logo']): ?>
                <img src="<?php echo $settings['site_logo']; ?>" alt="Logo" class="h-8 object-contain">
             <?php else: ?>
                <i class="fas fa-chart-line text-emerald-600"></i>
             <?php endif; ?>
             <?php echo $settings['site_name']; ?>
        </a>

        <div class="hidden md:flex items-center gap-8">
            <a href="/" class="text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-emerald-600 transition-all">Home</a>
            <?php if ($settings['news_enabled']): ?>
                <a href="/news" class="text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-emerald-600 transition-all">Sport News</a>
            <?php endif; ?>
            <a href="/#pricing" onclick="handlePricingClick(event)" class="text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-emerald-600 transition-all">Pricing</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/dashboard" class="text-sm font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-4 py-2 rounded-xl">Dashboard</a>
            <?php else: ?>
                <a href="/login" id="nav-login-btn" class="text-sm font-bold text-slate-600 dark:text-slate-400">Login</a>
                <a href="/register" class="text-sm font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-4 py-2 rounded-xl">Sign Up</a>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-4">
            <button onclick="toggleMobileMenu()" class="md:hidden text-slate-600 dark:text-slate-400 p-2"><i class="fas fa-bars text-xl"></i></button>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu" class="fixed inset-0 bg-slate-900/60 z-[200] hidden">
        <div class="bg-white dark:bg-slate-900 w-64 h-full p-8 shadow-2xl animate-in slide-in-from-left duration-300 relative z-[210]">
            <div class="flex justify-between items-center mb-10">
                <div class="text-xl font-black">Menu</div>
                <button onclick="toggleMobileMenu()" class="text-slate-400"><i class="fas fa-times text-xl"></i></button>
            </div>
            <nav class="space-y-6">
                <a href="/" class="block text-lg font-bold text-slate-600 dark:text-slate-300 hover:text-emerald-600">Home</a>
                <?php if ($settings['news_enabled']): ?>
                    <a href="/news" class="block text-lg font-bold text-slate-600 dark:text-slate-300 hover:text-emerald-600">Sport News</a>
                <?php endif; ?>
                <a href="/#pricing" onclick="toggleMobileMenu(); handlePricingClick(event)" class="block text-lg font-bold text-slate-600 dark:text-slate-300">Pricing</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/dashboard" onclick="toggleMobileMenu()" class="block text-lg font-bold text-emerald-600">Dashboard</a>
                    <a href="#" onclick="logout(); return false;" class="block text-lg font-bold text-red-500">Logout</a>
                <?php else: ?>
                    <a href="/login" onclick="toggleMobileMenu()" class="block text-lg font-bold text-slate-600 dark:text-slate-300">Login</a>
                    <a href="/register" onclick="toggleMobileMenu()" class="block text-lg font-bold text-emerald-600">Sign Up</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</nav>

<script>
function handlePricingClick(e) {
    if (window.location.pathname.includes('index.php') || window.location.pathname === '/' || window.location.pathname === '') {
        e.preventDefault();
        if (typeof showSubscribeModal === 'function') {
            showSubscribeModal();
        } else {
            window.location.hash = 'pricing';
        }
    }
}

function logout() {
    fetch('/api/user_auth?action=logout')
        .then(() => {
            localStorage.removeItem('visitor_id');
            window.location.href = 'login.php';
        });
}

function toggleMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
        menu.classList.add('block');
        document.body.style.overflow = 'hidden';
    } else {
        menu.classList.add('hidden');
        menu.classList.remove('block');
        document.body.style.overflow = '';
    }
}
</script>

<script>
    // Set screen fingerprint cookie
    if (!document.cookie.includes('v_screen')) {
        const screenData = window.screen.width + 'x' + window.screen.height + 'x' + window.screen.colorDepth;
        document.cookie = "v_screen=" + screenData + ";path=/;max-age=" + (86400 * 365);
    }
</script>

<!-- Splash Screen -->
<div id="splash-screen" class="fixed inset-0 bg-white dark:bg-slate-950 z-[200] flex items-center justify-center transition-opacity duration-500">
    <div class="text-center">
        <img src="<?php echo $settings['site_logo'] ?: 'assets/img/icon-512.png'; ?>" alt="Logo" class="h-24 w-24 mx-auto mb-4 animate-roll shadow-2xl rounded-3xl">
        <h2 class="text-2xl font-black text-slate-900 dark:text-white">
            <?php
                $name_parts = explode('.', $settings['site_name'] ?: 'SurePredictor.com');
                echo $name_parts[0];
                if (isset($name_parts[1])) echo '<span class="text-emerald-600">.' . $name_parts[1] . '</span>';
            ?>
        </h2>
    </div>
</div>

<script>
    window.addEventListener('load', () => {
        setTimeout(() => {
            const splash = document.getElementById('splash-screen');
            if (splash) {
                splash.style.opacity = '0';
                setTimeout(() => splash.remove(), 500);
            }
        }, 1500);
    });
</script>
