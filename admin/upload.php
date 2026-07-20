<?php
/**
 * 安全文件上传接口
 * - 使用 finfo 检测真实文件类型（非客户端MIME）
 * - 白名单校验扩展名
 * - 禁止SVG上传
 * - 禁止可执行文件扩展名
 * - 目录权限 0755
 * - 速率限制
 */

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/auth.php';
handleAdminAuth();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => '未上传文件']);
    exit;
}

// 速率限制：每分钟最多10次上传
if (!checkRateLimit('upload', 10, 60)) {
    http_response_code(429);
    echo json_encode(['error' => '上传频率超限，请稍后再试']);
    exit;
}

$file = $_FILES['file'];

// 允许的图片类型（移除了SVG）
$allowedImageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
// 允许的图片扩展名
$allowedImageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
// 允许的视频类型
$allowedVideoMimes = ['video/mp4', 'video/webm', 'video/ogg'];
// 允许的视频扩展名
$allowedVideoExts = ['mp4', 'webm', 'ogg'];

// 检测真实文件类型
$actualMime = '';
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!file_exists($file['tmp_name'])) {
    echo json_encode(['error' => '文件上传错误']);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$actualMime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

// 判断是图片还是视频，分别验证
$isImage = in_array($actualMime, $allowedImageMimes) && in_array($ext, $allowedImageExts);
$isVideo = in_array($actualMime, $allowedVideoMimes) && in_array($ext, $allowedVideoExts);

if (!$isImage && !$isVideo) {
    echo json_encode(['error' => '不支持的文件类型，仅接受图片（jpg/png/gif/webp）和视频（mp4/webm/ogg）']);
    exit;
}

// 文件大小限制
$maxSize = $isImage ? 10 * 1024 * 1024 : 100 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    echo json_encode(['error' => '文件过大，最大限制：' . ($isImage ? '10MB' : '100MB')]);
    exit;
}

// 上传目录（权限0755）
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// 生成安全的随机文件名
$prefix = $isVideo ? 'video_' : 'img_';
$filename = $prefix . date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$target = $uploadDir . $filename;

// ==================== 图像处理函数 ====================
function createImageFromType($path, $type) {
    switch ($type) {
        case IMAGETYPE_JPEG: return @imagecreatefromjpeg($path);
        case IMAGETYPE_PNG:
            $img = @imagecreatefrompng($path);
            if ($img) {
                imagealphablending($img, true);
                imagesavealpha($img, true);
            }
            return $img;
        case IMAGETYPE_GIF: return @imagecreatefromgif($path);
        case IMAGETYPE_WEBP: return @imagecreatefromwebp($path);
    }
    return null;
}

function saveImageByType($img, $path, $type, $compress = false) {
    switch ($type) {
        case IMAGETYPE_JPEG:
            return imagejpeg($img, $path, $compress ? 85 : 90);
        case IMAGETYPE_PNG:
            return imagepng($img, $path, $compress ? 6 : 6);
        case IMAGETYPE_GIF:
            return imagegif($img, $path);
        case IMAGETYPE_WEBP:
            return imagewebp($img, $path, $compress ? 85 : 90);
    }
    return false;
}

function resizeImage($src, $dest, $maxWidth) {
    if (!file_exists($src)) return false;
    $info = getimagesize($src);
    if (!$info) return false;

    list($width, $height, $type) = $info;

    if ($width <= $maxWidth) {
        copy($src, $dest);
        return true;
    }

    $ratio = $maxWidth / $width;
    $newWidth = $maxWidth;
    $newHeight = (int)($height * $ratio);

    $srcImg = createImageFromType($src, $type);
    if (!$srcImg) return false;

    $dstImg = imagecreatetruecolor($newWidth, $newHeight);
    if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
        $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
        imagefill($dstImg, 0, 0, $transparent);
    }

    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    $result = saveImageByType($dstImg, $dest, $type);

    imagedestroy($srcImg);
    imagedestroy($dstImg);
    return $result;
}

function compressOriginal($src) {
    if (!file_exists($src)) return false;
    $info = getimagesize($src);
    if (!$info) return false;

    $type = $info[2];
    if ($type == IMAGETYPE_GIF) return true; // GIF 跳过压缩

    $srcImg = createImageFromType($src, $type);
    if (!$srcImg) return false;

    $result = saveImageByType($srcImg, $src, $type, true);
    imagedestroy($srcImg);
    return $result;
}

if (move_uploaded_file($file['tmp_name'], $target)) {
    // 设置文件权限为0644
    chmod($target, 0644);

    // 返回路径相对于站点根目录
    $relativePath = 'uploads/' . $filename;
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($referer, '/admin/') !== false) {
        $relativePath = '../uploads/' . $filename;
    }

    $response = ['location' => $relativePath];

    // 图片处理：生成缩略图、中等尺寸、压缩原图
    if ($isImage && (extension_loaded('gd') || function_exists('gd_info'))) {
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $thumbFile = $baseName . '_thumb.' . $ext;
        $mediumFile = $baseName . '_medium.' . $ext;
        $thumbPath = $uploadDir . $thumbFile;
        $mediumPath = $uploadDir . $mediumFile;

        $thumbRelative = 'uploads/' . $thumbFile;
        $mediumRelative = 'uploads/' . $mediumFile;
        if (strpos($referer, '/admin/') !== false) {
            $thumbRelative = '../uploads/' . $thumbFile;
            $mediumRelative = '../uploads/' . $mediumFile;
        }

        if (resizeImage($target, $thumbPath, 300)) {
            $response['thumb'] = $thumbRelative;
        }
        if (resizeImage($target, $mediumPath, 800)) {
            $response['medium'] = $mediumRelative;
        }
        compressOriginal($target);
    }

    echo json_encode($response);
} else {
    echo json_encode(['error' => '上传失败']);
}
?>
