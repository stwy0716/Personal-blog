<?php
/**
 * 个人主页安全基础设施
 * 提供：CSRF防护、安全响应头、JSON原子写入、文件锁、速率限制、输入消毒
 */

// ==================== 安全响应头 ====================
function sendSecurityHeaders() {
    // 防止MIME嗅探
    header('X-Content-Type-Options: nosniff');
    // 防止点击劫持
    header('X-Frame-Options: DENY');
    // XSS保护（旧浏览器兼容）
    header('X-XSS-Protection: 1; mode=block');
    // 引用策略
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // 内容安全策略
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://code.jquery.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; img-src 'self' data: blob: https:; media-src 'self' https:; connect-src 'self'; frame-src 'none'; object-src 'none'; base-uri 'self'; form-action 'self'");
    // HTTPS强制（生产环境启用）
    // header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ==================== CSRF 防护 ====================
function generateCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time']) ||
        time() - $_SESSION['csrf_token_time'] > 3600) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    // 时间验证：token有效期1小时
    if (time() - ($_SESSION['csrf_token_time'] ?? 0) > 3600) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

function verifyPostCsrf(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCsrfToken($token)) {
        http_response_code(403);
        die('CSRF验证失败，请刷新页面后重试。');
    }
    return true;
}

// ==================== JSON 文件安全读写 ====================
function readJsonFile(string $path): array {
    if (!file_exists($path)) return [];
    $raw = file_get_contents($path);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) return [];
    return is_array($data) ? $data : [];
}

function writeJsonFile(string $path, array $data, bool $pretty = true): bool {
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    if ($pretty) $flags |= JSON_PRETTY_PRINT;
    $content = json_encode($data, $flags);

    // 原子写入：先写临时文件，再rename
    $dir = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $temp = tempnam($dir, 'json_');
    if ($temp === false) return false;

    // 使用文件锁防止并发写入
    $fp = fopen($temp, 'w');
    if ($fp === false) { @unlink($temp); return false; }

    if (flock($fp, LOCK_EX)) {
        $result = fwrite($fp, $content);
        fflush($fp);
        flock($fp, LOCK_UN);
    } else {
        fclose($fp);
        @unlink($temp);
        return false;
    }
    fclose($fp);

    if ($result === false) { @unlink($temp); return false; }

    if (!rename($temp, $path)) {
        @unlink($temp);
        return false;
    }

    return true;
}

function atomicJsonUpdate(string $path, callable $updater): bool {
    $data = readJsonFile($path);
    $data = $updater($data);
    return writeJsonFile($path, $data);
}

// ==================== 输入验证和消毒 ====================
function sanitizeString(string $input, int $maxLength = 1000): string {
    return mb_substr(trim($input), 0, $maxLength);
}

function sanitizeHtml(string $input): string {
    return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function validateFileType(string $filePath, array $allowedMimes): bool {
    if (!file_exists($filePath)) return false;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    return in_array($mime, $allowedMimes);
}

function isAllowedExtension(string $filename, array $allowedExts): bool {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, $allowedExts);
}

// ==================== 速率限制 ====================
function checkRateLimit(string $key, int $maxRequests = 30, int $windowSeconds = 60): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $now = time();
    $windowStart = $now - $windowSeconds;

    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [];
    }

    // 清除过期记录
    $_SESSION['rate_limit'][$key] = array_filter(
        $_SESSION['rate_limit'][$key],
        fn($t) => $t > $windowStart
    );

    if (count($_SESSION['rate_limit'][$key]) >= $maxRequests) {
        return false; // 超出限制
    }

    $_SESSION['rate_limit'][$key][] = $now;
    return true;
}

// ==================== 简易 HTML 消毒（白名单方式） ====================
function sanitizeRichText(string $html): string {
    // 移除危险标签和属性
    $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/si', '', $html);
    $html = preg_replace('/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/si', '', $html);
    $html = preg_replace('/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/si', '', $html);
    $html = preg_replace('/<embed\b[^>]*>/si', '', $html);
    $html = preg_replace('/<form\b[^<]*(?:(?!<\/form>)<[^<]*)*<\/form>/si', '', $html);
    $html = preg_replace('/<meta\b[^>]*>/si', '', $html);
    $html = preg_replace('/<link\b[^>]*>/si', '', $html);
    $html = preg_replace('/<base\b[^>]*>/si', '', $html);
    $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/si', '', $html);

    // 移除事件处理器属性
    $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/si', '', $html);
    $html = preg_replace('/\s*on\w+\s*=\s*\S+/si', '', $html);

    // 移除 javascript: 和 data: 协议
    $html = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/si', 'href="#"', $html);
    $html = preg_replace('/src\s*=\s*["\']javascript:[^"\']*["\']/si', 'src=""', $html);
    $html = preg_replace('/src\s*=\s*["\']data:[^"\']*["\']/si', 'src=""', $html);

    // 移除 SVG（可能包含内嵌脚本）
    $html = preg_replace('/<svg\b[^<]*(?:(?!<\/svg>)<[^<]*)*<\/svg>/si', '', $html);

    return $html;
}

// ==================== Session 安全配置 ====================
function configureSecureSession() {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    // 如果使用HTTPS，取消注释下行
    // ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}
?>
