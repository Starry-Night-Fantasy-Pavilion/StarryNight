<?php
$title = '排行榜 - 星夜阁';
// 样式已在页面内联
use app\config\FrontendConfig;
$defaultCoverImage = FrontendConfig::getThemeImageUrl('default-cover.png');
$defaultAvatarImage = FrontendConfig::getThemeImageUrl('default-avatar.png');
?>

<?php require __DIR__ . '/layout.php'; ?>

<div class="main-content">
    <div class="container">
        <header class="page-header">
            <h1>社区排行榜</h1>
            <p>发现最受欢迎的作品和优秀的创作者</p>
        </header>

        <!-- 周期选择器 -->
        <div class="period-selector">
            <button class="period-btn active" data-period="daily">日榜</button>
            <button class="period-btn" data-period="weekly">周榜</button>
            <button class="period-btn" data-period="monthly">月榜</button>
            <button class="period-btn" data-period="all">总榜</button>
        </div>

        <!-- 排行榜内容 -->
        <div class="ranking-content">
            <!-- 小说排行榜 -->
            <section class="ranking-section">
                <div class="section-header">
                    <h2>
                        <span class="icon">📚</span>
                        小说排行榜
                    </h2>
                    <div class="ranking-tabs">
                        <button class="tab-btn active" data-type="novel" data-ranking="hot">热门</button>
                        <button class="tab-btn" data-type="novel" data-ranking="new">新作</button>
                        <button class="tab-btn" data-type="novel" data-ranking="favorite">收藏</button>
                    </div>
                </div>
                <div class="ranking-list" id="novel-ranking">
                    <div class="loading">加载中...</div>
                </div>
            </section>

            <!-- 动漫排行榜 -->
            <section class="ranking-section">
                <div class="section-header">
                    <h2>
                        <span class="icon">🎬</span>
                        动漫排行榜
                    </h2>
                    <div class="ranking-tabs">
                        <button class="tab-btn active" data-type="anime" data-ranking="hot">热门</button>
                        <button class="tab-btn" data-type="anime" data-ranking="new">新作</button>
                        <button class="tab-btn" data-type="anime" data-ranking="favorite">收藏</button>
                    </div>
                </div>
                <div class="ranking-list" id="anime-ranking">
                    <div class="loading">加载中...</div>
                </div>
            </section>

            <!-- 音乐排行榜 -->
            <section class="ranking-section">
                <div class="section-header">
                    <h2>
                        <span class="icon">🎵</span>
                        音乐排行榜
                    </h2>
                    <div class="ranking-tabs">
                        <button class="tab-btn active" data-type="music" data-ranking="hot">热门</button>
                        <button class="tab-btn" data-type="music" data-ranking="new">新作</button>
                        <button class="tab-btn" data-type="music" data-ranking="favorite">收藏</button>
                    </div>
                </div>
                <div class="ranking-list" id="music-ranking">
                    <div class="loading">加载中...</div>
                </div>
            </section>

            <!-- 创作者排行榜 -->
            <section class="ranking-section">
                <div class="section-header">
                    <h2>
                        <span class="icon">👨‍🎨</span>
                        创作者排行榜
                    </h2>
                    <div class="ranking-tabs">
                        <button class="tab-btn active" data-type="creator" data-ranking="views">人气</button>
                        <button class="tab-btn" data-type="creator" data-ranking="works">作品</button>
                        <button class="tab-btn" data-type="creator" data-ranking="favorites">收藏</button>
                    </div>
                </div>
                <div class="ranking-list" id="creator-ranking">
                    <div class="loading">加载中...</div>
                </div>
            </section>

            <!-- 邀请排行榜 -->
            <section class="ranking-section">
                <div class="section-header">
                    <h2>
                        <span class="icon">🎁</span>
                        邀请排行榜
                    </h2>
                    <div class="ranking-tabs">
                        <button class="tab-btn active" data-type="invitation" data-ranking="count">邀请数</button>
                        <button class="tab-btn" data-type="invitation" data-ranking="recharge">贡献值</button>
                    </div>
                </div>
                <div class="ranking-list" id="invitation-ranking">
                    <div class="loading">加载中...</div>
                </div>
            </section>
        </div>

        <!-- 统计信息 -->
        <section class="stats-section">
            <h2>平台统计</h2>
            <div class="stats-grid" id="platform-stats">
                <div class="loading">加载中...</div>
            </div>
        </section>
    </div>
</div>

<script>
// 静态资源路径（避免硬编码 /web/default/...）
const DEFAULT_COVER = '<?= htmlspecialchars($defaultCoverImage, ENT_QUOTES) ?>';
const DEFAULT_AVATAR = '<?= htmlspecialchars($defaultAvatarImage, ENT_QUOTES) ?>';

