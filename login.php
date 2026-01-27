<?php
require_once 'includes/header.php';
?>

<div class="max-w-md mx-auto px-4 py-20">
    <div class="text-center mb-10">
        <h1 class="text-3xl font-black text-slate-900 dark:text-white mb-2">Welcome Back</h1>
        <p class="text-slate-500">Access your premium predictions and account credits.</p>
    </div>

    <?php if (isset($_GET['err'])): ?>
        <div class="bg-red-500/10 border border-red-500/20 text-red-600 p-4 rounded-xl text-sm mb-6 text-center font-bold">
            <?php
                if($_GET['err'] == 'account_suspended') echo "Your account has been suspended. Please contact support.";
                else echo "Session lost. Please login again.";
            ?>
        </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 p-8 rounded-[2.5rem] shadow-xl">
         <form id="login-form" class="space-y-4">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-4">Email, Username or Visitor ID</label>
                <input type="text" name="identifier" required placeholder="name@example.com or username" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-4">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20">
            </div>

            <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-bold text-lg hover:bg-slate-800 transition-all shadow-lg">Login to Account</button>
         </form>

         <div class="mt-8 pt-8 border-t border-slate-50 dark:border-slate-700/50 flex flex-col gap-4 text-center">
            <p class="text-sm text-slate-500">New to SurePredictor? <a href="register.php" class="text-emerald-600 font-bold hover:underline">Register here</a></p>
            <button onclick="showResetID()" class="text-[10px] font-bold text-slate-400 hover:text-emerald-600 uppercase tracking-widest transition-all italic underline decoration-slate-200">Forgot Password or ID?</button>
         </div>
    </div>
</div>

<script>
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    if (!data.identifier || !data.password) {
        alert("Please enter both identifier and password.");
        return;
    }

    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Logging in...';

    fetch('api/user_auth.php?action=login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            localStorage.setItem('visitor_id', data.user['user_id']);
            window.location.href = 'dashboard.php';
        } else {
            alert(data.message);
            btn.disabled = false;
            btn.innerText = originalText;
        }
    })
    .catch(err => {
        console.error(err);
        alert("A connection error occurred. Please try again.");
        btn.disabled = false;
        btn.innerText = originalText;
    });
});

</script>

<?php require_once 'includes/footer.php'; ?>
