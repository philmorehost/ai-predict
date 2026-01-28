<?php
require_once 'includes/header.php';
?>

<div class="max-w-md mx-auto px-4 py-20">
    <div class="text-center mb-10">
        <h1 class="text-3xl font-black text-slate-900 dark:text-white mb-2">Create Account</h1>
        <p class="text-slate-500">Join SurePredictor for premium AI insights.</p>
    </div>

    <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 p-8 rounded-[2.5rem] shadow-xl">
         <form id="register-form" class="space-y-4">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-4">Full Name</label>
                <input type="text" name="full_name" required placeholder="John Doe" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-4">Username</label>
                <input type="text" name="username" required placeholder="johndoe123" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-4">Email Address</label>
                <input type="email" name="email" required placeholder="name@example.com" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-4">Phone Number</label>
                <input type="tel" name="phone" placeholder="+123..." class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-4">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20">
            </div>

            <button type="submit" class="w-full bg-emerald-600 text-white py-4 rounded-2xl font-bold text-lg hover:bg-emerald-500 transition-all shadow-lg shadow-emerald-600/20">Sign Up Now</button>
         </form>

         <div class="mt-8 pt-8 border-t border-slate-50 dark:border-slate-700/50 text-center">
            <p class="text-sm text-slate-500">Already have an account? <a href="login.php" class="text-emerald-600 font-bold hover:underline">Login here</a></p>
         </div>
    </div>
</div>

<script>
document.getElementById('register-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    // Simple validation
    if (!data.full_name || !data.username || !data.email || !data.password) {
        alert("Please fill all required fields.");
        return;
    }

    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Creating account...';

    fetch('api/user_auth?action=register', {
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
