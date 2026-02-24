<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-share-detail">
    <div class="container">
        <div class="share-detail-header">
            <a href="/share" class="back-link">← 返回资源分享</a>
            <h1 class="share-detail-title"><?= $h($resource['title'] ?? '') ?></h1>
            <div class="share-detail-meta">
                <span class="resource-type"><?= $h($resource['resource_type'] ?? '') ?></span>
                <span class="resource-author">作者: <?= $h($resource['user_nickname'] ?? $resource['username'] ?? '匿名') ?></span>
            </div>
        </div>

        <div class="share-detail-content">
            <div class="resource-description">
                <h2>资源描述</h2>
                <p><?= nl2br($h($resource['description'] ?? '')) ?></p>
            </div>

            <div class="resource-actions">
                <a href="/share" class="btn btn-outline">返回列表</a>
            </div>
        </div>
    </div>
</div>
