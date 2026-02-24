/**
 * 导出功能脚本
 * 处理运营页面的数据导出功能
 */

document.addEventListener('DOMContentLoaded', function() {
    initExportFunctionality();
});

/**
 * 初始化导出功能
 */
function initExportFunctionality() {
    // 为所有导出按钮添加点击事件
    const exportButtons = document.querySelectorAll('.operations-export-btn');
    exportButtons.forEach(button => {
        button.addEventListener('click', handleExportClick);
    });
}

/**
 * 处理导出按钮点击
 */
function handleExportClick(event) {
    const button = event.currentTarget;
    const exportType = button.getAttribute('data-export-type');
    
    if (!exportType) {
        console.error('导出类型未定义');
        return;
    }
    
    // 显示加载状态
    const originalText = button.textContent;
    button.disabled = true;
    button.innerHTML = '<span class="export-loading"></span> 导出中...';
    
    // 获取表格数据
    const tableData = getTableData();
    
    if (!tableData || tableData.length === 0) {
        showExportMessage('没有可导出的数据', 'warning');
        resetExportButton(button, originalText);
        return;
    }
    
    // 根据导类型处理导出
    setTimeout(() => {
        try {
            exportToCSV(tableData, exportType);
            showExportMessage('数据导出成功', 'success');
        } catch (error) {
            console.error('导出失败:', error);
            showExportMessage('导出失败，请重试', 'error');
        } finally {
            resetExportButton(button, originalText);
        }
    }, 1000);
}

/**
 * 获取表格数据
 */
function getTableData() {
    const table = document.querySelector('.operations-table');
    if (!table) {
        return null;
    }
    
    const headers = [];
    const rows = [];
    
    // 获取表头
    const headerCells = table.querySelectorAll('thead th');
    headerCells.forEach(cell => {
        headers.push(cell.textContent.trim());
    });
    
    // 获取数据行
    const dataRows = table.querySelectorAll('tbody tr');
    dataRows.forEach(row => {
        const rowData = [];
        const cells = row.querySelectorAll('td');
        cells.forEach(cell => {
            rowData.push(cell.textContent.trim());
        });
        rows.push(rowData);
    });
    
    return {
        headers: headers,
        rows: rows
    };
}

/**
 * 导出为CSV文件
 */
