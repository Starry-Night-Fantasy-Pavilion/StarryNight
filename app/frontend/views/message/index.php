<?php
/** @var array $announcements */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var int $totalPages */
/** @var int $unreadCount */
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<?php
$title = '消息接收';
$currentPage = 'messages';
require __DIR__ . '/../user_center_layout.php';
?>

<div class="content-container">
    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <h2 class="dashboard-card-title">站内公告</h2>
            <div class="dashboard-card-actions">
                <?php if ($unreadCount > 0): ?>
                    <span class="unread-badge"><?= $h($unreadCount) ?> 条未读</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="announcement-list">
            <?php if (empty($announcements)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📢</div>
                    <p class="empty-state-text">暂无公告</p>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-item <?= $announcement['is_read'] ? 'read' : 'unread' ?>" 
                         data-announcement-id="<?= (int)$announcement['id'] ?>">
                        <div class="announcement-header">
                            <div class="announcement-title-row">
                                <?php if (!$announcement['is_read']): ?>
                                    <span class="unread-dot"></span>
                                <?php endif; ?>
                                <?php if ($announcement['is_top']): ?>
                                    <span class="top-badge">置顶</span>
                                <?php endif; ?>
                                <h3 class="announcement-title"><?= $h($announcement['title']) ?></h3>
                            </div>
                            <div class="announcement-meta">
                                <span class="announcement-category">
                                    <?php
                                    $categories = [
                                        'system_update' => '系统更新',
                                        'activity_notice' => '活动通知',
                                        'maintenance' => '维护公告'
                                    ];
                                    echo $h($categories[$announcement['category']] ?? '系统公告');
                                    ?>
                                </span>
                                <span class="announcement-time">
                                    <?= $h(date('Y-m-d H:i', strtotime($announcement['published_at'] ?? $announcement['created_at']))) ?>
                                </span>
                                <?php if ($announcement['is_read']): ?>
                                    <span class="read-status">已读</span>
                                <?php else: ?>
                                    <span class="read-status unread">未读</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="announcement-content">
                            <?= $announcement['content'] ?>
                        </div>
                        <?php if (!$announcement['is_read']): ?>
                            <div class="announcement-actions">
                                <button class="btn-mark-read" onclick="markAsRead(<?= (int)$announcement['id'] ?>)">
                                    标记为已读
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="pagination-btn">上一页</a>
                <?php endif; ?>
                
                <span class="pagination-info">第 <?= $h($page) ?> 页 / 共 <?= $h($totalPages) ?> 页</span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="pagination-btn">下一页</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.announcement-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.announcement-item {
    padding: 20px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    transition: all 0.2s ease;
}

.announcement-item.unread {
    background: rgba(59, 130, 246, 0.1);
    border-color: rgba(59, 130, 246, 0.3);
}

.announcement-item:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.12);
    transform: translateY(-2px);
}

.announcement-header {
    margin-bottom: 12px;
}

.announcement-title-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.unread-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #ef4444;
    flex-shrink: 0;
}

.top-badge {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    background: rgba(234, 179, 8, 0.2);
    color: #facc15;
    font-weight: 600;
}

.announcement-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary, #f1f5f9);
    margin: 0;
}

.announcement-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.85rem;
    color: var(--text-secondary, #94a3b8);
}

.announcement-category {
    padding: 2px 8px;
    border-radius: 4px;
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa;
}

.announcement-time {
    color: var(--text-secondary, #94a3b8);
}

.read-status {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.read-status.unread {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.announcement-content {
    color: var(--text-primary, #f1f5f9);
    line-height: 1.6;
    margin-bottom: 12px;
}

.announcement-actions {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.btn-mark-read {
    padding: 6px 16px;
    border-radius: 6px;
    background: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
    border: 1px solid rgba(59, 130, 246, 0.3);
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.btn-mark-read:hover {
    background: rgba(59, 130, 246, 0.3);
    border-color: rgba(59, 130, 246, 0.5);
}

.unread-badge {
    padding: 4px 12px;
    border-radius: 999px;
    background: rgba(239, 68, 68, 0.2);
    color: #fecaca;
    font-size: 0.875rem;
    font-weight: 600;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.pagination-btn {
    padding: 8px 16px;
    border-radius: 8px;
    background: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
    text-decoration: none;
    border: 1px solid rgba(59, 130, 246, 0.3);
    transition: all 0.2s ease;
}

.pagination-btn:hover {
    background: rgba(59, 130, 246, 0.3);
    border-color: rgba(59, 130, 246, 0.5);
}

.pagination-info {
    color: var(--text-secondary, #94a3b8);
    font-size: 0.875rem;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.empty-state-text {
    color: var(--text-secondary, #94a3b8);
    font-size: 1rem;
}
</style>

<script>
function markAsRead(announcementId) {
    fetch('/messages/mark-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'announcement_id=' + announcementId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 更新UI
            const item = document.querySelector(`[data-announcement-id="${announcementId}"]`);
            if (item) {
                item.classList.remove('unread');
                item.classList.add('read');
                
                // 移除未读标记
                const unreadDot = item.querySelector('.unread-dot');
                if (unreadDot) unreadDot.remove();
                
                // 更新状态
                const readStatus = item.querySelector('.read-status');
                if (readStatus) {
                    readStatus.textContent = '已读';
                    readStatus.classList.remove('unread');
                }
                
                // 移除标记按钮
                const actions = item.querySelector('.announcement-actions');
                if (actions) actions.remove();
            }
            
            // 更新未读数量
            if (data.data && data.data.unread_count !== undefined) {
                updateUnreadCount(data.data.unread_count);
            }
        } else {
            alert(data.message || '操作失败');
        }
    })
    .catch(error => {
        console.error('标记已读失败:', error);
        alert('操作失败，请稍后重试');
    });
}

function updateUnreadCount(count) {
    // 更新页面上的未读数量显示
    const badge = document.querySelector('.unread-badge');
    if (count > 0) {
        if (badge) {
            badge.textContent = count + ' 条未读';
        } else {
            const actions = document.querySelector('.dashboard-card-actions');
            if (actions) {
                const newBadge = document.createElement('span');
                newBadge.className = 'unread-badge';
                newBadge.textContent = count + ' 条未读';
                actions.appendChild(newBadge);
            }
        }
    } else {
        if (badge) badge.remove();
    }
    
    // 更新顶部导航栏的未读数量
    const topBarBadge = document.querySelector('.top-bar .icon-btn[title="通知"] .unread-count');
    if (count > 0) {
        if (topBarBadge) {
            topBarBadge.textContent = count;
        } else {
            const notifyBtn = document.querySelector('.top-bar .icon-btn[title="通知"]');
            if (notifyBtn) {
                const newBadge = document.createElement('span');
                newBadge.className = 'unread-count';
                newBadge.textContent = count;
                newBadge.style.cssText = 'position: absolute; top: 6px; right: 6px; width: 18px; height: 18px; background: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; color: #fff; font-weight: 600; border: 2px solid var(--bg-sidebar, rgba(15, 23, 42, 0.7));';
                notifyBtn.style.position = 'relative';
                notifyBtn.appendChild(newBadge);
            }
        }
    } else {
        if (topBarBadge) topBarBadge.remove();
    }
}

// 页面加载时检查未读数量
document.addEventListener('DOMContentLoaded', function() {
    fetch('/messages/unread-count')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                updateUnreadCount(data.data.unread_count || 0);
            }
        })
        .catch(error => {
            console.error('获取未读数量失败:', error);
        });
});
</script>
