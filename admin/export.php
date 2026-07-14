<?php
require_once __DIR__ . '/auth.php';
handleAdminAuth();

// 白名单验证导出类型
$allowedTypes = ['guestbook', 'diary', 'operation'];
$type = in_array($_GET['type'] ?? '', $allowedTypes) ? $_GET['type'] : 'guestbook';

// 生成安全的文件名
$safeFilename = $type . '_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $safeFilename . '"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

if ($type === 'guestbook') {
    $messages = readJsonFile(__DIR__ . '/../data/guestbook.json');
    fputcsv($output, ['ID', '昵称', '内容', '时间', '点赞数', '回复数']);
    foreach ($messages as $msg) {
        fputcsv($output, [
            $msg['id'],
            $msg['name'],
            $msg['content'],
            $msg['timestamp'],
            $msg['likes'] ?? 0,
            count($msg['replies'] ?? [])
        ]);
    }
} elseif ($type === 'diary') {
    $diaries = readJsonFile(__DIR__ . '/../data/diaries.json');
    fputcsv($output, ['ID', '标题', '日期', '摘要', '标签']);
    foreach ($diaries as $d) {
        fputcsv($output, [
            $d['id'],
            $d['title'],
            $d['date'],
            $d['excerpt'] ?? '',
            implode(', ', $d['tags'] ?? [])
        ]);
    }
} elseif ($type === 'operation') {
    $logs = readJsonFile(__DIR__ . '/../data/operation_logs.json');
    fputcsv($output, ['时间', '操作', '描述', 'IP', '管理员']);
    foreach ($logs as $log) {
        // IP地址掩码处理（隐私保护）
        $ip = $log['ip'] ?? '';
        $maskedIp = preg_replace('/\d+\.\d+/', '$1.***', $ip);
        fputcsv($output, [
            $log['time'] ?? '',
            $log['action'] ?? '',
            $log['description'] ?? '',
            $maskedIp,
            $log['admin'] ?? ''
        ]);
    }
}

fclose($output);
exit;
?>
