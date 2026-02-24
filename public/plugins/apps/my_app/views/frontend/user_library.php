<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的图书馆 - 在线书城</title>
    <link rel="stylesheet" href="/static/frontend/css/style.css">
    <style>
        .library-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .section {
            margin-bottom: 40px;
        }
        .section-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .book-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .book-item:hover {
            transform: translateY(-5px);
        }
        .book-cover {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .book-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .book-author {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        .progress-fill {
            height: 100%;
            background: #007bff;
            transition: width 0.3s;
        }
        .progress-text {
            font-size: 12px;
            color: #666;
        }
        .bookmark-list {
            list-style: none;
            padding: 0;
        }
        .bookmark-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
            background: #f9f9f9;
        }
        .bookmark-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .bookmark-chapter {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .bookmark-note {
            color: #333;
            font-style: italic;
            margin-bottom: 10px;
        }
        .bookmark-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        .tab.active {
            border-bottom-color: #007bff;
            font-weight: bold;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="library-container">
        <h1>我的图书馆</h1>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab('reading')">阅读进度</div>
            <div class="tab" onclick="showTab('bookmarks')">我的书签</div>
            <div class="tab" onclick="showTab('recommendations')">推荐书籍</div>
        </div>

        <!-- 阅读进度 -->
        <div id="reading" class="tab-content active">
            <div class="section">
                <h2 class="section-title">正在阅读</h2>
                <?php if (!empty($readingProgress)): ?>
                    <div class="book-grid">
                        <?php foreach ($readingProgress as $progress): ?>
                            <div class="book-item">
                                <?php if ($progress['cover_image']): ?>
                                    <img src="<?= $progress['cover_image'] ?>" alt="<?= $progress['book_title'] ?>" class="book-cover">
                                <?php else: ?>
                                    <div class="book-cover" style="background: #ddd; display: flex; align-items: center; justify-content: center; color: #666;">
                                        无封面
                                    </div>
                                <?php endif; ?>
                                <div class="book-title"><?= htmlspecialchars($progress['book_title']) ?></div>
                                <div class="book-author"><?= htmlspecialchars($progress['author']) ?></div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $progress['progress'] ?>%"></div>
                                </div>
                                <div class="progress-text">已读 <?= number_format($progress['progress'], 1) ?>%</div>
                                <div style="margin-top: 10px;">
                                    <a href="/bookstore/read/<?= $progress['book_id'] ?>/<?= $progress['chapter_id'] ?>" class="btn btn-primary">继续阅读</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>暂无阅读记录</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 我的书签 -->
        <div id="bookmarks" class="tab-content">
            <div class="section">
                <h2 class="section-title">我的书签</h2>
                <?php if (!empty($bookmarks)): ?>
                    <div class="bookmark-list">
                        <?php foreach ($bookmarks as $bookmark): ?>
                            <div class="bookmark-item">
                                <div class="bookmark-title"><?= htmlspecialchars($bookmark['book_title']) ?></div>
                                <div class="bookmark-chapter">章节：<?= htmlspecialchars($bookmark['chapter_title']) ?></div>
                                <?php if ($bookmark['note']): ?>
                                    <div class="bookmark-note">备注：<?= htmlspecialchars($bookmark['note']) ?></div>
                                <?php endif; ?>
                                <div class="bookmark-actions">
                                    <a href="/bookstore/read/<?= $bookmark['book_id'] ?>/<?= $bookmark['chapter_id'] ?>" class="btn btn-primary">继续阅读</a>
                                    <button onclick="deleteBookmark(<?= $bookmark['id'] ?>)" class="btn btn-danger">删除</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>暂无书签</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 推荐书籍 -->
        <div id="recommendations" class="tab-content">
            <div class="section">
                <h2 class="section-title">为您推荐</h2>
                <?php if (!empty($recommendations)): ?>
                    <div class="book-grid">
                        <?php foreach ($recommendations as $rec): ?>
                            <div class="book-item">
                                <?php if ($rec['cover_image']): ?>
                                    <img src="<?= $rec['cover_image'] ?>" alt="<?= $rec['title'] ?>" class="book-cover">
                                <?php else: ?>
                                    <div class="book-cover" style="background: #ddd; display: flex; align-items: center; justify-content: center; color: #666;">
                                        无封面
                                    </div>
                                <?php endif; ?>
                                <div class="book-title"><?= htmlspecialchars($rec['title']) ?></div>
                                <div class="book-author"><?= htmlspecialchars($rec['author']) ?></div>
                                <div style="margin-top: 10px;">
                                    <a href="/bookstore/book/<?= $rec['book_id'] ?>" class="btn btn-primary">查看详情</a>
                                </div>
                                <?php if ($rec['reason']): ?>
                                    <div style="margin-top: 5px; font-size: 12px; color: #666;">
                                        推荐理由：<?= htmlspecialchars($rec['reason']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>暂无推荐书籍</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // 隐藏所有标签内容
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // 移除所有标签的激活状态
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // 显示选中的标签内容
            document.getElementById(tabName).classList.add('active');
            
            // 激活选中的标签
            event.target.classList.add('active');
        }

        function deleteBookmark(bookmarkId) {
            if (confirm('确定要删除这个书签吗？')) {
                fetch('/bookstore/api/delete-bookmark', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        bookmark_id: bookmarkId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('删除失败：' + data.message);
                    }
                })
                .catch(error => {
                    alert('删除失败：' + error.message);
                });
            }
        }
    </script>
</body>
</html>