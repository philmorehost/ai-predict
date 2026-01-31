<?php
require_once 'auth.php';
require_once '../includes/functions.php';

ensureDatabaseTablesExist($conn);
$settings = getSettings($conn);

// Handle Page Actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $title = sanitize($_POST['title'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    if (empty($slug)) $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));

    $content = $_POST['content'] ?? ''; // Don't sanitize content for HTML support (using CKEditor)
    $meta_title = sanitize($_POST['meta_title'] ?? $title);
    $meta_description = sanitize($_POST['meta_description'] ?? '');
    $meta_keywords = sanitize($_POST['meta_keywords'] ?? '');
    $status = sanitize($_POST['status'] ?? 'published');

    if ($action == 'add') {
        $stmt = $conn->prepare("INSERT INTO pages (title, slug, content, meta_title, meta_description, meta_keywords, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $title, $slug, $content, $meta_title, $meta_description, $meta_keywords, $status);
        $stmt->execute();
        $msg = 'created';
    } elseif ($action == 'edit') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE pages SET title = ?, slug = ?, content = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssssssi", $title, $slug, $content, $meta_title, $meta_description, $meta_keywords, $status, $id);
        $stmt->execute();
        $msg = 'updated';
    } elseif ($action == 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM pages WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $msg = 'deleted';
    }
    header("Location: pages.php?msg=" . $msg);
    exit;
}

require_once 'header.php';

$pages_list = $conn->query("SELECT * FROM pages ORDER BY created_at DESC");

// If edit mode
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_item = $conn->query("SELECT * FROM pages WHERE id = $edit_id")->fetch_assoc();
}

$status_msg = '';
if (isset($_GET['msg'])) {
    switch($_GET['msg']) {
        case 'created': $status_msg = 'Page created successfully!'; break;
        case 'updated': $status_msg = 'Page updated successfully!'; break;
        case 'deleted': $status_msg = 'Page deleted successfully!'; break;
    }
}
?>

<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>

<div class="flex justify-between items-center mb-8">
    <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight italic uppercase">Custom Pages</h2>
    <?php if (!$edit_item && !isset($_GET['add'])): ?>
    <a href="?add=1" class="bg-emerald-600 text-white px-6 py-2 rounded-xl font-bold text-sm shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 transition-all flex items-center gap-2">
        <i class="fas fa-plus"></i> Create New Page
    </a>
    <?php endif; ?>
</div>

<?php if ($status_msg): ?>
<div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 rounded-2xl font-bold flex items-center gap-3 animate-in fade-in slide-in-from-top-4 duration-500">
    <i class="fas fa-check-circle"></i>
    <?php echo $status_msg; ?>
</div>
<?php endif; ?>

