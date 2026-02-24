<div class="page-header"><div class="container"><h1>书名生成器</h1><p>根据内容生成吸引读者的爆款书名</p></div></div>

<div class="container">
    <form method="POST" action="/novel_creation/do_title_generator" class="analysis-form">
        <div class="form-section">
            <h3>基本信息</h3>
            <div class="form-group">
                <label>小说类型 <span class="required">*</span></label>
                <input type="text" name="novel_type" placeholder="如：玄幻、言情、悬疑..." required value="<?= htmlspecialchars($_POST['novel_type'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>核心主题 <span class="required">*</span></label>
                <input type="text" name="core_theme" placeholder="如：热血、虐恋、推理..." required value="<?= htmlspecialchars($_POST['core_theme'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>关键词</label>
                <input type="text" name="keywords" placeholder="如：穿越、系统、废材逆袭..." value="<?= htmlspecialchars($_POST['keywords'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>生成数量</label>
                <input type="number" name="count" value="<?= htmlspecialchars($_POST['count'] ?? 5) ?>" min="3" max="10">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">生成书名</button>
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
.form-group input{width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:8px;font-size:1rem;}
.form-actions{display:flex;gap:1rem;justify-content:center;margin:2rem 0;}
.btn{padding:0.875rem 2rem;border:none;border-radius:8px;cursor:pointer;font-size:1rem;}
.btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:white;}
</style>
