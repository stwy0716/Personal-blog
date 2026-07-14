<?php
/**
 * 前台自定义页面路由
 * 通过 GET 参数 slug 加载对应页面
 * 支持自定义模板 page-{slug}.php，否则使用通用模板
 */
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

// 如果slug为空，显示404
if (empty($slug)) {
    http_response_code(404);
    $page_title = '页面未找到';
    include __DIR__ . '/includes/header.php';
    echo '<div class="max-w-5xl mx-auto px-6 pt-20 pb-16 text-center">
        <div class="w-20 h-20 rounded-3xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-file-circle-question text-gray-300 dark:text-gray-600 text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold mb-2">页面未找到</h1>
        <p class="text-gray-500">请提供有效的页面标识。</p>
    </div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// 加载页面数据
$pagesFile = __DIR__ . '/data/pages.json';
$pages = readJsonFile($pagesFile);

$pageData = null;
foreach ($pages as $p) {
    if ($p['slug'] === $slug) {
        $pageData = $p;
        break;
    }
}

if (!$pageData) {
    http_response_code(404);
    $page_title = '页面未找到';
    include __DIR__ . '/includes/header.php';
    echo '<div class="max-w-5xl mx-auto px-6 pt-20 pb-16 text-center">
        <div class="w-20 h-20 rounded-3xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-file-circle-question text-gray-300 dark:text-gray-600 text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold mb-2">页面未找到</h1>
        <p class="text-gray-500">找不到标识为 "' . sanitizeHtml($slug) . '" 的页面。</p>
    </div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = $pageData['title'];

// 检查是否有自定义模板
$customTemplate = __DIR__ . '/page-' . $slug . '.php';

include __DIR__ . '/includes/header.php';

if (file_exists($customTemplate)) {
    // 使用自定义模板
    include $customTemplate;
} else {
    // 通用模板
    ?>
    <div class="max-w-4xl mx-auto px-6 pt-12 pb-16">
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold tracking-tight mb-3"><?= sanitizeHtml($pageData['title']) ?></h1>
        </div>
        <div class="glass-card rounded-3xl p-8 md:p-10">
            <?php if (!empty($pageData['content'])): ?>
                <div class="prose prose-gray dark:prose-invert max-w-none">
                    <?= $pageData['content'] ?>
                </div>
            <?php else: ?>
                <p class="text-gray-400 text-center py-10">此页面暂无内容。</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

include __DIR__ . '/includes/footer.php';
