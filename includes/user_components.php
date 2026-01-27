<!-- User Profile Bar (Sticky Top) -->
<div id="user-bar" class="hidden fixed top-0 left-0 w-full bg-white/80 dark:bg-slate-900/80 backdrop-blur-md z-[100] border-b border-slate-100 dark:border-slate-800 shadow-sm animate-in slide-in-from-top duration-500">
    <div class="max-w-5xl mx-auto px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <div class="h-10 w-10 bg-emerald-600 rounded-full flex items-center justify-center text-white font-black text-sm" id="user-initials">SP</div>
            <div class="flex gap-6">
                <div>
                    <div class="text-xs font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Balance</div>
                    <div class="text-lg font-black text-slate-900 dark:text-white leading-none"><span id="user-credits">0.00</span> Credits</div>
                </div>
                <div class="hidden sm:block">
                    <div class="text-xs font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Predictions</div>
                    <div class="text-lg font-black text-slate-900 dark:text-white leading-none"><span id="user-stats-preds">0</span> Total</div>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="showProfile()" class="p-2.5 bg-slate-50 dark:bg-slate-800 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-all">
                <i class="fas fa-user-gear text-slate-600 dark:text-slate-400"></i>
            </button>
            <button onclick="showSubscribeModal()" class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 transition-all">
                Top Up
            </button>
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div id="profile-modal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-md z-[200] flex items-center justify-center hidden p-4">
    <div class="bg-white dark:bg-slate-800 rounded-[3rem] max-w-md w-full shadow-2xl animate-in zoom-in duration-300">
        <div class="p-8 md:p-10">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase italic">My Profile</h2>
                <button onclick="hideProfileModal()" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
            </div>

            <div id="profile-notification" class="hidden mb-6 p-4 rounded-2xl text-sm font-bold flex items-center gap-3"></div>

            <div class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 ml-4">Visitor ID</label>
                    <div class="bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 font-mono text-sm text-slate-500" id="profile-vid">SP-XXXXXXXX</div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 ml-4">Full Name</label>
                    <input type="text" id="profile-name" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20 text-slate-900 dark:text-white font-medium">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 ml-4">Email Address</label>
                    <input type="email" id="profile-email" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20 text-slate-900 dark:text-white font-medium">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 ml-4">Phone Number</label>
                    <input type="tel" id="profile-phone" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20 text-slate-900 dark:text-white font-medium">
                </div>

                <button onclick="saveProfile()" id="save-profile-btn" class="w-full py-4 bg-emerald-600 text-white font-black rounded-2xl shadow-lg shadow-emerald-600/20 transition-all hover:bg-emerald-500 active:scale-95 flex items-center justify-center gap-3">
                    <i class="fas fa-save"></i>
                    <span>Update Info</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Subscription Modal -->
