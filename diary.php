<?php
require_once __DIR__ . '/includes/security.php';
$content = readJsonFile(__DIR__ . '/data/content.json');
$settings = $content['settings'] ?? [];
$lang = $settings['language'] ?? 'zh';
$i18nData = $content['i18n'] ?? [];
$i18n = $i18nData[$lang] ?? $i18nData['en'] ?? [];
$page_title = $i18n['diary']['title'] ?? '日记';
include __DIR__ . '/includes/header.php';

$diaries = readJsonFile(__DIR__ . '/data/diaries.json');
$categories = readJsonFile(__DIR__ . '/data/categories.json');
$isAdmin = !empty($_SESSION['admin_logged_in']);

// 过滤草稿和未到达发布时间的日记（非管理员）
$diaries = array_filter($diaries, function($d) use ($isAdmin) {
    if (($d['status'] ?? 'published') === 'draft' && !$isAdmin) return false;
    $pubAt = $d['publish_at'] ?? '';
    if ($pubAt !== '' && strtotime($pubAt) > time() && !$isAdmin) return false;
    return true;
});

// 按分类筛选
$activeCategory = $_GET['category'] ?? '';
if ($activeCategory !== '') {
    $diaries = array_filter($diaries, fn($d) => ($d['category'] ?? '') === $activeCategory);
}

// Build category slug -> name/color map
$categoryMap = [];
foreach ($categories as $cat) {
    if (is_array($cat)) {
        $categoryMap[$cat['slug'] ?? ''] = [
            'name' => $cat['name'] ?? '',
            'color' => $cat['color'] ?? 'blue',
        ];
    }
}

// 排序：pinned 优先，然后按日期倒序
usort($diaries, function($a, $b) {
    $aPinned = !empty($a['pinned']) ? 1 : 0;
    $bPinned = !empty($b['pinned']) ? 1 : 0;
    if ($aPinned !== $bPinned) {
        return $bPinned <=> $aPinned;
    }
    return strcmp($b['date'] ?? '', $a['date'] ?? '');
});

function diaryListUrl(array $d): string {
    if (!empty($d['slug'])) {
        return 'diary-detail.php?slug=' . urlencode($d['slug']);
    }
    return 'diary-detail.php?id=' . (int)$d['id'];
}
?>

