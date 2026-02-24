<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $track ? '编辑音乐' : '添加音乐'; ?></title>
    <link rel="stylesheet" href="/static/admin/css/plugin-modal.css">
</head>
<body>
<div class="admin-container">
    <div class="admin-header">
        <h2 class="admin-title"><?php echo $track ? '编辑音乐' : '添加音乐'; ?></h2>
    </div>
    
    <form class="settings-form" method="post" action="/admin/music/tracks/save" enctype="multipart/form-data">
        <?php if ($track): ?>
            <input type="hidden" name="id" value="<?php echo $track['id']; ?>">
        <?php endif; ?>
        
        <div class="form-section">
            <h3 class="section-title">基本信息</h3>
            
            <div class="form-group">
                <label class="form-label">音乐标题 *</label>
                <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($track['title'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">艺术家 *</label>
                <input type="text" class="form-control" name="artist" value="<?php echo htmlspecialchars($track['artist'] ?? ''); ?>" required>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">专辑</label>
                        <select class="form-control" name="album_id">
                            <option value="">无专辑</option>
                            <?php foreach ($albums as $album): ?>
                                <option value="<?php echo $album['id']; ?>" <?php echo ($track['album_id'] ?? '') == $album['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($album['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">风格</label>
                        <select class="form-control" name="genre">
                            <option value="">请选择</option>
                            <?php foreach ($genres as $genre): ?>
                                <option value="<?php echo htmlspecialchars($genre); ?>" <?php echo ($track['genre'] ?? '') === $genre ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($genre); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">情绪</label>
                        <select class="form-control" name="mood">
                            <option value="">请选择</option>
                            <?php foreach ($moods as $mood): ?>
                                <option value="<?php echo htmlspecialchars($mood); ?>" <?php echo ($track['mood'] ?? '') === $mood ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($mood); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">时长</label>
                        <div class="d-flex align-items-center" style="gap: 10px;">
                            <input type="number" class="form-control" name="duration_minutes" value="<?php echo $track['duration'] ? floor($track['duration'] / 60) : ''; ?>" min="0" max="99" placeholder="分" style="width: 80px;">
                            <span>:</span>
                            <input type="number" class="form-control" name="duration_seconds" value="<?php echo $track['duration'] ? $track['duration'] % 60 : ''; ?>" min="0" max="59" placeholder="秒" style="width: 80px;">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">简介</label>
                <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($track['description'] ?? ''); ?></textarea>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="section-title">媒体文件</h3>
            
            <div class="form-group">
                <label class="form-label">音乐文件 *</label>
                <input type="file" class="form-control" name="audio_file" accept="audio/*">
                <div class="form-description">支持 MP3、WAV、FLAC 格式，最大 50MB</div>
                <?php if ($track && !empty($track['file_path'])): ?>
                    <div class="mt-2 text-muted" style="margin-top: 10px;">
                        当前文件: <?php echo htmlspecialchars(basename($track['file_path'])); ?>
                        <?php if (isset($track['file_size'])): ?>
                            (<?php echo $this->formatFileSize($track['file_size']); ?>)
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label class="form-label">封面图片</label>
                <input type="file" class="form-control" name="cover_image" accept="image/*">
                <div class="form-description">支持 JPG、PNG 格式，最大 5MB</div>
                <?php if ($track && !empty($track['cover_image'])): ?>
                    <div class="d-flex align-items-center" style="gap: 15px; margin-top: 15px;">
                        <img src="<?php echo htmlspecialchars($track['cover_image']); ?>" alt="封面" style="width: 80px; height: 80px; border-radius: 8px; object-fit: cover; border: 1px solid var(--glass-border);">
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
                    <option value="draft" <?php echo ($track['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>草稿</option>
                    <option value="pending" <?php echo ($track['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>待审核</option>
                    <option value="published" <?php echo ($track['status'] ?? '') === 'published' ? 'selected' : ''; ?>>已发布</option>
                </select>
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="featured" name="featured" value="1" <?php echo ($track['featured'] ?? 0) ? 'checked' : ''; ?>>
                    <label for="featured">设为推荐</label>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $track ? '保存修改' : '添加音乐'; ?></button>
            <button type="button" class="btn btn-secondary" onclick="history.back()">取消</button>
        </div>
    </form>
</div>

<script>
    function removeCover() {
        if (confirm('确定要移除当前封面吗？')) {
            const coverSection = document.querySelector('.d-flex.align-items-center[style*="margin-top: 15px"]');
            if (coverSection) {
                coverSection.remove();
            }
            
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