// 全局变量
let currentPeriod = 'weekly';
let currentRankings = {};

// DOM加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    loadInitialRankings();
    loadPlatformStats();
});

// 初始化事件监听器
function initializeEventListeners() {
    // 周期选择器
    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentPeriod = this.dataset.period;
            loadAllRankings();
        });
    });

    // 排行榜标签
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const type = this.dataset.type;
            const ranking = this.dataset.ranking;
            
            // 更新标签状态
            document.querySelectorAll(`[data-type="${type}"]`).forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // 加载对应的排行榜
            loadRanking(type, ranking);
        });
    });
}

// 加载初始排行榜数据
function loadInitialRankings() {
    const defaultRankings = [
        { type: 'novel', ranking: 'hot' },
        { type: 'anime', ranking: 'hot' },
        { type: 'music', ranking: 'hot' },
        { type: 'creator', ranking: 'views' },
        { type: 'invitation', ranking: 'count' }
    ];

    defaultRankings.forEach(({ type, ranking }) => {
        loadRanking(type, ranking);
    });
}

// 加载所有排行榜
function loadAllRankings() {
    const activeTabs = document.querySelectorAll('.tab-btn.active');
    activeTabs.forEach(btn => {
        const type = btn.dataset.type;
        const ranking = btn.dataset.ranking;
        loadRanking(type, ranking);
    });
}

// 加载排行榜数据
async function loadRanking(type, ranking) {
    const container = document.getElementById(`${type}-ranking`);
    if (!container) return;

    container.innerHTML = '<div class="loading">加载中...</div>';

    try {
        const response = await fetch(`/api/ranking/${type}/${currentPeriod}/${ranking}/10`);
        const data = await response.json();

        if (data.success) {
            currentRankings[`${type}_${ranking}`] = data.data.rankings;
            renderRanking(container, data.data.rankings, type);
        } else {
            throw new Error(data.message || '加载失败');
        }
    } catch (error) {
        container.innerHTML = `<div class="error">加载失败: ${error.message}</div>`;
    }
}

// 渲染排行榜
function renderRanking(container, rankings, type) {
    if (!rankings || rankings.length === 0) {
        container.innerHTML = '<div class="empty">暂无数据</div>';
        return;
    }

    let html = '';
    rankings.forEach((item, index) => {
        html += createRankingItem(item, type, index + 1);
    });

    container.innerHTML = html;
}

// 创建排行榜项目
function createRankingItem(item, type, rank) {
    const rankClass = rank <= 3 ? `rank-${rank}` : 'rank-normal';
    const rankIcon = rank <= 3 ? getRankIcon(rank) : `<span class="rank-number">${rank}</span>`;

    switch (type) {
        case 'novel':
        case 'anime':
            return createWorkRankingItem(item, rankClass, rankIcon, type);
        case 'music':
            return createMusicRankingItem(item, rankClass, rankIcon);
        case 'creator':
            return createCreatorRankingItem(item, rankClass, rankIcon);
        case 'invitation':
            return createInvitationRankingItem(item, rankClass, rankIcon);
        default:
            return '';
    }
}

// 获取排名图标
function getRankIcon(rank) {
    const icons = {
        1: '🥇',
        2: '🥈',
        3: '🥉'
    };
    return icons[rank] || '';
}

// 创建作品排行榜项目
function createWorkRankingItem(item, rankClass, rankIcon, type) {
    const authorName = item.author?.nickname || item.author?.username || '未知作者';
    const workType = type === 'novel' ? '小说' : '动漫';
    const stats = type === 'novel' 
        ? `浏览 ${item.view_count} | 收藏 ${item.favorite_count}`
        : `浏览 ${item.view_count} | 收藏 ${item.favorite_count}`;

    return `
        <div class="ranking-item ${rankClass}">
            <div class="rank-info">
                <div class="rank-icon">${rankIcon}</div>
            </div>
            <div class="item-cover">
                ${item.cover_image ? `<img src="${item.cover_image}" alt="${item.title}" onerror="this.src='${DEFAULT_COVER}'">` : '<div class="no-cover">📖</div>'}
            </div>
            <div class="item-info">
                <h3 class="item-title">${item.title}</h3>
                <p class="item-meta">
                    <span class="genre">${item.genre || '未分类'}</span>
                    <span class="author">作者: ${authorName}</span>
                </p>
                <p class="item-stats">${stats}</p>
                <div class="item-rating">
                    <span class="rating-score">⭐ ${item.rating?.toFixed(1) || '0.0'}</span>
                    <span class="rating-count">(${item.rating_count || 0})</span>
                </div>
            </div>
            <div class="item-actions">
                <a href="/${workType}/${item.id}" class="btn-view">查看</a>
            </div>
        </div>
    `;
}

