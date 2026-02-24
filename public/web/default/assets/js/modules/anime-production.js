// AI动漫制作工作台 JavaScript

// 全局变量
let currentSection = 'dashboard';
let currentPage = 1;
let currentFilters = {
    project: 'all',
    template: 'all',
    history: 'all'
};

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// 初始化应用
function initializeApp() {
    loadDashboard();
    setupNavigation();
    setupEventListeners();
    setupModalHandlers();
    setupInfiniteScroll();
}

// 导航设置
function setupNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.content-section');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // 移除所有active类
            navLinks.forEach(l => l.classList.remove('active'));
            sections.forEach(s => s.classList.remove('active'));
            
            // 添加active类到当前链接和对应section
            this.classList.add('active');
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            if (targetSection) {
                targetSection.classList.add('active');
                currentSection = targetId;
                
                // 根据不同section加载不同数据
                switch(targetId) {
                    case 'dashboard':
                        loadDashboard();
                        break;
                    case 'projects':
                        loadProjects();
                        break;
                    case 'templates':
                        loadTemplates();
                        break;
                    case 'ai-assistant':
                        // AI助手页面不需要额外加载数据
                        break;
                    case 'history':
                        loadHistory();
                        break;
                }
            }
        });
    });
}

// 设置事件监听器
function setupEventListeners() {
    // 项目过滤器
    const projectFilter = document.getElementById('project-filter');
    if (projectFilter) {
        projectFilter.addEventListener('change', function() {
            currentFilters.project = this.value;
            currentPage = 1;
            loadProjects();
        });
    }
    
    // 模板分类过滤
    const categoryBtns = document.querySelectorAll('.category-btn');
    categoryBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            categoryBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilters.template = this.dataset.category;
            loadTemplates();
        });
    });
    
    // 历史记录过滤器
    const historyTypeFilter = document.getElementById('history-type-filter');
    const historyDateFilter = document.getElementById('history-date-filter');
    
    if (historyTypeFilter) {
        historyTypeFilter.addEventListener('change', function() {
            currentFilters.history = this.value;
            currentPage = 1;
            loadHistory();
        });
    }
    
    if (historyDateFilter) {
        historyDateFilter.addEventListener('change', function() {
            currentPage = 1;
            loadHistory();
        });
    }
}

// 设置模态框处理器
function setupModalHandlers() {
    // 点击模态框外部关闭
    const modalOverlay = document.getElementById('modal-overlay');
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                hideAllModals();
            }
        });
    }
    
    // ESC键关闭模态框
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideAllModals();
        }
    });
}

// 设置无限滚动
function setupInfiniteScroll() {
    const contentArea = document.querySelector('.content-area');
    if (contentArea) {
        let isLoading = false;
        
        contentArea.addEventListener('scroll', function() {
            if (isLoading) return;
            
            const scrollHeight = contentArea.scrollHeight;
            const scrollTop = contentArea.scrollTop;
            const clientHeight = contentArea.clientHeight;
            
            if (scrollTop + clientHeight >= scrollHeight - 100) {
                isLoading = true;
                
                // 根据当前section加载更多数据
                switch(currentSection) {
                    case 'projects':
                        loadProjects(true);
                        break;
                    case 'templates':
                        loadTemplates(true);
                        break;
                    case 'history':
                        loadHistory(true);
                        break;
                }
                
                setTimeout(() => {
                    isLoading = false;
                }, 1000);
            }
        });
    }
}

// 加载仪表板数据
async function loadDashboard() {
    try {
        showLoading();
        const response = await fetch('/api/anime-production/dashboard');
        const data = await response.json();
        
        if (data.success) {
            updateDashboard(data.data);
        } else {
            showError('加载仪表板失败：' + data.error);
        }
    } catch (error) {
        console.error('加载仪表板失败:', error);
        showError('加载仪表板失败，请重试');
    } finally {
        hideLoading();
    }
}

// 更新仪表板数据
function updateDashboard(data) {
    // 更新统计数据
    updateStatElement('total-projects', data.project_stats.total_projects);
    updateStatElement('completed-projects', data.project_stats.completed_projects);
    updateStatElement('in-progress-projects', data.project_stats.in_progress_projects);
    
    // 更新最近活动
    updateRecentActivities(data.recent_activity);
    
    // 更新生成历史
    updateGenerationHistory(data.recent_generations);
    
    // 更新进行中的任务
    updateOngoingTasks(data.ongoing_tasks);
}

