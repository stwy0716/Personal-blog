<?php
/**
 * 个人主页安装向导
 * 
 * 检测环境 -> 设置站点信息 -> 完成安装
 * 安装完成后此文件会自动删除
 */

// 防止直接访问被 .htaccess 拦截时的处理
if (isset($_GET['step']) || isset($_GET['action'])) {
    // 允许通过
}

require_once __DIR__ . '/includes/security.php';

$adminJsonPath = __DIR__ . '/data/admin.json';
$contentJsonPath = __DIR__ . '/data/content.json';
$isInstalled = file_exists($adminJsonPath);

// 处理 POST 请求
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CSRF 验证
    verifyPostCsrf();

    if ($action === 'reinstall_confirm') {
        // 重新安装：删除 admin.json 和 content.json
        if (file_exists($adminJsonPath)) {
            unlink($adminJsonPath);
        }
        if (file_exists($contentJsonPath)) {
            unlink($contentJsonPath);
        }
        $isInstalled = false;
        header('Location: install.php');
        exit;
    }

    if ($action === 'install' && !$isInstalled) {
        $siteTitle = trim($_POST['site_title'] ?? '');
        $siteSubtitle = trim($_POST['site_subtitle'] ?? '');
        $adminPassword = $_POST['admin_password'] ?? '';
        $adminPasswordConfirm = $_POST['admin_password_confirm'] ?? '';

        if (empty($siteTitle)) {
            $errors[] = '站点标题不能为空';
        }
        if (mb_strlen($siteTitle) > 50) {
            $errors[] = '站点标题不能超过 50 个字符';
        }
        if (empty($adminPassword)) {
            $errors[] = '管理员密码不能为空';
        }
        if (mb_strlen($adminPassword) < 8) {
            $errors[] = '管理员密码至少需要 8 个字符';
        }
        if (!preg_match('/[a-zA-Z]/', $adminPassword)) {
            $errors[] = '管理员密码需要包含至少一个字母';
        }
        if (!preg_match('/\d/', $adminPassword)) {
            $errors[] = '管理员密码需要包含至少一个数字';
        }
        if ($adminPassword !== $adminPasswordConfirm) {
            $errors[] = '两次输入的密码不一致';
        }

        if (empty($errors)) {
            // 检查目录是否可写
            if (!is_writable(__DIR__ . '/data/')) {
                $errors[] = 'data/ 目录不可写，请检查权限';
            }
        }

        if (empty($errors)) {
            // 创建 admin.json
            $adminData = [
                'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
                'failed_attempts' => 0,
                'lockout_until' => null,
                'last_login' => null
            ];

            if (!file_exists(__DIR__ . '/data/')) {
                mkdir(__DIR__ . '/data/', 0755, true);
            }

            $adminJson = json_encode($adminData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if (file_put_contents($adminJsonPath, $adminJson) === false) {
                $errors[] = '写入 admin.json 失败，请检查 data/ 目录权限';
            }
        }

        if (empty($errors)) {
            // 创建 content.json
            $contentData = [
                'site' => [
                    'title' => $siteTitle,
                    'subtitle' => $siteSubtitle ?: '开发者 / 创作者 / 探索者',
                    'avatar' => 'https://picsum.photos/id/1005/320/320',
                    'title_prefix' => '你好，我是'
                ],
                'settings' => [
                    'glassmorphism_enabled' => true,
                    'glassmorphism_blur' => 16,
                    'glassmorphism_opacity' => 0.72,
                    'page_loader_enabled' => true,
                    'music_autoplay' => false,
                    'reading_progress' => true,
                    'dark_mode_toggle' => true,
                    'diary_front_edit' => true,
                    'guestbook_enabled' => true,
                    'diary_comments_enabled' => true,
                    'comment_rich_text' => true,
                    'footer_social_links' => true,
                    'footer_music_player' => true,
                    'theme_color' => 'indigo',
                    'language' => 'zh'
                ],
                'blog_intro' => '欢迎来到我的数字角落！这里记录我的技术探索、生活故事和成长感悟。每一天都是学习新事物的好机会。',
                'about' => [
                    'intro' => '我是一名热爱生活的全栈开发者，喜欢构建美好的数字产品，也喜欢用镜头记录日常的点滴。',
                    'timeline' => []
                ],
                'skills' => [],
                'contact' => [
                    'email' => '',
                    'wechat' => '',
                    'github' => '',
                    'qq' => '',
                    'bilibili' => ''
                ],
                'homepage' => [
                    'explore_title' => '探索我的世界',
                    'card_about_title' => '关于我',
                    'card_about_desc' => '我的成长时间线、技能树和人生旅程。了解我是如何走到今天的。',
                    'card_diary_title' => '日记',
                    'card_diary_desc' => '生活故事、技术心得与旅行笔记。支持富文本与图片。',
                    'card_guestbook_title' => '留言板',
                    'card_guestbook_desc' => '留下你的足迹，点赞与回复。所有留言永久保存。'
                ],
                'footer' => [
                    'text' => '个人空间。用心构建。',
                    'icp' => ''
                ],
                'social_links' => [
                    'github' => '',
                    'bilibili' => '',
                    'zhihu' => '',
                    'twitter' => '',
                    'weibo' => ''
                ],
                'seo' => [
                    'description' => '一个用于分享技术心得、生活故事和创意作品的个人主页。',
                    'keywords' => '个人主页,博客,日记,作品集'
                ],
                'nav' => [
                    'home' => '首页',
                    'about' => '关于',
                    'diary' => '日记',
                    'guestbook' => '留言板'
                ],
                'i18n' => [
                    'zh' => [
                        'nav' => ['home' => '首页', 'about' => '关于', 'diary' => '日记', 'guestbook' => '留言板'],
                        'footer' => ['text' => '个人空间。用心构建。', 'playing' => '正在播放', 'no_track' => '暂无曲目', 'tooltip_theme' => '切换主题', 'tooltip_back_to_top' => '回到顶部'],
                        'index' => [
                            'explore_title' => '探索我的世界',
                            'card_about_title' => '关于我',
                            'card_about_desc' => '我的成长时间线、技能树和人生旅程。',
                            'card_diary_title' => '日记',
                            'card_diary_desc' => '生活故事、技术心得与旅行笔记。支持富文本与图片。',
                            'card_guestbook_title' => '留言板',
                            'card_guestbook_desc' => '留下你的足迹，点赞与回复。所有留言永久保存。',
                            'personal_digital_space' => '个人数字空间',
                            'read_latest_journal' => '阅读最新日记',
                            'guestbook' => '留言板',
                            'blog_intro_title' => '博客简介',
                            'editable_hint' => '可通过后台面板 > 内容管理编辑',
                            'edit' => '编辑',
                            'view_my_story' => '了解我的故事',
                            'browse_all_entries' => '浏览所有日记',
                            'leave_a_message' => '留言',
                            'discover_more' => '发现更多',
                            'tags_cloud' => '标签云',
                            'tags_cloud_desc' => '探索所有话题，按标签浏览日记。',
                            'browse_tags' => '浏览标签',
                            'search' => '搜索',
                            'search_desc' => '通过关键词或标签搜索所有日记。',
                            'start_searching' => '开始搜索'
                        ],
                        'about' => [
                            'intro' => '我是一名热爱生活的全栈开发者，喜欢构建美好的数字产品，也喜欢用镜头记录日常。',
                            'timeline_title' => '时间线',
                            'about_title' => '关于我',
                            'about_subtitle' => '我的成长旅程与生活哲学',
                            'my_story_label' => '我的故事',
                            'welcome_intro' => '欢迎了解我的故事',
                            'skills_title' => '技能',
                            'no_timeline' => '暂无时间线数据，请在后台添加。',
                            'no_skills' => '暂无技能数据',
                            'contact_title' => '联系方式',
                            'email_label' => '邮箱',
                            'wechat_label' => '微信',
                            'github_label' => 'GitHub',
                            'view_projects' => '查看项目'
                        ],
                        'guestbook' => [
                            'title' => '留言板',
                            'subtitle' => '留下你的想法、建议或问候',
                            'placeholder_name' => '你的名字',
                            'placeholder_content' => '写下你想说的话...',
                            'submit' => '提交留言',
                            'reply' => '回复',
                            'no_messages' => '暂无留言，来做第一个留言的人吧！',
                            'closed_title' => '留言板已关闭',
                            'closed_desc' => '管理员已关闭留言板功能。',
                            'back_home' => '返回首页',
                            'label' => '留言板',
                            'leave_message' => '留言',
                            'your_name' => '你的名字',
                            'message' => '留言内容',
                            'placeholder_message' => '写下你想说的话...',
                            'placeholder_reply' => '回复...',
                            'empty_message' => '留言内容不能为空',
                            'submitting' => '提交中...',
                            'submit_failed' => '提交失败，请重试',
                            'network_error' => '网络错误，请检查服务器',
                            'already_liked' => '你已经点赞过了',
                            'empty_reply' => '回复内容不能为空',
                            'reply_failed' => '回复失败',
                            'playing' => '正在播放',
                            'no_track' => '暂无曲目',
                            'tooltip_theme' => '切换主题',
                            'tooltip_back_to_top' => '回到顶部',
                            'manage_content' => '管理内容'
                        ],
                        'diary' => [
                            'title' => '日记',
                            'subtitle' => '记录生活、技术与思考',
                            'read_more' => '阅读更多',
                            'tags' => '标签',
                            'back_to_list' => '返回列表',
                            'diary_detail' => [
                                'not_published' => '该日记尚未发布',
                                'check_back_later' => '请稍后再来看看。',
                                'back_to_journal' => '返回日记',
                                'draft_label' => '草稿',
                                'edit_entry' => '编辑此篇',
                                'all_entries' => '所有日记',
                                'comments_title' => '评论',
                                'nickname_label' => '昵称',
                                'your_name_placeholder' => '你的名字',
                                'comment_label' => '评论',
                                'comment_placeholder' => '写下你的评论...',
                                'post_comment' => '发表评论',
                                'no_comments' => '暂无评论，来做第一个评论的人吧！',
                                'discuss_guestbook' => '去留言板讨论',
                                'toc_title' => '目录'
                            ]
                        ]
                    ],
                    'en' => [
                        'nav' => ['home' => 'Home', 'about' => 'About', 'diary' => 'Journal', 'guestbook' => 'Guestbook'],
                        'footer' => ['text' => 'Personal Space. Built with care.', 'playing' => 'Playing', 'no_track' => 'No track', 'tooltip_theme' => 'Toggle theme', 'tooltip_back_to_top' => 'Back to top'],
                        'index' => [
                            'explore_title' => 'Explore My World',
                            'card_about_title' => 'About Me',
                            'card_about_desc' => 'My timeline, growth journey, and skill tree. Learn how I got to where I am today.',
                            'card_diary_title' => 'Journal',
                            'card_diary_desc' => 'Life stories, tech insights, and travel notes. Rich text with image support.',
                            'card_guestbook_title' => 'Guestbook',
                            'card_guestbook_desc' => 'Leave a message, like, and reply. All messages are saved permanently.',
                            'personal_digital_space' => 'Personal Digital Space',
                            'read_latest_journal' => 'Read Latest Journal',
                            'guestbook' => 'Guestbook',
                            'blog_intro_title' => 'Blog Intro',
                            'editable_hint' => 'Editable via Admin Panel > Content Management',
                            'edit' => 'Edit',
                            'view_my_story' => 'View My Story',
                            'browse_all_entries' => 'Browse All Entries',
                            'leave_a_message' => 'Leave a Message',
                            'discover_more' => 'Discover More',
                            'tags_cloud' => 'Tags Cloud',
                            'tags_cloud_desc' => 'Explore all topics, browse journal entries by tag.',
                            'browse_tags' => 'Browse Tags',
                            'search' => 'Search',
                            'search_desc' => 'Search all journal entries by keyword or tag.',
                            'start_searching' => 'Start Searching'
                        ],
                        'about' => [
                            'intro' => 'I\'m a passionate full-stack developer and life explorer. I love building beautiful digital products and capturing everyday moments through photography.',
                            'timeline_title' => 'Timeline',
                            'about_title' => 'About Me',
                            'about_subtitle' => 'My Growth Journey & Life Philosophy',
                            'my_story_label' => 'My Story',
                            'welcome_intro' => 'Welcome to know my story',
                            'skills_title' => 'Skills',
                            'no_timeline' => 'No timeline data yet. Please add in admin panel.',
                            'no_skills' => 'No skills data',
                            'contact_title' => 'Contact',
                            'email_label' => 'Email',
                            'wechat_label' => 'WeChat',
                            'github_label' => 'GitHub',
                            'view_projects' => 'View Projects'
                        ],
                        'guestbook' => [
                            'title' => 'Guestbook',
                            'subtitle' => 'Leave your thoughts, suggestions, or greetings',
                            'placeholder_name' => 'Your name',
                            'placeholder_content' => 'Write something...',
                            'submit' => 'Post Message',
                            'reply' => 'Reply',
                            'no_messages' => 'No messages yet. Be the first to leave one!',
                            'closed_title' => 'Guestbook Closed',
                            'closed_desc' => 'The admin has closed the guestbook.',
                            'back_home' => 'Back to Home',
                            'label' => 'Guestbook',
                            'leave_message' => 'Leave a Message',
                            'your_name' => 'Your Name',
                            'message' => 'Message',
                            'placeholder_message' => 'Write something...',
                            'placeholder_reply' => 'Reply...',
                            'empty_message' => 'Message cannot be empty',
                            'submitting' => 'Submitting...',
                            'submit_failed' => 'Submit failed, please try again',
                            'network_error' => 'Network error, please check server',
                            'already_liked' => 'You already liked this',
                            'empty_reply' => 'Reply cannot be empty',
                            'reply_failed' => 'Reply failed',
                            'playing' => 'Playing',
                            'no_track' => 'No track',
                            'tooltip_theme' => 'Toggle theme',
                            'tooltip_back_to_top' => 'Back to top',
                            'manage_content' => 'Manage Content'
                        ],
                        'diary' => [
                            'title' => 'Journal',
                            'subtitle' => 'Life, tech, and reflections',
                            'read_more' => 'Read More',
                            'tags' => 'Tags',
                            'back_to_list' => 'Back to List',
                            'diary_detail' => [
                                'not_published' => 'This entry is not published',
                                'check_back_later' => 'Please check back later.',
                                'back_to_journal' => 'Back to Journal',
                                'draft_label' => 'Draft',
                                'edit_entry' => 'Edit This Entry',
                                'all_entries' => 'All Entries',
                                'comments_title' => 'Comments',
                                'nickname_label' => 'Nickname',
                                'your_name_placeholder' => 'Your name',
                                'comment_label' => 'Comment',
                                'comment_placeholder' => 'Write your comment...',
                                'post_comment' => 'Post Comment',
                                'no_comments' => 'No comments yet. Be the first to comment!',
                                'discuss_guestbook' => 'Discuss in Guestbook',
                                'toc_title' => 'Table of Contents'
                            ]
                        ]
                    ]
                ]
            ];

            if (!writeJsonFile($contentJsonPath, $contentData)) {
                $errors[] = '写入 content.json 失败，请检查 data/ 目录权限';
                // 回滚：删除已创建的 admin.json
                if (file_exists($adminJsonPath)) {
                    unlink($adminJsonPath);
                }
            }
        }

        if (empty($errors)) {
            $success = true;
            // 安装完成后删除自身
            @unlink(__FILE__);
        }
    }
}

