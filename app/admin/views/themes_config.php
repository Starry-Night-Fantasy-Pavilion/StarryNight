<?php $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/'); ?>

<style>
.config-container {
    background: rgba(255, 255, 255, 0.04);
    border-radius: 20px;
    padding: 30px;
    border: 1px solid rgba(255, 255, 255, 0.15);
}
.config-form-group {
    margin-bottom: 20px;
}
.config-label {
    display: block;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
    margin-bottom: 8px;
}
.config-input {
    width: 100%;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
    font-size: 14px;
}
.config-input:focus {
    outline: none;
    border-color: rgba(0, 242, 255, 0.5);
    background: rgba(255, 255, 255, 0.08);
}
.config-textarea {
    min-height: 100px;
    resize: vertical;
}
.config-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 30px;
}
</style>

<div class="config-container">
    <?php if (!empty($_GET['success'])): ?>
        <div class="tm-alert" style="margin-bottom: 20px; background: rgba(16,185,129,0.12); border-color: rgba(16,185,129,0.3); color: #b7f7dc;">
            配置保存成功！
        </div>
    <?php endif; ?>
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="margin: 0; color: #fff;">主题配置 - <?= htmlspecialchars($theme['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
        <a href="/<?= htmlspecialchars($adminPrefix, ENT_QUOTES, 'UTF-8') ?>/themes" class="tm-btn">返回</a>
    </div>
    
    <form method="post" action="/<?= htmlspecialchars($adminPrefix, ENT_QUOTES, 'UTF-8') ?>/themes/config?theme_id=<?= urlencode($theme['id']) ?>">
        <div class="config-form-group">
            <label class="config-label">主题名称</label>
            <input type="text" name="config[name]" value="<?= htmlspecialchars($currentConfig['name'] ?? $theme['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="config-input">
        </div>
        
        <div class="config-form-group">
            <label class="config-label">自定义CSS</label>
            <textarea name="config[custom_css]" class="config-input config-textarea" placeholder="输入自定义CSS代码..."><?= htmlspecialchars($currentConfig['custom_css'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        
        <div class="config-form-group">
            <label class="config-label">自定义JavaScript</label>
            <textarea name="config[custom_js]" class="config-input config-textarea" placeholder="输入自定义JavaScript代码..."><?= htmlspecialchars($currentConfig['custom_js'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        
        <div class="config-form-group">
            <label class="config-label">Logo URL</label>
            <input type="text" name="config[logo_url]" value="<?= htmlspecialchars($currentConfig['logo_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="config-input" placeholder="https://example.com/logo.png">
        </div>
        
        <div class="config-form-group">
            <label class="config-label">Favicon URL</label>
            <input type="text" name="config[favicon_url]" value="<?= htmlspecialchars($currentConfig['favicon_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="config-input" placeholder="https://example.com/favicon.ico">
        </div>
        
        <div class="config-actions">
            <a href="/<?= htmlspecialchars($adminPrefix, ENT_QUOTES, 'UTF-8') ?>/themes" class="tm-btn">取消</a>
            <button type="submit" class="tm-btn tm-btn-primary">保存配置</button>
        </div>
    </form>
</div>
