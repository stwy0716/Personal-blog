<?php
/**
 * 管理员认证系统
 * - 安全Session配置
 * - 密码哈希（bcrypt）
 * - 登录失败锁定（5次/10分钟）
 * - Session超时（1小时）
 * - CSRF防护
 * - 操作审计日志
 * - 原子JSON写入
 */

require_once __DIR__ . '/../includes/security.php';

// 安全Session配置
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

define('ADMIN_CONFIG_FILE', __DIR__ . '/../data/admin.json');
define('MAX_FAILED_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 10 * 60);
define('SESSION_TIMEOUT', 60 * 60);

function getAdminConfig() {
    if (!file_exists(ADMIN_CONFIG_FILE)) {
        $default = [
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'failed_attempts' => 0,
            'lockout_until' => null,
            'last_login' => null,
            'password_changed' => false
        ];
        writeJsonFile(ADMIN_CONFIG_FILE, $default);
        return $default;
    }
    return readJsonFile(ADMIN_CONFIG_FILE);
}

function saveAdminConfig($config) {
    writeJsonFile(ADMIN_CONFIG_FILE, $config);
}

function isLockedOut() {
    $config = getAdminConfig();
    return !empty($config['lockout_until']) && time() < $config['lockout_until'];
}

function recordFailedAttempt() {
    atomicJsonUpdate(ADMIN_CONFIG_FILE, function($config) {
        $config['failed_attempts'] = ($config['failed_attempts'] ?? 0) + 1;
        if ($config['failed_attempts'] >= MAX_FAILED_ATTEMPTS) {
            $config['lockout_until'] = time() + LOCKOUT_DURATION;
            $config['failed_attempts'] = 0;
        }
        return $config;
    });
}

function resetFailedAttempts() {
    atomicJsonUpdate(ADMIN_CONFIG_FILE, function($config) {
        $config['failed_attempts'] = 0;
        $config['lockout_until'] = null;
        return $config;
    });
}

function verifyAdminPassword($password) {
    $config = getAdminConfig();
    return password_verify($password, $config['password_hash'] ?? '');
}

function updateAdminPassword($newPassword) {
    // 密码强度要求：至少8位，包含数字和字母
    if (strlen($newPassword) < 8) return 'length';
    if (!preg_match('/[a-zA-Z]/', $newPassword)) return 'need_letter';
    if (!preg_match('/[0-9]/', $newPassword)) return 'need_number';
    
    atomicJsonUpdate(ADMIN_CONFIG_FILE, function($config) use ($newPassword) {
        $config['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        $config['password_changed'] = true;
        return $config;
    });
    return true;
}

function isAdminLoggedIn() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        logoutAdmin();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function loginAdmin() {
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['last_activity'] = time();
    atomicJsonUpdate(ADMIN_CONFIG_FILE, function($config) {
        $config['last_login'] = date('Y-m-d H:i:s');
        $config['failed_attempts'] = 0;
        $config['lockout_until'] = null;
        return $config;
    });
}

function logoutAdmin() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
}

/**
 * 统一登录入口：处理登录POST、登出GET、未认证显示登录表单
 */
function handleAdminAuth() {
    // 发送安全响应头
    sendSecurityHeaders();
    
    // 处理登出
    if (isset($_GET['logout'])) {
        logoutAdmin();
        header('Location: ' . basename($_SERVER['PHP_SELF']));
        exit;
    }
    
    // 处理登录POST
    if (isset($_POST['login_password'])) {
        // CSRF验证
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $GLOBALS['login_error'] = '安全验证失败，请刷新后重试。';
        } elseif (isLockedOut()) {
            $GLOBALS['login_error'] = '登录失败次数过多，请稍后再试。';
        } elseif (verifyAdminPassword($_POST['login_password'])) {
            loginAdmin();
            logOperation('login', '管理员登录');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            recordFailedAttempt();
            $GLOBALS['login_error'] = '密码错误';
        }
    }
    
    // 检查是否已登录
    if (isAdminLoggedIn()) {
        return true;
    }
    
    // 显示登录表单
    showLoginForm();
    exit;
}

function showLoginForm() {
    $error = $GLOBALS['login_error'] ?? '';
    $csrfToken = generateCsrfToken();
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>管理员登录</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    </head>
    <body class="bg-zinc-950 text-white flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md px-6">
            <div class="text-center mb-8">
                <div class="mx-auto w-16 h-16 bg-indigo-600 rounded-3xl flex items-center justify-center mb-5">
                    <i class="fa-solid fa-shield-halved text-4xl"></i>
                </div>
                <h1 class="text-4xl font-bold tracking-tighter">管理员登录</h1>
                <p class="text-zinc-400 mt-2 text-sm">安全认证</p>
            </div>
            <?php if ($error): ?>
                <div class="mb-5 p-3.5 bg-red-900/50 text-red-300 text-sm rounded-2xl flex items-center gap-x-2">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span><?= sanitizeHtml($error) ?></span>
                </div>
            <?php endif; ?>
            <?php if (isLockedOut()): ?>
                <div class="mb-5 p-3.5 bg-amber-900/50 text-amber-300 text-sm rounded-2xl flex items-center gap-x-2">
                    <i class="fa-solid fa-lock"></i>
                    <span>账号已锁定。请等待 <?= ceil((getAdminConfig()['lockout_until'] - time()) / 60) ?> 分钟。</span>
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= sanitizeHtml($csrfToken) ?>">
                <div>
                    <label class="block text-sm font-medium mb-2 text-zinc-300">管理员密码</label>
                    <input type="password" name="login_password" required autofocus
                           class="w-full px-5 py-3.5 bg-zinc-900 border border-zinc-700 focus:border-indigo-500 rounded-3xl text-sm outline-none"
                           placeholder="请输入密码">
                </div>
                <button type="submit" 
                        class="w-full py-3.5 bg-white hover:bg-zinc-100 active:bg-zinc-200 transition text-zinc-900 font-semibold rounded-3xl flex items-center justify-center gap-x-2">
                    <i class="fa-solid fa-sign-in-alt"></i>
                    <span>登录</span>
                </button>
            </form>
            <div class="mt-8 text-center">
                <p class="text-xs text-zinc-600">首次使用？请查看文档。</p>
            </div>
        </div>
    </body>
    </html>
    <?php
}

// ==================== 操作审计日志 ====================
define('OPERATION_LOG_FILE', __DIR__ . '/../data/operation_logs.json');

function logOperation($action, $description) {
    atomicJsonUpdate(OPERATION_LOG_FILE, function($logs) use ($action, $description) {
        $entry = [
            'time'        => date('Y-m-d H:i:s'),
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'action'      => $action,
            'description' => $description,
            'admin'       => 'admin'
        ];
        array_unshift($logs, $entry);
        if (count($logs) > 200) $logs = array_slice($logs, 0, 200);
        return $logs;
    });
}

function getOperationLogs($limit = 50) {
    $logs = readJsonFile(OPERATION_LOG_FILE);
    return array_slice($logs, 0, $limit);
}
?>
