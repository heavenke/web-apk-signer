<?php
// functions.php

require_once __DIR__ . '/constants.php';

/**
 * 兼容 PHP 7.x 的 str_ends_with 函数
 */
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        if ($needle === '') return true;
        $len = strlen($needle);
        return substr($haystack, -$len) === $needle;
    }
}

/**
 * 验证是否为有效的 ZIP 文件（适用于 APK/JAR）
 */
function is_valid_zip_file($filePath) {
    $header = @file_get_contents($filePath, false, null, 0, 4);
    return $header === "PK\x03\x04";
}

/**
 * 红acted（隐藏）日志中的敏感路径信息
 */
function redact_paths($text) {
    return preg_replace('/\/[a-zA-Z0-9._-]+\/tmp\/[a-zA-Z0-9._-]+/', '[REDACTED]', $text);
}

/**
 * 获取客户端真实 IP 地址
 */
function getRealIP() {
    foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

?>