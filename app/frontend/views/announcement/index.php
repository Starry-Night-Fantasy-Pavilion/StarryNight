<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>公告中心 - 星夜阁</title>
    <?php use app\config\FrontendConfig; ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('modules/membership.css')) ?>">
</head>
<body>
    <div class="membership-container">
        <!-- 页面头部 -->
        <header class="page-header">
            <div class="header-content">
                <h1>公告中心</h1>
                <p>查看系统公告和重要通知</p>
                
                <!-- 筛选器 -->
                <div class="filter-section">
                    <div class="filter-group">
                        <label for="categoryFilter">公告分类：</label>
                        <select id="categoryFilter" onchange="filterAnnouncements()">
                            <option value="">全部分类</option>
                            <option value="system_update">系统更新</option>
                            <option value="activity_notice">活动通知</option>
                            <option value="maintenance">维护公告</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="statusFilter">发布状态：</label>
                        <select id="statusFilter" onchange="filterAnnouncements()">
                            <option value="">全部状态</option>
                            <option value="1">已发布</option>
                            <option value="0">草稿</option>
                        </select>
                    </div>
                </div>
            </div>
        </header>

        <!-- 公告列表 -->
        <main class="announcements-main">
            <div class="announcements-list" id="announcementsList">
                <!-- 公告项目将通过JavaScript动态加载 -->
            </div>
            
            <!-- 分页 -->
            <div class="pagination" id="pagination">
                <!-- 分页信息将通过JavaScript动态生成 -->
            </div>
        </main>
    </div>

    <!-- 公告详情模态框 -->
    <div id="announcementModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">公告详情</h3>
                <button class="modal-close" onclick="closeAnnouncementModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="announcement-detail" id="modalContent">
                    <!-- 公告内容将通过JavaScript动态加载 -->
                </div>
                
                <div class="announcement-meta">
                    <div class="meta-item">
                        <span class="meta-label">发布时间：</span>
                        <span class="meta-value" id="modalPublishedAt"></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">分类：</span>
                        <span class="meta-value" id="modalCategory"></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">状态：</span>
                        <span class="meta-value" id="modalStatus"></span>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button class="btn btn-secondary" onclick="closeAnnouncementModal()">关闭</button>
                    <button class="btn btn-primary" id="markAsReadBtn" onclick="markAsRead()">标记已读</button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= htmlspecialchars(FrontendConfig::getThemeJsUrl('modules/membership.js')) ?>"></script>
    <script>
        let currentPage = 1;
        let currentType = '';
        let currentStatus = '';
        let totalPages = 1;

        // 页面加载完成后执行
        document.addEventListener('DOMContentLoaded', function() {
            initializeAnnouncements();
        });

        /**
         * 初始化公告功能
         */
        function initializeAnnouncements() {
            loadAnnouncements();
            loadUnreadCount();
        }

        /**
         * 加载公告列表
         */
        function loadAnnouncements() {
            const type = document.getElementById('categoryFilter').value;
            const status = document.getElementById('statusFilter').value;
            
            fetch('/announcement/list?type=' + type + '&status=' + status + '&page=' + currentPage, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderAnnouncements(data.data.announcements);
                    updatePagination(data.data.page, data.data.totalPages);
                    currentType = type;
                    currentStatus = status;
                    totalPages = data.data.totalPages;
                } else {
                    console.error('加载公告列表失败:', data.message);
                }
            })
            .catch(error => {
                console.error('网络错误:', error);
            });
        }

        /**
         * 加载未读数量
         */
        function loadUnreadCount() {
            fetch('/announcement/unread-count', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const unreadCount = data.data.unread_count;
                    updateUnreadCount(unreadCount);
                }
            })
            .catch(error => {
                console.error('加载未读数量失败:', error);
            });
        }

        /**
         * 渲染公告列表
         */
        function renderAnnouncements(announcements) {
            const announcementsList = document.getElementById('announcementsList');
            
            if (announcements.length === 0) {
                announcementsList.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">📢</div>
                        <p>暂无公告</p>
                    </div>
                `;
                return;
            }

            let html = '';
            announcements.forEach(announcement => {
                const isUnread = !announcement.read_at;
                const isTop = announcement.is_top;
                const isPopup = announcement.is_popup;
                
                html += `
                    <div class="announcement-item ${isUnread ? 'unread' : 'read'} ${isTop ? 'top' : ''} ${isPopup ? 'popup' : ''}">
                        <div class="announcement-header">
                            <div class="announcement-info">
                                <h3>${announcement.title}</h3>
                                <div class="announcement-meta">
                                    <span class="announcement-category">${getCategoryName(announcement.category)}</span>
                                    <span class="announcement-date">${formatDate(announcement.published_at)}</span>
                                </div>
                            </div>
                            <div class="announcement-status">
                                <span class="status-badge status-${announcement.status}">${getStatusText(announcement.status)}</span>
                            </div>
                        </div>
                        
                        <div class="announcement-actions">
                            <a href="javascript:void(0)" onclick="viewAnnouncement(${announcement.id})" class="btn btn-outline">查看详情</a>
                            ${isUnread ? `<a href="javascript:void(0)" onclick="markAsRead(${announcement.id})" class="btn btn-secondary">标记已读</a>` : ''}
                        </div>
                    </div>
                `;
            });
            
            announcementsList.innerHTML = html;
        }

        /**
         * 更新未读数量显示
         */
        function updateUnreadCount(count) {
            const unreadElements = document.querySelectorAll('.unread-count');
            unreadElements.forEach(element => {
                element.textContent = count > 0 ? count : '';
            });
        }

        /**
         * 更新分页
         */
        function updatePagination(page, total) {
            currentPage = page;
            totalPages = total;
            
            const pagination = document.getElementById('pagination');
            if (totalPages > 1) {
                let html = '';
                
                if (page > 1) {
                    html += `<a href="?page=${page - 1}&type=${currentType}&status=${currentStatus}" class="page-link">上一页</a>`;
                }
                
                html += `<span class="page-info">第 ${page} 页，共 ${totalPages} 页</span>`;
                
                if (page < totalPages) {
                    html += `<a href="?page=${page + 1}&type=${currentType}&status=${currentStatus}" class="page-link">下一页</a>`;
                }
                
                pagination.innerHTML = html;
            } else {
                pagination.innerHTML = `<span class="page-info">第 1 页，共 ${totalPages} 页</span>`;
            }
        }

        /**
         * 筛选公告
         */
        function filterAnnouncements() {
            loadAnnouncements();
        }

        /**
         * 查看公告详情
         */
        function viewAnnouncement(id) {
            fetch('/announcement/detail?id=' + id, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAnnouncementModal(data.data.announcement);
                    
                    // 如果是未读状态，标记为已读
                    if (!data.announcement.read_at) {
                        markAsRead(data.announcement.id);
                    }
                } else {
                    console.error('获取公告详情失败:', data.message);
                }
            })
            .catch(error => {
                console.error('网络错误:', error);
            });
        }

        /**
         * 标记公告为已读
         */
        function markAsRead(id) {
            fetch('/announcement/mark-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('公告已标记为已读', 'success');
                    loadAnnouncements();
                    loadUnreadCount();
                } else {
                    showNotification('标记失败', 'error');
                }
            })
            .catch(error => {
                console.error('网络错误:', error);
            });
        }

        /**
         * 显示公告详情模态框
         */
        function showAnnouncementModal(announcement) {
            document.getElementById('modalTitle').textContent = announcement.title;
            document.getElementById('modalPublishedAt').textContent = formatDate(announcement.published_at);
            document.getElementById('modalCategory').textContent = getCategoryName(announcement.category);
            document.getElementById('modalStatus').textContent = getStatusText(announcement.status);
            document.getElementById('modalContent').innerHTML = announcement.content;
            
            const modal = document.getElementById('announcementModal');
            modal.style.display = 'block';
            
            // 更新已读状态
            if (!announcement.read_at) {
                const markBtn = document.getElementById('markAsReadBtn');
                if (markBtn) {
                    markBtn.textContent = '已读';
                    markBtn.disabled = true;
                }
            }
            
            // 如果是弹窗公告，3秒后自动关闭
            if (announcement.is_popup) {
                setTimeout(() => {
                    if (modal.style.display === 'block') {
                        closeAnnouncementModal();
                    }
                }, 3000);
            }
        }

        /**
         * 关闭公告详情模态框
         */
        function closeAnnouncementModal() {
            const modal = document.getElementById('announcementModal');
            modal.style.display = 'none';
        }

        /**
         * 获取分类名称
         */
        function getCategoryName(category) {
            const categories = {
                'system_update': '系统更新',
                'activity_notice': '活动通知',
                'maintenance': '维护公告'
            };
            return categories[category] || '其他';
        }

        /**
         * 获取状态文本
         */
        function getStatusText(status) {
            const statusMap = {
                '0': '草稿',
                '1': '已发布'
            };
            return statusMap[status] || '未知';
        }

        /**
         * 格式化日期
         */
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('zh-CN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        /**
         * 显示通知
         */
        function showNotification(message, type = 'info') {
            // 创建通知元素
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            
            // 添加到页面
            document.body.appendChild(notification);
            
            // 3秒后自动移除
            setTimeout(() => {
                notification.classList.add('hide');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 3000);
            }, 100);
        }
    </script>
</body>
</html>

<?php
/**
 * 获取分类名称
 */
function getCategoryName($category) {
    $categories = [
        'system_update' => '系统更新',
        'activity_notice' => '活动通知',
        'maintenance' => '维护公告'
    ];
    return $categories[$category] ?? '其他';
}

/**
 * 获取状态文本
 */
function getStatusText($status) {
    $statusMap = [
        '0' => '草稿',
        '1' => '已发布'
    ];
    return $statusMap[$status] ?? '未知';
}

/**
 * 格式化日期
 */
function formatDate($dateString) {
    $date = new Date($dateString);
    return $date->format('Y-m-d H:i:s');
}
?>