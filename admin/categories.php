<?php
/**
 * 后台分类管理
 * CRUD 分类：名称、slug、描述、排序
 * 数据存储在 data/categories.json
 */
require_once __DIR__ . '/auth.php';
handleAdminAuth();

$categoriesFile = __DIR__ . '/../data/categories.json';

// 获取最大ID
function getMaxCategoryId($categories) {
    $maxId = 0;
    foreach ($categories as $c) {
        if (($c['id'] ?? 0) > $maxId) $maxId = $c['id'];
    }
    return $maxId;
}

// 处理POST - 添加/编辑
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    verifyPostCsrf();

    $categories = readJsonFile($categoriesFile);
    $editId = (int)($_POST['edit_id'] ?? 0);

    $name = sanitizeString($_POST['name'] ?? '', 100);
    $slug = sanitizeString($_POST['slug'] ?? '', 100);
    $description = sanitizeString($_POST['description'] ?? '', 500);
    $sort_order = max(0, (int)($_POST['sort_order'] ?? 0));

    // slug 验证：只允许字母、数字、横线和下划线
    $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug);

    if (empty($name) || empty($slug)) {
        $error = '名称和别名不能为空。';
    } else {
        // 检查 slug 是否重复（排除当前编辑项）
        $slugExists = false;
        foreach ($categories as $c) {
            if ($c['slug'] === $slug && (int)$c['id'] !== $editId) {
                $slugExists = true;
                break;
            }
        }
        if ($slugExists) {
            $error = '别名 "' . $slug . '" 已被使用，请更换。';
        } else {
            if ($editId > 0) {
                // 编辑
                foreach ($categories as &$c) {
                    if ((int)$c['id'] === $editId) {
                        $c['name'] = $name;
                        $c['slug'] = $slug;
                        $c['description'] = $description;
                        $c['sort_order'] = $sort_order;
                        break;
                    }
                }
                unset($c);
                logOperation('category_update', '编辑了分类：' . $name);
            } else {
                // 添加
                $newId = getMaxCategoryId($categories) + 1;
                $categories[] = [
                    'id' => $newId,
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'sort_order' => $sort_order,
                ];
                logOperation('category_add', '添加了分类：' . $name);
            }

            // 按排序排序
            usort($categories, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

            if (writeJsonFile($categoriesFile, $categories)) {
                $message = $editId > 0 ? '分类已更新！' : '分类已添加！';
            } else {
                $error = '保存失败，请检查权限。';
            }
        }
    }
}

// 处理POST - 删除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    verifyPostCsrf();
    $delId = (int)$_POST['delete_id'];

    atomicJsonUpdate($categoriesFile, function($categories) use ($delId) {
        return array_values(array_filter($categories, fn($c) => (int)$c['id'] !== $delId));
    });
    logOperation('category_delete', '删除了分类 #' . $delId);
    header('Location: categories.php?deleted=1');
    exit;
}

$categories = readJsonFile($categoriesFile);
usort($categories, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

// 获取编辑数据
$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($categories as $c) {
        if ((int)$c['id'] === $editId) {
            $editCategory = $c;
            break;
        }
    }
}

