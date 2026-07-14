<?php
require_once __DIR__ . '/security.php';

// 安全Session配置
configureSecureSession();

// 发送安全响应头
sendSecurityHeaders();

$site_title = '我的个人空间';
$content = readJsonFile(__DIR__ . '/../data/content.json');
if (!empty($content['site']['title'])) {
    $site_title = $content['site']['title'];
}
$seo = $content['seo'] ?? [];

// 读取后台设置
$settings = $content['settings'] ?? [];
$glassEnabled = !empty($settings['glassmorphism_enabled']);
$glassBlur = max(0, min(30, (int)($settings['glassmorphism_blur'] ?? 16)));
$glassOpacity = max(0.1, min(0.95, (float)($settings['glassmorphism_opacity'] ?? 0.72)));
$pageLoaderEnabled = !empty($settings['page_loader_enabled']);
$readingProgressEnabled = !empty($settings['reading_progress']);
$darkModeToggle = !empty($settings['dark_mode_toggle']);
$diaryFrontEdit = !empty($settings['diary_front_edit']);
$guestbookEnabled = !empty($settings['guestbook_enabled']);
$diaryCommentsEnabled = !empty($settings['diary_comments_enabled']);
$commentRichText = !empty($settings['comment_rich_text']);
$footerSocialLinks = !empty($settings['footer_social_links']);
$footerMusicPlayer = !empty($settings['footer_music_player']);
$musicAutoplay = !empty($settings['music_autoplay']);

// 多语言与主题色
$lang = $settings['language'] ?? 'en';
$i18nData = $content['i18n'] ?? [];
$i18n = $i18nData[$lang] ?? $i18nData['en'] ?? [];
$navTexts = $i18n['nav'] ?? $content['nav'] ?? ['home'=>'首页','about'=>'关于','diary'=>'日记','guestbook'=>'留言板'];

$themeColor = $settings['theme_color'] ?? 'indigo';
$themeMap = [
    'indigo'  => ['primary'=>'#6366f1','hover'=>'#4f46e5','light'=>'#e0e7ff','tw'=>'indigo'],
    'emerald' => ['primary'=>'#10b981','hover'=>'#059669','light'=>'#d1fae5','tw'=>'emerald'],
    'rose'    => ['primary'=>'#f43f5e','hover'=>'#e11d48','light'=>'#ffe4e6','tw'=>'rose'],
    'cyan'    => ['primary'=>'#06b6d4','hover'=>'#0891b2','light'=>'#cffafe','tw'=>'cyan'],
    'amber'   => ['primary'=>'#f59e0b','hover'=>'#d97706','light'=>'#fef3c7','tw'=>'amber'],
    'violet'  => ['primary'=>'#8b5cf6','hover'=>'#7c3aed','light'=>'#ede9fe','tw'=>'violet'],
];
$theme = $themeMap[$themeColor] ?? $themeMap['indigo'];

// 页面访问统计（原子写入）
$statsFile = __DIR__ . '/../data/stats.json';
$today = date('Y-m-d');
$stats = readJsonFile($statsFile);
if (!isset($stats[$today])) $stats[$today] = ['pv' => 0, 'uv' => 0];
$stats[$today]['pv']++;
if (empty($_SESSION['counted_uv_' . $today])) {
    $stats[$today]['uv']++;
    $_SESSION['counted_uv_' . $today] = true;
}
writeJsonFile($statsFile, $stats);