<div id="subscribe-modal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-md z-[200] flex items-center justify-center hidden p-4">
    <div class="bg-white dark:bg-slate-800 rounded-[3rem] max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl animate-in zoom-in duration-300">
        <div class="p-8 md:p-12">
            <div class="flex justify-between items-start mb-8">
                <div id="modal-header-text">
                    <h2 class="text-3xl md:text-5xl font-black text-slate-900 dark:text-white tracking-tighter italic uppercase leading-none mb-4">Elevate Your Strategy</h2>
                    <p class="text-slate-500 dark:text-slate-400">Unlock unlimited AI tactical deep-dives and precise score forecasts.</p>
                </div>
                <button onclick="hideSubscribeModal()" class="text-slate-400 hover:text-slate-600 p-2"><i class="fas fa-times text-2xl"></i></button>
            </div>

            <!-- Step 1: Pricing Grid -->
            <div id="pricing-step" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                    <?php
                    $pkgs = $conn->query("SELECT * FROM credit_packages WHERE status = 1 ORDER BY price_usd ASC");
                    while($pkg = $pkgs->fetch_assoc()):
                    ?>
                    <div class="border-2 border-slate-100 dark:border-slate-700 rounded-[2.5rem] p-8 flex flex-col items-center text-center group hover:border-emerald-500 transition-all">
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4"><?php echo $pkg['name']; ?></div>
                        <div class="text-4xl font-black text-slate-900 dark:text-white mb-2">$<?php echo number_format($pkg['price_usd'], 2); ?></div>
                        <div class="text-xs font-bold text-emerald-600 mb-8 italic"><?php echo $pkg['credits']; ?> Credits</div>
                        <button onclick="initiatePayment(<?php echo htmlspecialchars(json_encode($pkg)); ?>)" class="w-full py-4 bg-slate-900 dark:bg-slate-700 text-white font-black rounded-2xl group-hover:bg-emerald-600 transition-all shadow-lg">Purchase</button>
                    </div>
                    <?php endwhile; ?>
                </div>

                <div class="text-center">
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-4">Accepted Payment Gateways</p>
                    <div class="flex justify-center gap-6 grayscale opacity-40">
                        <i class="fab fa-cc-visa text-3xl"></i>
                        <i class="fab fa-cc-mastercard text-3xl"></i>
                        <i class="fab fa-cc-apple-pay text-3xl"></i>
                        <i class="fas fa-money-bill-transfer text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Step 2: Contact Info Form (If not logged in) -->
            <div id="contact-step" class="hidden max-w-md mx-auto space-y-8 py-10">
                <div class="text-center">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2">Identify Yourself</h3>
                    <p class="text-slate-500 text-sm">Where should we send your credits and Visitor ID?</p>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-4">Email Address</label>
                        <input type="email" id="purchase-email" placeholder="name@example.com" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-4">Phone Number</label>
                        <input type="tel" id="purchase-phone" placeholder="+123..." class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20">
                    </div>
                    <button onclick="submitContactInfo()" class="w-full py-4 bg-emerald-600 text-white font-black rounded-2xl shadow-lg shadow-emerald-600/20">Continue to Payment</button>
                    <button onclick="showPricing()" class="w-full text-slate-400 font-bold text-xs">Back to Packages</button>
                </div>
            </div>

            <!-- Step 3: Payment Method Selection -->
            <div id="payment-method-step" class="hidden max-w-md mx-auto space-y-8 py-10">
                <div class="text-center">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2">Select Method</h3>
                    <p class="text-slate-500 text-sm">Choose how you want to pay for <span id="selected-pkg-name" class="font-bold text-emerald-600"></span></p>
                </div>
                <div class="grid grid-cols-1 gap-4">
                    <button onclick="processOnlinePayment()" class="p-6 bg-slate-900 text-white rounded-[2rem] flex items-center justify-between group hover:bg-emerald-600 transition-all">
                        <div class="text-left">
                            <div class="font-black italic uppercase tracking-tight">Online Payment</div>
                            <div class="text-[10px] opacity-60">Visa, Mastercard, Mobile Money</div>
                        </div>
                        <i class="fas fa-credit-card text-2xl"></i>
                    </button>
                    <button onclick="showBankTransferUI()" class="p-6 bg-white dark:bg-slate-700 border-2 border-slate-100 dark:border-slate-600 rounded-[2rem] flex items-center justify-between group hover:border-emerald-500 transition-all">
                        <div class="text-left">
                            <div class="font-black italic uppercase tracking-tight text-slate-900 dark:text-white">Bank Transfer</div>
                            <div class="text-[10px] text-slate-400">Manual approval within 24h</div>
                        </div>
                        <i class="fas fa-university text-2xl text-slate-300"></i>
                    </button>
                </div>
                <button onclick="showPricing()" class="w-full text-slate-400 font-bold text-xs mt-4 underline">Cancel</button>
            </div>

            <!-- Step 4: Bank Transfer Details -->
            <div id="bank-transfer-step" class="hidden max-w-xl mx-auto space-y-8 py-6">
                <div class="bg-emerald-50 dark:bg-emerald-900/20 p-8 rounded-[2.5rem] border border-emerald-100 dark:border-emerald-800">
                    <h3 class="text-xl font-black text-emerald-800 dark:text-emerald-400 mb-6 uppercase tracking-tighter">Bank Instructions</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <div class="space-y-2">
                            <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Nigeria (NGN)</p>
                            <div class="text-sm font-bold text-slate-700 dark:text-slate-300 whitespace-pre-line"><?php echo $settings['bank_details_ngn']; ?></div>
                            <p class="text-xs font-black mt-2">Amount: <span class="text-lg" id="amt-ngn"></span></p>
                        </div>
                        <div class="space-y-2">
                            <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Kenya (KES)</p>
                            <div class="text-sm font-bold text-slate-700 dark:text-slate-300 whitespace-pre-line"><?php echo $settings['bank_details_kes']; ?></div>
                            <p class="text-xs font-black mt-2">Amount: <span class="text-lg" id="amt-kes"></span></p>
                        </div>
                    </div>

                    <div class="bg-white/50 dark:bg-slate-800/50 p-4 rounded-2xl border border-white/80 dark:border-slate-700/80 mb-6">
                        <p class="text-xs font-bold text-slate-500 mb-1 uppercase tracking-widest">Reference ID (IMPORTANT)</p>
                        <p class="font-mono text-lg font-black text-emerald-600" id="purchase-ref-id"></p>
                    </div>

                    <div class="space-y-4">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest text-center">Upload Proof of Payment</label>
                        <input type="file" id="proof-upload" class="hidden" onchange="handleProofUpload(event)">
                        <button onclick="document.getElementById('proof-upload').click()" id="upload-btn" class="w-full py-4 bg-emerald-600 text-white font-black rounded-2xl shadow-lg flex items-center justify-center gap-3">
                            <i class="fas fa-upload"></i>
                            <span>Select Proof Image/PDF</span>
                        </button>
                        <div id="upload-status" class="text-center text-xs font-bold text-slate-400 animate-pulse hidden">Uploading...</div>
                    </div>
                </div>
                <button onclick="showPaymentMethods()" class="w-full text-slate-400 font-bold text-xs underline">Back to Payment Methods</button>
            </div>
        </div>
    </div>