// 创建音乐排行榜项目
function createMusicRankingItem(item, rankClass, rankIcon) {
    const artistName = item.artist?.nickname || item.artist?.username || '未知艺术家';
    const stats = `播放 ${item.play_count} | 收藏 ${item.favorite_count}`;

    return `
        <div class="ranking-item ${rankClass}">
            <div class="rank-info">
                <div class="rank-icon">${rankIcon}</div>
            </div>
            <div class="item-cover">
                ${item.cover_image ? `<img src="${item.cover_image}" alt="${item.title}" onerror="this.src='${DEFAULT_COVER}'">` : '<div class="no-cover">🎵</div>'}
            </div>
            <div class="item-info">
                <h3 class="item-title">${item.title}</h3>
                <p class="item-meta">
                    <span class="genre">${item.genre || '未分类'}</span>
                    <span class="artist">艺术家: ${artistName}</span>
                </p>
                <p class="item-stats">${stats}</p>
                <div class="item-rating">
                    <span class="rating-score">⭐ ${item.rating?.toFixed(1) || '0.0'}</span>
                    <span class="rating-count">(${item.rating_count || 0})</span>
                </div>
            </div>
            <div class="item-actions">
                <a href="/music/${item.id}" class="btn-view">播放</a>
            </div>
        </div>
    `;
}

// 创建创作者排行榜项目
function createCreatorRankingItem(item, rankClass, rankIcon) {
    const totalWorks = (item.novel_count || 0) + (item.anime_count || 0) + (item.music_count || 0);
    const avgRating = ((item.avg_novel_rating || 0) + (item.avg_anime_rating || 0) + (item.avg_music_rating || 0)) / 3;

    return `
        <div class="ranking-item ${rankClass}">
            <div class="rank-info">
                <div class="rank-icon">${rankIcon}</div>
            </div>
            <div class="item-avatar">
                ${item.avatar ? `<img src="${item.avatar}" alt="${item.nickname || item.username}" onerror="this.src='${DEFAULT_AVATAR}'">` : '<div class="no-avatar">👤</div>'}
            </div>
            <div class="item-info">
                <h3 class="item-title">${item.nickname || item.username}</h3>
                <p class="item-meta">
                    <span class="works-count">作品: ${totalWorks}</span>
                    <span class="views-count">浏览: ${item.total_views}</span>
                </p>
                <p class="item-stats">
                    小说: ${item.novel_count || 0} | 
                    动漫: ${item.anime_count || 0} | 
                    音乐: ${item.music_count || 0}
                </p>
                <div class="item-rating">
                    <span class="rating-score">⭐ ${avgRating.toFixed(1)}</span>
                </div>
            </div>
            <div class="item-actions">
                <a href="/creator/${item.id}" class="btn-view">主页</a>
            </div>
        </div>
    `;
}

// 创建邀请排行榜项目
function createInvitationRankingItem(item, rankClass, rankIcon) {
    return `
        <div class="ranking-item ${rankClass}">
            <div class="rank-info">
                <div class="rank-icon">${rankIcon}</div>
            </div>
            <div class="item-avatar">
                ${item.avatar ? `<img src="${item.avatar}" alt="${item.nickname || item.username}" onerror="this.src='${DEFAULT_AVATAR}'">` : '<div class="no-avatar">👤</div>'}
            </div>
            <div class="item-info">
                <h3 class="item-title">${item.nickname || item.username}</h3>
                <p class="item-meta">
                    <span class="invitation-count">邀请: ${item.invitation_count}</span>
                    <span class="recharge-amount">贡献: ¥${item.total_recharge.toFixed(2)}</span>
                </p>
                <p class="item-stats">
                    奖励: ¥${item.total_reward.toFixed(2)}
                </p>
            </div>
            <div class="item-actions">
                <button class="btn-view" onclick="showInvitationDetails('${item.username}')">详情</button>
            </div>
        </div>
    `;
}

// 加载平台统计
async function loadPlatformStats() {
    const container = document.getElementById('platform-stats');
    container.innerHTML = '<div class="loading">加载中...</div>';

    try {
        const response = await fetch('/api/ranking/stats');
        const data = await response.json();

        if (data.success) {
            renderPlatformStats(container, data.data);
        } else {
            throw new Error(data.message || '加载失败');
        }
    } catch (error) {
        container.innerHTML = `<div class="error">加载失败: ${error.message}</div>`;
    }
}

