<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="finance-card">
    <div class="finance-card-header">
        <h2>活动配置</h2>
        <a href="/<?= $adminPrefix ?>/finance/activity/new" class="action-btn action-btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            新增活动
        </a>
    </div>
    <div class="finance-card-body">
        <?php if (empty($list)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎉</div>
                <p class="empty-state-text">暂无活动配置，点击上方按钮新增</p>
                <a href="/<?= $adminPrefix ?>/finance/activity/new" class="action-btn action-btn-primary">立即新增</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="finance-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>活动名称</th>
                            <th>类型</th>
                            <th>活动时间</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $a): ?>
                            <tr>
                                <td><?= (int)$a['id'] ?></td>
                                <td>
                                    <span style="font-weight: 500; color: #fff;"><?= htmlspecialchars($a['title'] ?? '') ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $type = $a['activity_type'] ?? '';
                                    $typeConfig = [
                                        'first_charge' => ['label' => '首充优惠', 'color' => '#22c55e', 'icon' => '🎁'],
                                        'invite' => ['label' => '邀请奖励', 'color' => '#0ea5e9', 'icon' => '👥'],
                                        'holiday' => ['label' => '节日活动', 'color' => '#f59e0b', 'icon' => '🎊'],
                                        'limited' => ['label' => '限时活动', 'color' => '#ef4444', 'icon' => '⏰'],
                                    ];
                                    $config = $typeConfig[$type] ?? ['label' => $type, 'color' => '#8b5cf6', 'icon' => '📌'];
                                    ?>
                                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 6px; background: <?= $config['color'] ?>20; color: <?= $config['color'] ?>; font-size: 13px;">
                                        <span><?= $config['icon'] ?></span>
                                        <?= htmlspecialchars($config['label']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 12px; color: rgba(255,255,255,0.7);">
                                        <div style="display: flex; align-items: center; gap: 4px;">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12 6 12 12 16 14"/>
                                            </svg>
                                            <?= htmlspecialchars($a['start_at'] ?? '') ?>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 4px; margin-top: 4px;">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <line x1="15" y1="9" x2="9" y2="15"/>
                                                <line x1="9" y1="9" x2="15" y2="15"/>
                                            </svg>
                                            <?= htmlspecialchars($a['end_at'] ?? '') ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $now = time();
                                    $start = !empty($a['start_at']) ? strtotime($a['start_at']) : 0;
                                    $end = !empty($a['end_at']) ? strtotime($a['end_at']) : 0;
                                    
                                    if (!empty($a['is_active'])) {
                                        if ($end > 0 && $end < $now) {
                                            $statusClass = 'inactive';
                                            $statusText = '已结束';
                                        } elseif ($start > 0 && $start > $now) {
                                            $statusClass = 'pending';
                                            $statusText = '待开始';
                                        } else {
                                            $statusClass = 'active';
                                            $statusText = '进行中';
                                        }
                                    } else {
                                        $statusClass = 'inactive';
                                        $statusText = '已停用';
                                    }
                                    ?>
                                    <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($statusText) ?></span>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <a href="/<?= $adminPrefix ?>/finance/activity/<?= (int)$a['id'] ?>" class="action-btn action-btn-secondary">
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

        <div style="margin-top: 20px; padding: 16px; background: rgba(168, 85, 247, 0.1); border-radius: 8px; border: 1px solid rgba(168, 85, 247, 0.2);">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span style="color: #a855f7; font-weight: 500;">活动类型说明</span>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-top: 12px;">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: rgba(255,255,255,0.7);">
                    <span>🎁</span> 首充优惠
                </div>
                <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: rgba(255,255,255,0.7);">
                    <span>👥</span> 邀请奖励
                </div>
                <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: rgba(255,255,255,0.7);">
                    <span>🎊</span> 节日活动
                </div>
                <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: rgba(255,255,255,0.7);">
                    <span>⏰</span> 限时活动
                </div>
            </div>
            <p style="margin: 12px 0 0 0; font-size: 13px; color: rgba(255,255,255,0.5); line-height: 1.6;">
                活动规则与奖励配置可在后续迭代中扩展，支持多种优惠组合和触发条件。
            </p>
        </div>
    </div>
</div>
