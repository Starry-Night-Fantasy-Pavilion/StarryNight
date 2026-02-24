<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-share-prompts">
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">提示词分享</h1>
        </div>
    </div>

    <div class="container">
        <div class="prompts-grid">
            <?php if (empty($prompts ?? [])): ?>
                <div class="empty-state">
                    <p>暂无提示词</p>
                </div>
            <?php else: ?>
                <?php foreach ($prompts as $prompt): ?>
                    <div class="prompt-card">
                        <h3 class="prompt-title"><?= $h($prompt['title'] ?? '') ?></h3>
                        <p class="prompt-description"><?= $h(mb_substr($prompt['description'] ?? '', 0, 100)) ?><?= mb_strlen($prompt['description'] ?? '') > 100 ? '...' : '' ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
