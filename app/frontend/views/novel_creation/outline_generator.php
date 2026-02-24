<?php
$title = $title ?? '大纲生成 - 星夜阁';
$novels = $novels ?? [];
$novel_id = $novel_id ?? 0;
$outlines = $outlines ?? [];
$outline_type = $outline_type ?? 'chapter';

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
    <style>
        .page-novel-outline { min-height: 100vh; background: var(--bg-primary, #f5f5f5); }
        .outline-container { max-width: 1000px; margin: 0 auto; padding: 40px 20px; }
        .novel-select-section { background: var(--bg-card, #fff); padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .novel-select-section select { width: 100%; padding: 12px; border: 1px solid var(--border-color, #e5e7eb); border-radius: 8px; font-size: 16px; }
        .outline-tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .outline-tab { padding: 10px 20px; border: none; background: var(--bg-card, #fff); border-radius: 8px; cursor: pointer; font-size: 14px; transition: all 0.2s; }
        .outline-tab:hover { background: var(--bg-hover, #f3f4f6); }
        .outline-tab.active { background: var(--primary-color, #6366f1); color: white; }
        .outline-card { background: var(--bg-card, #fff); border-radius: 12px; padding: 20px; margin-bottom: 15px; border: 1px solid var(--border-color, #e5e7eb); }
        .outline-card h4 { margin: 0 0 10px 0; color: var(--text-primary, #333); font-size: 18px; }
        .outline-card p { margin: 0; color: var(--text-secondary, #666); line-height: 1.6; }
        .outline-meta { display: flex; gap: 15px; margin-top: 10px; font-size: 13px; color: var(--text-muted, #999); }
        .outline-tree { padding-left: 20px; border-left: 2px solid var(--border-color, #e5e7eb); }
        .outline-tree .outline-card { margin-left: 20px; }
        .empty-outline { text-align: center; padding: 60px 20px; color: var(--text-muted, #999); }
    </style>
</head>
<body class="page-novel-outline">
    <div class="outline-container">
        <h1 style="text-align: center; margin-bottom: 30px; color: var(--text-primary, #333);">📋 大纲生成系统</h1>
        
        <!-- 小说选择 -->
        <div class="novel-select-section">
            <select id="novelSelect" onchange="switchNovel(this.value)">
                <option value="0">-- 请选择小说 --</option>
                <?php foreach ($novels as $novel): ?>
                <option value="<?= $novel['id'] ?>" <?= $novel_id == $novel['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($novel['title']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- 新建大纲 -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h3 class="card-title">✨ 生成新大纲</h3>
            </div>
            <div class="card-body">
                <form action="/novel_creation/do_outline_generator" method="post">
                    <input type="hidden" name="novel_id" value="<?= $novel_id ?>">
                    
                    <div class="form-group">
                        <label>小说题材</label>
                        <input type="text" name="genre" placeholder="如：玄幻、言情、都市、历史、科幻等" required>
                    </div>
                    
                    <div class="form-group">
                        <label>小说类型</label>
                        <select name="type" required>
                            <option value="">-- 请选择 --</option>
                            <option value="长篇小说">长篇小说</option>
                            <option value="中篇小说">中篇小说</option>
                            <option value="短篇小说">短篇小说</option>
                            <option value="网络连载">网络连载</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>核心主题</label>
                        <input type="text" name="theme" placeholder="如：复仇、成长、爱情、救赎等" required>
                    </div>
                    
                    <div class="form-group">
                        <label>目标字数</label>
                        <input type="number" name="target_words" placeholder="如：100000" value="100000">
                    </div>
                    
                    <div class="form-group">
                        <label>核心冲突</label>
                        <textarea name="conflict" placeholder="描述故事的核心矛盾和冲突" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>大纲级别</label>
                        <select name="outline_level">
                            <option value="chapter">章节级大纲</option>
                            <option value="plot">情节点级大纲</option>
                            <option value="detail">细纲级大纲</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg btn-block">🚀 生成大纲</button>
                </form>
            </div>
        </div>
        
        <!-- 已有大纲 -->
        <?php if ($novel_id > 0 && !empty($outlines)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📚 已有大纲</h3>
                <div class="outline-tabs">
                    <button class="outline-tab <?= $outline_type == 'chapter' ? 'active' : '' ?>" onclick="filterOutline('chapter')">章节级</button>
                    <button class="outline-tab <?= $outline_type == 'plot' ? 'active' : '' ?>" onclick="filterOutline('plot')">情节点级</button>
                    <button class="outline-tab <?= $outline_type == 'detail' ? 'active' : '' ?>" onclick="filterOutline('detail')">细纲级</button>
                </div>
            </div>
            <div class="card-body">
                <?php foreach ($outlines as $outline): ?>
                <div class="outline-card" data-type="<?= $outline['outline_type'] ?>">
                    <h4><?= htmlspecialchars($outline['title']) ?></h4>
                    <p><?= htmlspecialchars($outline['content']) ?></p>
                    <div class="outline-meta">
                        <span>级别: <?= $outline['level'] ?></span>
                        <span>排序: <?= $outline['sort_order'] ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php elseif ($novel_id > 0): ?>
        <div class="empty-outline">
            <p>暂无大纲内容</p>
            <p>请先使用上方工具生成大纲</p>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function switchNovel(novelId) {
            if (novelId > 0) {
                window.location.href = '/novel_creation/outline_generator?novel_id=' + novelId;
            }
        }
        
        function filterOutline(type) {
            document.querySelectorAll('.outline-tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            
            document.querySelectorAll('.outline-card').forEach(card => {
                if (type === 'all' || card.dataset.type === type) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
