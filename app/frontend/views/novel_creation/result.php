<div class="page-header">
    <div class="container">
        <h1><?= htmlspecialchars($tool_name) ?>结果</h1>
        <p>AI生成的内容，仅供参考</p>
    </div>
</div>

<div class="container">
    <div class="result-section">
        <?php if (isset($result) && $result['success']): ?>
            <div class="success-badge">生成成功</div>

            <?php if (($tool_name ?? '') === '仿写创作' && !empty($params['reference_text'] ?? '')): ?>
                <div class="imitation-compare">
                    <div class="imitation-column">
                        <h3>原文参考</h3>
                        <div class="imitation-original">
                            <?= nl2br(htmlspecialchars($params['reference_text'])) ?>
                        </div>
                    </div>
                    <div class="imitation-column">
                        <h3>仿写结果</h3>
                        <div class="result-content">
                            <?= nl2br(htmlspecialchars($result['content'])) ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="result-content">
                    <?= nl2br(htmlspecialchars($result['content'] ?? '')) ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="error-badge">生成失败</div>
            <div class="error-message">
                <?= htmlspecialchars($result['error'] ?? '未知错误') ?>
            </div>
        <?php endif; ?>

        <div class="result-actions">
            <?php if (isset($result) && $result['success']): ?>
                <button type="button" class="btn btn-outline js-copy-result">复制内容</button>
            <?php endif; ?>
            <a href="<?= $back_url ?>" class="btn btn-primary">重新创作</a>
        </div>
    </div>
</div>
