<?php
require_once __DIR__ . '/includes/security.php';

header('Content-Type: application/xml; charset=utf-8');

// 如果 install.php 仍存在，禁止生成 sitemap
if (file_exists(__DIR__ . '/install.php')) {
    http_response_code(403);
    echo '<error>Sitemap is disabled while install.php exists.</error>';
    exit;
}

$diaries = readJsonFile(__DIR__ . '/data/diaries.json');
$settings = readJsonFile(__DIR__ . '/data/content.json');
$guestbookEnabled = !empty($settings['settings']['guestbook_enabled']);

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

$today = date('Y-m-d');

function addUrl(&$urls, $loc, $priority, $lastmod, $changefreq = 'weekly') {
    $urls[] = [
        'loc' => $loc,
        'priority' => $priority,
        'lastmod' => $lastmod,
        'changefreq' => $changefreq,
    ];
}

$urls = [];

// 前台静态页面
addUrl($urls, $baseUrl . '/index.php', '1.0', $today, 'daily');
addUrl($urls, $baseUrl . '/about.php', '0.5', $today, 'monthly');
addUrl($urls, $baseUrl . '/diary.php', '0.8', $today, 'weekly');
addUrl($urls, $baseUrl . '/tags.php', '0.5', $today, 'weekly');
addUrl($urls, $baseUrl . '/search.php', '0.4', $today, 'weekly');
addUrl($urls, $baseUrl . '/friends.php', '0.5', $today, 'weekly');

if ($guestbookEnabled) {
    addUrl($urls, $baseUrl . '/guestbook.php', '0.5', $today, 'daily');
}

// 动态自定义页面
$pages = readJsonFile(__DIR__ . '/data/pages.json');
foreach ($pages as $page) {
    if (!empty($page['slug'])) {
        addUrl($urls, $baseUrl . '/' . $page['slug'] . '.php', '0.5', $today, 'weekly');
    }
}

// 所有已发布日记
foreach ($diaries as $diary) {
    if (($diary['status'] ?? 'published') === 'published') {
        $diaryDate = !empty($diary['date']) ? $diary['date'] : $today;
        addUrl($urls, $baseUrl . '/diary-detail.php?id=' . (int)$diary['id'], '0.8', $diaryDate, 'monthly');
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
    <url>
        <loc><?= htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') ?></loc>
        <lastmod><?= htmlspecialchars($url['lastmod'], ENT_XML1, 'UTF-8') ?></lastmod>
        <changefreq><?= htmlspecialchars($url['changefreq'], ENT_XML1, 'UTF-8') ?></changefreq>
        <priority><?= htmlspecialchars($url['priority'], ENT_XML1, 'UTF-8') ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
