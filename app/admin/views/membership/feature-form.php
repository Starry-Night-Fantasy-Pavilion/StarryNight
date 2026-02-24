<?php
// 功能权限表单
$isEdit = !empty($feature);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?= $isEdit ? '编辑功能' : '新增功能' ?></title>
</head>
<body>
    <h1><?= $isEdit ? '编辑功能' : '新增功能' ?></h1>

    <form method="post">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)($feature['id'] ?? '')) ?>">
        <?php endif; ?>

        <div>
            <label>Key：
                <input type="text" name="feature_key" value="<?= htmlspecialchars((string)($feature['feature_key'] ?? '')) ?>" required>
            </label>
        </div>
        <div>
            <label>名称：
                <input type="text" name="feature_name" value="<?= htmlspecialchars((string)($feature['feature_name'] ?? '')) ?>" required>
            </label>
        </div>
        <div>
            <label>分类：
                <input type="text" name="category" value="<?= htmlspecialchars((string)($feature['category'] ?? '')) ?>">
            </label>
        </div>
        <div>
            <label>描述：
                <textarea name="description" rows="4" cols="40"><?= htmlspecialchars((string)($feature['description'] ?? '')) ?></textarea>
            </label>
        </div>
        <div>
            <label>需要 VIP：
                <input type="checkbox" name="require_vip" value="1" <?= !empty($feature['require_vip']) ? 'checked' : '' ?>>
            </label>
        </div>
        <div>
            <label>启用：
                <input type="checkbox" name="is_enabled" value="1" <?= !isset($feature['is_enabled']) || $feature['is_enabled'] ? 'checked' : '' ?>>
            </label>
        </div>
        <div>
            <label>排序：
                <input type="number" name="sort_order" value="<?= htmlspecialchars((string)($feature['sort_order'] ?? 0)) ?>">
            </label>
        </div>

        <button type="submit">保存</button>
    </form>
</body>
</html>

