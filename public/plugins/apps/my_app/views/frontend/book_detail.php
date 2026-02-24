<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($book['title']) ?> - 在线书城</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            display: flex;
            gap: 30px;
            padding: 30px;
            border-bottom: 1px solid #dee2e6;
        }
        .book-cover {
            width: 200px;
            height: 280px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .book-info {
            flex: 1;
        }
        .book-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #212529;
        }
        .book-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #6c757d;
        }
        .book-description {
            color: #495057;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .book-stats {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            color: #6c757d;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
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
        .content-section {
            display: flex;
            gap: 30px;
            padding: 0 30px 30px;
        }
        .section {
            flex: 1;
        }
        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #212529;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }
        .chapter-list {
            list-style: none;
            padding: 0;
        }
        .chapter-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f3f4;
            transition: background 0.2s;
        }
        .chapter-item:hover {
            background: #f8f9fa;
        }
        .chapter-item a {
            text-decoration: none;
            color: #495057;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chapter-number {
            font-size: 12px;
            color: #6c757d;
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 12px;
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
        .bookmark-list {
            list-style: none;
            padding: 0;
        }
        .bookmark-item {
            padding: 10px;
            border-bottom: 1px solid #f1f3f4;
            font-size: 14px;
        }
        .bookmark-item a {
            color: #007bff;
            text-decoration: none;
        }
        .similar-books {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }
        .similar-book {
            text-align: center;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            transition: transform 0.2s;
        }
        .similar-book:hover {
            transform: translateY(-3px);
        }
        .similar-book-cover {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        .similar-book-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #212529;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .similar-book-author {
            font-size: 12px;
            color: #6c757d;
        }
        .reading-progress-chapter {
            color: #007bff;
            font-weight: bold;
        }
        .add-bookmark-btn {
            background: none;
            border: 1px solid #007bff;
            color: #007bff;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
        }
        .add-bookmark-btn:hover {
            background: #007bff;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="<?= htmlspecialchars($book['cover_image'] ?: '/static/frontend/images/default-book-cover.jpg') ?>" alt="<?= htmlspecialchars($book['title']) ?>" class="book-cover">
            <div class="book-info">
                <h1 class="book-title"><?= htmlspecialchars($book['title']) ?></h1>
                <div class="book-meta">
                    <span>作者：<?= htmlspecialchars($book['author']) ?></span>
                    <span>分类：<?= htmlspecialchars($book['category'] ?: '未分类') ?></span>
                    <span>状态：<?= htmlspecialchars($book['status']) ?></span>
                </div>
                <div class="book-description"><?= nl2br(htmlspecialchars($book['description'])) ?></div>
                <div class="book-stats">
                    <div class="stat-item">📖 阅读 <?= number_format($book['views']) ?></div>
                    <div class="stat-item">❤️ 喜欢 <?= number_format($book['likes']) ?></div>
                </div>
                <div class="action-buttons">
                    <?php if ($userId): ?>
                        <?php if ($readingProgress && $readingProgress['chapter_id']): ?>
                            <a href="/bookstore/read/<?= $book['id'] ?>/<?= $readingProgress['chapter_id'] ?>" class="btn btn-primary">
                                📖 继续阅读
                            </a>
                        <?php else: ?>
                            <a href="/bookstore/read/<?= $book['id'] ?>/<?= $chapters[0]['id'] ?? '' ?>" class="btn btn-primary">
                                📖 开始阅读
                            </a>
                        <?php endif; ?>
                        <a href="/bookstore/library" class="btn btn-secondary">📚 我的图书馆</a>
                    <?php else: ?>
                        <a href="/bookstore/read/<?= $book['id'] ?>/<?= $chapters[0]['id'] ?? '' ?>" class="btn btn-primary">
                            📖 开始阅读
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="content-section">
            <!-- 章节列表 -->
            <div class="section">
                <h2 class="section-title">章节列表</h2>
                <?php if ($readingProgress): ?>
                    <div class="progress-info">
                        阅读进度：<?= number_format($readingProgress['progress'], 1) ?>%
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $readingProgress['progress'] ?>%"></div>
                        </div>
                        <?php if ($readingProgress['chapter_title']): ?>
                            <div>当前章节：<span class="reading-progress-chapter"><?= htmlspecialchars($readingProgress['chapter_title']) ?></span></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($chapters)): ?>
                    <ul class="chapter-list">
                        <?php foreach ($chapters as $index => $chapter): ?>
                            <li class="chapter-item">
                                <a href="/bookstore/read/<?= $book['id'] ?>/<?= $chapter['id'] ?>">
                                    <span><?= htmlspecialchars($chapter['title']) ?></span>
                                    <span class="chapter-number">第 <?= $index + 1 ?> 章</span>
                                    <?php if ($userId): ?>
                                        <button class="add-bookmark-btn" onclick="addBookmark(<?= $chapter['id'] ?>)">🔖</button>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>暂无章节。</p>
                <?php endif; ?>
            </div>

            <!-- 书签列表 -->
            <?php if ($userId && !empty($bookmarks)): ?>
                <div class="section">
                    <h2 class="section-title">我的书签</h2>
                    <ul class="bookmark-list">
                        <?php foreach ($bookmarks as $bookmark): ?>
                            <li class="bookmark-item">
                                <a href="/bookstore/read/<?= $book['id'] ?>/<?= $bookmark['chapter_id'] ?>#position-<?= $bookmark['position'] ?? 0 ?>">
                                    <?= htmlspecialchars($bookmark['chapter_title']) ?>
                                    <?php if ($bookmark['note']): ?>
                                        - <?= htmlspecialchars($bookmark['note']) ?>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- 相似书籍推荐 -->
            <?php if (!empty($similarBooks)): ?>
                <div class="section">
                    <h2 class="section-title">相似推荐</h2>
                    <div class="similar-books">
                        <?php foreach ($similarBooks as $similarBook): ?>
                            <div class="similar-book">
                                <a href="/bookstore/book/<?= $similarBook['id'] ?>">
                                    <?php if ($similarBook['cover_image']): ?>
                                        <img src="<?= htmlspecialchars($similarBook['cover_image']) ?>" alt="<?= htmlspecialchars($similarBook['title']) ?>" class="similar-book-cover">
                                    <?php else: ?>
                                        <div class="similar-book-cover" style="background: #e9ecef; display: flex; align-items: center; justify-content: center; color: #6c757d; height: 120px;">
                                            无封面
                                        </div>
                                    <?php endif; ?>
                                    <div class="similar-book-title"><?= htmlspecialchars($similarBook['title']) ?></div>
                                    <div class="similar-book-author"><?= htmlspecialchars($similarBook['author']) ?></div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($userId): ?>
    <script>
        function addBookmark(chapterId) {
            const chapterTitle = prompt('请输入书签备注（可选）：');
            const note = chapterTitle || '';
            
            fetch('/bookstore/api/add-bookmark', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    book_id: <?= $book['id'] ?>,
                    chapter_id: chapterId,
                    position: 0,
                    note: note
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('书签添加成功！');
                    location.reload();
                } else {
                    alert('添加失败：' + data.message);
                }
            })
            .catch(error => {
                alert('添加失败：' + error.message);
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
                    book_id: <?= $book['id'] ?>,
                    chapter_id: <?= $chapters[0]['id'] ?? 0 ?>,
                    progress: progress
                })
            })
            .catch(error => {
                console.error('进度保存失败:', error);
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>