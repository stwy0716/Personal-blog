<?php
// 必须在任何输出之前加载安全基础设施并启动会话
require_once __DIR__ . '/includes/security.php';
configureSecureSession();
sendSecurityHeaders();

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$diaries = readJsonFile(__DIR__ . '/data/diaries.json');

$diary = null;
if ($slug !== '') {
    foreach ($diaries as $d) {
        if (($d['slug'] ?? '') === $slug) {
            $diary = $d;
            $id = (int)$d['id'];
            break;
        }
    }
} else {
    foreach ($diaries as $d) {
        if ((int)$d['id'] === $id) {
            $diary = $d;
            break;
        }
    }
}

if (!$diary) {
    header('Location: diary.php');
    exit;
}

$isAdmin = !empty($_SESSION['admin_logged_in']);
$isDraft = !empty($diary['status']) && $diary['status'] === 'draft';
$publishAt = $diary['publish_at'] ?? '';
$isScheduled = $publishAt !== '' && strtotime($publishAt) > time();
$hasPassword = !empty($diary['password']);
$isUnlocked = !empty($_SESSION['diary_unlocked_' . $diary['id']]);
$passwordError = false;

// Handle password unlock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_diary'])) {
    verifyPostCsrf();
    $inputPassword = $_POST['diary_password'] ?? '';
    if ($hasPassword && password_verify($inputPassword, $diary['password'])) {
        $_SESSION['diary_unlocked_' . $diary['id']] = true;
        $isUnlocked = true;
        $redirectUrl = $slug !== '' ? 'diary-detail.php?slug=' . urlencode($slug) : 'diary-detail.php?id=' . $id;
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        $passwordError = true;
    }
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_comment'])) {
    verifyPostCsrf();
    $name = sanitizeString($_POST['name'] ?? '', 50);
    $commentContent = sanitizeRichText($_POST['comment_content'] ?? '');

    if ($name && $commentContent && $id > 0) {
        $commentsFile = __DIR__ . '/data/diary_comments.json';
        $comments = readJsonFile($commentsFile);

        $newComment = [
            'id' => time() . rand(100, 999),
            'diary_id' => $id,
            'parent_id' => 0,
            'name' => sanitizeHtml($name),
            'content' => $commentContent,
            'timestamp' => date('Y-m-d H:i'),
            'hidden' => false
        ];

        $comments[] = $newComment;
        writeJsonFile($commentsFile, $comments);

        $redirectUrl = $slug !== '' ? 'diary-detail.php?slug=' . urlencode($slug) : 'diary-detail.php?id=' . $id;
        header('Location: ' . $redirectUrl . '#comments');
        exit;
    }
}

// Views counter (non-admin only, once per session)
if (!$isAdmin && empty($_SESSION['diary_viewed_' . $diary['id']])) {
    atomicJsonUpdate(__DIR__ . '/data/diaries.json', function($diaries) use ($id) {
        foreach ($diaries as &$d) {
            if ((int)$d['id'] === $id) {
                $d['views'] = ($d['views'] ?? 0) + 1;
                break;
            }
        }
        return $diaries;
    });
    $_SESSION['diary_viewed_' . $diary['id']] = true;
}

// 查找上一篇和下一篇日记（同状态=published 且未定时，按 id 排序）
$prevDiary = null;
$nextDiary = null;
$publishedDiaries = array_filter($diaries, function($d) {
    if (($d['status'] ?? 'published') !== 'published') return false;
    $pubAt = $d['publish_at'] ?? '';
    if ($pubAt !== '' && strtotime($pubAt) > time()) return false;
    return true;
});
$publishedDiaries = array_values($publishedDiaries);
for ($i = 0; $i < count($publishedDiaries); $i++) {
    if ((int)$publishedDiaries[$i]['id'] === $id) {
        if ($i > 0) $prevDiary = $publishedDiaries[$i - 1];
        if ($i < count($publishedDiaries) - 1) $nextDiary = $publishedDiaries[$i + 1];
        break;
    }
}

function diaryUrl(array $d): string {
    if (!empty($d['slug'])) {
        return 'diary-detail.php?slug=' . urlencode($d['slug']);
    }
    return 'diary-detail.php?id=' . (int)$d['id'];
}

