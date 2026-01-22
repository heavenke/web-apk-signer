# APK/JAR 签名平台

一个基于 Web 的工具，用于对 Android APK 文件和 Java JAR 文件进行数字签名。

## 功能特性

*   **支持多种文件格式**: 可上传并处理 `.apk` 和 `.jar` 文件。
*   **APK 签名**: 支持 V1 (Jar Signature)、V2 (Full APK Signature)、V3 (APK Signature) 等多种 Android APK 签名方案，用户可根据需求灵活选择。
*   **JAR 签名**: 支持对 JAR 文件进行标准的 JAR 签名。
*   **大文件支持**: 采用分块上传技术，有效解决特定浏览器（如 iOS 平台的夸克浏览器）在处理大文件上传时的兼容性问题。
*   **进度显示**: 上传过程中提供实时进度条，提升用户体验。
*   **包名提取**: 自动从 APK 文件中提取应用包名，并记录到日志中。
*   **安全校验**: 严格的文件类型、大小、格式验证，以及配置文件路径的安全性检查。
*   **速率限制**: 限制单个 IP 地址的上传频率（30秒内最多一次），防止滥用。
*   **操作日志**: 详细记录每次签名操作的时间、IP、文件信息、结果等，便于审计和排查问题。
*   **自动清理**: 自动清理上传和签名过程中产生的临时文件。

## 技术优势

*   **模块化设计**: 代码结构清晰，分为配置加载、常量定义、工具函数、安全验证、上传处理、签名处理等多个模块，易于维护和扩展。
*   **兼容性强**: 针对特定浏览器的兼容性问题进行了专门优化，确保平台在不同环境下稳定运行。
*   **安全性高**: 实施了多层次的安全验证机制，保障系统和数据安全。
*   **高效处理**: 利用成熟的 Java 工具链（`jarsigner`, `apksigner`, `zipalign`）进行签名和对齐操作，保证签名质量和效率。

## 部署教程

### 环境要求

*   **Web 服务器**: Apache, Nginx 或其他支持 PHP 的服务器。
*   **PHP 版本**: >= 7.0 (推荐 7.4 或更高版本以获得更好的性能和安全性)
*   **PHP 扩展**:
    *   `fileinfo` (用于文件类型检测)
    *   `json` (用于 AJAX 响应)
    *   `mbstring` (处理中文等多字节字符，可选但推荐)
