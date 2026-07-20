<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '方法不允许']);
    exit;
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token invalid']);
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
    // 查找父评论作者邮箱
    $notifyTo = null;
    $parentEmail = null;
    $parentName = null;
    foreach ($messages as $msg) {
        if ((int)$msg['id'] === $parentId) {
            $parentName = $msg['name'] ?? '';
            $parentEmail = $msg['email'] ?? null;
            break;
        }
    }

    if (!empty($parentEmail) && filter_var($parentEmail, FILTER_VALIDATE_EMAIL)) {
        $notifyTo = $parentEmail;
    } elseif ($parentName === 'Admin') {
        // 如果回复的是管理员且没有邮箱，使用管理员邮箱
        $config = getSmtpConfig();
        $notifyTo = $config['admin_email'] ?? null;
    }

    // 非阻塞发送邮件通知
    try {
        sendCommentNotification('reply', [
            'name' => $name,
            'content' => $content,
            'link' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/guestbook.php',
            'page_title' => '留言板'
        ], $notifyTo);
    } catch (Throwable $e) {
        // 邮件发送失败不影响回复成功
    }

    echo json_encode(['success' => true, 'message' => '回复成功']);
} else {
    echo json_encode(['success' => false, 'message' => '回复失败']);
}
?>