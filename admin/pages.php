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
    $image_url = sanitize($_POST['image_url'] ?? '');
    $show_in_main_menu = isset($_POST['show_in_main_menu']) ? 1 : 0;
    $show_in_footer_menu = isset($_POST['show_in_footer_menu']) ? 1 : 0;
    $show_in_news_sidebar = isset($_POST['show_in_news_sidebar']) ? 1 : 0;

    // Handle Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/pages/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $file_ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . '_' . uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = 'uploads/pages/' . $file_name;
        }
    }

    if ($action == 'add') {
        $stmt = $conn->prepare("INSERT INTO pages (title, slug, content, image_url, meta_title, meta_description, meta_keywords, status, show_in_main_menu, show_in_footer_menu, show_in_news_sidebar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssiii", $title, $slug, $content, $image_url, $meta_title, $meta_description, $meta_keywords, $status, $show_in_main_menu, $show_in_footer_menu, $show_in_news_sidebar);
        $stmt->execute();
        $msg = 'created';
    } elseif ($action == 'edit') {
        $id = (int)$_POST['id'];
        if (!empty($image_url)) {
            $stmt = $conn->prepare("UPDATE pages SET title = ?, slug = ?, content = ?, image_url = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, status = ?, show_in_main_menu = ?, show_in_footer_menu = ?, show_in_news_sidebar = ? WHERE id = ?");
            $stmt->bind_param("ssssssssiiii", $title, $slug, $content, $image_url, $meta_title, $meta_description, $meta_keywords, $status, $show_in_main_menu, $show_in_footer_menu, $show_in_news_sidebar, $id);
        } else {
            $stmt = $conn->prepare("UPDATE pages SET title = ?, slug = ?, content = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, status = ?, show_in_main_menu = ?, show_in_footer_menu = ?, show_in_news_sidebar = ? WHERE id = ?");
            $stmt->bind_param("ssssssiiii", $title, $slug, $content, $meta_title, $meta_description, $meta_keywords, $status, $show_in_main_menu, $show_in_footer_menu, $show_in_news_sidebar, $id);
        }
        $stmt->execute();
        $msg = 'updated';
    } elseif ($action == 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM pages WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $msg = 'deleted';
    } elseif ($action == 'bulk_delete') {
        $ids = $_POST['ids'] ?? [];
        if (!empty($ids)) {
            $ids = array_map('intval', $ids);
            $id_list = implode(',', $ids);
            $conn->query("DELETE FROM pages WHERE id IN ($id_list)");
            $msg = 'bulk_deleted';
        }
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
        case 'bulk_deleted': $status_msg = 'Selected pages deleted successfully!'; break;
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

    <form method="POST" enctype="multipart/form-data" class="space-y-8">
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
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-100 dark:border-slate-800 pb-2">Featured Image</h4>
                    <div class="space-y-4">
                        <?php if ($edit_item && $edit_item['image_url']): ?>
                            <img src="../<?php echo $edit_item['image_url']; ?>" class="w-full h-32 object-cover rounded-xl border border-slate-200 dark:border-slate-700">
                        <?php endif; ?>

                        <div class="flex flex-col gap-2">
                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Upload New</label>
                            <input type="file" name="image" class="text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        </div>

                        <div>
                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Or Image URL</label>
                            <input type="text" name="image_url" value="<?php echo $edit_item ? htmlspecialchars($edit_item['image_url']) : ''; ?>" placeholder="https://..." class="w-full bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl py-2 px-4 outline-none text-xs">
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-100 dark:border-slate-800 pb-2">Display Settings</h4>
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="show_in_main_menu" id="show_in_main_menu" value="1" <?php echo ($edit_item && $edit_item['show_in_main_menu']) ? 'checked' : ''; ?> class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <label for="show_in_main_menu" class="text-xs font-bold text-slate-600">Show in Main Menu</label>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="show_in_footer_menu" id="show_in_footer_menu" value="1" <?php echo ($edit_item && $edit_item['show_in_footer_menu']) ? 'checked' : ''; ?> class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <label for="show_in_footer_menu" class="text-xs font-bold text-slate-600">Show in Footer Menu</label>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="show_in_news_sidebar" id="show_in_news_sidebar" value="1" <?php echo ($edit_item && $edit_item['show_in_news_sidebar']) ? 'checked' : ''; ?> class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <label for="show_in_news_sidebar" class="text-xs font-bold text-slate-600">Show in News Sidebar</label>
                        </div>
                    </div>

                    <div class="mt-8">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Publishing Status</label>
                        <select name="status" class="w-full bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl py-2 px-4 outline-none text-sm">
                            <option value="published" <?php echo ($edit_item && $edit_item['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                            <option value="draft" <?php echo ($edit_item && $edit_item['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>
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
    <form id="bulk-form" method="POST">
    <input type="hidden" name="action" value="bulk_delete">

    <div class="flex flex-col md:flex-row justify-between md:items-center gap-6 mb-8">
        <div class="flex items-center gap-4">
            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight italic">Page Repository</h3>
            <button type="submit" id="bulk-delete-btn" onclick="return confirm('Delete selected pages?')" class="hidden px-4 py-2 bg-red-500/10 text-red-600 rounded-xl font-bold text-xs hover:bg-red-500 hover:text-white transition-all">
                <i class="fas fa-trash mr-2"></i> Delete Selected
            </button>
        </div>
        <a href="?add=1" class="px-6 py-2 bg-emerald-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 transition-all text-center">
            <i class="fas fa-plus mr-2"></i> Create New Page
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50 dark:border-slate-700">
                    <th class="px-4 py-4 w-10">
                        <input type="checkbox" id="select-all" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    </th>
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
                        <input type="checkbox" name="ids[]" value="<?php echo $item['id']; ?>" class="page-checkbox rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    </td>
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
    </form>
</div>

<script>
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.page-checkbox');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateBulkDeleteVisibility();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkDeleteVisibility);
    });

    function updateBulkDeleteVisibility() {
        const checkedCount = document.querySelectorAll('.page-checkbox:checked').length;
        if (checkedCount > 0) {
            bulkDeleteBtn.classList.remove('hidden');
        } else {
            bulkDeleteBtn.classList.add('hidden');
        }
    }
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
