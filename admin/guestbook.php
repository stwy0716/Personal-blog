<?php
require_once __DIR__ . '/auth.php';
handleAdminAuth();

$guestbookFile = __DIR__ . '/../data/guestbook.json';

// 处理POST删除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    verifyPostCsrf();
    $delId = (int)$_POST['delete_id'];

    atomicJsonUpdate($guestbookFile, function($messages) use ($delId) {
        $messages = array_values(array_filter($messages, fn($m) => (int)$m['id'] !== $delId));
        return $messages;
    });
    logOperation('guestbook_delete', '删除了留言 #' . $delId);
    header('Location: guestbook.php?deleted=1');
    exit;
}

$messages = readJsonFile($guestbookFile);
usort($messages, fn($a, $b) => strcmp($b['timestamp'], $a['timestamp']));

$admin_page_title = '留言板管理';
include __DIR__ . '/admin_header.php';
?>
    <div class="max-w-5xl mx-auto px-6 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold tracking-tight">留言板管理</h1>
            <a href="export.php?type=guestbook" class="px-4 py-2 text-sm bg-violet-600 text-white rounded-xl flex items-center gap-x-2">
                <i class="fa-solid fa-download"></i> 导出 CSV
            </a>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="mb-6 p-4 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-2xl">留言已删除。</div>
        <?php endif; ?>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
            <h3 class="font-semibold text-lg mb-5">所有留言（<?= count($messages) ?>）</h3>
            <?php if (empty($messages)): ?>
                <p class="text-zinc-400">暂无留言。</p>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($messages as $msg): ?>
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-5">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-x-3">
                                    <span class="font-semibold"><?= sanitizeHtml($msg['name']) ?></span>
                                    <span class="text-xs text-zinc-400"><?= sanitizeHtml($msg['timestamp']) ?></span>
                                </div>
                                <div class="flex items-center gap-x-3">
                                    <span class="text-sm text-red-500"><i class="fa-solid fa-heart"></i> <?= $msg['likes'] ?? 0 ?></span>
                                    <form method="POST" class="inline" onsubmit="return confirm('确认删除？')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="delete_id" value="<?= (int)$msg['id'] ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-600">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="mt-3 text-[15px] text-zinc-700 dark:text-zinc-300"><?= nl2br(sanitizeHtml($msg['content'])) ?></div>
                            <?php if (!empty($msg['replies'])): ?>
                                <div class="mt-4 pl-4 border-l-2 border-zinc-200 dark:border-zinc-700 space-y-3">
                                    <?php foreach ($msg['replies'] as $reply): ?>
                                        <div>
                                            <span class="font-medium text-sm"><?= sanitizeHtml($reply['name']) ?></span>
                                            <span class="text-xs text-zinc-400 ml-2"><?= sanitizeHtml($reply['timestamp']) ?></span>
                                            <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1"><?= nl2br(sanitizeHtml($reply['content'])) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
