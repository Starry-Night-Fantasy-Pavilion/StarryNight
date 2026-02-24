<?php
// 知识库分类管理
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>知识库分类</title>
</head>
<body>
    <h1>知识库分类</h1>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars((string)$error) ?></p>
    <?php endif; ?>

    <form method="post">
        <div>
            <label>名称：
                <input type="text" name="name" required>
            </label>
        </div>
        <div>
            <label>描述：
                <textarea name="description" rows="3" cols="40"></textarea>
            </label>
        </div>
        <div>
            <label>图标：
                <input type="text" name="icon">
            </label>
        </div>
        <div>
            <label>父级：
                <select name="parent_id">
                    <option value="0">无</option>
                    <?php if (!empty($categories) && is_iterable($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars((string)($cat['id'] ?? '')) ?>">
                                <?= htmlspecialchars((string)($cat['name'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </label>
        </div>
        <div>
            <label>排序：
                <input type="number" name="sort_order" value="0">
            </label>
        </div>
        <div>
            <label>启用：
                <input type="checkbox" name="is_active" value="1" checked>
            </label>
        </div>
        <button type="submit">新增分类</button>
    </form>

    <h2>分类列表</h2>
    <ul>
        <?php if (!empty($categories) && is_iterable($categories)): ?>
            <?php foreach ($categories as $cat): ?>
                <li>
                    <?= htmlspecialchars((string)($cat['name'] ?? '')) ?>
                    (ID: <?= htmlspecialchars((string)($cat['id'] ?? '')) ?>)
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li>暂无分类</li>
        <?php endif; ?>
    </ul>
</body>
</html>