// 更新统计元素
function updateStatElement(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = value;
    }
}

// 更新最近活动
function updateRecentActivities(activities) {
    const container = document.getElementById('recent-activities');
    if (!container) return;
    
    if (activities.length === 0) {
        container.innerHTML = '<div class="no-data">暂无最近活动</div>';
        return;
    }
    
    container.innerHTML = activities.map(activity => `
        <div class="activity-item">
            <div class="activity-title">${escapeHtml(activity.title)}</div>
            <div class="activity-status ${activity.status}">${getStatusText(activity.status)}</div>
            <div class="activity-time">${formatTime(activity.updated_at)}</div>
        </div>
    `).join('');
}

// 更新生成历史
function updateGenerationHistory(generations) {
    const container = document.getElementById('generation-history');
    if (!container) return;
    
    if (generations.length === 0) {
        container.innerHTML = '<div class="no-data">暂无生成历史</div>';
        return;
    }
    
    container.innerHTML = generations.map(generation => {
        const result = generation.result ? JSON.parse(generation.result) : {};
        return `
            <div class="history-item">
                <div class="history-type">${getGenerationTypeText(generation.generation_type)}</div>
                <div class="history-title">${escapeHtml(result.title || '生成内容')}</div>
                <div class="history-time">${formatTime(generation.created_at)}</div>
            </div>
        `;
    }).join('');
}

// 更新进行中的任务
function updateOngoingTasks(tasks) {
    const container = document.getElementById('ongoing-tasks');
    if (!container) return;
    
    if (tasks.length === 0) {
        container.innerHTML = '<div class="no-data">暂无进行中的任务</div>';
        return;
    }
    
    container.innerHTML = tasks.map(task => `
        <div class="task-item">
            <div class="task-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${task.progress}%"></div>
                </div>
                <span class="progress-text">${task.progress}%</span>
            </div>
            <div class="task-info">
                <div class="task-title">${escapeHtml(task.title)}</div>
                <div class="task-project">${escapeHtml(task.project_title)}</div>
            </div>
        </div>
    `).join('');
}

// 加载项目列表
async function loadProjects(append = false) {
    try {
        if (!append) {
            currentPage = 1;
        }
        
        showLoading();
        const response = await fetch(`/api/anime-production/projects?page=${currentPage}&limit=12&status=${currentFilters.project}`);
        const data = await response.json();
        
        if (data.success) {
            if (append) {
                appendProjects(data.data.projects);
            } else {
                updateProjects(data.data.projects);
                updatePagination('projects-pagination', data.data.pagination);
            }
        } else {
            showError('加载项目失败：' + data.error);
        }
    } catch (error) {
        console.error('加载项目失败:', error);
        showError('加载项目失败，请重试');
    } finally {
        hideLoading();
    }
}

// 更新项目列表
function updateProjects(projects) {
    const container = document.getElementById('projects-grid');
    if (!container) return;
    
    if (projects.length === 0) {
        container.innerHTML = '<div class="no-data">暂无项目</div>';
        return;
    }
    
    container.innerHTML = projects.map(project => `
        <div class="project-card" onclick="viewProject(${project.id})">
            <h3>${escapeHtml(project.title)}</h3>
            <div class="project-meta">
                <span class="project-status ${project.status}">${getStatusText(project.status)}</span>
                <span class="project-type">${getProjectTypeText(project.type)}</span>
                <span class="project-genre">${project.genre || '未分类'}</span>
            </div>
            <div class="project-description">${escapeHtml(project.description || '暂无描述')}</div>
            <div class="project-stats">
                <span>集数: ${project.target_episodes || 0}</span>
                <span>完成: ${project.completed_episodes || 0}</span>
            </div>
            <div class="project-updated">${formatTime(project.updated_at)}</div>
        </div>
    `).join('');
}

// 更新分页
function updatePagination(containerId, pagination) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const { current_page, total_pages, total_items } = pagination;
    
    container.innerHTML = `
        <button onclick="goToPage(${current_page - 1})" ${current_page <= 1 ? 'disabled' : ''}>上一页</button>
        <span>第 ${current_page} 页 / 共 ${total_pages} 页</span>
        <button onclick="goToPage(${current_page + 1})" ${current_page >= total_pages ? 'disabled' : ''}>下一页</button>
        <span>总计 ${total_items} 个项目</span>
    `;
}

