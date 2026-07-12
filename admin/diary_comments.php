<?php
require_once __DIR__ . '/auth.php';
handleAdminAuth();

$commentsFile = __DIR__ . '/../data/diary_comments.json';
$comments = readJsonFile($commentsFile);
$diaries = readJsonFile(__DIR__ . '/../data/diaries.json');
$diaryMap = [];
foreach ($diaries as $d) $diaryMap[$d['id']] = $d['title'];

// POST删除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    verifyPostCsrf();
    $delId = (int)$_POST['delete_id'];

    atomicJsonUpdate($commentsFile, function($comments) use ($delId) {
        return array_values(array_filter($comments, fn($c) => (int)$c['id'] !== $delId));
    });
    logOperation('comment_delete', '删除了评论 #' . $delId);
    header('Location: diary_comments.php?deleted=1');
    exit;
}

// POST隐藏/显示
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    verifyPostCsrf();
    $toggleId = (int)$_POST['toggle_id'];

    atomicJsonUpdate($commentsFile, function($comments) use ($toggleId) {
        foreach ($comments as &$c) {
            if ((int)$c['id'] === $toggleId) {
                $c['hidden'] = !($c['hidden'] ?? false);
                break;
            }
        }
        return $comments;
    });

    $comments = readJsonFile($commentsFile);
    foreach ($comments as $c) {
        if ((int)$c['id'] === $toggleId) {
            logOperation('comment_toggle', (($c['hidden'] ?? false) ? '隐藏' : '显示') . '了评论 #' . $toggleId);
            break;
        }
    }
    header('Location: diary_comments.php?toggled=1');
    exit;
}

// POST编辑
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_comment'])) {
    verifyPostCsrf();
    $editId = (int)$_POST['comment_id'];
    $newContent = sanitizeRichText($_POST['comment_content'] ?? '');

    atomicJsonUpdate($commentsFile, function($comments) use ($editId, $newContent) {
        foreach ($comments as &$c) {
            if ((int)$c['id'] === $editId) {
                $c['content'] = $newContent;
                break;
            }
        }
        return $comments;
    });
    logOperation('comment_edit', '编辑了评论 #' . $editId);
    header('Location: diary_comments.php?edited=1');
    exit;
}

$comments = readJsonFile($commentsFile);
usort($comments, fn($a, $b) => $b['id'] <=> $a['id']);

$admin_page_title = '评论管理';
include __DIR__ . '/admin_header.php';
?>
    <div class="max-w-5xl mx-auto px-6 py-8">
        <h1 class="text-3xl font-bold tracking-tight mb-6">日记评论管理</h1>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="mb-4 p-3 bg-amber-100 text-amber-700 rounded-xl text-sm">评论已删除。</div>
        <?php endif; ?>
        <?php if (isset($_GET['toggled'])): ?>
            <div class="mb-4 p-3 bg-blue-100 text-blue-700 rounded-xl text-sm">可见性已切换。</div>
        <?php endif; ?>
        <?php if (isset($_GET['edited'])): ?>
            <div class="mb-4 p-3 bg-emerald-100 text-emerald-700 rounded-xl text-sm">评论已更新。</div>
        <?php endif; ?>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
            <h3 class="font-semibold text-lg mb-5">所有评论（<?= count($comments) ?>）</h3>
            <?php if (empty($comments)): ?>
                <p class="text-zinc-400">暂无评论。</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($comments as $c): ?>
                        <div class="border rounded-xl p-5 <?= !empty($c['hidden']) ? 'border-red-200 bg-red-50 dark:bg-red-950/20 dark:border-red-800' : 'border-zinc-200 dark:border-zinc-700' ?>">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="font-semibold"><?= sanitizeHtml($c['name']) ?></span>
                                    <span class="text-xs text-zinc-400 ml-2"><?= sanitizeHtml($c['timestamp']) ?></span>
                                    <?php if (!empty($c['hidden'])): ?>
                                        <span class="ml-2 text-xs px-2 py-0.5 bg-red-100 text-red-600 rounded-full">已隐藏</span>
                                    <?php endif; ?>
                                    <div class="text-xs text-zinc-500 mt-1">
                                        日记：<?= sanitizeHtml($diaryMap[$c['diary_id'] ?? 0] ?? '未知') ?>
                                    </div>
                                </div>
                                <div class="flex items-center gap-x-2">
                                    <button onclick="toggleEdit(<?= (int)$c['id'] ?>)" class="text-xs px-3 h-8 flex items-center rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200">
                                        <i class="fa-solid fa-pen mr-1"></i> 编辑
                                    </button>
                                    <form method="POST" class="inline" onsubmit="return confirm('确认切换可见性？')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="toggle_id" value="<?= (int)$c['id'] ?>">
                                        <button type="submit" class="text-xs px-3 h-8 flex items-center rounded-lg bg-amber-100 text-amber-700 hover:bg-amber-200">
                                            <i class="fa-solid fa-eye-slash mr-1"></i> <?= !empty($c['hidden']) ? '显示' : '隐藏' ?>
                                        </button>
                                    </form>
                                    <form method="POST" class="inline" onsubmit="return confirm('确认永久删除？')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="delete_id" value="<?= (int)$c['id'] ?>">
                                        <button type="submit" class="text-xs px-3 h-8 flex items-center rounded-lg bg-red-100 text-red-600 hover:bg-red-200">
                                            <i class="fa-solid fa-trash mr-1"></i> 删除
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div id="content-display-<?= (int)$c['id'] ?>" class="text-[15px] text-zinc-700 dark:text-zinc-300 mt-2">
                                <?= sanitizeRichText($c['content']) ?>
                            </div>

                            <form id="edit-form-<?= (int)$c['id'] ?>" method="POST" class="hidden mt-3">
                                <?= csrfField() ?>
                                <input type="hidden" name="comment_id" value="<?= (int)$c['id'] ?>">
                                <textarea name="comment_content" rows="3" class="w-full px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent text-sm"><?= sanitizeHtml($c['content']) ?></textarea>
                                <div class="flex gap-x-2 mt-2">
                                    <button type="submit" name="edit_comment" class="px-4 h-8 text-xs rounded-lg bg-emerald-600 text-white">保存</button>
                                    <button type="button" onclick="toggleEdit(<?= (int)$c['id'] ?>)" class="px-4 h-8 text-xs rounded-lg border">取消</button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function toggleEdit(id) {
        document.getElementById('edit-form-' + id).classList.toggle('hidden');
        document.getElementById('content-display-' + id).classList.toggle('hidden');
    }
    </script>
</body>
</html>
