<div class="page-header"><div class="container"><h1>封面描述生成器</h1><p>根据小说内容生成精美的封面描述</p></div></div>

<div class="container">
    <form method="POST" action="/novel_creation/do_cover_generator" class="analysis-form">
        <div class="form-section">
            <h3>基本信息</h3>
            <div class="form-group">
                <label>书名 <span class="required">*</span></label>
                <input type="text" name="title" placeholder="请输入书名" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>小说类型 <span class="required">*</span></label>
                <input type="text" name="novel_type" placeholder="如：玄幻、言情..." required value="<?= htmlspecialchars($_POST['novel_type'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>核心主题 <span class="required">*</span></label>
                <input type="text" name="core_theme" placeholder="故事的核心主题" required value="<?= htmlspecialchars($_POST['core_theme'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>关键元素</label>
                <textarea name="key_elements" rows="3" placeholder="如：主角形象、关键场景、核心意象..."><?= htmlspecialchars($_POST['key_elements'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">生成封面描述</button>
        </div>
    </form>
</div>

<style>
.page-header{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:2rem 0;text-align:center;margin-bottom:2rem;}
.analysis-form{max-width:900px;margin:0 auto;}
.form-section{background:white;border:1px solid #e9ecef;border-radius:12px;padding:1.5rem;}
.form-section h3{margin:0 0 1.25rem 0;color:#495057;border-bottom:2px solid #667eea;padding-bottom:0.75rem;}
.form-group{margin-bottom:1rem;}
.form-group label{display:block;margin-bottom:0.5rem;font-weight:600;color:#495057;}
.required{color:#dc3545;}
.form-group input,.form-group textarea{width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:8px;font-size:1rem;}
.form-actions{display:flex;gap:1rem;justify-content:center;margin:2rem 0;}
.btn{padding:0.875rem 2rem;border:none;border-radius:8px;cursor:pointer;font-size:1rem;}
.btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:white;}
</style>
