/**
 * AI音乐编辑器JavaScript文件
 */

// 全局变量
let currentProjectId = null;
let currentTool = 'lyrics';
let projectData = null;
let tracks = [];
let lyrics = null;
let autoSaveTimer = null;

// API基础URL
const API_BASE = '/api/ai-music';

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    initializeEditor();
});

/**
 * 初始化编辑器
 */
function initializeEditor() {
    // 从URL获取项目ID
    const urlParams = new URLSearchParams(window.location.search);
    currentProjectId = urlParams.get('id') || urlParams.split('/').pop();
    
    if (currentProjectId) {
        loadProject(currentProjectId);
    }
    
    setupEditorEventListeners();
    startAutoSave();
}

/**
 * 设置编辑器事件监听器
 */
function setupEditorEventListeners() {
    // 工具切换
    document.querySelectorAll('.tool-item').forEach(item => {
        item.addEventListener('click', function() {
            const tool = this.dataset.tool;
            switchTool(tool);
        });
    });

    // 键盘快捷键
    document.addEventListener('keydown', handleKeyboardShortcuts);

    // 窗口关闭前提醒保存
    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges()) {
            e.preventDefault();
            e.returnValue = '您有未保存的更改，确定要离开吗？';
        }
    });
}

/**
 * 加载项目数据
 */
