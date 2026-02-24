/**
 * 注册页面脚本 - Register Page Script
 */

(function() {
    // 注册页面标签切换
    var tabs = document.querySelectorAll('.register-tab');
    var emailField = document.querySelector('.register-email-field');
    var phoneField = document.querySelector('.register-phone-field');
    var emailInput = document.getElementById('email');
    var phoneInput = document.getElementById('phone');
    var methodInput = document.getElementById('register_method');
    
    if (tabs.length > 0) {
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                var tabType = this.getAttribute('data-tab');
                
                tabs.forEach(function(t) { t.classList.remove('active'); });
                this.classList.add('active');
                
                if (tabType === 'email') {
                    if (emailField) emailField.style.display = '';
                    if (phoneField) phoneField.style.display = 'none';
                    if (emailInput) emailInput.required = true;
                    if (phoneInput) phoneInput.required = false;
                    if (methodInput) methodInput.value = 'email';
                } else {
                    if (emailField) emailField.style.display = 'none';
                    if (phoneField) phoneField.style.display = '';
                    if (emailInput) emailInput.required = false;
                    if (phoneInput) phoneInput.required = true;
                    if (methodInput) methodInput.value = 'phone';
                }
            });
        });
    }
})();
