<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($album['name']); ?> - <?php echo htmlspecialchars($album['artist']); ?> - 星夜阁音乐库</title>
    <link rel="stylesheet" href="/assets/web/css/music-player.css">
    <style>
        .album-detail {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .album-header {
            display: flex;
            gap: 30px;
            margin-bottom: 40px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .album-cover {
            width: 300px;
            height: 300px;
            flex-shrink: 0;
            background: linear-gradient(45deg, #ff9a9e, #fad0c4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
        }
        .album-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .album-info {
            flex: 1;
            padding: 30px;
        }
        .album-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        .album-artist {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 15px;
        }
        .album-meta {
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
        .album-stats {
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
        .album-actions {
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
        .album-description {
            background: white;
            padding: 25px;
            border-radius: 8px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .track-list {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .track-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
            cursor: pointer;
        }
        .track-item:last-child {
            border-bottom: none;
        }
        .track-item:hover {
            background: #f8f9fa;
        }
        .track-number {
            width: 40px;
            color: #888;
            font-weight: 500;
        }
        .track-name {
            flex: 1;
            font-weight: 500;
            color: #333;
        }
        .track-duration {
            color: #888;
            font-size: 0.9rem;
        }
        .track-actions-small {
            display: flex;
            gap: 10px;
            margin-left: 20px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .track-item:hover .track-actions-small {
            opacity: 1;
        }
        .action-icon {
            cursor: pointer;
            color: #666;
        }
        .action-icon:hover {
            color: #667eea;
        }
        .related-albums {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .related-album {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .related-album:hover {
            transform: translateY(-5px);
        }
        .related-cover {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(45deg, #ff9a9e, #fad0c4);
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
    </style>
</head>
<body>
    <div class="album-detail">
        <div class="album-header">
            <div class="album-cover">
                <?php if ($album['cover_image']): ?>
                    <img src="<?php echo htmlspecialchars($album['cover_image']); ?>" alt="<?php echo htmlspecialchars($album['name']); ?>">
                <?php else: ?>
                    <span>💿</span>
                <?php endif; ?>
            </div>
            <div class="album-info">
                <h1 class="album-title"><?php echo htmlspecialchars($album['name']); ?></h1>
                <p class="album-artist"><?php echo htmlspecialchars($album['artist']); ?></p>
                
                <div class="album-meta">
                    <div class="meta-item">
                        <span class="meta-label">发行时间</span>
                        <span class="meta-value"><?php echo date('Y-m-d', strtotime($album['release_date'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">风格</span>
                        <span class="meta-value"><?php echo htmlspecialchars($album['genre'] ?? '未知'); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">曲目数</span>
                        <span class="meta-value"><?php echo count($tracks); ?></span>
                    </div>
                </div>
                
                <div class="album-stats">
                    <div class="stat-item">
                        <span class="stat-icon">▶</span>
                        <span><?php echo number_format($album['plays']); ?> 播放</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-icon">♥</span>
                        <span><?php echo number_format($album['likes']); ?> 喜欢</span>
                    </div>
                </div>
                
                <div class="album-actions">
                    <button class="action-button play-button" onclick="playAlbum(<?php echo $album['id']; ?>)">
                        ▶ 播放全部
                    </button>
                    <button class="action-button download-button" onclick="downloadAlbum(<?php echo $album['id']; ?>)">
                        ⬇ 下载专辑
                    </button>
                </div>
            </div>
        </div>

        <?php if ($album['description']): ?>
            <div class="section">
                <h2 class="section-title">专辑简介</h2>
                <div class="album-description">
                    <?php echo nl2br(htmlspecialchars($album['description'])); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="section">
            <h2 class="section-title">曲目列表</h2>
            <div class="track-list">
                <?php foreach ($tracks as $index => $track): ?>
                    <div class="track-item" onclick="playTrack(<?php echo $track['id']; ?>)">
                        <div class="track-number"><?php echo $index + 1; ?></div>
                        <div class="track-name"><?php echo htmlspecialchars($track['title']); ?></div>
                        <div class="track-duration"><?php echo $this->formatDuration($track['duration']); ?></div>
                        <div class="track-actions-small">
                            <span class="action-icon" onclick="event.stopPropagation(); downloadTrack(<?php echo $track['id']; ?>)">⬇</span>
                            <span class="action-icon" onclick="event.stopPropagation(); addToPlaylist(<?php echo $track['id']; ?>)">+</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">相关专辑</h2>
            <div class="related-albums">
                <?php foreach ($relatedAlbums as $relatedAlbum): ?>
                    <div class="related-album" onclick="location.href='/music/album/<?php echo $relatedAlbum['id']; ?>'">
                        <div class="related-cover">
                            <?php if ($relatedAlbum['cover_image']): ?>
                                <img src="<?php echo htmlspecialchars($relatedAlbum['cover_image']); ?>" alt="<?php echo htmlspecialchars($relatedAlbum['name']); ?>">
                            <?php else: ?>
                                <span>💿</span>
                            <?php endif; ?>
                        </div>
                        <div class="related-info">
                            <h3 class="related-title"><?php echo htmlspecialchars($relatedAlbum['name']); ?></h3>
                            <p class="related-artist"><?php echo htmlspecialchars($relatedAlbum['artist']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function playAlbum(id) {
            // 播放专辑的第一首歌，播放器会自动加载专辑列表
            <?php if (count($tracks) > 0): ?>
                window.location.href = '/music/player/<?php echo $tracks[0]['id']; ?>';
            <?php else: ?>
                alert('专辑中没有歌曲');
            <?php endif; ?>
        }
        
        function playTrack(id) {
            window.location.href = '/music/player/' + id;
        }
        
        function downloadAlbum(id) {
            // 显示格式选择对话框
            const formats = ['ZIP (MP3)', 'ZIP (FLAC)'];
            const format = prompt('请选择下载格式:\n' + formats.map((f, i) => `${i+1}. ${f}`).join('\n'), '1');
            
            if (format && formats[parseInt(format)-1]) {
                window.location.href = `/api/v1/music/download/album/${id}/${parseInt(format) === 1 ? 'mp3' : 'flac'}`;
            }
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
    </script>
</body>
</html>