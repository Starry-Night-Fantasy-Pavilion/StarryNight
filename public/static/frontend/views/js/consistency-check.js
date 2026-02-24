function(() {
    var container = document.querySelector('.consistency-check-container');
    var adminPrefixRaw = (container && container.dataset && container.dataset.adminPrefix) ? container.dataset.adminPrefix : '';
    var adminPrefix = (adminPrefixRaw || '').replace(/^\/+|\/+$/g, '');
    var base = adminPrefix ? `/${adminPrefix}/consistency` : '/consistency';

    var checkContent = document.getElementById('checkContent');
    var charCount = document.getElementById('charCount');
    var wordCount = document.getElementById('wordCount');
    var sensitivity = document.getElementById('sensitivity');
    var rangeValue = document.querySelector('.range-value');
    var form = document.getElementById('checkForm');
    var checkBtn = document.getElementById('checkBtn');

    var resultSection = document.getElementById('resultSection');
    var conflictsList = document.getElementById('conflictsList');

    var btnLoadExample = document.getElementById('btnLoadExample');
    var btnSaveReport = document.getElementById('btnSaveReport');
    var btnExportResult = document.getElementById('btnExportResult');
    var btnRetryCheck = document.getElementById('btnRetryCheck');

    function updateCounts() {
        if (!checkContent || !charCount || !wordCount) return;
        var content = checkContent.value || '';
        charCount.textContent = String(content.length);
        wordCount.textContent = String(content.split(/\s+/).filter(word => word.length > 0).length);
    }

    function updateSensitivityLabel() {
        if (!sensitivity || !rangeValue) return;
        rangeValue.textContent = String(sensitivity.value);
    }

    function showResultSection() {
        if (!resultSection) return;
        resultSection.classList.remove('is-hidden');
    }

    function hideResultSection() {
        if (!resultSection) return;
        resultSection.classList.add('is-hidden');
    }

    function getStatusLabel(status) {
        var labels = {
            success: '通过',
            warning: '警告',
            error: '冲突'
        };
        return labels[status] || '未知';
    }

    function getConflictTypeLabel(type) {
        var labels = {
            worldview: '世界观',
            character: '角色',
            event: '事件',
            rule: '规则'
        };
        return labels[type] || type;
    }

    function getSeverityLabel(severity) {
        var labels = {
            low: '低',
            medium: '中',
            high: '高',
            critical: '严重'
        };
        return labels[severity] || severity;
    }

    function createConflictHtml(conflict) {
        return (
            '<div class="conflict-item severity-' + conflict.severity + '">' +
            '<div class="conflict-header">' +
            '<div class="conflict-type">' + getConflictTypeLabel(conflict.type) + '</div>' +
            '<div class="conflict-severity">' + getSeverityLabel(conflict.severity) + '</div>' +
            '<div class="conflict-score">相似度: ' + (conflict.similarity * 100) + '%</div>' +
            '</div>' +
            '<div class="conflict-content">' +
            '<div class="conflict-original">' +
            '<h4>原文内容</h4>' +
            '<p>' + conflict.original_content + '</p>' +
            '</div>' +
            '<div class="conflict-core">' +
            '<h4>冲突设定</h4>' +
            '<p><strong>' + conflict.core_setting_title + '</strong></p>' +
            '<p>' + conflict.core_setting_content + '</p>' +
            '</div>' +
            '</div>' +
            '<div class="conflict-suggestion">' +
            '<h4>修复建议</h4>' +
            '<p>' + conflict.suggestion + '</p>' +
            '</div>' +
            '</div>'
        );
    }

    function displayResult(result) {
        showResultSection();

        var summaryCards = document.querySelectorAll('.summary-value');
        if (summaryCards.length >= 4) {
            summaryCards[0].textContent = getStatusLabel(result.overall_status);
            summaryCards[1].textContent = result.conflict_count + ' 个';
            summaryCards[2].textContent = (result.avg_similarity * 100) + '%';
            summaryCards[3].textContent = result.check_time + 's';
        }

        if (conflictsList) {
            conflictsList.innerHTML = '';
            if (result.conflicts && result.conflicts.length > 0) {
                result.conflicts.forEach(conflict => {
                    conflictsList.insertAdjacentHTML('beforeend', createConflictHtml(conflict));
                });
            } else {
                conflictsList.innerHTML = '<div class="no-conflicts"><p>未发现冲突，内容与核心设定一致！</p></div>';
            }
        }

        if (resultSection && typeof resultSection.scrollIntoView === 'function') {
            resultSection.scrollIntoView({ behavior: 'smooth' });
        }
    }

    function loadExample() {
        if (!checkContent) return;
        var exampleContent =
            '第一章：神秘的开始\n\n月光透过古老的城堡窗户洒在石制地板上，艾莉丝独自站在大厅中央。她是一位年轻的魔法师，拥有控制火焰的能力，这是她家族世代相传的天赋。\n\n"你真的要去吗？"她的导师马格努斯问道，"黑暗森林的危险超乎想象。"\n\n艾莉丝点点头，握紧了手中的法杖。"我必须去，只有找到失落的魔法宝石，才能拯救我们的村庄。"\n\n突然，大厅的门被推开，一个身穿黑袍的神秘人走了进来。"我知道宝石的下落，"他说道，"但我需要你的帮助。"\n\n艾莉丝警惕地看着这个陌生人，她能感觉到他身上散发出的黑暗气息。这个人似乎并不像表面上看起来那么简单...';

        checkContent.value = exampleContent;
        updateCounts();
    }

    function clearForm() {
        if (form) form.reset();
        updateCounts();
        updateSensitivityLabel();
        hideResultSection();
    }

    function saveReport() {
        alert('报告已保存');
    }

    function exportResult() {
        window.open(base + '/check/export', '_blank');
    }

    function retryCheck() {
        if (!form) return;
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }
        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    }

    if (checkContent) checkContent.addEventListener('input', updateCounts);
    if (sensitivity) sensitivity.addEventListener('input', updateSensitivityLabel);
    if (form) {
        form.addEventLisfunction('reset', () { => {
            setTimeout(clearForm, 0);
        });
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var content = (checkContent && checkContent.value ? checkContent.value.trim() : '');
            if (!content) {
                alert('请输入要检查的内容');
                return;
            }

            if (checkBtn) {
                checkBtn.disabled = true;
                checkBtn.innerHTML = '<i class="icon">⏳</i> 检查中...';
            }

            var formData = new FormData(form);

            fetch(base + '/check', {
                method: 'POST',
                body: formData
            })
                .then(resp => resp.json())
                .then(data => {
                    if (data.success) {
                        displayResult(data.result);
                    } else {
                        alert('检查失败：' + (data.message || '未知错误'));
                    }
                })
                .catch(error => {
                    alert('检查失败：' + error.message);
                })
              function(() {ly(() => {
                    if (checkBtn) {
                        checkBtn.disabled = false;
                        checkBtn.innerHTML = '<i class="icon">🔍</i> 开始检查';
                    }
                });
        });
    }

    if (btnLoadExample) btnLoadExample.addEventListener('click', loadExample);
    if (btnSaveReport) btnSaveReport.addEventListener('click', saveReport);
    if (btnExportResult) btnExportResult.addEventListener('click', exportResult);
    if (btnRetryCheck) btnRetryCheck.addEventListener('click', retryCheck);

    updateCounts();
    updateSensitivityLabel();
})();

