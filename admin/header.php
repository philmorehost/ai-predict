<?php
require_once 'auth.php';
require_once '../includes/functions.php';
ensureDatabaseTablesExist($conn);
$settings = getSettings($conn);
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo $settings['site_name']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '<?php echo $settings['primary_color'] ?: '#059669'; ?>',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 text-slate-400 p-6 flex flex-col hidden lg:flex">
            <div class="mb-10 px-2">
                <h1 class="text-xl font-black text-white">SurePredictor<span class="text-emerald-500">.</span></h1>
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-600 mt-1">Management Portal</p>
            </div>

            <nav class="space-y-2 flex-1">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $current_page == 'dashboard' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i class="fas fa-grid-2"></i>
                    <span class="font-bold">Dashboard</span>
                </a>
                <a href="history.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $current_page == 'history' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i class="fas fa-history"></i>
                    <span class="font-bold">Prediction History</span>
                </a>
                <a href="users.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $current_page == 'users' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i class="fas fa-users"></i>
                    <span class="font-bold">Premium Visitors</span>
                </a>
                <a href="packages.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $current_page == 'packages' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i class="fas fa-box-open"></i>
                    <span class="font-bold">Credit Packages</span>
                </a>
                <a href="payments.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $current_page == 'payments' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i class="fas fa-money-bill-wave"></i>
                    <span class="font-bold">Payment Requests</span>
                </a>
                <a href="settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $current_page == 'settings' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i class="fas fa-cog"></i>
                    <span class="font-bold">Site Settings</span>
                </a>
                <a href="ads.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $current_page == 'ads' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i class="fas fa-ad"></i>
                    <span class="font-bold">Ads Management</span>
                </a>
                <a href="seo.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $current_page == 'seo' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i class="fas fa-search"></i>
                    <span class="font-bold">SEO Manager</span>
                </a>
                <a href="theme.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $current_page == 'theme' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i class="fas fa-palette"></i>
                    <span class="font-bold">Theme Settings</span>
                </a>
            </nav>

            <div class="pt-6 border-t border-slate-800 space-y-2">
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $current_page == 'profile' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i class="fas fa-user-circle"></i>
                    <span class="font-bold">My Profile</span>
                </a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-red-500/10 hover:text-red-500 transition-all">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="font-bold">Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-4 lg:p-8 overflow-y-auto">
            <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-10">
                <div class="flex items-center justify-between w-full md:w-auto">
                    <div>
                        <h2 class="text-2xl lg:text-3xl font-black text-slate-900 dark:text-white"><?php echo ucwords(str_replace('_', ' ', $current_page)); ?></h2>
                        <p class="text-slate-500 text-sm">Welcome back, <?php echo $_SESSION['admin_user']; ?></p>
                    </div>
                    <button onclick="toggleAdminMenu()" class="lg:hidden p-2 text-slate-600 dark:text-slate-400">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
                <div class="flex items-center gap-3 w-full md:w-auto overflow-x-auto pb-2 md:pb-0">
                    <button onclick="fetchLatestNews()" class="whitespace-nowrap px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 transition-all text-xs font-bold flex items-center gap-2">
                        <i class="fas fa-sync"></i> Sync News
                    </button>
                    <a href="../" target="_blank" class="p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 transition-all">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </header>

            <!-- Mobile Admin Menu -->
            <div id="mobile-admin-menu" class="fixed inset-0 bg-slate-900/60 z-[200] hidden">
                <div class="bg-slate-900 w-64 h-full p-6 shadow-2xl animate-in slide-in-from-left duration-300 relative z-[210]">
                    <div class="flex justify-between items-center mb-10">
                        <h1 class="text-xl font-black text-white">Admin Menu</h1>
                        <button onclick="toggleAdminMenu()" class="text-slate-400"><i class="fas fa-times text-xl"></i></button>
                    </div>
                    <nav class="space-y-2">
                        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $current_page == 'dashboard' ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
                            <i class="fas fa-grid-2"></i>
                            <span class="font-bold">Dashboard</span>
                        </a>
                        <a href="history.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $current_page == 'history' ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
                            <i class="fas fa-history"></i>
                            <span class="font-bold">History</span>
                        </a>
                        <a href="users.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $current_page == 'users' ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
                            <i class="fas fa-users"></i>
                            <span class="font-bold">Users</span>
                        </a>
                        <a href="packages.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $current_page == 'packages' ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
                            <i class="fas fa-box-open"></i>
                            <span class="font-bold">Packages</span>
                        </a>
                        <a href="payments.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $current_page == 'payments' ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
                            <i class="fas fa-money-bill-wave"></i>
                            <span class="font-bold">Payments</span>
                        </a>
                        <a href="settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $current_page == 'settings' ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
                            <i class="fas fa-cog"></i>
                            <span class="font-bold">Settings</span>
                        </a>
                        <a href="ads.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $current_page == 'ads' ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
                            <i class="fas fa-ad"></i>
                            <span class="font-bold">Ads</span>
                        </a>
                        <div class="pt-4 mt-4 border-t border-slate-800">
                            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-500 hover:bg-red-500/10 transition-all">
                                <i class="fas fa-sign-out-alt"></i>
                                <span class="font-bold">Logout</span>
                            </a>
                        </div>
                    </nav>
                </div>
            </div>

            <script>
            function toggleAdminMenu() {
                const menu = document.getElementById('mobile-admin-menu');
                if (menu.classList.contains('hidden')) {
                    menu.classList.remove('hidden');
                    menu.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                } else {
                    menu.classList.add('hidden');
                    menu.classList.remove('flex');
                    document.body.style.overflow = '';
                }
            }

            function fetchLatestNews() {
                const btn = event.currentTarget;
                const original = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
                btn.disabled = true;

                fetch('../api/fetch_news')
                    .then(res => res.text())
                    .then(data => alert(data))
                    .finally(() => {
                        btn.innerHTML = original;
                        btn.disabled = false;
                    });
            }
            </script>
