<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="finance-card">
    <div class="finance-card-header">
        <h2>会员等级配置</h2>
        <a href="/<?= $adminPrefix ?>/finance/membership-level/new" class="action-btn action-btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            新增等级
        </a>
    </div>
    <div class="finance-card-body">
        <?php if (empty($levels)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">⭐</div>
                <p class="empty-state-text">暂无会员等级，点击上方按钮新增</p>
                <a href="/<?= $adminPrefix ?>/finance/membership-level/new" class="action-btn action-btn-primary">立即新增</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="finance-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>等级名称</th>
                            <th>月付/年付</th>
                            <th>星夜币折扣</th>
                            <th>功能权限/配额</th>
                            <th>状态</th>
                            <th>排序</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($levels as $l): ?>
                            <tr>
                                <td><?= (int)$l['id'] ?></td>
                                <td>
                                    <?php
                                    $levelClass = 'free';
                                    if (stripos($l['name'] ?? '', 'svip') !== false) {
                                        $levelClass = 'svip';
                                    } elseif (stripos($l['name'] ?? '', 'vip') !== false) {
                                        $levelClass = 'vip';
                                    }
                                    ?>
                                    <span class="level-badge <?= $levelClass ?>">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                                        </svg>
                                        <?= htmlspecialchars($l['name'] ?? '') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="price-display">
                                        <span class="currency">¥</span>
                                        <span class="amount"><?= number_format((float)($l['price_monthly'] ?? 0), 0) ?></span>
                                    </div>
                                    <span style="color: rgba(255,255,255,0.4); margin: 0 4px;">/</span>
                                    <div class="price-display">
                                        <span class="currency">¥</span>
                                        <span class="amount"><?= number_format((float)($l['price_yearly'] ?? 0), 0) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span style="color: #22c55e; font-weight: 600;"><?= number_format((float)($l['coin_discount_percent'] ?? 100), 0) ?>%</span>
                                </td>
                                <td style="max-width: 200px;">
                                    <?php
                                    $perm = $l['permissions_json'] ?? null;
                                    $quota = $l['quota_json'] ?? null;
                                    ?>
                                    <?php if ($perm || $quota): ?>
                                        <div style="font-size: 12px; line-height: 1.6;">
                                            <?php if ($perm): ?>
                                                <div style="color: rgba(255,255,255,0.7);">
                                                    <span style="color: #0ea5e9;">权限:</span>
                                                    <?= is_string($perm) ? $perm : json_encode($perm, JSON_UNESCAPED_UNICODE) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($quota): ?>
                                                <div style="color: rgba(255,255,255,0.7);">
                                                    <span style="color: #a855f7;">配额:</span>
                                                    <?= is_string($quota) ? $quota : json_encode($quota, JSON_UNESCAPED_UNICODE) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: rgba(255,255,255,0.3);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($l['is_active'])): ?>
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
                                    <span style="color: rgba(255,255,255,0.6);"><?= (int)($l['sort_order'] ?? 0) ?></span>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <a href="/<?= $adminPrefix ?>/finance/membership-level/<?= (int)$l['id'] ?>" class="action-btn action-btn-secondary">
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
    </div>
</div>
