<?php
// AI 提示词模板管理
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>AI 提示词模板</title>
</head>
<body>
    <h1>AI 提示词模板</h1>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars((string)$error) ?></p>
    <?php endif; ?>

    <form method="get">
        <label>分类：
            <input type="text" name="category" value="<?= htmlspecialchars((string)($category ?? '')) ?>">
        </label>
        <label>是否系统模板：
            <select name="is_system">
                <option value="">全部</option>
                <option value="1" <?= (isset($isSystem) && $isSystem === true) ? 'selected' : '' ?>>是</option>
                <option value="0" <?= (isset($isSystem) && $isSystem === false) ? 'selected' : '' ?>>否</option>
            </select>
        </label>
        <label>是否启用：
            <select name="is_active">
                <option value="">全部</option>
                <option value="1" <?= (isset($isActive) && $isActive === true) ? 'selected' : '' ?>>启用</option>
                <option value="0" <?= (isset($isActive) && $isActive === false) ? 'selected' : '' ?>>停用</option>
            </select>
        </label>
        <label>搜索：
            <input type="text" name="search" value="<?= htmlspecialchars((string)($searchTerm ?? '')) ?>">
        </label>
        <button type="submit">筛选</button>
    </form>

    <h2>创建新模板</h2>
    <form method="post">
        <div>
            <label>名称：
                <input type="text" name="name" required>
            </label>
        </div>
        <div>
            <label>分类：
                <input type="text" name="category">
            </label>
        </div>
        <div>
            <label>描述：
                <textarea name="description" rows="3" cols="40"></textarea>
            </label>
        </div>
        <div>
            <label>模板内容：
                <textarea name="template_content" rows="6" cols="60"></textarea>
            </label>
        </div>
        <div>
            <label>变量说明（每行 key: 描述）：
                <textarea name="variables" rows="4" cols="60"></textarea>
            </label>
        </div>
        <div>
            <label>系统模板：
                <input type="checkbox" name="is_system" value="1">
            </label>
        </div>
        <div>
            <label>启用：
                <input type="checkbox" name="is_active" value="1" checked>
            </label>
        </div>
        <button type="submit">保存模板</button>
    </form>

    <h2>模板列表</h2>
    <?php $list = $result['items'] ?? $result['data'] ?? []; ?>
    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
        <tr>
            <th>ID</th>
            <th>名称</th>
            <th>分类</th>
            <th>系统</th>
            <th>启用</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($list) && is_iterable($list)): ?>
            <?php foreach ($list as $tpl): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($tpl['id'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($tpl['name'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($tpl['category'] ?? '')) ?></td>
                    <td><?= !empty($tpl['is_system']) ? '是' : '否' ?></td>
                    <td><?= !empty($tpl['is_active']) ? '启用' : '停用' ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">暂无模板</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

