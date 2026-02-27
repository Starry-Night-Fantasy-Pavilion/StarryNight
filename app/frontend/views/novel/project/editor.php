<?php
/** @var array $novel */
/** @var array $chapters */
/** @var array $characters */
/** @var array $outlines */
?>
<div class="novel-editor-page" data-novel-id="<?= (int)$novel['id'] ?>">
    <div class="editor-layout">
        <!-- 左侧边栏 -->
        <div class="editor-sidebar">
            <div class="sidebar-section">
                <h3><?= htmlspecialchars($novel['title']) ?></h3>
                <div class="novel-info">
                    <div>字数：<?= number_format($novel['current_words'] ?? 0) ?> / <?= number_format($novel['target_words'] ?? 0) ?></div>
                    <div>状态：<?= htmlspecialchars($novel['status'] ?? 'draft') ?></div>
                </div>
            </div>

            <div class="sidebar-tabs">
                <button class="tab-btn active" data-tab="chapters">章节</button>
                <button class="tab-btn" data-tab="characters">角色</button>
                <button class="tab-btn" data-tab="outline">大纲</button>
            </div>

            <div class="tab-content active" id="tab-chapters">
                <div class="section-header">
                    <h4>章节列表</h4>
                    <button class="btn-sm btn-primary" onclick="createNewChapter()">+ 新建章节</button>
                </div>
                <div class="chapter-list">
                    <?php if (empty($chapters)): ?>
                        <div class="empty-state">暂无章节</div>
                    <?php else: ?>
                        <?php foreach ($chapters as $chapter): ?>
                            <div class="chapter-item" data-chapter-id="<?= (int)$chapter['id'] ?>" onclick="loadChapter(<?= (int)$chapter['id'] ?>)">
                                <div class="chapter-title"><?= htmlspecialchars($chapter['title'] ?: '第' . $chapter['chapter_number'] . '章') ?></div>
                                <div class="chapter-meta"><?= number_format($chapter['word_count'] ?? 0) ?> 字</div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-content" id="tab-characters">
                <div class="section-header">
                    <h4>角色管理</h4>
                    <button class="btn-sm btn-primary" onclick="showGenerateCharacterModal()">+ AI生成</button>
                </div>
                <div class="character-list">
                    <?php if (empty($characters)): ?>
                        <div class="empty-state">暂无角色</div>
                    <?php else: ?>
                        <?php foreach ($characters as $char): ?>
                            <div class="character-item">
                                <div class="character-name"><?= htmlspecialchars($char['name']) ?></div>
                                <div class="character-type"><?= htmlspecialchars($char['role_type'] ?? 'other') ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-content" id="tab-outline">
                <div class="section-header">
                    <h4>大纲</h4>
                    <button class="btn-sm btn-primary" onclick="generateOutline()">AI生成大纲</button>
                </div>
                <div class="outline-list">
                    <?php if (empty($outlines)): ?>
                        <div class="empty-state">暂无大纲</div>
                    <?php else: ?>
                        <?php foreach ($outlines as $outline): ?>
                            <div class="outline-item">
                                <div class="outline-title"><?= htmlspecialchars($outline['title'] ?? '大纲') ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 主编辑区 -->
        <div class="editor-main">
            <div class="editor-toolbar">
                <div class="toolbar-left">
                    <button class="btn-icon" onclick="saveChapter()" title="保存">💾</button>
                    <button class="btn-icon" onclick="showAIMenu()" title="AI辅助">✨</button>
                </div>
                <div class="toolbar-right">
                    <input type="text" id="chapter-title-input" class="title-input" placeholder="章节标题" value="">
                </div>
            </div>

            <div class="editor-content">
                <div id="editor" contenteditable="true" class="rich-editor" placeholder="开始创作..."></div>
            </div>

            <div class="editor-status">
                <span id="word-count">0 字</span>
                <span id="save-status" class="save-status">已保存</span>
            </div>
        </div>
    </div>

    <!-- AI功能菜单 -->
    <div id="ai-menu" class="ai-menu" style="display:none;">
        <div class="ai-menu-item" onclick="aiContinue()">续写</div>
        <div class="ai-menu-item" onclick="aiRewrite()">改写</div>
        <div class="ai-menu-item" onclick="aiExpand()">扩写</div>
        <div class="ai-menu-item" onclick="aiPolish()">润色</div>
    </div>

    <!-- AI生成角色模态框 -->
    <div id="character-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>AI生成角色</h3>
                <button class="modal-close" onclick="closeCharacterModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>角色类型</label>
                    <select id="character-role-type" class="form-control">
                        <option value="protagonist">主角</option>
                        <option value="supporting">配角</option>
                        <option value="antagonist">反派</option>
                        <option value="other">其他</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>性格特点（可选）</label>
                    <input type="text" id="character-personality" class="form-control" placeholder="例如：勇敢、善良、有点冲动">
                </div>
                <div class="form-group">
                    <label>故事作用（可选）</label>
                    <input type="text" id="character-function" class="form-control" placeholder="例如：主角的导师">
                </div>
                <button class="btn btn-primary" onclick="doGenerateCharacter()">生成</button>
            </div>
        </div>
    </div>
