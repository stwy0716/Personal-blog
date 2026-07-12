<?php
$page_title = '安全测试套件';
require_once __DIR__ . '/includes/header.php';

$results = [];
$passCount = 0;
$failCount = 0;

function recordTest(string $name, bool $passed, string $detail = ''): void {
    global $results, $passCount, $failCount;
    $results[] = ['name' => $name, 'passed' => $passed, 'detail' => $detail];
    if ($passed) $passCount++; else $failCount++;
}

// ==================== CSRF Token Tests ====================
try {
    $token = generateCsrfToken();
    recordTest('CSRF Token 生成', !empty($token) && strlen($token) === 64, '生成长度: ' . strlen($token));

    $valid = verifyCsrfToken($token);
    recordTest('CSRF Token 验证（正确）', $valid === true, '验证结果: ' . ($valid ? '通过' : '失败'));

    $invalid = verifyCsrfToken('invalid_token_12345678901234567890123456789012');
    recordTest('CSRF Token 验证（错误）', $invalid === false, '验证结果: ' . ($invalid ? '通过' : '失败'));

    $empty = verifyCsrfToken('');
    recordTest('CSRF Token 验证（空值）', $empty === false, '验证结果: ' . ($empty ? '通过' : '失败'));
} catch (Throwable $e) {
    recordTest('CSRF Token 生成', false, '异常: ' . $e->getMessage());
    recordTest('CSRF Token 验证（正确）', false, '异常: ' . $e->getMessage());
    recordTest('CSRF Token 验证（错误）', false, '异常: ' . $e->getMessage());
    recordTest('CSRF Token 验证（空值）', false, '异常: ' . $e->getMessage());
}

// ==================== Rate Limit Tests ====================
try {
    $testKey = 'security_test_rate';
    if (isset($_SESSION['rate_limit'][$testKey])) {
        unset($_SESSION['rate_limit'][$testKey]);
    }

    $passedWithinLimit = true;
    for ($i = 0; $i < 5; $i++) {
        if (!checkRateLimit($testKey, 5, 60)) {
            $passedWithinLimit = false;
            break;
        }
    }
    recordTest('速率限制（5次内通过）', $passedWithinLimit, '连续请求5次');

    $blocked = !checkRateLimit($testKey, 5, 60);
    recordTest('速率限制（第6次被拦截）', $blocked, '第6次请求应被拦截');

    unset($_SESSION['rate_limit'][$testKey]);
} catch (Throwable $e) {
    recordTest('速率限制（5次内通过）', false, '异常: ' . $e->getMessage());
    recordTest('速率限制（第6次被拦截）', false, '异常: ' . $e->getMessage());
}

// ==================== HTML Sanitization Tests ====================
try {
    $htmlCases = [
        ['<script>alert(1)</script>', ''],
        ['<p onclick="alert(1)">text</p>', '<p >text</p>'],
        ['<a href="javascript:alert(1)">link</a>', '<a href="#">link</a>'],
        ['<iframe src="evil.com"></iframe>', ''],
        ['<svg onload="alert(1)"></svg>', ''],
        ['<p>Safe content</p>', '<p>Safe content</p>'],
    ];

    $allHtmlPassed = true;
    $details = [];
    foreach ($htmlCases as [$input, $expected]) {
        $output = sanitizeRichText($input);
        $ok = trim($output) === trim($expected);
        if (!$ok) $allHtmlPassed = false;
        $details[] = ($ok ? '✓' : '✗') . ' ' . htmlspecialchars($input) . ' => ' . htmlspecialchars($output);
    }
    recordTest('HTML 消毒（RichText）', $allHtmlPassed, implode("; ", $details));
} catch (Throwable $e) {
    recordTest('HTML 消毒（RichText）', false, '异常: ' . $e->getMessage());
}

