<?php
$page_title = '首页';
include __DIR__ . '/includes/header.php';

// Load dynamic content from JSON
$site = $content['site'] ?? [
    'title' => '我的个人空间',
    'subtitle' => '开发者 / 创作者 / 探索者',
    'avatar' => 'https://picsum.photos/id/1005/320/320',
    'title_prefix' => '你好，我是'
];
$blogIntro = $content['blog_intro'] ?? '欢迎来到我的数字角落！这里记录我的技术探索、生活故事和成长感悟。';
$homepage = $content['homepage'] ?? [];

// 多语言支持：使用独立变量 $pageI18n，避免覆盖 header.php 中定义的 $i18n（完整语言块），
// 否则会导致 footer.php 中 $i18n['footer'] 失效。
$pageI18n = $i18n['index'] ?? ($content['i18n']['en']['index'] ?? []);

$text = function(string $key, string $fallback) use ($pageI18n): string {
    return $pageI18n[$key] ?? $fallback;
};
?>

<div class="max-w-5xl mx-auto px-6 pt-12 pb-16">
    <!-- Hero Section -->
    <div class="text-center mb-16">
        <!-- Avatar -->
        <div class="flex justify-center mb-8">
            <div class="relative group">
                <img src="<?= htmlspecialchars($site['avatar']) ?>" 
                     alt="头像"
                     class="w-36 h-36 md:w-40 md:h-40 rounded-[3rem] object-cover ring-8 ring-white dark:ring-gray-800 shadow-2xl group-hover:scale-[1.03] transition-transform duration-500">
                <div class="absolute -bottom-1 -right-1 w-9 h-9 bg-emerald-500 border-4 border-white dark:border-gray-800 rounded-2xl flex items-center justify-center">
                    <i class="fa-solid fa-check text-white text-sm"></i>
                </div>
            </div>
        </div>
        
        <!-- Title & Subtitle -->
        <div class="max-w-2xl mx-auto">
            <div class="inline-flex items-center gap-x-2 px-4 py-1.5 rounded-3xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 text-sm font-medium mb-4">
                <i class="fa-solid fa-globe"></i>
                <span><?= sanitizeHtml($text('personal_digital_space', '个人数字空间')) ?></span>
            </div>
            
            <h1 class="text-5xl md:text-6xl font-bold tracking-tighter mb-3">
                <?= htmlspecialchars($site['title_prefix']) ?> 
                <span class="bg-gradient-to-r from-indigo-600 via-violet-600 to-fuchsia-600 bg-clip-text text-transparent">
                    <?= htmlspecialchars(str_replace(' Personal Space', '', str_replace('My ', '', $site['title']))) ?>
                </span>
            </h1>
            
            <p class="text-2xl md:text-3xl text-gray-600 dark:text-gray-300 font-light tracking-tight mb-6">
                <?= htmlspecialchars($site['subtitle']) ?>
            </p>
            
            <div class="flex justify-center gap-x-3">
                <a href="diary.php" 
                   class="inline-flex items-center justify-center gap-x-2 px-8 h-12 rounded-3xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-semibold text-sm hover:bg-gray-800 dark:hover:bg-gray-100 active:scale-[0.985] transition-all shadow-lg">
                    <span><?= sanitizeHtml($text('read_latest_journal', '阅读最新日记')) ?></span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="guestbook.php" 
                   class="inline-flex items-center justify-center gap-x-2 px-6 h-12 rounded-3xl border border-gray-300 dark:border-gray-600 font-medium text-sm hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                    <span><?= sanitizeHtml($text('guestbook', '留言板')) ?></span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Blog Intro Card -->
    <div class="max-w-3xl mx-auto mb-16">
        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl p-8 md:p-10 shadow-sm">
            <div class="flex items-center gap-x-3 mb-5">
                <div class="w-8 h-8 rounded-2xl bg-amber-100 dark:bg-amber-900 flex items-center justify-center">
                    <i class="fa-solid fa-book text-amber-600 dark:text-amber-400"></i>
                </div>
                <h2 class="text-xl font-semibold tracking-tight"><?= sanitizeHtml($text('blog_intro_title', '博客简介')) ?></h2>
            </div>
            <p class="text-[15px] leading-relaxed text-gray-600 dark:text-gray-300">
                <?= nl2br(htmlspecialchars($blogIntro)) ?>
            </p>
            <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-xs">
                <div class="text-gray-400"><?= sanitizeHtml($text('editable_hint', '可通过后台管理面板 > 内容管理编辑')) ?></div>
                <a href="admin/content.php" class="text-indigo-600 hover:text-indigo-500 flex items-center gap-x-1 text-xs font-medium">
                    <?= sanitizeHtml($text('edit', '编辑')) ?> <i class="fa-solid fa-external-link-alt text-[10px]"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Entry Cards -->
    <div class="mb-8">
        <h2 class="text-center text-2xl font-semibold tracking-tight mb-8">
            <?= htmlspecialchars($homepage['explore_title'] ?? '探索我的世界') ?>
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
            <!-- About Card -->
            <a href="about.php" class="group card bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl p-7 flex flex-col hover:border-indigo-200 dark:hover:border-indigo-800">
                <div class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-900 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-user text-blue-600 dark:text-blue-400 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-2xl tracking-tight mb-2">
                    <?= htmlspecialchars($homepage['card_about_title'] ?? '关于我') ?>
                </h3>
                <p class="text-gray-600 dark:text-gray-400 text-[15px] flex-1">
                    <?= htmlspecialchars($homepage['card_about_desc'] ?? '我的成长时间线、技能树和人生旅程。了解我是如何走到今天的。') ?>
                </p>
                <div class="mt-6 flex items-center text-sm font-medium text-blue-600 dark:text-blue-400 group-hover:gap-x-2 transition-all">
                    <span><?= sanitizeHtml($text('view_my_story', '了解我的故事')) ?></span>
                    <i class="fa-solid fa-arrow-right-long ml-1 group-hover:ml-2 transition-all"></i>
                </div>
            </a>
            
            <!-- Diary Card -->
            <a href="diary.php" class="group card bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl p-7 flex flex-col hover:border-emerald-200 dark:hover:border-emerald-800">
                <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-900 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-book text-emerald-600 dark:text-emerald-400 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-2xl tracking-tight mb-2">
                    <?= htmlspecialchars($homepage['card_diary_title'] ?? '日记') ?>
                </h3>
                <p class="text-gray-600 dark:text-gray-400 text-[15px] flex-1">
                    <?= htmlspecialchars($homepage['card_diary_desc'] ?? '生活故事、技术心得与旅行笔记。支持富文本与图片。') ?>
                </p>
                <div class="mt-6 flex items-center text-sm font-medium text-emerald-600 dark:text-emerald-400 group-hover:gap-x-2 transition-all">
                    <span><?= sanitizeHtml($text('browse_all_entries', '浏览所有日记')) ?></span>
                    <i class="fa-solid fa-arrow-right-long ml-1 group-hover:ml-2 transition-all"></i>
                </div>
            </a>
            
            <!-- Guestbook Card -->
            <a href="guestbook.php" class="group card bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl p-7 flex flex-col hover:border-violet-200 dark:hover:border-violet-800">
                <div class="w-12 h-12 rounded-2xl bg-violet-100 dark:bg-violet-900 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-comments text-violet-600 dark:text-violet-400 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-2xl tracking-tight mb-2">
                    <?= htmlspecialchars($homepage['card_guestbook_title'] ?? '留言板') ?>
                </h3>
                <p class="text-gray-600 dark:text-gray-400 text-[15px] flex-1">
                    <?= htmlspecialchars($homepage['card_guestbook_desc'] ?? '留下你的足迹，点赞与回复。所有留言永久保存。') ?>
                </p>
                <div class="mt-6 flex items-center text-sm font-medium text-violet-600 dark:text-violet-400 group-hover:gap-x-2 transition-all">
                    <span><?= sanitizeHtml($text('leave_a_message', '留言')) ?></span>
                    <i class="fa-solid fa-arrow-right-long ml-1 group-hover:ml-2 transition-all"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Tags & Search Cards -->
    <div class="mt-12">
        <h2 class="text-center text-2xl font-semibold tracking-tight mb-8">
            <?= sanitizeHtml($text('discover_more', '发现更多')) ?>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl mx-auto">
            <!-- Tags Cloud Card -->
            <a href="tags.php" class="group card bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl p-7 flex flex-col hover:border-pink-200 dark:hover:border-pink-800 text-center">
                <div class="w-12 h-12 rounded-2xl bg-pink-100 dark:bg-pink-900 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform mx-auto">
                    <i class="fa-solid fa-tags text-pink-600 dark:text-pink-400 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-2xl tracking-tight mb-2">
                    <?= sanitizeHtml($text('tags_cloud', '标签云')) ?>
                </h3>
                <p class="text-gray-600 dark:text-gray-400 text-[15px] flex-1">
                    <?= sanitizeHtml($text('tags_cloud_desc', '探索所有话题，按标签浏览日记。')) ?>
                </p>
                <div class="mt-6 flex items-center justify-center text-sm font-medium text-pink-600 dark:text-pink-400 group-hover:gap-x-2 transition-all">
                    <span><?= sanitizeHtml($text('browse_tags', '浏览标签')) ?></span>
                    <i class="fa-solid fa-arrow-right-long ml-1 group-hover:ml-2 transition-all"></i>
                </div>
            </a>

            <!-- Search Card -->
            <a href="search.php" class="group card bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl p-7 flex flex-col hover:border-indigo-200 dark:hover:border-indigo-800 text-center">
                <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform mx-auto">
                    <i class="fa-solid fa-magnifying-glass text-indigo-600 dark:text-indigo-400 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-2xl tracking-tight mb-2">
                    <?= sanitizeHtml($text('search', '搜索')) ?>
                </h3>
                <p class="text-gray-600 dark:text-gray-400 text-[15px] flex-1">
                    <?= sanitizeHtml($text('search_desc', '通过关键词或标签搜索所有日记。')) ?>
                </p>
                <div class="mt-6 flex items-center justify-center text-sm font-medium text-indigo-600 dark:text-indigo-400 group-hover:gap-x-2 transition-all">
                    <span><?= sanitizeHtml($text('start_searching', '开始搜索')) ?></span>
                    <i class="fa-solid fa-arrow-right-long ml-1 group-hover:ml-2 transition-all"></i>
                </div>
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
