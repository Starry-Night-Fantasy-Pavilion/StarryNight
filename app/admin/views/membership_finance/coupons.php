<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="finance-card">
    <div class="finance-card-header">
        <h2>优惠券管理</h2>
        <a href="/<?= $adminPrefix ?>/finance/coupon/new" class="action-btn action-btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            新增优惠券
        </a>
    </div>
    <div class="finance-card-body">
        <?php if (empty($list)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎟️</div>
                <p class="empty-state-text">暂无优惠券，点击上方按钮新增</p>
                <a href="/<?= $adminPrefix ?>/finance/coupon/new" class="action-btn action-btn-primary">立即新增</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="finance-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>名称</th>
                            <th>类型/面额</th>
                            <th>最低消费</th>
                            <th>有效期</th>
                            <th>发放/已用</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $c): ?>
                            <tr>
                                <td><?= (int)$c['id'] ?></td>
                                <td>
                                    <span style="font-weight: 500; color: #fff;"><?= htmlspecialchars($c['name'] ?? '') ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $type = $c['type'] ?? '';
                                    $typeLabel = '';
                                    $typeColor = '';
                                    switch ($type) {
                                        case 'discount':
                                            $typeLabel = '折扣券';
                                            $typeColor = '#a855f7';
                                            break;
                                        case 'cash':
                                            $typeLabel = '现金券';
                                            $typeColor = '#22c55e';
                                            break;
                                        case 'full_reduction':
                                            $typeLabel = '满减券';
                                            $typeColor = '#f59e0b';
                                            break;
                                        default:
                                            $typeLabel = $type;
                                            $typeColor = '#0ea5e9';
                                    }
                                    ?>
                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; background: <?= $typeColor ?>20; color: <?= $typeColor ?>; font-size: 12px; margin-bottom: 4px;">
                                        <?= htmlspecialchars($typeLabel) ?>
                                    </span>
                                    <div style="font-size: 14px; font-weight: 600; color: #fff; margin-top: 4px;">
                                        <?php if ($type === 'discount'): ?>
                                            <?= number_format((float)($c['face_value'] ?? 0), 1) ?>折
                                        <?php else: ?>
                                            ¥<?= number_format((float)($c['face_value'] ?? 0), 0) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="price-display">
                                        <span class="currency">¥</span>
                                        <span class="amount"><?= number_format((float)($c['min_amount'] ?? 0), 0) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 12px; color: rgba(255,255,255,0.7);">
                                        <div style="display: flex; align-items: center; gap: 4px;">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12 6 12 12 16 14"/>
                                            </svg>
                                            <?= htmlspecialchars($c['valid_from'] ?? '') ?>
                                        </div>
                                        <div style="margin-left: 14px;">至 <?= htmlspecialchars($c['valid_to'] ?? '') ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="text-align: center;">
                                            <div style="font-size: 18px; font-weight: 600; color: #0ea5e9;"><?= (int)($c['total_quantity'] ?? 0) ?></div>
                                            <div style="font-size: 11px; color: rgba(255,255,255,0.5);">已发放</div>
                                        </div>
                                        <div style="width: 1px; height: 30px; background: rgba(255,255,255,0.1);"></div>
                                        <div style="text-align: center;">
                                            <div style="font-size: 18px; font-weight: 600; color: #22c55e;"><?= (int)($c['used_quantity'] ?? 0) ?></div>
                                            <div style="font-size: 11px; color: rgba(255,255,255,0.5);">已使用</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($c['is_active'])): ?>
                                        <span class="status-badge active">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                            启用
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge inactive">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                <line x1="18" y1="6" x2="6" y2="18"/>
                                                <line x1="6" y1="6" x2="18" y2="18"/>
                                            </svg>
                                            停用
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <a href="/<?= $adminPrefix ?>/finance/coupon/<?= (int)$c['id'] ?>" class="action-btn action-btn-secondary">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                            编辑
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div style="margin-top: 20px; padding: 16px; background: rgba(14, 165, 233, 0.1); border-radius: 8px; border: 1px solid rgba(14, 165, 233, 0.2);">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0ea5e9" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <span style="color: #0ea5e9; font-weight: 500;">功能说明</span>
            </div>
            <p style="margin: 0; font-size: 13px; color: rgba(255,255,255,0.7); line-height: 1.6;">
                优惠券支持折扣券、现金券、满减券三种类型。创建、发放、核销功能可在后续迭代中扩展接口与表单。
            </p>
        </div>
    </div>
</div>
