/**
 * 仪表板表单脚本
 * 处理仪表板页面的表单交互和数据筛选
 */

document.addEventListener('DOMContentLoaded', function () {
    initDateRangePickers();
    initFilterForms();
    initSearchInputs();
    initQuickActions();
    initExportFunctions();
});

/**
 * 初始化日期范围选择器
 */
function initDateRangePickers() {
    document.querySelectorAll('.date-range-picker').forEach(function (picker) {
        var startInput = picker.querySelector('.date-start');
        var endInput = picker.querySelector('.date-end');
        var presetBtns = picker.querySelectorAll('.date-preset');

        // 预设按钮
        presetBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var range = this.getAttribute('data-range');
                var dates = getDateRange(range);

                if (startInput) startInput.value = dates.start;
                if (endInput) endInput.value = dates.end;

                // 触发筛选
                var form = picker.closest('form');
                if (form) {
                    triggerFilter(form);
                }
            });
        });

        // 日期变化时自动筛选
        [startInput, endInput].forEach(function (input) {
            if (input) {
                input.addEventListener('change', function () {
                    var form = picker.closest('form');
                    if (form) {
                        triggerFilter(form);
                    }
                });
            }
        });
    });
}

/**
 * 获取日期范围
 */
function getDateRange(range) {
    var today = new Date();
    var start = new Date();
    var end = new Date();

    switch (range) {
        case 'today':
            start = today;
            break;
        case 'yesterday':
            start = new Date(today.getTime() - 86400000);
            end = start;
            break;
        case 'week':
            start = new Date(today.getTime() - 7 * 86400000);
            break;
        case 'month':
            start = new Date(today.getFullYear(), today.getMonth(), 1);
            end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            break;
        case 'last_month':
            start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            end = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
        case 'quarter':
            var quarter = Math.floor(today.getMonth() / 3);
            start = new Date(today.getFullYear(), quarter * 3, 1);
            end = new Date(today.getFullYear(), quarter * 3 + 3, 0);
            break;
        case 'year':
            start = new Date(today.getFullYear(), 0, 1);
            end = new Date(today.getFullYear(), 11, 31);
            break;
    }

    return {
        start: formatDate(start),
        end: formatDate(end)
    };
}

/**
 * 格式化日期
 */
function formatDate(date) {
    var year = date.getFullYear();
    var month = String(date.getMonth() + 1).padStart(2, '0');
    var day = String(date.getDate()).padStart(2, '0');
    return year + '-' + month + '-' + day;
}

/**
 * 初始化筛选表单
 */
function initFilterForms() {
    document.querySelectorAll('form[data-filter]').forEach(function (form) {
        // 防抖处理
        var debounceTimer;

        form.querySelectorAll('input, select').forEach(function (input) {
            input.addEventListener('change', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    triggerFilter(form);
                }, 300);
            });

            // 搜索框实时筛选
            if (input.type === 'search' || input.classList.contains('search-input')) {
                input.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function () {
                        triggerFilter(form);
                    }, 500);
                });
            }
        });

        // 重置按钮
        var resetBtn = form.querySelector('[type="reset"]');
        if (resetBtn) {
            resetBtn.addEventListener('click', function (e) {
                e.preventDefault();
                form.reset();
                triggerFilter(form);
            });
        }
    });
}

/**
 * 触发筛选
 */
function triggerFilter(form) {
    if (!form) return;

    var formData = new FormData(form);
    var params = new URLSearchParams();

    formData.forEach(function (value, key) {
        if (value !== null && value !== undefined && String(value).trim() !== '') {
            params.append(key, value);
        }
    });

    // 更新 URL
    var newUrl = window.location.pathname + '?' + params.toString();
    window.history.pushState({}, '', newUrl);

    // 加载数据
    loadFilteredData(params);
}

/**
 * 加载筛选后的数据
 */
async function loadFilteredData(params) {
    var container = document.querySelector('[data-filter-container]');
    if (!container) return;

    container.classList.add('loading');

    try {
        var url = window.location.pathname + '?' + params.toString() + '&ajax=1';
        var response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        var html = await response.text();
        container.innerHTML = html;

        // 重新初始化容器内的组件
        initFilterForms();
        initSearchInputs();
    } catch (error) {
        console.error('Failed to load filtered data:', error);
    } finally {
        container.classList.remove('loading');
    }
}

