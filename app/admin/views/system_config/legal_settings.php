<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<div class="card">
    <div class="card-header"><h2 class="sysconfig-card-title">协议设置</h2></div>
    <div class="card-body">
        <form method="POST" class="form-horizontal" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">用户协议 TXT 文件（仅支持 .txt，存储到 /static/errors/txt）</label>
                <input type="file" name="user_agreement_file" class="form-control" accept=".txt,text/plain">
                <?php if (!empty($data['user_agreement_txt_path'])): ?>
                    <small class="text-muted d-block sysconfig-helpblock">
                        当前文件：<?= htmlspecialchars($data['user_agreement_txt_path']) ?> ，
                        <a href="/legal/user-agreement" target="_blank">点击预览用户协议</a>
                    </small>
                <?php else: ?>
                    <small class="text-muted">未配置则前台不展示/或提示未配置。</small>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">隐私协议 TXT 文件（仅支持 .txt，存储到 /static/errors/txt）</label>
                <input type="file" name="privacy_policy_file" class="form-control" accept=".txt,text/plain">
                <?php if (!empty($data['privacy_policy_txt_path'])): ?>
                    <small class="text-muted d-block sysconfig-helpblock">
                        当前文件：<?= htmlspecialchars($data['privacy_policy_txt_path']) ?> ，
                        <a href="/legal/privacy-policy" target="_blank">点击预览隐私协议</a>
                    </small>
                <?php else: ?>
                    <small class="text-muted">未配置则前台不展示/或提示未配置。</small>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">保存</button>
        </form>
    </div>
</div>

