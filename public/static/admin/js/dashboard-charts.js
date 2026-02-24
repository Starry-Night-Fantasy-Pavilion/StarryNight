/**
 * 仪表板图表脚本
 * 处理 Chart.js 图表的初始化和更新
 */

document.addEventListener('DOMContentLoaded', function() {
    initDashboardCharts();
    initChartResponsiveness();
});

/**
 * 初始化仪表板图表
 */
function initDashboardCharts() {
    // 用户增长图表
    initUserGrowthChart();
    
    // 收入统计图表
    initRevenueChart();
    
    // 内容发布图表
    initContentChart();
    
    // 用户活跃度图表
    initActivityChart();
}

/**
 * 用户增长图表
 */
function initUserGrowthChart() {
    var ctx = document.getElementById('userGrowthChart');
    if (!ctx) return;
    
    var data = (window.dashboardData && window.dashboardData.userGrowth) || {
        labels: ['1月', '2月', '3月', '4月', '5月', '6月'],
        datasets: [{
            label: '新用户',
            data: [65, 78, 90, 81, 96, 105],
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }]
    };
    
    new Chart(ctx, {
        type: 'line',
        data: data,
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
 * 收入统计图表
 */
function initRevenueChart() {
    var ctx = document.getElementById('revenueChart');
    if (!ctx) return;
    
    var data = (window.dashboardData && window.dashboardData.revenue) || {
        labels: ['1月', '2月', '3月', '4月', '5月', '6月'],
        datasets: [{
            label: '收入',
            data: [12000, 19000, 15000, 22000, 28000, 32000],
            backgroundColor: [
                'rgba(59, 130, 246, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(139, 92, 246, 0.8)',
                'rgba(236, 72, 153, 0.8)'
            ],
            borderRadius: 4
        }]
    };
    
    new Chart(ctx, {
        type: 'bar',
        data: data,
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
 * 内容发布图表
 */
function initContentChart() {
    var ctx = document.getElementById('contentChart');
    if (!ctx) return;
    
    var data = window.dashboardData?.content || {
        labels: ['小说', '音乐', '动漫', '社区'],
        datasets: [{
            data: [45, 25, 20, 10],
            backgroundColor: [
                '#3b82f6',
                '#10b981',
                '#f59e0b',
                '#8b5cf6'
            ],
            borderWidth: 0
        }]
    };
    
    new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: 'rgba(255, 255, 255, 0.9)',
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        usePointStyle: true,
                        padding: 20
                    }
                }
            }
        }
    });
}

/**
 * 用户活跃度图表
 */
function initActivityChart() {
    var ctx = document.getElementById('activityChart');
    if (!ctx) return;
    
    var data = window.dashboardData?.activity || {
        labels: ['周一', '周二', '周三', '周四', '周五', '周六', '周日'],
        datasets: [{
            label: '活跃用户',
            data: [320, 350, 380, 360, 400, 450, 420],
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: '新用户',
            data: [120, 150, 180, 140, 200, 220, 190],
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }]
    };
    
    new Chart(ctx, {
        type: 'line',
        data: data,
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
 * 初始化图表响应式
 */
function initChartResponsiveness() {
    // 监听侧边栏切换事件，重新调整图表大小
    var sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    // 如果正在恢复状态，忽略此次变化
                    if (sidebar.dataset.restoring === 'true') {
                        return;
                    }
                    // 延迟触发 resize，让布局先稳定
                    setTimeout(function() {
                        window.dispatchEvent(new Event('resize'));
                    }, 300);
                }
            });
        });

        observer.observe(sidebar, { attributes: true });
    }
}

/**
 * 更新图表数据
 */
function updateChartData(chartId, newData) {
    var chart = Chart.getChart(chartId);
    if (chart) {
        chart.data = newData;
        chart.update();
    }
}

/**
 * 刷新所有图表
 */
function refreshAllCharts() {
    var chartIds = ['userGrowthChart', 'revenueChart', 'contentChart', 'activityChart'];
    
    chartIds.forEach(id => {
        var chart = Chart.getChart(id);
        if (chart) {
            chart.update();
        }
    });
}

/**
 * 导出图表为图片
 */
function exportChart(chartId, filename) {
    var canvas = document.getElementById(chartId);
    if (canvas) {
        var link = document.createElement('a');
        link.download = filename || 'chart.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    }
}
