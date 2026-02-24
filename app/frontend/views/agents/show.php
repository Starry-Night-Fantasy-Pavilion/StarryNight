<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-agent-detail">
    <div class="container">
        <div class="agent-detail-header">
            <a href="/agents" class="back-link">← 返回智能体</a>
            <h1 class="agent-detail-title"><?= $h($agent['name'] ?? '') ?></h1>
            <div class="agent-detail-meta">
                <span class="agent-category"><?= $h($agent['category'] ?? '') ?></span>
                <span class="agent-author">作者: <?= $h($agent['user_nickname'] ?? $agent['username'] ?? '匿名') ?></span>
            </div>
        </div>

        <div class="agent-detail-content">
            <div class="agent-description">
                <h2>智能体描述</h2>
                <p><?= nl2br($h($agent['description'] ?? '')) ?></p>
            </div>

            <div class="agent-actions">
                <button class="btn btn-primary" onclick="useAgent(<?= $h($agent['id']) ?>)">使用智能体</button>
                <a href="/agents" class="btn btn-outline">返回列表</a>
            </div>
        </div>
    </div>
</div>

<script>
function useAgent(id) {
    fetch('/agents/use', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({id: id})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('智能体已启用！');
        } else {
            alert('启用失败: ' + (data.message || '未知错误'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('启用失败，请重试');
    });
}
</script>