async function loadProject(projectId) {
    try {
        showLoading();
        const response = await fetch(`${API_BASE}/project/${projectId}`);
        if (response.ok) {
            const data = await response.json();
            projectData = data.data;
            displayProjectInfo();
            loadProjectComponents();
        } else {
            showToast('加载项目失败', 'error');
        }
    } catch (error) {
        console.error('加载项目失败:', error);
        showToast('加载项目失败', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * 显示项目信息
 */
function displayProjectInfo() {
    if (!projectData) return;

    // 更新页面标题
    const titleElement = document.getElementById('projectTitle');
    if (titleElement) {
        titleElement.textContent = projectData.project.title;
    }

    // 更新状态
    const statusElement = document.getElementById('projectStatus');
    if (statusElement) {
        const statusClass = getStatusClass(projectData.project.status);
        const statusText = getStatusText(projectData.project.status);
        statusElement.innerHTML = `<span class="status-badge ${statusClass}">${statusText}</span>`;
    }

    // 更新项目信息
    document.getElementById('projectGenre').textContent = projectData.project.genre || '-';
    document.getElementById('projectBPM').textContent = projectData.project.bpm || '-';
    document.getElementById('projectKey').textContent = projectData.project.key_signature || '-';
    document.getElementById('projectDuration').textContent = formatDuration(projectData.project.duration || 0);

    // 更新页面标题
    document.title = `${projectData.project.title} - AI音乐编辑器`;
}

/**
 * 加载项目组件
 */
function loadProjectComponents() {
    // 加载歌词
    if (projectData.lyrics && projectData.lyrics.length > 0) {
        lyrics = projectData.lyrics[0];
        displayLyrics();
    }

    // 加载音轨
    if (projectData.tracks) {
        tracks = projectData.tracks;
        displayTracks();
    }
}

/**
 * 切换工具
 */
function switchTool(tool) {
    // 更新工具状态
    document.querySelectorAll('.tool-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-tool="${tool}"]`).classList.add('active');

    // 切换面板
    document.querySelectorAll('.editor-panel').forEach(panel => {
        panel.classList.remove('active');
    });
    document.getElementById(`${tool}Panel`).classList.add('active');

    currentTool = tool;

    // 初始化工具
    initializeTool(tool);
}

/**
 * 初始化工具
 */
function initializeTool(tool) {
    switch (tool) {
        case 'lyrics':
            initializeLyricsEditor();
            break;
        case 'melody':
            initializeMelodyEditor();
            break;
        case 'arrangement':
            initializeArrangementEditor();
            break;
        case 'vocal':
            initializeVocalEditor();
            break;
        case 'mix':
            initializeMixEditor();
            break;
    }
}

/**
 * 初始化歌词编辑器
 */
function initializeLyricsEditor() {
    const textarea = document.getElementById('lyricsTextarea');
    if (textarea && lyrics) {
        textarea.value = lyrics.content;
        analyzeLyricsContent();
    }

    // 监听歌词变化
    if (textarea) {
        textarea.addEventListener('input', debounce(analyzeLyricsContent, 1000));
    }
}

/**
 * 显示歌词
 */
function displayLyrics() {
    const textarea = document.getElementById('lyricsTextarea');
    if (textarea && lyrics) {
        textarea.value = lyrics.content;
        analyzeLyricsContent();
    }
}

/**
 * 分析歌词内容
 */
function analyzeLyricsContent() {
    const textarea = document.getElementById('lyricsTextarea');
    if (!textarea) return;

    const content = textarea.value;
    if (!content.trim()) return;

    // 这里可以调用API进行歌词分析
    // 暂时使用模拟数据
    const emotionAnalysis = analyzeEmotion(content);
    const structure = analyzeStructure(content);
    
    displayEmotionAnalysis(emotionAnalysis);
    displayStructure(structure);
}

/**
 * 显示情感分析
 */
function displayEmotionAnalysis(emotions) {
    const container = document.getElementById('emotionChart');
    if (!container) return;

    const maxEmotion = Math.max(...Object.values(emotions));
    
    container.innerHTML = Object.entries(emotions).map(([emotion, value]) => `
        <div class="emotion-item">
            <span class="emotion-label">${getEmotionLabel(emotion)}</span>
            <div class="emotion-bar">
                <div class="emotion-fill" style="width: ${(value / maxEmotion * 100)}%"></div>
            </div>
            <span class="emotion-value">${Math.round(value * 100)}%</span>
        </div>
    `).join('');
}

/**
 * 显示结构分析
 */
function displayStructure(structure) {
    const container = document.getElementById('structureView');
    if (!container) return;

    container.innerHTML = Object.entries(structure).map(([section, lines]) => `
        <div class="structure-item">
            <strong>${getSectionLabel(section)}</strong>
            ${Array.isArray(lines) ? lines.join('<br>') : lines}
        </div>
    `).join('');
}

/**
 * 初始化旋律编辑器
 */
function initializeMelodyEditor() {
    // 初始化钢琴卷帘编辑器
    initializePianoRoll();
    
    // 设置旋律参数
    if (projectData.project) {
        document.getElementById('melodyBPM').value = projectData.project.bpm || 120;
        document.getElementById('melodyKey').value = projectData.project.key_signature || 'C';
    }
}

/**
 * 初始化钢琴卷帘编辑器
 */
function initializePianoRoll() {
    const pianoRoll = document.getElementById('pianoRoll');
    if (!pianoRoll) return;

    // 这里可以初始化钢琴卷帘编辑器
    // 暂时显示占位内容
    pianoRoll.innerHTML = `
        <div class="piano-roll-placeholder">
            <i class="fas fa-music"></i>
            <p>钢琴卷帘编辑器</p>
            <p class="text-muted">点击添加音符，拖拽移动音符</p>
        </div>
    `;
}

/**
 * 初始化编曲编辑器
 */
function initializeArrangementEditor() {
    displayTracks();
    initializeTimeline();
}

/**
 * 显示音轨
 */
function displayTracks() {
    const container = document.getElementById('trackList');
    if (!container) return;

    if (tracks.length === 0) {
        container.innerHTML = '<p class="text-muted">暂无音轨，点击"添加音轨"创建</p>';
        return;
    }

    container.innerHTML = tracks.map((track, index) => `
        <div class="track-item ${track.mute ? 'muted' : ''} ${track.solo ? 'solo' : ''}" data-track-id="${track.id}">
            <div class="track-info">
                <div class="track-name">${track.name}</div>
                <div class="track-type">${getTrackTypeLabel(track.type)}</div>
            </div>
            <div class="track-controls">
                <button class="track-btn" onclick="toggleMute(${track.id})" title="静音">
                    <i class="fas fa-volume-${track.mute ? 'mute' : 'up'}"></i>
                </button>
                <button class="track-btn" onclick="toggleSolo(${track.id})" title="独奏">
                    <i class="fas fa-headphones${track.solo ? '' : '-alt'}"></i>
                </button>
                <button class="track-btn" onclick="deleteTrack(${track.id})" title="删除">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

/**
 * 初始化时间轴
 */
function initializeTimeline() {
    const timeline = document.getElementById('timeline');
    if (!timeline) return;

    // 这里可以初始化时间轴
    timeline.innerHTML = `
        <div class="timeline-placeholder">
            <i class="fas fa-clock"></i>
            <p>时间轴</p>
            <p class="text-muted">拖拽音轨到时间轴进行编排</p>
        </div>
    `;
}

/**
 * 初始化人声编辑器
 */
function initializeVocalEditor() {
    displayVocalTracks();
    initializeVocalEffects();
}

/**
 * 显示人声音轨
 */
function displayVocalTracks() {
    const container = document.getElementById('vocalTracks');
    if (!container) return;

    const vocalTracks = tracks.filter(track => track.type === 'vocal');
    
    if (vocalTracks.length === 0) {
        container.innerHTML = '<p class="text-muted">暂无人声音轨</p>';
        return;
    }

    container.innerHTML = vocalTracks.map(track => `
        <div class="vocal-track">
            <div class="vocal-track-header">
                <div class="vocal-track-name">${track.name}</div>
                <div class="vocal-track-actions">
                    <button class="track-btn" onclick="recordVocal(${track.id})" title="录制">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <button class="track-btn" onclick="playVocal(${track.id})" title="播放">
                        <i class="fas fa-play"></i>
                    </button>
                </div>
            </div>
            <div class="vocal-waveform">
                <canvas id="waveform-${track.id}" width="400" height="80"></canvas>
            </div>
        </div>
    `).join('');
}

/**
 * 初始化人声效果
 */
function initializeVocalEffects() {
    // 设置效果器默认值
    const effects = {
        pitchCorrection: 0,
        noiseReduction: 0,
        reverb: 20,
        compression: 30
    };

    Object.entries(effects).forEach(([effect, value]) => {
        const slider = document.getElementById(effect);
        if (slider) {
            slider.value = value;
        }
    });
}

/**
 * 初始化混音编辑器
 */
function initializeMixEditor() {
    displayMixerChannels();
    initializeMasterControls();
}

/**
 * 显示混音通道
 */
function displayMixerChannels() {
    const container = document.getElementById('mixerChannels');
    if (!container) return;

    if (tracks.length === 0) {
        container.innerHTML = '<p class="text-muted">暂无音轨</p>';
        return;
    }

    container.innerHTML = tracks.map(track => `
        <div class="mixer-channel">
            <div class="channel-header">
                <div class="channel-name">${track.name}</div>
                <div class="channel-type">${getTrackTypeLabel(track.type)}</div>
            </div>
            <div class="channel-fader">
                <label>音量</label>
                <input type="range" class="volume-slider" min="0" max="100" value="${Math.abs(track.volume * 2)}" 
                       onchange="updateTrackVolume(${track.id}, this.value)">
            </div>
            <div class="channel-pan">
                <label>声像</label>
                <input type="range" class="pan-slider" min="-100" max="100" value="${track.pan}" 
                       onchange="updateTrackPan(${track.id}, this.value)">
            </div>
        </div>
    `).join('');
}

/**
 * 初始化主控
 */
function initializeMasterControls() {
    // 设置主控默认值
    document.getElementById('masterVolume').value = 75;
    document.getElementById('masterPan').value = 0;
}

/**
 * AI生成歌词
 */
async function generateLyrics() {
    if (!currentProjectId) {
        showToast('请先创建项目', 'warning');
        return;
    }

    const params = {
        theme: projectData.project.title,
        emotion: 'happy',
        style: projectData.project.genre || 'pop',
        word_count: 200
    };

    try {
        showLoading();
        const response = await fetch(`${API_BASE}/lyrics/generate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(params)
        });

        if (response.ok) {
            const data = await response.json();
            lyrics = data.data;
            displayLyrics();
            showToast('AI歌词生成成功', 'success');
        } else {
            const error = await response.json();
            showToast(error.error || 'AI歌词生成失败', 'error');
        }
    } catch (error) {
        console.error('AI歌词生成失败:', error);
        showToast('AI歌词生成失败', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * AI生成旋律
 */
async function generateMelody() {
    if (!currentProjectId) {
        showToast('请先创建项目', 'warning');
        return;
    }

    try {
        showLoading();
        const response = await fetch(`${API_BASE}/melody/generate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                project_id: currentProjectId,
                style: projectData.project.genre || 'pop',
                emotion: 'happy'
            })
        });

        if (response.ok) {
            const data = await response.json();
            showToast('AI旋律生成成功', 'success');
            // 更新旋律编辑器
            updateMelodyEditor(data.data);
        } else {
            const error = await response.json();
            showToast(error.error || 'AI旋律生成失败', 'error');
        }
    } catch (error) {
        console.error('AI旋律生成失败:', error);
        showToast('AI旋律生成失败', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * AI自动编曲
 */
async function autoArrange() {
    if (!currentProjectId) {
        showToast('请先创建项目', 'warning');
        return;
    }

    try {
        showLoading();
        const response = await fetch(`${API_BASE}/arrangement/generate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                project_id: currentProjectId,
                style: projectData.project.genre || 'pop'
            })
        });

        if (response.ok) {
            const data = await response.json();
            tracks = data.data;
            displayTracks();
            showToast('AI自动编曲成功', 'success');
        } else {
            const error = await response.json();
            showToast(error.error || 'AI自动编曲失败', 'error');
        }
    } catch (error) {
        console.error('AI自动编曲失败:', error);
        showToast('AI自动编曲失败', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * AI自动混音
 */
async function autoMix() {
    if (!currentProjectId) {
        showToast('请先创建项目', 'warning');
        return;
    }

    try {
        showLoading();
        const response = await fetch(`${API_BASE}/mix/auto`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                project_id: currentProjectId,
                style: 'balanced'
            })
        });

        if (response.ok) {
            const data = await response.json();
            showToast('AI自动混音成功', 'success');
            // 更新混音设置
            updateMixSettings(data.data.settings);
        } else {
            const error = await response.json();
            showToast(error.error || 'AI自动混音失败', 'error');
        }
    } catch (error) {
        console.error('AI自动混音失败:', error);
        showToast('AI自动混音失败', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * 添加音轨
 */
function addTrack() {
    showModal('addTrackModal');
}

/**
 * 提交添加音轨
 */
async function submitAddTrack() {
    const form = document.getElementById('addTrackForm');
    const formData = new FormData(form);
    
    const trackData = {
        project_id: currentProjectId,
        name: formData.get('name'),
        type: formData.get('type'),
        instrument: formData.get('instrument')
    };

    try {
        showLoading();
        const response = await fetch(`${API_BASE}/track`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(trackData)
        });

        if (response.ok) {
            const data = await response.json();
            tracks.push(data.data);
            displayTracks();
            closeModal('addTrackModal');
            showToast('音轨添加成功', 'success');
        } else {
            const error = await response.json();
            showToast(error.error || '添加音轨失败', 'error');
        }
    } catch (error) {
        console.error('添加音轨失败:', error);
        showToast('添加音轨失败', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * 删除音轨
 */
async function deleteTrack(trackId) {
    confirmAction('确定要删除这个音轨吗？', async () => {
        try {
            showLoading();
            const response = await fetch(`${API_BASE}/track/${trackId}`, {
                method: 'DELETE'
            });

            if (response.ok) {
                tracks = tracks.filter(track => track.id !== trackId);
                displayTracks();
                showToast('音轨删除成功', 'success');
            } else {
                const error = await response.json();
                showToast(error.error || '删除音轨失败', 'error');
            }
        } catch (error) {
            console.error('删除音轨失败:', error);
            showToast('删除音轨失败', 'error');
        } finally {
            hideLoading();
        }
    });
}

/**
 * 切换静音状态
 */
function toggleMute(trackId) {
    const track = tracks.find(t => t.id === trackId);
    if (track) {
        track.mute = !track.mute;
        displayTracks();
    }
}

/**
 * 切换独奏状态
 */
function toggleSolo(trackId) {
    const track = tracks.find(t => t.id === trackId);
    if (track) {
        track.solo = !track.solo;
        displayTracks();
    }
}

/**
 * 更新音轨音量
 */
function updateTrackVolume(trackId, value) {
    const track = tracks.find(t => t.id === trackId);
    if (track) {
        track.volume = value / 50 - 1; // 转换为 -1 到 1 的范围
    }
}

/**
 * 更新音轨声像
 */
function updateTrackPan(trackId, value) {
    const track = tracks.find(t => t.id === trackId);
    if (track) {
        track.pan = parseInt(value);
    }
}

/**
 * 保存项目
 */
async function saveProject() {
    if (!currentProjectId || !projectData) return;

    try {
        showLoading();
        
        // 收集所有更改的数据
        const updateData = {
            title: projectData.project.title,
            description: projectData.project.description,
            genre: projectData.project.genre,
            bpm: projectData.project.bpm,
            key_signature: projectData.project.key_signature
        };

        const response = await fetch(`${API_BASE}/project/${currentProjectId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(updateData)
        });

        if (response.ok) {
            showToast('项目保存成功', 'success');
        } else {
            const error = await response.json();
            showToast(error.error || '保存项目失败', 'error');
        }
    } catch (error) {
        console.error('保存项目失败:', error);
        showToast('保存项目失败', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * 导出项目
 */
function exportProject() {
    showModal('exportModal');
}

/**
 * 提交导出
 */
async function submitExport() {
    const form = document.getElementById('exportForm');
    const formData = new FormData(form);
    
    const exportData = {
        format: formData.get('format'),
        quality: formData.get('quality'),
        normalize: formData.get('normalizeAudio') === 'on',
        fade_in: parseFloat(formData.get('fadeIn')),
        fade_out: parseFloat(formData.get('fadeOut'))
    };

    try {
        showLoading();
        const response = await fetch(`${API_BASE}/export/${currentProjectId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(exportData)
        });

        if (response.ok) {
            const data = await response.json();
            closeModal('exportModal');
            showToast('导出成功', 'success');
            
            // 下载文件
            if (data.data.file_url) {
                window.open(data.data.file_url, '_blank');
            }
        } else {
            const error = await response.json();
            showToast(error.error || '导出失败', 'error');
        }
    } catch (error) {
        console.error('导出失败:', error);
        showToast('导出失败', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * 开始自动保存
 */
function startAutoSave() {
    autoSaveTimer = setInterval(() => {
        if (hasUnsavedChanges()) {
            saveProject();
        }
    }, 60000); // 每分钟自动保存一次
}

/**
 * 检查是否有未保存的更改
 */
function hasUnsavedChanges() {
    // 这里可以实现检查逻辑
    return false;
}

/**
 * 键盘快捷键处理
 */
function handleKeyboardShortcuts(event) {
    // Ctrl+S 保存
    if (event.ctrlKey && event.key === 's') {
        event.preventDefault();
        saveProject();
    }
    
    // Ctrl+E 导出
    if (event.ctrlKey && event.key === 'e') {
        event.preventDefault();
        exportProject();
    }
    
    // Ctrl+Z 撤销
    if (event.ctrlKey && event.key === 'z') {
        event.preventDefault();
        undo();
    }
    
    // Ctrl+Y 重做
    if (event.ctrlKey && event.key === 'y') {
        event.preventDefault();
        redo();
    }
}

/**
 * 撤销操作
 */
function undo() {
    // 实现撤销逻辑
    showToast('撤销功能开发中', 'info');
}

/**
 * 重做操作
 */
function redo() {
    // 实现重做逻辑
    showToast('重做功能开发中', 'info');
}

/**
 * 获取状态样式类
 */
function getStatusClass(status) {
    const classes = {
        1: 'status-draft',
        2: 'status-in-progress',
        3: 'status-completed',
        4: 'status-published'
    };
    return classes[status] || 'status-draft';
}

/**
 * 获取状态文本
 */
function getStatusText(status) {
    const texts = {
        1: '草稿',
        2: '进行中',
        3: '已完成',
        4: '已发布'
    };
    return texts[status] || '草稿';
}

/**
 * 获取情感标签
 */
function getEmotionLabel(emotion) {
    const labels = {
        happy: '快乐',
        sad: '悲伤',
        angry: '愤怒',
        fear: '恐惧',
        surprise: '惊讶',
        disgust: '厌恶',
        neutral: '中性'
    };
    return labels[emotion] || emotion;
}

/**
 * 获取段落标签
 */
function getSectionLabel(section) {
    const labels = {
        verse: '主歌',
        chorus: '副歌',
        bridge: '桥段',
        intro: '前奏',
        outro: '结尾'
    };
    return labels[section] || section;
}

/**
 * 获取音轨类型标签
 */
function getTrackTypeLabel(type) {
    const labels = {
        melody: '旋律',
        chord: '和弦',
        drums: '鼓组',
        bass: '贝斯',
        vocal: '人声',
        effect: '效果'
    };
    return labels[type] || type;
}

/**
 * 分析情感（简化版）
 */
function analyzeEmotion(content) {
    // 这里应该调用AI API，暂时返回模拟数据
    return {
        happy: 0.3,
        sad: 0.1,
        angry: 0.05,
        fear: 0.05,
        surprise: 0.1,
        disgust: 0.05,
        neutral: 0.35
    };
}

/**
 * 分析结构（简化版）
 */
function analyzeStructure(content) {
    // 这里应该调用AI API，暂时返回模拟数据
    return {
        verse: '第一段歌词内容\n第二段歌词内容',
        chorus: '副歌歌词内容\n副歌歌词内容'
    };
}