// 页面跳转
function goToPage(page) {
    currentPage = page;
    loadProjects();
}

// 查看项目详情
function viewProject(projectId) {
    // 这里可以跳转到项目详情页面或显示详情模态框
    console.log('查看项目:', projectId);
    // window.location.href = `/anime-production/project/${projectId}`;
}

// 加载模板
async function loadTemplates(append = false) {
    try {
        if (!append) {
            currentPage = 1;
        }
        
        showLoading();
        const response = await fetch(`/api/anime-production/templates?page=${currentPage}&category=${currentFilters.template}`);
        const data = await response.json();
        
        if (data.success) {
            if (append) {
                appendTemplates(data.data.templates);
            } else {
                updateTemplates(data.data.templates);
                updatePagination('templates-pagination', data.data.pagination);
            }
        } else {
            showError('加载模板失败：' + data.error);
        }
    } catch (error) {
        console.error('加载模板失败:', error);
        showError('加载模板失败，请重试');
    } finally {
        hideLoading();
    }
}

// 更新模板列表
function updateTemplates(templates) {
    const container = document.getElementById('templates-grid');
    if (!container) return;
    
    if (templates.length === 0) {
        container.innerHTML = '<div class="no-data">暂无模板</div>';
        return;
    }
    
    container.innerHTML = templates.map(template => `
        <div class="template-card" onclick="useTemplate(${template.id})">
            <div class="template-preview">
                <img src="${template.thumbnail || '/web/default/assets/images/default-template.png'}" alt="${escapeHtml(template.name)}">
            </div>
            <div class="template-info">
                <h3>${escapeHtml(template.name)}</h3>
                <div class="template-meta">
                    <span class="template-type">${template.type}</span>
                    <span class="template-genre">${template.genre}</span>
                    <span class="template-usage">使用 ${template.usage_count} 次</span>
                </div>
                <div class="template-description">${escapeHtml(template.description)}</div>
            </div>
        </div>
    `).join('');
}

// 使用模板
function useTemplate(templateId) {
    console.log('使用模板:', templateId);
    // 这里可以实现基于模板创建项目的功能
}

// 加载历史记录
async function loadHistory(append = false) {
    try {
        if (!append) {
            currentPage = 1;
        }
        
        showLoading();
        const params = new URLSearchParams({
            page: currentPage,
            limit: 20,
            generation_type: currentFilters.history
        });
        
        const response = await fetch(`/api/anime-production/generation-history?${params}`);
        const data = await response.json();
        
        if (data.success) {
            if (append) {
                appendHistory(data.data.generations);
            } else {
                updateHistory(data.data.generations);
                updatePagination('history-pagination', data.data.pagination);
            }
        } else {
            showError('加载历史记录失败：' + data.error);
        }
    } catch (error) {
        console.error('加载历史记录失败:', error);
        showError('加载历史记录失败，请重试');
    } finally {
        hideLoading();
    }
}

// 更新历史记录列表
function updateHistory(generations) {
    const container = document.getElementById('history-list');
    if (!container) return;
    
    if (generations.length === 0) {
        container.innerHTML = '<div class="no-data">暂无历史记录</div>';
        return;
    }
    
    container.innerHTML = generations.map(generation => {
        const result = generation.result ? JSON.parse(generation.result) : {};
        return `
            <div class="history-item">
                <div class="history-type">${getGenerationTypeText(generation.generation_type)}</div>
                <div class="history-content">
                    <div class="history-title">${escapeHtml(result.title || '生成内容')}</div>
                    <div class="history-details">
                        <span class="history-model">${generation.ai_model || '未知模型'}</span>
                        <span class="history-cost">${generation.cost ? '成本: ' + generation.cost : ''}</span>
                        <span class="history-quality">${generation.quality_score ? '质量: ' + generation.quality_score : ''}</span>
                    </div>
                </div>
                <div class="history-time">${formatTime(generation.created_at)}</div>
                <div class="history-actions">
                    <button class="btn btn-small" onclick="viewGenerationResult(${generation.id})">查看结果</button>
                    <button class="btn btn-small btn-secondary" onclick="downloadGenerationResult(${generation.id})">下载</button>
                </div>
            </div>
        `;
    }).join('');
}

