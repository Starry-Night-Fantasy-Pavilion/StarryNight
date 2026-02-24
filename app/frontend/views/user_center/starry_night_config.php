<style>
    .starry-night-config {
        max-width: 1000px;
        margin: 0 auto;
        padding: 24px;
    }
    .config-header {
        margin-bottom: 24px;
    }
    .config-header h1 {
        font-size: 24px;
        margin-bottom: 8px;
    }
    .config-header p {
        color: rgba(255,255,255,0.7);
    }
    .version-card {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 16px;
    }
    .version-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    .version-title {
        font-size: 18px;
        font-weight: bold;
    }
    .version-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        background: rgba(102, 126, 234, 0.3);
        color: #fff;
    }
    .version-badge.available {
        background: rgba(76, 175, 80, 0.3);
    }
    .version-badge.unavailable {
        background: rgba(244, 67, 54, 0.3);
    }
    .config-section {
        margin-top: 16px;
    }
    .config-section-title {
        font-size: 14px;
        color: rgba(255,255,255,0.7);
        margin-bottom: 8px;
    }
    .custom-config-toggle {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }
    .toggle-switch {
        position: relative;
        width: 50px;
        height: 24px;
        background: rgba(255,255,255,0.2);
        border-radius: 12px;
        cursor: pointer;
        transition: background 0.3s;
    }
    .toggle-switch.active {
        background: #667eea;
    }
    .toggle-switch::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        background: #fff;
        border-radius: 50%;
        top: 2px;
        left: 2px;
        transition: left 0.3s;
    }
    .toggle-switch.active::after {
        left: 28px;
    }
    .config-textarea {
        width: 100%;
        min-height: 200px;
        background: rgba(0,0,0,0.3);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 4px;
        padding: 12px;
        color: #fff;
        font-family: monospace;
        font-size: 14px;
        resize: vertical;
    }
    .config-textarea:focus {
        outline: none;
        border-color: #667eea;
    }
    .btn-save {
        background: #667eea;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 10px 24px;
        cursor: pointer;
        font-size: 14px;
        margin-top: 16px;
        transition: background 0.3s;
    }
    .btn-save:hover {
        background: #5568d3;
    }
    .btn-save:disabled {
        background: rgba(255,255,255,0.2);
        cursor: not-allowed;
    }
    .info-message {
        background: rgba(102, 126, 234, 0.2);
        border-left: 3px solid #667eea;
        padding: 12px;
        border-radius: 4px;
        margin-bottom: 16px;
        font-size: 14px;
    }
</style>

<div class="starry-night-config">
    <div class="config-header">
        <h1>星夜创作引擎配置</h1>
        <p>根据您的会员等级，您可以配置和使用不同版本的星夜创作引擎</p>
    </div>

    <?php if ($membership): ?>
        <div class="info-message">
            当前会员等级：<strong><?= htmlspecialchars($membership['level_name']) ?></strong>
        </div>
    <?php else: ?>
        <div class="info-message" style="background: rgba(244, 67, 54, 0.2); border-color: #f44336;">
            您当前是普通用户，可以使用基础版星夜创作引擎。升级会员可解锁更多功能。
        </div>
    <?php endif; ?>

    <?php 
    $versions = ['basic' => '基础版', 'standard' => '标准版', 'premium' => '高级版', 'enterprise' => '企业版'];
    foreach ($versions as $version => $versionName): 
        $isAvailable = isset($available_versions[$version]) && $available_versions[$version]['is_enabled'];
        $userConfig = $user_configs[$version] ?? null;
        $hasCustomConfig = $userConfig && $userConfig['is_enabled'] == 1;
    ?>
        <div class="version-card">
            <div class="version-header">
                <div>
                    <span class="version-title"><?= htmlspecialchars($versionName) ?></span>
                    <span class="version-badge <?= $isAvailable ? 'available' : 'unavailable' ?>">
                        <?= $isAvailable ? '可用' : '不可用' ?>
                    </span>
                </div>
            </div>

            <?php if ($isAvailable): ?>
                <div class="config-section">
                    <div class="custom-config-toggle">
                        <span class="config-section-title">启用自定义配置</span>
                        <div class="toggle-switch <?= $hasCustomConfig ? 'active' : '' ?>" 
                             data-version="<?= htmlspecialchars($version) ?>"
                             onclick="toggleCustomConfig(this, '<?= htmlspecialchars($version) ?>')">
                        </div>
                    </div>

                    <div id="config-<?= htmlspecialchars($version) ?>" style="display: <?= $hasCustomConfig ? 'block' : 'none' ?>;">
                        <div class="config-section-title">自定义配置（JSON格式）</div>
                        <textarea class="config-textarea" 
                                  id="textarea-<?= htmlspecialchars($version) ?>"
                                  placeholder='{"model": "gpt-4", "temperature": 0.7, ...}'><?= $hasCustomConfig ? htmlspecialchars($userConfig['custom_config']) : '{}' ?></textarea>
                        <button class="btn-save" onclick="saveConfig('<?= htmlspecialchars($version) ?>')">保存配置</button>
                    </div>

                    <?php if (!$hasCustomConfig): ?>
                        <div class="info-message" style="margin-top: 16px;">
                            当前使用后台默认配置。启用自定义配置后，将优先使用您的个人配置。
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="info-message" style="background: rgba(244, 67, 54, 0.2); border-color: #f44336;">
                    此版本需要更高等级的会员权限。请升级会员后使用。
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
function toggleCustomConfig(element, version) {
    const isActive = element.classList.contains('active');
    const configDiv = document.getElementById('config-' + version);
    
    if (isActive) {
        element.classList.remove('active');
        configDiv.style.display = 'none';
        // 禁用自定义配置
        saveConfigEnabled(version, 0);
    } else {
        element.classList.add('active');
        configDiv.style.display = 'block';
        // 启用自定义配置
        saveConfigEnabled(version, 1);
    }
}

function saveConfigEnabled(version, enabled) {
    fetch('/user_center/save_starry_night_config', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            engine_version: version,
            custom_config: '{}',
            is_enabled: enabled
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('保存失败:', data.error);
        }
    })
    .catch(error => {
        console.error('请求失败:', error);
    });
}

function saveConfig(version) {
    const textarea = document.getElementById('textarea-' + version);
    const config = textarea.value.trim();
    
    // 验证JSON格式
    try {
        JSON.parse(config);
    } catch (e) {
        alert('配置格式错误，请输入有效的JSON格式');
        return;
    }

    const btn = event.target;
    btn.disabled = true;
    btn.textContent = '保存中...';

    fetch('/user_center/save_starry_night_config', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            engine_version: version,
            custom_config: config,
            is_enabled: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.textContent = '保存配置';
        
        if (data.success) {
            alert('配置已保存');
        } else {
            alert('保存失败: ' + (data.error || '未知错误'));
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.textContent = '保存配置';
        alert('请求失败，请重试');
        console.error('请求失败:', error);
    });
}
</script>
