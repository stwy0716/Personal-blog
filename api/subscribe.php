<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '方法不允许']);
    exit;
}

configureSecureSession();

// 速率限制：每小时最多5次订阅（按IP）
$ipKey = 'subscribe_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
if (!checkRateLimit($ipKey, 5, 3600)) {
    http_response_code(429);
    echo json_encode(['error' => '订阅过于频繁，请稍后再试']);
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (empty($email)) {
    echo json_encode(['error' => '邮箱不能为空']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => '邮箱格式不正确']);
    exit;
}

$subscribersFile = __DIR__ . '/../data/subscribers.json';

// 检查是否已存在
$subscribers = readJsonFile($subscribersFile);
foreach ($subscribers as $s) {
    if (strcasecmp($s['email'] ?? '', $email) === 0) {
        if (($s['status'] ?? 'active') === 'active') {
            echo json_encode(['error' => '该邮箱已订阅']);
        } else {
            // 如果之前取消订阅，允许重新激活
            atomicJsonUpdate($subscribersFile, function($data) use ($email) {
                foreach ($data as &$item) {
                    if (strcasecmp($item['email'] ?? '', $email) === 0) {
                        $item['status'] = 'active';
                        $item['subscribed_at'] = date('Y-m-d H:i:s');
                        $item['token'] = bin2hex(random_bytes(16));
                        break;
                    }
                }
                unset($item);
                return $data;
            });
            echo json_encode(['success' => true, 'message' => '订阅成功']);
        }
        exit;
    }
}

// 添加新订阅者
$newSubscriber = [
    'id' => uniqid('sub_', true),
    'email' => $email,
    'status' => 'active',
    'subscribed_at' => date('Y-m-d H:i:s'),
    'token' => bin2hex(random_bytes(16)),
];

atomicJsonUpdate($subscribersFile, function($data) use ($newSubscriber) {
    $data[] = $newSubscriber;
    return $data;
});

echo json_encode(['success' => true, 'message' => '订阅成功']);
