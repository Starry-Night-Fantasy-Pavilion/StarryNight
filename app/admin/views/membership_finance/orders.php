<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="finance-card">
    <div class="finance-card-header">
        <h2>充值记录</h2>
    </div>
    <div class="finance-card-body">
        <!-- 筛选表单 -->
        <form method="GET" class="filter-form">
            <input type="text" name="user_id" class="form-control" placeholder="用户ID" value="<?= htmlspecialchars($_GET['user_id'] ?? '') ?>" style="width: 120px;">
            <select name="status" class="form-control" style="width: 140px;">
                <option value="">全部状态</option>
                <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>待支付</option>
                <option value="completed" <?= ($_GET['status'] ?? '') === 'completed' ? 'selected' : '' ?>>已完成</option>
                <option value="failed" <?= ($_GET['status'] ?? '') === 'failed' ? 'selected' : '' ?>>失败</option>
                <option value="refunded" <?= ($_GET['status'] ?? '') === 'refunded' ? 'selected' : '' ?>>已退款</option>
            </select>
            <button type="submit" class="action-btn action-btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                筛选
            </button>
            <a href="/<?= $adminPrefix ?>/finance/orders" class="action-btn action-btn-secondary">重置</a>
        </form>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <p class="empty-state-text">暂无充值记录</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="finance-table">
                    <thead>
                        <tr>
                            <th>订单号</th>
                            <th>用户</th>
                            <th>金额</th>
                            <th>支付方式</th>
                            <th>状态</th>
                            <th>支付时间</th>
                            <th>退款信息</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>
                                    <code style="background: rgba(255,255,255,0.08); padding: 4px 8px; border-radius: 4px; font-size: 12px; color: rgba(255,255,255,0.8);">
                                        <?= htmlspecialchars($o['order_id'] ?? '') ?>
                                    </code>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #0ea5e9, #8b5cf6); display: flex; align-items: center; justify-content: center; font-size: 12px; color: #fff;">
                                            <?= mb_substr(htmlspecialchars($o['nickname'] ?? $o['username'] ?? 'U'), 0, 1) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 500; color: #fff;"><?= htmlspecialchars($o['nickname'] ?? $o['username'] ?? '') ?></div>
                                            <div style="font-size: 11px; color: rgba(255,255,255,0.5);">ID: <?= (int)$o['user_id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="price-display">
                                        <span class="currency">¥</span>
                                        <span class="amount"><?= number_format((float)($o['amount'] ?? 0), 2) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span style="color: rgba(255,255,255,0.7);"><?= htmlspecialchars($o['payment_gateway'] ?? '-') ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $status = $o['status'] ?? '';
                                    $statusClass = '';
                                    $statusText = '';
                                    switch ($status) {
                                        case 'completed':
                                            $statusClass = 'active';
                                            $statusText = '已完成';
                                            break;
                                        case 'pending':
                                            $statusClass = 'pending';
                                            $statusText = '待支付';
                                            break;
                                        case 'failed':
                                            $statusClass = 'failed';
                                            $statusText = '失败';
                                            break;
                                        case 'refunded':
                                            $statusClass = 'refunded';
                                            $statusText = '已退款';
                                            break;
                                        default:
                                            $statusClass = 'inactive';
                                            $statusText = $status;
                                    }
                                    ?>
                                    <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($statusText) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($o['paid_at'])): ?>
                                        <div style="font-size: 13px; color: rgba(255,255,255,0.8);">
                                            <?= date('Y-m-d', strtotime($o['paid_at'])) ?>
                                        </div>
                                        <div style="font-size: 11px; color: rgba(255,255,255,0.5);">
                                            <?= date('H:i:s', strtotime($o['paid_at'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: rgba(255,255,255,0.3);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($o['status'] ?? '') === 'refunded'): ?>
                                        <div style="font-size: 12px; line-height: 1.6;">
                                            <div style="color: #ef4444;">
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -1px; margin-right: 2px;">
                                                    <circle cx="12" cy="12" r="10"/>
                                                    <line x1="15" y1="9" x2="9" y2="15"/>
                                                    <line x1="9" y1="9" x2="15" y2="15"/>
                                                </svg>
                                                原因: <?= htmlspecialchars($o['refund_reason'] ?? '-') ?>
                                            </div>
                                            <div style="color: rgba(255,255,255,0.5); margin-top: 4px;">
                                                时间: <?= !empty($o['refunded_at']) ? date('Y-m-d H:i', strtotime($o['refunded_at'])) : '-' ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: rgba(255,255,255,0.3);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($o['status'] ?? '') === 'completed'): ?>
                                        <a href="/<?= $adminPrefix ?>/finance/order/<?= (int)$o['id'] ?>/refund" class="action-btn action-btn-danger">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                                <path d="M3 3v5h5"/>
                                            </svg>
                                            退款
                                        </a>
                                    <?php else: ?>
                                        <span style="color: rgba(255,255,255,0.3);">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($hasMore ?? false): ?>
                <div class="pagination-wrapper">
                    <a href="?page=<?= ($page ?? 1) + 1 ?>&<?= http_build_query(array_diff_key($_GET, ['page' => 1])) ?>" class="pagination-btn">
                        加载更多
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
