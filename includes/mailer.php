<?php
/**
 * 邮件发送辅助函数
 * 支持完整 SMTP 协议（含 SSL/TLS 加密）
 */

require_once __DIR__ . '/security.php';

function getSmtpConfig() {
    $contentFile = __DIR__ . '/../data/content.json';
    $content = readJsonFile($contentFile);
    return [
        'smtp' => $content['smtp'] ?? [],
        'admin_email' => $content['contact']['email'] ?? '',
        'site_title' => $content['site']['title'] ?? '个人主页',
    ];
}

function smtpGetResponse($socket) {
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (substr($line, 3, 1) === ' ') break;
    }
    return $response;
}

function smtpCommand($socket, $command, $expectedCode) {
    if ($command) fwrite($socket, $command . "\r\n");
    $response = smtpGetResponse($socket);
    return strpos($response, (string)$expectedCode) === 0;
}

function smtpSendMail($to, $subject, $body) {
    $config = getSmtpConfig();
    $smtp = $config['smtp'];

    if (empty($smtp['host']) || empty($smtp['username']) || empty($smtp['password'])) {
        return false;
    }

    $host = $smtp['host'];
    $port = (int)($smtp['port'] ?? 587);
    $username = $smtp['username'];
    $password = $smtp['password'];
    $fromName = preg_replace('/[\r\n]/', '', $smtp['from_name'] ?? $config['site_title']);
    $encryption = strtolower($smtp['encryption'] ?? 'tls'); // tls / ssl / none
    $fromEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
    if (!$fromEmail) return false;

    $to = preg_replace('/[\r\n]/', '', $to);
    $subject = preg_replace('/[\r\n]/', '', $subject);

    $boundary = md5(uniqid());
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <" . $fromEmail . ">\r\n";
    $headers .= "Reply-To: " . $fromEmail . "\r\n";
    $headers .= "X-Mailer: PersonalHomepage/2.0\r\n";

    $message = $headers . "\r\n" . $body;

    $timeout = 30;
    $isSsl = ($encryption === 'ssl');
    $connectHost = $isSsl ? 'ssl://' . $host : $host;
    $connectPort = $port ?: ($isSsl ? 465 : 587);

    $socket = @fsockopen($connectHost, $connectPort, $errno, $errstr, $timeout);
    if (!$socket) return false;

    stream_set_timeout($socket, $timeout);

    smtpGetResponse($socket); // 220

    if (!smtpCommand($socket, 'EHLO ' . gethostname(), 250)) {
        fclose($socket);
        return false;
    }

    if (!$isSsl && $encryption === 'tls') {
        if (!smtpCommand($socket, 'STARTTLS', 220)) {
            fclose($socket);
            return false;
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return false;
        }
        if (!smtpCommand($socket, 'EHLO ' . gethostname(), 250)) {
            fclose($socket);
            return false;
        }
    }

    if (!smtpCommand($socket, 'AUTH LOGIN', 334)) {
        fclose($socket);
        return false;
    }
    if (!smtpCommand($socket, base64_encode($username), 334)) {
        fclose($socket);
        return false;
    }
    if (!smtpCommand($socket, base64_encode($password), 235)) {
        fclose($socket);
        return false;
    }

    if (!smtpCommand($socket, 'MAIL FROM:<' . $fromEmail . '>', 250)) {
        fclose($socket);
        return false;
    }
    if (!smtpCommand($socket, 'RCPT TO:<' . $to . '>', 250)) {
        fclose($socket);
        return false;
    }
    if (!smtpCommand($socket, 'DATA', 354)) {
        fclose($socket);
        return false;
    }

    $data = $message . "\r\n.\r\n";
    fwrite($socket, $data);
    $response = smtpGetResponse($socket);

    smtpCommand($socket, 'QUIT', 221);
    fclose($socket);

    return strpos($response, '250') === 0;
}

function sendMailWithFallback($to, $subject, $body) {
    if (smtpSendMail($to, $subject, $body)) {
        return true;
    }

    // Fallback to PHP mail()
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n";
    $headers .= "X-Mailer: PersonalHomepage/2.0 (mail)\r\n";

    return @mail($to, $subject, $body, $headers);
}

function sendCommentNotification($type, $data, $to = null) {
    $config = getSmtpConfig();
    $adminEmail = $config['admin_email'];

    if ($to === null) {
        $to = $adminEmail;
    }

    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $siteTitle = htmlspecialchars($config['site_title']);
    $name = htmlspecialchars($data['name'] ?? '匿名');
    $content = nl2br(htmlspecialchars($data['content'] ?? ''));
    $link = htmlspecialchars($data['link'] ?? '');
    $pageTitle = htmlspecialchars($data['page_title'] ?? '留言板');

    if ($type === 'reply') {
        $subject = '[' . $siteTitle . '] 新回复: ' . $pageTitle;
        $typeLabel = '回复';
    } else {
        $subject = '[' . $siteTitle . '] 新评论: ' . $pageTitle;
        $typeLabel = '新评论';
    }

    $body = '<!DOCTYPE html>';
    $body .= '<html><head><meta charset="UTF-8"></head><body style="font-family:system-ui,sans-serif;background:#f4f4f5;padding:40px 20px;">';
    $body .= '<div style="max-width:520px;margin:0 auto;background:#fff;border-radius:16px;padding:32px;box-shadow:0 4px 24px rgba(0,0,0,0.06);">';
    $body .= '<h2 style="margin:0 0 20px;font-size:18px;color:#18181b;">' . $siteTitle . ' - ' . $typeLabel . '</h2>';
    $body .= '<div style="background:#fafafa;border-radius:12px;padding:20px;margin-bottom:20px;">';
    $body .= '<p style="margin:0 0 8px;font-size:13px;color:#71717a;"><strong>来自：</strong> ' . $name . '</p>';
    $body .= '<p style="margin:0;font-size:14px;color:#27272a;line-height:1.6;">' . $content . '</p>';
    $body .= '</div>';
    if ($link) {
        $body .= '<a href="' . $link . '" style="display:inline-block;padding:10px 20px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:8px;font-size:13px;">查看页面</a>';
    }
    $body .= '<p style="margin:24px 0 0;font-size:12px;color:#a1a1aa;">这是一封自动通知邮件，请勿直接回复。</p>';
    $body .= '</div></body></html>';

    return sendMailWithFallback($to, $subject, $body);
}
