/**
 * Operations页面图表脚本
 * 处理运营页面的图表初始化
 */

document.addEventListener('DOMContentLoaded', function() {
    initOperationsCharts();
});

/**
 * 初始化Operations页面图表
 */
function initOperationsCharts() {
    // 收入图表
    initOperationsRevenueChart();
    
    // 新增用户图表
    initOperationsNewUserChart();
    
    // 新增小说图表
    initOperationsNewNovelChart();
    
    // 新增音乐图表
    initOperationsNewMusicChart();
    
    // 新增动漫图表
    initOperationsNewAnimeChart();
    
    // DAU图表
    initOperationsDAUChart();
    
    // 星夜币消耗图表
    initOperationsCoinSpendChart();
}

/**
 * 收入图表
 */
function initOperationsRevenueChart() {
    var ctx = document.getElementById('revenueChart');
    if (!ctx) return;
    
    // 从页面获取数据
    var chartDataElement = document.querySelector('[data-page-type="revenue"]');
    var chartData = chartDataElement ? JSON.parse(chartDataElement.getAttribute('data-chart-data')) : [];
    
    // 如果没有数据，不显示图表
    if (!chartData || chartData.length === 0) {
        var container = ctx.closest('.dashboard-chart-wrapper-lg');
        if (container) {
            container.innerHTML = '<div class="chart-empty-message">暂无收入数据</div>';
        }
        return;
    }
    
    var labels = chartData.map(item => item.label || '');
    var data = chartData.map(item => item.value || 0);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: '收入',
                data: data,
                backgroundColor: 'rgba(139, 92, 246, 0.8)',
                borderColor: 'rgba(139, 92, 246, 1)',
                borderWidth: 2,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '¥' + value.toLocaleString();
                        },
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

/**
 * 新增用户图表
 */
function initOperationsNewUserChart() {
    var ctx = document.getElementById('newUserChart');
    if (!ctx) return;
    
    var chartDataElement = document.querySelector('[data-page-type="new_user"]');
    var chartData = chartDataElement ? JSON.parse(chartDataElement.getAttribute('data-chart-data')) : [];
    
    if (!chartData || chartData.length === 0) {
        var container = ctx.closest('.dashboard-chart-wrapper-lg');
        if (container) {
            container.innerHTML = '<div class="chart-empty-message">暂无用户数据</div>';
        }
        return;
    }
    
    var labels = chartData.map(item => item.label || '');
    var data = chartData.map(item => item.value || 0);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '新增用户',
                data: data,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: 'rgba(255, 255, 255, 0.9)',
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        padding: 20,
                        usePointStyle: true
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

/**
 * 新增小说图表
 */
function initOperationsNewNovelChart() {
    var ctx = document.getElementById('newNovelChart');
    if (!ctx) return;
    
    var chartDataElement = document.querySelector('[data-page-type="new_novel"]');
    var chartData = chartDataElement ? JSON.parse(chartDataElement.getAttribute('data-chart-data')) : [];
    
    if (!chartData || chartData.length === 0) {
        var container = ctx.closest('.dashboard-chart-wrapper-lg');
        if (container) {
            container.innerHTML = '<div class="chart-empty-message">暂无小说数据</div>';
        }
        return;
    }
    
    var labels = chartData.map(item => item.label || '');
    var data = chartData.map(item => item.value || 0);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '新增小说',
                data: data,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#ef4444',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: 'rgba(255, 255, 255, 0.9)',
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        padding: 20,
                        usePointStyle: true
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

/**
 * 新增音乐图表
 */
function initOperationsNewMusicChart() {
    var ctx = document.getElementById('newMusicChart');
    if (!ctx) return;
    
    var chartDataElement = document.querySelector('[data-page-type="new_music"]');
    var chartData = chartDataElement ? JSON.parse(chartDataElement.getAttribute('data-chart-data')) : [];
    
    if (!chartData || chartData.length === 0) {
        var container = ctx.closest('.dashboard-chart-wrapper-lg');
        if (container) {
            container.innerHTML = '<div class="chart-empty-message">暂无音乐数据</div>';
        }
        return;
    }
    
    var labels = chartData.map(item => item.label || '');
    var data = chartData.map(item => item.value || 0);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '新增音乐',
                data: data,
                borderColor: '#06b6d4',
                backgroundColor: 'rgba(6, 182, 212, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#06b6d4',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: 'rgba(255, 255, 255, 0.9)',
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        padding: 20,
                        usePointStyle: true
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

/**
 * 新增动漫图表
 */
function initOperationsNewAnimeChart() {
    var ctx = document.getElementById('newAnimeChart');
    if (!ctx) return;
    
    var chartDataElement = document.querySelector('[data-page-type="new_anime"]');
    var chartData = chartDataElement ? JSON.parse(chartDataElement.getAttribute('data-chart-data')) : [];
    
    if (!chartData || chartData.length === 0) {
        var container = ctx.closest('.dashboard-chart-wrapper-lg');
        if (container) {
            container.innerHTML = '<div class="chart-empty-message">暂无动漫数据</div>';
        }
        return;
    }
    
    var labels = chartData.map(item => item.label || '');
    var data = chartData.map(item => item.value || 0);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '新增动漫',
                data: data,
                borderColor: '#f97316',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#f97316',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: 'rgba(255, 255, 255, 0.9)',
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        padding: 20,
                        usePointStyle: true
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

/**
 * DAU图表
 */
function initOperationsDAUChart() {
    var ctx = document.getElementById('dauChart');
    if (!ctx) return;
    
    var chartDataElement = document.querySelector('[data-page-type="dau"]');
    var chartData = chartDataElement ? JSON.parse(chartDataElement.getAttribute('data-chart-data')) : [];
    
    if (!chartData || chartData.length === 0) {
        var container = ctx.closest('.dashboard-chart-wrapper-lg');
        if (container) {
            container.innerHTML = '<div class="chart-empty-message">暂无活跃数据</div>';
        }
        return;
    }
    
    var labels = chartData.map(item => item.label || '');
    var data = chartData.map(item => item.value || 0);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '活跃用户',
                data: data,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: 'rgba(255, 255, 255, 0.9)',
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        padding: 20,
                        usePointStyle: true
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

/**
 * 星夜币消耗图表
 */
function initOperationsCoinSpendChart() {
    var ctx = document.getElementById('coinSpendChart');
    if (!ctx) return;
    
    var chartDataElement = document.querySelector('[data-page-type="coin_spend"]');
    var chartData = chartDataElement ? JSON.parse(chartDataElement.getAttribute('data-chart-data')) : [];
    
    if (!chartData || chartData.length === 0) {
        var container = ctx.closest('.dashboard-chart-wrapper-lg');
        if (container) {
            container.innerHTML = '<div class="chart-empty-message">暂无消耗数据</div>';
        }
        return;
    }
    
    var labels = chartData.map(item => item.label || '');
    var data = chartData.map(item => item.value || 0);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '星夜币消耗',
                data: data,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#f59e0b',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: 'rgba(255, 255, 255, 0.9)',
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        padding: 20,
                        usePointStyle: true
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}
