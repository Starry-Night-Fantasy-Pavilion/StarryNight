<?php
// 创建知识库
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>创建知识库</title>
</head>
<body>
    <h1>创建知识库</h1>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars((string)$error) ?></p>
    <?php endif; ?>

    <form method="post">
        <div>
            <label>标题：
                <input type="text" name="title" required>
            </label>
        </div>
        <div>
            <label>描述：
                <textarea name="description" rows="4" cols="40"></textarea>
            </label>
        </div>
        <div>
            <label>分类：
                <select name="category">
                    <option value="">请选择</option>
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
            <label>标签：
                <input type="text" name="tags" placeholder="用逗号分隔">
            </label>
        </div>
        <div>
            <label>可见性：
                <select name="visibility">
                    <option value="private">私有</option>
                    <option value="public">公开</option>
                </select>
            </label>
        </div>
        <div>
            <label>价格：
                <input type="text" name="price" value="0.00">
            </label>
        </div>
        <div>
            <label>状态：
                <select name="status">
                    <option value="draft">草稿</option>
                    <option value="published">已发布</option>
                </select>
            </label>
        </div>

        <button type="submit">保存</button>
    </form>
</body>
</html>

