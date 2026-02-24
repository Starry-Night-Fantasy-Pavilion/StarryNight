<?php
// 创建知识条目
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>创建知识条目</title>
</head>
<body>
    <h1>创建知识条目</h1>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars((string)$error) ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div>
            <label>标题：
                <input type="text" name="title" required>
            </label>
        </div>
        <div>
            <label>内容：
                <textarea name="content" rows="6" cols="60"></textarea>
            </label>
        </div>
        <div>
            <label>内容类型：
                <select name="content_type">
                    <option value="text">文本</option>
                    <option value="markdown">Markdown</option>
                    <option value="html">HTML</option>
                </select>
            </label>
        </div>
        <div>
            <label>标签：
                <input type="text" name="tags">
            </label>
        </div>
        <div>
            <label>排序：
                <input type="number" name="order_index" value="0">
            </label>
        </div>
        <div>
            <label>附件：
                <input type="file" name="file">
            </label>
        </div>

        <button type="submit">保存</button>
    </form>
</body>
</html>

