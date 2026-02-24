<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-agents">
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">智能体</h1>
            <p class="page-subtitle">AI智能体助手，提升创作效率</p>
        </div>
    </div>

    <div class="container">
        <div class="agents-filters">
            <div class="search-box">
                <form method="GET" action="/agents" class="search-form">
                    <input type="text" name="search" value="<?= $h($searchTerm ?? '') ?>" placeholder="搜索智能体...">
                    <button type="submit" class="btn btn-primary btn-sm">搜索</button>
                </form>
            </div>
            
            <div class="filter-tabs">
                <a href="/agents" class="filter-tab <?= !isset($currentCategory) ? 'active' : '' ?>">全部</a>
                <?php foreach ($categories ?? [] as $key => $label): ?>
                    <a href="/agents?category=<?= $h($key) ?>" class="filter-tab <?= ($currentCategory ?? '') === $key ? 'active' : '' ?>">
                        <?= $h($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="agents-grid">
            <?php if (empty($agents ?? [])): ?>
                <div class="empty-state">
                    <p>暂无智能体</p>
                </div>
            <?php else: ?>
                <?php foreach ($agents as $agent): ?>
                    <div class="agent-card">
                        <div class="agent-header">
                            <div class="agent-category"><?= $h($categories[$agent['category']] ?? $agent['category']) ?></div>
                            <div class="agent-stats">
                                <span><i class="fas fa-eye"></i> <?= number_format($agent['view_count'] ?? 0) ?></span>
                                <span><i class="fas fa-download"></i> <?= number_format($agent['usage_count'] ?? 0) ?></span>
                            </div>
                        </div>
                        <h3 class="agent-title">
                            <a href="/agents/<?= $h($agent['id']) ?>"><?= $h($agent['name']) ?></a>
                        </h3>
                        <p class="agent-description"><?= $h(mb_substr($agent['description'] ?? '', 0, 100)) ?><?= mb_strlen($agent['description'] ?? '') > 100 ? '...' : '' ?></p>
                        <div class="agent-footer">
                            <div class="agent-author">
                                <span>作者: <?= $h($agent['user_nickname'] ?? $agent['username'] ?? '匿名') ?></span>
                            </div>
                            <div class="agent-actions">
                                <a href="/agents/<?= $h($agent['id']) ?>" class="btn btn-primary btn-sm">查看详情</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (isset($pagination) && $pagination['totalPages'] > 1): ?>
            <div class="pagination">
                <?php if ($pagination['page'] > 1): ?>
                    <a href="/agents?page=<?= $pagination['page'] - 1 ?><?= isset($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" class="pagination-link">上一页</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <?php if ($i == $pagination['page']): ?>
                        <span class="pagination-link active"><?= $i ?></span>
                    <?php elseif ($i == 1 || $i == $pagination['totalPages'] || abs($i - $pagination['page']) <= 2): ?>
                        <a href="/agents?page=<?= $i ?><?= isset($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" class="pagination-link"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                    <a href="/agents?page=<?= $pagination['page'] + 1 ?><?= isset($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" class="pagination-link">下一页</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']): ?>
            <div class="create-agent-section">
                <a href="/agents/create" class="btn btn-primary">创建智能体</a>
            </div>
        <?php endif; ?>
    </div>
</div>
