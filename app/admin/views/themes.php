<?php $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/'); ?>

<style>
    .tm-page { display: flex; flex-direction: column; gap: 16px; }
    .tm-toolbar { display: flex; gap: 12px; align-items: center; justify-content: space-between; flex-wrap: wrap; }
    .tm-title { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .tm-title h2 { margin: 0; font-size: 18px; font-weight: 500; color: #fff; }
    .tm-links { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .tm-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 16px; border-radius: 12px; border: 1px solid var(--glass-border); background: rgba(255, 255, 255, 0.06); color: rgba(255, 255, 255, 0.9); text-decoration: none; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); font-size: 13px; font-weight: 600; letter-spacing: 0.3px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15); }
    .tm-btn:hover { background: rgba(255, 255, 255, 0.1); border-color: rgba(255,255,255,0.3); color: #fff; transform: translateY(-2px) scale(1.05); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2), 0 0 20px rgba(0, 242, 255, 0.1); }
    .tm-btn-primary { background: rgba(0, 242, 255, 0.18); border-color: rgba(0, 242, 255, 0.35); color: #fff; box-shadow: 0 2px 8px rgba(0, 242, 255, 0.25); }
    .tm-btn-primary:hover { background: rgba(0, 242, 255, 0.25); border-color: rgba(0, 242, 255, 0.5); box-shadow: 0 4px 16px rgba(0, 242, 255, 0.4), 0 0 30px rgba(0, 242, 255, 0.2); }
    .tm-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .tm-summary { color: rgba(255, 255, 255, 0.7); font-size: 13px; }
    .tm-alert { border: 1px solid rgba(255, 71, 87, 0.25); background: rgba(255, 71, 87, 0.08); color: rgba(255, 255, 255, 0.92); padding: 10px 14px; border-radius: 12px; }
    .tm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; }
    .tm-card { border: 1px solid var(--glass-border); border-radius: 20px; overflow: hidden; background: rgba(255, 255, 255, 0.04); display: flex; flex-direction: column; min-height: 0; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.05) inset; }
    .tm-card:hover { border-color: rgba(255, 255, 255, 0.2); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.1) inset, 0 0 40px rgba(0, 242, 255, 0.1); background: rgba(255, 255, 255, 0.06); transform: translateY(-4px); }
    .tm-card-active { border-color: rgba(0, 242, 255, 0.4); box-shadow: 0 0 0 2px rgba(0, 242, 255, 0.2) inset, 0 8px 32px rgba(0, 242, 255, 0.2), 0 0 50px rgba(0, 242, 255, 0.15); }
    .tm-preview { position: relative; width: 100%; aspect-ratio: 16/9; background: rgba(255,255,255,0.04); display: flex; align-items: center; justify-content: center; overflow: hidden; transition: all 0.3s ease; }
    .tm-card:hover .tm-preview { background: rgba(255,255,255,0.06); }
    .tm-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .tm-preview-placeholder { color: rgba(255, 255, 255, 0.55); font-size: 13px; }
    .tm-body { padding: 12px; display: flex; flex-direction: column; gap: 10px; min-height: 0; }
    .tm-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
    .tm-name { font-weight: 700; font-size: 15px; color: #fff; line-height: 1.25; }
    .tm-badges { display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
    .tm-badge { display: inline-flex; align-items: center; justify-content: center; padding: 2px 10px; border-radius: 999px; font-size: 12px; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.82); }
    .tm-badge-ok { border-color: rgba(16,185,129,0.3); background: rgba(16,185,129,0.14); color: #b7f7dc; }
    .tm-badge-bad { border-color: rgba(255,71,87,0.28); background: rgba(255,71,87,0.12); color: #ffd0d6; }
    .tm-badge-active { border-color: rgba(0, 242, 255, 0.3); background: rgba(0, 242, 255, 0.12); color: #e8feff; }
    .tm-meta { color: rgba(255, 255, 255, 0.65); font-size: 12px; display: flex; gap: 10px; flex-wrap: wrap; }
    .tm-desc { color: rgba(255, 255, 255, 0.7); font-size: 13px; line-height: 1.55; }
    .tm-missing { color: rgba(255, 255, 255, 0.85); font-size: 12px; border: 1px dashed rgba(255,71,87,0.28); background: rgba(255,71,87,0.08); padding: 10px 12px; border-radius: 14px; }
    .tm-actions { display: flex; gap: 10px; align-items: center; justify-content: space-between; flex-wrap: wrap; margin-top: 2px; }
    .tm-actions form { margin: 0; }
    .tm-actions-right { display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .tm-link { color: rgba(255, 255, 255, 0.7); text-decoration: none; font-size: 12px; border-bottom: 1px dashed rgba(255,255,255,0.22); }
    .tm-link:hover { color: #fff; border-bottom-color: rgba(255,255,255,0.45); }
</style>

<div class="tm-page">
    <?php if (!empty($_GET['error'])): ?>
        <div class="tm-alert">主题激活失败：该主题缺少必需文件（首页/登录 CSS 或模板）。</div>
    <?php endif; ?>

    <div class="tm-toolbar">
        <div class="tm-title">
            <h2>主题管理</h2>
        </div>
        <div class="tm-links">
            <span class="tm-summary">共 <?= count($themes) ?> 个主题</span>
            <button class="tm-btn tm-btn-primary" onclick="showUploadModal()">上传主题</button>
        </div>
    </div>

    <!-- 上传主题模态框 -->
    <div id="uploadModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: rgba(30,30,40,0.95); border-radius: 20px; padding: 30px; max-width: 500px; width: 90%; border: 1px solid rgba(255,255,255,0.2);">
            <h3 style="margin-top: 0; color: #fff;">上传主题</h3>
            <p style="color: rgba(255,255,255,0.7); font-size: 13px; margin-bottom: 20px;">请上传ZIP格式的主题包，主题包应包含theme.json文件</p>
            <form id="uploadForm" method="post" action="/<?= htmlspecialchars($adminPrefix, ENT_QUOTES, 'UTF-8') ?>/themes/upload" enctype="multipart/form-data">
                <input type="file" name="theme_file" accept=".zip" required style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.05); color: #fff; margin-bottom: 20px;">
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="tm-btn" onclick="hideUploadModal()">取消</button>
                    <button type="submit" class="tm-btn tm-btn-primary">上传</button>
                </div>
            </form>
        </div>
    </div>

    <div class="tm-grid">
        <?php foreach ($themes as $t): ?>
            <?php $isActive = $activeThemeId === $t['id']; ?>
            <div class="tm-card <?= $isActive ? 'tm-card-active' : '' ?>">
                <div class="tm-preview">
                    <?php if (!empty($t['preview'])): ?>
                        <img src="<?= htmlspecialchars((string) $t['preview'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                    <?php else: ?>
                        <div class="tm-preview-placeholder">无预览</div>
                    <?php endif; ?>
                </div>
                <div class="tm-body">
                    <div class="tm-head">
                        <div class="tm-name"><?= htmlspecialchars((string) $t['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="tm-badges">
                            <?php if ($t['valid']): ?>
                                <span class="tm-badge tm-badge-ok">可用</span>
                            <?php else: ?>
                                <span class="tm-badge tm-badge-bad">缺文件</span>
                            <?php endif; ?>
                            <?php if ($isActive): ?>
                                <span class="tm-badge tm-badge-active">当前启用</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tm-meta">
                        <span>ID: <?= htmlspecialchars((string) $t['id'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($t['version'])): ?>
                            <span>v<?= htmlspecialchars((string) $t['version'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?php if (!empty($t['author'])): ?>
                            <span>作者：<?= htmlspecialchars((string) $t['author'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($t['description'])): ?>
                        <div class="tm-desc"><?= htmlspecialchars((string) $t['description'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <?php if (!$t['valid'] && !empty($t['missing'])): ?>
                        <div class="tm-missing">缺失：<?= htmlspecialchars(implode(', ', $t['missing']), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <div class="tm-actions">
                        <form method="post" action="/<?= htmlspecialchars($adminPrefix, ENT_QUOTES, 'UTF-8') ?>/themes/activate" style="display: inline;">
                            <input type="hidden" name="theme_id" value="<?= htmlspecialchars((string) $t['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <button class="tm-btn tm-btn-primary" type="submit" <?= (!$t['valid'] || $isActive) ? 'disabled' : '' ?>><?= $isActive ? '已启用' : '激活' ?></button>
                        </form>
                        <div class="tm-actions-right">
                            <a class="tm-link" href="/<?= htmlspecialchars($adminPrefix, ENT_QUOTES, 'UTF-8') ?>/themes/preview?theme_id=<?= urlencode((string) $t['id']) ?>" target="_blank">预览</a>
                            <a class="tm-link" href="/<?= htmlspecialchars($adminPrefix, ENT_QUOTES, 'UTF-8') ?>/themes/config?theme_id=<?= urlencode((string) $t['id']) ?>">配置</a>
                            <?php if (!empty($t['preview'])): ?>
                                <a class="tm-link" href="<?= htmlspecialchars((string) $t['preview'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">预览图</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function showUploadModal() {
    document.getElementById('uploadModal').style.display = 'flex';
}
function hideUploadModal() {
    document.getElementById('uploadModal').style.display = 'none';
}
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('主题上传成功！');
            location.reload();
        } else {
            alert('上传失败：' + (data.message || '未知错误'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('上传失败，请稍后重试');
    });
});
</script>
