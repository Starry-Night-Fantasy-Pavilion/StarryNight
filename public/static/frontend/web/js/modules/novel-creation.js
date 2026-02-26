(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function showMessage(message) {
        if (window.toastr && typeof window.toastr.success === 'function') {
            window.toastr.success(message);
        } else {
            alert(message);
        }
    }

    ready(function () {
        // 清空分析表单
        var clearButtons = document.querySelectorAll('.js-clear-analysis-form');
        if (clearButtons && clearButtons.length) {
            clearButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var form = btn.closest('form');
                    if (!form) return;
                    if (confirm('确定要清空所有输入内容吗？')) {
                        form.reset();
                    }
                });
            });
        }

        // 结果页复制内容
        var copyBtn = document.querySelector('.js-copy-result');
        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                var contentEl = document.querySelector('.result-content');
                if (!contentEl) return;

                var text = contentEl.innerText || contentEl.textContent || '';
                if (!text) return;

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function () {
                        showMessage('内容已复制到剪贴板');
                    }).catch(function () {
                        showMessage('复制失败，请手动选择文本复制');
                    });
                } else {
                    // 兼容处理
                    var textarea = document.createElement('textarea');
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    textarea.value = text;
                    document.body.appendChild(textarea);
                    textarea.select();
                    try {
                        document.execCommand('copy');
                        showMessage('内容已复制到剪贴板');
                    } catch (e) {
                        showMessage('复制失败，请手动选择文本复制');
                    }
                    document.body.removeChild(textarea);
                }
            });
        }
    });
})();

