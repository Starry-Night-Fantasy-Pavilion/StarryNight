<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-share">
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">资源分享平台</h1>
            <p class="page-subtitle">分享和发现优质创作资源</p>
        </div>
    </div>

    <div class="container">
        <div class="share-filters">
            <div class="filter-tabs">
                <a href="/share" class="filter-tab <?= ($currentType ?? 'all') === 'all' ? 'active' : '' ?>">全部</a>
                <?php foreach ($resourceTypes ?? [] as $key => $label): ?>
                    <?php if ($key !== 'all'): ?>
                        <a href="/share?type=<?= $h($key) ?>" class="filter-tab <?= ($currentType ?? '') === $key ? 'active' : '' ?>">
                            <?= $h($label) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="resources-grid">
            <?php if (empty($resources ?? [])): ?>
                <div class="empty-state">
                    <p>暂无资源</p>
                </div>
            <?php else: ?>
                <?php foreach ($resources as $resource): ?>
                    <div class="resource-card">
                        <h3 class="resource-title">
                            <a href="/share/<?= $h($resource['id']) ?>"><?= $h($resource['title']) ?></a>
                        </h3>
                        <p class="resource-description"><?= $h(mb_substr($resource['description'] ?? '', 0, 100)) ?><?= mb_strlen($resource['description'] ?? '') > 100 ? '...' : '' ?></p>
                        <div class="resource-footer">
                            <span class="resource-type"><?= $h($resourceTypes[$resource['resource_type']] ?? $resource['resource_type']) ?></span>
                            <a href="/share/<?= $h($resource['id']) ?>" class="btn btn-primary btn-sm">查看详情</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (isset($pagination) && $pagination['totalPages'] > 1): ?>
            <div class="pagination">
                <?php if ($pagination['page'] > 1): ?>
                    <a href="/share?page=<?= $pagination['page'] - 1 ?>" class="pagination-link">上一页</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <?php if ($i == $pagination['page']): ?>
                        <span class="pagination-link active"><?= $i ?></span>
                    <?php elseif ($i == 1 || $i == $pagination['totalPages'] || abs($i - $pagination['page']) <= 2): ?>
                        <a href="/share?page=<?= $i ?>" class="pagination-link"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                    <a href="/share?page=<?= $pagination['page'] + 1 ?>" class="pagination-link">下一页</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
