<?php
require_once 'header.php';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM credit_packages WHERE id = $id");
    $success = "Package deleted!";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $price_usd = $_POST['price_usd'];
    $credits = $_POST['credits'];
    $description = $_POST['description'];

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE credit_packages SET name=?, price_usd=?, credits=?, description=? WHERE id=?");
        $stmt->bind_param("sddsi", $name, $price_usd, $credits, $description, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO credit_packages (name, price_usd, credits, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdds", $name, $price_usd, $credits, $description);
    }

    if ($stmt->execute()) {
        $success = "Package saved!";
    }
}

$packages = $conn->query("SELECT * FROM credit_packages ORDER BY price_usd ASC");
?>

<div class="flex justify-between items-center mb-8">
    <h2 class="text-2xl font-black text-slate-900 tracking-tight">Credit Packages</h2>
    <button onclick="document.getElementById('package-modal').classList.remove('hidden')" class="bg-emerald-600 text-white px-6 py-2 rounded-xl font-bold text-sm shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 transition-all flex items-center gap-2">
        <i class="fas fa-plus"></i> Add Package
    </button>
</div>

<?php if (isset($success)): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 p-4 rounded-xl text-sm mb-6 flex items-center gap-3">
        <i class="fas fa-check-circle"></i>
        <p><?php echo $success; ?></p>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <?php while($p = $packages->fetch_assoc()): ?>
        <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 flex gap-2">
                <button onclick="editPackage(<?php echo htmlspecialchars(json_encode($p)); ?>)" class="text-slate-400 hover:text-emerald-600"><i class="fas fa-edit"></i></button>
                <a href="?delete=<?php echo $p['id']; ?>" onclick="return confirm('Are you sure?')" class="text-slate-400 hover:text-red-600"><i class="fas fa-trash"></i></a>
            </div>
            <h3 class="text-xl font-black text-slate-800 mb-2"><?php echo $p['name']; ?></h3>
            <div class="text-3xl font-black text-emerald-600 mb-4">$<?php echo number_format($p['price_usd']); ?></div>
            <div class="text-sm text-slate-500 mb-6"><?php echo $p['credits']; ?> Prediction Credits</div>
            <p class="text-xs text-slate-400 italic"><?php echo $p['description']; ?></p>
        </div>
    <?php endwhile; ?>
</div>

<!-- Modal -->
<div id="package-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] flex items-center justify-center hidden">
    <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl">
        <h3 class="text-xl font-black text-slate-900 mb-6" id="modal-title">Create Package</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="id" id="p-id">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Package Name</label>
                <input type="text" name="name" id="p-name" required class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Price (USD)</label>
                    <input type="number" step="0.01" name="price_usd" id="p-price" required class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Credits</label>
                    <input type="number" name="credits" id="p-credits" required class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Short Description</label>
                <textarea name="description" id="p-desc" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all" rows="2"></textarea>
            </div>
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="document.getElementById('package-modal').classList.add('hidden')" class="flex-1 py-3 bg-slate-100 text-slate-600 font-bold rounded-xl">Cancel</button>
                <button type="submit" class="flex-1 py-3 bg-slate-900 text-white font-bold rounded-xl">Save Package</button>
            </div>
        </form>
    </div>
</div>

<script>
function editPackage(p) {
    document.getElementById('modal-title').innerText = 'Edit Package';
    document.getElementById('p-id').value = p.id;
    document.getElementById('p-name').value = p.name;
    document.getElementById('p-price').value = p.price_usd;
    document.getElementById('p-credits').value = p.credits;
    document.getElementById('p-desc').value = p.description;
    document.getElementById('package-modal').classList.remove('hidden');
}

function number_format(n) {
    return parseFloat(n).toFixed(2);
}
</script>

<?php require_once 'footer.php'; ?>
