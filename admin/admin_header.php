<?php
// Shared admin page header - include after auth check
// Usage: $admin_page_title = 'Page Name'; include 'admin_header.php';
$admin_page_title = $admin_page_title ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($admin_page_title) ?> - 管理后台</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap');
        body { font-family: 'Inter', system-ui, sans-serif; }
    </style>
</head>
<body class="bg-zinc-100 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100">
    <!-- Admin Top Nav -->
    <div class="bg-zinc-900 text-white">
        <div class="max-w-7xl mx-auto px-6 py-3 flex items-center justify-between text-sm">
            <div class="flex items-center gap-x-6">
                <a href="index.php" class="flex items-center gap-x-2 font-bold text-lg">
                    <i class="fa-solid fa-shield-halved text-indigo-400"></i>
                    <span>管理后台</span>
                </a>
                <nav class="hidden md:flex items-center gap-x-1">
                    <a href="index.php" class="px-3 py-1.5 rounded-lg hover:bg-zinc-800 <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'bg-zinc-800 text-indigo-400' : 'text-zinc-300' ?>">仪表盘</a>
                    <a href="content.php" class="px-3 py-1.5 rounded-lg hover:bg-zinc-800 <?= basename($_SERVER['PHP_SELF']) === 'content.php' ? 'bg-zinc-800 text-indigo-400' : 'text-zinc-300' ?>">内容管理</a>
                    <a href="diary.php" class="px-3 py-1.5 rounded-lg hover:bg-zinc-800 <?= basename($_SERVER['PHP_SELF']) === 'diary.php' ? 'bg-zinc-800 text-indigo-400' : 'text-zinc-300' ?>">日记管理</a>
                    <a href="guestbook.php" class="px-3 py-1.5 rounded-lg hover:bg-zinc-800 <?= basename($_SERVER['PHP_SELF']) === 'guestbook.php' ? 'bg-zinc-800 text-indigo-400' : 'text-zinc-300' ?>">留言板</a>
                    <a href="diary_comments.php" class="px-3 py-1.5 rounded-lg hover:bg-zinc-800 <?= basename($_SERVER['PHP_SELF']) === 'diary_comments.php' ? 'bg-zinc-800 text-indigo-400' : 'text-zinc-300' ?>">评论管理</a>
                    <a href="music.php" class="px-3 py-1.5 rounded-lg hover:bg-zinc-800 <?= basename($_SERVER['PHP_SELF']) === 'music.php' ? 'bg-zinc-800 text-indigo-400' : 'text-zinc-300' ?>">音乐管理</a>
                    <a href="friends.php" class="px-3 py-1.5 rounded-lg hover:bg-zinc-800 <?= basename($_SERVER['PHP_SELF']) === 'friends.php' ? 'bg-zinc-800 text-indigo-400' : 'text-zinc-300' ?>">友情链接</a>
                    <a href="pages.php" class="px-3 py-1.5 rounded-lg hover:bg-zinc-800 <?= basename($_SERVER['PHP_SELF']) === 'pages.php' ? 'bg-zinc-800 text-indigo-400' : 'text-zinc-300' ?>">页面</a>
                    <a href="images.php" class="px-3 py-1.5 rounded-lg hover:bg-zinc-800 <?= basename($_SERVER['PHP_SELF']) === 'images.php' ? 'bg-zinc-800 text-indigo-400' : 'text-zinc-300' ?>">图片</a>
                    <a href="backup.php" class="px-3 py-1.5 rounded-lg hover:bg-zinc-800 <?= basename($_SERVER['PHP_SELF']) === 'backup.php' ? 'bg-zinc-800 text-indigo-400' : 'text-zinc-300' ?>">数据备份</a>
                    <a href="logs.php" class="px-3 py-1.5 rounded-lg hover:bg-zinc-800 <?= basename($_SERVER['PHP_SELF']) === 'logs.php' ? 'bg-zinc-800 text-indigo-400' : 'text-zinc-300' ?>">操作日志</a>
                    <a href="settings.php" class="px-3 py-1.5 rounded-lg hover:bg-zinc-800 <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'bg-zinc-800 text-indigo-400' : 'text-zinc-300' ?>">设置</a>
                    <a href="security.php" class="px-3 py-1.5 rounded-lg hover:bg-zinc-800 <?= basename($_SERVER['PHP_SELF']) === 'security.php' ? 'bg-zinc-800 text-indigo-400' : 'text-zinc-300' ?>">安全中心</a>
                </nav>
            </div>
            <div class="flex items-center gap-x-3">
                <a href="../index.php" target="_blank" class="px-3 py-1.5 rounded-lg border border-zinc-700 hover:bg-zinc-800 text-zinc-300 text-xs">
                    <i class="fa-solid fa-eye mr-1"></i> 查看站点
                </a>
                <a href="?logout=1" class="px-3 py-1.5 rounded-lg text-red-400 hover:bg-red-950 text-xs">
                    <i class="fa-solid fa-sign-out-alt mr-1"></i> 退出登录
                </a>
            </div>
        </div>
    </div>
