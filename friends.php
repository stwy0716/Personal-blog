<?php
/**
 * 前台友情链接页面
 * 从 friends.json 读取友情链接，卡片式网格展示
 */
$page_title = '友情链接';
include __DIR__ . '/includes/header.php';

$friendsFile = __DIR__ . '/data/friends.json';
$friends = readJsonFile($friendsFile);
usort($friends, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
?>

<div class="max-w-5xl mx-auto px-6 pt-12 pb-16">
    <div class="text-center mb-10">
        <div class="inline-flex items-center gap-x-2 px-4 py-1.5 rounded-3xl bg-pink-50 dark:bg-pink-950 text-pink-600 dark:text-pink-400 text-sm font-medium mb-4">
            <i class="fa-solid fa-link"></i>
            <span>友情链接</span>
        </div>
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight mb-3">我的朋友们</h1>
        <p class="text-gray-500 dark:text-gray-400 max-w-xl mx-auto">收录了我欣赏的站点和朋友们的主页，欢迎互相交流学习。</p>
    </div>

    <?php if (empty($friends)): ?>
        <div class="text-center py-20">
            <div class="w-20 h-20 rounded-3xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-link-slash text-gray-300 dark:text-gray-600 text-2xl"></i>
            </div>
            <p class="text-gray-400">暂无友情链接</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($friends as $f): ?>
                <a href="<?= sanitizeHtml($f['url']) ?>" target="_blank" rel="noopener noreferrer"
                   class="group glass-card rounded-3xl p-6 flex flex-col items-center text-center hover:border-indigo-200 dark:hover:border-indigo-800 transition-all">
                    <?php if (!empty($f['avatar'])): ?>
                        <img src="<?= sanitizeHtml($f['avatar']) ?>" alt="<?= sanitizeHtml($f['name']) ?>"
                             class="w-16 h-16 rounded-2xl object-cover mb-4 ring-2 ring-gray-100 dark:ring-gray-700 group-hover:ring-indigo-200 dark:group-hover:ring-indigo-800 transition-all">
                    <?php else: ?>
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center mb-4 text-white text-2xl font-bold">
                            <?= mb_substr($f['name'], 0, 1) ?>
                        </div>
                    <?php endif; ?>
                    <h3 class="font-semibold text-lg mb-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                        <?= sanitizeHtml($f['name']) ?>
                    </h3>
                    <?php if (!empty($f['description'])): ?>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mb-3 line-clamp-2">
                            <?= sanitizeHtml($f['description']) ?>
                        </p>
                    <?php endif; ?>
                    <div class="mt-auto flex items-center gap-x-1 text-xs text-gray-400 group-hover:text-indigo-500 transition-colors">
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                        <span>访问</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
