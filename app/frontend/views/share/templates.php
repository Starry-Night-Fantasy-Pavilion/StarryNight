<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-share-templates">
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">模板分享</h1>
        </div>
    </div>

    <div class="container">
        <div class="templates-grid">
            <?php if (empty($templates ?? [])): ?>
                <div class="empty-state">
                    <p>暂无模板</p>
                </div>
            <?php else: ?>
                <?php foreach ($templates as $template): ?>
                    <div class="template-card">
                        <h3 class="template-title">
                            <a href="/templates/<?= $h($template['id']) ?>"><?= $h($template['title']) ?></a>
                        </h3>
                        <p class="template-description"><?= $h(mb_substr($template['description'] ?? '', 0, 100)) ?><?= mb_strlen($template['description'] ?? '') > 100 ? '...' : '' ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