<?php if ($edit_item || isset($_GET['add'])): ?>
<!-- Add/Edit Form -->
<div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 md:p-12 shadow-sm border border-slate-100 dark:border-slate-700 animate-in fade-in duration-500">
    <div class="flex justify-between items-center mb-10">
        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight italic"><?php echo $edit_item ? 'Edit Page' : 'Create New Page'; ?></h3>
        <a href="pages.php" class="text-slate-400 hover:text-slate-600 font-bold text-sm flex items-center gap-2">
            <i class="fas fa-times"></i> Cancel
        </a>
    </div>

    <form method="POST" class="space-y-8">
        <input type="hidden" name="action" value="<?php echo $edit_item ? 'edit' : 'add'; ?>">
        <?php if ($edit_item): ?><input type="hidden" name="id" value="<?php echo $edit_item['id']; ?>"><?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <div class="lg:col-span-2 space-y-8">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-4">Page Title</label>
                    <input type="text" name="title" required value="<?php echo $edit_item ? htmlspecialchars($edit_item['title']) : ''; ?>" placeholder="e.g. About Us, Terms of Service..." class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20 text-lg font-bold">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-4">Custom Slug (URL)</label>
                    <input type="text" name="slug" value="<?php echo $edit_item ? htmlspecialchars($edit_item['slug']) : ''; ?>" placeholder="leave-blank-for-auto-generate" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20 font-mono text-sm">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-4">Page Content</label>
                    <div class="prose max-w-none">
                        <textarea name="content" id="editor"><?php echo $edit_item ? $edit_item['content'] : ''; ?></textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                <div class="bg-slate-50 dark:bg-slate-900/50 p-8 rounded-[2rem] border border-slate-100 dark:border-slate-800 space-y-6">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">SEO & Metadata</h4>
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-4">Meta Title</label>
                        <input type="text" name="meta_title" value="<?php echo $edit_item ? htmlspecialchars($edit_item['meta_title']) : ''; ?>" class="w-full bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl py-3 px-5 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-4">Meta Description</label>
                        <textarea name="meta_description" class="w-full bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl py-3 px-5 outline-none text-sm h-24"><?php echo $edit_item ? htmlspecialchars($edit_item['meta_description']) : ''; ?></textarea>
                    </div>
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-4">Meta Keywords</label>
                        <input type="text" name="meta_keywords" value="<?php echo $edit_item ? htmlspecialchars($edit_item['meta_keywords']) : ''; ?>" placeholder="comma, separated, values" class="w-full bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl py-3 px-5 outline-none text-sm">
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-100 dark:border-slate-800 pb-2">Status</h4>
                    <select name="status" class="w-full bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl py-2 px-4 outline-none text-sm">
                        <option value="published" <?php echo ($edit_item && $edit_item['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo ($edit_item && $edit_item['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>

                <button type="submit" class="w-full py-4 bg-emerald-600 text-white font-black rounded-2xl shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 transition-all flex items-center justify-center gap-3">
                    <i class="fas fa-save"></i>
                    <span><?php echo $edit_item ? 'Update Page' : 'Create Page'; ?></span>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    ClassicEditor
        .create(document.querySelector('#editor'), {
            toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'undo', 'redo']
        })
        .catch(error => {
            console.error(error);
        });
</script>

<?php else: ?>
<!-- Pages List -->
<div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-700">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50 dark:border-slate-700">
                    <th class="px-4 py-4">Page Title</th>
                    <th class="px-4 py-4">URL Slug</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Created</th>
                    <th class="px-4 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                <?php while($item = $pages_list->fetch_assoc()): ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                    <td class="px-4 py-6">
                        <div class="text-sm font-bold text-slate-800 dark:text-white"><?php echo $item['title']; ?></div>
                    </td>
                    <td class="px-4 py-6">
                        <div class="text-xs font-mono text-slate-400">/p/<?php echo $item['slug']; ?></div>
                    </td>
                    <td class="px-4 py-6">
                        <span class="text-[10px] font-black uppercase tracking-widest <?php echo $item['status'] == 'published' ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20' : 'text-amber-600 bg-amber-50 dark:bg-amber-900/20'; ?> px-2 py-0.5 rounded"><?php echo $item['status']; ?></span>
                    </td>
                    <td class="px-4 py-6">
                        <div class="text-[10px] text-slate-400"><?php echo date('M j, Y', strtotime($item['created_at'])); ?></div>
                    </td>
                    <td class="px-4 py-6 text-right">
                        <div class="flex justify-end gap-2">
                            <a href="../p/<?php echo $item['slug']; ?>" target="_blank" class="p-2 text-slate-400 hover:text-blue-500 transition-all"><i class="fas fa-external-link-alt"></i></a>
                            <a href="?edit=<?php echo $item['id']; ?>" class="p-2 text-slate-400 hover:text-emerald-600 transition-all"><i class="fas fa-edit"></i></a>
                            <form method="POST" onsubmit="return confirm('Delete this page?')" class="inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="p-2 text-slate-400 hover:text-red-500 transition-all"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($pages_list->num_rows == 0): ?>
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-slate-400 text-sm italic">No custom pages created yet.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
