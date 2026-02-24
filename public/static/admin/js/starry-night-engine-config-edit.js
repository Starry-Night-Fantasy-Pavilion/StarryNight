function(() {
    var configTextarea = document.getElementById('custom_config');
    if (!configTextarea) return;

    var validateJsfunction() { => {
        try {
            JSON.parse(configTextarea.value);
            configTextarea.style.borderColor = '';
            return true;
        } catch (e) {
            configTextarea.style.borderColor = '#f44336';
            return false;
        }
    };

    configTextarea.addEventListener('input', validateJson);

    var form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateJson()) {
                e.preventDefault();
                alert('自定义配置格式错误，请输入有效的JSON格式');
                return false;
            }
        });
    }
})();