// 查看生成结果
function viewGenerationResult(generationId) {
    console.log('查看生成结果:', generationId);
    // 这里可以显示详细的结果内容
}

// 下载生成结果
function downloadGenerationResult(generationId) {
    console.log('下载生成结果:', generationId);
    // 这里可以实现下载功能
}

// 模态框控制
function showModal(modalId) {
    const overlay = document.getElementById('modal-overlay');
    const modal = document.getElementById(modalId);
    
    if (overlay && modal) {
        overlay.classList.remove('hidden');
        modal.classList.remove('hidden');
    }
}

function hideModal(modalId) {
    const overlay = document.getElementById('modal-overlay');
    const modal = document.getElementById(modalId);
    
    if (overlay && modal) {
        overlay.classList.add('hidden');
        modal.classList.add('hidden');
    }
}

function hideAllModals() {
    const overlay = document.getElementById('modal-overlay');
    const modals = document.querySelectorAll('.modal');
    
    if (overlay) {
        overlay.classList.add('hidden');
    }
    
    modals.forEach(modal => {
        modal.classList.add('hidden');
    });
}

// 显示创建项目模态框
function showCreateProjectModal() {
    showModal('create-project-modal');
}

// 创建项目
async function createProject() {
    const form = document.getElementById('create-project-form');
    const formData = new FormData(form);
    
    const data = {
        title: formData.get('title'),
        type: formData.get('type'),
        genre: formData.get('genre'),
        description: formData.get('description'),
        use_ai_assistance: formData.get('use_ai_assistance') === 'on'
    };
    
    // 验证必填字段
    if (!data.title) {
        showError('请填写项目标题');
        return;
    }
    
    try {
        showLoading();
        const response = await fetch('/api/anime-production/create-project', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            hideAllModals();
            showSuccess('项目创建成功！');
            loadDashboard(); // 重新加载仪表板
        } else {
            showError('创建失败：' + result.error);
        }
    } catch (error) {
        console.error('创建项目失败:', error);
        showError('创建失败，请重试');
    } finally {
        hideLoading();
    }
}

// 工具函数
function getStatusText(status) {
    const statusMap = {
        'planning': '企划中',
        'in_production': '制作中',
        'completed': '已完成',
        'suspended': '已暂停'
    };
    return statusMap[status] || status;
}

function getGenerationTypeText(type) {
    const typeMap = {
        'script': '脚本',
        'character': '角色',
        'scene': '场景',
        'animation': '动画',
        'audio': '音频',
        'video': '视频',
        'short_drama': '短剧'
    };
    return typeMap[type] || type;
}

function getProjectTypeText(type) {
    const typeMap = {
        'long': '长篇',
        'short': '短篇'
    };
    return typeMap[type] || type;
}

function formatTime(timeString) {
    const date = new Date(timeString);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 60000) { // 1分钟内
        return '刚刚';
    } else if (diff < 3600000) { // 1小时内
        return Math.floor(diff / 60000) + '分钟前';
    } else if (diff < 86400000) { // 1天内
        return Math.floor(diff / 3600000) + '小时前';
    } else {
        return Math.floor(diff / 86400000) + '天前';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 加载状态
function showLoading() {
    const loadingElements = document.querySelectorAll('.loading-indicator');
    loadingElements.forEach(el => el.classList.remove('hidden'));
}

function hideLoading() {
    const loadingElements = document.querySelectorAll('.loading-indicator');
    loadingElements.forEach(el => el.classList.add('hidden'));
}

// 消息提示
function showSuccess(message) {
    showMessage(message, 'success');
}

function showError(message) {
    showMessage(message, 'error');
}

function showMessage(message, type = 'info') {
    // 创建消息元素
    const messageDiv = document.createElement('div');
    messageDiv.className = `message message-${type}`;
    messageDiv.textContent = message;
    
    // 添加到页面
    document.body.appendChild(messageDiv);
    
    // 3秒后自动移除
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.parentNode.removeChild(messageDiv);
        }
    }, 3000);
}

