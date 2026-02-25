<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<div class="card">
    <div class="card-header"><h2 style="margin:0;"><?= $item ? '编辑通知' : '发布通知' ?></h2></div>
    <div class="card-body">
        <form method="POST" id="noticeForm">
            <div class="mb-3">
                <label class="form-label">内容（支持富文本）</label>
                <textarea name="content" id="noticeContent" class="form-control" rows="10" required><?= htmlspecialchars($item['content'] ?? '') ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">优先权重（0-100，越高出现越多次）</label>
                    <input type="number" name="priority" class="form-control" min="0" max="100"
                           value="<?= (int)($item['priority'] ?? 0) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">循环权重（可选）</label>
                    <input type="number" name="loop_weight" class="form-control" min="1" max="9"
                           value="<?= isset($item['loop_weight']) ? (int)$item['loop_weight'] : '' ?>"
                           placeholder="留空则按优先级自动计算循环次数">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">显示开始时间</label>
                    <input type="datetime-local" name="display_from" class="form-control" value="<?= !empty($item['display_from']) ? date('Y-m-d\TH:i', strtotime($item['display_from'])) : '' ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">显示结束时间</label>
                    <input type="datetime-local" name="display_to" class="form-control" value="<?= !empty($item['display_to']) ? date('Y-m-d\TH:i', strtotime($item['display_to'])) : '' ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">语言</label>
                    <select name="lang" class="form-control">
                        <option value="zh-CN" <?= ($item['lang'] ?? 'zh-CN') === 'zh-CN' ? 'selected' : '' ?>>zh-CN</option>
                        <option value="en" <?= ($item['lang'] ?? '') === 'en' ? 'selected' : '' ?>>en</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">状态</label>
                    <select name="status" class="form-control">
                        <option value="enabled" <?= ($item['status'] ?? 'enabled') === 'enabled' ? 'selected' : '' ?>>启用</option>
                        <option value="disabled" <?= ($item['status'] ?? '') === 'disabled' ? 'selected' : '' ?>>禁用</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">保存</button>
            <a href="/<?= $adminPrefix ?>/notice/list" class="btn btn-secondary">返回</a>
        </form>
    </div>
</div>
<script>
tinymce.init({
    selector: '#noticeContent',
    height: 400,
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