// ============ 环境检测函数 ============

function checkPhpVersion(): array {
    $version = PHP_VERSION;
    $major = (int) explode('.', $version)[0];
    $minor = (int) explode('.', $version)[1];
    $ok = ($major > 8 || ($major === 8 && $minor >= 0));
    return [
        'name' => 'PHP 版本',
        'value' => 'PHP ' . $version,
        'required' => '>= 8.0',
        'status' => $ok ? 'ok' : 'fail'
    ];
}

function checkExtension(string $name): array {
    $loaded = extension_loaded($name);
    return [
        'name' => $name . ' 扩展',
        'value' => $loaded ? '已安装' : '未安装',
        'required' => '必须启用',
        'status' => $loaded ? 'ok' : 'fail'
    ];
}

function checkDirWritable(string $dir, string $label): array {
    $path = __DIR__ . '/' . $dir;
    if (!is_dir($path)) {
        $created = @mkdir($path, 0755, true);
        if (!$created) {
            return [
                'name' => $label . ' 目录',
                'value' => '目录不存在且无法创建',
                'required' => '需要可写',
                'status' => 'fail'
            ];
        }
    }
    $writable = is_writable($path);
    return [
        'name' => $label . ' 目录',
        'value' => $writable ? '可写' : '不可写',
        'required' => '需要可写',
        'status' => $writable ? 'ok' : 'fail'
    ];
}

