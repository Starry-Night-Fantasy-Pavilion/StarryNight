<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户反馈 - 星夜阁</title>
    <?php use app\config\FrontendConfig; ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('modules/membership.css')) ?>">
    <style>
        .feedback-form {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 5px rgba(102, 126, 234, 0.2);
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .attachment-upload {
            border: 2px dashed #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
        }
        
        .attachment-upload:hover {
            border-color: #667eea;
            background: #f0f8ff;
        }
        
        .attachment-list {
            margin-top: 15px;
        }
        
        .attachment-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .attachment-info {
            flex: 1;
        }
        
        .attachment-name {
            font-weight: 600;
            color: #333;
        }
        
        .attachment-size {
            font-size: 0.9rem;
            color: #666;
        }
        
        .attachment-remove {
            color: #dc3545;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-outline {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-outline:hover {
            background: #667eea;
            color: white;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .statistics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .feedback-list {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .feedback-item {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .feedback-item:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }
        
        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .feedback-info {
            flex: 1;
        }
        
        .feedback-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .feedback-meta {
            font-size: 0.9rem;
            color: #666;
        }
        
        .feedback-content {
            color: #666;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        .feedback-actions {
            display: flex;
            gap: 10px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: #ffc107;
            color: #856404;
        }
        
        .status-processing {
            background: #17a2b8;
            color: white;
        }
        
        .status-resolved {
            background: #28a745;
            color: white;
        }
        
        .status-closed {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="membership-container">
        <!-- 页面头部 -->
        <header class="page-header">
            <div class="header-content">
                <h1>用户反馈</h1>
                <p>我们重视您的意见和建议，帮助我们不断改进产品和服务</p>
            </div>
        </header>

        <!-- 反馈表单 -->
        <main class="feedback-main">
            <div class="feedback-form">
                <h2>提交反馈</h2>
                <form id="feedbackForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="type">反馈类型</label>
                        <select name="type" id="type" required>
                            <option value="">请选择反馈类型</option>
                            <option value="suggestion">功能建议</option>
                            <option value="bug_report">Bug报告</option>
                            <option value="other">其他</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="title">反馈标题</label>
                        <input type="text" name="title" id="title" required placeholder="请简要描述问题或建议">
                    </div>
                    
                    <div class="form-group">
                        <label for="content">详细描述</label>
                        <textarea name="content" id="content" required placeholder="请详细描述您遇到的问题或建议，包括具体的错误信息、操作步骤等"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="attachments">附件上传</label>
                        <div class="attachment-upload">
                            <input type="file" name="attachments" id="attachments" multiple accept="image/*,.pdf,.doc,.doc,.txt">
                            <div class="upload-text">点击或拖拽文件到此处上传</div>
                        </div>
                        <div class="attachment-list" id="attachmentList"></div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="clearForm()">清空</button>
                        <button type="submit" class="btn btn-primary">提交反馈</button>
                    </div>
                </form>
            </div>
        </main>

        <!-- 我的反馈 -->
        <section class="my-feedback">
            <h2>我的反馈</h2>
            
            <!-- 筛选器 -->
            <div class="filter-section">
                <div class="filter-group">
                    <label for="typeFilter">反馈类型：</label>
                    <select id="typeFilter" onchange="filterFeedbacks()">
                        <option value="">全部类型</option>
                        <option value="suggestion">功能建议</option>
                        <option value="bug_report">Bug报告</option>
                        <option value="other">其他</option>
                    </select>
                    
                    <label for="statusFilter">处理状态：</label>
                    <select id="statusFilter" onchange="filterFeedbacks()">
                        <option value="">全部状态</option>
                        <option value="1">待处理</option>
                        <option value="2">处理中</option>
                        <option value="3">已解决</option>
                        <option value="4">已关闭</option>
                    </select>
                </div>
            </div>
            
            <!-- 反馈列表 -->
            <div class="feedback-list" id="feedbackList">
                <!-- 反馈项目将通过JavaScript动态加载 -->
            </div>
            
            <!-- 分页 -->
            <div class="pagination" id="pagination">
                <!-- 分页信息将通过JavaScript动态生成 -->
            </div>
        </section>

        <!-- 反馈统计 -->
        <section class="feedback-statistics">
            <h2>反馈统计</h2>
            <div class="statistics-grid" id="statisticsGrid">
                <!-- 统计卡片将通过JavaScript动态生成 -->
            </div>
        </section>
    </div>

    <script src="<?= htmlspecialchars(FrontendConfig::getThemeJsUrl('modules/membership.js')) ?>"></script>
    <script>
        let currentPage = 1;
        let currentType = '';
        let currentStatus = '';

        // 页面加载完成后执行
        document.addEventListener('DOMContentLoaded', function() {
            loadFeedbacks();
            loadStatistics();
        });

        // 加载反馈列表
        function loadFeedbacks() {
            const type = document.getElementById('typeFilter').value;
            const status = document.getElementById('statusFilter').value;
            
            fetch('/feedback/all?page=' + currentPage + '&type=' + type + '&status=' + status, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderFeedbackList(data.data.feedbacks);
                    updatePagination(data.data.page, data.data.totalPages);
                    currentType = type;
                    currentStatus = status;
                } else {
                    console.error('加载反馈列表失败:', data.message);
                }
            });
        }

        // 加载统计数据
        function loadStatistics() {
            fetch('/feedback/statistics', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderStatistics(data.data.statistics);
                } else {
                    console.error('加载统计数据失败:', data.message);
                }
            });
        }

        // 渲染反馈列表
        function renderFeedbackList(feedbacks) {
            const feedbackList = document.getElementById('feedbackList');
            
            if (feedbacks.length === 0) {
                feedbackList.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">📝</div>
                        <p>暂无反馈记录</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            feedbacks.forEach(feedback => {
                html += `
                    <div class="feedback-item">
                        <div class="feedback-header">
                            <div class="feedback-info">
                                <h3>${feedback.title}</h3>
                                <div class="feedback-meta">
                                    <span class="status-badge status-${feedback.status}">${getStatusText(feedback.status)}</span>
                                    <span class="feedback-date">${formatDate(feedback.created_at)}</span>
                                </div>
                            </div>
                            <div class="feedback-actions">
                                <a href="/feedback/detail?id=${feedback.id}" class="btn btn-outline">查看详情</a>
                            </div>
                        </div>
                        <div class="feedback-content">
                            ${feedback.content ? feedback.content.substring(0, 100) + '...' : '无详细描述'}
                        </div>
                    </div>
                `;
            });
            
            feedbackList.innerHTML = html;
        }

        // 渲染统计数据
        function renderStatistics(statistics) {
            const statisticsGrid = document.getElementById('statisticsGrid');
            
            if (statistics.length === 0) {
                statisticsGrid.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">📊</div>
                        <p>暂无统计数据</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            statistics.forEach(stat => {
                html += `
                    <div class="stat-card">
                        <div class="stat-icon">
                            ${getTypeIcon(stat.type)}
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">${stat.count}</div>
                            <div class="stat-label">${getTypeName(stat.type)}</div>
                        </div>
                        <div class="stat-label">反馈数量</div>
                        ${stat.resolved_rate ? `<div class="stat-label">解决率</div><div class="stat-value">${stat.resolved_rate}%</div>` : ''}
                    </div>
                `;
            });
            
            statisticsGrid.innerHTML = html;
        }

        // 更新分页
        function updatePagination(page, totalPages) {
            const pagination = document.getElementById('pagination');
            
            let html = '';
            
            if (page > 1) {
                html += `<a href="?page=${page - 1}&type=${currentType}&status=${currentStatus}" class="page-link">上一页</a>`;
            }
            
            html += `<span class="page-info">第 ${page} 页，共 ${totalPages} 页</span>`;
            
            if (page < totalPages) {
                html += `<a href="?page=${page + 1}&type=${currentType}&status=${currentStatus}" class="page-link">下一页</a>`;
            }
            
            pagination.innerHTML = html;
        }

        // 清空表单
        function clearForm() {
            document.getElementById('feedbackForm').reset();
            document.getElementById('attachmentList').innerHTML = '';
        }

        // 格式化日期
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

        // 获取状态文本
        function getStatusText(status) {
            const statusMap = {
                1: '待处理',
                2: '处理中',
                3: '已解决',
                4: '已关闭'
            };
            return statusMap[status] || status;
        }

        // 获取类型名称
        function getTypeName(type) {
            const typeMap = {
                'suggestion': '功能建议',
                'bug_report': 'Bug报告',
                'other': '其他'
            };
            return typeMap[type] || type;
        }

        // 获取类型图标
        function getTypeIcon(type) {
            const iconMap = {
                'suggestion': '💡',
                'bug_report': '🐛',
                'other': '📝'
            };
            return iconMap[type] || '📝';
        }

        // 筛选反馈
        function filterFeedbacks() {
            const type = document.getElementById('typeFilter').value;
            const status = document.getElementById('statusFilter').value;
            currentPage = 1;
            
            loadFeedbacks();
        }
    </script>
</body>
</html>