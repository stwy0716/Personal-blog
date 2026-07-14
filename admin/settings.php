<?php
/**
 * 后台设置中心
 * 集中控制前台功能开关和UI设置
 */
require_once __DIR__ . '/auth.php';
handleAdminAuth();

$contentFile = __DIR__ . '/../data/content.json';
$content = readJsonFile($contentFile);
$settings = $content['settings'] ?? [];

// 默认设置
$defaults = [
    'glassmorphism_enabled' => true,       // 毛玻璃效果（前台卡片、评论区等）
    'glassmorphism_blur' => 16,            // 模糊强度 (px)
    'glassmorphism_opacity' => 0.72,       // 透明度 (0-1)
    'page_loader_enabled' => true,         // 页面加载动画
    'music_autoplay' => false,             // 音乐自动播放
    'reading_progress' => true,             // 阅读进度条
    'dark_mode_toggle' => true,            // 前台深色模式切换按钮
    'diary_front_edit' => true,            // 前台日记编辑（仅管理员可见）
    'guestbook_enabled' => true,           // 留言板功能
    'diary_comments_enabled' => true,      // 日记评论功能
    'comment_rich_text' => true,            // 富文本评论
    'footer_social_links' => true,          // 页脚社交链接
    'footer_music_player' => true,          // 音乐播放器
    'music_shuffle' => false,               // 音乐随机播放
    'music_loop' => true,                  // 音乐循环播放
    'theme_color' => 'indigo',              // 主题色
    'language' => 'zh',                     // 站点语言
];

$settings = array_merge($defaults, $settings);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    verifyPostCsrf();

    $newSettings = [
        'glassmorphism_enabled' => isset($_POST['glassmorphism_enabled']),
        'glassmorphism_blur' => max(0, min(30, (int)($_POST['glassmorphism_blur'] ?? 16))),
        'glassmorphism_opacity' => max(0, min(1, (float)($_POST['glassmorphism_opacity'] ?? 0.72))),
        'page_loader_enabled' => isset($_POST['page_loader_enabled']),
        'music_autoplay' => isset($_POST['music_autoplay']),
        'reading_progress' => isset($_POST['reading_progress']),
        'dark_mode_toggle' => isset($_POST['dark_mode_toggle']),
        'diary_front_edit' => isset($_POST['diary_front_edit']),
        'guestbook_enabled' => isset($_POST['guestbook_enabled']),
        'diary_comments_enabled' => isset($_POST['diary_comments_enabled']),
        'comment_rich_text' => isset($_POST['comment_rich_text']),
        'footer_social_links' => isset($_POST['footer_social_links']),
        'footer_music_player' => isset($_POST['footer_music_player']),
        'music_shuffle' => isset($_POST['music_shuffle']),
        'music_loop' => isset($_POST['music_loop']),
        'theme_color' => in_array($_POST['theme_color'] ?? '', ['indigo', 'emerald', 'rose', 'cyan', 'amber', 'violet']) ? $_POST['theme_color'] : 'indigo',
        'language' => in_array($_POST['language'] ?? '', ['zh', 'en']) ? $_POST['language'] : 'zh',
    ];

    $content['settings'] = $newSettings;
    if (writeJsonFile($contentFile, $content)) {
        $message = '设置已保存！';
        $settings = $newSettings;
        logOperation('settings_update', '更新了站点设置');
    } else {
        $error = '保存失败，请检查权限。';
    }
}

