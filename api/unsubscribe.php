<?php
require_once __DIR__ . '/../includes/security.php';

$token = $_GET['token'] ?? '';

$subscribersFile = __DIR__ . '/../data/subscribers.json';
$subscribers = readJsonFile($subscribersFile);

$found = false;
foreach ($subscribers as &$s) {
    if (($s['token'] ?? '') === $token) {
        $s['status'] = 'unsubscribed';
        $found = true;
        break;
    }
}
unset($s);

if ($found) {
    writeJsonFile($subscribersFile, $subscribers);
}

$homeUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname(dirname($_SERVER['REQUEST_URI'])) . '/';
$homeUrl = preg_replace('/\/+$/', '/', $homeUrl);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>取消订阅</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap');
        body { font-family: 'Noto Sans SC', system-ui, sans-serif; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex items-center justify-center transition-colors duration-300">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-8 text-center shadow-sm">
            <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center mx-auto mb-5">
                <i class="fa-solid fa-check text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold mb-2">您已成功取消订阅</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-6">您的邮箱将不再收到更新通知。如需重新订阅，可随时访问订阅页面。</p>
            <a href="<?= htmlspecialchars($homeUrl) ?>" class="inline-flex items-center gap-x-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors">
                <i class="fa-solid fa-house"></i>
                <span>返回首页</span>
            </a>
        </div>
    </div>
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</body>
</html>
