<?php
require_once __DIR__ . '/auth.php';
handleAdminAuth();

$contentFile = __DIR__ . '/../data/content.json';
$content = readJsonFile($contentFile);

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_content'])) {
    $newContent = [
        'site' => [
            'title' => trim($_POST['site_title'] ?? 'My Personal Space'),
            'subtitle' => trim($_POST['site_subtitle'] ?? ''),
            'avatar' => trim($_POST['site_avatar'] ?? $content['site']['avatar'] ?? ''),
            'title_prefix' => trim($_POST['site_prefix'] ?? 'Hi, I\'m')
        ],
        'blog_intro' => trim($_POST['blog_intro'] ?? ''),
        'about' => [
            'intro' => trim($_POST['about_intro'] ?? ''),
            'timeline' => $content['about']['timeline'] ?? []
        ],
        'skills' => [],
        'contact' => [
            'email' => trim($_POST['contact_email'] ?? ''),
            'wechat' => trim($_POST['contact_wechat'] ?? ''),
            'github' => trim($_POST['contact_github'] ?? ''),
            'qq' => trim($_POST['contact_qq'] ?? ''),
            'bilibili' => trim($_POST['contact_bilibili'] ?? '')
        ],
        'homepage' => [
            'explore_title' => trim($_POST['explore_title'] ?? 'Explore My World'),
            'card_about_title' => trim($_POST['card_about_title'] ?? 'About Me'),
            'card_about_desc' => trim($_POST['card_about_desc'] ?? ''),
            'card_diary_title' => trim($_POST['card_diary_title'] ?? 'Journal'),
            'card_diary_desc' => trim($_POST['card_diary_desc'] ?? ''),
            'card_guestbook_title' => trim($_POST['card_guestbook_title'] ?? 'Guestbook'),
            'card_guestbook_desc' => trim($_POST['card_guestbook_desc'] ?? '')
        ],
        'footer' => [
            'text' => trim($_POST['footer_text'] ?? ''),
            'icp' => trim($_POST['footer_icp'] ?? '')
        ],
        'social_links' => [
            'github' => trim($_POST['social_github'] ?? ''),
            'bilibili' => trim($_POST['social_bilibili'] ?? ''),
            'zhihu' => trim($_POST['social_zhihu'] ?? ''),
            'twitter' => trim($_POST['social_twitter'] ?? ''),
            'weibo' => trim($_POST['social_weibo'] ?? '')
        ],
        'seo' => [
            'description' => trim($_POST['seo_description'] ?? ''),
            'keywords' => trim($_POST['seo_keywords'] ?? '')
        ],
        'nav' => $content['nav'] ?? []
    ];
    
    // Parse timeline
    $timelineRaw = trim($_POST['timeline_raw'] ?? '');
    if ($timelineRaw) {
        $newContent['about']['timeline'] = [];
        $lines = explode("\n", $timelineRaw);
        foreach ($lines as $line) {
            $parts = explode('|', trim($line), 3);
            if (count($parts) >= 3) {
                $newContent['about']['timeline'][] = [
                    'year' => trim($parts[0]),
                    'title' => trim($parts[1]),
                    'description' => trim($parts[2])
                ];
            }
        }
    }
    
    $skillsRaw = trim($_POST['skills_raw'] ?? '');
    if ($skillsRaw) {
        $newContent['skills'] = array_filter(array_map('trim', explode(',', $skillsRaw)));
    }
    
    if (writeJsonFile($contentFile, $newContent)) {
        $success = '内容保存成功！';
        logOperation('content_update', '更新了站点内容');
        $content = $newContent;
    } else {
        $error = '保存失败，请检查 data/ 目录权限。';
    }
}

$content = readJsonFile($contentFile);
$site = $content['site'] ?? [];
$blogIntro = $content['blog_intro'] ?? '';
$about = $content['about'] ?? ['intro' => '', 'timeline' => []];
$skills = $content['skills'] ?? [];
$contact = $content['contact'] ?? [];
$homepage = $content['homepage'] ?? [];
$footer = $content['footer'] ?? [];
$socialLinks = $content['social_links'] ?? [];
$seo = $content['seo'] ?? [];

$timelineRaw = '';
foreach ($about['timeline'] as $t) {
    $timelineRaw .= "{$t['year']}|{$t['title']}|{$t['description']}\n";
}
$skillsRaw = implode(', ', $skills);

