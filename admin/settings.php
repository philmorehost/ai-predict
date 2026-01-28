<?php
require_once 'auth.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Re-fetch current settings to ensure we have the latest paths for logo/icon
    $current_settings = getSettings($conn);

    $site_name = $_POST['site_name'];
    $site_description = $_POST['site_description'];
    $ai_provider = $_POST['ai_provider'] ?? 'gemini';
    $gemini_api_key = $_POST['gemini_api_key'];
    $gemini_model_prediction = $_POST['gemini_model_prediction'] ?? 'gemini-1.5-pro';
    $gemini_model_suggestion = $_POST['gemini_model_suggestion'] ?? 'gemini-1.5-flash';
    $deepseek_api_key = $_POST['deepseek_api_key'];
    $deepseek_base_url = $_POST['deepseek_base_url'] ?? 'https://api.deepseek.com/';
    $deepseek_model_prediction = $_POST['deepseek_model_prediction'] ?? 'deepseek-chat';
    $deepseek_model_suggestion = $_POST['deepseek_model_suggestion'] ?? 'deepseek-chat';
    $footer_text = $_POST['footer_text'];
    $contact_email = $_POST['contact_email'];
    $free_limit = $_POST['free_limit'] ?? 3;
    $prediction_charge = $_POST['prediction_charge'] ?? 0.03;
    $whatsapp_number = $_POST['whatsapp_number'] ?? '';
    $whatsapp_text = $_POST['whatsapp_text'] ?? '';
    $paystack_public_key = $_POST['paystack_public_key'] ?? '';
    $paystack_secret_key = $_POST['paystack_secret_key'] ?? '';
    $flutterwave_public_key = $_POST['flutterwave_public_key'] ?? '';
    $flutterwave_secret_key = $_POST['flutterwave_secret_key'] ?? '';
    $beewave_access_key = $_POST['beewave_access_key'] ?? '';
    $primary_currency = $_POST['primary_currency'] ?? 'USD';
    $conversion_rate_ngn = $_POST['conversion_rate_ngn'] ?? 1500;
    $conversion_rate_kes = $_POST['conversion_rate_kes'] ?? 130;
    $bank_details_ngn = $_POST['bank_details_ngn'] ?? '';
    $bank_details_kes = $_POST['bank_details_kes'] ?? '';
    $ad_expiry_date = $_POST['ad_expiry_date'] ?: null;
    $news_enabled = isset($_POST['news_enabled']) ? 1 : 0;
    $history_enabled = isset($_POST['history_enabled']) ? 1 : 0;

    $target_dir = "../assets/img/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0755, true);

    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    // Handle Logo Upload
    $site_logo = $current_settings['site_logo'];
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === 0) {
        $file_ext = strtolower(pathinfo($_FILES["site_logo"]["name"], PATHINFO_EXTENSION));
        if (in_array($file_ext, $allowed_exts)) {
            $file_name = "logo." . $file_ext;
            if (move_uploaded_file($_FILES["site_logo"]["tmp_name"], $target_dir . $file_name)) $site_logo = "assets/img/" . $file_name;
        }
    }

    // Handle Icon Upload
    $site_icon = $current_settings['site_icon'];
    if (isset($_FILES['site_icon']) && $_FILES['site_icon']['error'] === 0) {
        $file_ext = strtolower(pathinfo($_FILES["site_icon"]["name"], PATHINFO_EXTENSION));
        if (in_array($file_ext, $allowed_exts)) {
            $file_name = "icon." . $file_ext;
            if (move_uploaded_file($_FILES["site_icon"]["tmp_name"], $target_dir . $file_name)) {
                $site_icon = "assets/img/" . $file_name;
                if ($file_ext === 'png') {
                    @copy($target_dir . $file_name, $target_dir . "icon-192.png");
                    @copy($target_dir . $file_name, $target_dir . "icon-512.png");
                    @copy($target_dir . $file_name, $target_dir . "maskable-icon.png");
                }
            }
        }
    }

    $stmt = $conn->prepare("UPDATE settings SET site_name=?, site_description=?, gemini_api_key=?, footer_text=?, contact_email=?, site_logo=?, site_icon=?, gemini_model_prediction=?, gemini_model_suggestion=?, ai_provider=?, deepseek_api_key=?, deepseek_model_prediction=?, deepseek_model_suggestion=?, deepseek_base_url=?, free_limit=?, prediction_charge=?, whatsapp_number=?, whatsapp_text=?, paystack_public_key=?, paystack_secret_key=?, flutterwave_public_key=?, flutterwave_secret_key=?, primary_currency=?, conversion_rate_ngn=?, conversion_rate_kes=?, bank_details_ngn=?, bank_details_kes=?, ad_expiry_date=?, news_enabled=?, history_enabled=?, beewave_access_key=? WHERE id = 1");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("ssssssssssssssisssssssssssssiis", $site_name, $site_description, $gemini_api_key, $footer_text, $contact_email, $site_logo, $site_icon, $gemini_model_prediction, $gemini_model_suggestion, $ai_provider, $deepseek_api_key, $deepseek_model_prediction, $deepseek_model_suggestion, $deepseek_base_url, $free_limit, $prediction_charge, $whatsapp_number, $whatsapp_text, $paystack_public_key, $paystack_secret_key, $flutterwave_public_key, $flutterwave_secret_key, $primary_currency, $conversion_rate_ngn, $conversion_rate_kes, $bank_details_ngn, $bank_details_kes, $ad_expiry_date, $news_enabled, $history_enabled, $beewave_access_key);

    if ($stmt->execute()) $success = "Settings updated successfully!";
    else $error = "Error updating settings: " . $conn->error;
}

