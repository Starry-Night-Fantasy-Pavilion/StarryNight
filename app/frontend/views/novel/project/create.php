<?php ?>
<div class="novel-create-page">
    <h1 style="margin-bottom:24px;">创建新小说</h1>
    
    <div class="card" style="max-width:800px; margin:0 auto;">
        <div style="padding:24px;">
            <form method="POST">
                <div style="margin-bottom:20px;">
                    <label class="form-label">小说标题 *</label>
                    <input type="text" name="title" class="form-control" required placeholder="请输入小说标题">
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:20px;">
                    <div>
                        <label class="form-label">题材</label>
                        <select name="genre" class="form-control">
                            <option value="">请选择</option>
                            <option value="玄幻">玄幻</option>
                            <option value="都市">都市</option>
                            <option value="历史">历史</option>
                            <option value="科幻">科幻</option>
                            <option value="武侠">武侠</option>
                            <option value="言情">言情</option>
                            <option value="悬疑">悬疑</option>
                            <option value="其他">其他</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">类型</label>
                        <select name="type" class="form-control">
                            <option value="">请选择</option>
                            <option value="长篇">长篇</option>
                            <option value="中篇">中篇</option>
                            <option value="短篇">短篇</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom:20px;">
                    <label class="form-label">主题</label>
                    <textarea name="theme" class="form-control" rows="3" placeholder="描述小说的主题和核心冲突"></textarea>
                </div>

                <div style="margin-bottom:20px;">
                    <label class="form-label">目标字数</label>
                    <input type="number" name="target_words" class="form-control" value="0" min="0" placeholder="例如：100000">
                </div>

                <div style="margin-bottom:20px;">
                    <label class="form-label">简介</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="小说的简介"></textarea>
                </div>

                <div style="margin-bottom:20px;">
                    <label class="form-label">标签（逗号分隔）</label>
                    <input type="text" name="tags" class="form-control" placeholder="例如：热血,冒险,成长">
                </div>

                <div style="display:flex; gap:12px;">
                    <button type="submit" class="btn btn-primary">创建</button>
                    <a href="/novel" class="btn btn-secondary">取消</a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.novel-create-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px;
}
.form-label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    font-size: 14px;
}
.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 6px;
    background: rgba(255,255,255,0.05);
    color: #fff;
    font-size: 14px;
}
.form-control:focus {
    outline: none;
    border-color: #0ea5e9;
    background: rgba(255,255,255,0.08);
}

/* 原生 select 下拉弹层会继承 color，导致白底白字；这里强制用深色下拉方案 */
select.form-control {
    color-scheme: dark;
}

select.form-control option,
select.form-control optgroup {
    background-color: #0b1020;
    color: #f8fafc;
}
</style>
