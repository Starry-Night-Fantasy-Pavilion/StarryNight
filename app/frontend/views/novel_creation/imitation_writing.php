<div class="page-header">
    <div class="container">
        <h1>仿写创作</h1>
        <p>基于分析结果，仿照原文风格进行创作练习</p>
    </div>
</div>

<div class="container">
    <form method="POST" action="/novel_creation/do_imitation_writing" class="analysis-form">
        <div class="form-section">
            <h3>原文参考</h3>
            <div class="reference-box"><?= nl2br(htmlspecialchars($reference_text)) ?></div>
        </div>

        <div class="form-section">
            <h3>分析结果</h3>
            <div class="analysis-box"><?= nl2br(htmlspecialchars($analysis_result)) ?></div>
        </div>

        <input type="hidden" name="reference_text" value="<?= htmlspecialchars($reference_text) ?>">
        <input type="hidden" name="analysis" value="<?= htmlspecialchars($analysis_result) ?>">

        <div class="form-section">
            <h3>创作信息</h3>
            <div class="form-group">
                <label>仿写主题 <span class="required">*</span></label>
                <input type="text" name="new_theme" placeholder="您要创作的新主题" required>
            </div>
            <div class="form-group">
                <label>创作要求</label>
                <textarea name="requirements" rows="3" placeholder="特殊要求或注意事项..."></textarea>
            </div>
            <div class="form-group">
                <label>目标字数</label>
                <input type="number" name="word_count" value="500" min="200" max="2000">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">开始创作</button>
        </div>
    </form>
</div>
