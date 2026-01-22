<?php
// ===== 兼容 PHP 7.x 的 str_ends_with 函数 =====
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        if ($needle === '') return true;
        $len = strlen($needle);
        return substr($haystack, -$len) === $needle;
    }
}

session_start();

// 清理上一次的签名文件
if (!empty($_SESSION['signed_file']) && file_exists($_SESSION['signed_file'])) {
    @unlink($_SESSION['signed_file']);
}
unset($_SESSION['signed_file'], $_SESSION['signed_name']);

// 加载依赖
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/config_loader.php';
require_once __DIR__ . '/lib/SecurityValidator.php';
require_once __DIR__ . '/handlers/ChunkUploadHandler.php';
require_once __DIR__ . '/handlers/SignHandler.php';

// ===== 分块上传处理（用于 iOS/夸克）=====
if (isset($_GET['action']) && $_GET['action'] === 'chunk_upload') {
    $handler = new ChunkUploadHandler(CHUNKS_DIR);
    $handler->handleUpload();
    exit; // 确保处理完后退出
}

// ===== 处理分块上传后的签名触发 =====
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$isUploadRequest = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']));
$triggerSign = false;
$isChunkedUpload = false; // 新增标志，区分是否为分块上传

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['trigger_sign'])) {
    if (empty($_SESSION['pending_chunked_file']) || !file_exists($_SESSION['pending_chunked_file'])) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => '<div class="message error">无待处理文件。</div>']);
            exit;
        }
    }

    // 构造伪 $_FILES
    $tempFile = $_SESSION['pending_chunked_file'];
    $originalName = $_SESSION['pending_chunked_filename'];
    $_FILES['file'] = [
        'name' => $originalName,
        'type' => '',
        'tmp_name' => $tempFile,
        'error' => 0,
        'size' => filesize($tempFile)
    ];

    // 设置签名选项（从 POST 继承）
    $_POST['v1'] = !empty($_POST['v1']);
    $_POST['v2'] = !empty($_POST['v2']);
    $_POST['v3'] = !empty($_POST['v3']);

    $isUploadRequest = true;
    $triggerSign = true;
    $isChunkedUpload = true; // 标记为分块上传
}

// 确保 dl/ 目录存在
if (!is_dir(DOWNLOAD_DIR)) {
    mkdir(DOWNLOAD_DIR, 0755, true);
}

// 加载配置
$config = require __DIR__ . '/config_loader.php';

// 验证配置路径
if (!SecurityValidator::validateConfigPaths($config)) {
    die("Configuration Error: Invalid paths detected.");
}

$message = '';
$is_success = false;
$packageName = 'unknown'; // 初始化包名变量

// 仅当是上传请求时处理文件并记录日志
if ($isUploadRequest) {
    // ===== 🔒 限制上传频率（30秒内仅允许1次）=====
    $clientIP = getRealIP();
    $rateLimitKey = SESSION_KEY_LAST_UPLOAD_TIME_PREFIX . md5($clientIP);
    $now = time();
    $lastTime = $_SESSION[$rateLimitKey] ?? 0;

    if ($now - $lastTime < RATE_LIMIT_SECONDS && !$triggerSign) { // 触发签名时不检查频率
        $message = "操作过于频繁，请" . RATE_LIMIT_SECONDS . "秒后再试。";
        $is_success = false;

        // 记录日志
        if (!is_dir(LOG_DIR)) mkdir(LOG_DIR, 0755, true);
        $logFile = LOG_DIR . '/signlog_' . date('YmdHis') . '.log';
        file_put_contents($logFile, implode("\n", [
            "=== 频率限制触发 ===",
            "时间: " . date('Y-m-d H:i:s'),
            "客户端IP: " . $clientIP,
            "结果: 拒绝 - 操作过于频繁"
        ]) . "\n", LOCK_EX);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => '<div class="message error">操作过于频繁，请30秒后再试。</div>'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    } else {
        $_SESSION[$rateLimitKey] = $now;
    }

    // ===== 初始化日志 =====
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    $logFile = LOG_DIR . '/signlog_' . date('YmdHis') . '.log';
    $logData = [];
    $logData[] = "=== 签名操作日志 ===";
    $logData[] = "时间: " . date('Y-m-d H:i:s');
    $logData[] = "客户端IP: " . $clientIP;

    $file = $_FILES['file'];
    $logData[] = "上传文件名: " . ($file['name'] ?? 'N/A');
    $logData[] = "文件大小: " . round(($file['size'] ?? 0) / (1024 * 1024), 2) . " MB";
    $logData[] = "上传类型: " . ($isChunkedUpload ? '分块上传' : '常规上传');

    // --- 文件验证 ---
    $validationResult = SecurityValidator::validateUploadedFile($file);
    if (!$validationResult['valid']) {
        $message = $validationResult['message'];
        $logData[] = "结果: 失败 - " . $message;
    } else {
        $filename = strtolower($file['name']);
        $isJar = str_ends_with($filename, '.jar');
        $isApk = str_ends_with($filename, '.apk');
        $logData[] = "文件类型: " . ($isJar ? 'JAR' : ($isApk ? 'APK' : 'UNKNOWN'));

        if ($isApk) {
            $v1 = !empty($_POST['v1']);
            $v2 = !empty($_POST['v2']);
            $v3 = !empty($_POST['v3']);
            $logData[] = "签名方案: V1=" . ($v1 ? 'ON' : 'OFF') . ", V2=" . ($v2 ? 'ON' : 'OFF') . ", V3=" . ($v3 ? 'ON' : 'OFF');

            if (!$v1 && !$v2 && !$v3) {
                $message = "请至少选择一种签名方案（V1/V2/V3）";
                $logData[] = "结果: 失败 - " . $message;
            }
        }

        if (empty($message)) {
            // --- 签名处理 ---
            $signHandler = new SignHandler($config, DOWNLOAD_DIR);
            $result = $signHandler->processSignature($file, $isChunkedUpload, $_POST['v1'] ?? false, $_POST['v2'] ?? false, $_POST['v3'] ?? false);

            // --- 🔒 获取并记录包名 ---
            $packageName = $result['package_name'] ?? 'unknown';
            if ($isApk) { // 只在 APK 的情况下记录
                $logData[] = "APK包名: " . $packageName;
            }
            // --- END OF ADDITION ---

            $is_success = $result['success'];
            $message = $result['message'];
            $logData[] = $is_success ? "结果: 成功" : "结果: 失败 - " . $result['message'];
        }
    }

    file_put_contents($logFile, implode("\n", $logData) . "\n", LOCK_EX);
    chmod($logFile, 0644);

    // 清理会话
    if ($isChunkedUpload) {
        unset($_SESSION['pending_chunked_file'], $_SESSION['pending_chunked_filename']);
    }
} else {
    $message = '';
    $is_success = false;
}

// 处理 AJAX 响应
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    if (!$isUploadRequest && empty($_POST['trigger_sign'])) {
        echo json_encode(['success' => false, 'message' => '无效请求'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => $is_success,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    exit;
}

// 如果不是 AJAX 请求，则返回错误或重定向（根据需要）
if (!$isAjax && $isUploadRequest) {
    // 对于非AJAX的上传请求，可能需要返回一个简单的HTML页面或重定向
    // 但通常前端都是通过AJAX提交的，所以这里可以简单处理
    echo "Invalid Request Method for Direct Access.";
    exit;
}

// 如果没有上传请求，且不是AJAX，可能是直接访问该处理文件
echo "Access Denied.";
exit;

?>