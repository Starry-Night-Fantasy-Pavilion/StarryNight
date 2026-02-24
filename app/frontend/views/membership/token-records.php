<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>消费记录 - 星夜阁</title>
    <?php use app\config\FrontendConfig; ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('modules/membership.css')) ?>">
</head>
<body>
    <div class="membership-container">
        <!-- 页面头部 -->
        <header class="page-header">
            <div class="header-content">
                <h1>消费记录</h1>
                
                <!-- 筛选器 -->
                <div class="record-filters">
                    <select id="typeFilter" onchange="filterRecords()">
                        <option value="">全部类型</option>
                        <option value="ai_generation" <?= $type === 'ai_generation' ? 'selected' : '' ?>>AI生成</option>
                        <option value="file_upload" <?= $type === 'file_upload' ? 'selected' : '' ?>>文件上传</option>
                        <option value="storage_premium" <?= $type === 'storage_premium' ? 'selected' : '' ?>>高级存储</option>
                        <option value="feature_unlock" <?= $type === 'feature_unlock' ? 'selected' : '' ?>>功能解锁</option>
                        <option value="recharge" <?= $type === 'recharge' ? 'selected' : '' ?>>充值</option>
                        <option value="bonus" <?= $type === 'bonus' ? 'selected' : '' ?>>赠送</option>
                        <option value="refund" <?= $type === 'refund' ? 'selected' : '' ?>>退款</option>
                        <option value="system_adjust" <?= $type === 'system_adjust' ? 'selected' : '' ?>>系统调整</option>
                    </select>
                    <div class="date-filter">
                        <input type="date" id="startDate" placeholder="开始日期" onchange="filterRecords()">
                        <span>至</span>
                        <input type="date" id="endDate" placeholder="结束日期" onchange="filterRecords()">
                    </div>
                </div>
            </div>
        </header>

        <!-- 统计卡片 -->
        <section class="statistics-cards">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="icon-consumption"></i>
                </div>
                <div class="stat-content">
                    <h3>总消费</h3>
                    <p class="stat-value" id="totalConsumption">0</p>
                    <span class="stat-unit">星夜币</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="icon-income"></i>
                </div>
                <div class="stat-content">
                    <h3>总收入</h3>
                    <p class="stat-value" id="totalIncome">0</p>
                    <span class="stat-unit">星夜币</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="icon-balance"></i>
                </div>
                <div class="stat-content">
                    <h3>当前余额</h3>
                    <p class="stat-value" id="currentBalance">0</p>
                    <span class="stat-unit">星夜币</span>
                </div>
            </div>
        </section>

        <!-- 消费记录列表 -->
        <main class="records-main">
            <div class="records-list">
                <?php if ($records['records']): ?>
                    <?php foreach ($records['records'] as $record): ?>
                        <div class="record-item <?= $record['tokens'] < 0 ? 'consumption' : 'income' ?>">
                            <div class="record-header">
                                <div class="record-info">
                                    <h4><?= $this->getConsumptionTypeText($record['consumption_type']) ?></h4>
                                    <span class="record-time"><?= $record['created_at'] ?></span>
                                </div>
                                <div class="record-amount">
                                    <?php if ($record['tokens'] < 0): ?>
                                        <span class="amount negative">-<?= number_format(abs($record['tokens'])) ?></span>
                                    <?php else: ?>
                                        <span class="amount positive">+<?= number_format($record['tokens']) ?></span>
                                    <?php endif; ?>
                                    <span class="unit">星夜币</span>
                                </div>
                            </div>
                            
                            <div class="record-details">
                                <div class="detail-item">
                                    <span class="label">消费前余额：</span>
                                    <span class="value"><?= number_format($record['balance_before']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="label">消费后余额：</span>
                                    <span class="value"><?= number_format($record['balance_after']) ?></span>
                                </div>
                                <?php if ($record['description']): ?>
                                    <div class="detail-item">
                                        <span class="label">描述：</span>
                                        <span class="value"><?= htmlspecialchars($record['description']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($record['related_id'] && $record['related_type']): ?>
                                    <div class="detail-item">
                                        <span class="label">关联项目：</span>
                                        <span class="value">
                                            <a href="/<?= $this->getRelatedUrl($record['related_type'], $record['related_id']) ?>" class="related-link">
                                                查看详情
                                            </a>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📊</div>
                        <p>暂无消费记录</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 分页 -->
            <?php if ($records['totalPages'] > 1): ?>
                <div class="pagination">
                    <?php
                    $currentPage = $records['page'];
                    $totalPages = $records['totalPages'];
                    $total = $records['total'];
                    ?>
                    
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?= $currentPage - 1 ?>&type=<?= $type ?>" class="page-link">上一页</a>
                    <?php endif; ?>
                    
                    <span class="page-info">
                        第 <?= $currentPage ?> 页，共 <?= $totalPages ?> 页 (总计 <?= $total ?> 条记录)
                    </span>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?= $currentPage + 1 ?>&type=<?= $type ?>" class="page-link">下一页</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="<?= htmlspecialchars(FrontendConfig::getThemeJsUrl('modules/membership.js')) ?>"></script>
    <script>
        // 模拟数据，实际应该从后端获取
        const records = <?= json_encode($records['records']) ?>;
        
        // 计算统计数据
        function calculateStatistics() {
            let totalConsumption = 0;
            let totalIncome = 0;
            
            records.forEach(record => {
                if (record.tokens < 0) {
                    totalConsumption += Math.abs(record.tokens);
                } else {
                    totalIncome += record.tokens;
                }
            });
            
            document.getElementById('totalConsumption').textContent = numberFormat(totalConsumption);
            document.getElementById('totalIncome').textContent = numberFormat(totalIncome);
            
            // 计算当前余额（最后一条记录的余额）
            if (records.length > 0) {
                const lastRecord = records[records.length - 1];
                document.getElementById('currentBalance').textContent = numberFormat(lastRecord.balance_after);
            }
        }
        
        // 初始化统计数据
        calculateStatistics();
        
        function filterRecords() {
            const type = document.getElementById('typeFilter').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            let url = '?type=' + type;
            if (startDate) url += '&start_date=' + startDate;
            if (endDate) url += '&end_date=' + endDate;
            
            window.location.href = url;
        }
        
        function numberFormat(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        function getConsumptionTypeText(type) {
            const types = {
                'ai_generation': 'AI生成',
                'file_upload': '文件上传',
                'storage_premium': '高级存储',
                'feature_unlock': '功能解锁',
                'recharge': '充值',
                'bonus': '赠送',
                'refund': '退款',
                'system_adjust': '系统调整'
            };
            return types[type] || type;
        }
        
        function getRelatedUrl(type, id) {
            // 根据类型返回对应的URL
            const urls = {
                'novel': '/novel/' + id,
                'chapter': '/novel/chapter/' + id,
                'prompt': '/ai/prompts/' + id,
                'agent': '/ai/agents/' + id,
                'workflow': '/ai/workflows/' + id,
                'recharge_record': '/membership/orders?order_id=' + id,
                'membership_purchase': '/membership/orders?order_id=' + id
            };
            return urls[type] || '#';
        }
    </script>
</body>
</html>

<?php
/**
 * 获取消费类型文本
 */
function getConsumptionTypeText($type) {
    $texts = [
        'ai_generation' => 'AI生成',
        'file_upload' => '文件上传',
        'storage_premium' => '高级存储',
        'feature_unlock' => '功能解锁',
        'recharge' => '充值',
        'bonus' => '赠送',
        'refund' => '退款',
        'system_adjust' => '系统调整'
    ];
    
    return $texts[$type] ?? $type;
}

/**
 * 获取关联URL
 */
function getRelatedUrl($type, $id) {
    $urls = [
        'novel' => '/novel/' . $id,
        'chapter' => '/novel/chapter/' . $id,
        'prompt' => '/ai/prompts/' . $id,
        'agent' => '/ai/agents/' . $id,
        'workflow' => '/ai/workflows/' . $id,
        'recharge_record' => '/membership/orders?order_id=' . $id,
        'membership_purchase' => '/membership/orders?order_id=' . $id
    ];
    
    return $urls[$type] ?? '#';
}
?>