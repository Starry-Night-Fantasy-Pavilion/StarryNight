/**
 * 安装向导JavaScript功能
 */

// 生成随机字符串
function generateRandom(id, length) {
    var charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    var retVal = "";
    for (var i = 0, n = charset.length; i < length; ++i) {
        retVal += charset.charAt(Math.floor(Math.random() * n));
    }
    var input = document.getElementById(id);
    input.value = retVal;
    
    // 如果是密码，同步到确认密码并显示明文
    if (id === 'admin_pass') {
        input.type = 'text';
        var confirm = document.getElementById('admin_pass_confirm');
        if (confirm) {
            confirm.value = retVal;
            confirm.type = 'text';
        }
    }
    // 如果是后台路径，显示明文
    if (id === 'admin_path') {
        input.type = 'text';
    }
}

// 存储配置及其他页面功能
document.addEventListener('DOMContentLoaded', function() {
    // --- 存储配置 (Step 5) ---
    var storageTypeRadios = document.querySelectorAll('input[name="storage_type"]');
    var storageOptions = document.querySelectorAll('.storage-option');
    
    function updateStorageOptions() {
        // This function is only relevant for step 5, check if the elements exist
        var checkedRadio = document.querySelector('input[name="storage_type"]:checked');
        if (!checkedRadio) return;
        var selectedValue = checkedRadio.value;

        storageOptions.forEach(function(option) {
            var optionType = option.getAttribute('data-type');
            var details = option.querySelector('.storage-option-details');
            var inputsWithDataRequired = option.querySelectorAll('[data-required]');

            if (optionType === selectedValue) {
                option.classList.add('selected');
                if (details) details.style.display = 'block';
                inputsWithDataRequired.forEach(function(input) {
                    input.setAttribute('required', 'true');
                });
            } else {
                option.classList.remove('selected');
                if (details) details.style.display = 'none';
                inputsWithDataRequired.forEach(function(input) {
                    input.removeAttribute('required');
                });
            }
        });
    }

    if (storageTypeRadios.length > 0) {
        storageTypeRadios.forEach(function(radio) {
            radio.addEventListener('change', updateStorageOptions);
        });
        
        // Set initial state on page load for the storage page
        updateStorageOptions();
    }
    
    // --- 通用功能 ---

    // 表单字段错误状态清除
    var allInputs = document.querySelectorAll('input, textarea, select');
    allInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            this.classList.remove('error');
        });
    });

    // --- Generic Form Validation for all steps ---
    // This script provides a consistent validation experience.
    var forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var requiredFields = form.querySelectorAll('[required]');
            var isValid = true;
            var firstInvalidField = null;

            for (var field of requiredFields) {
                // An element is visible if its offsetParent is not null.
                var isVisible = field.offsetParent !== null;

                if (isVisible && field.value.trim() === '') {
                    field.classList.add('error');
                    isValid = false;
                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                } else {
                    field.classList.remove('error');
                }
            }

            if (!isValid) {
                e.preventDefault(); // Stop form submission
                alert('请填写所有必填字段。');
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
            }
        });
    });
});
