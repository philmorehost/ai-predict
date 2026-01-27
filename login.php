<?php
require_once 'includes/header.php';
?>

<div class="max-w-md mx-auto px-4 py-20">
    <div class="text-center mb-10">
        <h1 class="text-3xl font-black text-slate-900 dark:text-white mb-2">Welcome Back</h1>
        <p class="text-slate-500">Access your premium predictions and account credits.</p>
    </div>

    <!-- Visitor ID Login Card -->
    <div id="auth-section" class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 p-8 rounded-[2.5rem] shadow-xl">
         <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 text-center">Premium Access (Visitor ID)</label>
         <div class="space-y-4">
            <div class="relative">
                <input type="text" id="visitor-id-input" placeholder="Enter SP-XXXXXXXX" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20 text-center font-mono text-lg">
            </div>
            <button onclick="loginWithID()" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-bold text-lg hover:bg-slate-800 transition-all shadow-lg">Identify & Login</button>
         </div>

         <div class="mt-8 pt-8 border-t border-slate-50 dark:border-slate-700/50 flex flex-col gap-4">
            <a href="index.php#pricing" class="w-full py-3 bg-emerald-600 text-white text-center rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 transition-all">Get Premium (Subscribe)</a>
            <button onclick="showResetID()" class="w-full text-[10px] font-bold text-slate-400 hover:text-emerald-600 uppercase tracking-widest transition-all italic underline decoration-slate-200">Forgot Visitor ID?</button>
         </div>
    </div>

    <div class="mt-8 text-center">
        <a href="index.php" class="text-sm font-bold text-slate-400 hover:text-slate-600 transition-all">
            <i class="fas fa-arrow-left mr-2"></i> Back to Homepage
        </a>
    </div>
</div>

<script>
    // Ensure that if user is already logged in, we redirect to home
    if (localStorage.getItem('visitor_id') && activeVisitor) {
        window.location.href = 'index.php';
    }
</script>

<?php require_once 'includes/footer.php'; ?>
