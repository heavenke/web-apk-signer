<?php
// config_loader.php

/** * 配置加载器：从 /opt/config/.env 读取环境变量 * 要求 .env 文件存在且可读，格式为 KEY=VALUE */

// 定义 .env 文件路径
$envFile = '/opt/config/.env';

if (!file_exists($envFile)) {
    throw new RuntimeException("配置文件未找到: $envFile");
}

// 解析 .env 文件
$env = [];
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    // 跳过注释行
    if (strpos(trim($line), '#') === 0) {
        continue;
    }
    // 跳过无效行（不含 =）
    if (strpos($line, '=') === false) {
        continue;
    }
    // 分割键和值
    [$key, $value] = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    // 移除首尾的单引号或双引号（如果存在）
    if (preg_match('/^([\'"])(.*)\1$/', $value, $matches)) {
        $value = $matches[2];
    }
    $env[$key] = $value;
}

// 必需的配置项列表
$requiredKeys = [
    'KEYSTORE_PATH', 'KEYSTORE_PASSWORD', 'KEY_ALIAS', 'KEY_PASSWORD',
    'JAVA_PATH', 'APKSIGNER_JAR', 'ZIPALIGN_PATH', 'JARSIGNER_PATH', 'TEMP_DIR'
];

// 检查是否所有必需项都存在
foreach ($requiredKeys as $key) {
    if (!isset($env[$key]) || $env[$key] === '') {
        throw new RuntimeException("缺少必需的配置项: $key");
    }
}

// 返回配置数组
return [
    'keystore_path' => $env['KEYSTORE_PATH'],
    'keystore_password' => $env['KEYSTORE_PASSWORD'],
    'key_alias' => $env['KEY_ALIAS'],
    'key_password' => $env['KEY_PASSWORD'],
    'java_path' => $env['JAVA_PATH'],
    'apksigner_jar' => $env['APKSIGNER_JAR'],
    'zipalign_path' => $env['ZIPALIGN_PATH'],
    'jarsigner_path' => $env['JARSIGNER_PATH'],
    'temp_dir' => $env['TEMP_DIR'],
    'aapt_path' => $env['AAPT_PATH'] ?? null, // 新增：aapt 路径
];
?>