<?php
require_once __DIR__ . '/auth.php';
handleAdminAuth();

$diariesFile = __DIR__ . '/../data/diaries.json';
$diaries = readJsonFile($diariesFile);

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    verifyPostCsrf();
    $delId = (int)$_POST['delete_id'];
    atomicJsonUpdate($diariesFile, function($diaries) use ($delId) {
        return array_values(array_filter($diaries, fn($d) => (int)$d['id'] !== $delId));
    });
    logOperation('diary_delete', '删除了日记 #' . $delId);
    header('Location: diary.php?deleted=1');
    exit;
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_diary'])) {
    verifyPostCsrf();
    $id = isset($_POST['id']) && $_POST['id'] ? (int)$_POST['id'] : 0;
    $title = trim($_POST['title'] ?? '');
    $date = trim($_POST['date'] ?? date('Y-m-d'));
    $excerpt = trim($_POST['excerpt'] ?? '');
    $contentHtml = $_POST['content'] ?? '';
    $tagsRaw = trim($_POST['tags'] ?? '');
    $tags = $tagsRaw ? array_map('trim', explode(',', $tagsRaw)) : [];
    $status = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'published';
    $pinned = isset($_POST['pinned']) ? true : false;
    
    if ($title && $contentHtml) {
        if ($id > 0) {
            foreach ($diaries as &$d) {
                if ((int)$d['id'] === $id) {
                    $d['title'] = $title;
                    $d['date'] = $date;
                    $d['excerpt'] = $excerpt;
                    $d['content'] = $contentHtml;
                    $d['tags'] = $tags;
                    $d['status'] = $status;
                    $d['pinned'] = $pinned;
                    break;
                }
            }
            logOperation('diary_update', '更新了日记：' . $title);
        } else {
            $newId = 1;
            if (!empty($diaries)) {
                $ids = array_column($diaries, 'id');
                $newId = max($ids) + 1;
            }
            $diaries[] = [
                'id' => $newId,
                'title' => $title,
                'date' => $date,
                'excerpt' => $excerpt,
                'content' => $contentHtml,
                'tags' => $tags,
                'status' => $status,
                'pinned' => $pinned
            ];
            logOperation('diary_create', '创建了日记：' . $title);
        }
        
        usort($diaries, function($a, $b) {
            $pa = !empty($a['pinned']) ? 1 : 0;
            $pb = !empty($b['pinned']) ? 1 : 0;
            if ($pa !== $pb) return $pb <=> $pa;
            return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
        });
        writeJsonFile($diariesFile, $diaries);
        header('Location: diary.php?saved=1');
        exit;
    }
}

$editDiary = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($diaries as $d) {
        if ((int)$d['id'] === $editId) {
            $editDiary = $d;
            break;
        }
    }
}

