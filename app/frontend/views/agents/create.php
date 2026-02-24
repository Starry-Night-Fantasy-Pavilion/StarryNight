<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="page-agent-create">
    <div class="container">
        <h1 class="page-title">创建智能体</h1>
        
        <form method="POST" action="/agents" class="agent-form">
            <div class="form-group">
                <label for="name">智能体名称 *</label>
                <input type="text" id="name" name="name" class="form-control" required>
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
                <label for="description">描述 *</label>
                <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label for="system_prompt">系统提示词 *</label>
                <textarea id="system_prompt" name="system_prompt" class="form-control" rows="6" required></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">创建智能体</button>
                <a href="/agents" class="btn btn-outline">取消</a>
            </div>
        </form>
    </div>
</div>
