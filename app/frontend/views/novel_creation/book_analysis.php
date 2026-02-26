<div class="page-header">
    <div class="container">
        <h1>拆书分析</h1>
        <p>分析优秀作品的写作技巧，提升您的创作水平</p>
    </div>
</div>

<div class="container">
    <form method="POST" action="/novel_creation/do_book_analysis" class="analysis-form" id="analysisForm">
        <div class="form-section">
            <h3>参考文本</h3>
            <div class="form-group">
                <label for="reference_text">输入要分析的文本 <span class="required">*</span></label>
                <textarea id="reference_text" name="reference_text" rows="15"
                          placeholder="请粘贴您要分析的参考文本...
可以是您喜欢的作家作品片段，或者优秀的范文。"
                          required><?= htmlspecialchars($_POST['reference_text'] ?? '') ?></textarea>
                <small class="form-help">建议提供完整的段落或章节，以便AI进行全面的分析。</small>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">开始分析</button>
            <button type="button" class="btn btn-outline js-clear-analysis-form">清空重填</button>
        </div>
    </form>

    <div class="info-section">
        <h3>分析内容说明</h3>
        <div class="info-grid">
            <div class="info-card">
                <div class="info-icon">✍️</div>
                <h4>写作技巧</h4>
                <ul><li>叙事视角分析</li><li>描写手法提炼</li><li>修辞技巧总结</li></ul>
            </div>
            <div class="info-card">
                <div class="info-icon">🎨</div>
                <h4>风格特点</h4>
                <ul><li>语言风格特点</li><li>节奏控制方式</li><li>氛围营造技巧</li></ul>
            </div>
            <div class="info-card">
                <div class="info-icon">📐</div>
                <h4>结构特点</h4>
                <ul><li>段落安排方式</li><li>信息展示顺序</li><li>留白技巧运用</li></ul>
            </div>
        </div>
    </div>
</div>
