<?php
// handlers/SignHandler.php

require_once __DIR__ . '/../constants.php';
require_once __DIR__ . '/../functions.php';

class SignHandler {
    private $config;
    private $downloadDir;

    public function __construct($config, $downloadDir) {
        $this->config = $config;
        $this->downloadDir = $downloadDir;
        if (!is_dir($this->downloadDir)) {
            mkdir($this->downloadDir, 0755, true);
        }
    }

    // 添加一个新方法用于提取包名
    public function extractPackageName($apkFilePath) {
        $packageName = 'unknown';
        // 检查 aapt 路径是否存在且可执行，以及 APK 文件是否存在
        if (!empty($this->config['aapt_path']) && is_executable($this->config['aapt_path']) && file_exists($apkFilePath)) {
            // 使用与旧版相同的命令和参数
            $cmd = sprintf(
                '%s dump badging %s 2>/dev/null | awk -F"\'" \'/^package:/ {print $2}\'',
                escapeshellarg($this->config['aapt_path']),
                escapeshellarg($apkFilePath)
            );
            $output = [];
            $retval = 0; // 初始化返回值
            exec($cmd, $output, $retval);

            if ($retval === 0 && !empty($output[0]) && trim($output[0]) !== '') {
                $packageName = trim($output[0]);
            } else {
                error_log("Failed to extract package name from $apkFilePath. Command returned: $retval. Output: " . print_r($output, true));
            }
        } else {
            if (empty($this->config['aapt_path'])) {
                error_log("Cannot extract package name: aapt_path is not configured.");
            } elseif (!is_executable($this->config['aapt_path'])) {
                error_log("Cannot extract package name: aapt_path (" . $this->config['aapt_path'] . ") is not executable.");
            } elseif (!file_exists($apkFilePath)) {
                error_log("Cannot extract package name: APK file does not exist at $apkFilePath.");
            }
        }
        return $packageName;
    }


