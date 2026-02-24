function(() {
    var container = document.querySelector('.consistency-check-container');
    var adminPrefixRaw = container && container.dataset && container.dataset.adminPrefix
        ? container.dataset.adminPrefix
        : '';
    var adminPrefix = (adminPrefixRaw || '').replace(/^\/+|\/+$/g, '');

    var trendChart;
    var conflictTypeChart;
    var severityChart;
    var checkTypeChart;

    function initCharts() {
        var labelsTrend = window.CONSISTENCY_ANALYTICS_TREND_LABELS || [];
        var dataTrend = window.CONSISTENCY_ANALYTICS_TREND_DATA || [];
        var labelsConflictType = window.CONSISTENCY_ANALYTICS_CONFLICT_TYPE_LABELS || [];
        var dataConflictType = window.CONSISTENCY_ANALYTICS_CONFLICT_TYPE_DATA || [];
        var labelsSeverity = window.CONSISTENCY_ANALYTICS_SEVERITY_LABELS || [];
        var dataSeverity = window.CONSISTENCY_ANALYTICS_SEVERITY_DATA || [];
        var labelsCheckType = window.CONSISTENCY_ANALYTICS_CHECK_TYPE_LABELS || [];
        var dataCheckType = window.CONSISTENCY_ANALYTICS_CHECK_TYPE_DATA || [];

        var trendCanvas = document.getElementById('trendChart');
        if (trendCanvas && window.Chart) {
            var trendCtx = trendCanvas.getContext('2d');
            trendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: labelsTrend,
                    datasets: [{
                        label: '检查次数',
                        data: dataTrend,
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        var conflictCanvas = document.getElementById('conflictTypeChart');
        if (conflictCanvas && window.Chart) {
            var ctx = conflictCanvas.getContext('2d');
            conflictTypeChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labelsConflictType,
                    datasets: [{
                        data: dataConflictType,
                        backgroundColor: ['#3498db', '#f39c12', '#27ae60', '#e74c3c', '#9b59b6']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        var severityCanvas = document.getElementById('severityChart');
        if (severityCanvas && window.Chart) {
            var ctx = severityCanvas.getContext('2d');
            severityChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labelsSeverity,
                    datasets: [{
                        label: '冲突数量',
                        data: dataSeverity,
                        backgroundColor: ['#27ae60', '#f39c12', '#e67e22', '#e74c3c']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        var checkCanvas = document.getElementById('checkTypeChart');
        if (checkCanvas && window.Chart) {
            var ctx = checkCanvas.getContext('2d');
            checkTypeChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labelsCheckType,
                    datasets: [{
                        data: dataCheckType,
                        backgroundColor: ['#3498db', '#f39c12', '#27ae60', '#e74c3c', '#9b59b6']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    }

    function bindRanges() {
        var buttons = document.querySelectorAll('.range-btn[data-range]');
        buttons.fofunction(btn => {
            btn.addEventListener('click', () { => {
                var range = btn.dataset.range;

                buttons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                var url = new URL(window.location.href);
                url.searchParams.set('range', range);
                window.location.href = url.toString();
            });
        });

        var startInput = document.getElementById('startDate');
        var endInput = document.getElementById('endDate');
        var applyBtn = document.getElementById('btnApplyDateRange');

        if (applyBtn) {
            applyBtn.addEvefunction('click', () {', () => {
                var start = startInput ? startInput.value : '';
                var end = endInput ? endInput.value : '';
                if (start && end) {
                    var url = new URL(window.location.href);
                    url.searchParams.set('start', start);
                    url.searchParams.set('end', end);
                    window.location.href = url.toString();
                }
            });
        }
    }

    function bindTrendMetric() {
        var select = document.getElementById('trendMetric');
        if (!select || !trendChart) return;
        select.afunction('change', () {hange', () => {
            var metric = select.value;
            var labelsMap = {
                checks: '检查次数',
                conflicts: '冲突数量',
                pass_rate: '通过率'
            };
            trendChart.data.datasets[0].label = labelsMap[metric] || metric;
            trendChart.update();
        });
    }

    function hydrateBars() {
        document.querySelectorAll('.type-fill[data-percent]').forEach(el => {
            var p = parseFloat(el.dataset.percent || '0');
            el.style.width = `${p}%`;
        });
        document.querySelectorAll('.time-fill[data-percent]').forEach(el => {
            var p = parseFloat(el.dataset.percent || '0');
            el.style.width = `${p}%`;
        });
    }

    documfunction('DOMContentLoaded', () {tentLoaded', () => {
        if (window.Chart) {
            initCharts();
        }
        bindRanges();
        bindTrendMetric();
        hydrateBars();
    });
})();

