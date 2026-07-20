<?php
require_once __DIR__ . '/includes/security.php';
$content = readJsonFile(__DIR__ . '/data/content.json');
$settings = $content['settings'] ?? [];
$lang = $settings['language'] ?? 'zh';
$i18nData = $content['i18n'] ?? [];
$i18n = $i18nData[$lang] ?? $i18nData['en'] ?? [];
$archiveI18n = $i18n['archive'] ?? [];
$page_title = $archiveI18n['title'] ?? '归档';
include __DIR__ . '/includes/header.php';

$diaries = readJsonFile(__DIR__ . '/data/diaries.json');

// 过滤草稿，只显示已发布
$diaries = array_filter($diaries, fn($d) => empty($d['status']) || $d['status'] === 'published');

// 按日期倒序排序
usort($diaries, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

// 按年-月分组
$grouped = [];
foreach ($diaries as $diary) {
    $date = $diary['date'] ?? '';
    if (empty($date)) continue;
    $year = substr($date, 0, 4);
    $month = substr($date, 5, 2);
    if (!isset($grouped[$year])) {
        $grouped[$year] = [];
    }
    if (!isset($grouped[$year][$month])) {
        $grouped[$year][$month] = [];
    }
    $grouped[$year][$month][] = $diary;
}

$monthNames = [
    '01' => '一月', '02' => '二月', '03' => '三月', '04' => '四月',
    '05' => '五月', '06' => '六月', '07' => '七月', '08' => '八月',
    '09' => '九月', '10' => '十月', '11' => '十一月', '12' => '十二月',
];
$monthNamesEn = [
    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December',
];

$totalCount = count($diaries);
?>

<div class="max-w-4xl mx-auto px-6 py-12">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-10">
        <div>
            <div class="text-xs tracking-[2px] text-emerald-600 dark:text-emerald-400 font-semibold mb-1">ARCHIVE</div>
            <h1 class="text-5xl font-bold tracking-tighter"><?= sanitizeHtml($archiveI18n['title'] ?? '归档') ?></h1>
        </div>
        <div class="mt-4 md:mt-0 text-sm text-gray-500 dark:text-gray-400">
            <?= $totalCount ?> <?= sanitizeHtml($archiveI18n['entries_count'] ?? '篇日记') ?>
        </div>
    </div>

    <?php if (empty($grouped)): ?>
        <div class="text-center py-16 text-gray-400">
            <i class="fa-solid fa-box-archive text-5xl mb-4 opacity-40"></i>
            <p>暂无归档内容。</p>
        </div>
    <?php else: ?>
        <div class="space-y-10">
            <?php foreach ($grouped as $year => $months): ?>
                <div>
                    <h2 class="text-2xl font-bold tracking-tight mb-6 flex items-center gap-x-3">
                        <span class="w-1.5 h-8 bg-indigo-500 rounded-full"></span>
                        <?= sanitizeHtml($year) ?>
                        <span class="text-sm font-normal text-gray-400 dark:text-gray-500">
                            <?= array_sum(array_map('count', $months)) ?> <?= sanitizeHtml($archiveI18n['entries_count'] ?? '篇日记') ?>
                        </span>
                    </h2>
                    <div class="space-y-6 ml-4">
                        <?php foreach ($months as $month => $entries): ?>
                            <div class="border-l-2 border-gray-200 dark:border-gray-700 pl-6">
                                <h3 class="text-lg font-semibold mb-3 text-gray-700 dark:text-gray-300">
                                    <?= sanitizeHtml(($lang === 'zh' ? ($monthNames[$month] ?? $month) : ($monthNamesEn[$month] ?? $month))) ?>
                                    <span class="text-sm font-normal text-gray-400 dark:text-gray-500 ml-2">(<?= count($entries) ?>)</span>
                                </h3>
                                <div class="space-y-2">
                                    <?php foreach ($entries as $entry): ?>
                                        <a href="diary-detail.php?id=<?= (int)$entry['id'] ?>" class="group flex items-center gap-x-3 py-2 px-3 rounded-xl hover:bg-white dark:hover:bg-gray-800 hover:shadow-sm transition-all">
                                            <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0 w-14"><?= sanitizeHtml($entry['date'] ?? '') ?></span>
                                            <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors truncate">
                                                <?= sanitizeHtml($entry['title'] ?? '') ?>
                                            </span>
                                            <?php if (!empty($entry['tags'])): ?>
                                                <span class="hidden sm:inline-flex text-[10px] px-2 py-px rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 flex-shrink-0">
                                                    <?= sanitizeHtml($entry['tags'][0]) ?>
                                                </span>
                                            <?php endif; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="mt-16 text-center">
        <a href="index.php" class="inline-flex items-center text-sm px-5 py-2.5 rounded-2xl border border-dashed border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-500 dark:text-gray-400">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            <span><?= sanitizeHtml($archiveI18n['back_to_home'] ?? '返回首页') ?></span>
        </a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
