<?php
require_once __DIR__ . '/auth.php';
handleAdminAuth();

$allLogs = getOperationLogs(200);

// Search
$search = trim($_GET['search'] ?? '');
if ($search) {
    $logs = array_filter($allLogs, function($log) use ($search) {
        return stripos($log['action'] ?? '', $search) !== false ||
               stripos($log['description'] ?? '', $search) !== false;
    });
} else {
    $logs = $allLogs;
}

// Pagination
$pageSize = 20;
$totalLogs = count($logs);
$totalPages = max(1, (int)ceil($totalLogs / $pageSize));
$currentPage = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$offset = ($currentPage - 1) * $pageSize;
$logs = array_slice($logs, $offset, $pageSize);
$admin_page_title = '操作日志';
include __DIR__ . '/admin_header.php';
?>
    <div class="max-w-6xl mx-auto px-6 py-8">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold tracking-tight">操作日志</h1>
            <a href="export.php?type=operation" class="px-4 py-2 bg-orange-600 text-white rounded-2xl text-sm flex items-center gap-x-2">
                <i class="fa-solid fa-download"></i> <span>导出 CSV</span>
            </a>
        </div>

        <!-- Search -->
        <div class="mb-6 max-w-md">
            <form method="GET" class="relative">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       class="w-full pl-10 pr-4 py-3 rounded-2xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-900 focus:outline-none focus:border-orange-400 transition"
                       placeholder="搜索日志...">
                <i class="fa-solid fa-search absolute left-4 top-3.5 text-zinc-400"></i>
            </form>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-3xl p-7">
            <?php if (empty($logs)): ?>
                <p class="text-zinc-400">暂无活动记录</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-xs text-zinc-500">
                                <th class="py-3 pr-6">时间</th>
                                <th class="py-3 pr-6">操作</th>
                                <th class="py-3 pr-6">描述</th>
                                <th class="py-3">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="py-3 pr-6 font-mono text-xs text-zinc-500"><?= htmlspecialchars($log['time']) ?></td>
                                <td class="py-3 pr-6">
                                    <span class="px-3 py-1 text-xs rounded-xl bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 font-medium">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </span>
                                </td>
                                <td class="py-3 pr-6 text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($log['description']) ?></td>
                                <td class="py-3 text-xs font-mono text-zinc-500"><?= htmlspecialchars($log['ip'] ?? '') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalPages > 1): ?>
                <div class="flex items-center justify-between mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <span class="text-xs text-zinc-500">
                        第 <?= $currentPage ?> / <?= $totalPages ?> 页（共 <?= $totalLogs ?> 条）
                    </span>
                    <div class="flex items-center gap-x-2">
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=<?= $currentPage - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                               class="px-4 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                                上一页
                            </a>
                        <?php else: ?>
                            <span class="px-4 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 text-zinc-400 cursor-not-allowed">上一页</span>
                        <?php endif; ?>
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?= $currentPage + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                               class="px-4 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                                下一页
                            </a>
                        <?php else: ?>
                            <span class="px-4 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 text-zinc-400 cursor-not-allowed">下一页</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>