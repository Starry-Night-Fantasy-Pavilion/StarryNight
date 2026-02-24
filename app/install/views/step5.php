<!DOCTYPE html>
<html>
<head>
    <title>星夜阁 - 安装向导 - 存储配置</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="/static/install/css/style.css">
</head>
<body class="install-page step-5">
    <div class="install-wrapper">
        <?php include __DIR__ . '/_partials/sidebar.php'; ?>
        
        <div class="install-main">
            <form action="?step=5" method="post" style="display: contents;">
                <div class="install-content">
                    <h1>存储配置</h1>
                    <p class="description">请选择存储方式并配置相关参数。本地存储适合小型部署，OSS对象存储适合大规模部署。</p>
                    
                    <?php 
                    $config = $_SESSION['install_config'] ?? [];
                    if (isset($_SESSION['install_error'])): ?>
                        <div class="error-message">
                            <?php echo htmlspecialchars($_SESSION['install_error']); unset($_SESSION['install_error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-wrapper">
                        <div class="storage-options">
                            <div class="storage-option" data-type="local">
                                <div class="storage-option-header">
                                    <input type="radio" name="storage_type" value="local" id="storage_local"
                                           <?= ($config['storage']['storage_type'] ?? 'local') === 'local' ? 'checked' : '' ?>>
                                    <label for="storage_local" class="storage-option-title">本地文件存储</label>
                                </div>
                                <p class="storage-option-description">适合小型部署，文件存储在服务器本地磁盘上，配置简单，性能稳定。</p>
                                <div class="storage-option-details">
                                    <div class="config-section">
                                        <h4>基本配置</h4>
                                        <div class="config-grid">
                                            <div class="config-item">
                                                <label for="base_path">存储基础路径</label>
                                                <input type="text" id="base_path" name="base_path"
                                                       value="<?= htmlspecialchars($config['storage']['base_path'] ?? '/data') ?>" data-required="true">
                                                <div class="config-help">文件存储的根目录，例如：/data</div>
                                            </div>
                                            <div class="config-item">
                                                <label for="url_prefix">URL前缀</label>
                                                <input type="text" id="url_prefix" name="url_prefix"
                                                       value="<?= htmlspecialchars($config['storage']['url_prefix'] ?? '/data') ?>" data-required="true">
                                                <div class="config-help">访问文件的URL前缀，例如：/data</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="config-section">
                                        <h4>压缩设置</h4>
                                        <div class="config-grid">
                                            <div class="config-item">
                                                <label for="compression_enabled">启用文件压缩</label>
                                                <select id="compression_enabled" name="compression_enabled">
                                                    <option value="1" <?= ($config['storage']['compression_enabled'] ?? '1') == '1' ? 'selected' : '' ?>>启用</option>
                                                    <option value="0" <?= ($config['storage']['compression_enabled'] ?? '1') == '0' ? 'selected' : '' ?>>禁用</option>
                                                </select>
                                                <div class="config-help">自动压缩图片和文本文件以节省存储空间</div>
                                            </div>
                                            <div class="config-item">
                                                <label for="max_compression_level">最大压缩级别</label>
                                                <select id="max_compression_level" name="max_compression_level">
                                                    <option value="1" <?= ($config['storage']['max_compression_level'] ?? '6') == '1' ? 'selected' : '' ?>>1 (最低压缩)</option>
                                                    <option value="3" <?= ($config['storage']['max_compression_level'] ?? '6') == '3' ? 'selected' : '' ?>>3 (低压缩)</option>
                                                    <option value="6" <?= ($config['storage']['max_compression_level'] ?? '6') == '6' ? 'selected' : '' ?>>6 (中等压缩)</option>
                                                    <option value="9" <?= ($config['storage']['max_compression_level'] ?? '6') == '9' ? 'selected' : '' ?>>9 (高压缩)</option>
                                                </select>
                                                <div class="config-help">压缩级别越高，文件越小，但处理时间越长</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="storage-option" data-type="oss">
                                <div class="storage-option-header">
                                    <input type="radio" name="storage_type" value="oss" id="storage_oss"
                                           <?= ($config['storage']['storage_type'] ?? 'local') === 'oss' ? 'checked' : '' ?>>
                                    <label for="storage_oss" class="storage-option-title">OSS对象存储</label>
                                </div>
                                <p class="storage-option-description">适合大规模部署，文件存储在云端对象存储服务，支持CDN加速，扩展性强。</p>
                                <div class="storage-option-details">
                                    <div class="config-warning">
                                        <strong>注意：</strong>使用OSS存储需要先在云服务提供商处创建对象存储服务，并获取相关配置信息。
                                    </div>
                                    
                                    <div class="config-section">
                                        <h4>OSS基本配置</h4>
                                        <div class="config-grid">
                                            <div class="config-item">
                                                <label for="oss_endpoint">OSS Endpoint</label>
                                                <input type="text" id="oss_endpoint" name="oss[endpoint]"
                                                       value="<?= htmlspecialchars($config['storage']['oss']['endpoint'] ?? '') ?>"
                                                                                                               placeholder="例如：https://oss-cn-beijing.aliyuncs.com" data-required="true">
                                                <div class="config-help">OSS访问域名</div>
                                            </div>
                                            <div class="config-item">
                                                <label for="oss_bucket_name">Bucket名称</label>
                                                <input type="text" id="oss_bucket_name" name="oss[bucket_name]"
                                                       value="<?= htmlspecialchars($config['storage']['oss']['bucket_name'] ?? '') ?>" data-required="true">
                                                <div class="config-help">OSS存储桶名称</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="config-section">
                                        <h4>访问密钥</h4>
                                        <div class="config-grid">
                                            <div class="config-item">
                                                <label for="oss_access_key">AccessKey ID</label>
                                                <input type="text" id="oss_access_key" name="oss[access_key]"
                                                       value="<?= htmlspecialchars($config['storage']['oss']['access_key'] ?? '') ?>" data-required="true">
                                                <div class="config-help">OSS访问密钥ID</div>
                                            </div>
                                            <div class="config-item">
                                                <label for="oss_secret_key">AccessKey Secret</label>
                                                <input type="text" id="oss_secret_key" name="oss[secret_key]"
                                                       value="<?= htmlspecialchars($config['storage']['oss']['secret_key'] ?? '') ?>" data-required="true">
                                                <div class="config-help">OSS访问密钥Secret</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                                    </div>
                                    <div class="actions">
                                        <a href="?step=4" class="btn btn-secondary">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                                            上一步
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            下一步
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                        </button>
                                    </div>
            </form>
        </div>
    </div>
    
    <script src="/static/install/js/install.js"></script>
</body>
</html>