document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('plugin-config-form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(form);
        var data = {};
        var originalValues = {};

        // 先保存所有原始值（特别是密码字段）
        form.querySelectorAll('input[type="password"]').forEach(function(input) {
            var match = input.name.match(/config\[(.*?)\]/);
            if (!match) return;
            var fieldName = match[1];
            originalValues[fieldName] = input.getAttribute('data-original-value') || '';
        });

        for (var [key, value] of formData.entries()) {
            if (!key.startsWith('config[')) continue;
            var match = key.match(/config\[(.*?)\]/);
            if (!match) continue;
            var fieldName = match[1];

            // 如果是密码字段且为空，保留原始值
            var input = form.querySelector('input[name="config[' + fieldName + ']"]');
            if (input && input.type === 'password' && !value && originalValues[fieldName]) {
                data[fieldName] = originalValues[fieldName];
                continue;
            }

            // 默认按多值字段处理（与原实现保持兼容）
            if (data[fieldName] === undefined) {
                data[fieldName] = value;
            } else if (Array.isArray(data[fieldName])) {
                data[fieldName].push(value);
            } else {
                data[fieldName] = [data[fieldName], value];
            }
        }

        // 处理单个checkbox
        form.querySelectorAll('input[type="checkbox"]:not([name*="[]"])').forEach(function(checkbox) {
            var match = checkbox.name.match(/config\[(.*?)\]/);
            if (!match) return;
            var name = match[1];
            if (!checkbox.checked) {
                delete data[name];
            } else {
                data[name] = checkbox.value || true;
            }
        });

        // 验证必填项
        var requiredFields = form.querySelectorAll('.required');
        var hasError = false;
        requiredFields.forEach(function(required) {
            var label = required.closest('label');
            if (!label) return;
            var fieldName = label.getAttribute('for');
            if (fieldName && (!data[fieldName] || data[fieldName] === '')) {
                hasError = true;
                var input = form.querySelector('#' + fieldName);
                if (input) {
                    input.style.borderColor = '#ff4757';
                    input.focus();
                }
            }
        });

        if (hasError) {
            alert('请填写所有必填项（标记为*的字段）');
            return;
        }

        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                'config': JSON.stringify(data)
            }).toString()
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('配置已保存');
                    window.parent.postMessage({ type: 'close-plugin-modal' }, '*');
                } else {
                    alert('保存失败：' + (result.message || '未知错误'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('保存失败：网络错误');
            });
    });

    // 监听父窗口关闭消息（保留扩展点）
    window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'close-plugin-modal') {
            // 可在此处做清理
        }
    });
});

// 切换密码显示/隐藏
function togglePassword(fieldName) {
    var input = document.getElementById(fieldName);
    if (!input) return;
    var iconWrapper = input.nextElementSibling;
    if (!iconWrapper) return;
    var icon = iconWrapper.querySelector('.password-toggle-icon');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) icon.textContent = '🙈';
    } else {
        input.type = 'password';
        if (icon) icon.textContent = '👁️';
    }
}

