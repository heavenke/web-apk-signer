<?php
// handlers/ChunkUploadHandler.php

require_once __DIR__ . '/../constants.php';
require_once __DIR__ . '/../functions.php';

class ChunkUploadHandler {

    private $chunksDir;

    public function __construct($chunksDir) {
        $this->chunksDir = $chunksDir;
        if (!is_dir($this->chunksDir)) {
            mkdir($this->chunksDir, 0755, true);
        }
    }

    public function handleUpload() {
        $uploadId = $_POST['upload_id'] ?? null;
        $index = intval($_POST['index'] ?? -1);
        $totalChunks = intval($_POST['total_chunks'] ?? 1);
        $originalName = $_POST['filename'] ?? 'unknown';

        $fileType = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!$uploadId || $index < 0 || !in_array($fileType, [FILE_TYPE_APK, FILE_TYPE_JAR])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid parameters']);
            exit;
        }

        $chunkFile = $this->chunksDir . '/' . $uploadId . '_' . str_pad($index, 6, '0', STR_PAD_LEFT) . '.part';

        if (!isset($_FILES['chunk']) || $_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'Chunk upload failed']);
            exit;
        }

        if (!move_uploaded_file($_FILES['chunk']['tmp_name'], $chunkFile)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save chunk']);
            exit;
        }

        // 检查是否所有分片都已上传
        $allReceived = $this->checkAllChunksReceived($uploadId, $totalChunks);

        if ($allReceived) {
            $finalFile = $this->mergeChunks($uploadId, $totalChunks, $fileType);
            if ($finalFile) {
                // 保存到 session，供后续签名使用
                $_SESSION[SESSION_KEY_PENDING_CHUNKED_FILE] = $finalFile;
                $_SESSION[SESSION_KEY_PENDING_CHUNKED_FILENAME] = $originalName;
                echo json_encode(['done' => true, 'message' => 'All chunks received and merged.']);
            } else {
                echo json_encode(['error' => 'Failed to merge chunks']);
            }
        } else {
            echo json_encode(['done' => false, 'message' => 'Chunk received.']);
        }
        exit;
    }

    private function checkAllChunksReceived($uploadId, $totalChunks) {
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunk = $this->chunksDir . '/' . $uploadId . '_' . str_pad($i, 6, '0', STR_PAD_LEFT) . '.part';
            if (!file_exists($chunk)) {
                return false;
            }
        }
        return true;
    }

    private function mergeChunks($uploadId, $totalChunks, $fileType) {
        $finalFile = tempnam(sys_get_temp_dir(), TEMP_FILE_PREFIX_MERGED) . '.' . $fileType;
        $fp = fopen($finalFile, 'wb');
        if (!$fp) {
            error_log("Failed to create merged file: $finalFile");
            return false;
        }

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunk = $this->chunksDir . '/' . $uploadId . '_' . str_pad($i, 6, '0', STR_PAD_LEFT) . '.part';
            $chunkContent = file_get_contents($chunk);
            if ($chunkContent === false) {
                error_log("Failed to read chunk: $chunk");
                fclose($fp);
                unlink($finalFile);
                return false;
            }
            fwrite($fp, $chunkContent);
            unlink($chunk); // 清理分片
        }
        fclose($fp);
        return $finalFile;
    }
}
?>