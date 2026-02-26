/**
 * 通知弹窗功能模块
 * 处理通知栏点击弹窗显示所有通知的逻辑
 */
(function() {
    'use strict';
    
    function initNoticeModal() {
        var pill = document.getElementById('topBarNoticePill');
        var modal = document.getElementById('noticeModal');
        var modalBody = document.getElementById('noticeModalBody');
        var modalClose = document.getElementById('noticeModalClose');
        var modalOverlay = modal ? modal.querySelector('.notice-modal-overlay') : null;
        
        if (!pill || !modal || !modalBody) return;
        
        // 获取所有通知数据
        var allNoticesRaw = pill.getAttribute('data-all-notices') || '[]';
        var allNotices = [];
        try {
            allNotices = JSON.parse(allNoticesRaw);
        } catch (e) {
            console.error('Failed to parse notice data:', e);
            allNotices = [];
        }
        
        // 渲染通知列表
        function renderNotices() {
            if (!allNotices || allNotices.length === 0) {
                modalBody.innerHTML = '<div class="notice-modal-empty">暂无通知</div>';
                return;
            }
            
            // 按优先级排序
            var sorted = allNotices.slice().sort(function(a, b) {
                return (b.priority || 0) - (a.priority || 0);
            });
            
            var html = '<div class="notice-list">';
            sorted.forEach(function(notice) {
                var priority = notice.priority || 0;
                var level = 'low';
                if (priority >= 80) level = 'high';
                else if (priority >= 40) level = 'medium';
                
                var content = notice.content || '';
                var link = notice.link || null;
                var createdAt = notice.created_at || '';
                
                html += '<div class="notice-item notice-level-' + level + '">';
                html += '<div class="notice-item-header">';
                html += '<span class="notice-item-priority notice-priority-' + level + '">';
                if (level === 'high') html += '重要';
                else if (level === 'medium') html += '提醒';
                else html += '提示';
                html += '</span>';
                if (createdAt) {
                    html += '<span class="notice-item-time">' + escapeHtml(createdAt) + '</span>';
                }
                html += '</div>';
                html += '<div class="notice-item-content">';
                if (link) {
                    html += '<a href="' + escapeHtml(link) + '" target="_blank" rel="noopener">' + content + '</a>';
                } else {
                    html += content;
                }
                html += '</div>';
                html += '</div>';
            });
            html += '</div>';
            
            modalBody.innerHTML = html;
        }
        
        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // 打开弹窗
        function openModal() {
            renderNotices();
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            // 触发动画
            setTimeout(function() {
                modal.classList.add('visible');
            }, 10);
        }
        
        // 关闭弹窗
        function closeModal() {
            modal.classList.remove('visible');
            setTimeout(function() {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }, 300);
        }
        
        // 点击通知栏打开弹窗
        pill.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openModal();
        });
        
        // 关闭按钮
        if (modalClose) {
            modalClose.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeModal();
            });
        }
        
        // 点击遮罩层关闭
        if (modalOverlay) {
            modalOverlay.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeModal();
            });
        }
        
        // ESC 键关闭
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('visible')) {
                closeModal();
            }
        });
    }
    
    // DOM 加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNoticeModal);
    } else {
        initNoticeModal();
    }
})();
