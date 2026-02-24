<?php
$title = $title ?? '角色一致性检查 - 星夜阁';
$novels = $novels ?? [];
$novel_id = $novel_id ?? 0;
$characters = $characters ?? [];
$chapters = $chapters ?? [];
$current_chapter = $current_chapter ?? null;
$chapter_id = $chapter_id ?? 0;

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
        .consistency-container { max-width: 1000px; margin: 0 auto; padding: 40px 20px; }
        .novel-select-section { background: var(--bg-card, #fff); padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .novel-select-section select { width: 100%; padding: 12px; border: 1px solid var(--border-color, #e5e7eb); border-radius: 8px; font-size: 16px; }
        .section-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .section-card { background: var(--bg-card, #fff); border-radius: 12px; padding: 20px; border: 1px solid var(--border-color, #e5e7eb); }
        .section-card h3 { margin: 0 0 15px 0; color: var(--text-primary, #333); font-size: 16px; }
        .section-card select, .section-card textarea { width: 100%; padding: 10px; border: 1px solid var(--border-color, #e5e7eb); border-radius: 8px; font-size: 14px; }
        .section-card textarea { min-height: 200px; resize: vertical; }
        .empty-message { text-align: center; padding: 40px; color: var(--text-muted, #999); }
        @media (max-width: 768px) {
            .section-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="page-novel-creation">
    <div class="consistency-container">
        <h1 style="text-align: center; margin-bottom: 30px; color: var(--text-primary, #333);">🔍 角色一致性检查</h1>
        
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
        
        <?php if ($novel_id > 0): ?>
        <form action="/novel_creation/do_character_consistency_check" method="post">
            <input type="hidden" name="novel_id" value="<?= $novel_id ?>">
            
            <div class="section-row">
                <!-- 章节选择 -->
                <div class="section-card">
                    <h3>📖 选择章节</h3>
                    <select name="chapter_id" id="chapterSelect" onchange="loadChapter(this.value)">
                        <option value="0">-- 请选择章节 --</option>
                        <?php foreach ($chapters as $chapter): ?>
                        <option value="<?= $chapter['id'] ?>" <?= $chapter_id == $chapter['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($chapter['title'] ?: '第 ' . $chapter['chapter_number'] . ' 章') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- 角色选择 -->
                <div class="section-card">
                    <h3>👤 选择要检查的角色</h3>
                    <select name="character_id" id="characterSelect" onchange="loadCharacter(this.value)">
                        <option value="0">-- 请选择角色 --</option>
                        <?php foreach ($characters as $character): ?>
                        <option value="<?= $character['id'] ?>">
                            <?= htmlspecialchars($character['name']) ?> 
                            (<?= $character['role_type'] == 'protagonist' ? '主角' : ($character['role_type'] == 'supporting' ? '配角' : ($character['role_type'] == 'antagonist' ? '反派' : '其他')) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- 章节内容 -->
            <div class="section-card" style="margin-bottom: 20px;">
                <h3>📝 章节内容</h3>
                <textarea name="chapter_content" id="chapterContent" placeholder="输入或选择章节内容进行角色一致性检查..."><?= htmlspecialchars($current_chapter['content'] ?? '') ?></textarea>
            </div>
            
            <!-- 角色设定 -->
            <div class="section-card" style="margin-bottom: 20px;">
                <h3>📋 角色设定</h3>
                <textarea name="character_settings" id="characterSettings" placeholder="输入角色的设定信息，包括性格、背景、能力、说话方式等..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg btn-block">🔍 开始检查</button>
        </form>
        <?php else: ?>
        <div class="empty-message">
            <p style="font-size: 48px; margin-bottom: 10px;">📚</p>
            <p>请先选择小说</p>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function switchNovel(novelId) {
            if (novelId > 0) {
                window.location.href = '/novel_creation/character_consistency_check?novel_id=' + novelId;
            }
        }
        
        function loadChapter(chapterId) {
            if (chapterId > 0) {
                // 加载章节内容
                const chapters = <?= json_encode(array_column($chapters ?? [], 'content', 'id')) ?>;
                if (chapters[chapterId]) {
                    document.getElementById('chapterContent').value = chapters[chapterId];
                }
            }
        }
        
        function loadCharacter(characterId) {
            if (characterId > 0) {
                // 加载角色设定
                const characters = <?= json_encode(array_map(function($c) {
                    return [
                        'name' => $c['name'],
                        'age' => $c['age'],
                        'gender' => $c['gender'],
                        'appearance' => $c['appearance'],
                        'personality' => $c['personality'],
                        'background' => $c['background'],
                        'abilities' => $c['abilities'],
                        'motivation' => $c['motivation']
                    ];
                }, $characters ?? [])) ?>;
                
                if (characters[characterId]) {
                    const char = characters[characterId];
                    const personality = typeof char.personality === 'string' ? char.personality : JSON.stringify(char.personality);
                    document.getElementById('characterSettings').value = 
                        '角色名称：' + char.name + '\n' +
                        '年龄：' + char.age + '\n' +
                        '性别：' + (char.gender === 'male' ? '男' : (char.gender === 'female' ? '女' : '未知')) + '\n\n' +
                        '外貌描写：' + (char.appearance || '') + '\n\n' +
                        '性格特点：' + personality + '\n\n' +
                        '背景故事：' + (char.background || '') + '\n\n' +
                        '能力特长：' + (char.abilities || '') + '\n\n' +
                        '核心动机：' + (char.motivation || '');
                }
            }
        }
    </script>
</body>
</html>
