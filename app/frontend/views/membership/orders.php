<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的订单 - 星夜阁</title>
    <?php use app\config\FrontendConfig; ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('modules/membership.css')) ?>">
</head>
<body>
    <div class="membership-container">
        <!-- 页面头部 -->
        <header class="page-header">
            <div class="header-content">
                <h1>我的订单</h1>
                
                <!-- 订单类型切换 -->
                <div class="order-tabs">
                    <button class="tab-btn <?= $type === 'all' || $type === 'membership' ? 'active' : '' ?>" onclick="switchTab('membership')">
                        会员订单
                    </button>
                    <button class="tab-btn <?= $type === 'recharge' ? 'active' : '' ?>" onclick="switchTab('recharge')">
                        充值订单
                    </button>
                </div>

                <!-- 筛选器 -->
                <div class="order-filters">
                    <select id="statusFilter" onchange="filterOrders()">
                        <option value="">全部状态</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>待支付</option>
                        <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>已支付</option>
                        <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>支付失败</option>
                        <option value="refunded" <?= $status === 'refunded' ? 'selected' : '' ?>>已退款</option>
                    </select>
                    <input type="text" id="searchInput" placeholder="搜索订单号..." value="<?= htmlspecialchars($search) ?>" onkeyup="searchOrders()">
                </div>
            </div>
        </header>

        <!-- 会员订单 -->
        <div id="membershipOrders" class="orders-section <?= $type === 'all' || $type === 'membership' ? '' : 'hidden' ?>">
            <div class="orders-list">
                <?php if ($membershipOrders['records']): ?>
                    <?php foreach ($membershipOrders['records'] as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-info">
                                    <h3><?= htmlspecialchars($order['membership_name']) ?></h3>
                                    <span class="order-no">订单号：<?= htmlspecialchars($order['order_no']) ?></span>
                                </div>
                                <div class="order-status">
                                    <span class="status-badge status-<?= $order['payment_status'] ?>">
                                        <?= $this->getMembershipStatusText($order['payment_status']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="order-details">
                                <div class="detail-item">
                                    <span class="label">会员类型：</span>
                                    <span class="value"><?= $this->getMembershipTypeName($order['membership_type']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="label">开始时间：</span>
                                    <span class="value"><?= $order['start_time'] ?></span>
                                </div>
                                <?php if ($order['end_time']): ?>
                                    <div class="detail-item">
                                        <span class="label">到期时间：</span>
                                        <span class="value"><?= $order['end_time'] ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="detail-item">
                                    <span class="label">原价：</span>
                                    <span class="value">¥<?= $order['original_price'] ?></span>
                                </div>
                                <?php if ($order['discount_amount'] > 0): ?>
                                    <div class="detail-item">
                                        <span class="label">优惠金额：</span>
                                        <span class="value discount">-¥<?= $order['discount_amount'] ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="detail-item">
                                    <span class="label">实付金额：</span>
                                    <span class="value price">¥<?= $order['actual_price'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="label">创建时间：</span>
                                    <span class="value"><?= $order['created_at'] ?></span>
                                </div>
                                <?php if ($order['payment_time']): ?>
                                    <div class="detail-item">
                                        <span class="label">支付时间：</span>
                                        <span class="value"><?= $order['payment_time'] ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($order['refund_time']): ?>
                                    <div class="detail-item">
                                        <span class="label">退款时间：</span>
                                        <span class="value"><?= $order['refund_time'] ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($order['refund_reason']): ?>
                                    <div class="detail-item">
                                        <span class="label">退款原因：</span>
                                        <span class="value"><?= htmlspecialchars($order['refund_reason']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="order-actions">
                                <?php if ($order['payment_status'] === 'pending'): ?>
                                    <button class="btn btn-primary" onclick="payOrder('<?= htmlspecialchars($order['order_no']) ?>')">
                                        立即支付
                                    </button>
                                <?php endif; ?>
                                <?php if ($order['payment_status'] === 'paid' && $order['auto_renew']): ?>
                                    <button class="btn btn-secondary" onclick="cancelAutoRenew('<?= $order['id'] ?>')">
                                        取消自动续费
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-outline" onclick="viewOrderDetail('<?= $order['id'] ?>', 'membership')">
                                    查看详情
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <p>暂无会员订单</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 分页 -->
            <?php if ($membershipOrders['totalPages'] > 1): ?>
                <div class="pagination">
                    <?php
                    $currentPage = $membershipOrders['page'];
                    $totalPages = $membershipOrders['totalPages'];
                    $total = $membershipOrders['total'];
                    ?>
                    
                    <?php if ($currentPage > 1): ?>
                        <a href="?type=membership&page=<?= $currentPage - 1 ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>" class="page-link">上一页</a>
                    <?php endif; ?>
                    
                    <span class="page-info">
                        第 <?= $currentPage ?> 页，共 <?= $totalPages ?> 页 (总计 <?= $total ?> 条记录)
                    </span>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?type=membership&page=<?= $currentPage + 1 ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>" class="page-link">下一页</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 充值订单 -->
        <div id="rechargeOrders" class="orders-section <?= $type === 'recharge' ? '' : 'hidden' ?>">
            <div class="orders-list">
                <?php if ($rechargeOrders['records']): ?>
                    <?php foreach ($rechargeOrders['records'] as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-info">
                                    <h3><?= htmlspecialchars($order['package_name']) ?></h3>
                                    <span class="order-no">订单号：<?= htmlspecialchars($order['order_no']) ?></span>
                                </div>
                                <div class="order-status">
                                    <span class="status-badge status-<?= $order['payment_status'] ?>">
                                        <?= $this->getRechargeStatusText($order['payment_status']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="order-details">
                                <div class="detail-item">
                                    <span class="label">星夜币数量：</span>
                                    <span class="value"><?= number_format($order['total_tokens']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="label">基础星夜币：</span>
                                    <span class="value"><?= number_format($order['tokens']) ?></span>
                                </div>
                                <?php if ($order['bonus_tokens'] > 0): ?>
                                    <div class="detail-item">
                                        <span class="label">赠送星夜币：</span>
                                        <span class="value bonus">+<?= number_format($order['bonus_tokens']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="detail-item">
                                    <span class="label">原价：</span>
                                    <span class="value">¥<?= $order['original_price'] ?></span>
                                </div>
                                <?php if ($order['discount_amount'] > 0): ?>
                                    <div class="detail-item">
                                        <span class="label">优惠金额：</span>
                                        <span class="value discount">-¥<?= $order['discount_amount'] ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="detail-item">
                                    <span class="label">实付金额：</span>
                                    <span class="value price">¥<?= $order['actual_price'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="label">创建时间：</span>
                                    <span class="value"><?= $order['created_at'] ?></span>
                                </div>
                                <?php if ($order['payment_time']): ?>
                                    <div class="detail-item">
                                        <span class="label">支付时间：</span>
                                        <span class="value"><?= $order['payment_time'] ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($order['refund_time']): ?>
                                    <div class="detail-item">
                                        <span class="label">退款时间：</span>
                                        <span class="value"><?= $order['refund_time'] ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($order['refund_reason']): ?>
                                    <div class="detail-item">
                                        <span class="label">退款原因：</span>
                                        <span class="value"><?= htmlspecialchars($order['refund_reason']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="order-actions">
                                <?php if ($order['payment_status'] === 'pending'): ?>
                                    <button class="btn btn-primary" onclick="payOrder('<?= htmlspecialchars($order['order_no']) ?>')">
                                        立即支付
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-outline" onclick="viewOrderDetail('<?= $order['id'] ?>', 'recharge')">
                                    查看详情
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">💰</div>
                        <p>暂无充值订单</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 分页 -->
            <?php if ($rechargeOrders['totalPages'] > 1): ?>
                <div class="pagination">
                    <?php
                    $currentPage = $rechargeOrders['page'];
                    $totalPages = $rechargeOrders['totalPages'];
                    $total = $rechargeOrders['total'];
                    ?>
                    
                    <?php if ($currentPage > 1): ?>
                        <a href="?type=recharge&page=<?= $currentPage - 1 ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>" class="page-link">上一页</a>
                    <?php endif; ?>
                    
                    <span class="page-info">
                        第 <?= $currentPage ?> 页，共 <?= $totalPages ?> 页 (总计 <?= $total ?> 条记录)
                    </span>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?type=recharge&page=<?= $currentPage + 1 ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>" class="page-link">下一页</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?= htmlspecialchars(FrontendConfig::getThemeJsUrl('modules/membership.js')) ?>"></script>
    <script>
        function switchTab(type) {
            window.location.href = '?type=' + type + '&status=<?= $status ?>&search=<?= urlencode($search) ?>';
        }

        function filterOrders() {
            const status = document.getElementById('statusFilter').value;
            const search = document.getElementById('searchInput').value;
            window.location.href = '?type=<?= $type ?>&status=' + status + '&search=' + encodeURIComponent(search);
        }

        function searchOrders() {
            setTimeout(() => {
                filterOrders();
            }, 300);
        }

        function payOrder(orderNo) {
            // 跳转到支付页面
            alert('跳转到支付页面，订单号：' + orderNo);
        }

        function cancelAutoRenew(orderId) {
            if (confirm('确定要取消自动续费吗？')) {
                // 发送取消自动续费请求
                fetch('/membership/cancelAutoRenew', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({order_id: orderId})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('已取消自动续费');
                        location.reload();
                    } else {
                        alert('操作失败：' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('网络错误，请重试');
                });
            }
        }

        function viewOrderDetail(orderId, type) {
            // 显示订单详情
            alert('查看订单详情，ID：' + orderId + '，类型：' + type);
        }
    </script>
</body>
</html>

<?php
/**
 * 获取会员状态文本
 */
function getMembershipStatusText($status) {
    $texts = [
        'pending' => '待支付',
        'paid' => '已支付',
        'failed' => '支付失败',
        'refunded' => '已退款'
    ];
    
    return $texts[$status] ?? $status;
}

/**
 * 获取充值状态文本
 */
function getRechargeStatusText($status) {
    $texts = [
        'pending' => '待支付',
        'paid' => '已支付',
        'failed' => '支付失败',
        'refunded' => '已退款'
    ];
    
    return $texts[$status] ?? $status;
}

/**
 * 获取会员类型名称
 */
function getMembershipTypeName($type) {
    $names = [
        1 => '月度会员',
        2 => '年度会员',
        3 => '终身会员'
    ];
    
    return $names[$type] ?? '未知类型';
}
?>