// 计算阅读时长（中文约 400字/分钟）
$plainContent = strip_tags($diary['content'] ?? '');
$charCount = mb_strlen($plainContent);
$readingTime = max(1, ceil($charCount / 400));
$readingLabel = $readingTime . ' ' . ($text('minutes', '分钟'));

// 判断是否包含代码块，决定是否加载 PrismJS
$hasCode = preg_match('/<pre\b|<code\b/i', $diary['content'] ?? '');

// 多语言支持
$contentData = readJsonFile(__DIR__ . '/data/content.json');
$settings = $contentData['settings'] ?? [];
$lang = $settings['language'] ?? 'zh';
$i18n = $contentData['i18n'][$lang]['diary']['diary_detail'] ?? $contentData['i18n']['en']['diary']['diary_detail'] ?? [];

$text = function(string $key, string $fallback) use ($i18n): string {
    return $i18n[$key] ?? $fallback;
};

// 先消毒原始内容
$rawContent = sanitizeRichText($diary['content'] ?? '<p>暂无内容</p>');

// 提取 TOC 并给 h2/h3 添加 id
$tocItems = [];
$tocCounter = 0;
$processedContent = preg_replace_callback('/<(h2|h3)([^>]*)>(.*?)<\/\1>/si', function($matches) use (&$tocItems, &$tocCounter) {
    $tag = $matches[1];
    $attrs = $matches[2];
    $title = strip_tags($matches[3]);
    $tocCounter++;
    $anchorId = 'toc-heading-' . $tocCounter;

    if (preg_match('/id=["\']([^"\']+)["\']/i', $attrs, $idMatch)) {
        $anchorId = $idMatch[1];
    } else {
        $attrs .= ' id="' . $anchorId . '"';
    }

    $tocItems[] = [
        'level' => $tag === 'h2' ? 2 : 3,
        'title' => $title,
        'anchor' => $anchorId
    ];

    return '<' . $tag . $attrs . '>' . $matches[3] . '</' . $tag . '>';
}, $rawContent);

// 图片懒加载：给所有 img 添加 loading="lazy"
$processedContent = preg_replace('/<img([^>]*)>/i', '<img$1 loading="lazy">', $processedContent);
$processedContent = preg_replace('/loading="lazy"\s+loading="lazy"/i', 'loading="lazy"', $processedContent);

// 给内容中的 img 添加点击放大样式类
$processedContent = preg_replace('/<img([^>]*)class=["\']([^"\']*)["\']([^>]*)>/i', '<img$1class="$2 cursor-zoom-in"$3>', $processedContent);
$processedContent = preg_replace('/<img(?![^>]*class=)([^>]*)>/i', '<img$1 class="cursor-zoom-in">', $processedContent);

// 代码块高亮：给 pre > code 添加 PrismJS 类
$processedContent = preg_replace_callback('/<pre([^>]*)><code([^>]*)>(.*?)<\/code><\/pre>/si', function($m) {
    $preAttrs = $m[1];
    $codeAttrs = $m[2];
    if (!preg_match('/class=["\']/i', $codeAttrs)) {
        $codeAttrs .= ' class="language-none"';
    } else if (!preg_match('/language-/i', $codeAttrs)) {
        $codeAttrs = preg_replace('/class=["\']([^"\']*)["\']/i', 'class="$1 language-none"', $codeAttrs);
    }
    return '<pre' . $preAttrs . '><code' . $codeAttrs . '>' . $m[3] . '</code></pre>';
}, $processedContent);

$page_extra_head = '';

// KaTeX
$page_extra_head .= '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
';

if ($hasCode) {
    $page_extra_head .= '
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-html.min.js"></script>
    ';
}

