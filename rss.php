<?php
header('Content-Type: application/rss+xml; charset=utf-8');

$diariesFile = __DIR__ . '/data/diaries.json';
$diaries = file_exists($diariesFile) ? json_decode(file_get_contents($diariesFile), true) : [];

$siteTitle = "个人主页RSS订阅";
$siteLink = "https://your-domain.com"; // Replace with actual domain
$description = "生活故事、思考与探索";

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0">
<channel>
    <title><?= htmlspecialchars($siteTitle) ?></title>
    <link><?= htmlspecialchars($siteLink) ?></link>
    <description><?= htmlspecialchars($description) ?></description>
    <lastBuildDate><?= date('r') ?></lastBuildDate>
    <language>zh-CN</language>
    
    <?php foreach (array_slice($diaries, 0, 20) as $diary): ?>
    <item>
        <title><?= htmlspecialchars($diary['title']) ?></title>
        <link><?= $siteLink ?>/diary-detail.php?id=<?= $diary['id'] ?></link>
        <description><?= htmlspecialchars(strip_tags($diary['excerpt'] ?? '')) ?></description>
        <pubDate><?= date('r', strtotime($diary['date'])) ?></pubDate>
        <guid isPermaLink="false"><?= $siteLink ?>/diary/<?= $diary['id'] ?></guid>
    </item>
    <?php endforeach; ?>
</channel>
</rss>