*   **Java Runtime Environment (JRE)**: 版本 >= 8，用于执行签名工具。
*   **Android SDK Tools**: 需要 `apksigner.jar` 和 `zipalign` 工具。[点此访问Android 官网](https://developer.android.google.cn)  请下载部署Linux版本的命令行工具,文件名通常为commandlinetools-linux-********_latest.zip)
*   **Android Asset Packaging Tool (AAPT)**: (可选) 用于精确提取 APK 包名，通常包含在 Android SDK Build-tools 或 AOSP 中。
*   **JAR Signer**: 通常随 JDK/JRE 一起提供。

### 部署步骤

1.  **准备服务器**
    *   确保您的服务器满足上述环境要求。
    *   安装好 Web 服务器软件（如 Apache 或 Nginx）和 PHP。

2.  **上传代码**
    *   将您项目的所有 PHP 文件（`index.php`, `upload_handler.php`, `config_loader.php`, `functions.php`, `constants.php`, `lib/SecurityValidator.php`, `handlers/ChunkUploadHandler.php`, `handlers/SignHandler.php`）上传到您的 Web 服务器文档根目录下的一个新文件夹（例如 `apk-signer/`）。

3.  **创建配置文件**
    *   在服务器的 `/opt/config/` 目录下（或您希望的任何安全目录）创建一个名为 `.env` 的文件。
    *   编辑 `.env` 文件，填入以下配置项：
        ```bash
        # /opt/config/.env
        # Keystore 文件路径 (必需)
        KEYSTORE_PATH=/path/to/your/keystore.jks
        # Keystore 密码 (必需)
        KEYSTORE_PASSWORD=your_keystore_password
        # Key Alias (必需)
        KEY_ALIAS=your_key_alias
        # Key 密码 (必需)
        KEY_PASSWORD=your_key_password
        # Java 可执行文件路径 (必需)
        JAVA_PATH=/usr/bin/java # 示例路径，请根据实际安装位置修改
        # apksigner.jar 文件路径 (必需)
        APKSIGNER_JAR=/path/to/android/sdk/build-tools/33.0.0/lib/apksigner.jar # 示例路径，请根据实际位置修改
        # zipalign 可执行文件路径 (必需)
        ZIPALIGN_PATH=/path/to/android/sdk/build-tools/33.0.0/zipalign # 示例路径，请根据实际位置修改
        # jarsigner 可执行文件路径 (必需)
        JARSIGNER_PATH=/usr/bin/jarsigner # 示例路径，请根据实际安装位置修改
        # 临时文件存放目录 (必需)
        TEMP_DIR=/tmp
        # aapt 可执行文件路径 (可选，用于提取包名)
        AAPT_PATH=/path/to/android/sdk/build-tools/33.0.0/aapt # 示例路径，请根据实际位置修改
        ```
    *   **重要**: 请确保 `/opt/config/.env` 文件的权限足够严格，例如 `chmod 600 /opt/config/.env`，并确保 Web 服务器进程对其有读取权限。

4.  **创建必要目录**
    *   确保 Web 服务器进程可以读写以下目录：
        *   `./log/` (用于存放日志文件)
        *   `./chunks/` (用于存放上传的文件分块)
        *   `./dl/` (用于存放签名成功的文件，供用户下载)
    *   通常可以通过以下命令创建并设置权限（请根据您的 Web 服务器用户调整）：
        ```bash
        mkdir -p /path/to/your/webroot/apk-signer/log
        mkdir -p /path/to/your/webroot/apk-signer/chunks
        mkdir -p /path/to/your/webroot/apk-signer/dl
        chown -R www-data:www-data /path/to/your/webroot/apk-signer/log
        chown -R www-data:www-data /path/to/your/webroot/apk-signer/chunks
        chown -R www-data:www-data /path/to/your/webroot/apk-signer/dl
        chmod -R 755 /path/to/your/webroot/apk-signer/log
        chmod -R 755 /path/to/your/webroot/apk-signer/chunks
        chmod -R 755 /path/to/your/webroot/apk-signer/dl
        ```

5.  **配置 Web 服务器**
    *   **Apache (`.htaccess`)**: 如果使用 Apache，请确保启用了 `mod_rewrite`，并在项目根目录放置 `.htaccess` 文件（如果需要 URL 重写）。
    *   **Nginx**: 如果使用 Nginx，请确保其配置正确，能够将对 PHP 文件的请求传递给 PHP-FPM 处理器。

6.  **测试访问**
    *   在浏览器中访问您部署项目的 URL（例如 `http://yourdomain.com/apk-signer/`）。
    *   尝试上传一个 APK 或 JAR 文件进行签名，检查是否能正常工作，包括上传、签名、下载和日志记录。

7.  **配置自动清理已签名的文件**
    *   请将setup_dl_cleanup.sh移至/root目录运行它,默认每分钟执行一次删除存在超过60分钟的文件(即签名后下载链接有效期为1小时),如要自定义清理时间请自行修改此脚本.

### 注意事项

*   **安全性**: 请务必保护好您的 Keystore 文件和 `.env` 配置文件，避免泄露敏感信息。
*   **依赖路径**: 请仔细核对 `.env` 文件中所有路径的正确性，特别是 `JAVA_PATH`, `APKSIGNER_JAR`, `ZIPALIGN_PATH`, `JARSIGNER_PATH`, `AAPT_PATH`。
*   **文件权限**: 确保 Web 服务器进程对临时目录、日志目录和下载目录具有正确的读写权限。
*   **资源限制**: 检查 PHP 的 `upload_max_filesize`, `post_max_size`, `max_execution_time` 等配置，确保它们适合您的文件大小和处理时间需求。

---
Copyright &copy; 2026 [梦工厂](https://www.heavenke.cn). All Rights Reserved.