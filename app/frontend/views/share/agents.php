<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-share-agents">
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">智能体分享</h1>
        </div>
    </div>

    <div class="container">
        <div class="agents-grid">
            <?php if (empty($agents ?? [])): ?>
                <div class="empty-state">
                    <p>暂无智能体</p>
                </div>
            <?php else: ?>
                <?php foreach ($agents as $agent): ?>
                    <div class="agent-card">
                        <h3 class="agent-title">
                            <a href="/agents/<?= $h($agent['id']) ?>"><?= $h($agent['name']) ?></a>
                        </h3>
                        <p class="agent-description"><?= $h(mb_substr($agent['description'] ?? '', 0, 100)) ?><?= mb_strlen($agent['description'] ?? '') > 100 ? '...' : '' ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
