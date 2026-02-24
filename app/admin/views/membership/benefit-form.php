<?php
// 会员权益表单
$isEdit = !empty($benefit);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?= $isEdit ? '编辑会员权益' : '新增会员权益' ?></title>
</head>
<body>
    <h1><?= $isEdit ? '编辑会员权益' : '新增会员权益' ?></h1>

    <form method="post">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)($benefit['id'] ?? '')) ?>">
        <?php endif; ?>

        <div>
            <label>Key：
                <input type="text" name="benefit_key" value="<?= htmlspecialchars((string)($benefit['benefit_key'] ?? '')) ?>" required>
            </label>
        </div>
        <div>
            <label>名称：
                <input type="text" name="benefit_name" value="<?= htmlspecialchars((string)($benefit['benefit_name'] ?? '')) ?>" required>
            </label>
        </div>
        <div>
            <label>类型：
                <input type="text" name="benefit_type" value="<?= htmlspecialchars((string)($benefit['benefit_type'] ?? '')) ?>">
            </label>
        </div>
        <div>
            <label>值：
                <input type="text" name="value" value="<?= htmlspecialchars((string)($benefit['value'] ?? '')) ?>">
            </label>
        </div>
        <div>
            <label>描述：
                <textarea name="description" rows="4" cols="40"><?= htmlspecialchars((string)($benefit['description'] ?? '')) ?></textarea>
            </label>
        </div>
        <div>
            <label>启用：
                <input type="checkbox" name="is_enabled" value="1" <?= !isset($benefit['is_enabled']) || $benefit['is_enabled'] ? 'checked' : '' ?>>
            </label>
        </div>
        <div>
            <label>排序：
                <input type="number" name="sort_order" value="<?= htmlspecialchars((string)($benefit['sort_order'] ?? 0)) ?>">
            </label>
        </div>

        <button type="submit">保存</button>
    </form>
</body>
</html>

