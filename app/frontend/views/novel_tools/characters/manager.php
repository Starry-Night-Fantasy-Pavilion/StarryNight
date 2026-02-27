<?php
$title = $title ?? '角色管理 - 星夜阁';
$novels = $novels ?? [];
$novel_id = $novel_id ?? 0;
$characters = $characters ?? [];
$relationships = $relationships ?? [];

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
        .page-character-manager { min-height: 100vh; background: var(--bg-primary, #f5f5f5); }
        .manager-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        .novel-select-section { background: var(--bg-card, #fff); padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .novel-select-section select { width: 100%; padding: 12px; border: 1px solid var(--border-color, #e5e7eb); border-radius: 8px; font-size: 16px; }
        .character-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .character-card { background: var(--bg-card, #fff); border-radius: 12px; padding: 20px; border: 1px solid var(--border-color, #e5e7eb); }
        .character-card-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .character-avatar { width: 60px; height: 60px; border-radius: 50%; background: var(--primary-light, rgba(99, 102, 241, 0.1)); display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .character-info h4 { margin: 0 0 5px 0; color: var(--text-primary, #333); font-size: 18px; }
        .character-role { display: inline-block; padding: 3px 10px; border-radius: 15px; font-size: 12px; background: var(--primary-color, #6366f1); color: white; }
        .role-protagonist { background: #10b981; }
        .role-supporting { background: #6366f1; }
        .role-antagonist { background: #ef4444; }
        .character-detail { margin-bottom: 10px; }
        .character-detail label { font-weight: 500; color: var(--text-secondary, #666); font-size: 13px; }
        .character-detail p { margin: 5px 0 0 0; color: var(--text-primary, #333); font-size: 14px; }
        .character-actions { display: flex; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color, #e5e7eb); }
        .empty-characters { text-align: center; padding: 60px 20px; color: var(--text-muted, #999); }
        .action-bar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .relationship-section { background: var(--bg-card, #fff); border-radius: 12px; padding: 20px; margin-top: 20px; }
        .relationship-item { display: flex; align-items: center; gap: 10px; padding: 10px; background: var(--bg-secondary, #f8f9fa); border-radius: 8px; margin-bottom: 10px; }
        .relationship-arrow { color: var(--text-muted, #999); }
    </style>
</head>
<body class="page-character-manager">
    <div class="manager-container">
        <h1 style="text-align: center; margin-bottom: 30px; color: var(--text-primary, #333);">👥 角色管理系统</h1>
        
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
        <!-- 操作栏 -->
        <div class="action-bar">
            <a href="/novel_creation/character_generator?novel_id=<?= $novel_id ?>" class="btn btn-primary">✨ AI生成角色</a>
            <button class="btn btn-secondary" onclick="showAddCharacterModal()">➕ 手动添加</button>
        </div>
        
        <!-- 角色列表 -->
        <?php if (!empty($characters)): ?>
        <div class="character-grid">
            <?php foreach ($characters as $char): 
                $personality = json_decode($char['personality'] ?? '{}', true);
                $relationships = json_decode($char['relationships_json'] ?? '[]', true);
            ?>
            <div class="character-card">
                <div class="character-card-header">
                    <div class="character-avatar"><?= mb_substr($char['name'] ?? '?', 0, 1) ?></div>
                    <div class="character-info">
                        <h4><?= htmlspecialchars($char['name']) ?></h4>
                        <span class="character-role role-<?= $char['role_type'] ?>">
                            <?= $char['role_type'] == 'protagonist' ? '主角' : ($char['role_type'] == 'supporting' ? '配角' : ($char['role_type'] == 'antagonist' ? '反派' : '其他')) ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($char['age']): ?>
                <div class="character-detail">
                    <label>基本信息</label>
                    <p><?= $char['age'] ?>岁 | <?= $char['gender'] == 'male' ? '男' : ($char['gender'] == 'female' ? '女' : '未知') ?> | <?= $char['abilities'] ?? '无特殊能力' ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($char['appearance']): ?>
                <div class="character-detail">
                    <label>外貌描写</label>
                    <p><?= htmlspecialchars(mb_substr($char['appearance'], 0, 100)) ?><?= mb_strlen($char['appearance']) > 100 ? '...' : '' ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($char['personality']): ?>
                <div class="character-detail">
                    <label>性格特点</label>
                    <p><?= htmlspecialchars(implode(', ', $personality)) ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($char['motivation']): ?>
                <div class="character-detail">
                    <label>核心动机</label>
                    <p><?= htmlspecialchars(mb_substr($char['motivation'], 0, 80)) ?><?= mb_strlen($char['motivation']) > 80 ? '...' : '' ?></p>
                </div>
                <?php endif; ?>
                
                <div class="character-actions">
                    <button class="btn btn-primary btn-sm" onclick="editCharacter(<?= $char['id'] ?>)">编辑</button>
                    <button class="btn btn-secondary btn-sm" onclick="checkConsistency(<?= $char['id'] ?>)">一致性检查</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteCharacter(<?= $char['id'] ?>)">删除</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-characters">
            <p style="font-size: 48px; margin-bottom: 10px;">📝</p>
            <p>暂无角色</p>
            <p>请点击上方按钮添加角色</p>
        </div>
        <?php endif; ?>
        
        <!-- 角色关系 -->
        <?php if (!empty($relationships)): ?>
        <div class="relationship-section">
            <h3 style="margin-bottom: 15px;">🔗 角色关系图</h3>
            <?php foreach ($relationships as $rel): ?>
            <div class="relationship-item">
                <strong><?= htmlspecialchars($rel['from'] ?? '') ?></strong>
                <span class="relationship-arrow">→</span>
                <span><?= htmlspecialchars($rel['type'] ?? '') ?></span>
                <span class="relationship-arrow">→</span>
                <strong><?= htmlspecialchars($rel['to'] ?? '') ?></strong>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="empty-characters">
            <p style="font-size: 48px; margin-bottom: 10px;">📚</p>
            <p>请先选择小说</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- 添加角色弹窗 -->
    <div class="modal" id="addCharacterModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>添加角色</h3>
                <button class="modal-close" onclick="closeModal('addCharacterModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="characterForm">
                    <input type="hidden" name="novel_id" value="<?= $novel_id ?>">
                    <div class="form-group">
                        <label>角色名称</label>
                        <input type="text" name="name" required placeholder="输入角色名称">
                    </div>
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
                        <label>年龄</label>
                        <input type="number" name="age" placeholder="输入年龄">
                    </div>
                    <div class="form-group">
                        <label>性别</label>
                        <select name="gender">
                            <option value="unknown">未知</option>
                            <option value="male">男</option>
                            <option value="female">女</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>外貌描写</label>
                        <textarea name="appearance" rows="3" placeholder="描述角色的外貌特征"></textarea>
                    </div>
                    <div class="form-group">
                        <label>性格特点</label>
                        <textarea name="personality" rows="2" placeholder="描述角色的性格，用逗号分隔"></textarea>
                    </div>
                    <div class="form-group">
                        <label>背景故事</label>
                        <textarea name="background" rows="3" placeholder="角色的成长经历和关键事件"></textarea>
                    </div>
                    <div class="form-group">
                        <label>能力特长</label>
                        <input type="text" name="abilities" placeholder="角色的能力和弱点">
                    </div>
                    <div class="form-group">
                        <label>核心动机</label>
                        <textarea name="motivation" rows="2" placeholder="角色的核心追求和恐惧"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('addCharacterModal')">取消</button>
                <button class="btn btn-primary" onclick="saveCharacter()">保存</button>
            </div>
        </div>
    </div>

    <script>
        function switchNovel(novelId) {
            if (novelId > 0) {
                window.location.href = '/novel_creation/character_manager?novel_id=' + novelId;
            }
        }
        
        function showAddCharacterModal() {
            document.getElementById('addCharacterModal').classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function saveCharacter() {
            const form = document.getElementById('characterForm');
            const formData = new FormData(form);
            const data = {
                novel_id: <?= $novel_id ?>,
                character_data: JSON.stringify({
                    name: formData.get('name'),
                    role_type: formData.get('role_type'),
                    age: formData.get('age') ? parseInt(formData.get('age')) : null,
                    gender: formData.get('gender'),
                    appearance: formData.get('appearance'),
                    personality: formData.get('personality'),
                    background: formData.get('background'),
                    abilities: formData.get('abilities'),
                    motivation: formData.get('motivation')
                })
            };
            
            fetch('/novel_creation/save_character', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data).toString()
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeModal('addCharacterModal');
                    window.location.reload();
                } else {
                    alert(data.error || '保存失败');
                }
            });
        }
        
        function editCharacter(id) {
            alert('编辑功能开发中');
        }
        
        function checkConsistency(id) {
            window.location.href = '/novel_creation/character_consistency_check?novel_id=<?= $novel_id ?>&character_id=' + id;
        }
        
        function deleteCharacter(id) {
            if (!confirm('确定要删除这个角色吗？')) return;
            
            fetch('/novel_creation/delete_character', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'character_id=' + id
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error || '删除失败');
                }
            });
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>