$checks = [
    checkPhpVersion(),
    checkExtension('mbstring'),
    checkExtension('json'),
    checkDirWritable('data/', 'data'),
    checkDirWritable('uploads/', 'uploads'),
    checkDirWritable('backups/', 'backups'),
];

$allPassed = empty(array_filter($checks, fn($c) => $c['status'] === 'fail'));

// ============ 页面渲染 ============

function renderStatusIcon(string $status): string {
    if ($status === 'ok') {
        return '<span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></span>';
    }
    return '<span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-500/20 text-red-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></span>';
}
?>
<!DOCTYPE html>
<html lang="zh-CN" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装向导 - 个人主页系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: #0f172a;
            min-height: 100vh;
        }
        .card-glass {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(71, 85, 105, 0.4);
        }
        .input-dark {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(71, 85, 105, 0.5);
            color: #e2e8f0;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-dark:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        .input-dark::placeholder {
            color: #64748b;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            transition: all 0.2s;
        }
        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        .step-indicator {
            transition: all 0.3s;
        }
        .step-indicator.active {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            box-shadow: 0 0 12px rgba(99, 102, 241, 0.4);
        }
        .step-indicator.completed {
            background: #10b981;
            color: white;
        }
        .step-indicator.pending {
            background: rgba(51, 65, 85, 0.5);
            color: #64748b;
        }
        .step-line {
            height: 2px;
            transition: background 0.3s;
        }
        .step-line.active {
            background: linear-gradient(90deg, #10b981, #6366f1);
        }
        .step-line.pending {
            background: rgba(51, 65, 85, 0.5);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.4s ease-out;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="w-full max-w-lg">

        <!-- 系统已安装提示 -->
        <?php if ($isInstalled && !$success): ?>
        <div class="card-glass rounded-2xl p-8 fade-in text-center">
            <div class="mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-500/20 mb-4">
                    <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2">系统已安装</h1>
                <p class="text-slate-400">个人主页系统已完成安装配置，无需重复安装。</p>
            </div>
            <div class="space-y-3">
                <a href="admin/" class="btn-primary block w-full py-3 rounded-xl text-center font-medium">
                    进入后台
                </a>
                <div class="relative">
                    <button type="button" onclick="showReinstallConfirm()" class="btn-danger block w-full py-3 rounded-xl text-center font-medium">
                        重新安装
                    </button>
                </div>
            </div>
            <!-- 重新安装确认弹窗 -->
            <div id="reinstallModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
                <div class="card-glass rounded-2xl p-6 max-w-sm mx-4 fade-in">
                    <h3 class="text-lg font-bold text-white mb-2">确认重新安装？</h3>
                    <p class="text-slate-400 text-sm mb-6">此操作将删除管理员账号和所有站点配置数据。日记、留言等数据不会被删除。该操作不可撤销！</p>
                    <form method="POST" class="flex gap-3">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="reinstall_confirm">
                        <button type="button" onclick="hideReinstallConfirm()" class="flex-1 py-2.5 rounded-xl border border-slate-600 text-slate-300 hover:bg-slate-700/50 transition-colors">
                            取消
                        </button>
                        <button type="submit" class="flex-1 py-2.5 rounded-xl bg-red-600 text-white hover:bg-red-700 transition-colors">
                            确认重装
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 安装成功提示 -->
        <?php if ($success): ?>
        <div class="card-glass rounded-2xl p-8 fade-in text-center">
            <div class="mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-500/20 mb-4">
                    <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2">安装完成！</h1>
                <p class="text-slate-400">个人主页系统已成功安装，安装文件已自动删除。</p>
            </div>
            <a href="admin/" class="btn-primary block w-full py-3 rounded-xl text-center font-medium">
                进入后台管理
            </a>
            <a href="index.php" class="block w-full py-3 mt-3 rounded-xl border border-slate-600 text-slate-300 hover:bg-slate-700/50 transition-colors text-center font-medium">
                查看首页
            </a>
        </div>
        <?php endif; ?>

        <!-- 安装向导 -->
        <?php if (!$isInstalled && !$success): ?>
        <div class="card-glass rounded-2xl p-8 fade-in">
            <!-- 标题 -->
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-white mb-2">安装向导</h1>
                <p class="text-slate-400 text-sm">按照以下步骤完成个人主页系统的安装</p>
            </div>

            <!-- 步骤指示器 -->
            <div class="flex items-center justify-center mb-8">
                <div class="flex items-center">
                    <div class="step-indicator active w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold" id="step1Indicator">1</div>
                    <span class="text-xs text-slate-400 ml-2 mr-4 hidden sm:inline">环境检测</span>
                </div>
                <div class="step-line pending w-12 sm:w-20" id="step1Line"></div>
                <div class="flex items-center">
                    <div class="step-indicator pending w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold" id="step2Indicator">2</div>
                    <span class="text-xs text-slate-400 ml-2 mr-4 hidden sm:inline">站点设置</span>
                </div>
                <div class="step-line pending w-12 sm:w-20" id="step2Line"></div>
                <div class="flex items-center">
                    <div class="step-indicator pending w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold" id="step3Indicator">3</div>
                    <span class="text-xs text-slate-400 ml-2 hidden sm:inline">完成安装</span>
                </div>
            </div>

            <!-- Step 1: 环境检测 -->
            <div id="step1" class="fade-in">
                <h2 class="text-lg font-semibold text-white mb-4">Step 1 - 环境检测</h2>
                <div class="space-y-3 mb-6">
                    <?php foreach ($checks as $check): ?>
                    <div class="flex items-center justify-between py-3 px-4 rounded-xl bg-slate-800/50">
                        <div>
                            <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($check['name']); ?></div>
                            <div class="text-xs text-slate-500">要求: <?php echo htmlspecialchars($check['required']); ?></div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-slate-300"><?php echo htmlspecialchars($check['value']); ?></span>
                            <?php echo renderStatusIcon($check['status']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (!$allPassed): ?>
                <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20 mb-6">
                    <p class="text-red-400 text-sm">部分环境要求未满足，请修正以上标红的选项后再试。</p>
                </div>
                <button disabled class="btn-primary w-full py-3 rounded-xl font-medium opacity-50 cursor-not-allowed">
                    环境检测未通过
                </button>
                <?php else: ?>
                <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 mb-6">
                    <p class="text-emerald-400 text-sm">所有环境要求已满足，可以继续安装。</p>
                </div>
                <button onclick="goToStep(2)" class="btn-primary w-full py-3 rounded-xl font-medium">
                    下一步：设置站点信息
                </button>
                <?php endif; ?>
            </div>

            <!-- Step 2: 站点设置 -->
            <div id="step2" class="hidden fade-in">
                <h2 class="text-lg font-semibold text-white mb-4">Step 2 - 设置站点信息</h2>

                <?php if (!empty($errors)): ?>
                <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20 mb-6">
                    <ul class="text-red-400 text-sm space-y-1">
                        <?php foreach ($errors as $error): ?>
                        <li>- <?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" id="installForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="install">

                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1.5">站点标题 <span class="text-red-400">*</span></label>
                            <input type="text" name="site_title" required maxlength="50"
                                   class="input-dark w-full px-4 py-3 rounded-xl text-sm"
                                   placeholder="例如：我的个人空间"
                                   value="<?php echo htmlspecialchars($_POST['site_title'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1.5">站点副标题</label>
                            <input type="text" name="site_subtitle"
                                   class="input-dark w-full px-4 py-3 rounded-xl text-sm"
                                   placeholder="例如：开发者 / 创作者 / 探索者"
                                   value="<?php echo htmlspecialchars($_POST['site_subtitle'] ?? ''); ?>">
                        </div>
                        <div class="border-t border-slate-700/50 pt-5">
                            <p class="text-xs text-slate-500 mb-4">以下信息用于后台管理员登录</p>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1.5">管理员密码 <span class="text-red-400">*</span></label>
                                <input type="password" name="admin_password" required minlength="8"
                                       id="adminPassword"
                                       class="input-dark w-full px-4 py-3 rounded-xl text-sm"
                                       placeholder="至少 8 个字符，包含字母和数字">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1.5">确认密码 <span class="text-red-400">*</span></label>
                            <input type="password" name="admin_password_confirm" required minlength="8"
                                   id="adminPasswordConfirm"
                                   class="input-dark w-full px-4 py-3 rounded-xl text-sm"
                                   placeholder="再次输入密码">
                        </div>
                    </div>

                    <div class="flex gap-3 mt-8">
                        <button type="button" onclick="goToStep(1)" class="flex-1 py-3 rounded-xl border border-slate-600 text-slate-300 hover:bg-slate-700/50 transition-colors font-medium">
                            上一步
                        </button>
                        <button type="submit" class="btn-primary flex-1 py-3 rounded-xl font-medium" id="installBtn">
                            开始安装
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- 底部信息 -->
        <?php if (!$success): ?>
        <p class="text-center text-xs text-slate-600 mt-6">
            个人主页系统 &middot; 安装向导
        </p>
        <?php endif; ?>
    </div>

    <script>
        // 步骤切换
        function goToStep(step) {
            document.getElementById('step1').classList.add('hidden');
            document.getElementById('step2').classList.add('hidden');

            const step1Indicator = document.getElementById('step1Indicator');
            const step2Indicator = document.getElementById('step2Indicator');
            const step3Indicator = document.getElementById('step3Indicator');
            const step1Line = document.getElementById('step1Line');
            const step2Line = document.getElementById('step2Line');

            // 重置所有步骤状态
            [step1Indicator, step2Indicator, step3Indicator].forEach(el => {
                el.className = el.className.replace(/active|completed|pending/g, '').trim();
                el.classList.add('pending');
            });
            [step1Line, step2Line].forEach(el => {
                el.className = el.className.replace(/active|pending/g, '').trim();
                el.classList.add('pending');
            });

            if (step === 1) {
                document.getElementById('step1').classList.remove('hidden');
                step1Indicator.classList.remove('pending');
                step1Indicator.classList.add('active');
            } else if (step === 2) {
                document.getElementById('step2').classList.remove('hidden');
                step1Indicator.classList.remove('pending');
                step1Indicator.classList.add('completed');
                step2Indicator.classList.remove('pending');
                step2Indicator.classList.add('active');
                step1Line.classList.remove('pending');
                step1Line.classList.add('active');
            } else if (step === 3) {
                step1Indicator.classList.remove('pending');
                step1Indicator.classList.add('completed');
                step2Indicator.classList.remove('pending');
                step2Indicator.classList.add('completed');
                step3Indicator.classList.remove('pending');
                step3Indicator.classList.add('completed');
                step1Line.classList.remove('pending');
                step1Line.classList.add('active');
                step2Line.classList.remove('pending');
                step2Line.classList.add('active');
            }
        }

        // 重新安装确认弹窗
        function showReinstallConfirm() {
            document.getElementById('reinstallModal').classList.remove('hidden');
        }
        function hideReinstallConfirm() {
            document.getElementById('reinstallModal').classList.add('hidden');
        }
        // 点击弹窗外部关闭
        document.getElementById('reinstallModal')?.addEventListener('click', function(e) {
            if (e.target === this) hideReinstallConfirm();
        });

        // 安装按钮加载状态
        const installForm = document.getElementById('installForm');
        if (installForm) {
            installForm.addEventListener('submit', function() {
                const btn = document.getElementById('installBtn');
                btn.disabled = true;
                btn.innerHTML = '<span class="inline-block animate-spin mr-2"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>正在安装...';
            });
        }
    </script>
</body>
</html>