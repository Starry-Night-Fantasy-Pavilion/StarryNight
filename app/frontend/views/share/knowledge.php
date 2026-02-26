<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$searchTerm = $searchTerm ?? '';

$baseQuery = [
    'search' => $searchTerm ?: null,
];
?>
<div class="page-share page-share-knowledge">
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">知识库分享</h1>
            <p class="page-subtitle">浏览和导入公开知识库，用于自己的创作项目</p>
        </div>
    </div>

    <div class="container">
        <form class="share-search" method="get" action="/share/knowledge">
            <input
                type="text"
                name="search"
                value="<?= $h($searchTerm) ?>"
                placeholder="搜索知识库标题或描述…"
            >
            <button class="btn btn-primary btn-sm" type="submit">搜索</button>
        </form>

        <div class="resources-grid">
            <?php if (empty($knowledgeBases ?? [])): ?>
                <div class="empty-state">
                    <p>暂无知识库</p>
                </div>
            <?php else: ?>
                <?php foreach ($knowledgeBases as $kb): ?>
                    <div class="resource-card">
                        <h3 class="resource-title">
                            <a href="/knowledge/view/<?= $h($kb['id']) ?>"><?= $h($kb['title']) ?></a>
                        </h3>
                        <p class="resource-description">
                            <?= $h(mb_substr($kb['description'] ?? '', 0, 100)) ?>
                            <?= mb_strlen($kb['description'] ?? '') > 100 ? '…' : '' ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (isset($pagination) && $pagination['totalPages'] > 1): ?>
            <div class="pagination">
                <?php if ($pagination['page'] > 1): ?>
                    <?php
                    $prevQuery = array_filter($baseQuery, fn($v) => $v !== null);
                    $prevQuery['page'] = $pagination['page'] - 1;
                    ?>
                    <a href="/share/knowledge?<?= http_build_query($prevQuery) ?>" class="pagination-link">上一页</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <?php if ($i == $pagination['page']): ?>
                        <span class="pagination-link active"><?= $i ?></span>
                    <?php elseif ($i == 1 || $i == $pagination['totalPages'] || abs($i - $pagination['page']) <= 2): ?>
                        <?php
                        $pageQuery = array_filter($baseQuery, fn($v) => $v !== null);
                        $pageQuery['page'] = $i;
                        ?>
                        <a href="/share/knowledge?<?= http_build_query($pageQuery) ?>" class="pagination-link"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                    <?php
                    $nextQuery = array_filter($baseQuery, fn($v) => $v !== null);
                    $nextQuery['page'] = $pagination['page'] + 1;
                    ?>
                    <a href="/share/knowledge?<?= http_build_query($nextQuery) ?>" class="pagination-link">下一页</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
