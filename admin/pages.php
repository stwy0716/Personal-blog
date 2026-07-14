<?php
/**
 * 后台自定义页面管理
 * CRUD 自定义页面：标题、slug、内容（支持HTML）、排序
 * 数据存储在 data/pages.json
 */
require_once __DIR__ . '/auth.php';
handleAdminAuth();

$pagesFile = __DIR__ . '/../data/pages.json';

// 获取最大ID
function getMaxPageId($pages) {
    $maxId = 0;
    foreach ($pages as $p) {
        if (($p['id'] ?? 0) > $maxId) $maxId = $p['id'];
    }
    return $maxId;
}

// 处理POST - 添加/编辑
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_page'])) {
    verifyPostCsrf();

    $pages = readJsonFile($pagesFile);
    $editId = (int)($_POST['edit_id'] ?? 0);

    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['page_content'] ?? '';
    $sort_order = max(0, (int)($_POST['sort_order'] ?? 0));

    // 清理slug：只保留字母数字和连字符
    $slug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);
    if (empty($slug)) $slug = 'page-' . time();

    if (empty($title)) {
        $error = '标题不能为空。';
    } else {
        // 检查slug唯一性
        $slugExists = false;
        foreach ($pages as $p) {
            if ($p['slug'] === $slug && (int)$p['id'] !== $editId) {
                $slugExists = true;
                break;
            }
        }
        if ($slugExists) {
            $error = 'URL标识（slug）已存在，请更换。';
        } else {
            if ($editId > 0) {
                // 编辑
                foreach ($pages as &$p) {
                    if ((int)$p['id'] === $editId) {
                        $p['title'] = $title;
                        $p['slug'] = $slug;
                        $p['content'] = $content;
                        $p['sort_order'] = $sort_order;
                        break;
                    }
                }
                unset($p);
                logOperation('page_update', '编辑了页面：' . $title);
            } else {
                // 添加
                $newId = getMaxPageId($pages) + 1;
                $pages[] = [
                    'id' => $newId,
                    'title' => $title,
                    'slug' => $slug,
                    'content' => $content,
                    'sort_order' => $sort_order,
                ];
                logOperation('page_add', '添加了页面：' . $title);
            }

            usort($pages, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

            if (writeJsonFile($pagesFile, $pages)) {
                $message = $editId > 0 ? '页面已更新！' : '页面已添加！';
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

    atomicJsonUpdate($pagesFile, function($pages) use ($delId) {
        return array_values(array_filter($pages, fn($p) => (int)$p['id'] !== $delId));
    });
    logOperation('page_delete', '删除了页面 #' . $delId);
    header('Location: pages.php?deleted=1');
    exit;
}

$pages = readJsonFile($pagesFile);
usort($pages, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

// 获取编辑数据
$editPage = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($pages as $p) {
        if ((int)$p['id'] === $editId) {
            $editPage = $p;
            break;
        }
    }
}

$admin_page_title = '页面管理';
include __DIR__ . '/admin_header.php';
?>
    <div class="max-w-5xl mx-auto px-6 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold tracking-tight">页面管理</h1>
            <a href="pages.php" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-xl flex items-center gap-x-2 hover:bg-indigo-700 transition-colors">
                <i class="fa-solid fa-plus"></i> 新建页面
            </a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="mb-6 p-4 bg-emerald-500/10 text-emerald-400 rounded-2xl flex items-center gap-x-2">
                <i class="fa-solid fa-check-circle"></i> <span><?= sanitizeHtml($message) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 bg-red-500/10 text-red-400 rounded-2xl flex items-center gap-x-2">
                <i class="fa-solid fa-exclamation-circle"></i> <span><?= sanitizeHtml($error) ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="mb-6 p-4 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-2xl">页面已删除。</div>
        <?php endif; ?>

        <!-- 添加/编辑表单 -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7 mb-6">
            <h3 class="font-semibold text-lg mb-5">
                <?php if ($editPage): ?>
                    <i class="fa-solid fa-pen text-indigo-400 mr-2"></i>编辑页面
                <?php else: ?>
                    <i class="fa-solid fa-plus text-indigo-400 mr-2"></i>新建页面
                <?php endif; ?>
            </h3>
            <form method="POST" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="edit_id" value="<?= (int)($editPage['id'] ?? 0) ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-zinc-300 block mb-1.5">页面标题 *</label>
                        <input type="text" name="title" value="<?= sanitizeHtml($editPage['title'] ?? '') ?>" required
                               class="w-full px-4 py-2.5 bg-zinc-800 border border-zinc-700 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="页面标题">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-zinc-300 block mb-1.5">URL标识（slug）</label>
                        <input type="text" name="slug" value="<?= sanitizeHtml($editPage['slug'] ?? '') ?>"
                               class="w-full px-4 py-2.5 bg-zinc-800 border border-zinc-700 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="about-us">
                        <p class="text-xs text-zinc-500 mt-1">仅允许字母、数字、连字符和下划线。留空将自动生成。</p>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-zinc-300 block mb-1.5">页面内容（支持HTML）</label>
                    <textarea name="page_content" rows="10"
                              class="w-full px-4 py-2.5 bg-zinc-800 border border-zinc-700 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono" placeholder="在此输入页面内容，支持HTML标签..."><?= sanitizeHtml($editPage['content'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="text-sm font-medium text-zinc-300 block mb-1.5">排序（越小越靠前）</label>
                    <input type="number" name="sort_order" value="<?= (int)($editPage['sort_order'] ?? 0) ?>" min="0"
                           class="w-full px-4 py-2.5 bg-zinc-800 border border-zinc-700 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent max-w-[200px]">
                </div>

                <div class="flex items-center gap-x-3">
                    <button type="submit" name="save_page" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-x-2">
                        <i class="fa-solid fa-save"></i> 保存
                    </button>
                    <?php if ($editPage): ?>
                        <a href="pages.php" class="px-4 py-2.5 text-sm text-zinc-400 hover:text-zinc-300 transition-colors">取消编辑</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- 页面列表 -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
            <h3 class="font-semibold text-lg mb-5">所有页面（<?= count($pages) ?>）</h3>
            <?php if (empty($pages)): ?>
                <p class="text-zinc-400">暂无自定义页面。</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($pages as $p): ?>
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 flex items-center gap-x-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-x-2">
                                    <span class="font-medium text-sm"><?= sanitizeHtml($p['title']) ?></span>
                                    <span class="text-xs text-zinc-500 bg-zinc-800 px-2 py-0.5 rounded-md">
                                        /page.php?slug=<?= sanitizeHtml($p['slug']) ?>
                                    </span>
                                </div>
                                <?php if (!empty($p['content'])): ?>
                                    <div class="text-xs text-zinc-500 mt-0.5 truncate"><?= mb_substr(strip_tags($p['content']), 0, 80) ?></div>
                                <?php else: ?>
                                    <div class="text-xs text-zinc-600 mt-0.5">无内容</div>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-x-2 flex-shrink-0">
                                <a href="../page.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" class="px-3 py-1.5 text-xs text-emerald-400 hover:text-emerald-300 bg-emerald-950/50 rounded-lg transition-colors" title="查看页面">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="?edit=<?= (int)$p['id'] ?>" class="px-3 py-1.5 text-xs text-indigo-400 hover:text-indigo-300 bg-indigo-950/50 rounded-lg transition-colors">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form method="POST" class="inline" onsubmit="return confirm('确认删除此页面？')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="delete_id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="px-3 py-1.5 text-xs text-red-400 hover:text-red-300 bg-red-950/50 rounded-lg transition-colors">
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
