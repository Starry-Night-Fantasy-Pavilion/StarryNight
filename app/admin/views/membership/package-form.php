<?php
// 会员套餐表单
$isEdit = !empty($package);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?= $isEdit ? '编辑会员套餐' : '新增会员套餐' ?></title>
</head>
<body>
    <h1><?= $isEdit ? '编辑会员套餐' : '新增会员套餐' ?></h1>

    <form method="post">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)($package['id'] ?? '')) ?>">
        <?php endif; ?>

        <div>
            <label>名称：
                <input type="text" name="name" value="<?= htmlspecialchars((string)($package['name'] ?? '')) ?>" required>
            </label>
        </div>
        <div>
            <label>类型：
                <input type="number" name="type" value="<?= htmlspecialchars((string)($package['type'] ?? 1)) ?>">
            </label>
        </div>
        <div>
            <label>时长（天）：
                <input type="number" name="duration_days" value="<?= htmlspecialchars((string)($package['duration_days'] ?? 0)) ?>">
            </label>
        </div>
        <div>
            <label>原价：
                <input type="text" name="original_price" value="<?= htmlspecialchars((string)($package['original_price'] ?? '0')) ?>">
            </label>
        </div>
        <div>
            <label>折扣价：
                <input type="text" name="discount_price" value="<?= htmlspecialchars((string)($package['discount_price'] ?? '')) ?>">
            </label>
        </div>
        <div>
            <label>折扣比例：
                <input type="text" name="discount_rate" value="<?= htmlspecialchars((string)($package['discount_rate'] ?? '')) ?>">
            </label>
        </div>
        <div>
            <label>描述：
                <textarea name="description" rows="4" cols="40"><?= htmlspecialchars((string)($package['description'] ?? '')) ?></textarea>
            </label>
        </div>
        <div>
            <label>推荐：
                <input type="checkbox" name="is_recommended" value="1" <?= !empty($package['is_recommended']) ? 'checked' : '' ?>>
            </label>
        </div>
        <div>
            <label>启用：
                <input type="checkbox" name="is_enabled" value="1" <?= !isset($package['is_enabled']) || $package['is_enabled'] ? 'checked' : '' ?>>
            </label>
        </div>
        <div>
            <label>排序：
                <input type="number" name="sort_order" value="<?= htmlspecialchars((string)($package['sort_order'] ?? 0)) ?>">
            </label>
        </div>
        <div>
            <label>图标：
                <input type="text" name="icon" value="<?= htmlspecialchars((string)($package['icon'] ?? '')) ?>">
            </label>
        </div>
        <div>
            <label>角标：
                <input type="text" name="badge" value="<?= htmlspecialchars((string)($package['badge'] ?? '')) ?>">
            </label>
        </div>

        <button type="submit">保存</button>
    </form>
</body>
</html>

