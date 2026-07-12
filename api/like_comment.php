<?php
require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '方法不允许']);
    exit;
}

// 速率限制
if (!checkRateLimit('like', 30, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => '请求过于频繁']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => '无效的ID']);
    exit;
}

// 防重复点赞：同一session对同一消息只能点赞一次
if (session_status() === PHP_SESSION_NONE) session_start();
$likedKey = 'liked_' . $id;
if (!empty($_SESSION[$likedKey])) {
    $guestbookFile = __DIR__ . '/../data/guestbook.json';
    $messages = readJsonFile($guestbookFile);
    foreach ($messages as $msg) {
        if ((int)$msg['id'] === $id) {
            echo json_encode(['success' => true, 'likes' => $msg['likes'] ?? 0, 'already' => true]);
            return;
        }
    }
    echo json_encode(['success' => false, 'message' => '未找到该留言']);
    exit;
}

$guestbookFile = __DIR__ . '/../data/guestbook.json';
$found = false;
$newLikes = 0;

$messages = atomicJsonUpdate($guestbookFile, function($messages) use ($id, &$found, &$newLikes) {
    foreach ($messages as &$msg) {
        if ((int)$msg['id'] === $id) {
            $msg['likes'] = ($msg['likes'] ?? 0) + 1;
            $newLikes = $msg['likes'];
            $found = true;
            break;
        }
    }
    return $messages;
});

if ($found) {
    $_SESSION[$likedKey] = true;
    echo json_encode(['success' => true, 'likes' => $newLikes]);
} else {
    echo json_encode(['success' => false, 'message' => '操作失败']);
}
?>