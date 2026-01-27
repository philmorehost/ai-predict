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
if (localStorage.getItem('visitor_id')) {
    window.location.href = 'dashboard.php';
}
document.getElementById('register-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('api/user_auth.php?action=register', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            localStorage.setItem('visitor_id', data.user_id);
            window.location.href = 'dashboard.php';
        } else {
            alert(data.message);
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
