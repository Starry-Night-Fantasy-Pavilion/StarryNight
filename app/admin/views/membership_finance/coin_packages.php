<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="finance-card">
    <div class="finance-card-header">
        <h2>充值套餐管理</h2>
        <a href="/<?= $adminPrefix ?>/finance/coin-package/new" class="action-btn action-btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            新增套餐
        </a>
    </div>
    <div class="finance-card-body">
        <?php if (empty($packages)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">💰</div>
                <p class="empty-state-text">暂无充值套餐，点击上方按钮新增</p>
                <a href="/<?= $adminPrefix ?>/finance/coin-package/new" class="action-btn action-btn-primary">立即新增</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="finance-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>套餐名称</th>
                            <th>金额</th>
                            <th>赠送星夜币</th>
                            <th>有效期</th>
                            <th>销售状态</th>
                            <th>限时优惠</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($packages as $p): ?>
                            <tr>
                                <td><?= (int)$p['id'] ?></td>
                                <td>
                                    <span style="font-weight: 500; color: #fff;"><?= htmlspecialchars($p['name'] ?? '') ?></span>
                                </td>
                                <td>
                                    <div class="price-display">
                                        <span class="currency">¥</span>
                                        <span class="amount"><?= number_format((float)($p['amount'] ?? 0), 0) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span style="color: #fbbf24; font-weight: 600;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: -1px; margin-right: 2px;">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <?= number_format((int)($p['coin_amount'] ?? 0)) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php $validDays = (int)($p['valid_days'] ?? 0); ?>
                                    <span style="color: rgba(255,255,255,0.7);">
                                        <?= $validDays > 0 ? $validDays . ' 天' : '永久有效' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (($p['sale_status'] ?? '') === 'on_sale'): ?>
                                        <span class="status-badge active">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                            在售
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge inactive">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                <line x1="18" y1="6" x2="6" y2="18"/>
                                                <line x1="6" y1="6" x2="18" y2="18"/>
                                            </svg>
                                            下架
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($p['is_limited_offer'])): ?>
                                        <div style="font-size: 12px; color: #22c55e;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -1px; margin-right: 2px;">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12 6 12 12 16 14"/>
                                            </svg>
                                            <?= htmlspecialchars($p['offer_start_at'] ?? '') ?><br>
                                            <span style="margin-left: 14px;">至 <?= htmlspecialchars($p['offer_end_at'] ?? '') ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: rgba(255,255,255,0.3);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <a href="/<?= $adminPrefix ?>/finance/coin-package/<?= (int)$p['id'] ?>" class="action-btn action-btn-secondary">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                            编辑
                                        </a>
                                        <a href="/<?= $adminPrefix ?>/finance/coin-package/<?= (int)$p['id'] ?>/delete" 
                                           class="action-btn action-btn-danger"
                                           onclick="return confirm('确定要删除此套餐吗？');">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            </svg>
                                            删除
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
