<?php
// lib/SecurityValidator.php

require_once __DIR__ . '/../constants.php';
require_once __DIR__ . '/../functions.php';

class SecurityValidator {

    private static $validExecutables = [
        'KEYSTORE_PATH',
        'APKSIGNER_JAR',
        'ZIPALIGN_PATH',
        'JARSIGNER_PATH',
        'AAPT_PATH'
    ];

    /**
     * 验证配置文件加载的路径是否安全
     */
    public static function validateConfigPaths($config) {
        foreach (self::$validExecutables as $key) {
            if (isset($config[$key])) {
                $path = $config[$key];
                // 检查路径是否包含危险字符
                if (preg_match('/[;&|$`<>]/', $path)) {
                    error_log("Security Error: Invalid character in config path for key '$key': $path");
                    return false;
                }
                // 检查文件是否存在且可执行（如果是二进制文件或 JAR）
                if (!file_exists($path)) {
                    error_log("Config Error: File not found for key '$key': $path");
                    return false;
                }
                if (in_array($key, ['JAVA_PATH', 'JARSIGNER_PATH', 'ZIPALIGN_PATH']) && !is_executable($path)) {
                    error_log("Config Error: File not executable for key '$key': $path");
                    return false;
                }
                if (in_array($key, ['APKSIGNER_JAR', 'AAPT_PATH']) && !is_readable($path)) {
                    error_log("Config Error: File not readable for key '$key': $path");
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 验证上传的文件
     */
    public static function validateUploadedFile($file, $allowedTypes = [FILE_TYPE_APK, FILE_TYPE_JAR]) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => self::getUploadErrorMessage($file['error'])];
        }

        if ($file['size'] > MAX_FILE_SIZE_BYTES) {
            return ['valid' => false, 'message' => "文件大小不能大于 " . (MAX_FILE_SIZE_BYTES / (1024 * 1024)) . "MB！"];
        }

        $filename = strtolower($file['name']);
        $fileType = pathinfo($filename, PATHINFO_EXTENSION);

        if (!in_array($fileType, $allowedTypes)) {
            return ['valid' => false, 'message' => "仅支持 " . implode(', ', array_map(function($t) { return '.'.$t; }, $allowedTypes)) . " 文件！"];
        }

        // MIME 类型验证
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return ['valid' => false, 'message' => "无法初始化文件类型检测器。"];
        }
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $validMime = false;
        switch ($fileType) {
            case FILE_TYPE_JAR:
                $validMime = in_array($mime, ['application/java-archive', 'application/zip'], true);
                break;
            case FILE_TYPE_APK:
                $validMime = in_array($mime, [
                    'application/vnd.android.package-archive',
                    'application/zip',
                    'application/x-zip-compressed',
                    'application/java-archive'
                ], true);
                break;
        }

        if (!$validMime) {
            return ['valid' => false, 'message' => "文件格式无效：MIME 类型不被接受（当前类型: $mime）。"];
        }

        // ZIP 格式验证
        if (!is_valid_zip_file($file['tmp_name'])) {
            return ['valid' => false, 'message' => "文件格式无效：必须是标准 APK 或 JAR 文件。"];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * 获取上传错误信息
     */
    private static function getUploadErrorMessage($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => '文件大小超过服务器 php.ini 限制',
            UPLOAD_ERR_FORM_SIZE => '文件大小超过表单 MAX_FILE_SIZE 限制',
            UPLOAD_ERR_PARTIAL => '文件仅部分上传',
            UPLOAD_ERR_NO_FILE => '未选择文件',
            UPLOAD_ERR_NO_TMP_DIR => '缺少临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '无法写入磁盘',
            UPLOAD_ERR_EXTENSION => '上传被扩展阻止'
        ];
        return $errors[$errorCode] ?? '未知上传错误';
    }
}
?>