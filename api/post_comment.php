<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '方法不允许']);
    exit;
}

// 速率限制：每分钟最多5次留言
if (!checkRateLimit('guestbook_post', 5, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => '留言过于频繁，请稍后再试']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$name = sanitizeString($input['name'] ?? 'Anonymous', 50);
$content = sanitizeString($input['content'] ?? '', 2000);

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => '留言内容不能为空']);
    exit;
}

$guestbookFile = __DIR__ . '/../data/guestbook.json';
$messages = readJsonFile($guestbookFile);

$newId = 1;
if (!empty($messages)) {
    $ids = array_column($messages, 'id');
    $newId = max($ids) + 1;
}

$newMessage = [
    'id' => $newId,
    'name' => sanitizeHtml($name),
    'content' => sanitizeHtml($content),
    'timestamp' => date('Y-m-d H:i'),
    'likes' => 0,
    'replies' => []
];

$messages[] = $newMessage;

if (writeJsonFile($guestbookFile, $messages)) {
    sendCommentNotification('comment', [
        'name' => $name,
        'content' => $content,
        'link' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/guestbook.php',
        'page_title' => '留言板'
    ]);
    echo json_encode(['success' => true, 'id' => $newId, 'message' => '留言成功']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '保存失败，请稍后重试']);
}
?>