<div class="max-w-5xl mx-auto px-6 py-12">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-10">
        <div>
            <div class="text-xs tracking-[2px] text-emerald-600 dark:text-emerald-400 font-semibold mb-1"><?= strtoupper($i18n['diary']['title'] ?? '日记') ?></div>
            <h1 class="text-5xl font-bold tracking-tighter"><?= $i18n['diary']['subtitle'] ?? '记录生活、技术与思考' ?></h1>
        </div>
        <div class="mt-4 md:mt-0 text-sm text-gray-500 dark:text-gray-400">
            <?= count($diaries) ?> 篇<?= $i18n['diary']['title'] ?? '日记' ?> &middot; <?= $i18n['diary']['life_and_thoughts'] ?? '生活与思考' ?>
        </div>
    </div>

    <!-- Category Filter & Search -->
    <div class="mb-8 flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
            <!-- Category Filter -->
            <div class="relative">
                <select onchange="if(this.value){window.location.href='diary.php?category='+encodeURIComponent(this.value);}else{window.location.href='diary.php';}"
                        class="appearance-none w-full sm:w-44 pl-10 pr-8 py-3 rounded-2xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 focus:outline-none focus:border-emerald-400 transition text-sm cursor-pointer">
                    <option value="" <?= $activeCategory === '' ? 'selected' : '' ?>><?= $i18n['diary']['all_categories'] ?? '全部分类' ?></option>
                    <?php foreach ($categories as $cat): ?>
                        <?php $catSlug = is_array($cat) ? ($cat['slug'] ?? '') : $cat; $catName = is_array($cat) ? ($cat['name'] ?? $catSlug) : $cat; ?>
                        <option value="<?= htmlspecialchars($catSlug) ?>" <?= $activeCategory === $catSlug ? 'selected' : '' ?>>
                            <?= sanitizeHtml($catName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <i class="fa-solid fa-layer-group absolute left-4 top-3.5 text-gray-400"></i>
                <i class="fa-solid fa-chevron-down absolute right-3 top-3.5 text-gray-400 text-xs pointer-events-none"></i>
            </div>

            <form action="search.php" method="GET" class="w-full sm:w-auto sm:min-w-[280px]">
                <div class="relative">
                    <input type="text" name="q"
                           class="w-full pl-10 pr-4 py-3 rounded-2xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 focus:outline-none focus:border-emerald-400 transition"
                           placeholder="<?= $i18n['diary']['placeholder_search'] ?? '标题或内容...' ?>">
                    <i class="fa-solid fa-search absolute left-4 top-3.5 text-gray-400"></i>
                </div>
            </form>
        </div>
        <a href="tags.php"
           class="inline-flex items-center gap-x-2 px-4 py-3 rounded-2xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <i class="fa-solid fa-tags text-indigo-500"></i>
            <span><?= $i18n['index']['browse_tags'] ?? '浏览标签' ?></span>
        </a>
    </div>

    <?php if (empty($diaries)): ?>
        <div class="text-center py-16 text-gray-400">
            <i class="fa-solid fa-book text-5xl mb-4 opacity-40"></i>
            <p><?= $i18n['diary']['no_diary_yet'] ?? '暂无日记。在后台面板发布你的第一篇日记。' ?></p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="diary-grid">
            <?php foreach ($diaries as $diary): ?>
                <a href="<?= diaryListUrl($diary) ?>"
                   class="group card bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl flex flex-col md:flex-row overflow-hidden h-full">

                    <?php if (!empty($diary['cover_image'])): ?>
                        <div class="w-full md:w-28 h-40 md:h-auto flex-shrink-0">
                            <img src="<?= htmlspecialchars($diary['cover_image']) ?>" alt="<?= htmlspecialchars($diary['title']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </div>
                    <?php endif; ?>

                    <div class="p-7 flex flex-col flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-x-2 flex-wrap gap-y-1">
                                <div class="text-xs px-3 py-1 rounded-2xl bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300 font-medium">
                                    <?= sanitizeHtml($diary['date']) ?>
                                </div>
                                <?php if (!empty($diary['pinned'])): ?>
                                    <i class="fa-solid fa-thumbtack text-amber-500 text-xs" title="置顶"></i>
                                <?php endif; ?>
                                <?php if (!empty($diary['category'])): ?>
                                    <?php $catInfo = $categoryMap[$diary['category']] ?? null; $catDisplayName = $catInfo['name'] ?? $diary['category']; ?>
                                    <span class="text-[10px] px-2.5 py-px rounded-xl font-medium bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300">
                                        <?= sanitizeHtml($catDisplayName) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($diary['tags'])): ?>
                                <div class="flex gap-x-1.5 flex-shrink-0">
                                    <?php foreach (array_slice($diary['tags'], 0, 2) as $tag): ?>
                                        <span class="text-[10px] px-2.5 py-px rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors cursor-pointer"
                                              onclick="event.preventDefault(); event.stopPropagation(); window.location.href='search.php?tag=<?= urlencode($tag) ?>';">
                                            <?= sanitizeHtml($tag) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h3 class="font-semibold text-xl md:text-2xl tracking-tight leading-tight mb-3 group-hover:text-indigo-600 transition-colors">
                            <?= htmlspecialchars($diary['title']) ?>
                        </h3>

                        <p class="text-gray-600 dark:text-gray-400 text-[15px] line-clamp-3 flex-1">
                            <?= htmlspecialchars($diary['excerpt'] ?? mb_substr(strip_tags($diary['content'] ?? ''), 0, 120) . '...') ?>
                        </p>

                        <div class="mt-auto pt-6 flex items-center justify-between">
                            <div class="flex items-center text-sm text-emerald-600 dark:text-emerald-400 font-medium">
                                <?= $i18n['diary']['read_more'] ?? '阅读更多' ?>
                                <i class="fa-solid fa-arrow-right-long ml-2 group-hover:ml-3 transition-all"></i>
                            </div>
                            <?php if (isset($diary['views'])): ?>
                            <div class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-x-1">
                                <i class="fa-solid fa-eye"></i>
                                <?= (int)$diary['views'] ?> <?= sanitizeHtml($i18n['diary']['views_count'] ?? '浏览') ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="text-center mt-12">
        <a href="admin/content.php" class="inline-flex items-center text-sm px-5 py-2.5 rounded-2xl border border-dashed border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-500 dark:text-gray-400">
            <i class="fa-solid fa-plus mr-2"></i> 
            <span><?= $i18n['guestbook']['manage_content'] ?? '管理内容' ?></span>
        </a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