$page_extra_head .= '
<style>
.toc-sidebar { position: sticky; top: 6rem; max-height: calc(100vh - 8rem); overflow-y: auto; }
.toc-sidebar::-webkit-scrollbar { width: 4px; }
.toc-sidebar::-webkit-scrollbar-thumb { background: rgba(156,163,175,0.4); border-radius: 4px; }
.toc-link { display: block; padding: 0.35rem 0.5rem; border-radius: 0.5rem; font-size: 0.8rem; color: #6b7280; transition: all 0.15s; }
.dark .toc-link { color: #9ca3af; }
.toc-link:hover { background: rgba(99,102,241,0.08); color: #6366f1; }
.toc-link.active { background: rgba(99,102,241,0.12); color: #6366f1; font-weight: 500; }
.toc-link.level-3 { padding-left: 1.25rem; }
.diary-content img { border-radius: 0.75rem; margin: 1rem 0; }
.diary-content .img-align-center { display: block; margin: 1.5rem auto; text-align: center; }
.diary-content .img-align-left { display: block; margin: 1rem 0; }
.diary-content .img-align-right { display: block; margin: 1rem 0; margin-left: auto; }
.diary-content .img-float-left { float: left; margin: 0.5rem 1rem 0.5rem 0; }
.diary-content .img-float-right { float: right; margin: 0.5rem 0 0.5rem 1rem; }
.diary-content .img-float-left::after,
.diary-content .img-float-right::after { content: ""; display: table; clear: both; }
/* Cover hero */
.cover-hero { width: 100%; height: 320px; object-fit: cover; border-radius: 1rem; margin-bottom: 2rem; }
@media (max-width: 768px) { .cover-hero { height: 200px; } }
/* Code copy button */
.code-block-wrapper { position: relative; }
.code-copy-btn {
    position: absolute; top: 0.5rem; right: 0.5rem;
    padding: 0.35rem 0.75rem; font-size: 0.75rem; border-radius: 0.5rem;
    background: rgba(255,255,255,0.1); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.15);
    cursor: pointer; opacity: 0; transition: opacity 0.2s;
}
.code-block-wrapper:hover .code-copy-btn { opacity: 1; }
.code-copy-btn:hover { background: rgba(255,255,255,0.2); }
.code-copy-btn.copied { background: #10b981; color: #fff; border-color: #10b981; }
/* Share buttons */
.share-btn {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.5rem 1rem; border-radius: 0.75rem; font-size: 0.875rem;
    border: 1px solid #e5e7eb; background: #fff; color: #374151; cursor: pointer; transition: all 0.15s;
}
.dark .share-btn { background: #1f2937; border-color: #374151; color: #d1d5db; }
.share-btn:hover { border-color: #6366f1; color: #6366f1; }
</style>
';

$page_title = sanitizeHtml($diary['title']);

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $diary['title'],
    'datePublished' => $diary['date'],
    'author' => ['@type' => 'Person', 'name' => $site_title],
    'description' => mb_substr(strip_tags($diary['content'] ?? ''), 0, 200),
];

$ogOverride = '
<meta property="og:title" content="' . sanitizeHtml($diary['title']) . '">
<meta property="og:description" content="' . sanitizeHtml(mb_substr(strip_tags($diary['content'] ?? ''), 0, 200)) . '">
<meta property="og:type" content="article">
<meta property="og:url" content="' . ('http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) . '">
';
if (!empty($page_extra_head)) {
    $page_extra_head .= $ogOverride;
} else {
    $page_extra_head = $ogOverride;
}

include __DIR__ . '/includes/header.php';

// Visibility checks
$canViewContent = false;
$showPasswordForm = false;
$showScheduledMessage = false;

if ($isAdmin) {
    $canViewContent = true;
} else {
    if ($isDraft) {
        // can't view
    } elseif ($isScheduled) {
        $showScheduledMessage = true;
    } elseif ($hasPassword && !$isUnlocked) {
        $showPasswordForm = true;
    } else {
        $canViewContent = true;
    }
}

if (!$canViewContent && !$showPasswordForm && !$showScheduledMessage) {
    ?>
    <div class="max-w-3xl mx-auto px-6 py-20 text-center">
        <div class="text-gray-400 mb-4">
            <i class="fa-solid fa-lock text-5xl opacity-40"></i>
        </div>
        <h1 class="text-2xl font-bold mb-2"><?= sanitizeHtml($text('not_published', '该日记尚未发布')) ?></h1>
        <p class="text-gray-500 dark:text-gray-400 mb-6"><?= sanitizeHtml($text('check_back_later', '请稍后再来看看。')) ?></p>
        <a href="diary.php" class="inline-flex items-center text-sm px-5 py-2.5 rounded-2xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            <?= sanitizeHtml($text('back_to_journal', '返回日记')) ?>
        </a>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}
?>

<div class="max-w-6xl mx-auto px-6 py-10">
    <div class="flex flex-col lg:flex-row gap-10">
        <!-- Main Content -->
        <div class="flex-1 min-w-0">
            <a href="diary.php" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 mb-8 group">
                <i class="fa-solid fa-arrow-left mr-2 group-hover:-translate-x-0.5 transition"></i>
                <?= sanitizeHtml($text('back_to_journal', '返回日记')) ?>
            </a>

            <?php if ($showScheduledMessage): ?>
                <div class="mb-8 p-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl text-center">
                    <i class="fa-regular fa-clock text-amber-500 text-3xl mb-3"></i>
                    <h2 class="text-xl font-semibold text-amber-800 dark:text-amber-200 mb-2"><?= sanitizeHtml($text('password_protected', '即将发布')) ?></h2>
                    <p class="text-amber-700 dark:text-amber-300"><?= sanitizeHtml(sprintf($text('will_publish_at', '该日记将于 %s 发布'), $publishAt)) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($showPasswordForm): ?>
                <div class="mb-8 max-w-md mx-auto p-8 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-3xl">
                    <div class="text-center mb-6">
                        <i class="fa-solid fa-lock text-indigo-500 text-3xl mb-3"></i>
                        <h2 class="text-xl font-semibold"><?= sanitizeHtml($text('password_protected', '密码保护')) ?></h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?= sanitizeHtml($text('enter_password', '请输入密码以查看此日记')) ?></p>
                    </div>
                    <form method="POST" class="space-y-4">
                        <?= csrfField() ?>
                        <input type="password" name="diary_password" required
                               class="w-full px-4 h-12 rounded-2xl border border-gray-200 dark:border-gray-600 bg-transparent focus:border-indigo-400 outline-none text-sm"
                               placeholder="Password">
                        <?php if ($passwordError): ?>
                            <p class="text-sm text-red-500">密码错误，请重试。</p>
                        <?php endif; ?>
                        <button type="submit" name="unlock_diary"
                                class="w-full h-11 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm">
                            <i class="fa-solid fa-unlock mr-1"></i> 解锁
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($canViewContent): ?>
            <article>
                <div class="flex items-center gap-x-3 mb-4 flex-wrap">
                    <span class="px-4 py-1 text-xs font-medium bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300 rounded-2xl"><?= sanitizeHtml($diary['date']) ?></span>
                    <?php if ($isDraft && $isAdmin): ?>
                        <span class="px-3 py-px text-xs font-medium bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded-xl"><?= sanitizeHtml($text('draft_label', '草稿')) ?></span>
                    <?php endif; ?>
                    <?php if ($isScheduled && $isAdmin): ?>
                        <span class="px-3 py-px text-xs font-medium bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300 rounded-xl"><?= sanitizeHtml(sprintf($text('will_publish_at', '定时 %s'), $publishAt)) ?></span>
                    <?php endif; ?>
                    <?php if ($hasPassword && $isAdmin): ?>
                        <span class="px-3 py-px text-xs font-medium bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded-xl"><i class="fa-solid fa-lock mr-0.5"></i><?= sanitizeHtml($text('password_protected', '密码保护')) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($diary['category'])): ?>
                        <span class="px-3 py-px text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-xl"><?= sanitizeHtml($diary['category']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($diary['tags'])): ?>
                        <?php foreach ($diary['tags'] as $tag): ?>
                            <a href="search.php?tag=<?= urlencode($tag) ?>" class="text-xs px-3 py-px bg-gray-100 dark:bg-gray-700 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                <?= sanitizeHtml($tag) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($isAdmin && !empty($diaryFrontEdit)): ?>
                <div class="mb-4">
                    <a href="admin/diary.php?edit=<?= (int)$diary['id'] ?>" class="inline-flex items-center gap-x-2 px-4 py-2 text-sm font-medium rounded-2xl bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <span><?= sanitizeHtml($text('edit_entry', '编辑此篇')) ?></span>
                    </a>
                    <a href="admin/diary.php" class="inline-flex items-center gap-x-2 px-4 py-2 text-sm font-medium rounded-2xl bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600 transition-colors ml-2">
                        <i class="fa-solid fa-list"></i>
                        <span><?= sanitizeHtml($text('all_entries', '所有日记')) ?></span>
                    </a>
                </div>
                <?php endif; ?>

                <div class="flex items-center gap-x-3 text-sm text-gray-500 dark:text-gray-400 mb-2">
                    <i class="fa-regular fa-clock"></i>
                    <span><?= $readingLabel ?></span>
                    <span>&middot;</span>
                    <span><?= mb_strlen($plainContent) ?> <?= sanitizeHtml($text('chars', '字')) ?></span>
                    <?php if (!empty($diary['views']) || isset($diary['views'])): ?>
                        <span>&middot;</span>
                        <span><i class="fa-regular fa-eye mr-1"></i><?= (int)($diary['views'] ?? 0) ?> <?= sanitizeHtml($text('views', '浏览')) ?></span>
                    <?php endif; ?>
                </div>

                <h1 class="text-4xl md:text-5xl font-bold tracking-tighter leading-[1.1] mb-8">
                    <?= sanitizeHtml($diary['title']) ?>
                </h1>

                <?php if (!empty($diary['cover_image'])): ?>
                    <img src="<?= htmlspecialchars($diary['cover_image']) ?>" alt="<?= htmlspecialchars($diary['title']) ?>" class="cover-hero">
                <?php endif; ?>

                <div class="diary-content prose prose-lg dark:prose-invert max-w-none text-[15.2px] leading-relaxed">
                    <?= $processedContent ?>
                </div>
            </article>

            <!-- Social Sharing -->
            <div class="mt-10 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-x-3">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300"><?= sanitizeHtml($text('share', '分享')) ?>:</span>
                    <button type="button" class="share-btn" id="share-copy-btn" data-url="<?= htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>">
                        <i class="fa-solid fa-link"></i> <?= sanitizeHtml($text('copy_link', '复制链接')) ?>
                    </button>
                    <a href="https://service.weibo.com/share/share.php?url=<?= urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>&title=<?= urlencode($diary['title']) ?>" target="_blank" class="share-btn">
                        <i class="fa-brands fa-weibo text-red-500"></i> 微博
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($diary['title']) ?>" target="_blank" class="share-btn">
                        <i class="fa-brands fa-x-twitter"></i> Twitter
                    </a>
                </div>
            </div>

            <?php if ($prevDiary || $nextDiary): ?>
            <nav class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center gap-4">
                    <?php if ($prevDiary): ?>
                    <a href="<?= diaryUrl($prevDiary) ?>" class="flex-1 group">
                        <div class="text-xs text-gray-400 dark:text-gray-500 mb-1 group-hover:text-gray-600 dark:group-hover:text-gray-300"><i class="fa-solid fa-arrow-left mr-1"></i> <?= sanitizeHtml($text('prev_entry', '上一篇')) ?></div>
                        <div class="font-medium truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors"><?= sanitizeHtml($prevDiary['title']) ?></div>
                    </a>
                    <?php endif; ?>
                    <?php if ($nextDiary): ?>
                    <a href="<?= diaryUrl($nextDiary) ?>" class="flex-1 text-right group">
                        <div class="text-xs text-gray-400 dark:text-gray-500 mb-1 group-hover:text-gray-600 dark:group-hover:text-gray-300"><?= sanitizeHtml($text('next_entry', '下一篇')) ?> <i class="fa-solid fa-arrow-right ml-1"></i></div>
                        <div class="font-medium truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors"><?= sanitizeHtml($nextDiary['title']) ?></div>
                    </a>
                    <?php endif; ?>
                </div>
            </nav>
            <?php endif; ?>

            <?php if ($diaryCommentsEnabled): ?>
            <!-- 评论区 -->
            <div class="mt-14 pt-8 border-t border-gray-200 dark:border-gray-700" id="comments">
                <h3 class="text-2xl font-semibold tracking-tight mb-6 flex items-center gap-x-2">
                    <i class="fa-solid fa-comments text-indigo-500"></i>
                    <span><?= sanitizeHtml($text('comments_title', '评论')) ?></span>
                </h3>

                <!-- 评论表单 -->
                <div class="<?php if ($glassEnabled): ?>glass-card<?php else: ?>bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700<?php endif; ?> rounded-3xl p-6 mb-8">
                    <?php if ($commentRichText): ?>
                    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
                    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
                    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
                    <style>
                        .comment-editor .note-editor { border-radius: 0.75rem !important; overflow: hidden; min-height: auto; }
                        .comment-editor .note-editing-area { min-height: 120px; }
                        .comment-editor .note-toolbar { background: #f8fafc !important; }
                        .dark .comment-editor .note-toolbar { background: #1f2937 !important; }
                        .comment-editor .note-editor.note-frame { border-color: #e5e7eb !important; }
                        .dark .comment-editor .note-editor.note-frame { border-color: #374151 !important; }
                    </style>
                    <?php endif; ?>

                    <form method="POST" id="comment-form" class="space-y-4">
                        <?= csrfField() ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-medium text-gray-500 dark:text-gray-400 block mb-1.5"><?= sanitizeHtml($text('nickname_label', '昵称')) ?></label>
                                <input type="text" name="name" required
                                       class="w-full px-4 h-11 rounded-2xl border border-gray-200 dark:border-gray-600 bg-transparent focus:border-indigo-400 outline-none text-sm"
                                       placeholder="<?= sanitizeHtml($text('your_name_placeholder', '你的名字')) ?>" value="访客">
                            </div>
                        </div>
                        <div class="<?php if ($commentRichText): ?>comment-editor<?php endif; ?>">
                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 block mb-1.5"><?= sanitizeHtml($text('comment_label', '评论')) ?></label>
                            <?php if ($commentRichText): ?>
                                <textarea id="comment-editor" name="comment_content"></textarea>
                            <?php else: ?>
                                <textarea name="comment_content" rows="4" required
                                          class="w-full px-4 py-3 rounded-2xl border border-gray-200 dark:border-gray-600 bg-transparent focus:border-indigo-400 outline-none text-sm resize-y"
                                          placeholder="<?= sanitizeHtml($text('comment_placeholder', '写下你的评论...')) ?>"></textarea>
                            <?php endif; ?>
                        </div>
                        <button type="submit" name="post_comment"
                                class="px-6 h-10 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm flex items-center gap-x-2">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span><?= sanitizeHtml($text('post_comment', '发表评论')) ?></span>
                        </button>
                    </form>

                    <?php if ($commentRichText): ?>
                    <script>
                    $(document).ready(function() {
                        $('#comment-editor').summernote({
                            height: 150,
                            toolbar: [
                                ['font', ['bold', 'italic', 'underline']],
                                ['color', ['color']],
                                ['para', ['ul', 'ol']],
                                ['insert', ['link', 'picture', 'video']],
                                ['view', ['codeview']]
                            ]
                        });
                    });
                    </script>
                    <?php endif; ?>
                </div>

                <!-- 显示评论 -->
                <?php
                $commentsFile = __DIR__ . '/data/diary_comments.json';
                $allComments = readJsonFile($commentsFile);
                $diaryComments = array_filter($allComments, function($c) use ($id) {
                    return (int)($c['diary_id'] ?? 0) === $id && empty($c['hidden']);
                });
                usort($diaryComments, fn($a, $b) => $b['id'] <=> $a['id']);
                ?>

                <?php if (empty($diaryComments)): ?>
                    <p class="text-gray-400 text-center py-6"><?= sanitizeHtml($text('no_comments', '暂无评论，来做第一个评论的人吧！')) ?></p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($diaryComments as $comment): ?>
                            <div class="<?php if ($glassEnabled): ?>glass-card<?php else: ?>bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700<?php endif; ?> rounded-2xl p-5">
                                <div class="flex items-center gap-x-2 mb-3">
                                    <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-sm font-bold">
                                        <?= mb_strtoupper(mb_substr($comment['name'], 0, 1)) ?>
                                    </div>
                                    <span class="font-semibold text-sm"><?= sanitizeHtml($comment['name']) ?></span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500"><?= sanitizeHtml($comment['timestamp']) ?></span>
                                </div>
                                <div class="text-[15px] text-gray-700 dark:text-gray-300 prose prose-sm dark:prose-invert max-w-none">
                                    <?= sanitizeRichText($comment['content']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="mt-14 pt-8 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center text-sm">
                <a href="diary.php" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">&larr; <?= sanitizeHtml($text('all_entries', '所有日记')) ?></a>
                <a href="guestbook.php" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 flex items-center gap-x-1">
                    <?= sanitizeHtml($text('discuss_guestbook', '去留言板讨论')) ?> <i class="fa-solid fa-comments"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- TOC Sidebar -->
        <?php if (!empty($tocItems) && ($canViewContent || $isAdmin)): ?>
        <aside class="hidden lg:block w-64 flex-shrink-0">
            <div class="toc-sidebar">
                <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3"><?= sanitizeHtml($text('toc_title', '目录')) ?></div>
                <nav class="space-y-0.5 border-l border-gray-200 dark:border-gray-700 ml-1">
                    <?php foreach ($tocItems as $item): ?>
                        <a href="#<?= sanitizeHtml($item['anchor']) ?>" class="toc-link level-<?= $item['level'] ?>" data-anchor="<?= sanitizeHtml($item['anchor']) ?>">
                            <?= sanitizeHtml($item['title']) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </aside>
        <?php endif; ?>
    </div>
</div>

<script>
// TOC active state on scroll
(function() {
    const tocLinks = document.querySelectorAll('.toc-link');
    const headings = document.querySelectorAll('.diary-content [id^="toc-heading-"]');
    if (!tocLinks.length || !headings.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                tocLinks.forEach(link => link.classList.remove('active'));
                const activeLink = document.querySelector('.toc-link[data-anchor="' + entry.target.id + '"]');
                if (activeLink) activeLink.classList.add('active');
            }
        });
    }, { rootMargin: '-80px 0px -60% 0px', threshold: 0 });

    headings.forEach(h => observer.observe(h));

    tocLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.getElementById(this.dataset.anchor);
            if (target) {
                const y = target.getBoundingClientRect().top + window.pageYOffset - 80;
                window.scrollTo({ top: y, behavior: 'smooth' });
            }
        });
    });
})();

// PrismJS highlight
if (window.Prism) {
    Prism.highlightAll();
}

// KaTeX auto-render
if (window.renderMathInElement) {
    renderMathInElement(document.querySelector('.diary-content'), {
        delimiters: [
            {left: '$$', right: '$$', display: true},
            {left: '$', right: '$', display: false}
        ],
        throwOnError: false
    });
}

// Code copy buttons
(function() {
    const preBlocks = document.querySelectorAll('.diary-content pre');
    preBlocks.forEach(function(pre) {
        const wrapper = document.createElement('div');
        wrapper.className = 'code-block-wrapper';
        pre.parentNode.insertBefore(wrapper, pre);
        wrapper.appendChild(pre);

        const btn = document.createElement('button');
        btn.className = 'code-copy-btn';
        btn.innerHTML = '<i class="fa-regular fa-copy mr-1"></i>复制';
        btn.addEventListener('click', function() {
            const code = pre.querySelector('code');
            const text = code ? code.innerText : pre.innerText;
            navigator.clipboard.writeText(text).then(function() {
                btn.classList.add('copied');
                btn.innerHTML = '<i class="fa-solid fa-check mr-1"></i>已复制!';
                setTimeout(function() {
                    btn.classList.remove('copied');
                    btn.innerHTML = '<i class="fa-regular fa-copy mr-1"></i>复制';
                }, 2000);
            });
        });
        wrapper.appendChild(btn);
    });
})();

// Social share copy link
(function() {
    const shareBtn = document.getElementById('share-copy-btn');
    if (shareBtn) {
        shareBtn.addEventListener('click', function() {
            const url = this.dataset.url;
            navigator.clipboard.writeText(url).then(function() {
                const original = shareBtn.innerHTML;
                shareBtn.innerHTML = '<i class="fa-solid fa-check text-emerald-500"></i> 已复制!';
                setTimeout(function() {
                    shareBtn.innerHTML = original;
                }, 2000);
            });
        });
    }
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
