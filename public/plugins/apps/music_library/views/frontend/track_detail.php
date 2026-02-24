<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($track['title']); ?> - <?php echo htmlspecialchars($track['artist']); ?> - 星夜阁音乐库</title>
    <link rel="stylesheet" href="/assets/web/css/music-player.css">
    <style>
        .track-detail {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .track-header {
            display: flex;
            gap: 30px;
            margin-bottom: 40px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .track-cover {
            width: 300px;
            height: 300px;
            flex-shrink: 0;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
        }
        .track-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .track-info {
            flex: 1;
            padding: 30px;
        }
        .track-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        .track-artist {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 15px;
        }
        .track-album {
            font-size: 1rem;
            color: #888;
            margin-bottom: 20px;
        }
        .track-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }
        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .meta-label {
            font-size: 0.9rem;
            color: #888;
        }
        .meta-value {
            font-size: 1.1rem;
            font-weight: 500;
            color: #333;
        }
        .track-stats {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.1rem;
        }
        .stat-icon {
            font-size: 1.3rem;
        }
        .track-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        .action-button {
            padding: 12px 24px;
            border-radius: 25px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .play-button {
            background: #667eea;
            color: white;
        }
        .play-button:hover {
            background: #5a67d8;
        }
        .download-button {
            background: #28a745;
            color: white;
        }
        .download-button:hover {
            background: #218838;
        }
        .playlist-button {
            background: #ffc107;
            color: #333;
        }
        .playlist-button:hover {
            background: #e0a800;
        }
        .section {
            margin-bottom: 40px;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .track-description {
            background: white;
            padding: 25px;
            border-radius: 8px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .tabs {
            display: flex;
            border-bottom: 2px solid #eee;
            margin-bottom: 20px;
        }
        .tab {
            padding: 12px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .comments-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
        }
        .comment-form {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .comment-textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: vertical;
            font-family: inherit;
            font-size: 14px;
        }
        .comment-submit {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 500;
            margin-top: 10px;
        }
        .comment-submit:hover {
            background: #5a67d8;
        }
        .comment-list {
            max-height: 500px;
            overflow-y: auto;
        }
        .comment {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .comment:last-child {
            border-bottom: none;
        }
        .comment-author {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .comment-content {
            line-height: 1.5;
            margin-bottom: 10px;
        }
        .comment-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #888;
        }
        .like-button {
            background: none;
            border: none;
            color: #888;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .like-button:hover {
            color: #e74c3c;
        }
        .related-tracks {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .related-track {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .related-track:hover {
            transform: translateY(-5px);
        }
        .related-cover {
            width: 100%;
            height: 150px;
            object-fit: cover;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }
        .related-info {
            padding: 15px;
        }
        .related-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .related-artist {
            color: #666;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ai-tools {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .tool-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .tool-button {
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .tool-button:hover {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="track-detail">
        <div class="track-header">
            <div class="track-cover">
                <?php if ($track['cover_image']): ?>
                    <img src="<?php echo htmlspecialchars($track['cover_image']); ?>" alt="<?php echo htmlspecialchars($track['title']); ?>">
                <?php else: ?>
                    <span>♪</span>
                <?php endif; ?>
            </div>
            <div class="track-info">
                <h1 class="track-title"><?php echo htmlspecialchars($track['title']); ?></h1>
                <p class="track-artist"><?php echo htmlspecialchars($track['artist']); ?></p>
                <?php if ($album): ?>
                    <p class="track-album">专辑: <?php echo htmlspecialchars($album['name']); ?></p>
                <?php endif; ?>
                
                <div class="track-meta">
                    <div class="meta-item">
                        <span class="meta-label">风格</span>
                        <span class="meta-value"><?php echo htmlspecialchars($track['genre'] ?? '未知'); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">情绪</span>
                        <span class="meta-value"><?php echo htmlspecialchars($track['mood'] ?? '未知'); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">时长</span>
                        <span class="meta-value"><?php echo $this->formatDuration($track['duration']); ?></span>
                    </div>
                </div>
                
                <div class="track-stats">
                    <div class="stat-item">
                        <span class="stat-icon">▶</span>
                        <span><?php echo number_format($track['plays']); ?> 播放</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-icon">♥</span>
                        <span><?php echo number_format($track['likes']); ?> 喜欢</span>
                    </div>
                </div>
                
                <div class="track-actions">
                    <button class="action-button play-button" onclick="playTrack(<?php echo $track['id']; ?>)">
                        ▶ 播放
                    </button>
                    <button class="action-button download-button" onclick="downloadTrack(<?php echo $track['id']; ?>)">
                        ⬇ 下载
                    </button>
                    <button class="action-button playlist-button" onclick="addToPlaylist(<?php echo $track['id']; ?>)">
                        + 添加到歌单
                    </button>
                </div>
            </div>
        </div>

        <?php if ($track['description']): ?>
            <div class="section">
                <h2 class="section-title">简介</h2>
                <div class="track-description">
                    <?php echo nl2br(htmlspecialchars($track['description'])); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="ai-tools">
            <h2 class="section-title">AI 工具</h2>
            <div class="tool-buttons">
                <button class="tool-button" onclick="deconstructTrack(<?php echo $track['id']; ?>, 'melody')">
                    🎵 拆解旋律
                </button>
                <button class="tool-button" onclick="deconstructTrack(<?php echo $track['id']; ?>, 'lyrics')">
                    📝 拆解歌词
                </button>
                <button class="tool-button" onclick="deconstructTrack(<?php echo $track['id']; ?>, 'arrangement')">
                    🎼 拆解编曲
                </button>
                <button class="tool-button" onclick="deconstructTrack(<?php echo $track['id']; ?>, 'structure')">
                    🏗️ 拆解结构
                </button>
                <button class="tool-button" onclick="aiImitate(<?php echo $track['id']; ?>)">
                    🤖 AI 仿写
                </button>
            </div>
        </div>

        <div class="section">
            <div class="tabs">
                <button class="tab active" onclick="showTab('comments')">评论</button>
                <button class="tab" onclick="showTab('related')">相关音乐</button>
            </div>
            
            <div id="comments" class="tab-content active">
                <div class="comments-section">
                    <div class="comment-form">
                        <textarea class="comment-textarea" placeholder="写下您的评论..."></textarea>
                        <button class="comment-submit" onclick="submitComment()">发表评论</button>
                    </div>
                    
                    <div class="comment-list">
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment">
                                <div class="comment-author"><?php echo htmlspecialchars($comment['username'] ?? '匿名用户'); ?></div>
                                <div class="comment-content"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></div>
                                <div class="comment-meta">
                                    <span><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></span>
                                    <button class="like-button" onclick="likeComment(<?php echo $comment['id']; ?>)">
                                        <span>♥</span>
                                        <span><?php echo $comment['likes']; ?></span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div id="related" class="tab-content">
                <div class="related-tracks">
                    <?php foreach ($relatedTracks as $relatedTrack): ?>
                        <div class="related-track" onclick="playTrack(<?php echo $relatedTrack['id']; ?>)">
                            <div class="related-cover">
                                <?php if ($relatedTrack['cover_image']): ?>
                                    <img src="<?php echo htmlspecialchars($relatedTrack['cover_image']); ?>" alt="<?php echo htmlspecialchars($relatedTrack['title']); ?>">
                                <?php else: ?>
                                    <span>♪</span>
                                <?php endif; ?>
                            </div>
                            <div class="related-info">
                                <h3 class="related-title"><?php echo htmlspecialchars($relatedTrack['title']); ?></h3>
                                <p class="related-artist"><?php echo htmlspecialchars($relatedTrack['artist']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const trackId = <?php echo $track['id']; ?>;
        
        function playTrack(id) {
            window.location.href = '/music/player/' + id;
        }
        
        function downloadTrack(id) {
            // 显示格式选择对话框
            const formats = ['MP3', 'WAV', 'FLAC'];
            const format = prompt('请选择下载格式:\n' + formats.map((f, i) => `${i+1}. ${f}`).join('\n'), '1');
            
            if (format && formats[parseInt(format)-1]) {
                window.location.href = `/api/v1/music/download/${id}/${formats[parseInt(format)-1]}`;
            }
        }
        
        function addToPlaylist(id) {
            // 获取用户歌单列表
            fetch('/api/v1/music/playlists')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        const playlistNames = data.data.map(p => p.name);
                        const playlistName = prompt('请选择歌单:\n' + playlistNames.map((p, i) => `${i+1}. ${p}`).join('\n'), '1');
                        
                        if (playlistName && playlistNames[parseInt(playlistName)-1]) {
                            const playlistId = data.data[parseInt(playlistName)-1].id;
                            fetch(`/api/v1/music/playlist/${playlistId}/add`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    track_id: id
                                })
                            })
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    alert('已添加到歌单');
                                } else {
                                    alert('添加失败: ' + result.message);
                                }
                            });
                        }
                    } else {
                        alert('您还没有创建歌单');
                    }
                });
        }
        
        function deconstructTrack(id, type) {
            fetch('/api/v1/music/deconstruct', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    track_id: id,
                    type: type
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 显示分析结果
                    alert('AI分析完成！\n\n' + JSON.stringify(data.data, null, 2));
                } else {
                    alert('分析失败: ' + data.message);
                }
            });
        }
        
        function aiImitate(id) {
            const segment = prompt('请输入要仿写的音乐片段:');
            if (segment) {
                fetch('/api/v1/music/ai/imitate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        track_id: id,
                        segment: segment
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 显示仿写结果
                        alert('AI仿写完成！\n\n' + JSON.stringify(data.data, null, 2));
                    } else {
                        alert('仿写失败: ' + data.message);
                    }
                });
            }
        }
        
        function submitComment() {
            const content = document.querySelector('.comment-textarea').value.trim();
            if (!content) {
                alert('请输入评论内容');
                return;
            }
            
            fetch('/api/v1/music/comment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    track_id: trackId,
                    content: content
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('评论发表成功');
                    location.reload();
                } else {
                    alert('评论失败: ' + data.message);
                }
            });
        }
        
        function likeComment(commentId) {
            fetch(`/api/v1/music/comment/like/${commentId}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('操作失败');
                }
            });
        }
        
        function showTab(tabName) {
            // 隐藏所有标签内容
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // 移除所有标签的激活状态
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // 显示选中的标签内容
            document.getElementById(tabName).classList.add('active');
            
            // 激活选中的标签
            event.target.classList.add('active');
        }
    </script>
</body>
</html>