<?php
$page_title = '关于我';
include __DIR__ . '/includes/header.php';

$about = $content['about'] ?? ['intro' => '', 'timeline' => []];
$skills = $content['skills'] ?? [];
$contact = $content['contact'] ?? [];

// 多语言支持：使用独立变量 $pageI18n，避免覆盖 header.php 中定义的 $i18n（完整语言块），
// 否则会导致 footer.php 中 $i18n['footer'] 失效。
$pageI18n = $i18n['about'] ?? ($content['i18n']['en']['about'] ?? []);

$text = function(string $key, string $fallback) use ($pageI18n): string {
    return $pageI18n[$key] ?? $fallback;
};
?>

<div class="max-w-4xl mx-auto px-6 py-12">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="inline-block px-5 py-1.5 rounded-3xl bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 text-xs font-semibold tracking-[1.5px] mb-3"><?= sanitizeHtml($text('my_story_label', '我的故事')) ?></div>
            <h1 class="text-5xl font-bold tracking-tighter"><?= sanitizeHtml($text('about_title', '关于我')) ?></h1>
            <p class="mt-3 text-xl text-gray-500 dark:text-gray-400"><?= sanitizeHtml($text('about_subtitle', '我的成长旅程与生活哲学')) ?></p>
        </div>
        
        <!-- Intro -->
        <div class="prose prose-lg dark:prose-invert max-w-none mb-14 text-[15.5px] leading-relaxed text-gray-700 dark:text-gray-300">
            <?= nl2br(htmlspecialchars($about['intro'] ?? $text('welcome_intro', '欢迎了解我的故事。'))) ?>
        </div>
        
        <!-- Timeline -->
        <div class="mb-16">
            <div class="flex items-center gap-x-3 mb-8">
                <i class="fa-solid fa-clock-rotate-left text-2xl text-gray-400"></i>
                <h2 class="text-3xl font-semibold tracking-tight"><?= sanitizeHtml($text('timeline_title', '时间线')) ?></h2>
            </div>
            
            <div class="space-y-8 pl-2">
                <?php if (!empty($about['timeline'])): ?>
                    <?php foreach ($about['timeline'] as $item): ?>
                        <div class="timeline-item flex gap-x-6">
                            <div class="flex-shrink-0 w-9 h-9 mt-1 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white text-sm font-bold shadow-inner">
                                <?= htmlspecialchars(substr($item['year'] ?? '20', 2)) ?>
                            </div>
                            <div class="flex-1 pt-1">
                                <div class="font-semibold text-xl tracking-tight mb-1.5"><?= htmlspecialchars($item['title'] ?? '') ?></div>
                                <div class="text-gray-600 dark:text-gray-400 leading-relaxed">
                                    <?= nl2br(htmlspecialchars($item['description'] ?? '')) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500"><?= sanitizeHtml($text('no_timeline', '暂无时间线数据，请在后台添加。')) ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Skills -->
        <div class="mb-16">
            <div class="flex items-center gap-x-3 mb-6">
                <i class="fa-solid fa-cogs text-2xl text-gray-400"></i>
                <h2 class="text-3xl font-semibold tracking-tight"><?= sanitizeHtml($text('skills_title', '技能')) ?></h2>
            </div>
            
            <div class="flex flex-wrap gap-3">
                <?php foreach ($skills as $skill): ?>
                    <div class="tag px-5 py-2 text-sm font-medium">
                        <?= htmlspecialchars($skill) ?>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($skills)): ?>
                    <p class="text-gray-400"><?= sanitizeHtml($text('no_skills', '暂无技能数据')) ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Contact -->
        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl p-8">
            <h2 class="text-2xl font-semibold tracking-tight mb-6 flex items-center gap-x-3">
                <i class="fa-solid fa-handshake text-indigo-500"></i>
                <span><?= sanitizeHtml($text('contact_title', '联系方式')) ?></span>
            </h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 text-sm">
                <?php if (!empty($contact['email'])): ?>
                <a href="mailto:<?= htmlspecialchars($contact['email']) ?>" class="flex items-center gap-x-3 p-4 rounded-2xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group">
                    <div class="w-10 h-10 rounded-2xl bg-red-100 dark:bg-red-900 flex-shrink-0 flex items-center justify-center">
                        <i class="fa-solid fa-envelope text-red-500"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs text-gray-500"><?= sanitizeHtml($text('email_label', '邮箱')) ?></div>
                        <div class="font-medium truncate group-hover:text-indigo-600"><?= htmlspecialchars($contact['email']) ?></div>
                    </div>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($contact['wechat'])): ?>
                <div class="flex items-center gap-x-3 p-4 rounded-2xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <div class="w-10 h-10 rounded-2xl bg-green-100 dark:bg-green-900 flex-shrink-0 flex items-center justify-center">
                        <i class="fa-brands fa-weixin text-green-600"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs text-gray-500"><?= sanitizeHtml($text('wechat_label', '微信')) ?></div>
                        <div class="font-medium"><?= htmlspecialchars($contact['wechat']) ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($contact['github'])): ?>
                <a href="<?= htmlspecialchars($contact['github']) ?>" target="_blank" class="flex items-center gap-x-3 p-4 rounded-2xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group">
                    <div class="w-10 h-10 rounded-2xl bg-gray-800 dark:bg-gray-700 flex-shrink-0 flex items-center justify-center">
                        <i class="fa-brands fa-github text-white"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs text-gray-500"><?= sanitizeHtml($text('github_label', 'GitHub')) ?></div>
                        <div class="font-medium truncate group-hover:text-indigo-600"><?= sanitizeHtml($text('view_projects', '查看项目')) ?></div>
                    </div>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
