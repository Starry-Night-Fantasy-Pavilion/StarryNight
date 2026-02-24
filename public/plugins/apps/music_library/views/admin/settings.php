<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>音乐库设置</title>
    <link rel="stylesheet" href="/static/admin/css/plugin-modal.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h2 class="admin-title">音乐库设置</h2>
        </div>
        
        <form class="settings-form" method="post" action="/admin/music/settings/save">
            <?= csrf_field() ?>
            
            <div class="form-section">
                <h3 class="section-title">基本设置</h3>
                
                <div class="form-group">
                    <label class="form-label">每页显示数量</label>
                    <input type="number" class="form-control" name="items_per_page" value="<?php echo htmlspecialchars($settings['items_per_page'] ?? 20); ?>" min="5" max="100">
                    <div class="form-description">前台页面每页显示的音乐数量</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">默认音乐风格</label>
                    <select class="form-control" name="default_genre">
                        <option value="">全部</option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?php echo htmlspecialchars($genre); ?>" <?php echo ($settings['default_genre'] ?? '') === $genre ? 'selected' : ''; ?>><?php echo htmlspecialchars($genre); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-description">前台页面默认显示的音乐风格</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">允许用户上传</label>
                    <div class="form-check">
                        <input type="checkbox" id="allow_user_upload" name="allow_user_upload" value="1" <?php echo ($settings['allow_user_upload'] ?? 0) ? 'checked' : ''; ?>>
                        <label for="allow_user_upload">允许注册用户上传音乐</label>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">下载设置</h3>
                
                <div class="form-group">
                    <label class="form-label">下载权限</label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" id="download_free" name="download_free" value="1" <?php echo ($settings['download_free'] ?? 1) ? 'checked' : ''; ?>>
                                <label for="download_free">免费用户可下载</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" id="download_vip" name="download_vip" value="1" <?php echo ($settings['download_vip'] ?? 1) ? 'checked' : ''; ?>>
                                <label for="download_vip">VIP用户可下载</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" id="download_coins" name="download_coins" value="1" <?php echo ($settings['download_coins'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="download_coins">星夜币购买下载</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">下载价格（星夜币）</label>
                    <input type="number" class="form-control" name="download_price" value="<?php echo htmlspecialchars($settings['download_price'] ?? 10); ?>" min="0" max="1000">
                    <div class="form-description">单次下载所需的星夜币数量</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">每日下载限制</label>
                    <input type="number" class="form-control" name="daily_download_limit" value="<?php echo htmlspecialchars($settings['daily_download_limit'] ?? 10); ?>" min="0" max="100">
                    <div class="form-description">普通用户每日最大下载次数，0表示无限制</div>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">AI功能设置</h3>
                
                <div class="form-group">
                    <label class="form-label">启用AI拆歌</label>
                    <div class="form-check">
                        <input type="checkbox" id="ai_deconstruction_enabled" name="ai_deconstruction_enabled" value="1" <?php echo ($settings['ai_deconstruction_enabled'] ?? 1) ? 'checked' : ''; ?>>
                        <label for="ai_deconstruction_enabled">启用音乐拆解功能</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">启用AI仿写</label>
                    <div class="form-check">
                        <input type="checkbox" id="ai_imitation_enabled" name="ai_imitation_enabled" value="1" <?php echo ($settings['ai_imitation_enabled'] ?? 1) ? 'checked' : ''; ?>>
                        <label for="ai_imitation_enabled">启用音乐仿写功能</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">AI使用权限</label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" id="ai_free" name="ai_free" value="1" <?php echo ($settings['ai_free'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="ai_free">免费用户可使用</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" id="ai_vip" name="ai_vip" value="1" <?php echo ($settings['ai_vip'] ?? 1) ? 'checked' : ''; ?>>
                                <label for="ai_vip">VIP用户可使用</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" id="ai_coins" name="ai_coins" value="1" <?php echo ($settings['ai_coins'] ?? 1) ? 'checked' : ''; ?>>
                                <label for="ai_coins">星夜币购买使用</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">AI使用价格（星夜币）</label>
                    <input type="number" class="form-control" name="ai_usage_price" value="<?php echo htmlspecialchars($settings['ai_usage_price'] ?? 5); ?>" min="0" max="1000">
                    <div class="form-description">单次AI功能使用所需的星夜币数量</div>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">评论设置</h3>
                
                <div class="form-group">
                    <label class="form-label">评论审核</label>
                    <div class="form-check">
                        <input type="checkbox" id="comment_moderation" name="comment_moderation" value="1" <?php echo ($settings['comment_moderation'] ?? 1) ? 'checked' : ''; ?>>
                        <label for="comment_moderation">启用评论审核</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">敏感词过滤</label>
                    <textarea class="form-control" name="comment_filter" rows="4" placeholder="每行一个敏感词"><?php echo htmlspecialchars($settings['comment_filter'] ?? ''); ?></textarea>
                    <div class="form-description">评论中包含这些词汇将被自动标记为待审核</div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">保存设置</button>
                <button type="button" class="btn btn-danger" onclick="resetSettings()">重置默认</button>
            </div>
        </form>
    </div>

    <script>
        function resetSettings() {
            if (confirm('确定要重置所有设置为默认值吗？此操作不可撤销。')) {
                fetch('/api/v1/admin/music/settings/reset', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('设置已重置');
                        location.reload();
                    } else {
                        alert('重置失败: ' + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>