$admin_page_title = '分类管理';
include __DIR__ . '/admin_header.php';
?>
    <div class="max-w-5xl mx-auto px-6 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold tracking-tight">分类管理</h1>
            <a href="categories.php" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-xl flex items-center gap-x-2 hover:bg-indigo-700 transition-colors">
                <i class="fa-solid fa-plus"></i> 添加分类
            </a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="mb-6 p-4 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 rounded-2xl flex items-center gap-x-2">
                <i class="fa-solid fa-check-circle"></i> <span><?= sanitizeHtml($message) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 bg-red-500/10 text-red-700 dark:text-red-400 rounded-2xl flex items-center gap-x-2">
                <i class="fa-solid fa-exclamation-circle"></i> <span><?= sanitizeHtml($error) ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="mb-6 p-4 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-2xl">分类已删除。</div>
        <?php endif; ?>

        <!-- 添加/编辑表单 -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7 mb-6">
            <h3 class="font-semibold text-lg mb-5">
                <?php if ($editCategory): ?>
                    <i class="fa-solid fa-pen text-indigo-400 mr-2"></i>编辑分类
                <?php else: ?>
                    <i class="fa-solid fa-plus text-indigo-400 mr-2"></i>添加分类
                <?php endif; ?>
            </h3>
            <form method="POST" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="edit_id" value="<?= (int)($editCategory['id'] ?? 0) ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-zinc-300 block mb-1.5">名称 *</label>
                        <input type="text" name="name" value="<?= sanitizeHtml($editCategory['name'] ?? '') ?>" required
                               class="w-full px-4 py-2.5 bg-transparent border border-gray-300 dark:border-zinc-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="分类名称">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-zinc-300 block mb-1.5">别名 (slug) *</label>
                        <input type="text" name="slug" value="<?= sanitizeHtml($editCategory['slug'] ?? '') ?>" required
                               class="w-full px-4 py-2.5 bg-transparent border border-gray-300 dark:border-zinc-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="tech, life, notes...">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-zinc-300 block mb-1.5">排序（越小越靠前）</label>
                        <input type="number" name="sort_order" value="<?= (int)($editCategory['sort_order'] ?? 0) ?>" min="0"
                               class="w-full px-4 py-2.5 bg-transparent border border-gray-300 dark:border-zinc-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-zinc-300 block mb-1.5">描述</label>
                        <input type="text" name="description" value="<?= sanitizeHtml($editCategory['description'] ?? '') ?>"
                               class="w-full px-4 py-2.5 bg-transparent border border-gray-300 dark:border-zinc-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="分类描述">
                    </div>
                </div>

                <div class="flex items-center gap-x-3">
                    <button type="submit" name="save_category" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-x-2">
                        <i class="fa-solid fa-save"></i> 保存
                    </button>
                    <?php if ($editCategory): ?>
                        <a href="categories.php" class="px-4 py-2.5 text-sm text-gray-500 dark:text-zinc-400 hover:text-gray-600 dark:hover:text-zinc-300 transition-colors">取消编辑</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- 分类列表 -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
            <h3 class="font-semibold text-lg mb-5">所有分类（<?= count($categories) ?>）</h3>
            <?php if (empty($categories)): ?>
                <p class="text-gray-500 dark:text-zinc-400">暂无分类。</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($categories as $c): ?>
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 flex items-center gap-x-4">
                            <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-zinc-800 flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-folder text-gray-500 dark:text-zinc-500"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-x-2">
                                    <span class="font-medium text-sm truncate"><?= sanitizeHtml($c['name']) ?></span>
                                    <span class="text-xs px-2 py-0.5 rounded-lg bg-gray-100 dark:bg-zinc-800 text-gray-500 dark:text-zinc-400"><?= sanitizeHtml($c['slug']) ?></span>
                                </div>
                                <?php if (!empty($c['description'])): ?>
                                    <div class="text-xs text-gray-500 dark:text-zinc-500 mt-0.5 truncate"><?= sanitizeHtml($c['description']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-gray-400 dark:text-zinc-500 flex-shrink-0">
                                排序: <?= (int)($c['sort_order'] ?? 0) ?>
                            </div>
                            <div class="flex items-center gap-x-2 flex-shrink-0">
                                <a href="?edit=<?= (int)$c['id'] ?>" class="px-3 py-1.5 text-xs text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/50 rounded-lg transition-colors">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form method="POST" class="inline" onsubmit="return confirm('确认删除此分类？')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="delete_id" value="<?= (int)$c['id'] ?>">
                                    <button type="submit" class="px-3 py-1.5 text-xs text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300 bg-red-50 dark:bg-red-950/50 rounded-lg transition-colors">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
