<div class="page-header">
    <div class="container">
        <h1>章节质量评估</h1>
        <p>AI驱动的专业章节分析，助您发现优缺点，提升写作水平</p>
    </div>
</div>

<div class="container">
    <form method="POST" action="/novel_creation/do_chapter_analysis" class="analysis-form" id="analysisForm">
        <div class="form-section">
            <h3>章节内容</h3>
            <div class="form-group">
                <label for="chapter_content">输入要分析的章节内容 <span class="required">*</span></label>
                <textarea id="chapter_content" name="chapter_content" rows="15"
                          placeholder="请粘贴您的章节内容...
建议提供完整的章节，以便AI进行全面的分析和评估。"
                          required><?= htmlspecialchars($_POST['chapter_content'] ?? '') ?></textarea>
                <small class="form-help">章节内容越长，分析越全面。建议至少500字以上。</small>
            </div>
        </div>

        <div class="form-section">
            <h3>角色设定（选填）</h3>
            <div class="form-group">
                <label for="character_settings">主要角色设定</label>
                <textarea id="character_settings" name="character_settings" rows="6"
                          placeholder="请描述本章涉及的主要角色及其设定..."><?= htmlspecialchars($_POST['character_settings'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <h3>情节背景（选填）</h3>
            <div class="form-group">
                <label for="plot_background">情节背景与上下文</label>
                <textarea id="plot_background" name="plot_background" rows="6"
                          placeholder="请描述本章的故事情节背景..."><?= htmlspecialchars($_POST['plot_background'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                开始分析
            </button>
            <button type="button" class="btn btn-outline" onclick="clearForm()">
                清空重填
            </button>
        </div>
    </form>

    <div class="info-section">
        <h3>分析维度说明</h3>
        <div class="info-grid">
            <div class="info-card">
                <div class="info-icon">📖</div>
                <h4>情节评估</h4>
                <ul>
                    <li><strong>节奏</strong> - 叙事快慢是否合适</li>
                    <li><strong>冲突</strong> - 矛盾是否突出</li>
                    <li><strong>转折</strong> - 情节变化是否自然</li>
                </ul>
            </div>
            <div class="info-card">
                <div class="info-icon">👤</div>
                <h4>角色表现</h4>
                <ul>
                    <li><strong>行为合理性</strong> - 角色行为是否符合设定</li>
                    <li><strong>对话质量</strong> - 对话是否生动自然</li>
                    <li><strong>情感刻画</strong> - 情感表达是否到位</li>
                </ul>
            </div>
            <div class="info-card">
                <div class="info-icon">✍️</div>
                <h4>文笔质量</h4>
                <ul>
                    <li><strong>语言流畅度</strong> - 文字是否通顺</li>
                    <li><strong>描写生动性</strong> - 描写是否形象</li>
                    <li><strong>氛围营造</strong> - 氛围是否到位</li>
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
.page-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 2rem 0; text-align: center; margin-bottom: 2rem; }
.page-header h1 { font-size: 2rem; margin-bottom: 0.5rem; }
.analysis-form { max-width: 900px; margin: 0 auto 2rem; }
.form-section { background: white; border: 1px solid #e9ecef; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.form-section h3 { margin: 0 0 1.25rem 0; color: #495057; border-bottom: 2px solid #667eea; padding-bottom: 0.75rem; }
.form-group { margin-bottom: 1.25rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #495057; }
.required { color: #dc3545; }
.form-group textarea { width: 100%; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; font-family: inherit; line-height: 1.6; resize: vertical; }
.form-help { display: block; margin-top: 0.5rem; color: #6c757d; font-size: 0.85rem; }
.form-actions { display: flex; gap: 1rem; justify-content: center; margin: 2rem 0; }
.btn { padding: 0.875rem 2rem; border: none; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; transition: all 0.2s; font-size: 1rem; }
.btn-lg { padding: 1rem 2.5rem; font-size: 1.1rem; }
.btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
.btn-outline { background: transparent; color: #667eea; border: 2px solid #667eea; }
.info-section { max-width: 900px; margin: 0 auto 3rem; }
.info-section h3 { text-align: center; margin-bottom: 1.5rem; color: #495057; }
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.25rem; }
.info-card { background: white; border: 1px solid #e9ecef; border-radius: 12px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.info-icon { font-size: 2rem; margin-bottom: 0.75rem; }
.info-card h4 { margin: 0 0 0.75rem 0; color: #667eea; }
.info-card ul { margin: 0; padding-left: 1.25rem; color: #6c757d; font-size: 0.9rem; line-height: 1.8; }
</style>
