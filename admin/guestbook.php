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

// 处理POST回复
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_id'])) {
    verifyPostCsrf();
    $replyId = (int)$_POST['reply_id'];
    $replyContent = trim($_POST['reply_content'] ?? '');

    if ($replyContent !== '') {
        atomicJsonUpdate($guestbookFile, function($messages) use ($replyId, $replyContent) {
            foreach ($messages as &$m) {
                if ((int)$m['id'] === $replyId) {
                    if (!isset($m['replies'])) {
                        $m['replies'] = [];
                    }
                    $m['replies'][] = [
                        'name' => '管理员',
                        'content' => $replyContent,
                        'timestamp' => date('Y-m-d H:i:s'),
                    ];
                    break;
                }
            }
            return $messages;
        });
        logOperation('guestbook_reply', '回复了留言 #' . $replyId);
        header('Location: guestbook.php?replied=1');
        exit;
    }
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
        <?php if (isset($_GET['replied'])): ?>
            <div class="mb-6 p-4 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-2xl">回复已发送。</div>
        <?php endif; ?>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
            <h3 class="font-semibold text-lg mb-5">所有留言（<?= count($messages) ?>）</h3>
            <?php if (empty($messages)): ?>
                <p class="text-gray-500 dark:text-zinc-400">暂无留言。</p>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($messages as $msg): ?>
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-5">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-x-3">
                                    <span class="font-semibold"><?= sanitizeHtml($msg['name']) ?></span>
                                    <span class="text-xs text-gray-500 dark:text-zinc-400"><?= sanitizeHtml($msg['timestamp']) ?></span>
                                </div>
                                <div class="flex items-center gap-x-3">
                                    <span class="text-sm text-red-500 dark:text-red-400"><i class="fa-solid fa-heart"></i> <?= $msg['likes'] ?? 0 ?></span>
                                    <form method="POST" class="inline" onsubmit="return confirm('确认删除？')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="delete_id" value="<?= (int)$msg['id'] ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="mt-3 text-[15px] text-zinc-700 dark:text-zinc-300"><?= nl2br(sanitizeHtml($msg['content'])) ?></div>
                            <!-- Reply form -->
                            <form method="POST" class="mt-4">
                                <?= csrfField() ?>
                                <input type="hidden" name="reply_id" value="<?= (int)$msg['id'] ?>">
                                <div class="flex gap-x-2">
                                    <input type="text" name="reply_content" placeholder="回复此留言..." required
                                           class="flex-1 px-4 py-2 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-transparent text-sm focus:outline-none focus:border-indigo-500">
                                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-xl transition-colors">
                                        回复
                                    </button>
                                </div>
                            </form>

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
