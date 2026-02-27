<div class="page-header"><div class="container"><h1>黄金开篇生成</h1><p>生成引人入胜的小说开篇，奠定作品基调</p></div></div>

<div class="container">
    <form method="POST" action="/novel_creation/do_opening_generator" class="analysis-form">
        <div class="form-section">
            <h3>基本信息</h3>
            <div class="form-group">
                <label>小说类型 <span class="required">*</span></label>
                <input type="text" name="novel_type" placeholder="如：玄幻、言情、悬疑、都市..." required value="<?= htmlspecialchars($_POST['novel_type'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>核心主题 <span class="required">*</span></label>
                <input type="text" name="core_theme" placeholder="如：复仇、成长、爱情、救赎..." required value="<?= htmlspecialchars($_POST['core_theme'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>主要人物</label>
                <textarea name="main_character" rows="3" placeholder="描述主角的基本设定、性格特点..."><?= htmlspecialchars($_POST['main_character'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>开篇氛围</label>
                <input type="text" name="opening_atmosphere" placeholder="如：紧张、神秘、温馨、悲凉..." value="<?= htmlspecialchars($_POST['opening_atmosphere'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>目标字数</label>
                <input type="number" name="word_count" value="<?= htmlspecialchars($_POST['word_count'] ?? 500) ?>" min="200" max="2000">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">生成开篇</button>
        </div>
    </form>
</div>

<style>
.page-header{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:2rem 0;text-align:center;margin-bottom:2rem;}
.analysis-form{max-width:900px;margin:0 auto;}
.form-section{background:white;border:1px solid #e9ecef;border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;}
.form-section h3{margin:0 0 1.25rem 0;color:#495057;border-bottom:2px solid #667eea;padding-bottom:0.75rem;}
.form-group{margin-bottom:1rem;}
.form-group label{display:block;margin-bottom:0.5rem;font-weight:600;color:#495057;}
.required{color:#dc3545;}
.form-group input,.form-group textarea{width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:8px;font-size:1rem;}
.form-actions{display:flex;gap:1rem;justify-content:center;margin:2rem 0;}
.btn{padding:0.875rem 2rem;border:none;border-radius:8px;cursor:pointer;font-size:1rem;}
.btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:white;}
</style>
