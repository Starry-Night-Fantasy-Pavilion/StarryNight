function(() {
    var container = document.querySelector('.consistency-check-container');
    var adminPrefixRaw = (container && container.dataset && container.dataset.adminPrefix) ? container.dataset.adminPrefix : '';
    var adminPrefix = (adminPrefixRaw || '').replace(/^\/+|\/+$/g, '');
    var base = adminPrefix ? `/${adminPrefix}/consistency` : '/consistency';

    var sensitivity = document.getElementById('sensitivity_level');
    var rangeValue = document.querySelector('.range-value');
    var btnTestConnection = document.getElementById('btnTestConnection');
    var vectorDbMode = document.getElementById('vector_db_mode');

    function updateSensitivityLabel() {
        if (!sensitivity || !rangeValue) return;
        rangeValue.textContent = String(sensitivity.value);
    }

    async function testConnection() {
        var vectorDbIdEl = document.getElementById('primary_vector_db_id');
        var embeddingModelIdEl = document.getElementById('embedding_model_id');
        var vectorDbId = vectorDbIdEl ? vectorDbIdEl.value : '';
        var embeddingModelId = embeddingModelIdEl ? embeddingModelIdEl.value : '';

        if (!vectorDbId || !embeddingModelId) {
            alert('请先选择向量数据库和嵌入模型');
            return;
        }

        try {
            var resp = await fetch(base + '/test-connection', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    vector_db_id: vectorDbId,
                    embedding_model_id: embeddingModelId
                })
            });
            var data = await resp.json();
            if (data.success) {
                alert('连接测试成功！');
            } else {
                alert('连接测试失败：' + (data.message || '未知错误'));
            }
        } catch (error) {
            alert('连接测试出错：' + error.message);
        }
    }

    if (sensitivity) sensitivity.addEventListener('input', updateSensitivityLabel);
    if (btnTestConnection) btnTestConnection.addEventListener('click', testConnection);
    if (vectorDbMode) {
        vectorDbMode.addEventListener('change', function () {
            if (this.value === 'multi') {
                console.log('多数据库模式');
            } else {
                console.log('单数据库模式');
            }
        });
    }

    updateSensitivityLabel();
})();