$admin_page_title = '日记管理';
include __DIR__ . '/admin_header.php';
?>
    <!-- Summernote CSS/JS (free, no API key) -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
    <style>
        .note-editor { border-radius: 0.75rem !important; overflow: hidden; }
        .note-toolbar { background: #f8fafc !important; border-color: #e5e7eb !important; }
        .note-editing-area { min-height: 350px; }
    </style>

    <div class="max-w-5xl mx-auto px-6 py-8">
        <h1 class="text-3xl font-bold tracking-tight mb-6">日记管理</h1>

        <?php if (isset($_GET['saved'])): ?>
            <div class="mb-6 p-4 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-2xl">日记已保存！</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="mb-6 p-4 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-2xl">日记已删除。</div>
        <?php endif; ?>

        <!-- Editor -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7 mb-10">
            <h3 class="font-semibold text-lg mb-5"><?= $editDiary ? '编辑日记' : '新建日记' ?></h3>
            
            <form method="POST" id="diary-form">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= $editDiary ? $editDiary['id'] : '' ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-5">
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold text-zinc-500">标题</label>
                        <input type="text" name="title" required value="<?= htmlspecialchars($editDiary['title'] ?? '') ?>" 
                               class="mt-1 w-full h-12 px-5 text-lg font-medium rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-zinc-500">日期</label>
                        <input type="date" name="date" value="<?= htmlspecialchars($editDiary['date'] ?? date('Y-m-d')) ?>" 
                               class="mt-1 w-full h-12 px-5 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-zinc-500">状态</label>
                        <select name="status" class="mt-1 w-full h-12 px-5 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent">
                            <option value="published" <?= ($editDiary['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>已发布</option>
                            <option value="draft" <?= ($editDiary['status'] ?? '') === 'draft' ? 'selected' : '' ?>>草稿</option>
                        </select>
                    </div>
                </div>
                <div class="mb-5 flex items-center gap-x-2">
                    <input type="checkbox" id="pinned" name="pinned" value="1" <?= !empty($editDiary['pinned']) ? 'checked' : '' ?> 
                           class="w-5 h-5 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="pinned" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">置顶</label>
                </div>
                
                <div class="mb-5">
                    <label class="text-xs font-semibold text-zinc-500">摘要</label>
                    <textarea name="excerpt" rows="2" class="mt-1 w-full px-5 py-3 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent"><?= htmlspecialchars($editDiary['excerpt'] ?? '') ?></textarea>
                </div>
                
                <div class="mb-5">
                    <label class="text-xs font-semibold text-zinc-500 mb-1 block">内容（富文本）</label>
                    <textarea id="summernote-editor" name="content"><?= htmlspecialchars($editDiary['content'] ?? '') ?></textarea>
                </div>
                
                <div class="mb-6">
                    <label class="text-xs font-semibold text-zinc-500">标签（逗号分隔）</label>
                    <input type="text" name="tags" value="<?= htmlspecialchars(implode(', ', $editDiary['tags'] ?? [])) ?>" 
                           class="mt-1 w-full h-11 px-5 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent" placeholder="技术, 生活">
                </div>
                
                <div class="flex items-center gap-x-3">
                    <button type="submit" name="save_diary" class="px-8 h-11 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold flex items-center gap-x-2">
                        <i class="fa-solid fa-save"></i>
                        <span><?= $editDiary ? '更新日记' : '发布新日记' ?></span>
                    </button>
                    <?php if ($editDiary): ?>
                        <a href="diary.php" class="px-6 h-11 flex items-center text-sm text-zinc-500">取消</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Diary List -->
        <div>
            <h3 class="font-semibold text-lg mb-4 px-1">日记列表（<?= count($diaries) ?>）</h3>
            <?php if (empty($diaries)): ?>
                <p class="text-zinc-400 px-1">暂无日记。</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($diaries as $d): ?>
                        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-5 py-4 flex items-center justify-between gap-x-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-x-3">
                                    <span class="text-xs font-mono text-zinc-400"><?= htmlspecialchars($d['date']) ?></span>
                                    <?php if (!empty($d['pinned'])): ?>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 font-semibold">置顶</span>
                                    <?php endif; ?>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full <?= ($d['status'] ?? 'published') === 'published' ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400' ?> font-semibold"><?= ($d['status'] ?? 'published') === 'published' ? '已发布' : '草稿' ?></span>
                                    <span class="font-medium truncate"><?= htmlspecialchars($d['title']) ?></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-x-2 flex-shrink-0">
                                <a href="../diary-detail.php?id=<?= $d['id'] ?>" target="_blank" class="text-xs px-4 h-9 flex items-center rounded-lg border">预览</a>
                                <a href="?edit=<?= $d['id'] ?>" class="text-xs px-4 h-9 flex items-center rounded-lg bg-zinc-900 dark:bg-white text-white dark:text-zinc-900">编辑</a>
                                <form method="POST" class="inline" onsubmit="return confirm('确认永久删除？')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="delete_id" value="<?= (int)$d['id'] ?>">
                                    <button type="submit" class="text-xs px-3 h-9 flex items-center text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('#summernote-editor').summernote({
            height: 380,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            callbacks: {
                onImageUpload: function(files) {
                    for (var i = 0; i < files.length; i++) {
                        uploadImage(files[i]);
                    }
                }
            }
        });
    });
    
    function uploadImage(file) {
        var data = new FormData();
        data.append('file', file);
        $.ajax({
            url: 'upload.php',
            type: 'POST',
            data: data,
            contentType: false,
            processData: false,
            success: function(res) {
                if (res.location) {
                    $('#summernote-editor').summernote('insertImage', res.location);
                }
            }
        });
    }
    </script>
</body>
</html>