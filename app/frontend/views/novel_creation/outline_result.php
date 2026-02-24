<?php
$title = $title ?? '大纲生成结果 - 星夜阁';
$tool_name = $tool_name ?? '大纲生成';
$params = $params ?? [];
$result = $result ?? [];
$back_url = $back_url ?? '/novel_creation/outline_generator';

// 获取当前主题CSS路径
use app\services\ThemeManager;
use app\config\FrontendConfig;
$themeManager = new ThemeManager();
$activeThemeId = $themeManager->getActiveThemeId('web') ?? FrontendConfig::THEME_DEFAULT;
$themeBasePath = FrontendConfig::getThemePath($activeThemeId);
$cssPath = FrontendConfig::getAssetUrl(FrontendConfig::PATH_STATIC_FRONTEND_WEB_CSS . '/pages/novel-creation.css');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= $cssPath ?>">
</head>
<body class="page-novel-creation">
    <div class="result-container">
        <h1 style="text-align: center; margin-bottom: 30px;">🗂️ <?= htmlspecialchars($tool_name) ?></h1>
        
        <?php if (!$result['success']): ?>
        <div class="result-box" style="background: #fee2e2; border-color: #fecaca;">
            <h3 style="color: #dc2626;">❌ 生成失败</h3>
            <p><?= htmlspecialchars($result['error'] ?? '未知错误') ?></p>
        </div>
        <?php else: ?>
        
        <div class="result-box">
            <h3>📋 大纲内容</h3>
            <pre><?= htmlspecialchars($result['content']) ?></pre>
        </div>
        
        <?php if (!empty($params['novel_id'])): ?>
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h3 class="card-title">💾 保存大纲</h3>
            </div>
            <div class="card-body">
                <p style="margin-bottom: 15px; color: #666;">将生成的大纲保存到当前小说的大纲库中。</p>
                <button class="btn btn-success" onclick="saveOutline()">保存大纲</button>
            </div>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="<?= htmlspecialchars($back_url) ?>" class="btn btn-secondary" style="margin-right: 10px;">返回</a>
            <?php if ($result['success']): ?>
            <button class="btn btn-primary" onclick="copyContent()">复制内容</button>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function copyContent() {
            const content = document.querySelector('pre').textContent;
            navigator.clipboard.writeText(content).then(() => {
                alert('已复制到剪贴板');
            });
        }
        
        function saveOutline() {
            const novelId = <?= $params['novel_id'] ?? 0 ?>;
            const outlineData = <?= json_encode($result['outline'] ?? []) ?>;
            
            fetch('/novel_creation/save_outline', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'novel_id=' + novelId + '&outline_type=<?= $params['outline_level'] ?? 'chapter' ?>&outline_data=' + encodeURIComponent(JSON.stringify(outlineData))
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('大纲已保存！');
                } else {
                    alert(data.error || '保存失败');
                }
            });
        }
    </script>
</body>
</html>
