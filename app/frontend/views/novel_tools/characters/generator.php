<?php
use app\services\ThemeManager;
use app\config\FrontendConfig;

$title = $title ?? 'AI角色生成 - 星夜阁';
$novel_id = $novel_id ?? 0;

// 获取当前主题CSS路径
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
    <div class="form-container" style="padding-top: 40px;">
        <h1 style="text-align: center; margin-bottom: 30px;">✨ AI角色生成</h1>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">创建新角色</h3>
                <a href="/novel_creation/character_manager?novel_id=<?= $novel_id ?>" class="btn btn-secondary btn-sm">返回列表</a>
            </div>
            <div class="card-body">
                <form action="/novel_creation/do_character_generator" method="post">
                    <input type="hidden" name="novel_id" value="<?= $novel_id ?>">
                    
                    <div class="form-group">
                        <label>角色类型</label>
                        <select name="role_type" required>
                            <option value="protagonist">主角</option>
                            <option value="supporting">配角</option>
                            <option value="antagonist">反派</option>
                            <option value="other">其他</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>故事背景</label>
                        <textarea name="story_background" rows="4" placeholder="描述故事的整体背景、世界观设定等"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>性格特点提示</label>
                        <textarea name="personality_hints" rows="3" placeholder="你希望角色具有的性格特点，例如：勇敢但冲动、冷静睿智、亦正亦邪等"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>故事作用</label>
                        <textarea name="story_function" rows="3" placeholder="这个角色在故事中起什么作用？推动什么剧情？与其他角色的关系？"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg btn-block">🚀 AI生成角色</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