</div>

<style>
.novel-editor-page {
    height: calc(100vh - 60px);
    display: flex;
    flex-direction: column;
}
.editor-layout {
    display: flex;
    height: 100%;
}
.editor-sidebar {
    width: 280px;
    background: rgba(255,255,255,0.03);
    border-right: 1px solid rgba(255,255,255,0.1);
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}
.sidebar-section {
    padding: 16px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.sidebar-section h3 {
    font-size: 16px;
    margin-bottom: 8px;
}
.novel-info {
    font-size: 12px;
    opacity: 0.7;
}
.sidebar-tabs {
    display: flex;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.tab-btn {
    flex: 1;
    padding: 12px;
    background: transparent;
    border: none;
    color: rgba(255,255,255,0.7);
    cursor: pointer;
    border-bottom: 2px solid transparent;
}
.tab-btn.active {
    color: #0ea5e9;
    border-bottom-color: #0ea5e9;
}
.tab-content {
    display: none;
    flex: 1;
    overflow-y: auto;
    padding: 12px;
}
.tab-content.active {
    display: block;
}
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.section-header h4 {
    font-size: 14px;
    font-weight: 600;
}
.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
}
.btn-primary {
    background: #0ea5e9;
    color: #fff;
}
.chapter-item, .character-item, .outline-item {
    padding: 10px;
    margin-bottom: 8px;
    background: rgba(255,255,255,0.05);
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
}
.chapter-item:hover {
    background: rgba(255,255,255,0.1);
}
.chapter-item.active {
    background: rgba(14, 165, 233, 0.2);
    border: 1px solid #0ea5e9;
}
.chapter-title {
    font-weight: 500;
    margin-bottom: 4px;
}
.chapter-meta {
    font-size: 12px;
    opacity: 0.7;
}
.empty-state {
    text-align: center;
    padding: 24px;
    opacity: 0.5;
    font-size: 14px;
}
.editor-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: rgba(255,255,255,0.02);
}
.editor-toolbar {
    padding: 12px 16px;
    background: rgba(255,255,255,0.05);
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.toolbar-left {
    display: flex;
    gap: 8px;
}
.btn-icon {
    width: 32px;
    height: 32px;
    border: none;
    background: rgba(255,255,255,0.1);
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
}
.title-input {
    padding: 6px 12px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 6px;
    color: #fff;
    font-size: 14px;
    width: 300px;
}
.editor-content {
    flex: 1;
    padding: 24px;
    overflow-y: auto;
}
.rich-editor {
    min-height: 100%;
    outline: none;
    font-size: 16px;
    line-height: 1.8;
    color: #fff;
}
.rich-editor:empty:before {
    content: attr(placeholder);
    color: rgba(255,255,255,0.3);
}
.editor-status {
    padding: 8px 16px;
    background: rgba(255,255,255,0.05);
    border-top: 1px solid rgba(255,255,255,0.1);
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    opacity: 0.7;
}
.save-status {
    color: #22c55e;
}
.ai-menu {
    position: absolute;
    background: rgba(0,0,0,0.9);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 8px;
    padding: 8px;
    z-index: 1000;
}
.ai-menu-item {
    padding: 10px 16px;
    cursor: pointer;
    border-radius: 4px;
    transition: background 0.2s;
}
.ai-menu-item:hover {
    background: rgba(255,255,255,0.1);
}
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
}
.modal-content {
    background: #1a1f3a;
    border-radius: 12px;
    width: 500px;
    max-width: 90vw;
}
.modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-close {
    background: none;
    border: none;
    color: #fff;
    font-size: 24px;
    cursor: pointer;
    opacity: 0.7;
}
.modal-body {
    padding: 20px;
}
.form-group {
    margin-bottom: 16px;
}
.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
}
.form-control {
    width: 100%;
    padding: 10px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 6px;
    color: #fff;
}
</style>

