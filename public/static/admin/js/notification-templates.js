(function () {
    var openBtn = document.getElementById('btn-open-template-modal');
    var closeBtn = document.getElementById('btn-close-template-modal');
    var cancelBtn = document.getElementById('btn-cancel-template-modal');
    var backdrop = document.getElementById('template-modal-backdrop');
    var modal = document.getElementById('template-modal');
    var channelSelect = modal ? modal.querySelector('select[name="channel"]') : null;
    var nameInput = modal ? modal.querySelector('input[name="name"]') : null;
    var codeInput = modal ? modal.querySelector('input[name="code"]') : null;
    var fileInput = modal ? modal.querySelector('input[name="template_file"]') : null;

    var detailBackdrop = document.getElementById('template-detail-modal-backdrop');
    var detailModal = document.getElementById('template-detail-modal');
    var detailChannel = document.getElementById('detail_channel');
    var detailTitle = document.getElementById('detail_title');
    var detailCode = document.getElementById('detail_code');
    var detailFile = document.getElementById('detail_file');
    var closeDetailBtn = document.getElementById('btn-close-template-detail-modal');
    var closeDetailFooterBtn = document.getElementById('btn-close-template-detail-modal-footer');

    var testBackdrop = document.getElementById('template-test-modal-backdrop');
    var testModal = document.getElementById('template-test-modal');
    var testIdInput = document.getElementById('test_template_id');
    var testLabel = document.getElementById('template-test-label');
    var closeTestBtn = document.getElementById('btn-close-template-test-modal');
    var cancelTestBtn = document.getElementById('btn-cancel-template-test-modal');

    function openModal() {
        if (!modal || !backdrop) return;
        modal.style.display = 'block';
        backdrop.style.display = 'block';
    }

    function closeModal() {
        if (!modal || !backdrop) return;
        modal.style.display = 'none';
        backdrop.style.display = 'none';
    }

    function openDetailModal(data) {
        if (!detailModal || !detailBackdrop) return;
        if (detailChannel) detailChannel.textContent = data.channel || '';
        if (detailTitle) detailTitle.textContent = data.title || '';
        if (detailCode) detailCode.textContent = data.code || '';
        if (detailFile) detailFile.textContent = data.file || '';
        detailModal.style.display = 'block';
        detailBackdrop.style.display = 'block';
    }

    function closeDetailModal() {
        if (!detailModal || !detailBackdrop) return;
        detailModal.style.display = 'none';
        detailBackdrop.style.display = 'none';
    }

    function openTestModal(id, channel, code) {
        if (!testModal || !testBackdrop || !testIdInput) return;
        testIdInput.value = id || '';
        if (testLabel) {
            var label = '[' + (channel || '') + '] ' + (code || '');
            testLabel.textContent = label;
        }
        testModal.style.display = 'block';
        testBackdrop.style.display = 'block';
    }

    function closeTestModal() {
        if (!testModal || !testBackdrop) return;
        testModal.style.display = 'none';
        testBackdrop.style.display = 'none';
    }

    function slugify(str) {
        if (!str) return '';
        str = str.toLowerCase();
        str = str.replace(/[^a-z0-9]+/g, '_');
        str = str.replace(/^_+|_+$/g, '');
        return str;
    }

    function autoFillCodeFromName() {
        if (!codeInput || !nameInput) return;
        if (codeInput.value.trim() !== '') return;
        var channel = channelSelect ? (channelSelect.value || 'email') : 'email';
        var base = slugify(nameInput.value);
        if (!base) return;
        codeInput.value = channel + '_' + base;
    }

    function autoFillCodeFromFile() {
        if (!codeInput || !fileInput) return;
        if (codeInput.value.trim() !== '') return;
        var channel = channelSelect ? (channelSelect.value || 'email') : 'email';
        var full = fileInput.value.split(/[/\\]/).pop() || '';
        var base = full.split('.').slice(0, -1).join('.') || full;
        base = slugify(base);
        if (!base) return;
        codeInput.value = channel + '_' + base;
    }

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    if (nameInput) {
        nameInput.addEventListener('blur', autoFillCodeFromName);
    }
    if (fileInput) {
        fileInput.addEventListener('change', autoFillCodeFromFile);
    }

    // 列表按钮（详情 / 测试）
    document.addEventListener('click', function (e) {
        var detailBtn = e.target.closest('.btn-template-detail');
        if (detailBtn) {
            e.preventDefault();
            openDetailModal({
                channel: detailBtn.getAttribute('data-channel') || '',
                title: detailBtn.getAttribute('data-title') || '',
                code: detailBtn.getAttribute('data-code') || '',
                file: detailBtn.getAttribute('data-file') || ''
            });
            return;
        }

        var testBtn = e.target.closest('.btn-template-test');
        if (testBtn) {
            e.preventDefault();
            var id = testBtn.getAttribute('data-id') || '';
            var channel = testBtn.getAttribute('data-channel') || '';
            var code = testBtn.getAttribute('data-code') || '';
            openTestModal(id, channel, code);
            return;
        }
    });

    if (closeTestBtn) closeTestBtn.addEventListener('click', closeTestModal);
    if (cancelTestBtn) cancelTestBtn.addEventListener('click', closeTestModal);
    if (testBackdrop) testBackdrop.addEventListener('click', closeTestModal);
    if (closeDetailBtn) closeDetailBtn.addEventListener('click', closeDetailModal);
    if (closeDetailFooterBtn) closeDetailFooterBtn.addEventListener('click', closeDetailModal);
    if (detailBackdrop) detailBackdrop.addEventListener('click', closeDetailModal);

    // ESC 关闭所有弹窗
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeModal();
            closeTestModal();
            closeDetailModal();
        }
    });
})();

