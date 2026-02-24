<?php
// 编辑知识条目
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>编辑知识条目</title>
</head>
<body>
    <h1>编辑知识条目</h1>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars((string)$error) ?></p>
    <?php endif; ?>

    <?php if (!empty($item)): ?>
        <form method="post" enctype="multipart/form-data">
            <div>
                <label>标题：
                    <input type="text" name="title" value="<?= htmlspecialchars((string)($item['title'] ?? '')) ?>" required>
                </label>
            </div>
            <div>
                <label>内容：
                    <textarea name="content" rows="6" cols="60"><?= htmlspecialchars((string)($item['content'] ?? '')) ?></textarea>
                </label>
            </div>
            <div>
                <label>内容类型：
                    <select name="content_type">
                        <?php $ctype = $item['content_type'] ?? 'text'; ?>
                        <option value="text" <?= $ctype === 'text' ? 'selected' : '' ?>>文本</option>
                        <option value="markdown" <?= $ctype === 'markdown' ? 'selected' : '' ?>>Markdown</option>
                        <option value="html" <?= $ctype === 'html' ? 'selected' : '' ?>>HTML</option>
                    </select>
                </label>
            </div>
            <div>
                <label>标签：
                    <input type="text" name="tags" value="<?= htmlspecialchars((string)($item['tags'] ?? '')) ?>">
                </label>
            </div>
            <div>
                <label>排序：
                    <input type="number" name="order_index" value="<?= htmlspecialchars((string)($item['order_index'] ?? 0)) ?>">
                </label>
            </div>
            <div>
                <label>附件：
                    <input type="file" name="file">
                </label>
            </div>

            <button type="submit">保存</button>
        </form>
    <?php else: ?>
        <p>条目不存在。</p>
    <?php endif; ?>
</body>
</html>