// 追加数据
function appendProjects(newProjects) {
    const container = document.getElementById('projects-grid');
    if (!container) return;
    
    const existingProjects = Array.from(container.children);
    const allProjects = existingProjects.concat(newProjects);
    
    container.innerHTML = allProjects.map(project => `
        <div class="project-card" onclick="viewProject(${project.id})">
            <h3>${escapeHtml(project.title)}</h3>
            <div class="project-meta">
                <span class="project-status ${project.status}">${getStatusText(project.status)}</span>
                <span class="project-type">${getProjectTypeText(project.type)}</span>
                <span class="project-genre">${project.genre || '未分类'}</span>
            </div>
            <div class="project-description">${escapeHtml(project.description || '暂无描述')}</div>
            <div class="project-stats">
                <span>集数: ${project.target_episodes || 0}</span>
                <span>完成: ${project.completed_episodes || 0}</span>
            </div>
            <div class="project-updated">${formatTime(project.updated_at)}</div>
        </div>
    `).join('');
}

function appendTemplates(newTemplates) {
    const container = document.getElementById('templates-grid');
    if (!container) return;
    
    const existingTemplates = Array.from(container.children);
    const allTemplates = existingTemplates.concat(newTemplates);
    
    container.innerHTML = allTemplates.map(template => `
        <div class="template-card" onclick="useTemplate(${template.id})">
            <div class="template-preview">
                <img src="${template.thumbnail || '/web/default/assets/images/default-template.png'}" alt="${escapeHtml(template.name)}">
            </div>
            <div class="template-info">
                <h3>${escapeHtml(template.name)}</h3>
                <div class="template-meta">
                    <span class="template-type">${template.type}</span>
                    <span class="template-genre">${template.genre}</span>
                    <span class="template-usage">使用 ${template.usage_count} 次</span>
                </div>
                <div class="template-description">${escapeHtml(template.description)}</div>
            </div>
        </div>
    `).join('');
}

function appendHistory(newGenerations) {
    const container = document.getElementById('history-list');
    if (!container) return;
    
    const existingGenerations = Array.from(container.children);
    const allGenerations = existingGenerations.concat(newGenerations);
    
    container.innerHTML = allGenerations.map(generation => {
        const result = generation.result ? JSON.parse(generation.result) : {};
        return `
            <div class="history-item">
                <div class="history-type">${getGenerationTypeText(generation.generation_type)}</div>
                <div class="history-content">
                    <div class="history-title">${escapeHtml(result.title || '生成内容')}</div>
                    <div class="history-details">
                        <span class="history-model">${generation.ai_model || '未知模型'}</span>
                        <span class="history-cost">${generation.cost ? '成本: ' + generation.cost : ''}</span>
                        <span class="history-quality">${generation.quality_score ? '质量: ' + generation.quality_score : ''}</span>
                    </div>
                </div>
                <div class="history-time">${formatTime(generation.created_at)}</div>
                <div class="history-actions">
                    <button class="btn btn-small" onclick="viewGenerationResult(${generation.id})">查看结果</button>
                    <button class="btn btn-small btn-secondary" onclick="downloadGenerationResult(${generation.id})">下载</button>
                </div>
            </div>
        `;
    }).join('');
}

// AI工具页面功能
function showScriptGenerator() {
    console.log('显示脚本生成器');
    // 这里可以跳转到专门的脚本生成页面
}

function showCharacterDesigner() {
    console.log('显示角色设计器');
    // 这里可以跳转到专门的角色设计页面
}

function showSceneDesigner() {
    console.log('显示场景设计器');
    // 这里可以跳转到专门的场景设计页面
}

function showStoryboardCreator() {
    console.log('显示分镜创建器');
    // 这里可以跳转到专门的分镜创建页面
}

function showAnimationGenerator() {
    console.log('显示动画生成器');
    // 这里可以跳转到专门的动画生成页面
}

function showAudioProducer() {
    console.log('显示音频制作器');
    // 这里可以跳转到专门的音频制作页面
}

function showVideoComposer() {
    console.log('显示视频合成器');
    // 这里可以跳转到专门的视频合成页面
}

function showShortDramaCreator() {
    console.log('显示短剧创建器');
    // 这里可以跳转到专门的短剧创建页面
}

function showCreateShortDramaModal() {
    console.log('显示短剧创建模态框');
    // 这里可以显示短剧创建的模态框
}

function refreshHistory() {
    currentPage = 1;
    loadHistory();
}