<script>
let currentChapterId = 0;
let currentNovelId = <?= (int)$novel['id'] ?>;
let autoSaveTimer = null;

// 标签切换
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
});

// 自动保存
function startAutoSave() {
    if (autoSaveTimer) clearInterval(autoSaveTimer);
    autoSaveTimer = setInterval(() => {
        if (currentChapterId > 0) {
            saveChapter(true);
        }
    }, 30000); // 30秒自动保存
}

// 字数统计
function updateWordCount() {
    const content = document.getElementById('editor').innerText;
    const count = content.length;
    document.getElementById('word-count').textContent = count + ' 字';
}

document.getElementById('editor').addEventListener('input', () => {
    updateWordCount();
    document.getElementById('save-status').textContent = '未保存';
    document.getElementById('save-status').style.color = '#ef4444';
});

// 加载章节
function loadChapter(chapterId) {
    fetch(`/api/novel/chapter/${chapterId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                currentChapterId = chapterId;
                document.getElementById('editor').innerHTML = data.chapter.content || '';
                document.getElementById('chapter-title-input').value = data.chapter.title || '';
                document.querySelectorAll('.chapter-item').forEach(item => {
                    item.classList.remove('active');
                    if (parseInt(item.dataset.chapterId) === chapterId) {
                        item.classList.add('active');
                    }
                });
                updateWordCount();
                startAutoSave();
            }
        })
        .catch(err => {
            console.error('加载章节失败:', err);
            alert('加载章节失败');
        });
}

// 保存章节
function saveChapter(silent = false) {
    const title = document.getElementById('chapter-title-input').value;
    const content = document.getElementById('editor').innerHTML;

    const formData = new FormData();
    formData.append('novel_id', currentNovelId);
    if (currentChapterId > 0) {
        formData.append('chapter_id', currentChapterId);
    }
    formData.append('title', title);
    formData.append('content', content);

    fetch('/api/novel/chapter/save', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (!silent) {
                alert('保存成功');
            }
            if (data.chapter_id && currentChapterId === 0) {
                currentChapterId = data.chapter_id;
                location.reload();
            }
            document.getElementById('save-status').textContent = '已保存';
            document.getElementById('save-status').style.color = '#22c55e';
        } else {
            alert('保存失败: ' + (data.error || '未知错误'));
        }
    })
    .catch(err => {
        console.error('保存失败:', err);
        alert('保存失败');
    });
}

// 新建章节
function createNewChapter() {
    currentChapterId = 0;
    document.getElementById('editor').innerHTML = '';
    document.getElementById('chapter-title-input').value = '';
    updateWordCount();
}

// AI功能
function showAIMenu() {
    const menu = document.getElementById('ai-menu');
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

function aiContinue() {
    const content = document.getElementById('editor').innerText;
    const wordCount = prompt('续写字数（默认500）:', '500') || '500';
    
    const formData = new FormData();
    formData.append('novel_id', currentNovelId);
    formData.append('chapter_id', currentChapterId);
    formData.append('context', content);
    formData.append('word_count', wordCount);

    fetch('/novel/ai/continue', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('editor').innerHTML += '<p>' + data.content + '</p>';
            updateWordCount();
        } else {
            alert('AI续写失败: ' + (data.error || '未知错误'));
        }
    });
    document.getElementById('ai-menu').style.display = 'none';
}

function aiRewrite() {
    const selection = window.getSelection();
    const selectedText = selection.toString();
    if (!selectedText) {
        alert('请先选择要改写的内容');
        return;
    }
    const requirements = prompt('改写要求:', '');
    if (!requirements) return;

    const formData = new FormData();
    formData.append('content', selectedText);
    formData.append('requirements', requirements);

    fetch('/novel/ai/rewrite', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const range = selection.getRangeAt(0);
            range.deleteContents();
            range.insertNode(document.createTextNode(data.content));
        } else {
            alert('AI改写失败: ' + (data.error || '未知错误'));
        }
    });
    document.getElementById('ai-menu').style.display = 'none';
}

function aiExpand() {
    const content = document.getElementById('editor').innerText;
    const targetWords = prompt('目标字数:', '1000') || '1000';
    const direction = prompt('扩写方向（可选）:', '');

    const formData = new FormData();
    formData.append('content', content);
    formData.append('target_words', targetWords);
    formData.append('direction', direction);

    fetch('/novel/ai/expand', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('editor').innerHTML = '<p>' + data.content + '</p>';
            updateWordCount();
        } else {
            alert('AI扩写失败: ' + (data.error || '未知错误'));
        }
    });
    document.getElementById('ai-menu').style.display = 'none';
}

function aiPolish() {
    const content = document.getElementById('editor').innerText;
    const style = prompt('风格要求（可选）:', '');

    const formData = new FormData();
    formData.append('content', content);
    formData.append('style', style);

    fetch('/novel/ai/polish', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('editor').innerHTML = '<p>' + data.content + '</p>';
            updateWordCount();
        } else {
            alert('AI润色失败: ' + (data.error || '未知错误'));
        }
    });
    document.getElementById('ai-menu').style.display = 'none';
}

// 生成大纲
function generateOutline() {
    if (!confirm('确定要生成大纲吗？这将使用AI生成小说大纲。')) return;

    const formData = new FormData();
    formData.append('novel_id', currentNovelId);
    formData.append('outline_type', 'chapter');

    fetch('/novel/ai/outline', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('大纲生成成功！请查看左侧大纲面板。');
            location.reload();
        } else {
            alert('大纲生成失败: ' + (data.error || '未知错误'));
        }
    });
}

// 角色生成
function showGenerateCharacterModal() {
    document.getElementById('character-modal').style.display = 'flex';
}

function closeCharacterModal() {
    document.getElementById('character-modal').style.display = 'none';
}

function doGenerateCharacter() {
    const roleType = document.getElementById('character-role-type').value;
    const personality = document.getElementById('character-personality').value;
    const function_ = document.getElementById('character-function').value;

    const formData = new FormData();
    formData.append('novel_id', currentNovelId);
    formData.append('role_type', roleType);
    formData.append('personality_hints', personality);
    formData.append('story_function', function_);

    fetch('/novel/ai/character', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('角色生成成功！');
            closeCharacterModal();
            location.reload();
        } else {
            alert('角色生成失败: ' + (data.error || '未知错误'));
        }
    });
}

// 初始化
updateWordCount();
</script>