function exportToCSV(tableData, exportType) {
    if (!tableData || !tableData.headers || !tableData.rows) {
        throw new Error('无效的表格数据');
    }
    
    // 构建CSV内容
    let csvContent = '';
    
    // 添加BOM以支持中文
    csvContent = '\uFEFF';
    
    // 添加表头
    csvContent += tableData.headers.join(',') + '\n';
    
    // 添加数据行
    tableData.rows.forEach(row => {
        // 处理包含逗号和引号的字段
        const escapedRow = row.map(field => {
            const fieldStr = field.toString();
            // 如果字段包含逗号、引号或换行符，需要用引号包围并转义内部引号
            if (fieldStr.includes(',') || fieldStr.includes('"') || fieldStr.includes('\n')) {
                return '"' + fieldStr.replace(/"/g, '""') + '"';
            }
            return fieldStr;
        });
        csvContent += escapedRow.join(',') + '\n';
    });
    
    // 创建Blob对象
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    
    // 创建下载链接
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    // 设置文件名
    const fileName = getExportFileName(exportType);
    link.setAttribute('href', url);
    link.setAttribute('download', fileName);
    link.style.visibility = 'hidden';
    
    // 触发下载
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // 清理URL对象
    URL.revokeObjectURL(url);
}

/**
 * 获取导出文件名
 */
function getExportFileName(exportType) {
    const typeNames = {
        'revenue': '收入统计',
        'newUser': '新增用户',
        'dau': '活跃用户',
        'coinSpend': '星夜币消耗',
        'newNovel': '新增小说',
        'newMusic': '新增音乐',
        'newAnime': '新增动漫'
    };
    
    const typeName = typeNames[exportType] || '数据统计';
    const now = new Date();
    const dateStr = now.toISOString().split('T')[0]; // YYYY-MM-DD格式
    const timeStr = now.toTimeString().split(' ')[0].replace(/:/g, '-'); // HH-MM-SS格式
    
    return `${typeName}_${dateStr}_${timeStr}.csv`;
}

/**
 * 重置导出按钮状态
 */
function resetExportButton(button, originalText) {
    button.disabled = false;
    button.textContent = originalText;
}

/**
 * 显示导出消息
 */
function showExportMessage(message, type = 'info') {
    // 移除现有消息
    const existingMessage = document.querySelector('.export-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // 创建消息元素
    const messageEl = document.createElement('div');
    messageEl.className = `export-message export-message-${type}`;
    messageEl.textContent = message;
    
    // 设置样式
    const colors = {
        success: { bg: 'rgba(16, 185, 129, 0.1)', border: 'rgba(16, 185, 129, 0.3)', text: '#10b981' },
        error: { bg: 'rgba(239, 68, 68, 0.1)', border: 'rgba(239, 68, 68, 0.3)', text: '#ef4444' },
        warning: { bg: 'rgba(245, 158, 11, 0.1)', border: 'rgba(245, 158, 11, 0.3)', text: '#f59e0b' },
        info: { bg: 'rgba(59, 130, 246, 0.1)', border: 'rgba(59, 130, 246, 0.3)', text: '#3b82f6' }
    };
    
    const color = colors[type] || colors.info;
    
    messageEl.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${color.bg};
        border: 1px solid ${color.border};
        color: ${color.text};
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        animation: slideInRight 0.3s ease;
        max-width: 300px;
    `;
    
    document.body.appendChild(messageEl);
    
    // 自动移除消息
    setTimeout(() => {
        if (messageEl.parentNode) {
            messageEl.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                messageEl.remove();
            }, 300);
        }
    }, 3000);
}

/**
 * 添加导出按钮加载动画样式
 */
function addExportLoadingStyles() {
    const style = document.createElement('style');
    style.id = 'export-loading-styles';
    style.textContent = `
        .export-loading {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 6px;
            vertical-align: middle;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }
        
        .operations-export-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .operations-export-btn:disabled:hover {
            background: var(--primary-color);
            transform: none;
        }
    `;
    
    // 检查是否已经添加过样式
    if (!document.getElementById('export-loading-styles')) {
        document.head.appendChild(style);
    }
}

// 初始化样式
addExportLoadingStyles();

/**
 * 扩展：支持Excel导出（可选功能）
 */
function exportToExcel(tableData, exportType) {
    // 这里可以使用第三方库如 SheetJS 来实现Excel导出
    // 目前先使用CSV作为主要导出格式
    console.log('Excel导出功能待实现');
}

/**
 * 扩展：支持PDF导出（可选功能）
 */
function exportToPDF(tableData, exportType) {
    // 这里可以使用第三方库如 jsPDF 来实现PDF导出
    // 目前先使用CSV作为主要导出格式
    console.log('PDF导出功能待实现');
}

/**
 * 键盘快捷键支持
 */
document.addEventListener('keydown', function(event) {
    // Ctrl/Cmd + E 触发导出
    if ((event.ctrlKey || event.metaKey) && event.key === 'e') {
        event.preventDefault();
        const exportButton = document.querySelector('.operations-export-btn');
        if (exportButton && !exportButton.disabled) {
            exportButton.click();
        }
    }
});

/**
 * 移动端优化
 */
if ('ontouchstart' in window) {
    // 为移动端导出按钮添加触摸反馈
    document.addEventListener('touchstart', function(event) {
        if (event.target.classList.contains('operations-export-btn')) {
            event.target.style.transform = 'scale(0.95)';
        }
    }, { passive: true });
    
    document.addEventListener('touchend', function(event) {
        if (event.target.classList.contains('operations-export-btn')) {
            event.target.style.transform = '';
        }
    }, { passive: true });
}
