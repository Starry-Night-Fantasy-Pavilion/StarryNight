/**
 * AI音乐创作系统主要JavaScript文件
 */

// 全局变量
let currentProject = null;
let currentUser = null;

// API基础URL
const API_BASE = '/api/ai-music';

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * 初始化应用
 */
function initializeApp() {
    loadCurrentUser();
    loadUserStats();
    loadRecentProjects();
    loadRecommendedTemplates();
    loadPopularProjects();
    setupEventListeners();
}

/**
 * 设置事件监听器
 */
function setupEventListeners() {
    // 搜索功能
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
    }

    // 用户菜单
    const userMenu = document.querySelector('.user-menu');
    if (userMenu) {
        userMenu.addEventListener('click', toggleUserMenu);
    }

    // 点击外部关闭下拉菜单
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            closeAllDropdowns();
        }
    });
}

/**
 * 加载当前用户信息
 */
async function loadCurrentUser() {
    try {
        const response = await fetch('/api/user/current');
        if (response.ok) {
            const data = await response.json();
            currentUser = data.data;
            updateUserDisplay();
        }
    } catch (error) {
        console.error('加载用户信息失败:', error);
    }
}

/**
 * 更新用户显示
 */
function updateUserDisplay() {
    if (!currentUser) return;

    const username = document.querySelector('.username');
    const userAvatar = document.querySelector('.user-avatar img');

    if (username) {
        username.textContent = currentUser.username;
    }

    if (userAvatar && currentUser.avatar) {
        userAvatar.src = currentUser.avatar;
    }
}

/**
 * 加载用户统计信息
 */
async function loadUserStats() {
    try {
        const response = await fetch(`${API_BASE}/user/stats`);
        if (response.ok) {
            const data = await response.json();
            updateUserStats(data.data);
        }
    } catch (error) {
        console.error('加载用户统计失败:', error);
    }
}

/**
 * 更新用户统计显示
 */
function updateUserStats(stats) {
    const statsContainer = document.getElementById('userStats');
    if (!statsContainer) return;

    const totalProjects = statsContainer.querySelector('.stat-item:nth-child(1) .stat-value');
    const completedProjects = statsContainer.querySelector('.stat-item:nth-child(2) .stat-value');
    const inProgressProjects = statsContainer.querySelector('.stat-item:nth-child(3) .stat-value');

    if (totalProjects) totalProjects.textContent = stats.total_projects || 0;
    if (completedProjects) completedProjects.textContent = stats.completed_count || 0;
    if (inProgressProjects) inProgressProjects.textContent = stats.in_progress_count || 0;
}

/**
 * 加载最近项目
 */
async function loadRecentProjects() {
    try {
        const response = await fetch(`${API_BASE}/projects?page=1&limit=5`);
        if (response.ok) {
            const data = await response.json();
            displayRecentProjects(data.data);
        }
    } catch (error) {
        console.error('加载最近项目失败:', error);
    }
}

/**
 * 显示最近项目
 */
function displayRecentProjects(projects) {
    const container = document.getElementById('recentProjects');
    if (!container) return;

    if (projects.length === 0) {
        container.innerHTML = '<p class="text-muted">暂无项目</p>';
        return;
    }

    container.innerHTML = projects.map(project => `
        <div class="recent-project-item" onclick="openProject(${project.id})">
            <div class="recent-project-title">${project.title}</div>
            <div class="recent-project-meta">
                ${project.genre || '未分类'} • ${formatDate(project.updated_at)}
            </div>
        </div>
    `).join('');
}

/**
 * 加载推荐模板
 */
async function loadRecommendedTemplates() {
    try {
        const response = await fetch(`${API_BASE}/templates/recommended`);
        if (response.ok) {
            const data = await response.json();
            displayTemplates(data.data, 'recommendedTemplates');
        }
    } catch (error) {
        console.error('加载推荐模板失败:', error);
    }
}

/**
 * 加载热门项目
 */
async function loadPopularProjects() {
    try {
        const response = await fetch(`${API_BASE}/projects/popular?limit=6`);
        if (response.ok) {
            const data = await response.json();
            displayProjects(data.data, 'popularProjects');
        }
    } catch (error) {
        console.error('加载热门项目失败:', error);
    }
}

/**
 * 显示模板列表
 */
function displayTemplates(templates, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (templates.length === 0) {
        container.innerHTML = '<p class="text-muted">暂无模板</p>';
        return;
    }

    container.innerHTML = templates.map(template => `
        <div class="template-card" onclick="useTemplate(${template.id})">
            <div class="template-image">
                <i class="fas fa-music"></i>
            </div>
            <div class="template-content">
                <div class="template-title">${template.name}</div>
                <div class="template-description">${template.description}</div>
                <div class="template-meta">
                    <span>${template.style}</span>
                    ${template.is_premium ? '<span class="badge badge-warning">付费</span>' : ''}
                </div>
            </div>
        </div>
    `).join('');
}

/**
 * 显示项目列表
 */
