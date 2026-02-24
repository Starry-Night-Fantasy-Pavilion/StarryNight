<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<div class="card">
    <div class="card-header"><h2 style="margin:0;"><?= $item ? '编辑公告' : '发布公告' ?></h2></div>
    <div class="card-body">
        <form method="POST" id="announcementForm">
            <div class="mb-3">
                <label class="form-label">分类</label>
                <select name="category_id" class="form-control" style="width:200px;">
                    <option value="">无分类</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (int)($item['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">标题</label>
                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($item['title'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">内容（富文本）</label>
                <textarea name="content" id="announcementContent" class="form-control" rows="12" required><?= htmlspecialchars($item['content'] ?? '') ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">发布时间（留空=立即）</label>
                    <input type="datetime-local" name="publish_at" class="form-control" value="<?= !empty($item['publish_at']) ? date('Y-m-d\TH:i', strtotime($item['publish_at'])) : '' ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">状态</label>
                    <select name="status" class="form-control">
                        <option value="enabled" <?= ($item['status'] ?? 'enabled') === 'enabled' ? 'selected' : '' ?>>启用</option>
                        <option value="disabled" <?= ($item['status'] ?? '') === 'disabled' ? 'selected' : '' ?>>禁用</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">排序</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int)($item['sort_order'] ?? 0) ?>">
                </div>
            </div>
            <div class="mb-3">
                <label><input type="checkbox" name="is_pinned" value="1" <?= !empty($item['is_pinned']) ? 'checked' : '' ?>> 置顶</label>
                <label class="ms-3"><input type="checkbox" name="is_popup" value="1" <?= !empty($item['is_popup']) ? 'checked' : '' ?>> 弹窗显示</label>
            </div>
            <button type="submit" class="btn btn-primary">保存</button>
            <a href="/<?= $adminPrefix ?>/announcement/list" class="btn btn-secondary">返回</a>
        </form>
    </div>
</div>
<script>
tinymce.init({
    selector: '#announcementContent',
    height: 450,
    menubar: false,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | formatselect | ' +
        'bold italic backcolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'removeformat | help',
    language: 'zh_CN',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }'
});
</script>