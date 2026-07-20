<?php
// 先加载安全基础设施，因为下方在 include header.php 之前就需要调用 readJsonFile()
require_once __DIR__ . '/includes/security.php';
$content = readJsonFile(__DIR__ . '/data/content.json');
$settings = $content['settings'] ?? [];
$lang = $settings['language'] ?? 'zh';
$i18nData = $content['i18n'] ?? [];
$i18n = $i18nData[$lang] ?? $i18nData['en'] ?? [];
$page_title = $i18n['index']['search'] ?? '搜索';
include __DIR__ . '/includes/header.php';

$diaries = readJsonFile(__DIR__ . '/data/diaries.json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';

$results = [];
if ($query !== '' || $tag !== '') {
    foreach ($diaries as $diary) {
        // 前台不显示草稿
        if (!empty($diary['status']) && $diary['status'] === 'draft') {
            continue;
        }

        $match = false;

        if ($tag !== '') {
            // 标签筛选
            if (!empty($diary['tags']) && is_array($diary['tags'])) {
                foreach ($diary['tags'] as $t) {
                    if (strcasecmp(trim($t), $tag) === 0) {
                        $match = true;
                        break;
                    }
                }
            }
        } else {
            // 关键词搜索：标题、内容、标签
            $searchText = strtolower($query);
            $title = strtolower($diary['title'] ?? '');
            $searchContent = strtolower(strip_tags($diary['content'] ?? ''));
            $tagsStr = '';
            if (!empty($diary['tags']) && is_array($diary['tags'])) {
                $tagsStr = strtolower(implode(' ', $diary['tags']));
            }

            if (strpos($title, $searchText) !== false
                || strpos($searchContent, $searchText) !== false
                || strpos($tagsStr, $searchText) !== false) {
                $match = true;
            }
        }

        if ($match) {
            $results[] = $diary;
        }
    }

    // 排序：pinned 优先，然后按日期倒序
    usort($results, function($a, $b) {
        $aPinned = !empty($a['pinned']) ? 1 : 0;
        $bPinned = !empty($b['pinned']) ? 1 : 0;
        if ($aPinned !== $bPinned) {
            return $bPinned <=> $aPinned;
        }
        return strcmp($b['date'] ?? '', $a['date'] ?? '');
    });
}

function highlightKeyword(string $text, string $keyword): string {
    if ($keyword === '') return $text;
    return preg_replace('/(' . preg_quote($keyword, '/') . ')/iu', '<mark class="bg-yellow-200 dark:bg-yellow-700 px-0.5 rounded">$1</mark>', sanitizeHtml($text));
}

function getExcerpt(string $content, string $keyword, int $length = 160): string {
    $plain = strip_tags($content);
    if ($keyword === '') {
        return mb_substr($plain, 0, $length) . (mb_strlen($plain) > $length ? '...' : '');
    }
    // 尝试找到关键词位置，截取周围文本
    $pos = mb_stripos($plain, $keyword);
    if ($pos === false) {
        return mb_substr($plain, 0, $length) . (mb_strlen($plain) > $length ? '...' : '');
    }
    $start = max(0, $pos - (int)($length / 2));
    $prefix = $start > 0 ? '...' : '';
    $snippet = mb_substr($plain, $start, $length);
    $suffix = (mb_strlen($plain) > $start + $length) ? '...' : '';
    return htmlspecialchars($prefix . $snippet . $suffix, ENT_QUOTES, 'UTF-8');
}
?>

<div class="max-w-5xl mx-auto px-6 py-12">
    <div class="mb-10">
        <div class="text-xs tracking-[2px] text-indigo-600 dark:text-indigo-400 font-semibold mb-1"><?= strtoupper($i18n['index']['search'] ?? '搜索') ?></div>
        <h1 class="text-5xl font-bold tracking-tighter mb-6"><?= ($i18n['index']['search'] ?? '搜索') . ($i18n['diary']['title'] ?? '日记') ?></h1>

        <form action="search.php" method="GET" class="max-w-xl">
            <div class="relative">
                <input type="text" name="q"
                       value="<?= sanitizeHtml($query) ?>"
                       class="w-full pl-12 pr-24 py-4 rounded-2xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 focus:outline-none focus:border-indigo-400 transition text-base"
                       placeholder="<?= $i18n['search']['placeholder'] ?? '标题、内容或标签...' ?>">
                <i class="fa-solid fa-search absolute left-4 top-4.5 text-gray-400 text-lg"></i>
                <button type="submit"
                        class="absolute right-2 top-2 px-5 h-10 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium transition-colors">
                    <?= $i18n['index']['search'] ?? '搜索' ?>
                </button>
            </div>
        </form>

        <?php if ($tag !== ''): ?>
            <div class="mt-4 flex items-center gap-x-2">
                <span class="text-sm text-gray-500 dark:text-gray-400"><?= $i18n['search']['filter_by_tag'] ?? '按标签筛选：' ?></span>
                <span class="inline-flex items-center gap-x-1 px-3 py-1 rounded-xl bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 text-sm font-medium">
                    <?= sanitizeHtml($tag) ?>
                    <a href="search.php" class="hover:text-indigo-900 dark:hover:text-indigo-100 ml-1">
                        <i class="fa-solid fa-times text-xs"></i>
                    </a>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($query === '' && $tag === ''): ?>
        <div class="text-center py-16 text-gray-400">
            <i class="fa-solid fa-magnifying-glass text-5xl mb-4 opacity-40"></i>
            <p><?= $i18n['search']['start_typing'] ?? '输入关键词或选择标签开始搜索。' ?></p>
        </div>
    <?php elseif (empty($results)): ?>
        <div class="text-center py-16 text-gray-400">
            <i class="fa-solid fa-circle-xmark text-5xl mb-4 opacity-40"></i>
            <p class="text-lg mb-2"><?= $i18n['search']['no_results'] ?? '未找到相关结果' ?></p>
            <p class="text-sm"><?= $i18n['search']['try_other_keywords'] ?? '尝试其他关键词或浏览所有' ?><a href="tags.php" class="text-indigo-500 hover:underline"><?= $i18n['diary']['tags'] ?? '标签' ?></a>。</p>
        </div>
    <?php else: ?>
        <div class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            <?= sprintf($i18n['search']['results_found'] ?? '找到 %d 个结果', count($results)) ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($results as $diary): ?>
                <a href="diary-detail.php?id=<?= (int)$diary['id'] ?>"
                   class="group card bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl p-7 flex flex-col h-full">

                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-x-2">
                            <div class="text-xs px-3 py-1 rounded-2xl bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300 font-medium">
                                <?= sanitizeHtml($diary['date']) ?>
                            </div>
                            <?php if (!empty($diary['pinned'])): ?>
                                <i class="fa-solid fa-thumbtack text-amber-500 text-xs" title="置顶"></i>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($diary['tags'])): ?>
                            <div class="flex gap-x-1.5">
                                <?php foreach (array_slice($diary['tags'], 0, 2) as $t): ?>
                                    <span class="text-[10px] px-2.5 py-px rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400"><?= sanitizeHtml($t) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <h3 class="font-semibold text-2xl tracking-tight leading-tight mb-3 group-hover:text-indigo-600 transition-colors">
                        <?php if ($query !== ''): ?>
                            <?= highlightKeyword($diary['title'], $query) ?>
                        <?php else: ?>
                            <?= sanitizeHtml($diary['title']) ?>
                        <?php endif; ?>
                    </h3>

                    <p class="text-gray-600 dark:text-gray-400 text-[15px] line-clamp-3 flex-1">
                        <?= getExcerpt($diary['content'] ?? '', $query) ?>
                    </p>

                    <div class="mt-auto pt-6 flex items-center text-sm text-emerald-600 dark:text-emerald-400 font-medium">
                        <?= $i18n['diary']['read_more'] ?? '阅读更多' ?>
                        <i class="fa-solid fa-arrow-right-long ml-2 group-hover:ml-3 transition-all"></i>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="mt-10 flex justify-center">
        <a href="diary.php" class="inline-flex items-center text-sm px-5 py-2.5 rounded-2xl border border-dashed border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-500 dark:text-gray-400 transition-colors">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            <span><?= $i18n['diary']['back_to_list'] ?? '返回日记' ?></span>
        </a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
