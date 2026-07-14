<?php
require_once __DIR__ . '/auth.php';
handleAdminAuth();

$backupDir = __DIR__ . '/../backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$message = '';
$error = '';

// 处理恢复
if (isset($_POST['restore_backup']) && isset($_POST['restore_file'])) {
    verifyPostCsrf();

    $restoreFile = $backupDir . '/' . basename($_POST['restore_file']);

    if (file_exists($restoreFile) && pathinfo($restoreFile, PATHINFO_EXTENSION) === 'zip') {
        $zip = new ZipArchive();
        if ($zip->open($restoreFile) === TRUE) {
            // Zip Slip 防护：检查ZIP内所有文件路径
            $safe = true;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (strpos($name, '..') !== false || substr($name, 0, 1) === '/') {
                    $safe = false;
                    break;
                }
            }

            if (!$safe) {
                $error = '检测到不安全的备份文件，已阻止路径遍历攻击。';
            } else {
                $dataDir = __DIR__ . '/../data';
                // 恢复前自动备份当前数据
                $tempBackup = $backupDir . '/before_restore_' . date('Ymd_His') . '.zip';
                $zipBackup = new ZipArchive();
                if ($zipBackup->open($tempBackup, ZipArchive::CREATE) === TRUE) {
                    $files = glob($dataDir . '/*.json');
                    foreach ($files as $file) {
                        $zipBackup->addFile($file, basename($file));
                    }
                    $zipBackup->close();
                }

                $zip->extractTo($dataDir);
                $zip->close();

                logOperation('backup_restore', '从备份恢复数据：' . basename($restoreFile));
                $message = '数据恢复成功！恢复前的数据已自动备份。';
            }
        } else {
            $error = '无法打开备份文件';
        }
    } else {
        $error = '备份文件不存在或格式无效';
    }
}

if (isset($_POST['create_backup'])) {
    verifyPostCsrf();

    $timestamp = date('Ymd_His');
    $backupName = "backup_{$timestamp}.zip";
    $zipPath = $backupDir . '/' . $backupName;

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
        $dataDir = __DIR__ . '/../data';
        $files = glob($dataDir . '/*.json');
        foreach ($files as $file) {
            // 排除 admin.json（包含密码哈希）和 stats.json（临时统计数据）
            $basename = basename($file);
            if ($basename === 'admin.json' || $basename === 'stats.json') continue;
            $zip->addFile($file, basename($file));
        }
        $zip->close();
        logOperation('backup_create', '创建了数据备份：' . $backupName);
        $message = '备份已创建：' . $backupName;
    } else {
        $message = '备份失败';
    }
}

$backups = glob($backupDir . '/*.zip');
rsort($backups);
$admin_page_title = '数据备份';
include __DIR__ . '/admin_header.php';
?>
    <div class="max-w-4xl mx-auto px-6 py-8">
        <h1 class="text-3xl font-bold tracking-tight mb-8">数据备份</h1>

        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-3xl"><?= sanitizeHtml($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-3xl"><?= sanitizeHtml($error) ?></div>
        <?php endif; ?>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-3xl p-7 mb-8">
            <h3 class="font-semibold text-lg mb-4">创建新备份</h3>
            <form method="POST">
                <?= csrfField() ?>
                <button type="submit" name="create_backup"
                        class="px-8 h-12 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold flex items-center gap-x-2">
                    <i class="fa-solid fa-download"></i>
                    <span>备份所有数据（JSON）</span>
                </button>
                <p class="text-xs text-zinc-500 mt-2">出于安全考虑，不含管理员凭据和临时统计数据。</p>
            </form>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-3xl p-7">
            <h3 class="font-semibold text-lg mb-4">备份历史</h3>
            <?php if (empty($backups)): ?>
                <p class="text-zinc-400">暂无备份</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach (array_slice($backups, 0, 10) as $backup): ?>
                        <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800 rounded-2xl text-sm">
                            <div class="font-mono"><?= sanitizeHtml(basename($backup)) ?></div>
                            <div class="flex items-center gap-x-3">
                                <span class="text-xs text-zinc-500"><?= date('Y-m-d H:i', filemtime($backup)) ?></span>
                                <form method="POST" class="inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="restore_file" value="<?= sanitizeHtml(basename($backup)) ?>">
                                    <button type="submit" name="restore_backup" onclick="return confirm('确认从此备份恢复？当前数据将先进行备份。')"
                                            class="text-xs px-3 py-1 bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300 rounded-xl hover:bg-amber-200">
                                        恢复
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
