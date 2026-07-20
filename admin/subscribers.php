<?php
/**
 * 后台订阅者管理
 * CRUD 订阅者：邮箱、状态、订阅日期
 * 支持批量发送邮件给所有活跃订阅者
 * 数据存储在 data/subscribers.json
 */
require_once __DIR__ . '/auth.php';
handleAdminAuth();

require_once __DIR__ . '/../includes/mailer.php';

$subscribersFile = __DIR__ . '/../data/subscribers.json';

// 处理POST - 删除订阅者
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    verifyPostCsrf();
    $delId = $_POST['delete_id'];

    atomicJsonUpdate($subscribersFile, function($subscribers) use ($delId) {
        return array_values(array_filter($subscribers, fn($s) => ($s['id'] ?? '') !== $delId));
    });
    logOperation('subscriber_delete', '删除了订阅者 #' . sanitizeHtml($delId));
    header('Location: subscribers.php?deleted=1');
    exit;
}

// 处理POST - 批量发送邮件
$sendMessage = '';
$sendError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_bulk_email'])) {
    verifyPostCsrf();

    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($subject) || empty($message)) {
        $sendError = '主题和正文不能为空。';
    } else {
        $subscribers = readJsonFile($subscribersFile);
        $activeSubscribers = array_filter($subscribers, fn($s) => ($s['status'] ?? 'active') === 'active');
        $sentCount = 0;
        $failCount = 0;

        $siteConfig = getSmtpConfig();
        $siteTitle = $siteConfig['site_title'] ?? '个人主页';

        $body = '<!DOCTYPE html>';
        $body .= '<html><head><meta charset="UTF-8"></head><body style="font-family:system-ui,sans-serif;background:#f4f4f5;padding:40px 20px;">';
        $body .= '<div style="max-width:520px;margin:0 auto;background:#fff;border-radius:16px;padding:32px;box-shadow:0 4px 24px rgba(0,0,0,0.06);">';
        $body .= '<h2 style="margin:0 0 20px;font-size:18px;color:#18181b;">' . htmlspecialchars($siteTitle) . '</h2>';
        $body .= '<div style="font-size:14px;color:#27272a;line-height:1.6;">' . nl2br(htmlspecialchars($message)) . '</div>';
        $body .= '<p style="margin:24px 0 0;font-size:12px;color:#a1a1aa;">这是一封来自 ' . htmlspecialchars($siteTitle) . ' 的邮件。</p>';
        $body .= '</div></body></html>';

        foreach ($activeSubscribers as $s) {
            $to = $s['email'] ?? '';
            if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $failCount++;
                continue;
            }
            $result = smtpSendMail($to, $subject, $body);
            if (!$result) {
                // 如果SMTP不可用，尝试PHP mail()作为后备
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: " . ($siteConfig['admin_email'] ?: 'noreply@localhost') . "\r\n";
                $result = mail($to, $subject, $body, $headers);
            }
            if ($result) {
                $sentCount++;
            } else {
                $failCount++;
            }
        }

        $sendMessage = "邮件发送完成：成功 {$sentCount} 封" . ($failCount > 0 ? "，失败 {$failCount} 封" : '') . "。";
        logOperation('subscriber_bulk_email', '批量发送邮件：成功 ' . $sentCount . ' 封，失败 ' . $failCount . ' 封');
    }
}

$subscribers = readJsonFile($subscribersFile);

// 按订阅时间倒序
usort($subscribers, fn($a, $b) => strtotime($b['subscribed_at'] ?? '0') <=> strtotime($a['subscribed_at'] ?? '0'));

$activeCount = count(array_filter($subscribers, fn($s) => ($s['status'] ?? 'active') === 'active'));
$totalCount = count($subscribers);

$admin_page_title = '订阅管理';
include __DIR__ . '/admin_header.php';
?>
    <div class="max-w-5xl mx-auto px-6 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold tracking-tight">订阅管理</h1>
            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                活跃 <?= (int)$activeCount ?> / 总计 <?= (int)$totalCount ?>
            </div>
        </div>

        <?php if (!empty($sendMessage)): ?>
            <div class="mb-6 p-4 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 rounded-2xl flex items-center gap-x-2">
                <i class="fa-solid fa-check-circle"></i> <span><?= sanitizeHtml($sendMessage) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($sendError)): ?>
            <div class="mb-6 p-4 bg-red-500/10 text-red-700 dark:text-red-400 rounded-2xl flex items-center gap-x-2">
                <i class="fa-solid fa-exclamation-circle"></i> <span><?= sanitizeHtml($sendError) ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="mb-6 p-4 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-2xl">订阅者已删除。</div>
        <?php endif; ?>

        <!-- 批量发送邮件 -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7 mb-6">
            <h3 class="font-semibold text-lg mb-5">
                <i class="fa-solid fa-paper-plane text-indigo-400 mr-2"></i>批量发送邮件
            </h3>
            <form method="POST" class="space-y-4">
                <?= csrfField() ?>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-zinc-300 block mb-1.5">主题</label>
                    <input type="text" name="subject" required
                           class="w-full px-4 py-2.5 bg-transparent border border-gray-300 dark:border-zinc-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="邮件主题">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-zinc-300 block mb-1.5">正文</label>
                    <textarea name="message" rows="4" required
                              class="w-full px-4 py-2.5 bg-transparent border border-gray-300 dark:border-zinc-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none" placeholder="邮件正文内容..."></textarea>
                </div>
                <div class="flex items-center gap-x-3">
                    <button type="submit" name="send_bulk_email" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-x-2">
                        <i class="fa-solid fa-paper-plane"></i> 发送给所有活跃订阅者
                    </button>
                </div>
            </form>
        </div>

        <!-- 订阅者列表 -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
            <h3 class="font-semibold text-lg mb-5">所有订阅者（<?= count($subscribers) ?>）</h3>
            <?php if (empty($subscribers)): ?>
                <p class="text-gray-500 dark:text-zinc-400">暂无订阅者。</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($subscribers as $s): ?>
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 flex items-center gap-x-4">
                            <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-zinc-800 flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-envelope text-gray-500 dark:text-zinc-500"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-x-2">
                                    <span class="font-medium text-sm truncate text-gray-900 dark:text-white"><?= sanitizeHtml($s['email'] ?? '') ?></span>
                                    <?php if (($s['status'] ?? 'active') === 'active'): ?>
                                        <span class="px-2 py-0.5 text-[10px] font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-full">活跃</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 text-[10px] font-medium bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 rounded-full">已取消</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-zinc-500 mt-0.5">
                                    订阅于 <?= sanitizeHtml($s['subscribed_at'] ?? '未知') ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-x-2 flex-shrink-0">
                                <form method="POST" class="inline" onsubmit="return confirm('确认删除此订阅者？')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="delete_id" value="<?= sanitizeHtml($s['id'] ?? '') ?>">
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
