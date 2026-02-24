<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-template-create">
    <div class="container">
        <h1 class="page-title">创建模板</h1>
        
        <form method="POST" action="/templates" class="template-form">
            <div class="form-group">
                <label for="title">模板标题 *</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="category">分类 *</label>
                <select id="category" name="category" class="form-control" required>
                    <?php foreach ($categories ?? [] as $key => $label): ?>
                        <option value="<?= $h($key) ?>"><?= $h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="type">类型 *</label>
                <select id="type" name="type" class="form-control" required>
                    <?php foreach ($types ?? [] as $key => $label): ?>
                        <option value="<?= $h($key) ?>"><?= $h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="description">描述 *</label>
                <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label for="content">模板内容 *</label>
                <textarea id="content" name="content" class="form-control" rows="10" required></textarea>
            </div>

            <div class="form-group">
                <label for="structure">模板结构（JSON格式，可选）</label>
                <textarea id="structure" name="structure" class="form-control" rows="6"></textarea>
            </div>

            <div class="form-group">
                <label for="tags">标签（用逗号分隔）</label>
                <input type="text" id="tags" name="tags" class="form-control" placeholder="标签1,标签2,标签3">
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_public" value="1"> 公开模板
                </label>
            </div>

            <div class="form-group">
                <label for="price">价格（星币）</label>
                <input type="number" id="price" name="price" class="form-control" min="0" step="0.01" value="0">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">创建模板</button>
                <a href="/templates" class="btn btn-outline">取消</a>
            </div>
        </form>
    </div>
</div>
