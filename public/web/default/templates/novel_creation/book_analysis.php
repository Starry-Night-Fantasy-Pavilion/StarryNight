<div class="page-header">
    <div class="container">
        <h1>拆书分析</h1>
        <p>分析优秀作品的写作技巧，提升您的创作水平</p>
    </div>
</div>

<div class="container">
    <form method="POST" action="/novel_creation/do_book_analysis" class="analysis-form" id="analysisForm">
        <div class="form-section">
            <h3><i class="icon-book"></i> 参考文本</h3>
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
            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                <i class="icon-search"></i> 开始分析
            </button>
            <button type="button" class="btn btn-outline" onclick="clearForm()">
                <i class="icon-refresh"></i> 清空重填
            </button>
        </div>
    </form>

    <div class="info-section">
        <h3>分析内容说明</h3>
        <div class="info-grid">
            <div class="info-card">
                <div class="info-icon">✍️</div>
                <h4>写作技巧</h4>
                <ul>
                    <li>叙事视角分析</li>
                    <li>描写手法提炼</li>
                    <li>修辞技巧总结</li>
                </ul>
            </div>
            <div class="info-card">
                <div class="info-icon">🎨</div>
                <h4>风格特点</h4>
                <ul>
                    <li>语言风格特点</li>
                    <li>节奏控制方式</li>
                    <li>氛围营造技巧</li>
                </ul>
            </div>
            <div class="info-card">
                <div class="info-icon">📐</div>
                <h4>结构特点</h4>
                <ul>
                    <li>段落安排方式</li>
                    <li>信息展示顺序</li>
                    <li>留白技巧运用</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function clearForm() {
    if (confirm('确定要清空所有输入内容吗？')) {
        document.getElementById('analysisForm').reset();
    }
}
</script>

<style>
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 0;
    text-align: center;
    margin-bottom: 2rem;
}
.page-header h1 { font-size: 2rem; margin-bottom: 0.5rem; }
.analysis-form { max-width: 900px; margin: 0 auto 2rem; }
.form-section {
    background: white; border: 1px solid #e9ecef;
    border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.form-section h3 {
    margin: 0 0 1.25rem 0; color: #495057;
    display: flex; align-items: center; gap: 0.5rem;
    border-bottom: 2px solid #667eea; padding-bottom: 0.75rem;
}
.form-group { margin-bottom: 1.25rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #495057; }
.required { color: #dc3545; }
.form-group textarea {
    width: 100%; padding: 1rem; border: 1px solid #ddd;
    border-radius: 8px; font-size: 1rem; font-family: inherit;
    line-height: 1.6; resize: vertical;
}
.form-help { display: block; margin-top: 0.5rem; color: #6c757d; font-size: 0.85rem; }
.form-actions { display: flex; gap: 1rem; justify-content: center; margin: 2rem 0; }
.btn {
    padding: 0.875rem 2rem; border: none; border-radius: 8px;
    text-decoration: none; display: inline-flex; align-items: center;
    gap: 0.5rem; cursor: pointer; transition: all 0.2s; font-size: 1rem;
}
.btn-lg { padding: 1rem 2.5rem; font-size: 1.1rem; }
.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.btn-outline { background: transparent; color: #667eea; border: 2px solid #667eea; }
.info-section { max-width: 900px; margin: 0 auto 3rem; }
.info-section h3 { text-align: center; margin-bottom: 1.5rem; color: #495057; }
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.25rem; }
.info-card {
    background: white; border: 1px solid #e9ecef;
    border-radius: 12px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.info-icon { font-size: 2rem; margin-bottom: 0.75rem; }
.info-card h4 { margin: 0 0 0.75rem 0; color: #667eea; }
.info-card ul { margin: 0; padding-left: 1.25rem; color: #6c757d; font-size: 0.9rem; line-height: 1.8; }
</style>
