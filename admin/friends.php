<?php
/**
 * 后台友情链接管理
 * CRUD 友情链接：名称、URL、头像URL、描述、排序
 * 数据存储在 data/friends.json
 */
require_once __DIR__ . '/auth.php';
handleAdminAuth();

$friendsFile = __DIR__ . '/../data/friends.json';

// 获取最大ID
function getMaxFriendId($friends) {
    $maxId = 0;
    foreach ($friends as $f) {
        if (($f['id'] ?? 0) > $maxId) $maxId = $f['id'];
    }
    return $maxId;
}

// 处理POST - 添加/编辑
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_friend'])) {
    verifyPostCsrf();

    $friends = readJsonFile($friendsFile);
    $editId = (int)($_POST['edit_id'] ?? 0);

    $name = trim($_POST['name'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $avatar = trim($_POST['avatar'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $sort_order = max(0, (int)($_POST['sort_order'] ?? 0));

    if (empty($name) || empty($url)) {
        $error = '名称和URL不能为空。';
    } else {
        if ($editId > 0) {
            // 编辑
            foreach ($friends as &$f) {
                if ((int)$f['id'] === $editId) {
                    $f['name'] = $name;
                    $f['url'] = $url;
                    $f['avatar'] = $avatar;
                    $f['description'] = $description;
                    $f['sort_order'] = $sort_order;
                    break;
                }
            }
            unset($f);
            logOperation('friend_update', '编辑了友情链接：' . $name);
        } else {
            // 添加
            $newId = getMaxFriendId($friends) + 1;
            $friends[] = [
                'id' => $newId,
                'name' => $name,
                'url' => $url,
                'avatar' => $avatar,
                'description' => $description,
                'sort_order' => $sort_order,
            ];
            logOperation('friend_add', '添加了友情链接：' . $name);
        }

        // 按排序排序
        usort($friends, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

        if (writeJsonFile($friendsFile, $friends)) {
            $message = $editId > 0 ? '友情链接已更新！' : '友情链接已添加！';
        } else {
            $error = '保存失败，请检查权限。';
        }
    }
}

// 处理POST - 删除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    verifyPostCsrf();
    $delId = (int)$_POST['delete_id'];

    atomicJsonUpdate($friendsFile, function($friends) use ($delId) {
        return array_values(array_filter($friends, fn($f) => (int)$f['id'] !== $delId));
    });
    logOperation('friend_delete', '删除了友情链接 #' . $delId);
    header('Location: friends.php?deleted=1');
    exit;
}

$friends = readJsonFile($friendsFile);
usort($friends, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

// 获取编辑数据
$editFriend = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($friends as $f) {
        if ((int)$f['id'] === $editId) {
            $editFriend = $f;
            break;
        }
    }
}

$admin_page_title = '友情链接管理';
include __DIR__ . '/admin_header.php';
?>
    <div class="max-w-5xl mx-auto px-6 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold tracking-tight">友情链接管理</h1>
            <a href="friends.php" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-xl flex items-center gap-x-2 hover:bg-indigo-700 transition-colors">
                <i class="fa-solid fa-plus"></i> 添加链接
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
            <div class="mb-6 p-4 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-2xl">友情链接已删除。</div>
        <?php endif; ?>

        <!-- 添加/编辑表单 -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7 mb-6">
            <h3 class="font-semibold text-lg mb-5">
                <?php if ($editFriend): ?>
                    <i class="fa-solid fa-pen text-indigo-400 mr-2"></i>编辑友情链接
                <?php else: ?>
                    <i class="fa-solid fa-plus text-indigo-400 mr-2"></i>添加友情链接
                <?php endif; ?>
            </h3>
            <form method="POST" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="edit_id" value="<?= (int)($editFriend['id'] ?? 0) ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-zinc-300 block mb-1.5">名称 *</label>
                        <input type="text" name="name" value="<?= sanitizeHtml($editFriend['name'] ?? '') ?>" required
                               class="w-full px-4 py-2.5 bg-zinc-800 border border-zinc-700 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="网站名称">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-zinc-300 block mb-1.5">URL *</label>
                        <input type="url" name="url" value="<?= sanitizeHtml($editFriend['url'] ?? '') ?>" required
                               class="w-full px-4 py-2.5 bg-zinc-800 border border-zinc-700 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="https://example.com">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-zinc-300 block mb-1.5">头像URL</label>
                        <input type="url" name="avatar" value="<?= sanitizeHtml($editFriend['avatar'] ?? '') ?>"
                               class="w-full px-4 py-2.5 bg-zinc-800 border border-zinc-700 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="https://example.com/avatar.png">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-zinc-300 block mb-1.5">排序（越小越靠前）</label>
                        <input type="number" name="sort_order" value="<?= (int)($editFriend['sort_order'] ?? 0) ?>" min="0"
                               class="w-full px-4 py-2.5 bg-zinc-800 border border-zinc-700 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-zinc-300 block mb-1.5">描述</label>
                    <textarea name="description" rows="2"
                              class="w-full px-4 py-2.5 bg-zinc-800 border border-zinc-700 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none" placeholder="一句话描述"><?= sanitizeHtml($editFriend['description'] ?? '') ?></textarea>
                </div>

                <div class="flex items-center gap-x-3">
                    <button type="submit" name="save_friend" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-x-2">
                        <i class="fa-solid fa-save"></i> 保存
                    </button>
                    <?php if ($editFriend): ?>
                        <a href="friends.php" class="px-4 py-2.5 text-sm text-zinc-400 hover:text-zinc-300 transition-colors">取消编辑</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- 友情链接列表 -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
            <h3 class="font-semibold text-lg mb-5">所有友情链接（<?= count($friends) ?>）</h3>
            <?php if (empty($friends)): ?>
                <p class="text-zinc-400">暂无友情链接。</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($friends as $f): ?>
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 flex items-center gap-x-4">
                            <?php if (!empty($f['avatar'])): ?>
                                <img src="<?= sanitizeHtml($f['avatar']) ?>" alt="" class="w-10 h-10 rounded-xl object-cover flex-shrink-0">
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-xl bg-zinc-800 flex items-center justify-center flex-shrink-0">
                                    <i class="fa-solid fa-link text-zinc-500"></i>
                                </div>
                            <?php endif; ?>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-x-2">
                                    <span class="font-medium text-sm truncate"><?= sanitizeHtml($f['name']) ?></span>
                                    <a href="<?= sanitizeHtml($f['url']) ?>" target="_blank" class="text-zinc-500 hover:text-indigo-400 text-xs truncate max-w-[200px]">
                                        <?= sanitizeHtml($f['url']) ?>
                                    </a>
                                </div>
                                <?php if (!empty($f['description'])): ?>
                                    <div class="text-xs text-zinc-500 mt-0.5 truncate"><?= sanitizeHtml($f['description']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-x-2 flex-shrink-0">
                                <a href="?edit=<?= (int)$f['id'] ?>" class="px-3 py-1.5 text-xs text-indigo-400 hover:text-indigo-300 bg-indigo-950/50 rounded-lg transition-colors">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form method="POST" class="inline" onsubmit="return confirm('确认删除此友情链接？')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="delete_id" value="<?= (int)$f['id'] ?>">
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
