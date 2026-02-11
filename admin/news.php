<?php
require_once 'auth.php';
require_once '../includes/functions.php';

ensureDatabaseTablesExist($conn);
$settings = getSettings($conn);

// Handle News Actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $title = sanitize($_POST['title'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    if (empty($slug)) $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));

    $content = $_POST['content'] ?? ''; // Don't sanitize content for HTML support (using CKEditor)
    $source = sanitize($_POST['source'] ?? $settings['site_name'] ?? 'SurePredictor');
    $category_id = (int)($_POST['category_id'] ?? 1);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_trending = isset($_POST['is_trending']) ? 1 : 0;
    $image_url = sanitize($_POST['image_url'] ?? '');
    $published_at = date('Y-m-d H:i:s', strtotime($_POST['published_at'] ?? 'now'));
    $meta_description = sanitize($_POST['meta_description'] ?? '');
    $meta_keywords = sanitize($_POST['meta_keywords'] ?? '');

    $msg = 'published';

    // Handle Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/news/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $file_ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . '_' . uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = 'uploads/news/' . $file_name;
        }
    }

    if ($action == 'add') {
        $stmt = $conn->prepare("INSERT INTO news (title, slug, content, image_url, category_id, source, is_featured, is_trending, published_at, meta_description, meta_keywords) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssisiisss", $title, $slug, $content, $image_url, $category_id, $source, $is_featured, $is_trending, $published_at, $meta_description, $meta_keywords);
        $stmt->execute();
        $msg = 'published';
    } elseif ($action == 'edit') {
        $id = (int)$_POST['id'];
        if (!empty($image_url)) {
            $stmt = $conn->prepare("UPDATE news SET title = ?, slug = ?, content = ?, image_url = ?, category_id = ?, source = ?, is_featured = ?, is_trending = ?, published_at = ?, meta_description = ?, meta_keywords = ? WHERE id = ?");
            $stmt->bind_param("ssssisiisssi", $title, $slug, $content, $image_url, $category_id, $source, $is_featured, $is_trending, $published_at, $meta_description, $meta_keywords, $id);
        } else {
            $stmt = $conn->prepare("UPDATE news SET title = ?, slug = ?, content = ?, category_id = ?, source = ?, is_featured = ?, is_trending = ?, published_at = ?, meta_description = ?, meta_keywords = ? WHERE id = ?");
            $stmt->bind_param("sssisiisssi", $title, $slug, $content, $category_id, $source, $is_featured, $is_trending, $published_at, $meta_description, $meta_keywords, $id);
        }
        $stmt->execute();
        $msg = 'updated';
    } elseif ($action == 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $msg = 'deleted';
    } elseif ($action == 'bulk_delete') {
        $ids = $_POST['ids'] ?? [];
        if (!empty($ids)) {
            $ids = array_map('intval', $ids);
            $id_list = implode(',', $ids);
            $conn->query("DELETE FROM news WHERE id IN ($id_list)");
            $msg = 'bulk_deleted';
        }
    }
    header("Location: news.php?msg=" . $msg);
    exit;
}

require_once 'header.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$total_news = $conn->query("SELECT COUNT(*) FROM news")->fetch_row()[0];
$total_pages = ceil($total_news / $limit);

$news_list = $conn->query("SELECT n.*, c.name as category_name FROM news n LEFT JOIN news_categories c ON n.category_id = c.id ORDER BY n.created_at DESC LIMIT $limit OFFSET $offset");
$categories = $conn->query("SELECT * FROM news_categories ORDER BY name ASC");
$cat_options = [];
while($cat = $categories->fetch_assoc()) $cat_options[] = $cat;

// If edit mode
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_item = $conn->query("SELECT * FROM news WHERE id = $edit_id")->fetch_assoc();
}

