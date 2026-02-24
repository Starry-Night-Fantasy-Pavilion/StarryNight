<?php
$title = $title ?? '智能编辑器 - 星夜阁';
$novels = $novels ?? [];
$current_novel = $current_novel ?? null;
$chapters = $chapters ?? [];
$current_chapter = $current_chapter ?? null;
$novel_id = $novel_id ?? 0;
$chapter_id = $chapter_id ?? 0;

// 获取当前主题CSS路径
use app\services\ThemeManager;
use app\config\FrontendConfig;
$themeManager = new ThemeManager();
$activeThemeId = $themeManager->getActiveThemeId('web') ?? FrontendConfig::THEME_DEFAULT;
$themeBasePath = FrontendConfig::getThemePath($activeThemeId);
$cssPath = FrontendConfig::getAssetUrl(FrontendConfig::PATH_STATIC_FRONTEND_WEB_CSS . '/pages/novel-editor.css');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= $cssPath ?>">
    <style>
        .page-novel-editor { min-height: 100vh; }
    </style>
</head>
<body class="page-novel-editor">
    <div class="editor-container">
        <!-- 左侧边栏 -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>📚 小说选择</h3>
                <select class="novel-select" id="novelSelect" onchange="switchNovel(this.value)">
                    <option value="0">-- 请选择小说 --</option>
                    <?php foreach ($novels as $novel): ?>
                    <option value="<?= $novel['id'] ?>" <?= $novel_id == $novel['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($novel['title']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary btn-sm" onclick="showNewChapterModal()" style="width: 100%;">+ 新建章节</button>
            </div>
            <div class="chapter-list" id="chapterList">
                <?php if (empty($chapters)): ?>
                <div class="empty-state">
                    <p>暂无章节</p>
                </div>
                <?php else: ?>
                    <?php foreach ($chapters as $chapter): ?>
                    <div class="chapter-item <?= $chapter_id == $chapter['id'] ? 'active' : '' ?>" 
                         onclick="loadChapter(<?= $chapter['id'] ?>)">
                        <div class="chapter-title"><?= htmlspecialchars($chapter['title'] ?: '第 ' . $chapter['chapter_number'] . ' 章') ?></div>
                        <div class="chapter-meta">
                            <span class="status-badge status-<?= $chapter['status'] ?>">
                                <?= $chapter['status'] == 'published' ? '已发布' : '草稿' ?>
                            </span>
                            <span><?= number_format($chapter['word_count']) ?> 字</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 主编辑区 -->
        <div class="main-content">
            <div class="editor-toolbar">
                <div class="toolbar-group">
                    <button class="btn btn-success btn-sm" onclick="saveChapter()">💾 保存</button>
                    <button class="btn btn-secondary btn-sm" onclick="publishChapter()">🚀 发布</button>
                </div>
                <div class="toolbar-group">
                    <button class="btn btn-primary btn-sm" onclick="showAIFunction('continue')">✍️ 续写</button>
                    <button class="btn btn-primary btn-sm" onclick="showAIFunction('rewrite')">🔄 改写</button>
                    <button class="btn btn-primary btn-sm" onclick="showAIFunction('expand')">📝 扩写</button>
                    <button class="btn btn-primary btn-sm" onclick="showAIFunction('polish')">✨ 润色</button>
                </div>
                <div class="toolbar-group">
                    <button class="btn btn-secondary btn-sm" onclick="showVersions()">📜 历史</button>
                </div>
                <span class="word-count" id="wordCount">0 字</span>
                <span class="save-status" id="saveStatus"></span>
            </div>
            <div class="editor-area">
                <div class="editor-wrapper">
                    <input type="text" class="editor-title-input" id="chapterTitle" 
                           placeholder="章节标题" 
                           value="<?= htmlspecialchars($current_chapter['title'] ?? '') ?>">
                    <textarea class="editor-content" id="chapterContent" 
                              placeholder="开始书写你的故事..."><?= htmlspecialchars($current_chapter['content'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- AI辅助面板 -->
        <div class="ai-panel">
            <div class="ai-panel-header">🤖 AI 辅助</div>
            <div class="ai-panel-tabs">
                <button class="ai-tab active" onclick="switchAITab('assist')">写作辅助</button>
                <button class="ai-tab" onclick="switchAITab('history')">版本历史</button>
            </div>
            <div class="ai-panel-content" id="aiPanelContent">
                <!-- 写作辅助内容 -->
                <div id="aiAssistTab">
                    <div class="ai-form-group">
                        <label>续写上文</label>
                        <textarea id="aiContext" placeholder="输入上文内容或使用编辑器内容"><?= htmlspecialchars($current_chapter['content'] ?? '') ?></textarea>
                    </div>
                    <div class="ai-form-group">
                        <label>人物设定</label>
                        <textarea id="aiCharacters" placeholder="输入主要人物设定（可选）"></textarea>
                    </div>
                    <div class="ai-form-group">
                        <label>情节要求</label>
                        <textarea id="aiPlotRequirements" placeholder="输入情节发展方向（可选）"></textarea>
                    </div>
                    <div class="ai-form-group">
                        <label>创作字数</label>
                        <select id="aiWordCount">
                            <option value="300">300 字</option>
                            <option value="500" selected>500 字</option>
                            <option value="1000">1000 字</option>
                            <option value="2000">2000 字</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" style="width: 100%;" onclick="aiContinue()" id="btnContinue">AI 续写</button>
                    
                    <div id="aiResult" class="ai-result" style="display: none;"></div>
                </div>
                
                <!-- 版本历史内容 -->
                <div id="aiHistoryTab" style="display: none;">
                    <div class="version-list" id="versionList">
                        <p style="text-align: center; color: #999; padding: 20px;">暂无历史版本</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 新建章节弹窗 -->
    <div class="modal" id="newChapterModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>新建章节</h3>
                <button class="modal-close" onclick="closeModal('newChapterModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="ai-form-group">
                    <label>章节标题</label>
                    <input type="text" id="newChapterTitle" placeholder="输入章节标题（可选）">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('newChapterModal')">取消</button>
                <button class="btn btn-primary" onclick="createChapter()">创建</button>
            </div>
        </div>
    </div>

    <!-- AI功能弹窗 -->
    <div class="modal" id="aiFunctionModal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3 id="aiFunctionTitle">AI 功能</h3>
                <button class="modal-close" onclick="closeModal('aiFunctionModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="aiFunctionForm"></div>
                <div id="aiFunctionResult" class="ai-result" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('aiFunctionModal')">关闭</button>
                <button class="btn btn-primary" id="btnAIExecute" onclick="executeAIFunction()">执行</button>
            </div>
        </div>
    </div>

    <!-- 版本历史弹窗 -->
    <div class="modal" id="versionsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>版本历史</h3>
                <button class="modal-close" onclick="closeModal('versionsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="version-list" id="modalVersionList"></div>
            </div>
        </div>
    </div>

    <script>
        let currentChapterId = <?= $chapter_id ?>;
        let currentNovelId = <?= $novel_id ?>;
        let autoSaveTimer = null;

        // 页面加载时初始化
        document.addEventListener('DOMContentLoaded', function() {
            updateWordCount();
            
            // 实时字数统计
            document.getElementById('chapterContent').addEventListener('input', function() {
                updateWordCount();
            });

            // 自动保存（每30秒）
            autoSaveTimer = setInterval(autoSave, 30000);
        });

        // 更新字数统计
        function updateWordCount() {
            const content = document.getElementById('chapterContent').value;
            const wordCount = content.replace(/<[^>]*>/g, '').replace(/\s/g, '').length;
            document.getElementById('wordCount').textContent = wordCount + ' 字';
        }

        // 切换小说
        function switchNovel(novelId) {
            if (novelId > 0) {
                window.location.href = '/novel_creation/editor?novel_id=' + novelId;
            }
        }

        // 加载章节
        function loadChapter(chapterId) {
            window.location.href = '/novel_creation/editor?novel_id=' + currentNovelId + '&chapter_id=' + chapterId;
        }

        // 显示新建章节弹窗
        function showNewChapterModal() {
            if (currentNovelId <= 0) {
                alert('请先选择小说');
                return;
            }
            document.getElementById('newChapterModal').classList.add('active');
        }

        // 创建章节
        function createChapter() {
            const title = document.getElementById('newChapterTitle').value;
            
            fetch('/novel_creation/save_chapter', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'novel_id=' + currentNovelId + '&title=' + encodeURIComponent(title)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeModal('newChapterModal');
                    loadChapter(data.chapter_id);
                } else {
                    alert(data.error || '创建失败');
                }
            });
        }

        // 保存章节
        function saveChapter() {
            const title = document.getElementById('chapterTitle').value;
            const content = document.getElementById('chapterContent').value;
            const status = 'draft';
            
            if (currentNovelId <= 0) {
                alert('请先选择小说');
                return;
            }
            
            document.getElementById('saveStatus').innerHTML = '<span class="loading">保存中...</span>';
            
            fetch('/novel_creation/save_chapter', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'novel_id=' + currentNovelId + '&chapter_id=' + currentChapterId + 
                      '&title=' + encodeURIComponent(title) + 
                      '&content=' + encodeURIComponent(content) + 
                      '&status=' + status
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    currentChapterId = data.chapter_id;
                    document.getElementById('saveStatus').innerHTML = 
                        '<span class="auto-save-indicator">✓ 已保存</span>';
                } else {
                    document.getElementById('saveStatus').textContent = '保存失败';
                }
            })
            .catch(() => {
                document.getElementById('saveStatus').textContent = '保存失败';
            });
        }

        // 发布章节
        function publishChapter() {
            if (currentNovelId <= 0) {
                alert('请先选择小说');
                return;
            }
            
            if (!confirm('确定要发布章节吗？')) return;
            
            const title = document.getElementById('chapterTitle').value;
            const content = document.getElementById('chapterContent').value;
            
            fetch('/novel_creation/save_chapter', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'novel_id=' + currentNovelId + '&chapter_id=' + currentChapterId + 
                      '&title=' + encodeURIComponent(title) + 
                      '&content=' + encodeURIComponent(content) + 
                      '&status=published'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('发布成功！');
                    loadChapter(currentChapterId);
                } else {
                    alert(data.error || '发布失败');
                }
            });
        }

        // 自动保存
        function autoSave() {
            if (currentNovelId <= 0 || currentChapterId <= 0) return;
            saveChapter();
        }

        // 切换AI面板标签
        function switchAITab(tab) {
            document.querySelectorAll('.ai-tab').forEach(t => t.classList.remove('active'));
            document.querySelector('.ai-tab:nth-child(' + (tab === 'assist' ? 1 : 2) + ')').classList.add('active');
            
            document.getElementById('aiAssistTab').style.display = tab === 'assist' ? 'block' : 'none';
            document.getElementById('aiHistoryTab').style.display = tab === 'history' ? 'block' : 'none';
            
            if (tab === 'history') {
                loadVersions();
            }
        }

        // AI续写
        function aiContinue() {
            const context = document.getElementById('aiContext').value || document.getElementById('chapterContent').value;
            const characters = document.getElementById('aiCharacters').value;
            const plotRequirements = document.getElementById('aiPlotRequirements').value;
            const wordCount = document.getElementById('aiWordCount').value;
            
            const btn = document.getElementById('btnContinue');
            btn.textContent = '生成中...';
            btn.disabled = true;
            
            fetch('/novel_creation/ai_continue', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'context=' + encodeURIComponent(context) + 
                      '&characters=' + encodeURIComponent(characters) + 
                      '&plot_requirements=' + encodeURIComponent(plotRequirements) + 
                      '&word_count=' + wordCount
            })
            .then(r => r.json())
            .then(data => {
                btn.textContent = 'AI 续写';
                btn.disabled = false;
                
                if (data.success) {
                    const resultDiv = document.getElementById('aiResult');
                    resultDiv.style.display = 'block';
                    resultDiv.innerHTML = '<strong>AI生成内容：</strong><pre>' + data.content + '</pre>' +
                        '<button class="btn btn-primary btn-sm" style="margin-top: 10px;" onclick="insertContent()">插入到编辑器</button>';
                    window.aiGeneratedContent = data.content;
                } else {
                    alert(data.error || '生成失败');
                }
            })
            .catch(() => {
                btn.textContent = 'AI 续写';
                btn.disabled = false;
                alert('生成失败，请稍后重试');
            });
        }

        // 插入AI生成内容
        function insertContent() {
            const content = document.getElementById('chapterContent').value;
            document.getElementById('chapterContent').value = content + '\n\n' + window.aiGeneratedContent;
            updateWordCount();
        }

        // 显示AI功能弹窗
        function showAIFunction(func) {
            const modal = document.getElementById('aiFunctionModal');
            const title = document.getElementById('aiFunctionTitle');
            const form = document.getElementById('aiFunctionForm');
            
            window.currentAIFunction = func;
            
            switch(func) {
                case 'continue':
                    title.textContent = 'AI 续写';
                    form.innerHTML = `
                        <div class="ai-form-group">
                            <label>上文内容</label>
                            <textarea id="aiFuncContext" style="min-height: 120px;">${document.getElementById('chapterContent').value}</textarea>
                        </div>
                        <div class="ai-form-group">
                            <label>人物设定</label>
                            <textarea id="aiFuncCharacters" placeholder="输入人物设定（可选）"></textarea>
                        </div>
                        <div class="ai-form-group">
                            <label>情节要求</label>
                            <textarea id="aiFuncPlot" placeholder="情节发展方向（可选）"></textarea>
                        </div>
                        <div class="ai-form-group">
                            <label>创作字数</label>
                            <select id="aiFuncWordCount">
                                <option value="300">300 字</option>
                                <option value="500" selected>500 字</option>
                                <option value="1000">1000 字</option>
                                <option value="2000">2000 字</option>
                            </select>
                        </div>
                    `;
                    break;
                case 'rewrite':
                    title.textContent = 'AI 改写';
                    form.innerHTML = `
                        <div class="ai-form-group">
                            <label>原文内容</label>
                            <textarea id="aiFuncContent" style="min-height: 120px;">${document.getElementById('chapterContent').value}</textarea>
                        </div>
                        <div class="ai-form-group">
                            <label>改写要求</label>
                            <textarea id="aiFuncRequirements" placeholder="例如：简化句子、增强描写、改变风格等"></textarea>
                        </div>
                    `;
                    break;
                case 'expand':
                    title.textContent = 'AI 扩写';
                    form.innerHTML = `
                        <div class="ai-form-group">
                            <label>原文内容</label>
                            <textarea id="aiFuncContent" style="min-height: 120px;">${document.getElementById('chapterContent').value}</textarea>
                        </div>
                        <div class="ai-form-group">
                            <label>目标字数</label>
                            <select id="aiFuncTargetWords">
                                <option value="1000">1000 字</option>
                                <option value="1500" selected>1500 字</option>
                                <option value="2000">2000 字</option>
                                <option value="3000">3000 字</option>
                            </select>
                        </div>
                        <div class="ai-form-group">
                            <label>扩写方向</label>
                            <textarea id="aiFuncDirection" placeholder="描述扩写方向，例如：增加细节描写、展开对话等"></textarea>
                        </div>
                    `;
                    break;
                case 'polish':
                    title.textContent = 'AI 润色';
                    form.innerHTML = `
                        <div class="ai-form-group">
                            <label>原文内容</label>
                            <textarea id="aiFuncContent" style="min-height: 120px;">${document.getElementById('chapterContent').value}</textarea>
                        </div>
                        <div class="ai-form-group">
                            <label>润色风格</label>
                            <select id="aiFuncStyle">
                                <option value="">默认</option>
                                <option value="文学">文学风格</option>
                                <option value="简洁">简洁明了</option>
                                <option value="诗意">诗意优美</option>
                                <option value="幽默">幽默风趣</option>
                                <option value="严肃">严肃正式</option>
                            </select>
                        </div>
                    `;
                    break;
            }
            
            modal.classList.add('active');
        }

        // 执行AI功能
        function executeAIFunction() {
            const func = window.currentAIFunction;
            let url = '', body = '';
            
            switch(func) {
                case 'continue':
                    url = '/novel_creation/ai_continue';
                    body = 'context=' + encodeURIComponent(document.getElementById('aiFuncContext').value) +
                           '&characters=' + encodeURIComponent(document.getElementById('aiFuncCharacters').value) +
                           '&plot_requirements=' + encodeURIComponent(document.getElementById('aiFuncPlot').value) +
                           '&word_count=' + document.getElementById('aiFuncWordCount').value;
                    break;
                case 'rewrite':
                    url = '/novel_creation/ai_rewrite';
                    body = 'content=' + encodeURIComponent(document.getElementById('aiFuncContent').value) +
                           '&requirements=' + encodeURIComponent(document.getElementById('aiFuncRequirements').value);
                    break;
                case 'expand':
                    url = '/novel_creation/ai_expand';
                    body = 'content=' + encodeURIComponent(document.getElementById('aiFuncContent').value) +
                           '&target_words=' + document.getElementById('aiFuncTargetWords').value +
                           '&direction=' + encodeURIComponent(document.getElementById('aiFuncDirection').value);
                    break;
                case 'polish':
                    url = '/novel_creation/ai_polish';
                    body = 'content=' + encodeURIComponent(document.getElementById('aiFuncContent').value) +
                           '&style=' + encodeURIComponent(document.getElementById('aiFuncStyle').value);
                    break;
            }
            
            const btn = document.getElementById('btnAIExecute');
            btn.textContent = '生成中...';
            btn.disabled = true;
            
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
            .then(r => r.json())
            .then(data => {
                btn.textContent = '执行';
                btn.disabled = false;
                
                if (data.success) {
                    const resultDiv = document.getElementById('aiFunctionResult');
                    resultDiv.style.display = 'block';
                    resultDiv.innerHTML = '<strong>AI生成内容：</strong><pre style="max-height: 300px; overflow-y: auto;">' + data.content + '</pre>' +
                        '<button class="btn btn-primary btn-sm" style="margin-top: 10px;" onclick="replaceContent()">替换原文</button>' +
                        '<button class="btn btn-secondary btn-sm" style="margin-top: 10px; margin-left: 5px;" onclick="appendContent()">追加到原文</button>';
                    window.aiGeneratedContent = data.content;
                } else {
                    alert(data.error || '生成失败');
                }
            })
            .catch(() => {
                btn.textContent = '执行';
                btn.disabled = false;
                alert('生成失败，请稍后重试');
            });
        }

        // 替换原文
        function replaceContent() {
            document.getElementById('chapterContent').value = window.aiGeneratedContent;
            updateWordCount();
            closeModal('aiFunctionModal');
        }

        // 追加到原文
        function appendContent() {
            document.getElementById('chapterContent').value += '\n\n' + window.aiGeneratedContent;
            updateWordCount();
            closeModal('aiFunctionModal');
        }

        // 加载版本历史
        function loadVersions() {
            if (currentChapterId <= 0) {
                document.getElementById('versionList').innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">请先加载章节</p>';
                return;
            }
            
            fetch('/novel_creation/get_chapter_versions?chapter_id=' + currentChapterId)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.versions.length > 0) {
                    let html = '';
                    data.versions.forEach(v => {
                        const date = new Date(v.created_at);
                        html += '<div class="version-item" onclick="restoreVersion(' + v.id + ')">' +
                            '<div class="version-time">' + date.toLocaleString() + '</div>' +
                            '<div class="version-words">' + v.word_count + ' 字</div>' +
                            '</div>';
                    });
                    document.getElementById('versionList').innerHTML = html;
                    document.getElementById('modalVersionList').innerHTML = html;
                } else {
                    document.getElementById('versionList').innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">暂无历史版本</p>';
                    document.getElementById('modalVersionList').innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">暂无历史版本</p>';
                }
            })
            .catch(() => {
                document.getElementById('versionList').innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">加载失败</p>';
            });
        }

        // 显示版本历史弹窗
        function showVersions() {
            if (currentChapterId <= 0) {
                alert('请先选择章节');
                return;
            }
            loadVersions();
            document.getElementById('versionsModal').classList.add('active');
        }

        // 恢复版本
        function restoreVersion(versionId) {
            if (!confirm('确定要恢复此版本吗？当前内容将被覆盖。')) return;
            
            fetch('/novel_creation/restore_version', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'chapter_id=' + currentChapterId + '&version_id=' + versionId
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('版本已恢复');
                    closeModal('versionsModal');
                    loadChapter(currentChapterId);
                } else {
                    alert(data.error || '恢复失败');
                }
            });
        }

        // 关闭弹窗
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // 点击弹窗外部关闭
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>
