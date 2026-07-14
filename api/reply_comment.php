<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '方法不允许']);
    exit;
}

// 速率限制
if (!checkRateLimit('guestbook_reply', 10, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => '回复过于频繁，请稍后再试']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$parentId = (int)($input['parent_id'] ?? 0);
$name = sanitizeString($input['name'] ?? 'Anonymous', 50);
$content = sanitizeString($input['content'] ?? '', 1000);

if ($parentId <= 0 || empty($content)) {
    echo json_encode(['success' => false, 'message' => '参数无效']);
    exit;
}

$guestbookFile = __DIR__ . '/../data/guestbook.json';
$found = false;

$messages = atomicJsonUpdate($guestbookFile, function($messages) use ($parentId, $name, $content, &$found) {
    foreach ($messages as &$msg) {
        if ((int)$msg['id'] === $parentId) {
            if (!isset($msg['replies'])) $msg['replies'] = [];
            
            $msg['replies'][] = [
                'id' => time(),
                'name' => sanitizeHtml($name),
                'content' => sanitizeHtml($content),
                'timestamp' => date('Y-m-d H:i')
            ];
            $found = true;
            break;
        }
    }
    return $messages;
});

if ($found) {
    sendCommentNotification('reply', [
        'name' => $name,
        'content' => $content,
        'link' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/guestbook.php',
        'page_title' => '留言板'
    ]);
    echo json_encode(['success' => true, 'message' => '回复成功']);
} else {
    echo json_encode(['success' => false, 'message' => '回复失败']);
}
?>