function displayProjects(projects, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (projects.length === 0) {
        container.innerHTML = '<p class="text-muted">暂无项目</p>';
        return;
    }

    container.innerHTML = projects.map(project => `
        <div class="project-card" onclick="openProject(${project.id})">
            <div class="project-image">
                <i class="fas fa-music"></i>
            </div>
            <div class="project-content">
                <div class="project-title">${project.title}</div>
                <div class="project-description">${project.description || '暂无描述'}</div>
                <div class="project-meta">
                    <span>${project.genre || '未分类'}</span>
                    <span>by ${project.username}</span>
                </div>
            </div>
        </div>
    `).join('');
}

/**
 * 创建新项目
 */
function createNewProject() {
    showModal('newProjectModal');
}

/**
 * 提交新项目表单
 */
async function submitNewProject() {
    const form = document.getElementById('newProjectForm');
    const formData = new FormData(form);
    
    const projectData = {
        title: formData.get('title'),
        genre: formData.get('genre'),
        description: formData.get('description'),
        bpm: formData.get('bpm') ? parseInt(formData.get('bpm')) : null,
        key_signature: formData.get('key_signature')
    };

    try {
        showLoading();
        const response = await fetch(`${API_BASE}/project`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(projectData)
        });

        if (response.ok) {
            const data = await response.json();
            showToast('项目创建成功', 'success');
            closeModal('newProjectModal');
            openProject(data.data.id);
        } else {
            const error = await response.json();
            showToast(error.error || '创建项目失败', 'error');
        }
    } catch (error) {
        console.error('创建项目失败:', error);
        showToast('创建项目失败', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * 打开项目
 */
function openProject(projectId) {
    window.location.href = `/ai-music/editor/${projectId}`;
}

/**
 * 使用模板
 */
function useTemplate(templateId) {
    window.location.href = `/ai-music/create-from-template/${templateId}`;
}

/**
 * 导入项目
 */
function importProject() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json,.mid,.midi';
    input.onchange = handleFileImport;
    input.click();
}

/**
 * 处理文件导入
 */
async function handleFileImport(event) {
    const file = event.target.files[0];
    if (!file) return;

    try {
        showLoading();
        const formData = new FormData();
        formData.append('file', file);

        const response = await fetch(`${API_BASE}/import`, {
            method: 'POST',
            body: formData
        });

        if (response.ok) {
            const data = await response.json();
            showToast('项目导入成功', 'success');
            openProject(data.data.id);
        } else {
            const error = await response.json();
            showToast(error.error || '导入项目失败', 'error');
        }
    } catch (error) {
        console.error('导入项目失败:', error);
        showToast('导入项目失败', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * 打开功能模块
 */
function openFeature(feature) {
    window.location.href = `/ai-music/feature/${feature}`;
}

/**
 * 搜索处理
 */
async function handleSearch(event) {
    const keyword = event.target.value.trim();
    if (keyword.length < 2) return;

    try {
        const response = await fetch(`${API_BASE}/search?keyword=${encodeURIComponent(keyword)}`);
        if (response.ok) {
            const data = await response.json();
            // 显示搜索结果
            displaySearchResults(data.data);
        }
    } catch (error) {
        console.error('搜索失败:', error);
    }
}

/**
 * 显示搜索结果
 */
function displaySearchResults(results) {
    // 这里可以实现搜索结果的显示逻辑
    console.log('搜索结果:', results);
}

/**
 * 显示帮助
 */
function showHelp() {
    window.open('/ai-music/help', '_blank');
}

/**
 * 切换用户菜单
 */
function toggleUserMenu() {
    const menu = document.querySelector('.dropdown-menu');
    if (menu) {
        menu.classList.toggle('show');
    }
}

/**
 * 关闭所有下拉菜单
 */
function closeAllDropdowns() {
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.classList.remove('show');
    });
}

/**
 * 显示模态框
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

/**
 * 关闭模态框
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

/**
 * 显示加载提示
 */
function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

/**
 * 隐藏加载提示
 */
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

/**
 * 显示提示消息
 */
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icon = getToastIcon(type);
    toast.innerHTML = `
        <i class="fas ${icon}"></i>
        <span>${message}</span>
    `;

    container.appendChild(toast);

    // 自动移除
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

/**
 * 获取提示图标
 */
function getToastIcon(type) {
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    return icons[type] || icons.info;
}

/**
 * 格式化日期
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;

    if (diff < 60000) { // 1分钟内
        return '刚刚';
    } else if (diff < 3600000) { // 1小时内
        return Math.floor(diff / 60000) + '分钟前';
    } else if (diff < 86400000) { // 1天内
        return Math.floor(diff / 3600000) + '小时前';
    } else if (diff < 604800000) { // 1周内
        return Math.floor(diff / 86400000) + '天前';
    } else {
        return date.toLocaleDateString('zh-CN');
    }
}

/**
 * 防抖函数
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * 节流函数
 */
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * 确认对话框
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * 生成随机ID
 */
function generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

/**
 * 格式化文件大小
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * 格式化时长
 */
function formatDuration(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
}

// 全局错误处理
window.addEventListener('error', function(event) {
    console.error('全局错误:', event.error);
    showToast('发生了一个错误，请刷新页面重试', 'error');
});

// 网络错误处理
window.addEventListener('unhandledrejection', function(event) {
    console.error('未处理的Promise拒绝:', event.reason);
    showToast('网络请求失败，请检查网络连接', 'error');
});