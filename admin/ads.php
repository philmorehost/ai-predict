<?php
require_once 'auth.php';

// Handle Delete (Changed to POST for CSRF protection)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ad'])) {
    $id = (int)$_POST['id'];

    // Cleanup image file
    $res = $conn->query("SELECT image_url FROM ads WHERE id = $id");
    if ($row = $res->fetch_assoc()) {
        if ($row['image_url'] && file_exists('../' . $row['image_url'])) {
            unlink('../' . $row['image_url']);
        }
    }

    $conn->query("DELETE FROM ads WHERE id = $id");
    header("Location: ads.php?success=Ad deleted successfully");
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ad'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $position = $_POST['position'];
    $location = $_POST['location'];
    $anchor_link = $_POST['anchor_link'];
    $anchor_text = $_POST['anchor_text'];
    $price = (float)$_POST['price'];
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $client_contact = $_POST['client_contact'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $ad_code = $_POST['ad_code'];

    $image_url = $_POST['current_image_url'] ?? '';

    // Handle Image Upload
    if (isset($_FILES['ad_image']) && $_FILES['ad_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/ads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['ad_image']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['png', 'jpeg', 'jpg', 'webp', 'gif'];

        if (in_array($file_ext, $allowed_exts)) {
            $filename = uniqid('ad_') . '.' . $file_ext;
            if (move_uploaded_file($_FILES['ad_image']['tmp_name'], $upload_dir . $filename)) {
                // Delete old image if exists
                if ($image_url && file_exists('../' . $image_url)) {
                    unlink('../' . $image_url);
                }
                $image_url = 'uploads/ads/' . $filename;
            }
        }
    }

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE ads SET position = ?, location = ?, anchor_link = ?, anchor_text = ?, price = ?, expiry_date = ?, client_contact = ?, image_url = ?, is_active = ?, ad_code = ? WHERE id = ?");
        $stmt->bind_param("ssssdsssssi", $position, $location, $anchor_link, $anchor_text, $price, $expiry_date, $client_contact, $image_url, $is_active, $ad_code, $id);
        $stmt->execute();
        $success_msg = "Ad updated successfully";
    } else {
        $stmt = $conn->prepare("INSERT INTO ads (position, location, anchor_link, anchor_text, price, expiry_date, client_contact, image_url, is_active, ad_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdsssss", $position, $location, $anchor_link, $anchor_text, $price, $expiry_date, $client_contact, $image_url, $is_active, $ad_code);
        $stmt->execute();
        $success_msg = "Ad created successfully";
    }
    header("Location: ads.php?success=" . urlencode($success_msg));
    exit;
}

require_once 'header.php';

// Filtering
$filter_position = $_GET['position'] ?? '';
$filter_status = $_GET['status'] ?? '';

$query = "SELECT * FROM ads WHERE 1=1";
if ($filter_position) {
    $query .= " AND position = '" . $conn->real_escape_string($filter_position) . "'";
}
if ($filter_status == 'active') {
    $query .= " AND is_active = 1 AND (expiry_date IS NULL OR expiry_date >= CURDATE())";
} elseif ($filter_status == 'expired') {
    $query .= " AND expiry_date < CURDATE()";
} elseif ($filter_status == 'inactive') {
    $query .= " AND is_active = 0";
}

$ads = $conn->query($query . " ORDER BY id DESC");
?>

