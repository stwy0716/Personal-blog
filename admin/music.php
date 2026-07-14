<?php
require_once __DIR__ . '/auth.php';
handleAdminAuth();

$musicFile = __DIR__ . '/../data/music.json';
$musicList = file_exists($musicFile) ? json_decode(file_get_contents($musicFile), true) : [];
$uploadDir = __DIR__ . '/../uploads/music/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

// Delete music
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    foreach ($musicList as $k => $m) {
        if ((int)$m['id'] === $delId) {
            if (!empty($m['file']) && file_exists(__DIR__ . '/../' . $m['file'])) {
                unlink(__DIR__ . '/../' . $m['file']);
            }
            unset($musicList[$k]);
            logOperation('music_delete', '删除了音乐：' . $m['title']);
            break;
        }
    }
    $musicList = array_values($musicList);
    file_put_contents($musicFile, json_encode($musicList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Location: music.php?deleted=1');
    exit;
}

// Toggle single track enabled status
if (isset($_GET['toggle_enabled'])) {
    $toggleId = (int)$_GET['toggle_enabled'];
    foreach ($musicList as &$m) {
        if ((int)$m['id'] === $toggleId) {
            $m['enabled'] = empty($m['enabled']) ? true : false;
            logOperation('music_toggle', ($m['enabled'] ? '启用' : '禁用') . '了音乐：' . $m['title']);
            break;
        }
    }
    file_put_contents($musicFile, json_encode($musicList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Location: music.php?toggled=1');
    exit;
}

// Bulk enable all
if (isset($_GET['enable_all'])) {
    foreach ($musicList as &$m) {
        $m['enabled'] = true;
    }
    file_put_contents($musicFile, json_encode($musicList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    logOperation('music_bulk_enable', '全部启用了所有音乐');
    header('Location: music.php?bulk_enabled=1');
    exit;
}

// Bulk disable all
if (isset($_GET['disable_all'])) {
    foreach ($musicList as &$m) {
        $m['enabled'] = false;
    }
    file_put_contents($musicFile, json_encode($musicList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    logOperation('music_bulk_disable', '全部禁用了所有音乐');
    header('Location: music.php?bulk_disabled=1');
    exit;
}

// Set active track
if (isset($_GET['set_active'])) {
    $activeId = (int)$_GET['set_active'];
    foreach ($musicList as &$m) {
        $m['active'] = ((int)$m['id'] === $activeId);
    }
    file_put_contents($musicFile, json_encode($musicList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    logOperation('music_active', '设置了活跃音乐曲目 #' . $activeId);
    header('Location: music.php?activated=1');
    exit;
}

// Upload and add music
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_music'])) {
    $title = trim($_POST['music_title'] ?? '');
    $artist = trim($_POST['music_artist'] ?? '');
    
    if ($title && !empty($_FILES['music_file']['name'])) {
        $file = $_FILES['music_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['mp3', 'ogg', 'wav', 'flac', 'aac', 'm4a'];
        
        if (in_array($ext, $allowed) && $file['error'] === 0) {
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $dest = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $newId = 1;
                if (!empty($musicList)) {
                    $ids = array_column($musicList, 'id');
                    $newId = max($ids) + 1;
                }
                
                $isFirst = empty($musicList);
                $musicList[] = [
                    'id' => $newId,
                    'title' => $title,
                    'artist' => $artist,
                    'file' => 'uploads/music/' . $filename,
                    'active' => $isFirst,
                    'enabled' => true,
                    'added' => date('Y-m-d H:i')
                ];
                
                file_put_contents($musicFile, json_encode($musicList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                logOperation('music_add', '添加了音乐：' . $title);
                header('Location: music.php?added=1');
                exit;
            }
        }
    }
    $upload_error = '上传失败。请检查文件类型（mp3/ogg/wav/flac/aac/m4a）和大小。';
}

$admin_page_title = '音乐管理';
include __DIR__ . '/admin_header.php';
?>
    <div class="max-w-4xl mx-auto px-6 py-8">
        <h1 class="text-3xl font-bold tracking-tight mb-6">音乐管理</h1>
        <p class="text-sm text-zinc-500 mb-6">上传音乐在前台播放。设置一首曲目为活跃状态以自动播放。</p>

        <?php if (isset($_GET['added'])): ?>
            <div class="mb-4 p-3 bg-emerald-100 text-emerald-700 rounded-xl text-sm">音乐已添加！</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="mb-4 p-3 bg-amber-100 text-amber-700 rounded-xl text-sm">音乐已删除。</div>
        <?php endif; ?>
        <?php if (isset($_GET['activated'])): ?>
            <div class="mb-4 p-3 bg-blue-100 text-blue-700 rounded-xl text-sm">活跃曲目已更新。</div>
        <?php endif; ?>
        <?php if (isset($_GET['toggled'])): ?>
            <div class="mb-4 p-3 bg-emerald-100 text-emerald-700 rounded-xl text-sm">曲目启用状态已更新。</div>
        <?php endif; ?>
        <?php if (isset($_GET['bulk_enabled'])): ?>
            <div class="mb-4 p-3 bg-emerald-100 text-emerald-700 rounded-xl text-sm">已全部启用。</div>
        <?php endif; ?>
        <?php if (isset($_GET['bulk_disabled'])): ?>
            <div class="mb-4 p-3 bg-amber-100 text-amber-700 rounded-xl text-sm">已全部禁用。</div>
        <?php endif; ?>
        <?php if (!empty($upload_error)): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm"><?= $upload_error ?></div>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7 mb-8">
            <h3 class="font-semibold text-lg mb-5 flex items-center gap-x-2">
                <i class="fa-solid fa-upload text-indigo-500"></i> 上传音乐
            </h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-zinc-500 block mb-1.5">歌曲名称</label>
                        <input type="text" name="music_title" required class="w-full px-4 h-11 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent" placeholder="歌曲名">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-zinc-500 block mb-1.5">歌手</label>
                        <input type="text" name="music_artist" class="w-full px-4 h-11 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent" placeholder="歌手名">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-medium text-zinc-500 block mb-1.5">音频文件（mp3/ogg/wav/flac/aac/m4a）</label>
                    <input type="file" name="music_file" accept="audio/*" required class="w-full px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-transparent text-sm">
                </div>
                <button type="submit" name="add_music" class="px-6 h-11 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold flex items-center gap-x-2">
                    <i class="fa-solid fa-plus"></i> 添加音乐
                </button>
            </form>
        </div>

        <!-- Music List -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-7">
            <h3 class="font-semibold text-lg mb-5">音乐库（<?= count($musicList) ?>）</h3>
            <?php if (!empty($musicList)): ?>
            <div class="flex gap-2 mb-4">
                <a href="?enable_all=1" class="text-xs px-3 h-8 flex items-center rounded-lg bg-emerald-100 text-emerald-700 hover:bg-emerald-200">
                    <i class="fa-solid fa-check-double mr-1"></i> 全部启用
                </a>
                <a href="?disable_all=1" onclick="return confirm('确认禁用所有曲目？')" class="text-xs px-3 h-8 flex items-center rounded-lg bg-amber-100 text-amber-700 hover:bg-amber-200">
                    <i class="fa-solid fa-ban mr-1"></i> 全部禁用
                </a>
            </div>
            <?php endif; ?>
            <?php if (empty($musicList)): ?>
                <p class="text-zinc-400">暂未上传音乐。</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($musicList as $m): 
                        $isEnabled = !isset($m['enabled']) || $m['enabled'] !== false;
                    ?>
                        <div class="flex items-center justify-between border border-zinc-200 dark:border-zinc-700 rounded-xl px-5 py-4 <?= !empty($m['active']) ? 'bg-indigo-50 dark:bg-indigo-950/30 border-indigo-300 dark:border-indigo-700' : '' ?> <?= !$isEnabled ? 'opacity-50' : '' ?>">
                            <div class="flex items-center gap-x-4">
                                <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-xl flex items-center justify-center">
                                    <i class="fa-solid fa-music text-indigo-600 dark:text-indigo-400"></i>
                                </div>
                                <div>
                                    <div class="font-semibold"><?= htmlspecialchars($m['title']) ?></div>
                                    <div class="text-sm text-zinc-500"><?= htmlspecialchars($m['artist'] ?? '未知') ?> &middot; <?= htmlspecialchars($m['added'] ?? '') ?></div>
                                </div>
                                <?php if (!empty($m['active'])): ?>
                                    <span class="ml-2 px-2 py-0.5 text-xs bg-indigo-600 text-white rounded-full">活跃</span>
                                <?php endif; ?>
                                <?php if (!$isEnabled): ?>
                                    <span class="ml-2 px-2 py-0.5 text-xs bg-zinc-400 text-white rounded-full">已禁用</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-x-2">
                                <label class="flex items-center gap-x-1.5 cursor-pointer select-none" title="<?= $isEnabled ? '点击禁用' : '点击启用' ?>">
                                    <span class="text-xs text-zinc-500">启用</span>
                                    <input type="checkbox" <?= $isEnabled ? 'checked' : '' ?> onchange="location.href='?toggle_enabled=<?= $m['id'] ?>'" class="w-4 h-4 accent-indigo-600 rounded cursor-pointer">
                                </label>
                                <audio controls class="h-8" style="max-width: 200px">
                                    <source src="../<?= htmlspecialchars($m['file']) ?>" type="audio/mpeg">
                                </audio>
                                <?php if (empty($m['active'])): ?>
                                    <a href="?set_active=<?= $m['id'] ?>" class="text-xs px-3 h-8 flex items-center rounded-lg bg-indigo-100 text-indigo-700 hover:bg-indigo-200">
                                        <i class="fa-solid fa-play mr-1"></i> 设为活跃
                                    </a>
                                <?php endif; ?>
                                <a href="?delete=<?= $m['id'] ?>" onclick="return confirm('确认删除此曲目？')" class="text-xs px-3 h-8 flex items-center rounded-lg bg-red-100 text-red-600 hover:bg-red-200">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>