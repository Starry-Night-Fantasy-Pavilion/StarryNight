<?php
// 编辑知识库
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>编辑知识库</title>
</head>
<body>
    <h1>编辑知识库</h1>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars((string)$error) ?></p>
    <?php endif; ?>

    <?php if (!empty($knowledgeBase)): ?>
        <form method="post">
            <div>
                <label>标题：
                    <input type="text" name="title" value="<?= htmlspecialchars((string)($knowledgeBase['title'] ?? '')) ?>" required>
                </label>
            </div>
            <div>
                <label>描述：
                    <textarea name="description" rows="4" cols="40"><?= htmlspecialchars((string)($knowledgeBase['description'] ?? '')) ?></textarea>
                </label>
            </div>
            <div>
                <label>分类：
                    <select name="category">
                        <option value="">请选择</option>
                        <?php if (!empty($categories) && is_iterable($categories)): ?>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars((string)($cat['id'] ?? '')) ?>"
                                    <?= (isset($knowledgeBase['category']) && (string)$knowledgeBase['category'] === (string)($cat['id'] ?? '')) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)($cat['name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </label>
            </div>
            <div>
                <label>标签：
                    <input type="text" name="tags" value="<?= htmlspecialchars((string)($knowledgeBase['tags'] ?? '')) ?>">
                </label>
            </div>
            <div>
                <label>可见性：
                    <select name="visibility">
                        <option value="private" <?= (isset($knowledgeBase['visibility']) && $knowledgeBase['visibility'] === 'private') ? 'selected' : '' ?>>私有</option>
                        <option value="public" <?= (isset($knowledgeBase['visibility']) && $knowledgeBase['visibility'] === 'public') ? 'selected' : '' ?>>公开</option>
                    </select>
                </label>
            </div>
            <div>
                <label>价格：
                    <input type="text" name="price" value="<?= htmlspecialchars((string)($knowledgeBase['price'] ?? '0.00')) ?>">
                </label>
            </div>
            <div>
                <label>状态：
                    <select name="status">
                        <option value="draft" <?= (isset($knowledgeBase['status']) && $knowledgeBase['status'] === 'draft') ? 'selected' : '' ?>>草稿</option>
                        <option value="published" <?= (isset($knowledgeBase['status']) && $knowledgeBase['status'] === 'published') ? 'selected' : '' ?>>已发布</option>
                    </select>
                </label>
            </div>

            <button type="submit">保存</button>
        </form>
    <?php else: ?>
        <p>知识库不存在。</p>
    <?php endif; ?>
</body>
</html>

