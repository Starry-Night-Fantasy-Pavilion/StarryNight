<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$currentType = $currentType ?? 'all';
$searchTerm = $searchTerm ?? '';
$sortBy = $sortBy ?? 'created_at';
$sortOrder = $sortOrder ?? 'desc';

// 构造保留筛选条件的查询字符串（用于分页链接）
$baseQuery = [
    'type' => $currentType !== 'all' ? $currentType : null,
    'search' => $searchTerm ?: null,
    'sort_by' => $sortBy !== 'created_at' ? $sortBy : null,
    'sort_order' => $sortOrder !== 'desc' ? $sortOrder : null,
];
?>
<div class="page-share">
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">资源分享平台</h1>
            <p class="page-subtitle">分享和发现优质创作资源（知识库 / 提示词 / 模板 / 智能体）</p>
        </div>
    </div>

    <div class="container">
        <div class="share-toolbar">
            <div class="filter-tabs">
                <a href="/share" class="filter-tab <?= $currentType === 'all' ? 'active' : '' ?>">全部</a>
                <?php foreach ($resourceTypes ?? [] as $key => $label): ?>
                    <?php if ($key !== 'all'): ?>
                        <a href="/share?type=<?= $h($key) ?>" class="filter-tab <?= $currentType === $key ? 'active' : '' ?>">
                            <?= $h($label) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <form class="share-search" method="get" action="/share">
                <input type="hidden" name="type" value="<?= $h($currentType) ?>">
                <input
                    type="text"
                    name="search"
                    value="<?= $h($searchTerm) ?>"
                    placeholder="搜索标题或描述…"
                >
                <select name="sort_by">
                    <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>最新发布</option>
                    <option value="price" <?= $sortBy === 'price' ? 'selected' : '' ?>>价格</option>
                </select>
                <select name="sort_order">
                    <option value="desc" <?= $sortOrder === 'desc' ? 'selected' : '' ?>>从高到低</option>
                    <option value="asc" <?= $sortOrder === 'asc' ? 'selected' : '' ?>>从低到高</option>
                </select>
                <button class="btn btn-primary btn-sm" type="submit">搜索</button>
            </form>
        </div>

        <div class="resources-grid">
            <?php if (empty($resources ?? [])): ?>
                <div class="empty-state">
                    <p>暂无资源</p>
                </div>
            <?php else: ?>
                <?php foreach ($resources as $resource): ?>
                    <div class="resource-card">
                        <div class="resource-card-header">
                            <span class="resource-type-pill">
                                <?= $h($resourceTypes[$resource['resource_type']] ?? $resource['resource_type'] ?? '') ?>
                            </span>
                            <?php if (!empty($resource['price']) && (int)$resource['price'] > 0): ?>
                                <span class="resource-price-tag"><?= (int)$resource['price'] ?> 星夜币</span>
                            <?php else: ?>
                                <span class="resource-price-tag resource-price-free-tag">免费</span>
                            <?php endif; ?>
                        </div>

                        <h3 class="resource-title">
                            <a href="/share/<?= $h($resource['id']) ?>"><?= $h($resource['title']) ?></a>
                        </h3>

                        <p class="resource-description">
                            <?= $h(mb_substr($resource['description'] ?? '', 0, 100)) ?>
                            <?= mb_strlen($resource['description'] ?? '') > 100 ? '…' : '' ?>
                        </p>

                        <div class="resource-meta-row">
                            <span>作者：<?= $h($resource['user_nickname'] ?? $resource['username'] ?? '匿名') ?></span>
                            <span>浏览：<?= (int)($resource['view_count'] ?? 0) ?></span>
                        </div>

                        <div class="resource-meta-row">
                            <?php
                            $avgRating = (float)($resource['avg_rating'] ?? 0);
                            $totalRatings = (int)($resource['total_ratings'] ?? 0);
                            ?>
                            <span>评分：<?= $avgRating ?> / 5（<?= $totalRatings ?>）</span>
                            <span>收藏：<?= (int)($resource['favorite_count'] ?? 0) ?></span>
                        </div>

                        <div class="resource-footer">
                            <a href="/share/<?= $h($resource['id']) ?>" class="btn btn-primary btn-sm">查看详情</a>
                        </div>
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
                    <a href="/share?<?= http_build_query($prevQuery) ?>" class="pagination-link">上一页</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <?php if ($i == $pagination['page']): ?>
                        <span class="pagination-link active"><?= $i ?></span>
                    <?php elseif ($i == 1 || $i == $pagination['totalPages'] || abs($i - $pagination['page']) <= 2): ?>
                        <?php
                        $pageQuery = array_filter($baseQuery, fn($v) => $v !== null);
                        $pageQuery['page'] = $i;
                        ?>
                        <a href="/share?<?= http_build_query($pageQuery) ?>" class="pagination-link"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                    <?php
                    $nextQuery = array_filter($baseQuery, fn($v) => $v !== null);
                    $nextQuery['page'] = $pagination['page'] + 1;
                    ?>
                    <a href="/share?<?= http_build_query($nextQuery) ?>" class="pagination-link">下一页</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