require_once 'header.php';
?>

<div class="max-w-5xl grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2">
        <?php if (isset($success)): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 p-4 rounded-xl text-sm mb-6 flex items-center gap-3"><i class="fas fa-check-circle"></i><p><?php echo $success; ?></p></div>
        <?php endif; ?>

        <div class="flex gap-4 mb-8 overflow-x-auto pb-2 border-b border-slate-100">
            <button onclick="showTab('general')" class="tab-btn px-6 py-2 rounded-xl font-bold text-sm bg-slate-900 text-white" id="tab-general">General</button>
            <button onclick="showTab('branding')" class="tab-btn px-6 py-2 rounded-xl font-bold text-sm text-slate-500" id="tab-branding">Branding & PWA</button>
            <button onclick="showTab('ai')" class="tab-btn px-6 py-2 rounded-xl font-bold text-sm text-slate-500" id="tab-ai">AI Provider</button>
            <button onclick="showTab('limits')" class="tab-btn px-6 py-2 rounded-xl font-bold text-sm text-slate-500" id="tab-limits">Limits</button>
            <button onclick="showTab('payments')" class="tab-btn px-6 py-2 rounded-xl font-bold text-sm text-slate-500" id="tab-payments">Payments</button>
            <button onclick="showTab('support')" class="tab-btn px-6 py-2 rounded-xl font-bold text-sm text-slate-500" id="tab-support">Ads & Help</button>
        </div>

        <form method="POST" enctype="multipart/form-data" class="bg-white rounded-3xl border border-slate-100 shadow-sm p-8 space-y-8">
            <div id="section-general" class="tab-section space-y-6">
                <div><label class="block text-xs font-black text-slate-400 uppercase mb-2">Site Name</label><input type="text" name="site_name" value="<?php echo $settings['site_name']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-4 px-6"></div>
                <div><label class="block text-xs font-black text-slate-400 uppercase mb-2">Site Description</label><textarea name="site_description" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-4 px-6"><?php echo $settings['site_description']; ?></textarea></div>
                <div><label class="block text-xs font-black text-slate-400 uppercase mb-2">Contact Email</label><input type="email" name="contact_email" value="<?php echo $settings['contact_email']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-4 px-6"></div>

                <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <input type="checkbox" name="news_enabled" id="news_enabled" value="1" <?php echo $settings['news_enabled'] ? 'checked' : ''; ?> class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="news_enabled" class="text-sm font-bold text-slate-700">Enable Sport News Section</label>
                </div>

                <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <input type="checkbox" name="history_enabled" id="history_enabled" value="1" <?php echo $settings['history_enabled'] ? 'checked' : ''; ?> class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="history_enabled" class="text-sm font-bold text-slate-700">Enable Last 10 Match Forecasts</label>
                </div>
            </div>

            <div id="section-branding" class="tab-section hidden space-y-8">
                <div class="bg-emerald-50/50 p-6 rounded-2xl border border-emerald-100">
                    <h4 class="text-sm font-black text-emerald-800 uppercase tracking-widest mb-4">Site Visuals</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <label class="block text-xs font-black text-slate-400 uppercase">Logo (Site Splash)</label>
                            <div class="p-4 bg-white border border-slate-200 rounded-2xl">
                                <?php if ($settings['site_logo']): ?>
                                    <div class="mb-4 p-2 bg-slate-50 rounded-lg border border-slate-100 inline-block">
                                        <img src="../<?php echo $settings['site_logo']; ?>" class="h-12 object-contain">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="site_logo" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                                <p class="text-[10px] text-slate-400 mt-2 italic">Recommended: Horizontal PNG with transparency</p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <label class="block text-xs font-black text-slate-400 uppercase">App Icon (PWA & Favicon)</label>
                            <div class="p-4 bg-white border border-slate-200 rounded-2xl">
                                <?php if ($settings['site_icon']): ?>
                                    <div class="mb-4 p-2 bg-slate-50 rounded-lg border border-slate-100 inline-block">
                                        <img src="../<?php echo $settings['site_icon']; ?>" class="h-12 w-12 object-contain">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="site_icon" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                                <p class="text-[10px] text-slate-400 mt-2 italic">Recommended: Square 512x512 PNG</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-amber-50/50 p-6 rounded-2xl border border-amber-100">
                    <h4 class="text-sm font-black text-amber-800 uppercase tracking-widest mb-2"><i class="fas fa-mobile-screen mr-2"></i>PWA Synchronization</h4>
                    <p class="text-[11px] text-amber-700 leading-relaxed mb-0">Uploading an icon automatically updates <b>manifest.json</b> and generates standard Android/iOS icon sizes (192px and 512px) for your progressive web app.</p>
                </div>
            </div>

            <div id="section-ai" class="tab-section hidden space-y-6">
                <label class="block text-xs font-black text-slate-400 uppercase mb-2">AI Provider</label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="p-4 border-2 rounded-2xl cursor-pointer transition-all <?php echo $settings['ai_provider'] == 'gemini' ? 'bg-emerald-50 border-emerald-500' : ''; ?>" onclick="switchAIProvider('gemini')"><input type="radio" name="ai_provider" value="gemini" class="hidden" <?php echo $settings['ai_provider'] == 'gemini' ? 'checked' : ''; ?>><b>Gemini</b></label>
                    <label class="p-4 border-2 rounded-2xl cursor-pointer transition-all <?php echo $settings['ai_provider'] == 'deepseek' ? 'bg-emerald-50 border-emerald-500' : ''; ?>" onclick="switchAIProvider('deepseek')"><input type="radio" name="ai_provider" value="deepseek" class="hidden" <?php echo $settings['ai_provider'] == 'deepseek' ? 'checked' : ''; ?>><b>DeepSeek</b></label>
                </div>

                <div id="gemini-settings" class="<?php echo $settings['ai_provider'] == 'gemini' ? '' : 'hidden'; ?> space-y-4">
                    <input type="password" name="gemini_api_key" value="<?php echo $settings['gemini_api_key']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-4 px-6" placeholder="Gemini API Key">
                    <div class="grid grid-cols-2 gap-4">
                        <select id="gemini_model_prediction" name="gemini_model_prediction" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4">
                            <option value="gemini-1.5-pro" <?php echo $settings['gemini_model_prediction'] == 'gemini-1.5-pro' ? 'selected' : ''; ?>>Gemini 1.5 Pro</option>
                            <option value="gemini-1.5-flash" <?php echo $settings['gemini_model_prediction'] == 'gemini-1.5-flash' ? 'selected' : ''; ?>>Gemini 1.5 Flash</option>
                        </select>
                        <select name="gemini_model_suggestion" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4">
                            <option value="gemini-1.5-flash" <?php echo $settings['gemini_model_suggestion'] == 'gemini-1.5-flash' ? 'selected' : ''; ?>>Gemini 1.5 Flash</option>
                        </select>
                    </div>
                    <button type="button" onclick="testAIConnection('gemini')" class="text-xs font-bold text-emerald-600 underline">Test Gemini</button>
                </div>

                <div id="deepseek-settings" class="<?php echo $settings['ai_provider'] == 'deepseek' ? '' : 'hidden'; ?> space-y-4">
                    <input type="password" name="deepseek_api_key" value="<?php echo $settings['deepseek_api_key']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-4 px-6" placeholder="DeepSeek API Key">
                    <input type="text" name="deepseek_base_url" value="<?php echo $settings['deepseek_base_url']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-4 px-6" placeholder="Base URL">
                    <div class="grid grid-cols-2 gap-4">
                        <select id="deepseek_model_prediction" name="deepseek_model_prediction" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4">
                            <option value="deepseek-chat" <?php echo $settings['deepseek_model_prediction'] == 'deepseek-chat' ? 'selected' : ''; ?>>DeepSeek Chat</option>
                            <option value="deepseek-reasoner" <?php echo $settings['deepseek_model_prediction'] == 'deepseek-reasoner' ? 'selected' : ''; ?>>DeepSeek Reasoner</option>
                        </select>
                        <select name="deepseek_model_suggestion" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4">
                            <option value="deepseek-chat" <?php echo $settings['deepseek_model_suggestion'] == 'deepseek-chat' ? 'selected' : ''; ?>>DeepSeek Chat</option>
                        </select>
                    </div>
                    <button type="button" onclick="testAIConnection('deepseek')" class="text-xs font-bold text-emerald-600 underline">Test DeepSeek</button>
                </div>
            </div>

            <div id="section-limits" class="tab-section hidden space-y-6">
                <div class="grid grid-cols-2 gap-6">
                    <div><label class="block text-xs font-black text-slate-400 uppercase mb-2">Free Limit/Day</label><input type="number" name="free_limit" value="<?php echo $settings['free_limit']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-4 px-6"></div>
                    <div><label class="block text-xs font-black text-slate-400 uppercase mb-2">Credit/Predict</label><input type="number" step="0.01" name="prediction_charge" value="<?php echo $settings['prediction_charge']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-4 px-6"></div>
                </div>
            </div>

            <div id="section-payments" class="tab-section hidden space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" name="paystack_public_key" value="<?php echo $settings['paystack_public_key']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4" placeholder="Paystack Public">
                    <input type="password" name="paystack_secret_key" value="<?php echo $settings['paystack_secret_key']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4" placeholder="Paystack Secret">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" name="flutterwave_public_key" value="<?php echo $settings['flutterwave_public_key']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4" placeholder="Flutterwave Public">
                    <input type="password" name="flutterwave_secret_key" value="<?php echo $settings['flutterwave_secret_key']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4" placeholder="Flutterwave Secret">
                </div>
                <div class="grid grid-cols-1 gap-4">
                    <input type="text" name="beewave_access_key" value="<?php echo $settings['beewave_access_key']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4" placeholder="BeeWave Access Key">
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <select name="primary_currency" class="bg-slate-50 border border-slate-200 rounded-xl py-3 px-4"><option value="USD" <?php echo $settings['primary_currency'] == 'USD' ? 'selected' : ''; ?>>USD</option><option value="NGN" <?php echo $settings['primary_currency'] == 'NGN' ? 'selected' : ''; ?>>NGN</option></select>
                    <input type="number" step="0.01" name="conversion_rate_ngn" value="<?php echo $settings['conversion_rate_ngn']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4" placeholder="USD to NGN">
                    <input type="number" step="0.01" name="conversion_rate_kes" value="<?php echo $settings['conversion_rate_kes']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4" placeholder="USD to KES">
                </div>
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Bank Details (Nigeria)</label>
                        <textarea name="bank_details_ngn" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all"><?php echo $settings['bank_details_ngn']; ?></textarea>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Bank Details (Kenya)</label>
                        <textarea name="bank_details_kes" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all"><?php echo $settings['bank_details_kes']; ?></textarea>
                    </div>
                </div>
            </div>

            <div id="section-support" class="tab-section hidden space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" name="whatsapp_number" value="<?php echo $settings['whatsapp_number']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4" placeholder="WhatsApp Number">
                    <input type="date" name="ad_expiry_date" value="<?php echo $settings['ad_expiry_date']; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4">
                </div>
                <textarea name="footer_text" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4"><?php echo $settings['footer_text']; ?></textarea>
            </div>

            <button type="submit" class="w-full py-4 bg-slate-900 text-white font-bold rounded-xl shadow-lg">Save Settings</button>
        </form>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-3xl border border-slate-100 p-8 shadow-sm">
            <h3 class="font-black mb-6">Setup Guide</h3>
            <div id="gemini-guide" class="<?php echo $settings['ai_provider'] == 'gemini' ? '' : 'hidden'; ?> space-y-4 text-xs text-slate-500">
                <p>1. Go to <b>aistudio.google.com</b></p>
                <p>2. Create API key in new project.</p>
                <p>3. Use <b>Gemini 1.5 Flash</b> for better availability.</p>
            </div>
            <div id="deepseek-guide" class="<?php echo $settings['ai_provider'] == 'deepseek' ? '' : 'hidden'; ?> space-y-4 text-xs text-slate-500">
                <p>1. Go to <b>platform.deepseek.com</b></p>
                <p>2. Create key and <b>add balance</b> (min $5).</p>
                <p>3. DeepSeek is a <b>paid</b> service.</p>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(id) {
    // Hide all sections
    document.querySelectorAll('.tab-section').forEach(s => s.classList.add('hidden'));
    document.getElementById('section-' + id).classList.remove('hidden');

    // Update button styles
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('bg-slate-900', 'text-white');
        b.classList.add('text-slate-900', 'text-slate-500', 'hover:bg-slate-100'); // Ensure base text color is black-ish (slate-900)
    });

    const activeBtn = document.getElementById('tab-' + id);
    activeBtn.classList.remove('text-slate-500', 'hover:bg-slate-100');
    activeBtn.classList.add('bg-slate-900', 'text-white');
}
function switchAIProvider(p) {
    document.getElementById('gemini-settings').classList.toggle('hidden', p !== 'gemini');
    document.getElementById('deepseek-settings').classList.toggle('hidden', p !== 'deepseek');
    document.getElementById('gemini-guide').classList.toggle('hidden', p !== 'gemini');
    document.getElementById('deepseek-guide').classList.toggle('hidden', p !== 'deepseek');
}
function testAIConnection(p) {
    const key = document.querySelector(`input[name="${p}_api_key"]`).value;
    const model = document.getElementById(`${p}_model_prediction`).value;
    const url = document.querySelector(`input[name="${p}_base_url"]`)?.value || '';
    const btn = event.target;
    btn.innerText = 'Testing...';
    const fd = new FormData();
    fd.append('key', key);
    fd.append('model', model);
    fd.append('provider', p);
    fd.append('base_url', url);

    fetch(`../api/test_ai`, { method: 'POST', body: fd })
        .then(r => r.json()).then(d => { alert(`[${p.toUpperCase()}] ${d.message}`); btn.innerText = 'Test ' + p; });
}
</script>
<?php require_once 'footer.php'; ?>
