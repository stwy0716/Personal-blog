<?php
require_once __DIR__ . '/includes/security.php';

sendSecurityHeaders();

$contentFile = __DIR__ . '/data/content.json';
$content = readJsonFile($contentFile);
$error = $content['error_pages']['403'] ?? [
    'title' => '拒绝访问',
    'message' => '抱歉，你没有权限访问此页面。',
    'button_text' => '返回首页'
];
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($error['title']) ?> - 403</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-zinc-950 text-white flex items-center justify-center min-h-screen">
    <div class="text-center px-6 max-w-md">
        <div class="text-8xl font-bold text-zinc-800 mb-4">403</div>
        <h1 class="text-4xl font-bold mb-4"><?= htmlspecialchars($error['title']) ?></h1>
        <p class="text-zinc-400 mb-8"><?= htmlspecialchars($error['message']) ?></p>
        
        <a href="index.php" class="inline-flex items-center justify-center gap-x-2 px-8 h-12 rounded-3xl bg-white text-zinc-900 font-semibold hover:bg-zinc-100 transition">
            <i class="fa-solid fa-home"></i>
            <span><?= htmlspecialchars($error['button_text']) ?></span>
        </a>
    </div>
</body>
</html>