// 渲染平台统计
function renderPlatformStats(container, stats) {
    const html = `
        <div class="stat-item">
            <h3>小说</h3>
            <div class="stat-number">${stats.novel_stats?.total || 0}</div>
            <div class="stat-label">总作品数</div>
        </div>
        <div class="stat-item">
            <h3>动漫</h3>
            <div class="stat-number">${stats.anime_stats?.total || 0}</div>
            <div class="stat-label">总作品数</div>
        </div>
        <div class="stat-item">
            <h3>音乐</h3>
            <div class="stat-number">${stats.music_stats?.total || 0}</div>
            <div class="stat-label">总作品数</div>
        </div>
        <div class="stat-item">
            <h3>创作者</h3>
            <div class="stat-number">${stats.invitation_stats?.active_inviters || 0}</div>
            <div class="stat-label">活跃用户</div>
        </div>
    `;

    container.innerHTML = html;
}

// 显示邀请详情
function showInvitationDetails(username) {
    // 这里可以实现显示邀请详情的弹窗
    alert(`用户 ${username} 的邀请详情功能正在开发中`);
}
</script>

<style>
/* 排行榜页面样式 */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
}

.page-header h1 {
    font-size: 2.5rem;
    margin-bottom: 10px;
    background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.page-header p {
    color: #888;
    font-size: 1.1rem;
}

.period-selector {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 40px;
}

.period-btn {
    padding: 10px 20px;
    border: 1px solid rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.05);
    color: #fff;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.period-btn:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.3);
}

.period-btn.active {
    background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
    border-color: transparent;
}

.ranking-section {
    margin-bottom: 60px;
    background: rgba(255,255,255,0.02);
    border-radius: 15px;
    padding: 30px;
    border: 1px solid rgba(255,255,255,0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}

.section-header h2 {
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-header .icon {
    font-size: 1.8rem;
}

.ranking-tabs {
    display: flex;
    gap: 5px;
}

.tab-btn {
    padding: 8px 16px;
    border: 1px solid rgba(255,255,255,0.2);
    background: transparent;
    color: #888;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.tab-btn:hover {
    background: rgba(255,255,255,0.05);
    color: #fff;
}

.tab-btn.active {
    background: rgba(255,255,255,0.1);
    color: #fff;
    border-color: rgba(255,255,255,0.3);
}

.ranking-list {
    min-height: 200px;
}

.ranking-item {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: rgba(255,255,255,0.03);
    border-radius: 12px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.ranking-item:hover {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
    transform: translateY(-2px);
}

.rank-info {
    flex-shrink: 0;
}

.rank-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
}

.rank-number {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    font-weight: bold;
}

.rank-1 .rank-icon { color: #FFD700; }
.rank-2 .rank-icon { color: #C0C0C0; }
.rank-3 .rank-icon { color: #CD7F32; }

.item-cover, .item-avatar {
    width: 80px;
    height: 80px;
    flex-shrink: 0;
    border-radius: 8px;
    overflow: hidden;
}

.item-cover img, .item-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-cover, .no-avatar {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.1);
    font-size: 2rem;
}

.item-info {
    flex: 1;
    min-width: 0;
}

.item-title {
    font-size: 1.2rem;
    margin-bottom: 8px;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.item-meta {
    color: #888;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.item-meta span {
    margin-right: 15px;
}

.item-stats {
    color: #666;
    font-size: 0.85rem;
    margin-bottom: 8px;
}

.item-rating {
    display: flex;
    align-items: center;
    gap: 8px;
}

.rating-score {
    color: #FFD700;
    font-weight: bold;
}

.rating-count {
    color: #888;
    font-size: 0.85rem;
}

.item-actions {
    flex-shrink: 0;
}

.btn-view {
    padding: 8px 16px;
    background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.btn-view:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.stats-section {
    background: rgba(255,255,255,0.02);
    border-radius: 15px;
    padding: 30px;
    border: 1px solid rgba(255,255,255,0.1);
}

.stats-section h2 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 1.8rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-item {
    text-align: center;
    padding: 20px;
    background: rgba(255,255,255,0.03);
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.1);
}

.stat-item h3 {
    color: #888;
    margin-bottom: 10px;
    font-size: 1rem;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: #fff;
    margin-bottom: 5px;
    background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
}

.loading, .error, .empty {
    text-align: center;
    padding: 40px;
    color: #888;
}

.error {
    color: #ff6b6b;
}

@media (max-width: 768px) {
    .container {
        padding: 10px;
    }
    
    .ranking-item {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .item-info {
        width: 100%;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>