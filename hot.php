<?php
require_once __DIR__ . '/includes/security.php';
$content = readJsonFile(__DIR__ . '/data/content.json');
$settings = $content['settings'] ?? [];
$lang = $settings['language'] ?? 'zh';
$i18nData = $content['i18n'] ?? [];
$i18n = $i18nData[$lang] ?? $i18nData['en'] ?? [];
$hotI18n = $i18n['hot'] ?? [];
$page_title = $hotI18n['title'] ?? '热门';
include __DIR__ . '/includes/header.php';

$diaries = readJsonFile(__DIR__ . '/data/diaries.json');

// 过滤草稿，只显示已发布
$diaries = array_filter($diaries, fn($d) => empty($d['status']) || $d['status'] === 'published');

// 按阅读量倒序排序（views 不存在时默认为 0）
usort($diaries, fn($a, $b) => ($b['views'] ?? 0) <=> ($a['views'] ?? 0));

// 取前 20
$topDiaries = array_slice($diaries, 0, 20);
?>

<div class="max-w-4xl mx-auto px-6 py-12">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-10">
        <div>
            <div class="text-xs tracking-[2px] text-rose-600 dark:text-rose-400 font-semibold mb-1">HOT</div>
            <h1 class="text-5xl font-bold tracking-tighter"><?= sanitizeHtml($hotI18n['title'] ?? '热门') ?></h1>
        </div>
        <div class="mt-4 md:mt-0 text-sm text-gray-500 dark:text-gray-400">
            <?= sanitizeHtml($hotI18n['subtitle'] ?? '阅读量最高的日记') ?>
        </div>
    </div>

    <?php if (empty($topDiaries)): ?>
        <div class="text-center py-16 text-gray-400">
            <i class="fa-solid fa-fire text-5xl mb-4 opacity-40"></i>
            <p>暂无热门内容。</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($topDiaries as $index => $diary): ?>
                <?php
                $rank = $index + 1;
                $views = $diary['views'] ?? 0;
                $rankClass = '';
                $rankIcon = '';
                if ($rank === 1) {
                    $rankClass = 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800';
                } elseif ($rank === 2) {
                    $rankClass = 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700';
                } elseif ($rank === 3) {
                    $rankClass = 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-800';
                } else {
                    $rankClass = 'bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 border-gray-100 dark:border-gray-700/50';
                }
                ?>
                <a href="diary-detail.php?id=<?= (int)$diary['id'] ?>" class="group flex items-center gap-x-4 py-3 px-4 rounded-2xl border border-gray-100 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-800 hover:shadow-sm transition-all">
                    <div class="w-10 h-10 flex-shrink-0 flex items-center justify-center rounded-xl border <?= $rankClass ?> font-bold text-sm">
                        <?php if ($rank <= 3): ?>
                            <i class="fa-solid fa-trophy"></i>
                        <?php else: ?>
                            <?= $rank ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors truncate">
                            <?= sanitizeHtml($diary['title'] ?? '') ?>
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                            <?= sanitizeHtml($diary['date'] ?? '') ?>
                            <?php if (!empty($diary['tags'])): ?>
                                <span class="mx-1">·</span>
                                <span><?= sanitizeHtml(implode(', ', array_slice($diary['tags'], 0, 3))) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-x-1.5 text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">
                        <i class="fa-regular fa-eye"></i>
                        <span><?= number_format($views) ?></span>
                        <span class="hidden sm:inline"><?= sanitizeHtml($hotI18n['views'] ?? '阅读') ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="mt-16 text-center">
        <a href="index.php" class="inline-flex items-center text-sm px-5 py-2.5 rounded-2xl border border-dashed border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-500 dark:text-gray-400">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            <span><?= sanitizeHtml($hotI18n['back_to_home'] ?? '返回首页') ?></span>
        </a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