// 初始化 JSON-LD 结构化数据（子页面可覆盖）
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => $site_title,
    'description' => $seo['description'] ?? '',
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitizeHtml($page_title ?? $site_title) ?></title>
    <?php if (!empty($seo['description'])): ?>
    <meta name="description" content="<?= sanitizeHtml($seo['description']) ?>">
    <?php endif; ?>
    <?php if (!empty($seo['keywords'])): ?>
    <meta name="keywords" content="<?= sanitizeHtml($seo['keywords']) ?>">
    <?php endif; ?>
    <meta name="theme-color" content="<?= sanitizeHtml($theme['primary']) ?>">
    <?php if (!empty($seo['description'])): ?>
    <meta property="og:title" content="<?= sanitizeHtml($page_title ?? $site_title) ?>">
    <meta property="og:description" content="<?= sanitizeHtml($seo['description']) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= sanitizeHtml($site_title) ?>">
    <?php endif; ?>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌐</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&family=Inter:wght@300;400;500;600&display=swap');
        
        :root {
            --theme-primary: <?= sanitizeHtml($theme['primary']) ?>;
            --theme-primary-hover: <?= sanitizeHtml($theme['hover']) ?>;
            --theme-primary-light: <?= sanitizeHtml($theme['light']) ?>;
        }
        
        body {
            font-family: 'Noto Sans SC', 'Inter', system-ui, sans-serif;
        }
        
        .glass {
            background: rgba(255, 255, 255, <?= $glassOpacity ?>);
            backdrop-filter: blur(<?= $glassBlur ?>px);
            -webkit-backdrop-filter: blur(<?= $glassBlur ?>px);
        }
        .dark .glass {
            background: rgba(17, 24, 39, <?= $glassOpacity ?>);
        }
        <?php if ($glassEnabled): ?>
        .glass-card {
            background: rgba(255, 255, 255, <?= $glassOpacity ?>);
            backdrop-filter: blur(<?= $glassBlur ?>px);
            -webkit-backdrop-filter: blur(<?= $glassBlur ?>px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .dark .glass-card {
            background: rgba(31, 41, 55, <?= $glassOpacity ?>);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        <?php endif; ?>
        
        .nav-link { transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        .nav-link:hover { color: var(--theme-primary); transform: translateY(-1px); }
        
        .card {
            transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1), 
                       box-shadow 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        .dark .card { box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.3); }
        
        .music-player {
            background: linear-gradient(135deg, #1e2937 0%, #0f172a 100%);
        }
        
        .nav-active { color: var(--theme-primary); font-weight: 600; }
        
        .tag {
            display: inline-flex; align-items: center;
            padding: 0.25rem 0.75rem; font-size: 0.75rem; font-weight: 500;
            border-radius: 9999px; background-color: #f1e7ff; color: #7c3aed;
        }
        .dark .tag { background-color: #312e81; color: #c4b5fd; }
        
        /* Page load animation */
        .fade-in { animation: fadeInUp 0.5s ease-out forwards; opacity: 0; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        
        /* Loading spinner */
        #page-loader {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: white; z-index: 9999; display: flex;
            align-items: center; justify-content: center;
            transition: opacity 0.3s ease;
        }
        .dark #page-loader { background: #111827; }
        #page-loader.hidden { opacity: 0; pointer-events: none; }
        .loader-dot {
            width: 10px; height: 10px; border-radius: 50%; background: var(--theme-primary);
            animation: bounce 0.6s infinite alternate;
            margin: 0 4px;
        }
        .loader-dot:nth-child(2) { animation-delay: 0.2s; }
        .loader-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce {
            to { transform: translateY(-12px); opacity: 0.3; }
        }
        
        /* Reading progress bar */
        #reading-progress {
            position: fixed; top: 0; left: 0; height: 3px;
            background: linear-gradient(to right, var(--theme-primary), var(--theme-primary-hover));
            z-index: 100; width: 0; transition: width 0.1s;
        }
        <?php if ($glassEnabled): ?>
        .glassmorphism-bg {
            background: linear-gradient(135deg, #667eea22 0%, #764ba222 25%, #f093fb22 50%, #f5576c22 75%, #4facfe22 100%);
            background-color: #f8fafc;
            min-height: 100vh;
            background-attachment: fixed;
        }
        .dark .glassmorphism-bg {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 25%, #f093fb15 50%, #f5576c15 75%, #4facfe15 100%);
            background-color: #111827;
        }
        .glassmorphism-bg .card {
            background: rgba(255, 255, 255, <?= $glassOpacity ?>);
            backdrop-filter: blur(<?= $glassBlur ?>px);
            -webkit-backdrop-filter: blur(<?= $glassBlur ?>px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
        }
        .dark .glassmorphism-bg .card {
            background: rgba(31, 41, 55, <?= $glassOpacity ?>);
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        .glassmorphism-bg .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .dark .glassmorphism-bg .card:hover {
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        <?php endif; ?>
    </style>
    <?php if (!empty($page_extra_head)) echo $page_extra_head; ?>
    <?php if (!empty($jsonLd)): ?>
    <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE) ?></script>
    <?php endif; ?>
</head>
<body class="<?php if ($glassEnabled): ?>glassmorphism-bg<?php else: ?>bg-gray-50 dark:bg-gray-900<?php endif; ?> text-gray-900 dark:text-gray-100 transition-colors duration-300">
    <?php if ($pageLoaderEnabled): ?>
    <!-- Page Loader -->
    <div id="page-loader">
        <div class="flex"><div class="loader-dot"></div><div class="loader-dot"></div><div class="loader-dot"></div></div>
    </div>
    <?php endif; ?>
    
    <?php if ($readingProgressEnabled): ?>
    <!-- Reading progress (visible on diary-detail) -->
    <div id="reading-progress"></div>
    <?php endif; ?>
    
    <!-- Top Navigation -->
    <nav class="sticky top-0 z-50 glass border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-6xl mx-auto px-6">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center gap-x-3 group">
                    <div class="w-9 h-9 bg-<?= sanitizeHtml($theme['tw']) ?>-600 rounded-2xl flex items-center justify-center text-white shadow-inner group-hover:rotate-12 transition-transform">
                        <i class="fa-solid fa-feather-pointed text-xl"></i>
                    </div>
                    <span class="font-bold text-2xl tracking-tight"><?= sanitizeHtml($site_title) ?></span>
                </a>
                
                <div class="hidden md:flex items-center gap-x-8 text-sm font-medium">
                    <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'nav-active' : 'text-gray-600 dark:text-gray-300' ?>"><?= sanitizeHtml($navTexts['home'] ?? '首页') ?></a>
                    <a href="about.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'about.php' ? 'nav-active' : 'text-gray-600 dark:text-gray-300' ?>"><?= sanitizeHtml($navTexts['about'] ?? '关于') ?></a>
                    <a href="diary.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'diary.php' || basename($_SERVER['PHP_SELF']) === 'diary-detail.php' ? 'nav-active' : 'text-gray-600 dark:text-gray-300' ?>"><?= sanitizeHtml($navTexts['diary'] ?? '日记') ?></a>
                    <a href="guestbook.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'guestbook.php' ? 'nav-active' : 'text-gray-600 dark:text-gray-300' ?>"><?= sanitizeHtml($navTexts['guestbook'] ?? '留言板') ?></a>
                    <a href="friends.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'friends.php' ? 'nav-active' : 'text-gray-600 dark:text-gray-300' ?>">友情链接</a>
                </div>
                
                <div class="flex items-center gap-x-4">
                    <?php if (!empty($_SESSION['admin_logged_in'])): ?>
                    <a href="admin/index.php" 
                       class="hidden md:flex items-center gap-x-2 px-4 py-2 text-sm font-medium rounded-2xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors">
                        <i class="fa-solid fa-user-shield mr-1.5"></i>
                        <span>管理后台</span>
                    </a>
                    <?php endif; ?>
                    <button id="mobile-menu-btn"
                            class="md:hidden w-10 h-10 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-2xl transition-colors">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <div id="mobile-menu" class="hidden md:hidden border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div class="px-6 py-4 flex flex-col gap-y-1 text-sm">
                <a href="index.php" class="py-2.5 px-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'text-' . sanitizeHtml($theme['tw']) . '-600 font-semibold' : '' ?>"><?= sanitizeHtml($navTexts['home'] ?? '首页') ?></a>
                <a href="about.php" class="py-2.5 px-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 <?= basename($_SERVER['PHP_SELF']) === 'about.php' ? 'text-' . sanitizeHtml($theme['tw']) . '-600 font-semibold' : '' ?>"><?= sanitizeHtml($navTexts['about'] ?? '关于') ?></a>
                <a href="diary.php" class="py-2.5 px-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 <?= basename($_SERVER['PHP_SELF']) === 'diary.php' ? 'text-' . sanitizeHtml($theme['tw']) . '-600 font-semibold' : '' ?>"><?= sanitizeHtml($navTexts['diary'] ?? '日记') ?></a>
                <a href="guestbook.php" class="py-2.5 px-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 <?= basename($_SERVER['PHP_SELF']) === 'guestbook.php' ? 'text-' . sanitizeHtml($theme['tw']) . '-600 font-semibold' : '' ?>"><?= sanitizeHtml($navTexts['guestbook'] ?? '留言板') ?></a>
                <a href="friends.php" class="py-2.5 px-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 <?= basename($_SERVER['PHP_SELF']) === 'friends.php' ? 'text-' . sanitizeHtml($theme['tw']) . '-600 font-semibold' : '' ?>">友情链接</a>
                <?php if (!empty($_SESSION['admin_logged_in'])): ?>
                <div class="pt-2 border-t border-gray-100 dark:border-gray-700 mt-1">
                    <a href="admin/index.php" class="flex items-center gap-x-2 py-2.5 px-3 text-sm font-medium rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900">
                        <i class="fa-solid fa-user-shield"></i> 管理面板
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <script>
        // Theme management
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
        
        function initCommon() {
            // Hide loader
            const loader = document.getElementById('page-loader');
            if (loader) setTimeout(() => loader.classList.add('hidden'), 200);
            
            // Theme toggle
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', () => {
                    document.documentElement.classList.toggle('dark');
                    localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
                });
            }
            
            // Mobile menu
            const btn = document.getElementById('mobile-menu-btn');
            const menu = document.getElementById('mobile-menu');
            if (btn && menu) {
                btn.addEventListener('click', () => {
                    menu.classList.toggle('hidden');
                    const icon = btn.querySelector('i');
                    if (icon) { icon.classList.toggle('fa-bars'); icon.classList.toggle('fa-times'); }
                });
            }
            
            // Reading progress bar (for diary detail pages)
            const progressBar = document.getElementById('reading-progress');
            if (progressBar && document.querySelector('.diary-content')) {
                window.addEventListener('scroll', () => {
                    const h = document.documentElement.scrollHeight - window.innerHeight;
                    progressBar.style.width = h > 0 ? (window.scrollY / h * 100) + '%' : '0';
                });
            }
            
            // Back to top visibility
            const backBtn = document.getElementById('back-to-top');
            if (backBtn) {
                window.addEventListener('scroll', () => {
                    backBtn.style.display = window.scrollY > 300 ? 'flex' : 'none';
                });
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCommon);
        } else {
            initCommon();
        }
    </script>