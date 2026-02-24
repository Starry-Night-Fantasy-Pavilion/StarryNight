<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>在线书城</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .nav {
            display: flex;
            gap: 20px;
        }
        .nav a {
            text-decoration: none;
            color: #495057;
            font-weight: 500;
            transition: color 0.2s;
        }
        .nav a:hover {
            color: #007bff;
        }
        .nav a.active {
            color: #007bff;
            border-bottom: 2px solid #007bff;
        }
        .section {
            margin-bottom: 40px;
        }
        .section-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #212529;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section-link {
            font-size: 14px;
            color: #007bff;
            text-decoration: none;
        }
        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .book-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .book-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .book-cover {
            width: 100%;
            height: 280px;
            object-fit: cover;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        .book-info {
            padding: 15px;
        }
        .book-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #212529;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .book-author {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
        }
        .book-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #6c757d;
        }
        .book-views, .book-likes {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        .pagination a, .pagination span {
            padding: 8px 16px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-decoration: none;
            color: #495057;
        }
        .pagination a:hover {
            background: #e9ecef;
        }
        .pagination span {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        .recommendation-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .book-item {
            position: relative;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">在线书城</div>
            <nav class="nav">
                <a href="/bookstore" class="active">首页</a>
                <a href="/bookstore/library">我的图书馆</a>
            </nav>
        </header>

        <!-- 推荐书籍 -->
        <?php if (!empty($recommendations)): ?>
            <section class="section">
                <div class="section-title">
                    为您推荐
                    <a href="/bookstore/library" class="section-link">查看更多 →</a>
                </div>
                <div class="book-grid">
                    <?php foreach ($recommendations as $rec): ?>
                        <div class="book-item">
                            <div class="recommendation-badge">推荐</div>
                            <?php if ($rec['cover_image']): ?>
                                <img src="<?= htmlspecialchars($rec['cover_image']) ?>" alt="<?= htmlspecialchars($rec['title']) ?>" class="book-cover">
                            <?php else: ?>
                                <div class="book-cover">无封面</div>
                            <?php endif; ?>
                            <div class="book-info">
                                <div class="book-title"><?= htmlspecialchars($rec['title']) ?></div>
                                <div class="book-author"><?= htmlspecialchars($rec['author']) ?></div>
                                <div class="book-meta">
                                    <div class="book-views">📖 <?= number_format($rec['views'] ?? 0) ?></div>
                                    <div class="book-likes">❤️ <?= number_format($rec['likes'] ?? 0) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- 热门书籍 -->
        <?php if (!empty($popularBooks)): ?>
            <section class="section">
                <div class="section-title">
                    热门书籍
                </div>
                <div class="book-grid">
                    <?php foreach ($popularBooks as $book): ?>
                        <div class="book-item">
                            <?php if ($book['cover_image']): ?>
                                <img src="<?= htmlspecialchars($book['cover_image']) ?>" alt="<?= htmlspecialchars($book['title']) ?>" class="book-cover">
                            <?php else: ?>
                                <div class="book-cover">无封面</div>
                            <?php endif; ?>
                            <div class="book-info">
                                <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
                                <div class="book-author"><?= htmlspecialchars($book['author']) ?></div>
                                <div class="book-meta">
                                    <div class="book-views">📖 <?= number_format($book['views']) ?></div>
                                    <div class="book-likes">❤️ <?= number_format($book['likes']) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- 最新书籍 -->
        <section class="section">
            <div class="section-title">
                最新作品
            </div>
            <?php if (!empty($books)): ?>
                <div class="book-grid">
                    <?php foreach ($books as $book): ?>
                        <div class="book-item">
                            <a href="/bookstore/book/<?= htmlspecialchars($book['id']) ?>" style="text-decoration: none; color: inherit;">
                                <?php if ($book['cover_image']): ?>
                                    <img src="<?= htmlspecialchars($book['cover_image']) ?>" alt="<?= htmlspecialchars($book['title']) ?>" class="book-cover">
                                <?php else: ?>
                                    <div class="book-cover">无封面</div>
                                <?php endif; ?>
                                <div class="book-info">
                                    <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
                                    <div class="book-author"><?= htmlspecialchars($book['author']) ?></div>
                                    <div class="book-meta">
                                        <div class="book-views">📖 <?= number_format($book['views']) ?></div>
                                        <div class="book-likes">❤️ <?= number_format($book['likes']) ?></div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    暂无作品。
                </div>
            <?php endif; ?>
        </section>

        <!-- 分页 -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="/bookstore?page=<?= $currentPage - 1 ?>">上一页</a>
                <?php endif; ?>

                <span>第 <?= $currentPage ?> 页 / 共 <?= $totalPages ?> 页</span>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="/bookstore?page=<?= $currentPage + 1 ?>">下一页</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>