<div class="space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900">Advertisement Manager</h2>
            <p class="text-slate-500 text-sm">Manage your link and image advertisements</p>
        </div>
        <button onclick="openAdModal()" class="px-6 py-3 bg-emerald-600 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 transition-all flex items-center gap-2">
            <i class="fas fa-plus"></i> Create New Ad
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-wrap gap-4">
        <form method="GET" class="flex flex-wrap gap-4 w-full">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-2">By Position</label>
                <select name="position" onchange="this.form.submit()" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20">
                    <option value="">All Positions</option>
                    <option value="text_link" <?php echo $filter_position == 'text_link' ? 'selected' : ''; ?>>Text Link Ads</option>
                    <option value="image" <?php echo $filter_position == 'image' ? 'selected' : ''; ?>>Image Ads</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-2">By Status</label>
                <select name="status" onchange="this.form.submit()" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $filter_status == 'active' ? 'selected' : ''; ?>>Active & Not Expired</option>
                    <option value="expired" <?php echo $filter_status == 'expired' ? 'selected' : ''; ?>>Expired</option>
                    <option value="inactive" <?php echo $filter_status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <?php if ($filter_position || $filter_status): ?>
                <div class="flex items-end">
                    <a href="ads.php" class="px-4 py-2.5 text-slate-400 hover:text-red-500 text-sm font-bold transition-all">
                        <i class="fas fa-times-circle"></i> Clear
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 p-4 rounded-xl text-sm flex items-center gap-3">
            <i class="fas fa-check-circle"></i>
            <p><?php echo htmlspecialchars($_GET['success']); ?></p>
        </div>
    <?php endif; ?>

    <!-- Ads Table -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Type / Location</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Client / Price</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Expiry</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if ($ads->num_rows == 0): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">No advertisements found matching your criteria.</td>
                        </tr>
                    <?php endif; ?>
                    <?php while ($ad = $ads->fetch_assoc()): ?>
                        <?php
                        $is_expired = $ad['expiry_date'] && strtotime($ad['expiry_date']) < time();
                        ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 overflow-hidden">
                                        <?php if ($ad['image_url']): ?>
                                            <img src="../<?php echo htmlspecialchars($ad['image_url']); ?>" class="h-full w-full object-cover">
                                        <?php else: ?>
                                            <i class="fas <?php echo $ad['position'] == 'image' ? 'fa-image' : 'fa-link'; ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $ad['location']))); ?></div>
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-tighter"><?php echo $ad['position'] == 'image' ? 'Image Ad' : 'Text Link Ad'; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-slate-700"><?php echo htmlspecialchars($ad['client_contact'] ?: 'N/A'); ?></div>
                                <div class="text-xs text-emerald-600 font-bold">$<?php echo number_format($ad['price'], 2); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm <?php echo $is_expired ? 'text-red-500 font-bold' : 'text-slate-600'; ?>">
                                    <?php echo $ad['expiry_date'] ? date('M j, Y', strtotime($ad['expiry_date'])) : 'Never'; ?>
                                </div>
                                <?php if ($is_expired): ?>
                                    <div class="text-[9px] text-red-400 font-black uppercase tracking-widest">Expired</div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($ad['is_active']): ?>
                                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 text-[10px] font-black uppercase rounded-lg">Active</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-500 text-[10px] font-black uppercase rounded-lg">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button onclick='editAd(<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>)' class="p-2 text-slate-400 hover:text-emerald-600 transition-colors">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this ad?')">
                                        <input type="hidden" name="id" value="<?php echo $ad['id']; ?>">
                                        <button type="submit" name="delete_ad" class="p-2 text-slate-400 hover:text-red-500 transition-colors">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Ad Modal -->
