<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>存储管理 - 星夜阁</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .storage-progress {
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
        }
        .storage-progress .progress-bar {
            transition: width 0.3s ease;
        }
        .file-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        .file-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .file-icon {
            font-size: 2rem;
            color: #6c757d;
        }
        .quota-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
        }
        .quota-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border: 1px solid #f1b0b7;
            border-radius: 8px;
            padding: 15px;
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        .upload-area.dragover {
            border-color: #007bff;
            background-color: #e3f2fd;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="bi bi-hdd"></i> 存储管理</h2>
                
                <!-- 存储配额概览 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-pie-chart"></i> 存储配额概览</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($isOverQuota): ?>
                            <div class="quota-danger mb-3">
                                <h6><i class="bi bi-exclamation-triangle"></i> 存储空间已超出配额限制</h6>
                                <p class="mb-0">您已超出存储配额，请删除部分文件或升级会员等级以获得更多存储空间。</p>
                            </div>
                        <?php elseif ($usagePercentage > 80): ?>
                            <div class="quota-warning mb-3">
                                <h6><i class="bi bi-exclamation-circle"></i> 存储空间即将用尽</h6>
                                <p class="mb-0">您的存储空间使用率已达到 <?= $usagePercentage ?>%，建议及时清理或升级存储配额。</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>已使用空间</span>
                                    <strong><?= UserStorageQuota::formatBytes($usedSpace) ?></strong>
                                </div>
                                <div class="storage-progress progress mt-2">
                                    <div class="progress-bar <?= $isOverQuota ? 'bg-danger' : ($usagePercentage > 80 ? 'bg-warning' : 'bg-primary') ?>" 
                                         style="width: <?= min($usagePercentage, 100) ?>%"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>总配额</span>
                                    <strong><?= UserStorageQuota::formatBytes($totalQuota) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span>剩余空间</span>
                                    <strong class="<?= $isOverQuota ? 'text-danger' : ($usagePercentage > 80 ? 'text-warning' : 'text-success') ?>">
                                        <?= UserStorageQuota::formatBytes($remainingSpace) ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <small class="text-muted">使用率: <?= $usagePercentage ?>%</small>
                        </div>
                    </div>
                </div>

                <!-- 文件上传 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-cloud-upload"></i> 文件上传</h5>
                    </div>
                    <div class="card-body">
                        <div class="upload-area" id="uploadArea">
                            <i class="bi bi-cloud-upload display-4 text-muted mb-3"></i>
                            <h5>拖拽文件到此处或点击选择</h5>
                            <p class="text-muted">支持单个文件上传，最大文件大小取决于您的会员等级</p>
                            <input type="file" id="fileInput" style="display: none;">
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                                <i class="bi bi-folder-open"></i> 选择文件
                            </button>
                        </div>
                        <div id="uploadProgress" class="mt-3" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar" id="uploadProgressBar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <div class="text-center mt-2">
                                <span id="uploadStatus">准备上传...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 文件列表 -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-files"></i> 我的文件</h5>
                        <span class="badge bg-primary"><?= count($userFiles) ?> 个文件</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($userFiles)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox display-1 text-muted"></i>
                                <p class="text-muted mt-3">暂无文件</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($userFiles as $file): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="file-item">
                                            <div class="d-flex align-items-start">
                                                <div class="me-3">
                                                    <i class="bi bi-file-earmark file-icon"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?= htmlspecialchars(basename($file['file_path'])) ?></h6>
                                                    <p class="text-muted mb-2 small">
                                                        大小: <?= UserStorageQuota::formatBytes($file['file_size']) ?><br>
                                                        类型: <?= $file['mime_type'] ?? '未知' ?><br>
                                                        上传时间: <?= date('Y-m-d H:i', strtotime($file['created_at'])) ?>
                                                    </p>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                                onclick="downloadFile('<?= htmlspecialchars($file['file_path']) ?>')">
                                                            <i class="bi bi-download"></i> 下载
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                                onclick="deleteFile(<?= $file['id'] ?>)">
                                                            <i class="bi bi-trash"></i> 删除
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 文件上传相关
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const uploadProgress = document.getElementById('uploadProgress');
        const uploadProgressBar = document.getElementById('uploadProgressBar');
        const uploadStatus = document.getElementById('uploadStatus');

        // 点击上传区域
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });

        // 拖拽上传
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                uploadFile(files[0]);
            }
        });

        // 文件选择
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                uploadFile(e.target.files[0]);
            }
        });

        // 上传文件
        function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            
            uploadProgress.style.display = 'block';
            uploadStatus.textContent = '正在上传...';
            
            const xhr = new XMLHttpRequest();
            
            // 上传进度
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    uploadProgressBar.style.width = percentComplete + '%';
                    uploadStatus.textContent = `上传中... ${Math.round(percentComplete)}%`;
                }
            });
            
            // 上传完成
            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            uploadStatus.textContent = '上传成功！';
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            uploadStatus.textContent = '上传失败: ' + response.message;
                        }
                    } catch (e) {
                        uploadStatus.textContent = '上传失败: 服务器响应错误';
                    }
                } else {
                    uploadStatus.textContent = '上传失败: 网络错误';
                }
            });
            
            // 上传错误
            xhr.addEventListener('error', function() {
                uploadStatus.textContent = '上传失败: 网络错误';
            });
            
            xhr.open('POST', '/storage/upload');
            xhr.send(formData);
        }

        // 下载文件
        function downloadFile(filePath) {
            window.open(filePath, '_blank');
        }

        // 删除文件
        function deleteFile(fileId) {
            if (confirm('确定要删除这个文件吗？此操作不可恢复。')) {
                fetch('/storage/delete-file', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'file_id=' + fileId
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert('文件删除成功');
                        location.reload();
                    } else {
                        alert('删除失败: ' + result.message);
                    }
                })
                .catch(error => {
                    alert('请求失败: ' + error.message);
                });
            }
        }

        // 定期更新存储使用情况
        setInterval(function() {
            fetch('/api/storage/usage')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const data = result.data;
                        // 可以在这里更新页面上的存储使用情况显示
                        console.log('存储使用情况:', data);
                    }
                })
                .catch(error => {
                    console.error('获取存储使用情况失败:', error);
                });
        }, 30000); // 每30秒更新一次
    </script>
</body>
</html>