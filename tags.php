<?php
// 先加载安全基础设施，因为下方在 include header.php 之前就需要调用 readJsonFile()
require_once __DIR__ . '/includes/security.php';
$content = readJsonFile(__DIR__ . '/data/content.json');
$settings = $content['settings'] ?? [];
$lang = $settings['language'] ?? 'zh';
$i18nData = $content['i18n'] ?? [];
$i18n = $i18nData[$lang] ?? $i18nData['en'] ?? [];
$page_title = $i18n['diary']['tags'] ?? '标签';
include __DIR__ . '/includes/header.php';

$diaries = readJsonFile(__DIR__ . '/data/diaries.json');

// 统计标签频率
$tagCounts = [];
foreach ($diaries as $diary) {
    if (!empty($diary['status']) && $diary['status'] === 'draft') {
        continue;
    }
    if (!empty($diary['tags']) && is_array($diary['tags'])) {
        foreach ($diary['tags'] as $tag) {
            $tag = trim($tag);
            if ($tag !== '') {
                $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
            }
        }
    }
}

// 按频率排序
arsort($tagCounts);
$maxCount = empty($tagCounts) ? 1 : max($tagCounts);
$minCount = empty($tagCounts) ? 1 : min($tagCounts);

function getTagSizeClass($count, $max, $min) {
    if ($max === $min) return 'text-lg';
    $ratio = ($count - $min) / ($max - $min);
    if ($ratio >= 0.8) return 'text-3xl font-bold';
    if ($ratio >= 0.6) return 'text-2xl font-semibold';
    if ($ratio >= 0.4) return 'text-xl font-medium';
    if ($ratio >= 0.2) return 'text-base font-medium';
    return 'text-sm';
}

function getTagColorClass($count, $max, $min) {
    if ($max === $min) return 'text-indigo-600 dark:text-indigo-400';
    $ratio = ($count - $min) / ($max - $min);
    if ($ratio >= 0.8) return 'text-indigo-700 dark:text-indigo-300';
    if ($ratio >= 0.6) return 'text-violet-600 dark:text-violet-400';
    if ($ratio >= 0.4) return 'text-fuchsia-600 dark:text-fuchsia-400';
    if ($ratio >= 0.2) return 'text-pink-500 dark:text-pink-400';
    return 'text-gray-500 dark:text-gray-400';
}
?>

<div class="max-w-5xl mx-auto px-6 py-12">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-10">
        <div>
            <div class="text-xs tracking-[2px] text-indigo-600 dark:text-indigo-400 font-semibold mb-1"><?= strtoupper($i18n['diary']['tags'] ?? '标签') ?></div>
            <h1 class="text-5xl font-bold tracking-tighter"><?= $i18n['index']['tags_cloud'] ?? '所有标签' ?></h1>
        </div>
        <div class="mt-4 md:mt-0 text-sm text-gray-500 dark:text-gray-400">
            <?= count($tagCounts) ?> <?= $i18n['diary']['tags'] ?? '标签' ?> &middot; 总计 <?= array_sum($tagCounts) ?>
        </div>
    </div>

    <?php if (empty($tagCounts)): ?>
        <div class="text-center py-16 text-gray-400">
            <i class="fa-solid fa-tags text-5xl mb-4 opacity-40"></i>
            <p>暂无标签。在后台为日记添加标签。</p>
        </div>
    <?php else: ?>
        <div class="<?php if ($glassEnabled): ?>glass-card<?php else: ?>bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700<?php endif; ?> rounded-3xl p-8 md:p-12">
            <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-4">
                <?php foreach ($tagCounts as $tag => $count): ?>
                    <a href="search.php?tag=<?= urlencode($tag) ?>"
                       class="inline-block transition-all duration-200 hover:scale-110 hover:opacity-80 <?= getTagSizeClass($count, $maxCount, $minCount) ?> <?= getTagColorClass($count, $maxCount, $minCount) ?>">
                        <?= sanitizeHtml($tag) ?>
                        <span class="text-xs opacity-60 ml-0.5"><?= $count ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-10 flex justify-center">
        <a href="diary.php" class="inline-flex items-center text-sm px-5 py-2.5 rounded-2xl border border-dashed border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-500 transition-colors">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            <span><?= $i18n['diary']['back_to_list'] ?? '返回日记' ?></span>
        </a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
