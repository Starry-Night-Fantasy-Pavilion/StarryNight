<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$searchTerm = $searchTerm ?? '';

$baseQuery = [
    'search' => $searchTerm ?: null,
];
?>
<div class="page-share page-share-templates">
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">模板分享</h1>
            <p class="page-subtitle">共享小说 / 动漫 / 音乐等创作模板，一键套用</p>
        </div>
    </div>

    <div class="container">
        <form class="share-search" method="get" action="/share/templates">
            <input
                type="text"
                name="search"
                value="<?= $h($searchTerm) ?>"
                placeholder="搜索模板标题或描述…"
            >
            <button class="btn btn-primary btn-sm" type="submit">搜索</button>
        </form>

        <div class="resources-grid">
            <?php if (empty($templates ?? [])): ?>
                <div class="empty-state">
                    <p>暂无模板</p>
                </div>
            <?php else: ?>
                <?php foreach ($templates as $template): ?>
                    <div class="resource-card">
                        <h3 class="resource-title">
                            <a href="/templates/<?= $h($template['id']) ?>"><?= $h($template['title']) ?></a>
                        </h3>
                        <p class="resource-description">
                            <?= $h(mb_substr($template['description'] ?? '', 0, 100)) ?>
                            <?= mb_strlen($template['description'] ?? '') > 100 ? '…' : '' ?>
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
                    <a href="/share/templates?<?= http_build_query($prevQuery) ?>" class="pagination-link">上一页</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <?php if ($i == $pagination['page']): ?>
                        <span class="pagination-link active"><?= $i ?></span>
                    <?php elseif ($i == 1 || $i == $pagination['totalPages'] || abs($i - $pagination['page']) <= 2): ?>
                        <?php
                        $pageQuery = array_filter($baseQuery, fn($v) => $v !== null);
                        $pageQuery['page'] = $i;
                        ?>
                        <a href="/share/templates?<?= http_build_query($pageQuery) ?>" class="pagination-link"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                    <?php
                    $nextQuery = array_filter($baseQuery, fn($v) => $v !== null);
                    $nextQuery['page'] = $pagination['page'] + 1;
                    ?>
                    <a href="/share/templates?<?= http_build_query($nextQuery) ?>" class="pagination-link">下一页</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