</div>

<script>
    let activeVisitor = <?php
        if (isset($_SESSION['user_id'])) {
            $s_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT * FROM visitors WHERE user_id = ?");
            $stmt->bind_param("s", $s_id);
            $stmt->execute();
            echo json_encode($stmt->get_result()->fetch_assoc());
        } else {
            echo 'null';
        }
    ?>;

    if (activeVisitor) {
        window.addEventListener('load', () => {
            updateUIForVisitor();
        });
    }

    // Visitor Logic
    function loginWithID() {
        const idInput = document.getElementById('visitor-id-input');
        const id = idInput ? idInput.value : null;
        if (!id) return;

        fetch(`api/auth?action=login&user_id=${encodeURIComponent(id)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    activeVisitor = data.visitor;
                    localStorage.setItem('visitor_id', id);
                    updateUIForVisitor();

                    // Redirect if on login page
                    if (window.location.pathname.includes('login.php')) {
                        window.location.href = 'dashboard.php';
                    }
                } else {
                    alert(data.message);
                }
            });
    }

    function updateUIForVisitor() {
        if (!activeVisitor) return;
        if (document.getElementById('auth-section')) document.getElementById('auth-section').classList.add('hidden');
        if (document.getElementById('user-bar')) document.getElementById('user-bar').classList.remove('hidden');
        if (document.getElementById('nav-login-btn')) document.getElementById('nav-login-btn').classList.add('hidden');
        if (document.getElementById('user-credits')) document.getElementById('user-credits').innerText = parseFloat(activeVisitor.credits).toFixed(2);
        if (document.getElementById('user-stats-preds')) document.getElementById('user-stats-preds').innerText = activeVisitor.total_predictions || 0;
        if (document.getElementById('user-initials')) {
            const initials = activeVisitor.full_name ? activeVisitor.full_name.split(' ').map(n => n[0]).join('').substring(0, 2) : activeVisitor.user_id.substring(3, 5);
            document.getElementById('user-initials').innerText = initials.toUpperCase();
        }

        // Show a stats card if they're on the homepage
        if (window.location.pathname.includes('index.php') || window.location.pathname.endsWith('/') || window.location.pathname === '' || window.location.pathname.includes('dashboard.php')) {
            if (!document.getElementById('premium-stats-card')) {
                const statsCard = document.createElement('div');
                statsCard.id = 'premium-stats-card';
                statsCard.className = 'mb-12 grid grid-cols-2 md:grid-cols-3 gap-4 animate-in fade-in slide-in-from-bottom-4 duration-700';
                statsCard.innerHTML = `
                    <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 p-6 rounded-3xl shadow-sm">
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Current Credits</div>
                        <div class="text-2xl font-black text-emerald-600">${parseFloat(activeVisitor.credits).toFixed(2)}</div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 p-6 rounded-3xl shadow-sm">
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Predictions</div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white">${activeVisitor.total_predictions || 0}</div>
                    </div>
                    <div class="col-span-2 md:col-span-1 bg-emerald-600 p-6 rounded-3xl shadow-lg shadow-emerald-600/20 flex flex-col justify-center">
                        <div class="text-[10px] font-black text-white/60 uppercase tracking-widest mb-1">Account Status</div>
                        <div class="text-lg font-black text-white">Premium Explorer</div>
                    </div>
                `;
                const header = document.querySelector('header');
                if (header) header.parentNode.insertBefore(statsCard, header.nextSibling);
            }
        }
    }

    function showResetID() {
        const email = prompt("Enter your registered email to recover your Visitor ID:");
        if (email) {
            fetch(`api/auth?action=reset&email=${encodeURIComponent(email)}`)
                .then(res => res.json())
                .then(data => alert(data.message));
        }
    }

    function showProfile() {
        if (!activeVisitor) return;
        document.getElementById('profile-vid').innerText = activeVisitor.user_id;
        document.getElementById('profile-name').value = activeVisitor.full_name || '';
        document.getElementById('profile-email').value = activeVisitor.email || '';
        document.getElementById('profile-phone').value = activeVisitor.phone || '';
        document.getElementById('profile-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function hideProfileModal() {
        document.getElementById('profile-modal').classList.add('hidden');
        document.body.style.overflow = '';
        document.getElementById('profile-notification').classList.add('hidden');
    }

    function saveProfile() {
        const full_name = document.getElementById('profile-name').value;
        const email = document.getElementById('profile-email').value;
        const phone = document.getElementById('profile-phone').value;
        const btn = document.getElementById('save-profile-btn');
        const notif = document.getElementById('profile-notification');

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        const formData = new FormData();
        formData.append('user_id', activeVisitor.user_id);
        formData.append('full_name', full_name);
        formData.append('email', email);
        formData.append('phone', phone);

        fetch('api/auth?action=update_profile', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                notif.classList.remove('hidden', 'bg-red-50', 'text-red-600', 'bg-emerald-50', 'text-emerald-600');
                if (data.success) {
                    notif.classList.add('bg-emerald-50', 'text-emerald-600');
                    notif.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                    activeVisitor.full_name = full_name;
                    activeVisitor.email = email;
                    activeVisitor.phone = phone;
                    setTimeout(() => location.reload(), 1500);
                } else {
                    notif.classList.add('bg-red-50', 'text-red-600');
                    notif.innerHTML = '<i class="fas fa-circle-exclamation"></i> ' + data.message;
                }
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Update Info';
            });
    }

    function showSubscribeModal() {
        document.getElementById('subscribe-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function hideSubscribeModal() {
        document.getElementById('subscribe-modal').classList.add('hidden');
        document.body.style.overflow = '';
        if (window.location.hash === '#pricing') {
            history.replaceState(null, null, ' ');
        }
    }

    let currentSelectedPkg = null;
    let currentOrder = null;

    function hideAllSteps() {
        document.getElementById('pricing-step').classList.add('hidden');
        document.getElementById('contact-step').classList.add('hidden');
        document.getElementById('payment-method-step').classList.add('hidden');
        document.getElementById('bank-transfer-step').classList.add('hidden');
    }

    function showPricing() {
        hideAllSteps();
        document.getElementById('pricing-step').classList.remove('hidden');
    }

    function initiatePayment(pkg) {
        currentSelectedPkg = pkg;
        if (activeVisitor) {
            submitContactInfo(activeVisitor.email, activeVisitor.phone);
        } else {
            hideAllSteps();
            document.getElementById('contact-step').classList.remove('hidden');
        }
    }

    function submitContactInfo(email = null, phone = null) {
        const e = email || document.getElementById('purchase-email').value;
        const p = phone || document.getElementById('purchase-phone').value;

        if (!e || !p) {
            alert("Please provide both email and phone number.");
            return;
        }

        const formData = new FormData();
        formData.append('email', e);
        formData.append('phone', p);
        formData.append('package_id', currentSelectedPkg.id);

        fetch('api/payment?action=create_order', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    currentOrder = data;
                    showPaymentMethods();
                } else {
                    alert(data.message);
                }
            });
    }

    function showPaymentMethods() {
        hideAllSteps();
        document.getElementById('selected-pkg-name').innerText = currentSelectedPkg.name + ' ($' + currentSelectedPkg.price_usd + ')';
        document.getElementById('payment-method-step').classList.remove('hidden');
    }

    function processOnlinePayment() {
        const provider = "<?php echo ($settings['primary_currency'] == 'NGN') ? 'paystack' : 'flutterwave'; ?>";
        if (provider === 'paystack') {
            payWithPaystack();
        } else {
            payWithFlutterwave();
        }
    }

    function payWithPaystack() {
        const handler = PaystackPop.setup({
            key: '<?php echo $settings['paystack_public_key']; ?>',
            email: currentOrder.email || (activeVisitor ? activeVisitor.email : document.getElementById('purchase-email').value),
            amount: currentSelectedPkg.price_usd * <?php echo $settings['conversion_rate_ngn']; ?> * 100,
            currency: 'NGN',
            ref: 'SP_' + Math.floor((Math.random() * 1000000000) + 1),
            callback: function(response){
                verifyPayment(response.reference, 'paystack');
            }
        });
        handler.openIframe();
    }

    function payWithFlutterwave() {
        FlutterwaveCheckout({
            public_key: '<?php echo $settings['flutterwave_public_key']; ?>',
            tx_ref: 'SP_' + Math.floor((Math.random() * 1000000000) + 1),
            amount: currentSelectedPkg.price_usd,
            currency: 'USD',
            payment_options: 'card,mobilemoney,ussd',
            customer: {
                email: currentOrder.email || (activeVisitor ? activeVisitor.email : document.getElementById('purchase-email').value),
                phone_number: currentOrder.phone || (activeVisitor ? activeVisitor.phone : document.getElementById('purchase-phone').value),
                name: 'Visitor ' + currentOrder.user_id,
            },
            callback: function (data) {
                verifyPayment(data.transaction_id, 'flutterwave');
            },
            customizations: {
                title: 'SurePredictor Credits',
                description: 'Payment for ' + currentSelectedPkg.name,
                logo: '<?php echo $settings['site_logo']; ?>',
            },
        });
    }

    function verifyPayment(ref, provider) {
        fetch(`api/payment?action=verify_payment&ref=${ref}&v_id=${currentOrder.visitor_id}&pkg_id=${currentSelectedPkg.id}&provider=${provider}`)
            .then(res => res.json()).then(d => {
                alert(d.message);
                if (d.success) {
                    localStorage.setItem('visitor_id', currentOrder.user_id);
                    location.reload();
                }
            });
    }

    function showBankTransferUI() {
        hideAllSteps();
        document.getElementById('amt-ngn').innerText = '₦' + (currentSelectedPkg.price_usd * <?php echo $settings['conversion_rate_ngn']; ?>).toLocaleString();
        document.getElementById('amt-kes').innerText = 'KSh ' + (currentSelectedPkg.price_usd * <?php echo $settings['conversion_rate_kes']; ?>).toLocaleString();
        document.getElementById('purchase-ref-id').innerText = currentOrder.user_id;
        document.getElementById('bank-transfer-step').classList.remove('hidden');
    }

    function handleProofUpload(e) {
        const file = e.target.files[0];
        if (!file) return;

        document.getElementById('upload-btn').classList.add('opacity-50', 'pointer-events-none');
        document.getElementById('upload-status').classList.remove('hidden');

        const fd = new FormData();
        fd.append('v_id', currentOrder.visitor_id);
        fd.append('package_id', currentSelectedPkg.id);
        fd.append('amount', currentSelectedPkg.price_usd);
        fd.append('currency', 'USD');
        fd.append('proof', file);

        fetch('api/payment?action=bank_transfer', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    localStorage.setItem('visitor_id', currentOrder.user_id);
                    location.reload();
                }
            })
            .finally(() => {
                document.getElementById('upload-btn').classList.remove('opacity-50', 'pointer-events-none');
                document.getElementById('upload-status').classList.add('hidden');
            });
    }

    // Auto-login if ID in storage
    const storedID = localStorage.getItem('visitor_id');
    if (storedID && !activeVisitor) {
        const idInput = document.getElementById('visitor-id-input');
        if (idInput) idInput.value = storedID;

        fetch(`api/auth?action=login&user_id=${encodeURIComponent(storedID)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    activeVisitor = data.visitor;
                    updateUIForVisitor();
                }
            });
    }

    // Auto-open pricing modal if hash is present
    if (window.location.hash === '#pricing') {
        setTimeout(() => showSubscribeModal(), 500);
    }
</script>