$admin_page_title = '内容管理';
include __DIR__ . '/admin_header.php';
?>
    <div class="max-w-4xl mx-auto px-6 py-8">
        <h1 class="text-3xl font-bold tracking-tight mb-1">内容管理</h1>
        <p class="text-sm text-zinc-500 mb-6">管理所有前台内容，刷新后生效。</p>

        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-2xl flex items-center gap-x-3">
                <i class="fa-solid fa-check-circle"></i> <span><?= sanitizeHtml($success) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-2xl flex items-center gap-x-3">
                <i class="fa-solid fa-exclamation-triangle"></i> <span><?= sanitizeHtml($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-8">
                <?= csrfField() ?>
            <!-- Site Info -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
                <h3 class="font-semibold text-lg mb-5 flex items-center gap-x-2">
                    <i class="fa-solid fa-globe text-indigo-500"></i> 站点信息
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1.5">站点标题</label>
                    <input type="text" name="site_title" value="<?= htmlspecialchars($site['title'] ?? '') ?>" class="w-full px-4 h-11 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent"></div>
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1.5">副标题</label>
                    <input type="text" name="site_subtitle" value="<?= htmlspecialchars($site['subtitle'] ?? '') ?>" class="w-full px-4 h-11 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent"></div>
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1.5">头像链接</label>
                    <input type="text" name="site_avatar" value="<?= htmlspecialchars($site['avatar'] ?? '') ?>" class="w-full px-4 h-11 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent"></div>
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1.5">标题前缀</label>
                    <input type="text" name="site_prefix" value="<?= htmlspecialchars($site['title_prefix'] ?? '') ?>" class="w-full px-4 h-11 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent"></div>
                </div>
            </div>

            <!-- SEO -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
                <h3 class="font-semibold text-lg mb-5 flex items-center gap-x-2">
                    <i class="fa-solid fa-search text-cyan-500"></i> SEO 设置
                </h3>
                <div class="grid grid-cols-1 gap-5">
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1.5">Meta 描述</label>
                    <textarea name="seo_description" rows="2" class="w-full px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent"><?= htmlspecialchars($seo['description'] ?? '') ?></textarea></div>
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1.5">关键词（逗号分隔）</label>
                    <input type="text" name="seo_keywords" value="<?= htmlspecialchars($seo['keywords'] ?? '') ?>" class="w-full px-4 h-11 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent"></div>
                </div>
            </div>

            <!-- Homepage Cards -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
                <h3 class="font-semibold text-lg mb-5 flex items-center gap-x-2">
                    <i class="fa-solid fa-home text-emerald-500"></i> 首页卡片
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2"><label class="text-xs font-medium text-zinc-500 block mb-1.5">探索区块标题</label>
                    <input type="text" name="explore_title" value="<?= htmlspecialchars($homepage['explore_title'] ?? 'Explore My World') ?>" class="w-full px-4 h-11 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent"></div>
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1.5">关于卡片标题</label>
                    <input type="text" name="card_about_title" value="<?= htmlspecialchars($homepage['card_about_title'] ?? '') ?>" class="w-full px-4 h-11 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent"></div>
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1.5">关于卡片描述</label>
                    <input type="text" name="card_about_desc" value="<?= htmlspecialchars($homepage['card_about_desc'] ?? '') ?>" class="w-full px-4 h-11 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent"></div>
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1.5">日记卡片标题</label>
                    <input type="text" name="card_diary_title" value="<?= htmlspecialchars($homepage['card_diary_title'] ?? '') ?>" class="w-full px-4 h-11 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent"></div>
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1.5">日记卡片描述</label>
                    <input type="text" name="card_diary_desc" value="<?= htmlspecialchars($homepage['card_diary_desc'] ?? '') ?>" class="w-full px-4 h-11 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent"></div>
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1.5">留言板卡片标题</label>
                    <input type="text" name="card_guestbook_title" value="<?= htmlspecialchars($homepage['card_guestbook_title'] ?? '') ?>" class="w-full px-4 h-11 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent"></div>
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1.5">留言板卡片描述</label>
                    <input type="text" name="card_guestbook_desc" value="<?= htmlspecialchars($homepage['card_guestbook_desc'] ?? '') ?>" class="w-full px-4 h-11 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent"></div>
                </div>
            </div>

            <!-- Blog Intro -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
                <h3 class="font-semibold text-lg mb-5 flex items-center gap-x-2">
                    <i class="fa-solid fa-book text-amber-500"></i> 博客简介
                </h3>
                <textarea name="blog_intro" rows="4" class="w-full px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent"><?= htmlspecialchars($blogIntro) ?></textarea>
            </div>

            <!-- About -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
                <h3 class="font-semibold text-lg mb-5 flex items-center gap-x-2">
                    <i class="fa-solid fa-user text-blue-500"></i> 关于我
                </h3>
                <div class="mb-5">
                    <label class="text-xs font-medium text-zinc-500 block mb-1.5">个人简介</label>
                    <textarea name="about_intro" rows="3" class="w-full px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent"><?= htmlspecialchars($about['intro'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="text-xs font-medium text-zinc-500 block mb-1.5">时间线（每行格式：年份|标题|描述）</label>
                    <textarea name="timeline_raw" rows="6" class="w-full px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent font-mono text-sm"><?= htmlspecialchars($timelineRaw) ?></textarea>
                </div>
            </div>

            <!-- Skills & Contact -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
                    <h3 class="font-semibold text-lg mb-5 flex items-center gap-x-2">
                        <i class="fa-solid fa-cogs text-purple-500"></i> 技能
                    </h3>
                    <textarea name="skills_raw" rows="4" class="w-full px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent"><?= htmlspecialchars($skillsRaw) ?></textarea>
                    <p class="text-[10px] text-zinc-400 mt-1">逗号分隔</p>
                </div>
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
                    <h3 class="font-semibold text-lg mb-5 flex items-center gap-x-2">
                        <i class="fa-solid fa-envelope text-red-500"></i> 联系方式
                    </h3>
                    <div class="space-y-3">
                        <div><label class="text-xs font-medium text-zinc-500 block mb-1">邮箱</label>
                        <input type="text" name="contact_email" value="<?= htmlspecialchars($contact['email'] ?? '') ?>" class="w-full px-4 h-10 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent text-sm"></div>
                        <div><label class="text-xs font-medium text-zinc-500 block mb-1">微信</label>
                        <input type="text" name="contact_wechat" value="<?= htmlspecialchars($contact['wechat'] ?? '') ?>" class="w-full px-4 h-10 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent text-sm"></div>
                        <div><label class="text-xs font-medium text-zinc-500 block mb-1">GitHub</label>
                        <input type="text" name="contact_github" value="<?= htmlspecialchars($contact['github'] ?? '') ?>" class="w-full px-4 h-10 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent text-sm"></div>
                        <div><label class="text-xs font-medium text-zinc-500 block mb-1">QQ</label>
                        <input type="text" name="contact_qq" value="<?= htmlspecialchars($contact['qq'] ?? '') ?>" class="w-full px-4 h-10 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent text-sm"></div>
                        <div><label class="text-xs font-medium text-zinc-500 block mb-1">B站</label>
                        <input type="text" name="contact_bilibili" value="<?= htmlspecialchars($contact['bilibili'] ?? '') ?>" class="w-full px-4 h-10 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent text-sm"></div>
                    </div>
                </div>
            </div>

            <!-- Social Links -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
                <h3 class="font-semibold text-lg mb-5 flex items-center gap-x-2">
                    <i class="fa-solid fa-share-nodes text-pink-500"></i> 社交链接（在页脚显示）
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1">GitHub 链接</label>
                    <input type="text" name="social_github" value="<?= htmlspecialchars($socialLinks['github'] ?? '') ?>" class="w-full px-4 h-10 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent text-sm"></div>
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1">B站链接</label>
                    <input type="text" name="social_bilibili" value="<?= htmlspecialchars($socialLinks['bilibili'] ?? '') ?>" class="w-full px-4 h-10 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent text-sm"></div>
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1">知乎链接</label>
                    <input type="text" name="social_zhihu" value="<?= htmlspecialchars($socialLinks['zhihu'] ?? '') ?>" class="w-full px-4 h-10 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent text-sm"></div>
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1">Twitter/X 链接</label>
                    <input type="text" name="social_twitter" value="<?= htmlspecialchars($socialLinks['twitter'] ?? '') ?>" class="w-full px-4 h-10 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent text-sm"></div>
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1">微博链接</label>
                    <input type="text" name="social_weibo" value="<?= htmlspecialchars($socialLinks['weibo'] ?? '') ?>" class="w-full px-4 h-10 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent text-sm"></div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
                <h3 class="font-semibold text-lg mb-5 flex items-center gap-x-2">
                    <i class="fa-solid fa-shoe-prints text-zinc-500"></i> 页脚
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1.5">页脚文字</label>
                    <input type="text" name="footer_text" value="<?= htmlspecialchars($footer['text'] ?? '') ?>" class="w-full px-4 h-11 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent" placeholder="例如：个人空间，用心构建。"></div>
                    <div><label class="text-xs font-medium text-zinc-500 block mb-1.5">ICP/备案号</label>
                    <input type="text" name="footer_icp" value="<?= htmlspecialchars($footer['icp'] ?? '') ?>" class="w-full px-4 h-11 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent" placeholder="选填"></div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" name="save_content" class="px-10 h-12 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold flex items-center gap-x-2">
                    <i class="fa-solid fa-save"></i> <span>保存所有更改</span>
                </button>
            </div>
        </form>
    </div>
</body>
</html>