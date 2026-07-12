<?php
require_once __DIR__ . '/auth.php';
handleAdminAuth();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    verifyPostCsrf();

    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (verifyAdminPassword($current)) {
        if ($new !== $confirm) {
            $error = '两次输入的新密码不一致';
        } else {
            $result = updateAdminPassword($new);
            if ($result === true) {
                logOperation('password_change', '管理员密码已修改');
                logoutAdmin();
                header('Location: index.php');
                exit;
            } elseif ($result === 'length') {
                $error = '新密码至少需要8个字符';
            } elseif ($result === 'need_letter') {
                $error = '密码必须包含至少一个字母';
            } elseif ($result === 'need_number') {
                $error = '密码必须包含至少一个数字';
            } else {
                $error = '密码修改失败';
            }
        }
    } else {
        $error = '当前密码不正确';
    }
}

$admin_page_title = '安全中心';
include __DIR__ . '/admin_header.php';
?>
    <div class="max-w-2xl mx-auto px-6 py-8">
        <h1 class="text-3xl font-bold tracking-tight mb-8">安全中心</h1>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-2xl"><?= sanitizeHtml($error) ?></div>
        <?php endif; ?>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
            <h3 class="font-semibold text-lg mb-5">修改管理员密码</h3>
            <form method="POST" class="space-y-5">
                <?= csrfField() ?>
                <div>
                    <label class="text-xs font-medium text-zinc-500 block mb-1.5">当前密码</label>
                    <input type="password" name="current_password" required class="w-full px-5 h-12 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent">
                </div>
                <div>
                    <label class="text-xs font-medium text-zinc-500 block mb-1.5">新密码（至少8位，须包含字母和数字）</label>
                    <input type="password" name="new_password" required minlength="8" class="w-full px-5 h-12 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent">
                </div>
                <div>
                    <label class="text-xs font-medium text-zinc-500 block mb-1.5">确认新密码</label>
                    <input type="password" name="confirm_password" required class="w-full px-5 h-12 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent">
                </div>
                <button type="submit" name="change_password" class="w-full h-12 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">
                    修改密码
                </button>
            </form>
        </div>
        <div class="mt-4 text-xs text-zinc-400 text-center">修改后将自动退出登录</div>
    </div>
</body>
</html>
