<?php
// constants.php

// --- 文件类型 ---
define('FILE_TYPE_APK', 'apk');
define('FILE_TYPE_JAR', 'jar');

// --- 临时文件前缀 ---
define('TEMP_FILE_PREFIX_UPLOAD', 'upload_');
define('TEMP_FILE_PREFIX_SIGNED', 'signed_');
define('TEMP_FILE_PREFIX_MERGED', 'merged_');

// --- 上传限制 ---
define('MAX_FILE_SIZE_BYTES', 256 * 1024 * 1024); // 256 MB
define('CHUNK_SIZE_BYTES', 2 * 1024 * 1024);      // 2 MB per chunk

// --- 速率限制 ---
define('RATE_LIMIT_SECONDS', 30); // 30秒内最多一次上传

// --- Session Keys ---
define('SESSION_KEY_PENDING_CHUNKED_FILE', 'pending_chunked_file');
define('SESSION_KEY_PENDING_CHUNKED_FILENAME', 'pending_chunked_filename');
define('SESSION_KEY_LAST_UPLOAD_TIME_PREFIX', 'upload_last_time_');

// --- Log Directory ---
define('LOG_DIR', __DIR__ . '/log');

// --- Chunks Directory ---
define('CHUNKS_DIR', __DIR__ . '/chunks');

// --- Download Directory ---
define('DOWNLOAD_DIR', __DIR__ . '/dl');

?>