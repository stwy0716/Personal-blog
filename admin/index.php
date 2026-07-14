<?php
require_once __DIR__ . '/auth.php';
handleAdminAuth();

$statsFile = __DIR__ . '/../data/stats.json';
$today = date('Y-m-d');
$stats = readJsonFile($statsFile);
$todayStats = $stats[$today] ?? ['pv' => 0, 'uv' => 0];

$diaries = readJsonFile(__DIR__ . '/../data/diaries.json');
$messages = readJsonFile(__DIR__ . '/../data/guestbook.json');
$commentsFile = __DIR__ . '/../data/diary_comments.json';
$comments = readJsonFile($commentsFile);
$musicFile = __DIR__ . '/../data/music.json';
$musicList = readJsonFile($musicFile);
$operationLogs = getOperationLogs(5);

// 最近7天访问统计
$chartDays = [];
$chartData = [];
$maxVal = 1;
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $chartDays[] = date('m-d', strtotime("-$i days"));
    $pv = $stats[$day]['pv'] ?? 0;
    $uv = $stats[$day]['uv'] ?? 0;
    $chartData[] = ['pv' => $pv, 'uv' => $uv];
    $maxVal = max($maxVal, $pv, $uv);
}

$admin_page_title = '仪表盘';
include __DIR__ . '/admin_header.php';
?>
    <div class="max-w-6xl mx-auto px-6 py-8">
        <h1 class="text-4xl font-bold tracking-tight mb-1">仪表盘</h1>
        <p class="text-zinc-500 mb-8">欢迎回来，管理员</p>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-5">
                <div class="text-sm text-zinc-500">今日访问量</div>
                <div class="text-3xl font-bold mt-1"><?= $todayStats['pv'] ?></div>
            </div>
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-5">
                <div class="text-sm text-zinc-500">日记</div>
                <div class="text-3xl font-bold mt-1"><?= count($diaries) ?></div>
            </div>
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-5">
                <div class="text-sm text-zinc-500">留言</div>
                <div class="text-3xl font-bold mt-1"><?= count($messages) ?></div>
            </div>
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-5">
                <div class="text-sm text-zinc-500">评论</div>
                <div class="text-3xl font-bold mt-1"><?= count($comments) ?></div>
            </div>
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-5">
                <div class="text-sm text-zinc-500">音乐</div>
                <div class="text-3xl font-bold mt-1"><?= count($musicList) ?></div>
            </div>
        </div>

        <!-- Quick Access -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="content.php" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-5 hover:border-indigo-300 transition-all flex items-center gap-x-4">
                    <div class="w-11 h-11 bg-indigo-100 dark:bg-indigo-900 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-edit text-indigo-600 text-xl"></i>
                    </div>
                    <div><div class="font-semibold">内容管理</div><div class="text-xs text-zinc-500">站点信息、关于、卡片、页脚</div></div>
                </a>
                <a href="diary.php" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-5 hover:border-emerald-300 transition-all flex items-center gap-x-4">
                    <div class="w-11 h-11 bg-emerald-100 dark:bg-emerald-900 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-book text-emerald-600 text-xl"></i>
                    </div>
                    <div><div class="font-semibold">日记管理</div><div class="text-xs text-zinc-500">富文本书日记</div></div>
                </a>
                <a href="guestbook.php" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-5 hover:border-violet-300 transition-all flex items-center gap-x-4">
                    <div class="w-11 h-11 bg-violet-100 dark:bg-violet-900 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-comments text-violet-600 text-xl"></i>
                    </div>
                    <div><div class="font-semibold">留言板</div><div class="text-xs text-zinc-500">留言与回复</div></div>
                </a>
                <a href="diary_comments.php" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-5 hover:border-orange-300 transition-all flex items-center gap-x-4">
                    <div class="w-11 h-11 bg-orange-100 dark:bg-orange-900 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-comment-dots text-orange-600 text-xl"></i>
                    </div>
                    <div><div class="font-semibold">评论管理</div><div class="text-xs text-zinc-500">编辑、隐藏、删除</div></div>
                </a>
                <a href="music.php" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-5 hover:border-pink-300 transition-all flex items-center gap-x-4">
                    <div class="w-11 h-11 bg-pink-100 dark:bg-pink-900 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-music text-pink-600 text-xl"></i>
                    </div>
                    <div><div class="font-semibold">音乐管理</div><div class="text-xs text-zinc-500">上传与管理曲目</div></div>
                </a>
                <a href="backup.php" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-5 hover:border-cyan-300 transition-all flex items-center gap-x-4">
                    <div class="w-11 h-11 bg-cyan-100 dark:bg-cyan-900 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-download text-cyan-600 text-xl"></i>
                    </div>
                    <div><div class="font-semibold">数据备份</div><div class="text-xs text-zinc-500">备份与恢复数据</div></div>
                </a>
                <a href="settings.php" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-5 hover:border-indigo-300 transition-all flex items-center gap-x-4 md:col-span-2">
                    <div class="w-11 h-11 bg-indigo-100 dark:bg-indigo-900 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-sliders text-indigo-600 text-xl"></i>
                    </div>
                    <div><div class="font-semibold">设置</div><div class="text-xs text-zinc-500">前台功能与界面</div></div>
                </a>
            </div>
            <div class="md:col-span-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-5 flex flex-col">
                <h3 class="font-semibold mb-4 flex items-center gap-x-2">
                    <i class="fa-solid fa-chart-bar text-indigo-500"></i> 近7天
                </h3>
                <div class="flex-1 flex items-end justify-between gap-x-2" style="min-height: 160px;">
                    <?php foreach ($chartData as $idx => $d): 
                        $pvH = $maxVal > 0 ? round(($d['pv'] / $maxVal) * 100) : 0;
                        $uvH = $maxVal > 0 ? round(($d['uv'] / $maxVal) * 100) : 0;
                    ?>
                    <div class="flex-1 flex flex-col items-center justify-end gap-y-1">
                        <div class="w-full flex items-end justify-center gap-x-0.5" style="height: 120px;">
                            <div class="w-full max-w-[14px] rounded-t-md bg-indigo-400 dark:bg-indigo-600 relative group" style="height: <?= max(4, $pvH) ?>%;">
                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block text-[10px] bg-zinc-800 text-white px-1.5 py-0.5 rounded whitespace-nowrap z-10">PV: <?= $d['pv'] ?></div>
                            </div>
                            <div class="w-full max-w-[14px] rounded-t-md bg-emerald-400 dark:bg-emerald-600 relative group" style="height: <?= max(4, $uvH) ?>%;">
                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block text-[10px] bg-zinc-800 text-white px-1.5 py-0.5 rounded whitespace-nowrap z-10">UV: <?= $d['uv'] ?></div>
                            </div>
                        </div>
                        <span class="text-[10px] text-zinc-400"><?= $chartDays[$idx] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex items-center justify-center gap-x-4 mt-3 text-xs text-zinc-500">
                    <span class="flex items-center gap-x-1"><span class="w-2 h-2 rounded-sm bg-indigo-400 dark:bg-indigo-600 inline-block"></span> PV</span>
                    <span class="flex items-center gap-x-1"><span class="w-2 h-2 rounded-sm bg-emerald-400 dark:bg-emerald-600 inline-block"></span> UV</span>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-semibold text-xl"><i class="fa-solid fa-history text-orange-500 mr-2"></i>最近活动</h3>
                <a href="logs.php" class="text-sm text-orange-600 hover:text-orange-500">查看全部 &rarr;</a>
            </div>
            <?php if (empty($operationLogs)): ?>
                <p class="text-zinc-400">暂无活动记录</p>
            <?php else: ?>
                <div class="space-y-2 text-sm">
                    <?php foreach ($operationLogs as $log): ?>
                        <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800 rounded-xl">
                            <div><span class="font-medium"><?= htmlspecialchars($log['action']) ?></span><span class="text-zinc-500 ml-2"><?= htmlspecialchars($log['description']) ?></span></div>
                            <div class="text-xs text-zinc-400 font-mono"><?= htmlspecialchars($log['time']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>