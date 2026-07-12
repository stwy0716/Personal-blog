<?php
$contentFile = __DIR__ . '/data/content.json';
$content = file_exists($contentFile) ? json_decode(file_get_contents($contentFile), true) : [];
$error = $content['error_pages']['500'] ?? [
    'title' => '服务器错误',
    'message' => '发生了内部服务器错误，请稍后重试。',
    'button_text' => '返回首页'
];
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($error['title']) ?> - 500</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-zinc-950 text-white flex items-center justify-center min-h-screen">
    <div class="text-center px-6 max-w-md">
        <div class="text-8xl font-bold text-zinc-800 mb-4">500</div>
        <h1 class="text-4xl font-bold mb-4"><?= htmlspecialchars($error['title']) ?></h1>
        <p class="text-zinc-400 mb-8"><?= htmlspecialchars($error['message']) ?></p>
        
        <a href="index.php" class="inline-flex items-center justify-center gap-x-2 px-8 h-12 rounded-3xl bg-white text-zinc-900 font-semibold hover:bg-zinc-100 transition">
            <i class="fa-solid fa-home"></i>
            <span><?= htmlspecialchars($error['button_text']) ?></span>
        </a>
    </div>
</body>
</html>