<div id="ad-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl animate-in zoom-in duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-xl font-black text-slate-900 uppercase" id="modal-title">Create New Ad</h3>
            <button onclick="closeAdModal()" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-8 space-y-6 max-h-[80vh] overflow-y-auto">
            <input type="hidden" name="id" id="ad-id" value="0">
            <input type="hidden" name="current_image_url" id="ad-current-image">
            <input type="hidden" name="save_ad" value="1">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Position*</label>
                    <select name="position" id="ad-position" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20" onchange="togglePositionFields()">
                        <option value="text_link">Text link ads</option>
                        <option value="image">Image ads</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Location*</label>
                    <select name="location" id="ad-location" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20">
                        <option value="header_text_link">Header Text Link</option>
                        <option value="footer_link">Footer Link</option>
                        <option value="body_text_link">Body Text Link</option>
                        <option value="body_image">Body image</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Anchor Link</label>
                    <input type="url" name="anchor_link" id="ad-anchor-link" placeholder="https://..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>
                <div id="anchor-text-container">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Anchor Text</label>
                    <input type="text" name="anchor_text" id="ad-anchor-text" placeholder="Click here" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>
            </div>

            <div id="image-upload-container" class="hidden">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Upload Image</label>
                <div class="flex items-center gap-4">
                    <div id="image-preview" class="h-20 w-20 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center overflow-hidden">
                        <i class="fas fa-image text-slate-200 text-2xl"></i>
                    </div>
                    <input type="file" name="ad_image" accept="image/*" class="flex-1 text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Price ($)</label>
                    <input type="number" step="0.01" name="price" id="ad-price" value="0.00" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Expiry Date*</label>
                    <input type="date" name="expiry_date" id="ad-expiry-date" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Client Contact</label>
                <input type="text" name="client_contact" id="ad-client-contact" placeholder="Email or Phone" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20">
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Custom Ad Code (Fallback)</label>
                <textarea name="ad_code" id="ad-code" rows="3" placeholder="HTML or JS code..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-sm font-mono outline-none focus:ring-2 focus:ring-emerald-500/20"></textarea>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="checkbox" name="is_active" id="ad-is-active" value="1" checked class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Active Status</span>
                </label>

                <button type="submit" class="px-8 py-4 bg-slate-900 text-white font-black rounded-2xl shadow-xl hover:bg-slate-800 transition-all">Save Advertisement</button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePositionFields() {
    const position = document.getElementById('ad-position').value;
    const anchorTextContainer = document.getElementById('anchor-text-container');
    const imageUploadContainer = document.getElementById('image-upload-container');

    if (position === 'image') {
        anchorTextContainer.classList.add('hidden');
        imageUploadContainer.classList.remove('hidden');
    } else {
        anchorTextContainer.classList.remove('hidden');
        imageUploadContainer.classList.add('hidden');
    }
}

function openAdModal() {
    document.getElementById('modal-title').innerText = 'Create New Ad';
    document.getElementById('ad-id').value = '0';
    document.getElementById('ad-current-image').value = '';
    document.getElementById('ad-position').value = 'text_link';
    document.getElementById('ad-location').value = 'header_text_link';
    document.getElementById('ad-anchor-link').value = '';
    document.getElementById('ad-anchor-text').value = '';
    document.getElementById('ad-price').value = '0.00';
    document.getElementById('ad-expiry-date').value = '';
    document.getElementById('ad-client-contact').value = '';
    document.getElementById('ad-code').value = '';
    document.getElementById('ad-is-active').checked = true;
    document.getElementById('image-preview').innerHTML = '<i class="fas fa-image text-slate-200 text-2xl"></i>';

    togglePositionFields();
    document.getElementById('ad-modal').classList.remove('hidden');
}

function closeAdModal() {
    document.getElementById('ad-modal').classList.add('hidden');
}

function editAd(ad) {
    document.getElementById('modal-title').innerText = 'Edit Ad';
    document.getElementById('ad-id').value = ad.id;
    document.getElementById('ad-current-image').value = ad.image_url || '';
    document.getElementById('ad-position').value = ad.position || 'text_link';
    document.getElementById('ad-location').value = ad.location || 'header_text_link';
    document.getElementById('ad-anchor-link').value = ad.anchor_link || '';
    document.getElementById('ad-anchor-text').value = ad.anchor_text || '';
    document.getElementById('ad-price').value = ad.price || '0.00';
    document.getElementById('ad-expiry-date').value = ad.expiry_date || '';
    document.getElementById('ad-client-contact').value = ad.client_contact || '';
    document.getElementById('ad-code').value = ad.ad_code || '';
    document.getElementById('ad-is-active').checked = ad.is_active == 1;

    if (ad.image_url) {
        document.getElementById('image-preview').innerHTML = `<img src="../${ad.image_url}" class="h-full w-full object-cover">`;
    } else {
        document.getElementById('image-preview').innerHTML = '<i class="fas fa-image text-slate-200 text-2xl"></i>';
    }

    togglePositionFields();
    document.getElementById('ad-modal').classList.remove('hidden');
}
</script>

<?php require_once 'footer.php'; ?>