$status_msg = '';
if (isset($_GET['msg'])) {
    switch($_GET['msg']) {
        case 'published': $status_msg = 'Article published successfully!'; break;
        case 'updated': $status_msg = 'Article updated successfully!'; break;
        case 'deleted': $status_msg = 'Article deleted successfully!'; break;
        case 'bulk_deleted': $status_msg = 'Selected articles deleted successfully!'; break;
    }
}
?>

<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>

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
        <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight italic"><?php echo $edit_item ? 'Edit Article' : 'Compose New Article'; ?></h3>
        <a href="news.php" class="text-slate-400 hover:text-slate-600 font-bold text-sm flex items-center gap-2">
            <i class="fas fa-times"></i> Cancel
        </a>
    </div>

    <form method="POST" enctype="multipart/form-data" class="space-y-8">
        <input type="hidden" name="action" value="<?php echo $edit_item ? 'edit' : 'add'; ?>">
        <?php if ($edit_item): ?><input type="hidden" name="id" value="<?php echo $edit_item['id']; ?>"><?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <div class="lg:col-span-2 space-y-8">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-4">Article Title</label>
                    <input type="text" name="title" required value="<?php echo $edit_item ? htmlspecialchars($edit_item['title']) : ''; ?>" placeholder="Enter a catchy headline..." class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl py-4 px-6 outline-none focus:ring-2 focus:ring-emerald-500/20 text-lg font-bold">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-4">Content Body</label>
                    <div class="prose max-w-none">
                        <textarea name="content" id="editor"><?php echo $edit_item ? $edit_item['content'] : ''; ?></textarea>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-900/50 p-8 rounded-[2rem] border border-slate-100 dark:border-slate-800 space-y-6">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">SEO & Metadata</h4>
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-4">Meta Description</label>
                        <textarea name="meta_description" class="w-full bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl py-3 px-5 outline-none text-sm h-20"><?php echo $edit_item ? htmlspecialchars($edit_item['meta_description']) : ''; ?></textarea>
                    </div>
                    <div>
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-4">Meta Keywords (Comma separated)</label>
                        <input type="text" name="meta_keywords" value="<?php echo $edit_item ? htmlspecialchars($edit_item['meta_keywords']) : ''; ?>" placeholder="football, prediction, analysis..." class="w-full bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl py-3 px-5 outline-none text-sm">
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-100 dark:border-slate-800 pb-2">Publishing Options</h4>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Category</label>
                            <select name="category_id" class="w-full bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl py-2 px-4 outline-none text-sm">
                                <?php foreach($cat_options as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($edit_item && $edit_item['category_id'] == $cat['id']) ? 'selected' : ''; ?>><?php echo $cat['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Source / Author</label>
                            <input type="text" name="source" value="<?php echo $edit_item ? htmlspecialchars($edit_item['source']) : $settings['site_name']; ?>" class="w-full bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl py-2 px-4 outline-none text-sm">
                        </div>

                        <div class="flex items-center gap-4 py-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_featured" class="sr-only peer" <?php echo ($edit_item && $edit_item['is_featured']) ? 'checked' : ''; ?>>
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-600"></div>
                                <span class="ml-3 text-xs font-bold text-slate-500">Featured Post</span>
                            </label>
                        </div>

                        <div class="flex items-center gap-4 py-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_trending" class="sr-only peer" <?php echo ($edit_item && $edit_item['is_trending']) ? 'checked' : ''; ?>>
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-600"></div>
                                <span class="ml-3 text-xs font-bold text-slate-500">Trending News</span>
                            </label>
                        </div>

                        <div>
                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Schedule Publication</label>
                            <input type="datetime-local" name="published_at" value="<?php echo $edit_item ? date('Y-m-d\TH:i', strtotime($edit_item['published_at'] ?: 'now')) : date('Y-m-d\TH:i'); ?>" class="w-full bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl py-2 px-4 outline-none text-sm">
                            <p class="text-[9px] text-slate-400 mt-1 italic">*Article won't be visible until this time.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-100 dark:border-slate-800 pb-2">Cover Image</h4>

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

                <button type="submit" class="w-full py-4 bg-emerald-600 text-white font-black rounded-2xl shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 transition-all flex items-center justify-center gap-3">
                    <i class="fas fa-paper-plane"></i>
                    <span><?php echo $edit_item ? 'Update Article' : 'Publish Article'; ?></span>
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
<!-- News List -->
<div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-700">
    <form id="bulk-form" method="POST">
    <input type="hidden" name="action" value="bulk_delete">

    <div class="flex flex-col md:flex-row justify-between md:items-center gap-6 mb-8">
        <div class="flex items-center gap-4">
            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight italic">Article Repository</h3>
            <button type="submit" id="bulk-delete-btn" onclick="return confirm('Delete selected articles?')" class="hidden px-4 py-2 bg-red-500/10 text-red-600 rounded-xl font-bold text-xs hover:bg-red-500 hover:text-white transition-all">
                <i class="fas fa-trash mr-2"></i> Delete Selected
            </button>
        </div>
        <a href="?add=1" target="_self" class="px-6 py-2 bg-emerald-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 transition-all text-center">
            <i class="fas fa-plus mr-2"></i> Create New Article
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50 dark:border-slate-700">
                    <th class="px-4 py-4 w-10">
                        <input type="checkbox" id="select-all" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    </th>
                    <th class="px-4 py-4">Article</th>
                    <th class="px-4 py-4">Category</th>
                    <th class="px-4 py-4">Stats</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                <?php while($item = $news_list->fetch_assoc()): ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                    <td class="px-4 py-6">
                        <input type="checkbox" name="ids[]" value="<?php echo $item['id']; ?>" class="news-checkbox rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    </td>
                    <td class="px-4 py-6">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-16 bg-slate-100 dark:bg-slate-700 rounded-lg overflow-hidden shrink-0">
                                <img src="../<?php echo $item['image_url'] ?: 'assets/img/placeholder.jpg'; ?>" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-800 dark:text-white line-clamp-1"><?php echo $item['title']; ?></div>
                                <div class="text-[10px] text-slate-400 mt-1"><?php echo date('M j, Y', strtotime($item['created_at'])); ?> • By <?php echo $item['source']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-6">
                        <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-2 py-0.5 rounded"><?php echo $item['category_name']; ?></span>
                    </td>
                    <td class="px-4 py-6">
                        <div class="flex flex-col gap-1">
                            <div class="text-xs font-bold text-slate-600 dark:text-slate-400"><i class="fas fa-eye mr-1 opacity-50"></i> <?php echo number_format($item['views']); ?></div>
                        </div>
                    </td>
                    <td class="px-4 py-6">
                        <div class="flex gap-2">
                            <?php if ($item['is_featured']): ?>
                                <span class="h-2 w-2 rounded-full bg-amber-500" title="Featured"></span>
                            <?php endif; ?>
                            <?php if ($item['is_trending']): ?>
                                <span class="h-2 w-2 rounded-full bg-red-500" title="Trending"></span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-4 py-6 text-right">
                        <div class="flex justify-end gap-2">
                            <a href="?edit=<?php echo $item['id']; ?>" target="_self" class="p-2 text-slate-400 hover:text-emerald-600 transition-all"><i class="fas fa-edit"></i></a>
                            <form method="POST" onsubmit="return confirm('Delete this article?')" class="inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="p-2 text-slate-400 hover:text-red-500 transition-all"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="mt-8 flex justify-center gap-2">
        <?php for($i=1; $i<=$total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="h-10 w-10 flex items-center justify-center rounded-xl font-bold text-sm <?php echo $page == $i ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-400'; ?> transition-all"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    </form>
</div>

<script>
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.news-checkbox');
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
        const checkedCount = document.querySelectorAll('.news-checkbox:checked').length;
        if (checkedCount > 0) {
            bulkDeleteBtn.classList.remove('hidden');
        } else {
            bulkDeleteBtn.classList.add('hidden');
        }
    }
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
