<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>播放 <?php echo htmlspecialchars($track['title']); ?> - 星夜阁音乐库</title>
    <link rel="stylesheet" href="/assets/web/css/music-player.css">
    <style>
        .player-page {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            height: calc(100vh - 100px);
            display: flex;
            flex-direction: column;
        }
        .player-container {
            flex: 1;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            overflow: hidden;
        }
        .player-main {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .player-sidebar {
            width: 350px;
            background: #fff;
            border-left: 1px solid #eee;
            display: flex;
            flex-direction: column;
        }
        .album-art {
            width: 300px;
            height: 300px;
            border-radius: 12px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
            margin-bottom: 30px;
            object-fit: cover;
            background: #ddd;
        }
        .track-info-large {
            text-align: center;
            margin-bottom: 30px;
        }
        .track-title-large {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        .track-artist-large {
            font-size: 1.2rem;
            color: #666;
        }
        .progress-container {
            width: 100%;
            max-width: 600px;
            margin-bottom: 30px;
        }
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #ddd;
            border-radius: 3px;
            cursor: pointer;
            position: relative;
        }
        .progress-fill {
            height: 100%;
            background: #667eea;
            border-radius: 3px;
            width: 0%;
            position: relative;
        }
        .progress-handle {
            width: 12px;
            height: 12px;
            background: #667eea;
            border-radius: 50%;
            position: absolute;
            right: -6px;
            top: -3px;
            transform: scale(0);
            transition: transform 0.2s;
        }
        .progress-bar:hover .progress-handle {
            transform: scale(1);
        }
        .time-info {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.9rem;
            color: #666;
        }
        .controls {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        .control-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #333;
            transition: color 0.3s;
        }
        .control-btn:hover {
            color: #667eea;
        }
        .btn-play {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.4);
        }
        .btn-play:hover {
            background: #5a67d8;
            color: white;
            transform: scale(1.05);
        }
        .btn-secondary {
            font-size: 1.2rem;
        }
        .playlist-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            color: #333;
        }
        .playlist-items {
            flex: 1;
            overflow-y: auto;
        }
        .playlist-item {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: background 0.2s;
            border-bottom: 1px solid #f5f5f5;
        }
        .playlist-item:hover {
            background: #f8f9fa;
        }
        .playlist-item.active {
            background: #f0f4ff;
            border-left: 3px solid #667eea;
        }
        .item-cover {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            object-fit: cover;
            background: #ddd;
        }
        .item-info {
            flex: 1;
            min-width: 0;
        }
        .item-title {
            font-weight: 500;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .item-artist {
            font-size: 0.85rem;
            color: #888;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .item-duration {
            font-size: 0.85rem;
            color: #888;
        }
        .volume-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            width: 200px;
        }
        .volume-slider {
            flex: 1;
            height: 4px;
            background: #ddd;
            border-radius: 2px;
            cursor: pointer;
            position: relative;
        }
        .volume-fill {
            height: 100%;
            background: #666;
            border-radius: 2px;
            width: 70%;
        }
    </style>
