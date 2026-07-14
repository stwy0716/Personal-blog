<?php
/**
 * 后台图片管理器
 * 扫描 uploads/ 目录下所有图片文件
 * 网格展示缩略图，支持预览、删除、复制URL、分页
 */
require_once __DIR__ . '/auth.php';
handleAdminAuth();

$uploadDir = __DIR__ . '/../uploads/';
$allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$perPage = 20;
$page = max(1, (int)($_GET['p'] ?? 1));

$message = '';
$error = '';

// 处理POST删除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    verifyPostCsrf();
    $delFile = basename($_POST['delete_image']);
    $filePath = $uploadDir . $delFile;
    if (file_exists($filePath)) {
        // 检查是否是图片
        $ext = strtolower(pathinfo($delFile, PATHINFO_EXTENSION));
        if (in_array($ext, $allowedExts)) {
            unlink($filePath);
            logOperation('image_delete', '删除了图片：' . $delFile);
            header('Location: images.php?deleted=1');
            exit;
        } else {
            $error = '不允许删除此类型的文件。';
        }
    } else {
        $error = '文件不存在。';
    }
}

// 扫描图片文件
$images = [];
if (is_dir($uploadDir)) {
    $allFiles = scandir($uploadDir);
    foreach ($allFiles as $file) {
        if ($file === '.' || $file === '..') continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, $allowedExts)) {
            $fullPath = $uploadDir . $file;
            $images[] = [
                'name' => $file,
                'size' => filesize($fullPath),
                'time' => filemtime($fullPath),
                'url' => 'uploads/' . $file,
            ];
        }
    }
    // 按时间倒序排列
    usort($images, fn($a, $b) => $b['time'] <=> $a['time']);
}