// ==================== String Sanitization Tests ====================
try {
    $s1 = sanitizeString('  hello world  ');
    recordTest('输入消毒（trim）', $s1 === 'hello world', '输入: "  hello world  " => "' . $s1 . '"');

    $long = str_repeat('a', 2000);
    $s2 = sanitizeString($long, 100);
    recordTest('输入消毒（截断）', strlen($s2) === 100, '长度 2000 => ' . strlen($s2));

    $s3 = sanitizeString('<b>test</b>');
    recordTest('输入消毒（保留标签）', $s3 === '<b>test</b>', '结果: ' . htmlspecialchars($s3));
} catch (Throwable $e) {
    recordTest('输入消毒（trim）', false, '异常: ' . $e->getMessage());
    recordTest('输入消毒（截断）', false, '异常: ' . $e->getMessage());
    recordTest('输入消毒（保留标签）', false, '异常: ' . $e->getMessage());
}

// ==================== JSON Atomic Write Tests ====================
try {
    $tmpFile = __DIR__ . '/data/_security_test_' . uniqid() . '.json';
    $testData = ['test' => true, 'value' => 42, 'nested' => ['a' => 'b']];
    $written = writeJsonFile($tmpFile, $testData);
    recordTest('JSON 原子写入', $written === true, '文件: ' . basename($tmpFile));

    $readData = readJsonFile($tmpFile);
    $match = $readData === $testData;
    recordTest('JSON 读取验证', $match, '读取结果: ' . ($match ? '一致' : '不一致'));

    if (file_exists($tmpFile)) {
        unlink($tmpFile);
        recordTest('JSON 临时文件清理', !file_exists($tmpFile), '已删除临时文件');
    } else {
        recordTest('JSON 临时文件清理', false, '文件不存在');
    }
} catch (Throwable $e) {
    recordTest('JSON 原子写入', false, '异常: ' . $e->getMessage());
    recordTest('JSON 读取验证', false, '异常: ' . $e->getMessage());
    recordTest('JSON 临时文件清理', false, '异常: ' . $e->getMessage());
}

$total = $passCount + $failCount;
$allPassed = $failCount === 0;
?>

<div class="max-w-4xl mx-auto px-6 py-12">
    <div class="text-center mb-10">
        <div class="inline-flex px-4 py-1 rounded-3xl bg-emerald-100 dark:bg-emerald-900 text-emerald-600 dark:text-emerald-300 text-xs font-semibold tracking-widest mb-3">安全</div>
        <h1 class="text-4xl font-bold tracking-tighter">安全测试套件</h1>
        <p class="mt-2 text-gray-500">自动化安全基础设施验证</p>
    </div>

    <div class="space-y-4">
        <?php foreach ($results as $r): ?>
        <div class="flex items-start gap-x-4 p-4 rounded-2xl border <?= $r['passed'] ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-100 dark:border-emerald-800' : 'bg-red-50 dark:bg-red-900/20 border-red-100 dark:border-red-800' ?>">
            <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm <?= $r['passed'] ? 'bg-emerald-200 dark:bg-emerald-800 text-emerald-700 dark:text-emerald-200' : 'bg-red-200 dark:bg-red-800 text-red-700 dark:text-red-200' ?>">
                <i class="fa-solid <?= $r['passed'] ? 'fa-check' : 'fa-xmark' ?>"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-sm <?= $r['passed'] ? 'text-emerald-800 dark:text-emerald-200' : 'text-red-800 dark:text-red-200' ?>">
                    <?= sanitizeHtml($r['name']) ?> — <?= $r['passed'] ? '通过' : '失败' ?>
                </div>
                <?php if (!empty($r['detail'])): ?>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 break-all"><?= sanitizeHtml($r['detail']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-10 p-6 rounded-3xl border text-center <?= $allPassed ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' ?>">
        <div class="text-2xl font-bold <?= $allPassed ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' ?>">
            <?= $allPassed ? '所有测试通过' : '存在失败的测试' ?>
        </div>
        <div class="mt-2 text-sm text-gray-500">
            总计 <?= $total ?> 项 · 通过 <?= $passCount ?> · 失败 <?= $failCount ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
