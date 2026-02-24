<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $album ? '编辑专辑' : '添加专辑'; ?></title>
    <link rel="stylesheet" href="/static/admin/css/plugin-modal.css">
</head>
<body>
<div class="admin-container">
    <div class="admin-header">
        <h2 class="admin-title"><?php echo $album ? '编辑专辑' : '添加专辑'; ?></h2>
    </div>
    
    <form class="settings-form" method="post" action="/admin/music/albums/save" enctype="multipart/form-data">
        <?php if ($album): ?>
            <input type="hidden" name="id" value="<?php echo $album['id']; ?>">
        <?php endif; ?>
        
        <div class="form-section">
            <h3 class="section-title">基本信息</h3>
            
            <div class="form-group">
                <label class="form-label">专辑名称 *</label>
                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($album['name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">艺术家 *</label>
                <input type="text" class="form-control" name="artist" value="<?php echo htmlspecialchars($album['artist'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">风格</label>
                <select class="form-control" name="genre">
                    <option value="">请选择</option>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?php echo htmlspecialchars($genre); ?>" <?php echo ($album['genre'] ?? '') === $genre ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($genre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">发行时间</label>
                <input type="date" class="form-control" name="release_date" value="<?php echo $album['release_date'] ?? date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">简介</label>
                <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($album['description'] ?? ''); ?></textarea>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="section-title">封面图片</h3>
            
            <div class="form-group">
                <label class="form-label">上传封面</label>
                <input type="file" class="form-control" name="cover_image" accept="image/*">
                <div class="form-description">支持 JPG、PNG 格式，推荐尺寸 1000x1000，最大 5MB</div>
                <?php if ($album && !empty($album['cover_image'])): ?>
                    <div class="mt-2 d-flex align-items-center" style="gap: 15px; margin-top: 15px;">
                        <img src="<?php echo htmlspecialchars($album['cover_image']); ?>" alt="封面" style="width: 80px; height: 80px; border-radius: 8px; object-fit: cover; border: 1px solid var(--glass-border);">
                        <div>
                            <div class="text-muted">当前封面</div>
                            <button type="button" class="btn btn-sm btn-danger mt-1" onclick="removeCover()">移除</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="section-title">发布设置</h3>
            
            <div class="form-group">
                <label class="form-label">状态</label>
                <select class="form-control" name="status">
                    <option value="draft" <?php echo ($album['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>草稿</option>
                    <option value="pending" <?php echo ($album['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>待审核</option>
                    <option value="published" <?php echo ($album['status'] ?? '') === 'published' ? 'selected' : ''; ?>>已发布</option>
                </select>
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="featured" name="featured" value="1" <?php echo ($album['featured'] ?? 0) ? 'checked' : ''; ?>>
                    <label for="featured">设为推荐</label>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $album ? '保存修改' : '添加专辑'; ?></button>
            <button type="button" class="btn btn-secondary" onclick="history.back()">取消</button>
        </div>
    </form>
</div>

<script>
    function removeCover() {
        if (confirm('确定要移除当前封面吗？')) {
            const currentCover = document.querySelector('.mt-2');
            if (currentCover) {
                currentCover.remove();
            }
            
            // 添加隐藏字段标记移除封面
            const form = document.querySelector('.settings-form');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'remove_cover';
            input.value = '1';
            form.appendChild(input);
        }
    }
</script>
</body>
</html>
