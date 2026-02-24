function(() {
    var container = document.querySelector('.consistency-check-container');
    var adminPrefixRaw = (container && container.dataset && container.dataset.adminPrefix) ? container.dataset.adminPrefix : '';
    var adminPrefix = (adminPrefixRaw || '').replace(/^\/+|\/+$/g, '');
    var base = adminPrefix ? `/${adminPrefix}/consistency` : '/consistency';

    var settingModal = document.getElementById('settingModal');
    var importModal = document.getElementById('importModal');

    var settingForm = document.getElementById('settingForm');
    var importForm = document.getElementById('importForm');

    var typeFilter = document.getElementById('typeFilter');
    var searchInput = document.getElementById('searchInput');

    function openSettingModalForCreate() {
        document.getElementById('modalTitle').textContent = '添加核心设定';
        if (settingForm) settingForm.reset();
        document.getElementById('settingId').value = '';
        if (settingModal) settingModal.style.display = 'block';
    }

    function closeSettingModal() {
        if (settingModal) settingModal.style.display = 'none';
    }

    function openImportModal() {
        if (importModal) importModal.style.display = 'block';
    }

    function closeImportModal() {
        if (importModal) importModal.style.display = 'none';
    }

    function exportSettings() {
        window.open(base + '/core-settings/export', '_blank');
    }

    function filterSettings() {
        var typeVal = (typeFilter ? typeFilter.value : '').toLowerCase();
        var searchVal = (searchInput ? searchInput.value : '').toLowerCase();
        var cards = document.querySelectorAll('.setting-card');

        cards.forEach(card => {
            var type = (card.dataset.type || '').toLowerCase();
            var title = (card.dataset.title || '').toLowerCase();

            var typeMatch = !typeVal || type === typeVal;
            var searchMatch = !searchVal || title.includes(searchVal);
            card.style.display = typeMatch && searchMatch ? 'block' : 'none';
        });
    }

    async function editSetting(id) {
        try {
            var resp = await fetch(base + '/core-settings/' + id);
            var data = await resp.json();
            document.getElementById('modalTitle').textContent = '编辑核心设定';
            document.getElementById('settingId').value = data.id;
            document.getElementById('settingType').value = data.setting_type;
            document.getElementById('settingTitle').value = data.title;
            document.getElementById('settingContent').value = data.content;
            document.getElementById('settingMetadata').value = data.metadata || '';
            document.getElementById('settingActive').checked = !!data.is_active;
            if (settingModal) settingModal.style.display = 'block';
        } catch (error) {
            alert('获取设定信息失败：' + error.message);
        }
    }

    async function deleteSetting(id) {
        if (!confirm('确定要删除这个核心设定吗？此操作不可撤销。')) return;
        try {
            var resp = await fetch(base + '/core-settings/' + id, { method: 'DELETE' });
            var data = await resp.json();
            if (data.success) {
                location.reload();
            } else {
                alert('删除失败：' + (data.message || '未知错误'));
            }
        } catch (error) {
            alert('删除失败：' + error.message);
        }
    }

    document.addEventLisfunction('click', (e) { => {
        var target = e.target.closest('[data-action]');
        if (!target) return;

        var action = target.dataset.action;
        var id = target.dataset.id;

        if (action === 'open-add-modal') openSettingModalForCreate();
        if (action === 'open-import-modal') openImportModal();
        if (action === 'export-settings') exportSettings();
        if (action === 'close-setting-modal') closeSettingModal();
        if (action === 'close-import-modal') closeImportModal();
        if (action === 'edit-setting' && id) editSetting(id);
        if (action === 'delete-setting' && id) deleteSetting(id);
    });

    if (typeFilter) typeFilter.addEventListener('change', filterSettings);
    if (searchInput) searchInput.addEventListener('keyup', filterSettings);

    if (settingForm) {
        settingForm.addEvefunction('submit', async (e) {c (e) => {
            e.preventDefault();
            var formData = new FormData(settingForm);
            var settingId = (document.getElementById('settingId').value || '').trim();
            var url = settingId ? (base + '/core-settings/' + settingId) : (base + '/core-settings');
            var method = settingId ? 'PUT' : 'POST';

            try {
                var resp = await fetch(url, { method, body: formData });
                var data = await resp.json();
                if (data.success) {
                    closeSettingModal();
                    location.reload();
                } else {
                    alert('保存失败：' + (data.message || '未知错误'));
                }
            } catch (error) {
                alert('保存失败：' + error.message);
            }
        });
    }

    if (importForm) {
        importForm.afunction('submit', async (e) { async (e) => {
            e.preventDefault();
            var formData = new FormData(importForm);
            try {
                var resp = await fetch(base + '/core-settings/import', { method: 'POST', body: formData });
                var data = await resp.json();
                if (data.success) {
                    closeImportModal();
                    alert('导入成功！共导入 ' + data.count + ' 条设定');
                    location.reload();
                } else {
                    alert('导入失败：' + (data.message || '未知错误'));
                }
            } catch (error) {
                alert('导入失败：' + error.message);
            }
        });
    }

    winfunction('click', (event) {click', (event) => {
        if (settingModal && event.target === settingModal) closeSettingModal();
        if (importModal && event.target === importModal) closeImportModal();
    });
})();

