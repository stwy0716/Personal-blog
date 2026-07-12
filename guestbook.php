<?php
// 先加载安全基础设施，因为下方在 include header.php 之前就需要调用 readJsonFile()
require_once __DIR__ . '/includes/security.php';
// 在 header.php 之前读取内容，以便正确设置 $page_title
$content = readJsonFile(__DIR__ . '/data/content.json');
$settings = $content['settings'] ?? [];
$lang = $settings['language'] ?? 'zh';
$i18nData = $content['i18n'] ?? [];
$i18n = $i18nData[$lang] ?? $i18nData['en'] ?? [];
$gbI18n = $i18n['guestbook'] ?? [];
$page_title = $gbI18n['title'] ?? '留言板';
include __DIR__ . '/includes/header.php';

$guestbookFile = __DIR__ . '/data/guestbook.json';
$messages = readJsonFile($guestbookFile);

// 检查留言板功能是否启用
if (!$guestbookEnabled) {
    http_response_code(403);
    $closedTitle = $gbI18n['closed_title'] ?? '留言板已关闭';
    $closedDesc = $gbI18n['closed_desc'] ?? '留言板功能已被管理员关闭。';
    $backHome = $gbI18n['back_home'] ?? '返回首页';
    echo '<div class="max-w-3xl mx-auto px-6 py-20 text-center"><h1 class="text-4xl font-bold tracking-tighter">' . sanitizeHtml($closedTitle) . '</h1><p class="mt-4 text-gray-500">' . sanitizeHtml($closedDesc) . '</p><a href="index.php" class="mt-6 inline-block px-6 py-2 text-sm bg-indigo-600 text-white rounded-2xl hover:bg-indigo-700">' . sanitizeHtml($backHome) . '</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Sort newest first
usort($messages, fn($a, $b) => strcmp($b['timestamp'], $a['timestamp']));
?>

<div class="max-w-3xl mx-auto px-6 py-12">
    <div class="text-center mb-10">
        <div class="inline-flex px-4 py-1 rounded-3xl bg-violet-100 dark:bg-violet-900 text-violet-600 dark:text-violet-300 text-xs font-semibold tracking-widest mb-3"><?= sanitizeHtml($gbI18n['label'] ?? '留言板') ?></div>
        <h1 class="text-5xl font-bold tracking-tighter"><?= sanitizeHtml($gbI18n['title'] ?? '留言板') ?></h1>
        <p class="mt-2 text-gray-500"><?= sanitizeHtml($gbI18n['subtitle'] ?? '留下你的想法、建议或问候') ?></p>
    </div>
    
    <!-- Post Form -->
    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl p-7 mb-10 shadow-sm">
        <h3 class="font-semibold mb-4 flex items-center gap-x-2">
            <i class="fa-solid fa-pen text-violet-500"></i> 
            <span><?= sanitizeHtml($gbI18n['leave_message'] ?? '留言') ?></span>
        </h3>
        
        <form id="comment-form" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1.5"><?= sanitizeHtml($gbI18n['your_name'] ?? '你的名字') ?></label>
                    <input type="text" id="comment-name" required 
                           class="w-full px-4 h-11 rounded-2xl border border-gray-200 dark:border-gray-600 bg-transparent focus:border-violet-400 outline-none text-sm"
                           placeholder="<?= sanitizeHtml($gbI18n['placeholder_name'] ?? '你的名字') ?>" value="访客">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-medium text-gray-500 block mb-1.5"><?= sanitizeHtml($gbI18n['message'] ?? '留言内容') ?></label>
                    <textarea id="comment-content" required rows="3"
                              class="w-full px-4 py-3 rounded-2xl border border-gray-200 dark:border-gray-600 bg-transparent focus:border-violet-400 outline-none resize-y text-sm"
                              placeholder="<?= sanitizeHtml($gbI18n['placeholder_message'] ?? '写下你想说的话...') ?>"></textarea>
                </div>
            </div>
            
            <button type="submit"
                    class="w-full md:w-auto px-8 h-11 rounded-2xl bg-violet-600 hover:bg-violet-700 active:bg-violet-800 transition text-white font-semibold text-sm flex items-center justify-center gap-x-2">
                <i class="fa-solid fa-paper-plane"></i>
                <span><?= sanitizeHtml($gbI18n['submit'] ?? '提交') ?></span>
            </button>
        </form>
    </div>
    
    <!-- Messages List -->
    <div class="space-y-6" id="messages-list">
        <?php if (empty($messages)): ?>
            <div class="text-center py-10 text-gray-400"><?= sanitizeHtml($gbI18n['no_messages'] ?? '暂无留言，来做第一个留言的人吧！') ?></div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <div class="comment bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl p-6" data-id="<?= (int)$msg['id'] ?>">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="font-semibold"><?= sanitizeHtml($msg['name']) ?></span>
                            <span class="text-xs text-gray-400 ml-2"><?= sanitizeHtml($msg['timestamp']) ?></span>
                        </div>
                        
                        <div class="flex items-center gap-x-4">
                            <!-- Like button -->
                            <button onclick="likeComment(<?= (int)$msg['id'] ?>, this)"
                                    class="flex items-center gap-x-1.5 text-sm text-gray-500 hover:text-red-500 transition-colors">
                                <i class="fa-solid fa-heart"></i>
                                <span class="like-count"><?= (int)($msg['likes'] ?? 0) ?></span>
                            </button>
                            
                            <button onclick="showReplyForm(<?= (int)$msg['id'] ?>, this)"
                                    class="flex items-center gap-x-1.5 text-sm text-gray-500 hover:text-violet-500 transition-colors">
                                <i class="fa-solid fa-reply"></i>
                                <span><?= sanitizeHtml($gbI18n['reply'] ?? '回复') ?></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-[15px] leading-relaxed text-gray-700 dark:text-gray-300">
                        <?= nl2br(sanitizeHtml($msg['content'])) ?>
                    </div>
                    
                    <!-- Replies -->
                    <?php if (!empty($msg['replies'])): ?>
                        <div class="mt-5 space-y-4">
                            <?php foreach ($msg['replies'] as $reply): ?>
                                <div class="nested-reply pt-4">
                                    <div class="flex items-center gap-x-2">
                                        <span class="font-medium text-sm"><?= sanitizeHtml($reply['name']) ?></span>
                                        <span class="text-[10px] text-gray-400"><?= sanitizeHtml($reply['timestamp']) ?></span>
                                    </div>
                                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        <?= nl2br(sanitizeHtml($reply['content'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Reply form placeholder -->
                    <div id="reply-form-<?= (int)$msg['id'] ?>" class="hidden mt-4 pl-1">
                        <div class="flex gap-x-3">
                            <input type="text" id="reply-name-<?= (int)$msg['id'] ?>" placeholder="<?= sanitizeHtml($gbI18n['placeholder_name'] ?? '你的名字') ?>" 
                                   class="flex-1 text-sm px-4 h-9 rounded-2xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900" value="访客">
                            <input type="text" id="reply-content-<?= (int)$msg['id'] ?>" placeholder="<?= sanitizeHtml($gbI18n['placeholder_reply'] ?? '回复...') ?>" 
                                   class="flex-[3] text-sm px-4 h-9 rounded-2xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900">
                            <button onclick="submitReply(<?= (int)$msg['id'] ?>)" 
                                    class="px-5 text-sm font-medium bg-violet-600 text-white rounded-2xl hover:bg-violet-700"><?= sanitizeHtml($gbI18n['reply'] ?? '回复') ?></button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
const gbI18n = <?= json_encode($gbI18n) ?>;

// Handle new comment submission
document.getElementById('comment-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const name = document.getElementById('comment-name').value.trim() || '匿名';
    const content = document.getElementById('comment-content').value.trim();
    
    if (!content) return alert(gbI18n.empty_message || '留言内容不能为空');
    
    const btn = this.querySelector('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> ' + (gbI18n.submitting || '提交中...');
    btn.disabled = true;
    
    try {
        const res = await fetch('api/post_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, content })
        });
        
        const data = await res.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || (gbI18n.submit_failed || '提交失败，请重试'));
        }
    } catch (err) {
        alert(gbI18n.network_error || '网络错误，请检查服务器');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});

async function likeComment(id, btnEl) {
    try {
        const res = await fetch('api/like_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        
        if (data.success) {
            const countSpan = btnEl.querySelector('.like-count');
            if (countSpan) countSpan.textContent = data.likes;
            if (data.already) {
                btnEl.style.color = '#ef4444';
                showToast(gbI18n.already_liked || '你已经点赞过了', 'warning');
            } else {
                btnEl.style.color = '#ef4444';
                setTimeout(() => btnEl.style.color = '', 1200);
            }
        }
    } catch(e) {
        console.error(e);
    }
}

function showReplyForm(parentId, btn) {
    const formContainer = document.getElementById('reply-form-' + parentId);
    if (!formContainer) return;
    
    document.querySelectorAll('[id^="reply-form-"]').forEach(el => {
        if (el.id !== 'reply-form-' + parentId) el.classList.add('hidden');
    });
    
    formContainer.classList.toggle('hidden');
    
    if (!formContainer.classList.contains('hidden')) {
        const input = document.getElementById('reply-content-' + parentId);
        if (input) input.focus();
    }
}

async function submitReply(parentId) {
    const nameInput = document.getElementById('reply-name-' + parentId);
    const contentInput = document.getElementById('reply-content-' + parentId);
    
    const name = nameInput.value.trim() || '匿名';
    const content = contentInput.value.trim();
    
    if (!content) return alert(gbI18n.empty_reply || '回复内容不能为空');
    
    try {
        const res = await fetch('api/reply_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ parent_id: parentId, name, content })
        });
        
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || (gbI18n.reply_failed || '回复失败'));
        }
    } catch(e) {
        alert(gbI18n.submit_failed || '提交失败');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>