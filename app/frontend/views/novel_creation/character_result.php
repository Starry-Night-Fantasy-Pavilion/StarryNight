<?php
$title = $title ?? '角色生成结果 - 星夜阁';
$tool_name = $tool_name ?? 'AI角色生成';
$params = $params ?? [];
$result = $result ?? [];
$back_url = $back_url ?? '/novel_creation/character_generator';

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
        <h1 style="text-align: center; margin-bottom: 30px;">✨ <?= htmlspecialchars($tool_name) ?></h1>
        
        <?php if (!$result['success']): ?>
        <div class="result-box" style="background: #fee2e2; border-color: #fecaca;">
            <h3 style="color: #dc2626;">❌ 生成失败</h3>
            <p><?= htmlspecialchars($result['error'] ?? '未知错误') ?></p>
        </div>
        <?php else: ?>
        
        <div class="result-box">
            <h3>📋 角色档案</h3>
            <pre><?= htmlspecialchars($result['content']) ?></pre>
        </div>
        
        <?php if (!empty($result['character'])): ?>
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h3 class="card-title">💾 保存到角色库</h3>
            </div>
            <div class="card-body">
                <form action="/novel_creation/save_character" method="post" id="saveCharacterForm">
                    <input type="hidden" name="novel_id" value="<?= $params['novel_id'] ?? 0 ?>">
                    <input type="hidden" name="character_data" value="<?= htmlspecialchars(json_encode($result['character'])) ?>">
                    
                    <div class="form-group">
                        <label>角色名称</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($result['character']['name'] ?? '') ?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-success">保存角色</button>
                </form>
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
    </script>
</body>
</html>
