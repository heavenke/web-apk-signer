<?php
// 无需任何 PHP 处理逻辑，只负责输出 HTML/CSS/JS
// 确保 constants.php 和 config_loader.php 在同级目录或子目录下，因为 JS 中可能会引用它们（尽管此处不会）
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APK/JAR 签名平台</title>
    <style>
        /* ===== 通用重置 ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* ===== 页面整体布局 —— Flex 实现三段式结构 ===== */
        body {
            background: url('/images/back.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
            margin: 0;
            color: #333;
        }

        /* ===== 主容器入场动画 ===== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .container {
            background: rgba(255, 255, 255, 0.93);
            backdrop-filter: blur(4px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 520px;
            padding: 36px;
            text-align: center;
            margin-top: 40px;
            z-index: 10;
            position: relative;
            animation: fadeInUp 0.6s ease-out forwards;
        }

        /* ===== 标题样式 ===== */
        h1 {
            color: #2c3e50;
            margin-bottom: 24px;
            font-size: 26px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .apk-logo {
            width: 32px;
            height: auto;
        }

        @media (max-width: 500px) {
            .apk-logo {
                width: 28px;
            }
        }

        /* ===== 表单布局 ===== */
        form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        /* ===== 文件上传区域 ===== */
        input[type="file"] {
            padding: 12px;
            border: 2px dashed #ccc;
            border-radius: 10px;
            background: #fafafa;
            cursor: pointer;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        /* ===== APK 签名选项（默认隐藏）===== */
        .options {
            display: none;
            flex-direction: column;
            gap: 12px;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }

        .options.show {
            display: flex;
        }

        .checkbox-group {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 15px;
        }

        /* ===== 提交按钮 ===== */
        button#submitBtn {
            background: #3498db;
            color: white;
            border: none;
            padding: 14px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        button#submitBtn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* ===== 进度条容器 ===== */
        #progressContainer {
            margin-top: 16px;
            display: none;
        }

        .progress-bar {
            height: 12px;
            background: #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            background: #3498db;
            width: 0%;
            transition: width 0.2s;
        }

        .progress-text {
            font-size: 14px;
            color: #555;
            margin-top: 4px;
        }

        /* ===== 消息提示框 —— 不变，保持原样 ===== */
        .message {
            margin-top: 24px;
            padding: 16px;
            border-radius: 10px;
            font-size: 16px;
            line-height: 1.5;
            white-space: normal;
            word-wrap: break-word;
            width: 100%;
            max-width: 520px;
            overflow: hidden;
            box-sizing: border-box;
            position: relative;
            z-index: 10;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* ===== 成功消息专用样式 ===== */
        .success-message {
            text-align: center;
            padding: 16px;
            border-radius: 10px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            width: 100%;
            max-width: 520px;
            margin: 24px auto 0;
            word-wrap: break-word;
        }

        .success-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #155724;
        }

        .btn-download {
            display: inline-block;
            margin: 12px 0;
            padding: 8px 20px;
            background: #2ecc71;
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.3s;
            font-size: 16px;
            white-space: nowrap;
            min-width: 120px;
            text-align: center;
        }

        .btn-download:hover {
            background: #27ae60;
        }

        .file-name {
            margin-top: 8px;
            font-size: 12px;
            color: #3498db;
            text-align: center;
            word-break: break-all;
        }

        .download-tip {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 8px;
            margin-bottom: 0;
        }

        /* ===== 页脚 —— 关键：固定在底部，使用 flex 布局 ===== */
        .footer-wrapper {
            width: 100%;
            max-width: 520px;
            margin-top: auto;
            text-align: center;
            z-index: 5;
        }

        .footer-content {
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 12px;
            line-height: 1.6;
            text-align: center;
            margin: 0 auto;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            text-shadow: 0 1px 1px rgba(255,255,255,0.6);
        }
    </style>
</head>
<body>

<!-- ===== 上部：主容器（上传表单）===== -->
<div class="container">
    <h1>
        <img src="/images/apk.png" alt="APK Icon" class="apk-logo"> APK / JAR 签名平台
    </h1>
    <form id="uploadForm" enctype="multipart/form-data">
        <input type="file" name="file" id="fileInput" required>
        <div class="options" id="apkOptions">
            <div>请选择签名方案：</div>
            <div class="checkbox-group">
                <label class="checkbox-item">
                    <input type="checkbox" name="v1"> V1 (JAR)
                </label>
                <label class="checkbox-item">
                    <input type="checkbox" name="v2" checked> V2
                </label>
                <label class="checkbox-item">
                    <input type="checkbox" name="v3" checked> V3
                </label>
            </div>
        </div>
        <button type="submit" id="submitBtn">上传并签名</button>
    </form>

    <!-- 进度条 -->
    <div id="progressContainer">
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
        <div class="progress-text" id="progressText">0%</div>
    </div>

    <div id="messageContainer"></div>
</div>

<!-- ===== 底部：页脚（始终贴底）===== -->
<div class="footer-wrapper">
    <div class="footer-content">
        <script src="//sdk.51.la/js-sdk-pro.min.js"></script>
        <script>LA.init({id:"L2axk8pGwfSyalvw",ck:"L2axk8pGwfSyalvw"})</script>
        <a target="_blank" title="51la网站统计" href="https://v6.51.la/land/L2axk8pGwfSyalvw">
            <img src="https://sdk.51.la/icon/1-2.png" alt="51La统计" style="vertical-align: middle; margin-right: 4px;">
        </a><br>
        Copyright &copy; 2026 <a href="https://www.heavenke.cn" target="_self" style="color: #3498db; text-decoration: none;">梦工厂</a> All Rights Reserved.<br>
        <a href="https://beian.miit.gov.cn" target="_blank" rel="noopener noreferrer" style="color: #3498db; text-decoration: none;">粤ICP备2021097857号-1</a><br>
        本站由<a href="https://www.aliyun.com" target="_blank" rel="noopener noreferrer" style="color: #3498db; text-decoration: none;">阿里云计算</a>提供CDN加速服务
    </div>
</div>

<script>
    const form = document.getElementById('uploadForm');
    const fileInput = document.getElementById('fileInput');
    const apkOptions = document.getElementById('apkOptions');
    const submitBtn = document.getElementById('submitBtn');
    const messageContainer = document.getElementById('messageContainer');
    const progressContainer = document.getElementById('progressContainer');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');

    // --- 修复：检测 iOS AND 夸克浏览器 ---
    const ua = navigator.userAgent.toLowerCase();
    const isIOS = /iphone|ipad|ipod/.test(ua) && !window.MSStream; // 检测 iOS
    const isQuark = /quark/.test(ua);                           // 检测夸克浏览器
    const useChunkedUpload = isIOS && isQuark;                  // 仅在 iOS + 夸克时启用分块上传
    console.log("Detected iOS:", isIOS, "Detected Quark:", isQuark, "Use Chunked Upload:", useChunkedUpload); // Debug log

    // 保留iOS兼容性代码
    (function() {
        const uaLower = navigator.userAgent.toLowerCase();
        const isIOSForAccept = /iphone|ipad|ipod/.test(uaLower);
        if (isIOSForAccept) {
            fileInput.removeAttribute('accept');
        } else {
            fileInput.setAttribute('accept', '.apk,.jar');
        }
    })();

    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        apkOptions.classList.toggle('show', file && file.name.toLowerCase().endsWith('.apk'));
        if (file) {
            const maxSize = 256 * 1024 * 1024;
            if (file.size > maxSize) {
                alert("文件大小不能大于 256M");
                this.value = '';
                apkOptions.classList.remove('show');
            }
        }
    });

    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function updateProgress(done, total) {
        const percent = total > 0 ? (done / total * 100).toFixed(1) : 0;
        progressFill.style.width = percent + '%';
        progressText.textContent = `${percent}% (${formatBytes(done)} / ${formatBytes(total)})`;
    }

    // ===== 分块上传函数 =====
    function uploadInChunks(file, options, successCallback, errorCallback) {
        console.log("Starting chunked upload..."); // Debug log
        const CHUNK_SIZE = 2 * 1024 * 1024; // 2MB per chunk
        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
        const uploadId = 'upload_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        let uploaded = 0;

        progressContainer.style.display = 'block';
        updateProgress(0, file.size);

        let currentChunkIndex = 0;

        function uploadNextChunk() {
            console.log(`Uploading chunk ${currentChunkIndex}/${totalChunks}`); // Debug log
            if (currentChunkIndex >= totalChunks) {
                console.log("All chunks uploaded successfully."); // Debug log
                successCallback({ success: true, uploadId, filename: file.name });
                return;
            }

            const start = currentChunkIndex * CHUNK_SIZE;
            const end = Math.min(start + CHUNK_SIZE, file.size);
            let chunk;
            try {
                 chunk = file.slice(start, end); // Attempt to slice the file
            } catch (sliceErr) {
                console.error("Error slicing file chunk:", sliceErr);
                errorCallback(new Error('文件切片失败: ' + sliceErr.message));
                return; // Exit on slice error
            }

            const formData = new FormData();
            formData.append('chunk', chunk);
            formData.append('upload_id', uploadId);
            formData.append('index', currentChunkIndex);
            formData.append('total_chunks', totalChunks);
            formData.append('filename', file.name);
            if (options.v1) formData.append('v1', '1');
            if (options.v2) formData.append('v2', '1');
            if (options.v3) formData.append('v3', '1');

            const xhr = new XMLHttpRequest();
            // --- 🔒 修改：指向新的处理文件 ---
            xhr.open('POST', 'upload_handler.php?action=chunk_upload', true);

            xhr.onload = function() {
                console.log(`Chunk ${currentChunkIndex} response loaded, status: ${xhr.status}`); // Debug log
                if (xhr.status === 200) {
                    try {
                        const result = JSON.parse(xhr.responseText);
                        if (result.error) {
                            console.error("Server error for chunk:", result.error); // Debug log
                            errorCallback(new Error(result.error || 'Upload failed'));
                            return;
                        }
                        uploaded += chunk.size;
                        updateProgress(uploaded, file.size);
                        if (result.done) {
                            console.log("All chunks reported as done by server."); // Debug log
                            successCallback({ success: true, uploadId, filename: file.name });
                            return;
                        }
                        currentChunkIndex++;
                        uploadNextChunk(); // Upload next chunk
                    } catch (parseErr) {
                        console.error("Error parsing chunk response:", parseErr); // Debug log
                        errorCallback(new Error('服务器响应解析失败: ' + parseErr.message));
                    }
                } else {
                    console.error("Network error for chunk, status:", xhr.status); // Debug log
                    errorCallback(new Error(`Upload failed with status ${xhr.status}`));
                }
            };

            xhr.onerror = function() {
                console.error("Network error occurred during chunk upload for index:", currentChunkIndex); // Debug log
                // Important: Ensure we stop further processing and reset UI on network error
                errorCallback(new Error('网络错误，分块上传失败'));
            };

            xhr.send(formData);
        }

        // Start uploading the first chunk
        uploadNextChunk();
    }


    // ===== 触发签名（合并后）=====
    function triggerSign(uploadId, filename, v1, v2, v3, successCallback, errorCallback) {
         console.log("Triggering final signature..."); // Debug log
        const formData = new FormData();
        formData.append('trigger_sign', '1');
        formData.append('upload_id', uploadId);
        formData.append('filename', filename);
        if (v1) formData.append('v1', '1');
        if (v2) formData.append('v2', '1');
        if (v3) formData.append('v3', '1');

        const xhr = new XMLHttpRequest();
        // --- 🔒 修改：指向新的处理文件 ---
        xhr.open('POST', 'upload_handler.php', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function() {
             console.log("Final signature response loaded, status:", xhr.status); // Debug log
            if (xhr.status === 200) {
                try {
                    const resp = JSON.parse(xhr.responseText);
                    successCallback(resp);
                } catch (parseErr) {
                     console.error("Error parsing final signature response:", parseErr); // Debug log
                    errorCallback(new Error('服务器响应解析失败: ' + parseErr.message));
                }
            } else {
                 console.error("Final signature request failed, status:", xhr.status); // Debug log
                errorCallback(new Error(`签名请求失败，HTTP ${xhr.status}: ${xhr.statusText}`));
            }
        };

        xhr.onerror = function() {
             console.error("Network error during final signature request"); // Debug log
            errorCallback(new Error('网络错误，签名请求失败'));
        };

        xhr.send(formData);
    }


    // --- 修复：将 submit 事件处理器改为非 async，并加强错误处理 ---
    form.addEventListener('submit', function(e) {
        console.log("Submit event fired, preventing default..."); // Debug log
        // --- 关键修复：确保 preventDefault 在最开始就被调用 ---
        e.preventDefault();
        e.stopPropagation(); // 额外添加，防止事件冒泡

        let file;
        try {
             file = fileInput.files[0];
             if (!file) {
                console.warn("No file selected after preventDefault.");
                // Re-enable button and show message
                submitBtn.disabled = false;
                submitBtn.textContent = '上传并签名';
                messageContainer.innerHTML = '<div class="message error">请先选择一个文件。</div>';
                return; // If no file, exit gracefully after UI update
            }
        } catch (fileErr) {
             console.error("Error accessing file object:", fileErr); // Debug log
             submitBtn.disabled = false;
             submitBtn.textContent = '上传并签名';
             messageContainer.innerHTML = '<div class="message error">访问文件时出错: ' + fileErr.message + '</div>';
             return;
        }


        // 清空旧消息，设置按钮状态
        messageContainer.innerHTML = '';
        submitBtn.disabled = true;
        submitBtn.textContent = '上传中...';

        // Wrap the main logic in a try-catch to catch any unexpected JS errors during execution
        try {
            // 根据设备类型选择上传方式
            if (!useChunkedUpload) {
                console.log("Using standard upload method..."); // Debug log
                // ========== 原有逻辑（非夸克）==========
                const xhr = new XMLHttpRequest();
                progressContainer.style.display = 'block';
                updateProgress(0, file.size);

                xhr.upload.onprogress = function(event) {
                    if (event.lengthComputable) {
                        updateProgress(event.loaded, event.total);
                    }
                };

                xhr.onload = function() {
                    progressContainer.style.display = 'none';
                    submitBtn.disabled = false;
                    submitBtn.textContent = '上传并签名';

                    try {
                        const data = JSON.parse(xhr.responseText);
                        const msgDiv = document.createElement('div');
                        msgDiv.className = 'message ' + (data.success ? 'success' : 'error');
                        msgDiv.innerHTML = data.message;
                        messageContainer.appendChild(msgDiv);
                    } catch (e) {
                        console.error("JSON Parse Error in standard upload:", e); // Debug log
                        messageContainer.innerHTML = '<div class="message error">服务器返回无效数据。</div>';
                    }
                };

                xhr.onerror = function() {
                    console.error("Network error in standard upload"); // Debug log
                    progressContainer.style.display = 'none';
                    submitBtn.disabled = false;
                    submitBtn.textContent = '上传并签名';
                    messageContainer.innerHTML = '<div class="message error">网络错误，请重试。</div>';
                };

                // Manually construct FormData instead of using the form element
                const formData = new FormData();
                formData.append('file', file, file.name); // Explicitly set filename

                // --- 🔒 修复：手动添加 V1/V2/V3 复选框的状态 ---
                const isApk = file.name.toLowerCase().endsWith('.apk');
                if (isApk) {
                    const v1Checkbox = document.querySelector('input[name="v1"]');
                    const v2Checkbox = document.querySelector('input[name="v2"]');
                    const v3Checkbox = document.querySelector('input[name="v3"]');

                    if (v1Checkbox.checked) formData.append('v1', '1');
                    if (v2Checkbox.checked) formData.append('v2', '1');
                    if (v3Checkbox.checked) formData.append('v3', '1');
                }
                // --- END OF FIX ---

                // --- 🔒 修改：指向新的处理文件 ---
                xhr.open('POST', 'upload_handler.php', true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send(formData);

            } else {
                console.log("Using chunked upload method for iOS+Quark..."); // Debug log
                // ========== iOS+夸克：分块上传 + 触发签名 ==========
                let v1, v2, v3;
                try {
                    v1 = document.querySelector('input[name="v1"]').checked;
                    v2 = document.querySelector('input[name="v2"]').checked;
                    v3 = document.querySelector('input[name="v3"]').checked;
                } catch (optionErr) {
                    console.error("Error reading options:", optionErr); // Debug log
                    progressContainer.style.display = 'none';
                    submitBtn.disabled = false;
                    submitBtn.textContent = '上传并签名';
                    messageContainer.innerHTML = '<div class="message error">读取签名选项时出错: ' + optionErr.message + '</div>';
                    return; // Stop if options cannot be read
                }


                // --- 修复：使用回调函数处理异步操作 ---
                uploadInChunks(
                    file,
                    { v1, v2, v3 },
                    // Success callback for upload
                    function(uploadResult) {
                         console.log("Chunked upload success callback triggered.", uploadResult); // Debug log
                        if (!uploadResult.success) {
                            console.error("Chunked upload reported failure via success callback"); // Should not happen if coded correctly, but just in case
                            progressContainer.style.display = 'none';
                            submitBtn.disabled = false;
                            submitBtn.textContent = '上传并签名';
                            messageContainer.innerHTML = '<div class="message error">错误: 分块上传未完成</div>';
                            return;
                        }

                        // 触发签名
                        submitBtn.textContent = '正在签名...';
                        triggerSign(
                            uploadResult.uploadId,
                            uploadResult.filename,
                            v1, v2, v3,
                            // Success callback for signing
                            function(signResult) {
                                console.log("Final signature success callback triggered.", signResult); // Debug log
                                progressContainer.style.display = 'none';
                                submitBtn.disabled = false;
                                submitBtn.textContent = '上传并签名';
                                const msgDiv = document.createElement('div');
                                msgDiv.className = 'message ' + (signResult.success ? 'success' : 'error');
                                msgDiv.innerHTML = signResult.message;
                                messageContainer.appendChild(msgDiv);
                            },
                            // Error callback for signing
                            function(signError) {
                                 console.error("Final signature error callback triggered:", signError); // Debug log
                                progressContainer.style.display = 'none';
                                submitBtn.disabled = false;
                                submitBtn.textContent = '上传并签名';
                                messageContainer.innerHTML = '<div class="message error">错误: ' + signError.message + '</div>';
                            }
                        );
                    },
                    // Error callback for upload (handles network errors, server errors, slice errors)
                    function(uploadError) {
                         console.error("Chunked upload error callback triggered:", uploadError); // Debug log
                        // CRITICAL: Reset UI state on ANY error during chunked upload process
                        progressContainer.style.display = 'none';
                        submitBtn.disabled = false;
                        submitBtn.textContent = '上传并签名';
                        messageContainer.innerHTML = '<div class="message error">错误: ' + uploadError.message + '</div>';
                        // Do NOT allow the flow to continue beyond this point if an error occurs.
                    }
                );
            } // End if (!useChunkedUpload)
        } catch (mainLogicErr) {
            console.error("Unexpected error in main submit handler logic:", mainLogicErr); // Debug log
            // Critical: Catch any unexpected JS errors in the main logic flow
            progressContainer.style.display = 'none';
            submitBtn.disabled = false;
            submitBtn.textContent = '上传并签名';
            messageContainer.innerHTML = '<div class="message error">内部错误，请重试: ' + mainLogicErr.message + '</div>';
        }

        // --- End of修复逻辑 ---
    }); // End of addEventListener

</script>

</body>
</html>