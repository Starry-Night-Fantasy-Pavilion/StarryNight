<?php
/** @var string $currentPage */
$adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/');
?>

<div class="ai-page">
    <div class="ai-tabs">
        <a class="ai-tab <?= ($currentPage ?? '') === 'ai-channels' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/ai/channels">配置</a>
        <a class="ai-tab <?= ($currentPage ?? '') === 'ai-monitor' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/ai/monitor">监控</a>
        <a class="ai-tab <?= ($currentPage ?? '') === 'ai-model-prices' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/ai/model-prices">定价</a>
        <a class="ai-tab <?= ($currentPage ?? '') === 'ai-preset-models' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/ai/preset-models">模型名</a>
        <a class="ai-tab <?= ($currentPage ?? '') === 'ai-templates' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/ai/templates">模板</a>
        <a class="ai-tab <?= ($currentPage ?? '') === 'ai-agents' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/ai/agents">智能体</a>
        <a class="ai-tab <?= ($currentPage ?? '') === 'ai-audits' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/ai/audits">审核</a>
        <a class="ai-tab <?= ($currentPage ?? '') === 'ai-embeddings' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/ai/embeddings">嵌入</a>
    </div>
</div>

