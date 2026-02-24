(function() {
    'use strict';

    function initLanguageSwitcher() {
        const langSelect = document.getElementById('language-select');
        if (!langSelect) {
            return;
        }

        langSelect.addEventListener('change', function() {
            const lang = this.value;
            fetch('/language/switch?lang=' + lang, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Language switch error:', error);
                window.location.href = '/language/switch?lang=' + lang;
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLanguageSwitcher);
    } else {
        initLanguageSwitcher();
    }
})();
