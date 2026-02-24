<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-template-detail">
    <div class="container">
        <div class="template-detail-header">
            <a href="/templates" class="back-link">← 返回模板库</a>
            <h1 class="template-detail-title"><?= $h($template['title'] ?? '') ?></h1>
            <div class="template-detail-meta">
                <span class="template-category"><?= $h($template['category'] ?? '') ?></span>
                <span class="template-author">作者: <?= $h($template['user_nickname'] ?? $template['username'] ?? '匿名') ?></span>
                <span class="template-stats">
                    <span><i class="fas fa-eye"></i> <?= number_format($template['view_count'] ?? 0) ?></span>
                    <span><i class="fas fa-download"></i> <?= number_format($template['usage_count'] ?? 0) ?></span>
                </span>
            </div>
        </div>

        <div class="template-detail-content">
            <div class="template-description">
                <h2>模板描述</h2>
                <p><?= nl2br($h($template['description'] ?? '')) ?></p>
            </div>

            <?php if (!empty($template['tags'])): ?>
                <div class="template-tags">
                    <h2>标签</h2>
                    <?php 
                    $tags = explode(',', $template['tags']);
                    foreach ($tags as $tag): 
                    ?>
                        <span class="tag"><?= $h(trim($tag)) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="template-content">
                <h2>模板内容</h2>
                <div class="template-content-box">
                    <pre><?= $h($template['content'] ?? '') ?></pre>
                </div>
            </div>

            <?php if (!empty($template['structure'])): ?>
                <div class="template-structure">
                    <h2>模板结构</h2>
                    <pre><?= $h($template['structure']) ?></pre>
                </div>
            <?php endif; ?>
        </div>

        <div class="template-detail-actions">
            <button class="btn btn-primary" onclick="applyTemplate(<?= $h($template['id']) ?>)">应用模板</button>
            <a href="/templates" class="btn btn-outline">返回列表</a>
        </div>
    </div>
</div>

<script>
function applyTemplate(id) {
    fetch('/templates/apply', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({id: id})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('模板应用成功！');
            // 可以跳转到编辑器或其他页面
        } else {
            alert('应用失败: ' + (data.message || '未知错误'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('应用失败，请重试');
    });
}
</script>