// 分页
$totalImages = count($images);
$totalPages = max(1, ceil($totalImages / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$pageImages = array_slice($images, $offset, $perPage);

function formatFileSize($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

$admin_page_title = '图片管理';
include __DIR__ . '/admin_header.php';
?>
    <div class="max-w-6xl mx-auto px-6 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold tracking-tight">图片管理</h1>
            <div class="flex items-center gap-x-3">
                <span class="text-sm text-zinc-400">共 <?= $totalImages ?> 张图片</span>
                <a href="upload.php" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-xl flex items-center gap-x-2 hover:bg-indigo-700 transition-colors">
                    <i class="fa-solid fa-upload"></i> 上传图片
                </a>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 bg-red-500/10 text-red-400 rounded-2xl flex items-center gap-x-2">
                <i class="fa-solid fa-exclamation-circle"></i> <span><?= sanitizeHtml($error) ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="mb-6 p-4 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-2xl">图片已删除。</div>
        <?php endif; ?>

        <?php if (empty($images)): ?>
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-12 text-center">
                <div class="w-20 h-20 rounded-3xl bg-zinc-800 flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-images text-zinc-600 text-2xl"></i>
                </div>
                <p class="text-zinc-400 mb-4">暂无图片</p>
                <a href="upload.php" class="inline-flex items-center gap-x-2 px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm hover:bg-indigo-700 transition-colors">
                    <i class="fa-solid fa-upload"></i> 上传图片
                </a>
            </div>
        <?php else: ?>
            <!-- 图片网格 -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-6">
                <?php foreach ($pageImages as $img): ?>
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden group">
                        <div class="aspect-square bg-zinc-800 flex items-center justify-center overflow-hidden cursor-pointer" onclick="openPreview('<?= sanitizeHtml($img['url']) ?>')">
                            <img src="<?= sanitizeHtml($img['url']) ?>" alt="<?= sanitizeHtml($img['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        </div>
                        <div class="p-3">
                            <div class="text-xs font-medium truncate mb-1" title="<?= sanitizeHtml($img['name']) ?>"><?= sanitizeHtml($img['name']) ?></div>
                            <div class="flex items-center justify-between text-[10px] text-zinc-500">
                                <span><?= formatFileSize($img['size']) ?></span>
                                <span><?= date('Y-m-d', $img['time']) ?></span>
                            </div>
                            <div class="flex items-center gap-x-1 mt-2">
                                <button onclick="copyImageUrl('<?= sanitizeHtml($img['url']) ?>')" class="px-2 py-1 text-[10px] text-indigo-400 hover:text-indigo-300 bg-indigo-950/50 rounded-md transition-colors" title="复制URL">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirm('确认删除此图片？')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="delete_image" value="<?= sanitizeHtml($img['name']) ?>">
                                    <button type="submit" class="px-2 py-1 text-[10px] text-red-400 hover:text-red-300 bg-red-950/50 rounded-md transition-colors" title="删除">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 分页 -->
            <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-center gap-x-2">
                <?php if ($page > 1): ?>
                    <a href="?p=<?= $page - 1 ?>" class="px-3 py-1.5 text-sm text-zinc-400 hover:text-zinc-300 bg-zinc-800 rounded-lg transition-colors">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg"><?= $i ?></span>
                    <?php elseif (abs($i - $page) <= 2 || $i === 1 || $i === $totalPages): ?>
                        <a href="?p=<?= $i ?>" class="px-3 py-1.5 text-sm text-zinc-400 hover:text-zinc-300 bg-zinc-800 rounded-lg transition-colors"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?p=<?= $page + 1 ?>" class="px-3 py-1.5 text-sm text-zinc-400 hover:text-zinc-300 bg-zinc-800 rounded-lg transition-colors">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- 图片预览 Modal -->
    <div id="image-preview-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/90" style="backdrop-filter: blur(4px);">
        <div class="relative max-w-[90vw] max-h-[90vh]">
            <button onclick="closePreview()" class="absolute -top-12 right-0 text-white/70 hover:text-white text-2xl transition-colors">
                <i class="fa-solid fa-times"></i>
            </button>
            <img id="preview-image" src="" alt="" class="max-w-[90vw] max-h-[85vh] object-contain rounded-lg">
            <div class="mt-3 flex items-center justify-center gap-x-3">
                <span id="preview-url" class="text-sm text-white/50 truncate max-w-[400px]"></span>
                <button onclick="copyPreviewUrl()" class="px-3 py-1 text-xs text-white bg-white/20 hover:bg-white/30 rounded-lg transition-colors">
                    <i class="fa-solid fa-copy mr-1"></i> 复制URL
                </button>
            </div>
        </div>
    </div>

    <script>
    function openPreview(url) {
        const modal = document.getElementById('image-preview-modal');
        const img = document.getElementById('preview-image');
        const urlEl = document.getElementById('preview-url');
        img.src = url;
        urlEl.textContent = url;
        urlEl.dataset.url = url;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closePreview() {
        const modal = document.getElementById('image-preview-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    // ESC关闭
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closePreview();
    });

    // 点击背景关闭
    document.getElementById('image-preview-modal').addEventListener('click', function(e) {
        if (e.target === this) closePreview();
    });

    function copyImageUrl(url) {
        const fullUrl = window.location.origin + '/' + url;
        navigator.clipboard.writeText(fullUrl).then(function() {
            showToast('URL已复制到剪贴板');
        }).catch(function() {
            // 降级方案
            const ta = document.createElement('textarea');
            ta.value = fullUrl;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            showToast('URL已复制到剪贴板');
        });
    }

    function copyPreviewUrl() {
        const urlEl = document.getElementById('preview-url');
        if (urlEl && urlEl.dataset.url) {
            copyImageUrl(urlEl.dataset.url);
        }
    }

    function showToast(msg) {
        const existing = document.querySelector('.copy-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = 'copy-toast fixed bottom-6 left-1/2 -translate-x-1/2 z-[60] px-5 py-3 bg-zinc-800 text-white text-sm rounded-xl shadow-lg border border-zinc-700';
        toast.innerHTML = '<i class="fa-solid fa-check text-emerald-400 mr-2"></i>' + msg;
        document.body.appendChild(toast);

        setTimeout(function() {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(function() { toast.remove(); }, 300);
        }, 2000);
    }
    </script>
</body>
</html>
