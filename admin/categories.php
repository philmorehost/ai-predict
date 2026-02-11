<?php
require_once 'auth.php';
require_once '../includes/functions.php';

// Handle Category Actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $name = sanitize($_POST['name'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $msg = 'added';

    if ($action == 'add') {
        if (empty($slug)) $slug = strtolower(str_replace(' ', '-', $name));
        $stmt = $conn->prepare("INSERT INTO news_categories (name, slug, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $slug, $description);
        $stmt->execute();
        $msg = 'added';
    } elseif ($action == 'edit') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE news_categories SET name = ?, slug = ?, description = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $slug, $description, $id);
        $stmt->execute();
        $msg = 'updated';
    } elseif ($action == 'delete') {
        $id = (int)$_POST['id'];
        if ($id != 1) { // Prevent deleting default category
            $stmt = $conn->prepare("DELETE FROM news_categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            // Move news to General
            $conn->query("UPDATE news SET category_id = 1 WHERE category_id = $id");
        }
        $msg = 'deleted';
    }
    header("Location: categories.php?msg=" . $msg);
    exit;
}

require_once 'header.php';

$categories = $conn->query("SELECT * FROM news_categories ORDER BY id ASC");

$status_msg = '';
if (isset($_GET['msg'])) {
    switch($_GET['msg']) {
        case 'added': $status_msg = 'Category added successfully!'; break;
        case 'updated': $status_msg = 'Category updated successfully!'; break;
        case 'deleted': $status_msg = 'Category deleted successfully!'; break;
    }
}
?>

<?php if ($status_msg): ?>
<div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 rounded-2xl font-bold flex items-center gap-3 animate-in fade-in slide-in-from-top-4 duration-500">
    <i class="fas fa-check-circle"></i>
    <?php echo $status_msg; ?>
</div>
<?php endif; ?>

<div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-700">
    <div class="flex justify-between items-center mb-8">
        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight italic">News Categories</h3>
        <button onclick="showAddModal()" class="px-6 py-2 bg-emerald-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 transition-all">
            <i class="fas fa-plus mr-2"></i> Add Category
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50 dark:border-slate-700">
                    <th class="px-4 py-4">ID</th>
                    <th class="px-4 py-4">Name</th>
                    <th class="px-4 py-4">Slug</th>
                    <th class="px-4 py-4">Description</th>
                    <th class="px-4 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                <?php while($cat = $categories->fetch_assoc()): ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                    <td class="px-4 py-4 text-sm font-bold text-slate-400"><?php echo $cat['id']; ?></td>
                    <td class="px-4 py-4 text-sm font-bold text-slate-700 dark:text-slate-300"><?php echo $cat['name']; ?></td>
                    <td class="px-4 py-4 text-xs font-mono text-slate-500"><?php echo $cat['slug']; ?></td>
                    <td class="px-4 py-4 text-xs text-slate-400"><?php echo $cat['description']; ?></td>
                    <td class="px-4 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($cat)); ?>)" class="p-2 text-slate-400 hover:text-emerald-600 transition-all"><i class="fas fa-edit"></i></button>
                            <?php if ($cat['id'] != 1): ?>
                            <form method="POST" onsubmit="return confirm('Are you sure? All news in this category will be moved to General.')" class="inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                <button type="submit" class="p-2 text-slate-400 hover:text-red-500 transition-all"><i class="fas fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="cat-modal" class="fixed inset-0 bg-slate-900/60 z-[200] flex items-center justify-center hidden p-4">
    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] max-w-md w-full shadow-2xl animate-in zoom-in duration-300">
        <form method="POST" class="p-8 md:p-10">
            <h2 id="modal-title" class="text-2xl font-black text-slate-900 dark:text-white mb-8 uppercase tracking-tight italic">Add Category</h2>
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="id" id="form-id">

            <div class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-4">Category Name</label>
                    <input type="text" name="name" id="form-name" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-4">Slug (Optional)</label>
                    <input type="text" name="slug" id="form-slug" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-4">Description</label>
                    <textarea name="description" id="form-description" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20"></textarea>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="hideModal()" class="flex-1 py-4 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold rounded-2xl">Cancel</button>
                    <button type="submit" class="flex-1 py-4 bg-emerald-600 text-white font-black rounded-2xl shadow-lg shadow-emerald-600/20">Save Category</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function showAddModal() {
    document.getElementById('modal-title').innerText = 'Add Category';
    document.getElementById('form-action').value = 'add';
    document.getElementById('form-id').value = '';
    document.getElementById('form-name').value = '';
    document.getElementById('form-slug').value = '';
    document.getElementById('form-description').value = '';
    document.getElementById('cat-modal').classList.remove('hidden');
}

function showEditModal(cat) {
    document.getElementById('modal-title').innerText = 'Edit Category';
    document.getElementById('form-action').value = 'edit';
    document.getElementById('form-id').value = cat.id;
    document.getElementById('form-name').value = cat.name;
    document.getElementById('form-slug').value = cat.slug;
    document.getElementById('form-description').value = cat.description;
    document.getElementById('cat-modal').classList.remove('hidden');
}

function hideModal() {
    document.getElementById('cat-modal').classList.add('hidden');
}
</script>

<?php require_once 'footer.php'; ?>
