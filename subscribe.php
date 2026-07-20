<?php
require_once __DIR__ . '/includes/security.php';

$content = readJsonFile(__DIR__ . '/data/content.json');
$lang = $content['settings']['language'] ?? 'zh';
$i18nData = $content['i18n'] ?? [];
$i18n = $i18nData[$lang] ?? $i18nData['en'] ?? [];
$subI18n = $i18n['subscribe'] ?? [];

$page_title = $subI18n['subscribe'] ?? '订阅更新';
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-xl mx-auto px-6 py-16 fade-in">
    <div class="text-center mb-10">
        <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-2xl flex items-center justify-center mx-auto mb-5">
            <i class="fa-solid fa-bell text-2xl"></i>
        </div>
        <h1 class="text-3xl font-bold tracking-tight mb-2"><?= sanitizeHtml($subI18n['subscribe'] ?? '订阅更新') ?></h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm"><?= sanitizeHtml($subI18n['description'] ?? '订阅以获取最新文章和动态通知。') ?></p>
    </div>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-sm">
        <form id="subscribe-form" class="space-y-4">
            <div>
                <label for="subscribe-email" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1.5">
                    <?= sanitizeHtml($subI18n['email_label'] ?? '邮箱地址') ?>
                </label>
                <input type="email" id="subscribe-email" name="email" required
                       class="w-full px-4 py-2.5 bg-transparent border border-gray-300 dark:border-zinc-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                       placeholder="<?= sanitizeHtml($subI18n['subscribe_email'] ?? '请输入您的邮箱') ?>">
            </div>
            <button type="submit" id="subscribe-btn"
                    class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors flex items-center justify-center gap-x-2">
                <i class="fa-solid fa-paper-plane"></i>
                <span id="subscribe-btn-text"><?= sanitizeHtml($subI18n['submit'] ?? '立即订阅') ?></span>
            </button>
        </form>

        <div id="subscribe-message" class="hidden mt-4 p-3.5 rounded-xl text-sm flex items-center gap-x-2"></div>
    </div>

    <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-6">
        <?= sanitizeHtml($subI18n['unsubscribe_hint'] ?? '您可以随时通过邮件中的链接取消订阅。') ?>
    </p>
</div>

<script>
(function() {
    const form = document.getElementById('subscribe-form');
    const emailInput = document.getElementById('subscribe-email');
    const btn = document.getElementById('subscribe-btn');
    const btnText = document.getElementById('subscribe-btn-text');
    const msgEl = document.getElementById('subscribe-message');

    function showMessage(text, isError) {
        msgEl.classList.remove('hidden', 'bg-emerald-500/10', 'text-emerald-700', 'dark:text-emerald-400', 'bg-red-500/10', 'text-red-700', 'dark:text-red-400');
        if (isError) {
            msgEl.classList.add('bg-red-500/10', 'text-red-700', 'dark:text-red-400');
        } else {
            msgEl.classList.add('bg-emerald-500/10', 'text-emerald-700', 'dark:text-emerald-400');
        }
        msgEl.innerHTML = '<i class="fa-solid ' + (isError ? 'fa-exclamation-circle' : 'fa-check-circle') + '"></i> <span>' + text + '</span>';
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const email = emailInput.value.trim();
        if (!email) {
            showMessage('<?= addslashes($subI18n['email_required'] ?? '请输入邮箱地址') ?>', true);
            return;
        }

        btn.disabled = true;
        btnText.textContent = '<?= addslashes($subI18n['submitting'] ?? '提交中...') ?>';

        fetch('api/subscribe.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'email=' + encodeURIComponent(email)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showMessage(data.message || '<?= addslashes($subI18n['subscribe_success'] ?? '订阅成功！') ?>', false);
                emailInput.value = '';
            } else {
                showMessage(data.error || '<?= addslashes($subI18n['error'] ?? '订阅失败，请重试') ?>', true);
            }
        })
        .catch(function() {
            showMessage('<?= addslashes($subI18n['network_error'] ?? '网络错误，请检查网络连接') ?>', true);
        })
        .finally(function() {
            btn.disabled = false;
            btnText.textContent = '<?= addslashes($subI18n['submit'] ?? '立即订阅') ?>';
        });
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
