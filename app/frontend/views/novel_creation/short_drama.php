<div class="page-header"><div class="container"><h1>短剧剧本</h1><p>专业的短剧剧本创作工具，打造精彩脚本</p></div></div>

<div class="container">
    <form method="POST" action="/novel_creation/do_short_drama" class="analysis-form">
        <div class="form-section">
            <h3>创作信息</h3>
            <div class="form-group">
                <label>剧名 <span class="required">*</span></label>
                <input type="text" name="title" placeholder="请输入剧名" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>类型 <span class="required">*</span></label>
                <input type="text" name="genre" placeholder="如：甜宠、虐恋、悬疑..." required value="<?= htmlspecialchars($_POST['genre'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>主要人物</label>
                <textarea name="main_character" rows="3" placeholder="主要角色及其特点..."><?= htmlspecialchars($_POST['main_character'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>剧情梗概 <span class="required">*</span></label>
                <textarea name="plot" rows="4" placeholder="简要描述剧情发展..." required><?= htmlspecialchars($_POST['plot'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>集数</label>
                <input type="number" name="episode_count" value="<?= htmlspecialchars($_POST['episode_count'] ?? 1) ?>" min="1" max="10">
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