$admin_page_title = '设置';
include __DIR__ . '/admin_header.php';
?>
    <div class="max-w-4xl mx-auto px-6 py-8">
        <h1 class="text-3xl font-bold tracking-tight mb-1">设置</h1>
        <p class="text-zinc-500 mb-8">在此控制所有前台功能和界面外观。</p>

        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-emerald-500/10 text-emerald-400 rounded-2xl flex items-center gap-x-2">
                <i class="fa-solid fa-check-circle"></i> <span><?= sanitizeHtml($message) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-500/10 text-red-400 rounded-2xl flex items-center gap-x-2">
                <i class="fa-solid fa-exclamation-circle"></i> <span><?= sanitizeHtml($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <?= csrfField() ?>

            <!-- UI Appearance -->
            <div class="rounded-2xl border border-zinc-800 p-7">
                <h3 class="font-semibold text-lg mb-6 flex items-center gap-x-2">
                    <i class="fa-solid fa-palette text-pink-400"></i> 界面外观
                </h3>
                <div class="space-y-5">
                    <?php function renderToggle($name, $label, $desc, $checked) { ?>
                    <div class="flex items-start justify-between gap-x-4">
                        <div class="flex-1">
                            <div class="font-medium text-sm"><?= $label ?></div>
                            <div class="text-xs text-zinc-500 mt-0.5"><?= $desc ?></div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 mt-0.5">
                            <input type="checkbox" name="<?= $name ?>" value="1" <?= $checked ? 'checked' : '' ?>
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-zinc-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                    <?php } ?>

                    <?php renderToggle('glassmorphism_enabled', '毛玻璃效果', '在卡片、评论和容器上启用磨砂玻璃效果', $settings['glassmorphism_enabled']); ?>

                    <?php if ($settings['glassmorphism_enabled']): ?>
                    <div class="ml-0.5 pl-6 border-l-2 border-zinc-700 space-y-4">
                        <div>
                            <label class="text-xs text-zinc-400 block mb-1.5">模糊强度（<?= $settings['glassmorphism_blur'] ?>px）</label>
                            <input type="range" name="glassmorphism_blur" min="0" max="30" value="<?= $settings['glassmorphism_blur'] ?>" class="w-full h-2 bg-zinc-700 rounded-lg appearance-none cursor-pointer accent-indigo-500">
                            <div class="flex justify-between text-[10px] text-zinc-600 mt-1"><span>0px（无）</span><span>30px（强）</span></div>
                        </div>
                        <div>
                            <label class="text-xs text-zinc-400 block mb-1.5">透明度（<?= round($settings['glassmorphism_opacity'] * 100) ?>%）</label>
                            <input type="range" name="glassmorphism_opacity" min="10" max="95" value="<?= round($settings['glassmorphism_opacity'] * 100) ?>" class="w-full h-2 bg-zinc-700 rounded-lg appearance-none cursor-pointer accent-indigo-500">
                            <div class="flex justify-between text-[10px] text-zinc-600 mt-1"><span>10%</span><span>95%</span></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php renderToggle('page_loader_enabled', '页面加载动画', '页面加载时的三点弹跳动画', $settings['page_loader_enabled']); ?>
                    <?php renderToggle('reading_progress', '阅读进度条', '在日记详情页显示进度条', $settings['reading_progress']); ?>
                    <?php renderToggle('dark_mode_toggle', '深色模式切换', '在角落显示主题切换按钮', $settings['dark_mode_toggle']); ?>
                </div>
            </div>

            <!-- Theme Color -->
            <div class="rounded-2xl border border-zinc-800 p-7">
                <h3 class="font-semibold text-lg mb-6 flex items-center gap-x-2">
                    <i class="fa-solid fa-paintbrush text-rose-400"></i> 主题色
                </h3>
                <div class="flex flex-wrap gap-4">
                    <?php
                    $themeColors = [
                        'indigo' => '靛蓝',
                        'emerald' => '翡翠绿',
                        'rose' => '玫瑰红',
                        'cyan' => '青色',
                        'amber' => '琥珀色',
                        'violet' => '紫色',
                    ];
                    foreach ($themeColors as $value => $label):
                        $checked = ($settings['theme_color'] ?? 'indigo') === $value;
                    ?>
                    <label class="flex items-center gap-x-2 cursor-pointer">
                        <input type="radio" name="theme_color" value="<?= $value ?>" <?= $checked ? 'checked' : '' ?> class="w-4 h-4 text-indigo-600 border-zinc-600 focus:ring-indigo-500 bg-zinc-800">
                        <span class="text-sm"><?= $label ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Language -->
            <div class="rounded-2xl border border-zinc-800 p-7">
                <h3 class="font-semibold text-lg mb-6 flex items-center gap-x-2">
                    <i class="fa-solid fa-language text-cyan-400"></i> 语言
                </h3>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-x-2 cursor-pointer">
                        <input type="radio" name="language" value="zh" <?= ($settings['language'] ?? 'zh') === 'zh' ? 'checked' : '' ?> class="w-4 h-4 text-indigo-600 border-zinc-600 focus:ring-indigo-500 bg-zinc-800">
                        <span class="text-sm">中文 (Chinese)</span>
                    </label>
                    <label class="flex items-center gap-x-2 cursor-pointer">
                        <input type="radio" name="language" value="en" <?= ($settings['language'] ?? 'zh') === 'en' ? 'checked' : '' ?> class="w-4 h-4 text-indigo-600 border-zinc-600 focus:ring-indigo-500 bg-zinc-800">
                        <span class="text-sm">English</span>
                    </label>
                </div>
            </div>

            <!-- Feature Controls -->
            <div class="rounded-2xl border border-zinc-800 p-7">
                <h3 class="font-semibold text-lg mb-6 flex items-center gap-x-2">
                    <i class="fa-solid fa-sliders text-indigo-400"></i> 功能控制
                </h3>
                <div class="space-y-5">
                    <?php renderToggle('music_autoplay', '音乐自动播放', '页面加载时自动播放音乐（浏览器可能会阻止）', $settings['music_autoplay']); ?>
                    <?php renderToggle('footer_music_player', '音乐播放器', '在页脚显示音乐播放器栏', $settings['footer_music_player']); ?>
                    <?php renderToggle('music_shuffle', '随机播放', '音乐列表随机播放模式', $settings['music_shuffle']); ?>
                    <?php renderToggle('music_loop', '循环播放', '播放列表循环播放', $settings['music_loop']); ?>
                    <?php renderToggle('footer_social_links', '社交链接', '在页脚显示社交媒体图标', $settings['footer_social_links']); ?>
                </div>
            </div>

            <!-- Interactive Features -->
            <div class="rounded-2xl border border-zinc-800 p-7">
                <h3 class="font-semibold text-lg mb-6 flex items-center gap-x-2">
                    <i class="fa-solid fa-comments text-emerald-400"></i> 互动功能
                </h3>
                <div class="space-y-5">
                    <?php renderToggle('guestbook_enabled', '留言板', '启用留言板页面和留言提交', $settings['guestbook_enabled']); ?>
                    <?php renderToggle('diary_comments_enabled', '日记评论', '允许访客评论日记', $settings['diary_comments_enabled']); ?>
                    <?php renderToggle('comment_rich_text', '富文本评论', '允许在评论中使用图片和视频（可能增加XSS风险）', $settings['comment_rich_text']); ?>
                    <?php renderToggle('diary_front_edit', '前台日记编辑', '为已登录管理员在日记页面显示编辑按钮', $settings['diary_front_edit']); ?>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" name="save_settings" class="px-10 h-12 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold flex items-center gap-x-2 transition-colors">
                    <i class="fa-solid fa-save"></i> <span>保存设置</span>
                </button>
            </div>
        </form>
    </div>
</body>
</html>
