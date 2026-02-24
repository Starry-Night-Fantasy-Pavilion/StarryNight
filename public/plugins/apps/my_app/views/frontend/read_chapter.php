<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($chapter['title']) ?> - <?= htmlspecialchars($chapter['book_title']) ?> - 在线书城</title>
    <style>
        body { 
            font-family: 'Georgia', 'serif';
            line-height: 1.8;
            margin: 0;
            padding: 0;
            background: #f8f9fa;
        }
        .reader-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            min-height: 100vh;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .reader-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .book-title {
            font-size: 24px;
            font-weight: bold;
            color: #212529;
            margin-bottom: 10px;
        }
        .chapter-title {
            font-size: 20px;
            color: #495057;
            margin-bottom: 20px;
        }
        .reader-content {
            font-size: 18px;
            line-height: 1.8;
            color: #212529;
            text-align: justify;
            margin-bottom: 40px;
            padding: 20px;
            background: #fafafa;
            border-radius: 8px;
        }
        .reader-toolbar {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px;
            z-index: 1000;
        }
        .toolbar-section {
            margin-bottom: 15px;
        }
        .toolbar-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #495057;
        }
        .bookmark-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
        }
        .bookmark-item {
            padding: 8px;
            border-bottom: 1px solid #f1f3f4;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 12px;
        }
        .bookmark-item:hover {
            background: #f8f9fa;
        }
        .bookmark-item:last-child {
            border-bottom: none;
        }
        .bookmark-chapter {
            font-weight: bold;
            color: #007bff;
        }
        .bookmark-note {
            color: #6c757d;
            font-size: 11px;
            margin-top: 2px;
        }
        .reader-nav {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .add-bookmark-form {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }
        .add-bookmark-form input {
            flex: 1;
            padding: 6px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .add-bookmark-form button {
            padding: 6px 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .ai-panel {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .ai-panel h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #495057;
        }
        .ai-panel textarea {
            width: 100%;
            min-height: 80px;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            resize: vertical;
            margin-bottom: 10px;
        }
        .ai-panel button {
            padding: 10px 15px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        .ai-panel button:hover {
            background: #0056b3;
        }
        #ai-result {
            margin-top: 15px;
            padding: 15px;
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 4px;
            white-space: pre-wrap;
            display: none;
        }
        .loading {
            text-align: center;
            padding: 10px;
            color: #6c757d;
        }
        .progress-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }
        .progress-fill {
            height: 100%;
            background: #007bff;
            transition: width 0.3s;
        }
        .close-toolbar {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="reader-container">
        <div class="reader-header">
            <div class="book-title"><?= htmlspecialchars($chapter['book_title']) ?></div>
            <div class="chapter-title"><?= htmlspecialchars($chapter['title']) ?></div>
            <?php if ($readingProgress): ?>
                <div class="progress-info">
                    阅读进度：<?= number_format($readingProgress['progress'], 1) ?>%
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $readingProgress['progress'] ?>%"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="reader-content" id="chapter-content">
            <?= nl2br(htmlspecialchars($chapter['content'])) ?>
        </div>

        <div class="reader-nav">
            <?php if ($prevChapterId): ?>
                <a href="/bookstore/read/<?= $chapter['book_id'] ?>/<?= $prevChapterId ?>" class="btn btn-secondary">
                    ← 上一章
                </a>
            <?php else: ?>
                <span class="btn btn-secondary" disabled>已是第一章</span>
            <?php endif; ?>
            
            <a href="/bookstore/book/<?= $chapter['book_id'] ?>" class="btn btn-primary">
                返回目录
            </a>
            
            <?php if ($nextChapterId): ?>
                <a href="/bookstore/read/<?= $chapter['book_id'] ?>/<?= $nextChapterId ?>" class="btn btn-secondary">
                    下一章 →
                </a>
            <?php else: ?>
                <span class="btn btn-secondary" disabled>已是最新章</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($userId): ?>
    <!-- 阅读工具栏 -->
    <div class="reader-toolbar" id="reader-toolbar">
        <button class="close-toolbar" onclick="toggleToolbar()">×</button>
        
        <!-- 书签 -->
        <div class="toolbar-section">
            <div class="toolbar-title">🔖 书签</div>
            <div class="bookmark-list">
                <?php if (!empty($bookmarks)): ?>
                    <?php foreach ($bookmarks as $bookmark): ?>
                        <div class="bookmark-item" onclick="scrollToBookmark(<?= $bookmark['position'] ?? 0 ?>)">
                            <div class="bookmark-chapter"><?= htmlspecialchars($bookmark['chapter_title']) ?></div>
                            <?php if ($bookmark['note']): ?>
                                <div class="bookmark-note"><?= htmlspecialchars($bookmark['note']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; color: #6c757d; padding: 20px;">
                        暂无书签
                    </div>
                <?php endif; ?>
            </div>
            <div class="add-bookmark-form">
                <input type="text" id="bookmark-note" placeholder="添加书签备注...">
                <button onclick="addCurrentBookmark()">添加</button>
            </div>
        </div>

        <!-- AI仿写工具 -->
        <div class="toolbar-section">
            <div class="toolbar-title">✨ AI仿写</div>
            <div class="ai-panel">
                <textarea id="ai-instructions" placeholder="请输入您的仿写或润色指令，例如：请用更幽默的风格重写，请扩写这段战斗描写..."></textarea>
                <button onclick="aiRewrite()">开始仿写</button>
                <div id="ai-result"></div>
                <div id="ai-loading" class="loading" style="display: none;">正在生成中，请稍候...</div>
            </div>
        </div>
    </div>

    <script>
        let toolbarVisible = true;
        
        function toggleToolbar() {
            const toolbar = document.getElementById('reader-toolbar');
            toolbarVisible = !toolbarVisible;
            toolbar.style.display = toolbarVisible ? 'block' : 'none';
        }

        function scrollToBookmark(position) {
            window.scrollTo(0, position);
        }

        function addCurrentBookmark() {
            const note = document.getElementById('bookmark-note').value;
            const scrollPosition = window.pageYOffset;
            
            fetch('/bookstore/api/add-bookmark', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    book_id: <?= $chapter['book_id'] ?>,
                    chapter_id: <?= $chapter['id'] ?>,
                    position: scrollPosition,
                    note: note
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('书签添加成功！');
                    document.getElementById('bookmark-note').value = '';
                    location.reload();
                } else {
                    alert('添加失败：' + data.message);
                }
            })
            .catch(error => {
                alert('添加失败：' + error.message);
            });
        }

        function aiRewrite() {
            const instructions = document.getElementById('ai-instructions').value;
            const resultDiv = document.getElementById('ai-result');
            const loadingDiv = document.getElementById('ai-loading');
            
            if (!instructions) {
                alert('请输入仿写指令！');
                return;
            }

            // 显示加载状态
            loadingDiv.style.display = 'block';
            resultDiv.style.display = 'none';

            fetch('/api/v1/bookstore/ai/rewrite', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    chapter_id: <?= $chapter['id'] ?>,
                    instructions: instructions,
                    model: 'gpt-4o-mini'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.textContent = data.rewritten_content;
                    resultDiv.style.display = 'block';
                } else {
                    resultDiv.textContent = '发生错误：' + (data.error || '未知错误');
                    resultDiv.style.display = 'block';
                }
            })
            .catch(error => {
                resultDiv.textContent = '请求失败：' + error;
                resultDiv.style.display = 'block';
            })
            .finally(() => {
                loadingDiv.style.display = 'none';
            });
        }

        // 自动保存阅读进度
        let lastScrollPosition = 0;
        window.addEventListener('scroll', function() {
            const scrollPosition = window.pageYOffset;
            const documentHeight = document.documentElement.scrollHeight - window.innerHeight;
            const progress = (scrollPosition / documentHeight) * 100;
            
            // 每10秒保存一次进度
            if (Math.abs(scrollPosition - lastScrollPosition) > 100) {
                lastScrollPosition = scrollPosition;
                updateReadingProgress(progress);
            }
        });

        function updateReadingProgress(progress) {
            fetch('/bookstore/api/update-progress', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    book_id: <?= $chapter['book_id'] ?>,
                    chapter_id: <?= $chapter['id'] ?>,
                    progress: progress
                })
            })
            .catch(error => {
                console.error('进度保存失败:', error);
            });
        }

        // 键盘快捷键
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'b':
                        e.preventDefault();
                        document.getElementById('bookmark-note').focus();
                        break;
                    case 'r':
                        e.preventDefault();
                        aiRewrite();
                        break;
                    case 't':
                        e.preventDefault();
                        toggleToolbar();
                        break;
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>