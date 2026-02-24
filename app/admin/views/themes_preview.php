<?php $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/'); ?>

<style>
.preview-container {
    background: rgba(255, 255, 255, 0.04);
    border-radius: 20px;
    padding: 20px;
    border: 1px solid rgba(255, 255, 255, 0.15);
}
.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.preview-iframe {
    width: 100%;
    height: 80vh;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    background: #fff;
}
</style>

<div class="preview-container">
    <div class="preview-header">
        <h2 style="margin: 0; color: #fff;">主题预览 - <?= htmlspecialchars($theme['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
        <a href="/<?= htmlspecialchars($adminPrefix, ENT_QUOTES, 'UTF-8') ?>/themes" class="tm-btn">返回</a>
    </div>
    
    <?php if (!empty($theme['preview'])): ?>
        <div style="margin-bottom: 20px;">
            <img src="<?= htmlspecialchars($theme['preview'], ENT_QUOTES, 'UTF-8') ?>" alt="预览图" style="max-width: 100%; border-radius: 10px;">
        </div>
    <?php endif; ?>
    
    <div>
        <p style="color: rgba(255,255,255,0.7); margin-bottom: 10px;">主题信息：</p>
        <ul style="color: rgba(255,255,255,0.8);">
            <li>ID: <?= htmlspecialchars($theme['id'] ?? '', ENT_QUOTES, 'UTF-8') ?></li>
            <?php if (!empty($theme['version'])): ?>
                <li>版本: <?= htmlspecialchars($theme['version'], ENT_QUOTES, 'UTF-8') ?></li>
            <?php endif; ?>
            <?php if (!empty($theme['author'])): ?>
                <li>作者: <?= htmlspecialchars($theme['author'], ENT_QUOTES, 'UTF-8') ?></li>
            <?php endif; ?>
            <?php if (!empty($theme['description'])): ?>
                <li>描述: <?= htmlspecialchars($theme['description'], ENT_QUOTES, 'UTF-8') ?></li>
            <?php endif; ?>
        </ul>
    </div>
    
    <div style="margin-top: 20px;">
        <p style="color: rgba(255,255,255,0.7); margin-bottom: 10px;">注意：这是静态预览，实际效果可能因配置而异。</p>
    </div>
</div>