/**
 * 初始化搜索输入框
 */
function initSearchInputs() {
    document.querySelectorAll('.search-input').forEach(function (input) {
        // 清除按钮
        var clearBtn = input.parentElement ? input.parentElement.querySelector('.search-clear') : null;
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                input.value = '';
                input.focus();
                input.dispatchEvent(new Event('input'));
            });
        }

        // 搜索建议
        var suggestions = input.parentElement ? input.parentElement.querySelector('.search-suggestions') : null;
        if (suggestions) {
            input.addEventListener('focus', function () {
                if (input.value.length >= 2) {
                    suggestions.classList.add('show');
                }
            });

            input.addEventListener('blur', function () {
                setTimeout(function () {
                    suggestions.classList.remove('show');
                }, 200);
            });

            input.addEventListener('input', function () {
                if (input.value.length >= 2) {
                    loadSearchSuggestions(input, suggestions);
                } else {
                    suggestions.classList.remove('show');
                }
            });
        }
    });
}

/**
 * 加载搜索建议
 */
async function loadSearchSuggestions(input, suggestions) {
    var type = input.getAttribute('data-search-type') || '';
    var query = input.value || '';

    if (!query) {
        suggestions.classList.remove('show');
        return;
    }

    try {
        var response = await fetch('/api/search-suggestions?type=' + encodeURIComponent(type) + '&q=' + encodeURIComponent(query));
        var data = await response.json();

        suggestions.innerHTML = (data || []).map(function (item) {
            var iconHtml = item.icon ? '<span class="icon">' + item.icon + '</span>' : '';
            return (
                '<div class="suggestion-item" data-value="' + item.value + '">' +
                iconHtml +
                '<span class="text">' + item.text + '</span>' +
                '</div>'
            );
        }).join('');

        suggestions.querySelectorAll('.suggestion-item').forEach(function (item) {
            item.addEventListener('click', function () {
                var textEl = item.querySelector('.text');
                input.value = textEl ? textEl.textContent : '';
                input.setAttribute('data-value', item.getAttribute('data-value') || '');
                suggestions.classList.remove('show');
                input.dispatchEvent(new Event('change'));
            });
        });

        suggestions.classList.add('show');
    } catch (error) {
        console.error('Failed to load suggestions:', error);
    }
}

/**
 * 初始化快捷操作
 */
function initQuickActions() {
    document.querySelectorAll('[data-quick-action]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var action = this.getAttribute('data-quick-action');
            var confirmMsg = this.getAttribute('data-confirm');

            if (confirmMsg && !window.confirm(confirmMsg)) {
                return;
            }

            this.disabled = true;
            var originalText = this.textContent;
            this.textContent = '处理中...';

            try {
                var response = await fetch(this.getAttribute('data-url') || window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ action: action })
                });

                var result = await response.json();

                if (result.success) {
                    showNotification('success', result.message || '操作成功');
                    if (result.reload) {
                        setTimeout(function () { window.location.reload(); }, 1000);
                    }
                    if (result.redirect) {
                        setTimeout(function () { window.location.href = result.redirect; }, 1000);
                    }
                } else {
                    showNotification('error', result.message || '操作失败');
                }
            } catch (error) {
                console.error(error);
                showNotification('error', '操作失败，请重试');
            } finally {
                this.disabled = false;
                this.textContent = originalText;
            }
        });
    });
}

/**
 * 初始化导出功能
 */
function initExportFunctions() {
    document.querySelectorAll('[data-export]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var format = this.getAttribute('data-export');
            var form = this.closest('form') || document.querySelector('form[data-filter]');

            var params = new URLSearchParams();
            if (form) {
                var formData = new FormData(form);
                formData.forEach(function (value, key) {
                    if (value !== null && value !== undefined && String(value).trim() !== '') {
                        params.append(key, value);
                    }
                });
            }

            params.append('export', format || 'csv');

            var url = window.location.pathname + '?' + params.toString();
            window.location.href = url;
        });
    });
}

/**
 * 显示通知
 */
function showNotification(type, message, duration) {
    if (duration === void 0) {
        duration = 3000;
    }

    var notification = document.createElement('div');
    notification.className = 'notification notification-' + type;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(function () {
        notification.classList.add('show');
    }, 10);

    setTimeout(function () {
        notification.classList.remove('show');
        setTimeout(function () { notification.remove(); }, 300);
    }, duration);
}
