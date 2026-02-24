<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-share-knowledge">
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">知识库分享</h1>
        </div>
    </div>

    <div class="container">
        <div class="knowledge-grid">
            <?php if (empty($knowledgeBases ?? [])): ?>
                <div class="empty-state">
                    <p>暂无知识库</p>
                </div>
            <?php else: ?>
                <?php foreach ($knowledgeBases as $kb): ?>
                    <div class="knowledge-card">
                        <h3 class="knowledge-title">
                            <a href="/knowledge/view/<?= $h($kb['id']) ?>"><?= $h($kb['title']) ?></a>
                        </h3>
                        <p class="knowledge-description"><?= $h(mb_substr($kb['description'] ?? '', 0, 100)) ?><?= mb_strlen($kb['description'] ?? '') > 100 ? '...' : '' ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
