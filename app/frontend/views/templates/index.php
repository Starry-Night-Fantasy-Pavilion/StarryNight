<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-templates">
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">模板库</h1>
            <p class="page-subtitle">丰富的创作模板，提升创作效率</p>
        </div>
    </div>

    <div class="container">
        <!-- 搜索和筛选 -->
        <div class="templates-filters">
            <div class="search-box">
                <form method="GET" action="/templates" class="search-form">
                    <input type="text" name="search" value="<?= $h($searchTerm ?? '') ?>" placeholder="搜索模板...">
                    <button type="submit" class="btn btn-primary btn-sm">搜索</button>
                </form>
            </div>
            
            <div class="filter-tabs">
                <a href="/templates" class="filter-tab <?= !isset($currentCategory) ? 'active' : '' ?>">全部</a>
                <?php foreach ($categories ?? [] as $key => $label): ?>
                    <a href="/templates?category=<?= $h($key) ?>" class="filter-tab <?= ($currentCategory ?? '') === $key ? 'active' : '' ?>">
                        <?= $h($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="filter-options">
                <select name="type" class="form-control" onchange="window.location.href=this.value ? '/templates?type=' + this.value : '/templates'">
                    <option value="">全部类型</option>
                    <?php foreach ($types ?? [] as $key => $label): ?>
                        <option value="<?= $h($key) ?>" <?= ($currentType ?? '') === $key ? 'selected' : '' ?>>
                            <?= $h($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- 模板列表 -->
        <div class="templates-grid">
            <?php if (empty($templates ?? [])): ?>
                <div class="empty-state">
                    <p>暂无模板</p>
                </div>
            <?php else: ?>
                <?php foreach ($templates as $template): ?>
                    <div class="template-card">
                        <div class="template-header">
                            <div class="template-category"><?= $h($categories[$template['category']] ?? $template['category']) ?></div>
                            <div class="template-stats">
                                <span><i class="fas fa-eye"></i> <?= number_format($template['view_count'] ?? 0) ?></span>
                                <span><i class="fas fa-download"></i> <?= number_format($template['usage_count'] ?? 0) ?></span>
                            </div>
                        </div>
                        <h3 class="template-title">
                            <a href="/templates/<?= $h($template['id']) ?>"><?= $h($template['title']) ?></a>
                        </h3>
                        <p class="template-description"><?= $h(mb_substr($template['description'] ?? '', 0, 100)) ?><?= mb_strlen($template['description'] ?? '') > 100 ? '...' : '' ?></p>
                        <?php if (!empty($template['tags'])): ?>
                            <div class="template-tags">
                                <?php 
                                $tags = explode(',', $template['tags']);
                                foreach (array_slice($tags, 0, 3) as $tag): 
                                ?>
                                    <span class="tag"><?= $h(trim($tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="template-footer">
                            <div class="template-author">
                                <span>作者: <?= $h($template['user_nickname'] ?? $template['username'] ?? '匿名') ?></span>
                            </div>
                            <div class="template-actions">
                                <a href="/templates/<?= $h($template['id']) ?>" class="btn btn-primary btn-sm">查看详情</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 分页 -->
        <?php if (isset($pagination) && $pagination['totalPages'] > 1): ?>
            <div class="pagination">
                <?php if ($pagination['page'] > 1): ?>
                    <a href="/templates?page=<?= $pagination['page'] - 1 ?><?= isset($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?><?= isset($currentCategory) ? '&category=' . urlencode($currentCategory) : '' ?>" class="pagination-link">上一页</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <?php if ($i == $pagination['page']): ?>
                        <span class="pagination-link active"><?= $i ?></span>
                    <?php elseif ($i == 1 || $i == $pagination['totalPages'] || abs($i - $pagination['page']) <= 2): ?>
                        <a href="/templates?page=<?= $i ?><?= isset($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?><?= isset($currentCategory) ? '&category=' . urlencode($currentCategory) : '' ?>" class="pagination-link"><?= $i ?></a>
                    <?php elseif (abs($i - $pagination['page']) == 3): ?>
                        <span class="pagination-ellipsis">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                    <a href="/templates?page=<?= $pagination['page'] + 1 ?><?= isset($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?><?= isset($currentCategory) ? '&category=' . urlencode($currentCategory) : '' ?>" class="pagination-link">下一页</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- 创建模板按钮 -->
        <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']): ?>
            <div class="create-template-section">
                <a href="/templates/create" class="btn btn-primary">创建模板</a>
            </div>
        <?php endif; ?>
    </div>
</div>
