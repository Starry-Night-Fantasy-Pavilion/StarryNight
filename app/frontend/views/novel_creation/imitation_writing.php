<div class="page-header"><div class="container"><h1>仿写创作</h1><p>基于分析结果，仿照原文风格进行创作练习</p></div></div>

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

<style>
.page-header{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:2rem 0;text-align:center;margin-bottom:2rem;}
.analysis-form{max-width:900px;margin:0 auto;}
.form-section{background:white;border:1px solid #e9ecef;border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;}
.form-section h3{margin:0 0 1rem 0;color:#495057;border-bottom:2px solid #667eea;padding-bottom:0.5rem;}
.reference-box,.analysis-box{background:#f8f9fa;border:1px solid #e9ecef;border-radius:8px;padding:1rem;white-space:pre-wrap;line-height:1.6;font-size:0.9rem;max-height:200px;overflow-y:auto;}
.form-group{margin-bottom:1rem;}
.form-group label{display:block;margin-bottom:0.5rem;font-weight:600;color:#495057;}
.required{color:#dc3545;}
.form-group input,.form-group textarea{width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:8px;font-size:1rem;}
.form-actions{display:flex;gap:1rem;justify-content:center;margin:2rem 0;}
.btn{padding:0.875rem 2rem;border:none;border-radius:8px;cursor:pointer;font-size:1rem;}
.btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:white;}
</style>
