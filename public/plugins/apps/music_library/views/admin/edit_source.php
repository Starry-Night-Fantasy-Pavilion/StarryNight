<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $source ? '编辑音乐源' : '添加音乐源'; ?></title>
    <link rel="stylesheet" href="/static/admin/css/plugin-modal.css">
</head>
<body>
<div class="admin-container">
    <div class="admin-header">
        <h2 class="admin-title"><?php echo $source ? '编辑音乐源' : '添加音乐源'; ?></h2>
    </div>
    
    <form class="settings-form" method="post" action="/admin/music/sources/save">
        <?php if ($source): ?>
            <input type="hidden" name="id" value="<?php echo $source['id']; ?>">
        <?php endif; ?>
        
        <div class="form-section">
            <h3 class="section-title">基本信息</h3>
            
            <div class="form-group">
                <label class="form-label">音乐源名称 *</label>
                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($source['name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">音乐源类型 *</label>
                <select class="form-control" name="type" required>
                    <option value="">请选择</option>
                    <option value="rss" <?php echo ($source['type'] ?? '') === 'rss' ? 'selected' : ''; ?>>RSS 订阅</option>
                    <option value="api" <?php echo ($source['type'] ?? '') === 'api' ? 'selected' : ''; ?>>API 接口</option>
                    <option value="scrape" <?php echo ($source['type'] ?? '') === 'scrape' ? 'selected' : ''; ?>>网页抓取</option>
                    <option value="upload" <?php echo ($source['type'] ?? '') === 'upload' ? 'selected' : ''; ?>>文件上传</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">URL 地址 *</label>
                <input type="url" class="form-control" name="url" value="<?php echo htmlspecialchars($source['url'] ?? ''); ?>" required>
                <div class="form-description">音乐源的访问地址，根据类型不同可能是 RSS、API 或网页地址</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">描述</label>
                <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($source['description'] ?? ''); ?></textarea>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="section-title">同步设置</h3>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">同步频率</label>
                        <select class="form-control" name="sync_frequency">
                            <option value="manual" <?php echo ($source['sync_frequency'] ?? 'manual') === 'manual' ? 'selected' : ''; ?>>手动同步</option>
                            <option value="hourly" <?php echo ($source['sync_frequency'] ?? '') === 'hourly' ? 'selected' : ''; ?>>每小时</option>
                            <option value="daily" <?php echo ($source['sync_frequency'] ?? '') === 'daily' ? 'selected' : ''; ?>>每天</option>
                            <option value="weekly" <?php echo ($source['sync_frequency'] ?? '') === 'weekly' ? 'selected' : ''; ?>>每周</option>
                            <option value="monthly" <?php echo ($source['sync_frequency'] ?? '') === 'monthly' ? 'selected' : ''; ?>>每月</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">状态</label>
                        <select class="form-control" name="status">
                            <option value="active" <?php echo ($source['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>激活</option>
                            <option value="inactive" <?php echo ($source['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>未激活</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="section-title">配置参数</h3>
            <div class="form-description mb-3">根据音乐源类型，可能需要额外的配置参数</div>
            
            <div id="config-fields">
                <?php 
                $config = $source ? json_decode($source['config'] ?? '{}', true) : [];
                if (empty($config)) {
                    $config = [['key' => '', 'value' => '']];
                }
                foreach ($config as $index => $item): 
                ?>
                    <div class="row mb-2 config-row">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="config[<?php echo $index; ?>][key]" value="<?php echo htmlspecialchars($item['key'] ?? ''); ?>" placeholder="参数名">
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="config[<?php echo $index; ?>][value]" value="<?php echo htmlspecialchars($item['value'] ?? ''); ?>" placeholder="参数值">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-sm btn-danger w-100" onclick="removeConfigRow(this)">删除</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="btn btn-sm btn-info mt-2" onclick="addConfigRow()">+ 添加参数</button>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $source ? '保存修改' : '添加音乐源'; ?></button>
            <?php if ($source): ?>
                <button type="button" class="btn btn-success" onclick="testSource()">测试连接</button>
                <button type="button" class="btn btn-warning" onclick="syncSource()">立即同步</button>
            <?php endif; ?>
            <button type="button" class="btn btn-secondary" onclick="history.back()">取消</button>
        </div>
    </form>
</div>

<script>
    let configRowIndex = <?php echo count($config) ?? 0; ?>;
    
    function addConfigRow() {
        const configFields = document.getElementById('config-fields');
        const newRow = document.createElement('div');
        newRow.className = 'row mb-2 config-row';
        newRow.innerHTML = `
            <div class="col-md-4">
                <input type="text" class="form-control" name="config[${configRowIndex}][key]" placeholder="参数名">
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control" name="config[${configRowIndex}][value]" placeholder="参数值">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-sm btn-danger w-100" onclick="removeConfigRow(this)">删除</button>
            </div>
        `;
        configFields.appendChild(newRow);
        configRowIndex++;
    }
    
    function removeConfigRow(button) {
        button.closest('.config-row').remove();
    }
    
    function testSource() {
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = '测试中...';
        button.disabled = true;
        
        const form = document.querySelector('.settings-form');
        const formData = new FormData(form);
        
        fetch('/api/v1/admin/music/sources/test', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            button.textContent = originalText;
            button.disabled = false;
            
            if (data.success) {
                alert('测试成功！连接正常。');
            } else {
                alert('测试失败: ' + data.message);
            }
        })
        .catch(error => {
            button.textContent = originalText;
            button.disabled = false;
            alert('测试失败: ' + error.message);
        });
    }
    
    function syncSource() {
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = '同步中...';
        button.disabled = true;
        
        const form = document.querySelector('.settings-form');
        const formData = new FormData(form);
        
        fetch('/api/v1/admin/music/sources/sync', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            button.textContent = originalText;
            button.disabled = false;
            
            if (data.success) {
                alert('同步成功！已导入 ' + data.data.tracks_added + ' 首音乐和 ' + data.data.albums_added + ' 个专辑。');
            } else {
                alert('同步失败: ' + data.message);
            }
        })
        .catch(error => {
            button.textContent = originalText;
            button.disabled = false;
            alert('同步失败: ' + error.message);
        });
    }
</script>
</body>
</html>
