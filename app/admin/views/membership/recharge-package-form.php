<?php
// 充值套餐表单
$isEdit = !empty($package);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?= $isEdit ? '编辑充值套餐' : '新增充值套餐' ?></title>
</head>
<body>
    <h1><?= $isEdit ? '编辑充值套餐' : '新增充值套餐' ?></h1>

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
            <label>代币数：
                <input type="number" name="tokens" value="<?= htmlspecialchars((string)($package['tokens'] ?? 0)) ?>">
            </label>
        </div>
        <div>
            <label>价格：
                <input type="text" name="price" value="<?= htmlspecialchars((string)($package['price'] ?? '0')) ?>">
            </label>
        </div>
        <div>
            <label>VIP 价：
                <input type="text" name="vip_price" value="<?= htmlspecialchars((string)($package['vip_price'] ?? '')) ?>">
            </label>
        </div>
        <div>
            <label>折扣比例：
                <input type="text" name="discount_rate" value="<?= htmlspecialchars((string)($package['discount_rate'] ?? '')) ?>">
            </label>
        </div>
        <div>
            <label>赠送代币：
                <input type="number" name="bonus_tokens" value="<?= htmlspecialchars((string)($package['bonus_tokens'] ?? 0)) ?>">
            </label>
        </div>
        <div>
            <label>热门：
                <input type="checkbox" name="is_hot" value="1" <?= !empty($package['is_hot']) ? 'checked' : '' ?>>
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
            <label>描述：
                <textarea name="description" rows="4" cols="40"><?= htmlspecialchars((string)($package['description'] ?? '')) ?></textarea>
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

