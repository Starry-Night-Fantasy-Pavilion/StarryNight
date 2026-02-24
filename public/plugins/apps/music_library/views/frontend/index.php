<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>在线音乐播放器 - 星夜阁</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f5f7fb;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
        }
        .music-app {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 16px;
            padding: 32px 28px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
        }
        .hero-main-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .hero-subtitle {
            opacity: 0.9;
        }
        .hero-badge {
            background: rgba(255,255,255,0.12);
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
        }
        .hero-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: rgba(255,255,255,0.18);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .hero-right {
            text-align: right;
            font-size: 0.85rem;
            opacity: 0.9;
        }
        .hero-right span {
            display: block;
        }
        .layout {
            display: grid;
            grid-template-columns: 2fr 1.3fr;
            gap: 20px;
            align-items: stretch;
        }
        @media (max-width: 992px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }
        .panel {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(15,23,42,0.08);
            padding: 20px 20px 16px;
            box-sizing: border-box;
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .panel-title {
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .panel-title-icon {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #a855f7 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.9rem;
        }
        .panel-subtitle {
            font-size: 0.8rem;
            color: #94a3b8;
        }
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
        }
        .search-input-wrap {
            flex: 1;
            position: relative;
        }
        .search-input {
            width: 100%;
            padding: 10px 40px 10px 34px;
            border-radius: 999px;
            border: 1px solid #e2e8f0;
            outline: none;
            font-size: 0.95rem;
            background: #f8fafc;
            transition: all 0.2s ease;
        }
        .search-input:focus {
            border-color: #6366f1;
            background: #ffffff;
            box-shadow: 0 0 0 1px rgba(99,102,241,0.3);
        }
        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.9rem;
        }
        .search-hint {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.75rem;
            color: #a0aec0;
        }
        .search-button {
            padding: 0 18px;
            border-radius: 999px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border: none;
            color: #fff;
            font-weight: 500;
            cursor: pointer;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 10px 20px rgba(79,70,229,0.35);
        }
        .search-button:hover {
            opacity: 0.95;
        }
        .vendor-select {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.82rem;
            color: #64748b;
        }
        .vendor-select select {
            border-radius: 999px;
            border: 1px solid #e2e8f0;
            padding: 2px 10px;
            font-size: 0.8rem;
            background: #f8fafc;
            outline: none;
        }
        .results-list {
            max-height: 400px;
            overflow-y: auto;
            margin: 8px 0;
            padding-right: 6px;
        }
        .result-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 6px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .result-item:hover {
            background: #f1f5f9;
        }
        .result-cover {
            width: 42px;
            height: 42px;
            border-radius: 9px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.85rem;
            overflow: hidden;
            flex-shrink: 0;
        }
        .result-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .result-main {
            flex: 1;
            min-width: 0;
        }
        .result-title {
            font-size: 0.95rem;
            font-weight: 500;
            color: #111827;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .result-meta {
            font-size: 0.8rem;
            color: #6b7280;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .result-actions {
            display: flex;
            gap: 6px;
        }
        .btn-icon {
            border-radius: 999px;
            border: none;
            padding: 4px 8px;
            font-size: 0.8rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-play {
            background: #4f46e5;
            color: #fff;
        }
        .btn-add {
            background: #e5e7eb;
            color: #374151;
        }
        .results-empty {
            text-align: center;
            color: #9ca3af;
            font-size: 0.9rem;
            padding: 30px 10px;
        }
        .results-empty span {
            display: block;
        }
        .results-empty small {
            font-size: 0.8rem;
            color: #a0aec0;
        }
        .player-main {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 12px 4px;
        }
        .player-cover {
            width: 180px;
            height: 180px;
            border-radius: 24px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            box-shadow: 0 18px 35px rgba(79,70,229,0.45);
            margin-bottom: 12px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 2.2rem;
        }
        .player-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .player-info {
            text-align: center;
            margin-bottom: 8px;
        }
        .player-title {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }
        .player-artist {
            font-size: 0.9rem;
            color: #6b7280;
        }
        .player-extra {
            font-size: 0.8rem;
            color: #9ca3af;
            margin-top: 2px;
        }
        .player-progress {
            width: 100%;
            margin: 8px 0;
        }
        .progress-bar {
            width: 100%;
            height: 5px;
            border-radius: 999px;
            background: #e5e7eb;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .progress-inner {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 0%;
            background: linear-gradient(135deg, #4f46e5, #ec4899);
            border-radius: inherit;
            transition: width 0.1s linear;
        }
        .time-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #9ca3af;
            margin-top: 4px;
        }
        .player-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 14px;
            margin-top: 10px;
        }
        .control-btn {
            border: none;
            background: none;
            cursor: pointer;
            font-size: 1.2rem;
            color: #4b5563;
        }
        .control-btn.main {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #4f46e5;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px rgba(79,70,229,0.5);
            font-size: 1.2rem;
        }
        .control-btn.main.paused {
            background: #111827;
            box-shadow: 0 12px 25px rgba(15,23,42,0.6);
        }
        .volume-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
            font-size: 0.85rem;
            color: #6b7280;
        }
        .volume-bar {
            width: 120px;
            height: 4px;
            border-radius: 999px;
            background: #e5e7eb;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .volume-inner {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 70%;
            background: #4b5563;
            border-radius: inherit;
        }
        .playlist {
            margin-top: 12px;
            border-top: 1px dashed #e5e7eb;
            padding-top: 8px;
        }
        .playlist-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }
        .playlist-title {
            font-size: 0.9rem;
            font-weight: 500;
            color: #4b5563;
        }
        .playlist-clear {
            border: none;
            background: none;
            color: #9ca3af;
            font-size: 0.75rem;
            cursor: pointer;
        }
        .playlist-items {
            max-height: 180px;
            overflow-y: auto;
        }
        .playlist-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 4px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s ease;
            font-size: 0.85rem;
        }
        .playlist-item:hover {
            background: #f3f4f6;
        }
        .playlist-item.active {
            background: #eef2ff;
            color: #4338ca;
        }
        .playlist-index {
            width: 18px;
            text-align: center;
            font-size: 0.78rem;
            color: #9ca3af;
        }
        .playlist-text {
            flex: 1;
            min-width: 0;
        }
        .playlist-text-title {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .playlist-text-meta {
            font-size: 0.75rem;
            color: #9ca3af;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .status-bar {
            font-size: 0.78rem;
            color: #9ca3af;
            margin-top: 6px;
            text-align: right;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="music-app">
        <div class="hero">
            <div>
                <div class="hero-badge">
                    <span class="hero-icon">♪</span>
                    <span>云音乐 · 在线解析播放</span>
                </div>
                <div class="hero-main-title">在线音乐播放器</div>
                <div class="hero-subtitle">支持网易云 / QQ / 酷我等多平台，一键搜索并播放你想听的歌</div>
            </div>
            <div class="hero-right">
                <span>数据来源：第三方解析接口</span>
                <span>仅供学习交流，请勿用于商业用途</span>
            </div>
        </div>

        <div class="layout">
            <!-- 左侧：搜索 + 搜索结果 -->
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">
                            <div class="panel-title-icon">🔍</div>
                            搜索歌曲
                        </div>
                        <div class="panel-subtitle">输入歌名 / 歌手 / 关键字，回车或点击搜索即可</div>
                    </div>
                    <div class="vendor-select">
                        平台：
                        <select id="vendor">
                            <option value="all">全部平台</option>
                            <option value="netease">网易云</option>
                            <option value="qq">QQ 音乐</option>
                            <option value="kuwo">酷我音乐</option>
                        </select>
                    </div>
                </div>

                <div class="search-bar">
                    <div class="search-input-wrap">
                        <span class="search-icon">⌕</span>
                        <input id="search-input" class="search-input" type="text" placeholder="例如：周杰伦 晴天 / 喜欢你 / 神秘嘉宾">
                        <span class="search-hint">Enter 搜索</span>
                    </div>
                    <button id="search-btn" class="search-button">
                        <span>搜索</span>
                    </button>
                </div>

                <div id="results" class="results-list">
                    <div class="results-empty" id="results-empty">
                        <span>还没有结果</span>
                        <small>从上方输入关键词开始，或切换不同平台尝试</small>
                    </div>
                </div>

                <div class="status-bar" id="search-status"></div>
            </div>

            <!-- 右侧：播放器 + 播放列表 -->
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">
                            <div class="panel-title-icon">▶</div>
                            播放器
                        </div>
                        <div class="panel-subtitle">支持顺序播放，点击搜索结果即可加入播放列表并播放</div>
                    </div>
                </div>

                <div class="player-main">
                    <div class="player-cover" id="player-cover">
                        <span>♪</span>
                    </div>
                    <div class="player-info">
                        <div class="player-title" id="player-title">未播放任何歌曲</div>
                        <div class="player-artist" id="player-artist">搜索一首歌，点击播放即可开始</div>
                        <div class="player-extra" id="player-extra"></div>
                    </div>

                    <div class="player-progress">
                        <div class="progress-bar" id="progress-bar">
                            <div class="progress-inner" id="progress-inner"></div>
                        </div>
                        <div class="time-row">
                            <span id="time-current">0:00</span>
                            <span id="time-total">0:00</span>
                        </div>
                    </div>

                    <div class="player-controls">
                        <button class="control-btn" id="btn-prev">⏮</button>
                        <button class="control-btn main" id="btn-toggle">▶</button>
                        <button class="control-btn" id="btn-next">⏭</button>
                    </div>

                    <div class="volume-row">
                        <span>🔊</span>
                        <div class="volume-bar" id="volume-bar">
                            <div class="volume-inner" id="volume-inner"></div>
                        </div>
                    </div>
                </div>

                <div class="playlist">
                    <div class="playlist-header">
                        <div class="playlist-title">播放列表</div>
                        <button class="playlist-clear" id="btn-clear-playlist">清空</button>
                    </div>
                    <div class="playlist-items" id="playlist"></div>
                </div>
            </div>
        </div>
    </div>

    <audio id="audio-player"></audio>

    <script>
        const searchInput = document.getElementById('search-input');
        const searchBtn = document.getElementById('search-btn');
        const vendorSelect = document.getElementById('vendor');
        const resultsContainer = document.getElementById('results');
        const resultsEmpty = document.getElementById('results-empty');
        const searchStatus = document.getElementById('search-status');

        const audio = document.getElementById('audio-player');
        const playerCover = document.getElementById('player-cover');
        const playerTitle = document.getElementById('player-title');
        const playerArtist = document.getElementById('player-artist');
        const playerExtra = document.getElementById('player-extra');
        const progressBar = document.getElementById('progress-bar');
        const progressInner = document.getElementById('progress-inner');
        const timeCurrent = document.getElementById('time-current');
        const timeTotal = document.getElementById('time-total');
        const btnPrev = document.getElementById('btn-prev');
        const btnToggle = document.getElementById('btn-toggle');
        const btnNext = document.getElementById('btn-next');
        const volumeBar = document.getElementById('volume-bar');
        const volumeInner = document.getElementById('volume-inner');
        const playlistEl = document.getElementById('playlist');
        const btnClearPlaylist = document.getElementById('btn-clear-playlist');

        let searchResults = [];
        let playlist = [];
        let currentIndex = -1;
        let isLoadingUrl = false;

        function setStatus(text) {
            searchStatus.textContent = text || '';
        }

        function formatTime(seconds) {
            if (!seconds || !isFinite(seconds)) return '0:00';
            const m = Math.floor(seconds / 60);
            const s = Math.floor(seconds % 60);
            return m + ':' + (s < 10 ? '0' : '') + s;
        }

        function normalizeTracks(apiData) {
            let data = apiData || {};
            let list = [];

            // 兼容 /api/v1/music/search 的返回：{ success, data: result }
            if (data && typeof data === 'object' && 'success' in data) {
                if (!data.success) return [];
                data = data.data || {};
            }

            let items = [];
            if (Array.isArray(data.results)) {
                items = data.results;
            } else if (Array.isArray(data.data)) {
                items = data.data;
            } else if (Array.isArray(data)) {
                items = data;
            }

            for (const item of items) {
                if (!item || typeof item !== 'object') continue;
                const id = item.id || item.songid || item.rid || item.mid;
                if (!id) continue;

                const title = item.name || item.songname || item.title || '';
                const artist = item.artist || item.singer || item.singers || item.author || '';
                const album = item.album || item.albumname || '';
                const cover = item.pic || item.cover || item.picUrl || item.albumPic || '';
                const vendor = item.source || item.vendor || item.platform || (vendorSelect.value || 'all');

                list.push({
                    id: String(id),
                    title: title,
                    artist: artist,
                    album: album,
                    cover_image: cover,
                    vendor: vendor
                });
            }
            return list;
        }

        async function search() {
            const keyword = searchInput.value.trim();
            const vendor = vendorSelect.value || 'all';
            if (!keyword) {
                setStatus('请输入搜索关键词');
                return;
            }

            setStatus('正在搜索中...');
            resultsContainer.innerHTML = '';
            resultsContainer.appendChild(resultsEmpty);
            resultsEmpty.style.display = 'block';
            resultsEmpty.querySelector('span').textContent = '正在搜索 "' + keyword + '" ...';

            try {
                const url = '/api/v1/music/search?keyword=' + encodeURIComponent(keyword) +
                    '&vendor=' + encodeURIComponent(vendor) + '&limit=30';
                const res = await fetch(url);
                const data = await res.json();

                searchResults = normalizeTracks(data);
                renderResults();

                if (searchResults.length === 0) {
                    setStatus('未找到相关歌曲，尝试更换关键词或平台');
                } else {
                    setStatus('共找到 ' + searchResults.length + ' 首歌曲，可点击播放或加入播放列表');
                }
            } catch (e) {
                console.error(e);
                setStatus('搜索失败：' + (e.message || '未知错误'));
                resultsContainer.innerHTML = '';
                resultsContainer.appendChild(resultsEmpty);
                resultsEmpty.style.display = 'block';
                resultsEmpty.querySelector('span').textContent = '搜索失败，请稍后重试';
            }
        }

        function renderResults() {
            resultsContainer.innerHTML = '';
            if (!searchResults.length) {
                resultsContainer.appendChild(resultsEmpty);
                resultsEmpty.style.display = 'block';
                resultsEmpty.querySelector('span').textContent = '暂无结果';
                return;
            }

            resultsEmpty.style.display = 'none';

            searchResults.forEach((track, index) => {
                const el = document.createElement('div');
                el.className = 'result-item';
                el.dataset.index = String(index);

                const cover = document.createElement('div');
                cover.className = 'result-cover';
                if (track.cover_image) {
                    const img = document.createElement('img');
                    img.src = track.cover_image;
                    cover.appendChild(img);
                } else {
                    cover.textContent = '♪';
                }

                const main = document.createElement('div');
                main.className = 'result-main';
                const title = document.createElement('div');
                title.className = 'result-title';
                title.textContent = track.title || '未知歌曲';
                const meta = document.createElement('div');
                meta.className = 'result-meta';
                const vendorLabel = track.vendor === 'netease' ? '网易云' :
                    track.vendor === 'qq' ? 'QQ 音乐' :
                    track.vendor === 'kuwo' ? '酷我音乐' : '多平台';
                meta.textContent = (track.artist || '未知歌手') + ' · ' + vendorLabel;
                main.appendChild(title);
                main.appendChild(meta);

                const actions = document.createElement('div');
                actions.className = 'result-actions';
                const playBtn = document.createElement('button');
                playBtn.className = 'btn-icon btn-play';
                playBtn.textContent = '播放';
                playBtn.onclick = (e) => {
                    e.stopPropagation();
                    playTrackFromSearch(index, true);
                };
                const addBtn = document.createElement('button');
                addBtn.className = 'btn-icon btn-add';
                addBtn.textContent = '加入';
                addBtn.onclick = (e) => {
                    e.stopPropagation();
                    addToPlaylist(track, true);
                };
                actions.appendChild(playBtn);
                actions.appendChild(addBtn);

                el.onclick = () => playTrackFromSearch(index, false);

                el.appendChild(cover);
                el.appendChild(main);
                el.appendChild(actions);

                resultsContainer.appendChild(el);
            });
        }

        function renderPlaylist() {
            playlistEl.innerHTML = '';
            if (!playlist.length) {
                const empty = document.createElement('div');
                empty.className = 'results-empty';
                empty.innerHTML = '<span>播放列表为空</span><small>从左侧搜索结果中加入歌曲</small>';
                playlistEl.appendChild(empty);
                return;
            }

            playlist.forEach((track, index) => {
                const el = document.createElement('div');
                el.className = 'playlist-item' + (index === currentIndex ? ' active' : '');
                el.dataset.index = String(index);

                const idx = document.createElement('div');
                idx.className = 'playlist-index';
                idx.textContent = index + 1;

                const text = document.createElement('div');
                text.className = 'playlist-text';
                const t1 = document.createElement('div');
                t1.className = 'playlist-text-title';
                t1.textContent = track.title || '未知歌曲';
                const t2 = document.createElement('div');
                t2.className = 'playlist-text-meta';
                const vendorLabel = track.vendor === 'netease' ? '网易云' :
                    track.vendor === 'qq' ? 'QQ 音乐' :
                    track.vendor === 'kuwo' ? '酷我音乐' : '多平台';
                t2.textContent = (track.artist || '未知歌手') + ' · ' + vendorLabel;
                text.appendChild(t1);
                text.appendChild(t2);

                el.onclick = () => {
                    playFromPlaylist(index);
                };

                el.appendChild(idx);
                el.appendChild(text);
                playlistEl.appendChild(el);
            });
        }

        function addToPlaylist(track, silent) {
            playlist.push(track);
            renderPlaylist();
            if (!silent) {
                setStatus('已加入播放列表：' + (track.title || '未知歌曲'));
            }
        }

        async function playTrackFromSearch(index, alsoAddToPlaylist) {
            const track = searchResults[index];
            if (!track) return;

            if (alsoAddToPlaylist) {
                addToPlaylist(track, true);
                currentIndex = playlist.length - 1;
            } else {
                const existingIndex = playlist.findIndex(t => t.id === track.id && t.vendor === track.vendor);
                if (existingIndex === -1) {
                    addToPlaylist(track, true);
                    currentIndex = playlist.length - 1;
                } else {
                    currentIndex = existingIndex;
                }
            }

            await playCurrent();
        }

        async function playFromPlaylist(index) {
            if (index < 0 || index >= playlist.length) return;
            currentIndex = index;
            await playCurrent();
        }

        async function playCurrent() {
            if (currentIndex < 0 || currentIndex >= playlist.length) return;
            const track = playlist[currentIndex];
            if (!track) return;

            isLoadingUrl = true;
            btnToggle.textContent = '⏳';
            btnToggle.classList.add('paused');
            setStatus('正在获取音频地址...');

            try {
                const vendor = track.vendor && track.vendor !== 'all' ? track.vendor : (vendorSelect.value || 'netease');
                const url = '/api/v1/music/song_url?vendor=' + encodeURIComponent(vendor) +
                    '&id=' + encodeURIComponent(track.id);
                const res = await fetch(url);
                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.message || '获取歌曲 URL 失败');
                }

                let result = data.data || {};
                let playUrl = result.url || result.playUrl || '';
                if (!playUrl && Array.isArray(result) && result.length) {
                    playUrl = result[0].url || '';
                }
                if (!playUrl) {
                    throw new Error('接口未返回可用的播放地址');
                }

                audio.src = playUrl;
                audio.play().then(() => {
                    btnToggle.textContent = '⏸';
                    btnToggle.classList.remove('paused');
                    setStatus('正在播放：' + (track.title || '未知歌曲'));
                }).catch(e => {
                    console.warn(e);
                    btnToggle.textContent = '▶';
                    btnToggle.classList.add('paused');
                    setStatus('自动播放被浏览器拦截，请手动点击播放按钮');
                });

                playerTitle.textContent = track.title || '未知歌曲';
                playerArtist.textContent = track.artist || '未知歌手';
                playerExtra.textContent = track.album ? ('专辑：' + track.album) : '';

                if (track.cover_image) {
                    playerCover.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = track.cover_image;
                    playerCover.appendChild(img);
                } else {
                    playerCover.innerHTML = '<span>♪</span>';
                }

                renderPlaylist();
            } catch (e) {
                console.error(e);
                setStatus('播放失败：' + (e.message || '未知错误'));
                btnToggle.textContent = '▶';
                btnToggle.classList.add('paused');
            } finally {
                isLoadingUrl = false;
            }
        }

        function togglePlay() {
            if (!audio.src) {
                if (playlist.length) {
                    playCurrent();
                }
                return;
            }
            if (audio.paused) {
                audio.play();
                btnToggle.textContent = '⏸';
                btnToggle.classList.remove('paused');
            } else {
                audio.pause();
                btnToggle.textContent = '▶';
                btnToggle.classList.add('paused');
            }
        }

        function playPrev() {
            if (!playlist.length) return;
            currentIndex = currentIndex <= 0 ? playlist.length - 1 : currentIndex - 1;
            playCurrent();
        }

        function playNext() {
            if (!playlist.length) return;
            currentIndex = currentIndex >= playlist.length - 1 ? 0 : currentIndex + 1;
            playCurrent();
        }

        function updateProgress() {
            if (!audio.duration || !isFinite(audio.duration)) return;
            const percent = (audio.currentTime / audio.duration) * 100;
            progressInner.style.width = percent + '%';
            timeCurrent.textContent = formatTime(audio.currentTime);
            timeTotal.textContent = formatTime(audio.duration);
        }

        function seek(e) {
            const rect = progressBar.getBoundingClientRect();
            const ratio = (e.clientX - rect.left) / rect.width;
            if (!audio.duration || ratio < 0 || ratio > 1) return;
            audio.currentTime = ratio * audio.duration;
        }

        function changeVolume(e) {
            const rect = volumeBar.getBoundingClientRect();
            const ratio = (e.clientX - rect.left) / rect.width;
            const v = Math.min(1, Math.max(0, ratio));
            audio.volume = v;
            volumeInner.style.width = (v * 100) + '%';
        }

        function clearPlaylist() {
            playlist = [];
            currentIndex = -1;
            audio.pause();
            audio.src = '';
            playerTitle.textContent = '未播放任何歌曲';
            playerArtist.textContent = '搜索一首歌，点击播放即可开始';
            playerExtra.textContent = '';
            playerCover.innerHTML = '<span>♪</span>';
            timeCurrent.textContent = '0:00';
            timeTotal.textContent = '0:00';
            progressInner.style.width = '0%';
            btnToggle.textContent = '▶';
            btnToggle.classList.add('paused');
            renderPlaylist();
            setStatus('播放列表已清空');
        }

        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                search();
            }
        });
        searchBtn.addEventListener('click', search);

        btnToggle.addEventListener('click', togglePlay);
        btnPrev.addEventListener('click', playPrev);
        btnNext.addEventListener('click', playNext);
        progressBar.addEventListener('click', seek);
        volumeBar.addEventListener('click', changeVolume);
        btnClearPlaylist.addEventListener('click', clearPlaylist);

        audio.addEventListener('timeupdate', updateProgress);
        audio.addEventListener('ended', playNext);
        audio.addEventListener('loadedmetadata', () => {
            timeTotal.textContent = formatTime(audio.duration);
        });

        // 页面加载后自动拉取一批推荐歌曲，让首页“自带音乐”
        window.addEventListener('load', () => {
            // 默认关键字可以是常见歌手/热门关键词，避免完全空白
            if (!searchInput.value.trim()) {
                searchInput.value = '周杰伦';
            }
            search();
        });

        renderPlaylist();
    </script>
</body>
</html>