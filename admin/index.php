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

// 最近14天访问统计（用于 Chart.js）
$chartDays14 = [];
$chartData14 = [];
for ($i = 13; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $chartDays14[] = date('m-d', strtotime("-$i days"));
    $pv = $stats[$day]['pv'] ?? 0;
    $uv = $stats[$day]['uv'] ?? 0;
    $chartData14[] = ['pv' => $pv, 'uv' => $uv];
}

// 最近7天访问统计（保留原有CSS柱状图数据）
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

// 热门日记（按浏览量降序取前5）
$popularDiaries = $diaries;
usort($popularDiaries, fn($a, $b) => ($b['views'] ?? 0) <=> ($a['views'] ?? 0));
$popularDiaries = array_slice($popularDiaries, 0, 5);

// 评论摘要统计
$totalComments = count($comments);
$totalGuestbook = count($messages);
$pendingCount = 0;
foreach ($comments as $c) {
    if (($c['status'] ?? 'published') === 'pending') $pendingCount++;
}
foreach ($messages as $m) {
    if (($m['status'] ?? 'published') === 'pending') $pendingCount++;
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
                    <i class="fa-solid fa-chart-line text-indigo-500"></i> 近14天趋势
                </h3>
                <div class="flex-1" style="min-height: 200px;">
                    <canvas id="visitTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts & Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="md:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-5">
                <h3 class="font-semibold mb-4 flex items-center gap-x-2">
                    <i class="fa-solid fa-fire text-orange-500"></i> 热门日记 Top 5
                </h3>
                <div style="min-height: 200px;">
                    <canvas id="popularDiariesChart"></canvas>
                </div>
            </div>
            <div class="md:col-span-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-5 flex flex-col">
                <h3 class="font-semibold mb-4 flex items-center gap-x-2">
                    <i class="fa-solid fa-comments text-violet-500"></i> 评论摘要
                </h3>
                <div class="flex-1 flex flex-col justify-center gap-y-4">
                    <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl">
                        <span class="text-sm text-zinc-500">日记评论</span>
                        <span class="text-2xl font-bold"><?= $totalComments ?></span>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl">
                        <span class="text-sm text-zinc-500">留言板消息</span>
                        <span class="text-2xl font-bold"><?= $totalGuestbook ?></span>
                    </div>
                    <?php if ($pendingCount > 0): ?>
                    <div class="flex items-center justify-between p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                        <span class="text-sm text-amber-600 dark:text-amber-400">待审核</span>
                        <span class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?= $pendingCount ?></span>
                    </div>
                    <?php endif; ?>
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

    <script>
    (function() {
        const isDark = document.documentElement.classList.contains('dark');
        const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)';
        const textColor = isDark ? '#a1a1aa' : '#71717a';
        const tickColor = isDark ? '#d4d4d8' : '#3f3f46';

        // 访问趋势图
        const visitCtx = document.getElementById('visitTrendChart');
        if (visitCtx) {
            new Chart(visitCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($chartDays14) ?>,
                    datasets: [
                        {
                            label: 'PV',
                            data: <?= json_encode(array_column($chartData14, 'pv')) ?>,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true,
                            pointRadius: 3,
                            pointHoverRadius: 5
                        },
                        {
                            label: 'UV',
                            data: <?= json_encode(array_column($chartData14, 'uv')) ?>,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true,
                            pointRadius: 3,
                            pointHoverRadius: 5
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: { color: textColor }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: gridColor },
                            ticks: { color: tickColor, font: { size: 10 } }
                        },
                        y: {
                            grid: { color: gridColor },
                            ticks: { color: tickColor, font: { size: 10 } },
                            beginAtZero: true
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    }
                }
            });
        }

        // 热门日记图
        const diaryCtx = document.getElementById('popularDiariesChart');
        if (diaryCtx) {
            new Chart(diaryCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_map(fn($d) => mb_strimwidth($d['title'] ?? '未命名', 0, 20, '...'), $popularDiaries)) ?>,
                    datasets: [{
                        label: '浏览量',
                        data: <?= json_encode(array_map(fn($d) => $d['views'] ?? 0, $popularDiaries)) ?>,
                        backgroundColor: '#f59e0b',
                        borderRadius: 4,
                        barThickness: 20
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            grid: { color: gridColor },
                            ticks: { color: tickColor, font: { size: 10 } },
                            beginAtZero: true
                        },
                        y: {
                            grid: { display: false },
                            ticks: { color: tickColor, font: { size: 11 } }
                        }
                    }
                }
            });
        }
    })();
    </script>
</body>
</html>