    public function processSignature($fileInfo, $isChunkedUpload = false, $v1 = false, $v2 = false, $v3 = false) {
        $inputFile = tempnam($this->config['temp_dir'], TEMP_FILE_PREFIX_UPLOAD) . '.' . pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
        $outputFile = tempnam($this->config['temp_dir'], TEMP_FILE_PREFIX_SIGNED) . '.' . pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
        $alignedApk = tempnam($this->config['temp_dir'], TEMP_FILE_PREFIX_SIGNED) . '.' . FILE_TYPE_APK;

        // 1. 保存上传文件到临时位置
        if ($isChunkedUpload) {
            if (!file_exists($fileInfo['tmp_name'])) {
                return ['success' => false, 'message' => '源文件不存在，无法处理。'];
            }
            if (!copy($fileInfo['tmp_name'], $inputFile)) {
                return ['success' => false, 'message' => '无法复制上传文件。'];
            }
        } else {
            if (!move_uploaded_file($fileInfo['tmp_name'], $inputFile)) {
                return ['success' => false, 'message' => '无法保存上传文件。'];
            }
        }

        $isApk = str_ends_with(strtolower($inputFile), '.' . FILE_TYPE_APK);
        $isJar = str_ends_with(strtolower($inputFile), '.' . FILE_TYPE_JAR);

        $result = ['success' => false, 'message' => ''];

        try {
            // 2. APK 包名提取 (可选，用于日志或返回给调用者)
            $packageName = 'unknown';
            if ($isApk) {
                $packageName = $this->extractPackageName($inputFile);
            }
            // 将包名放入结果中，以便调用者（如 upload_handler.php）可以获取
            $result['package_name'] = $packageName;

            // 3. 执行签名
            if ($isJar) {
                $cmd = sprintf(
                    '%s -keystore %s -storepass %s -keypass %s -digestalg SHA-256 -sigalg SHA256withRSA -signedjar %s %s %s 2>&1',
                    escapeshellarg($this->config['jarsigner_path']),
                    escapeshellarg($this->config['keystore_path']),
                    escapeshellarg($this->config['keystore_password']),
                    escapeshellarg($this->config['key_password']),
                    escapeshellarg($outputFile),
                    escapeshellarg($inputFile),
                    escapeshellarg($this->config['key_alias'])
                );
                exec($cmd, $output, $result_code);

                if ($result_code === 0 && file_exists($outputFile)) {
                    $outputFileName = $this->getSafeOutputFilename($fileInfo['name'], FILE_TYPE_JAR);
                    $dlPath = $this->downloadDir . '/' . $outputFileName;
                    if (file_exists($dlPath)) {
                        unlink($dlPath); // 删除旧文件
                    }
                    if (copy($outputFile, $dlPath)) {
                        chmod($dlPath, 0644);
                        $dlUrl = './dl/' . urlencode($outputFileName);
                        $result['success'] = true;
                        $result['message'] = '<div class="success-message">
                                                 <p class="success-title">JAR 签名成功！</p>
                                                 <a href="' . htmlspecialchars($dlUrl) . '" class="btn-download">点击下载</a>
                                                 <div class="file-name">' . htmlspecialchars($outputFileName) . '</div>
                                                 <p class="download-tip">提示: 下载链接有效期为1小时，请尽快下载。</p>
                                             </div>';
                    } else {
                        $result['message'] = '无法生成下载文件。';
                    }
                } else {
                    $result['message'] = "JAR 签名失败:\n" . redact_paths(implode("\n", $output));
                }
            } else if ($isApk) { // For APK
                if (!$v1 && !$v2 && !$v3) {
                    $result['message'] = "请至少选择一种签名方案（V1/V2/V3）";
                } else {
                    $apksignerArgs = ['sign'];
                    if ($v1) {
                        $apksignerArgs[] = '--v1-signing-enabled';
                        $apksignerArgs[] = 'true';
                    }
                    if ($v2) {
                        $apksignerArgs[] = '--v2-signing-enabled';
                        $apksignerArgs[] = 'true';
                    }
                    if ($v3) {
                        $apksignerArgs[] = '--v3-signing-enabled';
                        $apksignerArgs[] = 'true';
                    }
                    $apksignerArgs = array_merge($apksignerArgs, [
                        '--ks', $this->config['keystore_path'],
                        '--ks-key-alias', $this->config['key_alias'],
                        '--ks-pass', 'pass:' . $this->config['keystore_password'],
                        '--key-pass', 'pass:' . $this->config['key_password'],
                        '--out', $outputFile,
                        $inputFile
                    ]);
                    $escapedArgs = array_map('escapeshellarg', $apksignerArgs);
                    $apksignerCmd = escapeshellarg($this->config['java_path']) . ' -jar ' . escapeshellarg($this->config['apksigner_jar']) . ' ' . implode(' ', $escapedArgs);
                    $cmd = "HOME=/tmp " . $apksignerCmd . " 2>&1";
                    exec($cmd, $output, $result_code);

                    if ($result_code !== 0) {
                        $result['message'] = "APK 签名失败:\n" . redact_paths(implode("\n", $output));
                    } else {
                        // 4. Zipalign
                        $cmdAlign = sprintf(
                            'HOME=/tmp %s -f -v 4 %s %s 2>&1',
                            escapeshellarg($this->config['zipalign_path']),
                            escapeshellarg($outputFile),
                            escapeshellarg($alignedApk)
                        );
                        exec($cmdAlign, $alignOutput, $alignResultCode);

                        if ($alignResultCode === 0 && file_exists($alignedApk)) {
                            $outputFileName = $this->getSafeOutputFilename($fileInfo['name'], FILE_TYPE_APK);
                            $dlPath = $this->downloadDir . '/' . $outputFileName;
                            if (file_exists($dlPath)) {
                                unlink($dlPath); // 删除旧文件
                            }
                            if (copy($alignedApk, $dlPath)) {
                                chmod($dlPath, 0644);
                                $dlUrl = './dl/' . urlencode($outputFileName);
                                $result['success'] = true;
                                $result['message'] = '<div class="success-message">
                                                         <p class="success-title">APK 签名成功！</p>
                                                         <a href="' . htmlspecialchars($dlUrl) . '" class="btn-download">点击下载</a>
                                                         <div class="file-name">' . htmlspecialchars($outputFileName) . '</div>
                                                         <p class="download-tip">提示: 下载链接有效期为1小时，请尽快下载。</p>
                                                     </div>';
                            } else {
                                $result['message'] = '无法生成下载文件。';
                            }
                        } else {
                            $result['message'] = "Zipalign 失败:\n" . redact_paths(implode("\n", $alignOutput));
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $result['message'] = '处理过程中发生错误: ' . $e->getMessage();
        } finally {
            // 5. 清理临时文件
            foreach ([$inputFile, $outputFile, $alignedApk] as $tempFile) {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
            // 清理分块上传的临时文件
            if ($isChunkedUpload && !empty($_SESSION[SESSION_KEY_PENDING_CHUNKED_FILE]) && file_exists($_SESSION[SESSION_KEY_PENDING_CHUNKED_FILE])) {
                @unlink($_SESSION[SESSION_KEY_PENDING_CHUNKED_FILE]);
            }
        }

        return $result;
    }

    private function getSafeOutputFilename($originalName, $extension) {
        $originalName = pathinfo($originalName, PATHINFO_FILENAME);
        // 确保名称安全并添加唯一后缀
        $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $uniqueSuffix = '_' . bin2hex(random_bytes(4));
        return $cleanName . $uniqueSuffix . '-signed.' . $extension;
    }
}
?>