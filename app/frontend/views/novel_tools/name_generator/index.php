<div class="page-header"><div class="container"><h1>名字生成器</h1><p>生成独特而富有寓意的名字</p></div></div>

<div class="container">
    <form method="POST" action="/novel_creation/do_name_generator" class="analysis-form">
        <div class="form-section">
            <h3>基本信息</h3>
            <div class="form-group">
                <label>名字类型 <span class="required">*</span></label>
                <select name="name_type" required>
                    <option value="character" <?= ($_POST['name_type'] ?? '') == 'character' ? 'selected' : '' ?>>人物名字</option>
                    <option value="place" <?= ($_POST['name_type'] ?? '') == 'place' ? 'selected' : '' ?>>地名</option>
                    <option value="faction" <?= ($_POST['name_type'] ?? '') == 'faction' ? 'selected' : '' ?>>势力/组织名</option>
                    <option value="skill" <?= ($_POST['name_type'] ?? '') == 'skill' ? 'selected' : '' ?>>技能名</option>
                    <option value="item" <?= ($_POST['name_type'] ?? '') == 'item' ? 'selected' : '' ?>>物品名</option>
                </select>
            </div>
            <div class="form-group">
                <label>题材风格</label>
                <input type="text" name="genre" placeholder="如：玄幻、仙侠、现代..." value="<?= htmlspecialchars($_POST['genre'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>风格要求</label>
                <input type="text" name="style" placeholder="如：古风、简洁、霸气..." value="<?= htmlspecialchars($_POST['style'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>生成数量</label>
                <input type="number" name="count" value="<?= htmlspecialchars($_POST['count'] ?? 10) ?>" min="5" max="20">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">生成名字</button>
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
.form-group input,.form-group select{width:100%;padding:0.75rem;border:1px solid #ddd;border-radius:8px;font-size:1rem;}
.form-actions{display:flex;gap:1rem;justify-content:center;margin:2rem 0;}
.btn{padding:0.875rem 2rem;border:none;border-radius:8px;cursor:pointer;font-size:1rem;}
.btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:white;}
</style>
