<?php
require_once __DIR__ . '/includes/security.php';

header('Content-Type: application/rss+xml; charset=utf-8');

$content = readJsonFile(__DIR__ . '/data/content.json');
$siteTitle = $content['site']['title'] ?? '个人主页RSS订阅';
$siteLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$description = $content['seo']['description'] ?? '生活故事、思考与探索';

$diaries = readJsonFile(__DIR__ . '/data/diaries.json');

// 过滤草稿
$diaries = array_filter($diaries, fn($d) => empty($d['status']) || $d['status'] !== 'draft');

// 按日期倒序排序
usort($diaries, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0">
<channel>
    <title><?= htmlspecialchars($siteTitle) ?></title>
    <link><?= htmlspecialchars($siteLink) ?></link>
    <description><?= htmlspecialchars($description) ?></description>
    <lastBuildDate><?= date('r') ?></lastBuildDate>
    <language><?= ($content['settings']['language'] ?? 'zh') === 'zh' ? 'zh-CN' : 'en-US' ?></language>

    <?php foreach (array_slice($diaries, 0, 20) as $diary): ?>
    <item>
        <title><?= htmlspecialchars($diary['title']) ?></title>
        <link><?= htmlspecialchars($siteLink) ?>/diary-detail.php?id=<?= $diary['id'] ?></link>
        <description><?= htmlspecialchars(strip_tags($diary['excerpt'] ?? '')) ?></description>
        <pubDate><?= date('r', strtotime($diary['date'])) ?></pubDate>
        <guid isPermaLink="false"><?= htmlspecialchars($siteLink) ?>/diary/<?= $diary['id'] ?></guid>
    </item>
    <?php endforeach; ?>
</channel>
</rss>