</head>
<body>
    <div class="player-page">
        <div class="player-container">
            <div class="player-main">
                <img src="<?php echo htmlspecialchars($track['cover_image'] ?? '/assets/common/images/default-cover.jpg'); ?>" alt="Album Art" class="album-art" id="album-art">
                
                <div class="track-info-large">
                    <div class="track-title-large" id="track-title"><?php echo htmlspecialchars($track['title']); ?></div>
                    <div class="track-artist-large" id="track-artist"><?php echo htmlspecialchars($track['artist']); ?></div>
                </div>
                
                <div class="progress-container">
                    <div class="progress-bar" id="progress-bar">
                        <div class="progress-fill" id="progress-fill"></div>
                        <div class="progress-handle"></div>
                    </div>
                    <div class="time-info">
                        <span id="current-time">0:00</span>
                        <span id="total-time">0:00</span>
                    </div>
                </div>
                
                <div class="controls">
                    <button class="control-btn btn-secondary" id="btn-prev">⏮</button>
                    <button class="control-btn btn-play" id="btn-play">▶</button>
                    <button class="control-btn btn-secondary" id="btn-next">⏭</button>
                </div>
                
                <div class="volume-control">
                    <span>🔊</span>
                    <div class="volume-slider" id="volume-slider">
                        <div class="volume-fill" id="volume-fill"></div>
                    </div>
                </div>
            </div>
            
            <div class="player-sidebar">
                <div class="playlist-header">播放列表</div>
                <div class="playlist-items" id="playlist-items">
                    <?php foreach ($playlist as $index => $item): ?>
                        <div class="playlist-item <?php echo $item['id'] == $track['id'] ? 'active' : ''; ?>" 
                             onclick="loadTrack(<?php echo $index; ?>)"
                             data-index="<?php echo $index; ?>">
                            <img src="<?php echo htmlspecialchars($item['cover_image'] ?? '/assets/common/images/default-cover.jpg'); ?>" class="item-cover">
                            <div class="item-info">
                                <div class="item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                <div class="item-artist"><?php echo htmlspecialchars($item['artist']); ?></div>
                            </div>
                            <div class="item-duration"><?php echo $this->formatDuration($item['duration']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <audio id="audio-player"></audio>

    <script>
        const playlist = <?php echo json_encode($playlist); ?>;
        let currentIndex = playlist.findIndex(t => t.id == <?php echo $track['id']; ?>);
        const audio = document.getElementById('audio-player');
        const playBtn = document.getElementById('btn-play');
        const prevBtn = document.getElementById('btn-prev');
        const nextBtn = document.getElementById('btn-next');
        const progressBar = document.getElementById('progress-bar');
        const progressFill = document.getElementById('progress-fill');
        const currentTimeEl = document.getElementById('current-time');
        const totalTimeEl = document.getElementById('total-time');
        
        // 初始化
        if (currentIndex === -1 && playlist.length > 0) currentIndex = 0;
        loadTrack(currentIndex);
        
        // 播放/暂停
        playBtn.addEventListener('click', togglePlay);
        
        // 上一首/下一首
        prevBtn.addEventListener('click', playPrev);
        nextBtn.addEventListener('click', playNext);
        
        // 进度条点击
        progressBar.addEventListener('click', seek);
        
        // 音频事件
        audio.addEventListener('timeupdate', updateProgress);
        audio.addEventListener('ended', playNext);
        audio.addEventListener('loadedmetadata', () => {
            totalTimeEl.textContent = formatTime(audio.duration);
        });
        
        function loadTrack(index) {
            if (index < 0 || index >= playlist.length) return;
            
            currentIndex = index;
            const track = playlist[index];
            
            // 更新界面
            document.getElementById('album-art').src = track.cover_image || '/assets/common/images/default-cover.jpg';
            document.getElementById('track-title').textContent = track.title;
            document.getElementById('track-artist').textContent = track.artist;
            
            // 更新播放列表高亮
            document.querySelectorAll('.playlist-item').forEach((item, i) => {
                if (i === index) item.classList.add('active');
                else item.classList.remove('active');
            });
            
            // 加载音频
            // 这里应该是一个真实的音频URL，可能需要通过API获取
            audio.src = track.file_path || '/api/v1/music/stream/' + track.id;
            
            // 重置状态
            playBtn.textContent = '▶';
            progressFill.style.width = '0%';
            currentTimeEl.textContent = '0:00';
            
            // 自动播放
            audio.play().then(() => {
                playBtn.textContent = '⏸';
            }).catch(e => console.log('Auto-play prevented'));
            
            // 记录播放
            fetch('/api/v1/music/play/' + track.id, { method: 'POST' });
        }
        
        function togglePlay() {
            if (audio.paused) {
                audio.play();
                playBtn.textContent = '⏸';
            } else {
                audio.pause();
                playBtn.textContent = '▶';
            }
        }
        
        function playPrev() {
            let index = currentIndex - 1;
            if (index < 0) index = playlist.length - 1;
            loadTrack(index);
        }
        
        function playNext() {
            let index = currentIndex + 1;
            if (index >= playlist.length) index = 0;
            loadTrack(index);
        }
        
        function updateProgress() {
            const percent = (audio.currentTime / audio.duration) * 100;
            progressFill.style.width = percent + '%';
            currentTimeEl.textContent = formatTime(audio.currentTime);
        }
        
        function seek(e) {
            const rect = progressBar.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            audio.currentTime = percent * audio.duration;
        }
        
        function formatTime(seconds) {
            const min = Math.floor(seconds / 60);
            const sec = Math.floor(seconds % 60);
            return min + ':' + (sec < 10 ? '0' : '') + sec;
        }
        
        // 音量控制
        const volumeSlider = document.getElementById('volume-slider');
        const volumeFill = document.getElementById('volume-fill');
        
        volumeSlider.addEventListener('click', function(e) {
            const rect = volumeSlider.getBoundingClientRect();
            const percent = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
            audio.volume = percent;
            volumeFill.style.width = (percent * 100) + '%';
        });
    </script>
</body>
</html>