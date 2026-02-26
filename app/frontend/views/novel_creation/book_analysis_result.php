<div class="page-header">
    <div class="container">
        <h1>拆书分析结果</h1>
        <p>AI对原文的详细分析</p>
    </div>
</div>

<div class="container">
    <div class="result-section">
        <?php if (isset($result) && $result['success']): ?>
            <div class="success-badge">分析完成</div>
            <div class="result-content">
                <?= nl2br(htmlspecialchars($result['content'])) ?>
            </div>

            <div class="next-step">
                <a href="/novel_creation/imitation_writing" class="btn btn-primary btn-lg">开始仿写</a>
            </div>
        <?php else: ?>
            <div class="error-badge">分析失败</div>
            <div class="error-message">
                <?= htmlspecialchars($result['error'] ?? '未知错误') ?>
            </div>
        <?php endif; ?>

        <div class="result-actions">
            <a href="/novel_creation/book_analysis" class="btn btn-outline">重新分析</a>
        </div>
    </div>
</div>
