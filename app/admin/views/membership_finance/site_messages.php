<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="finance-card">
    <div class="finance-card-header">
        <h2>站内信管理</h2>
        <a href="/<?= $adminPrefix ?>/finance/site-message/new" class="action-btn action-btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            新增消息
        </a>
    </div>
    <div class="finance-card-body">
        <?php if (empty($list)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">✉️</div>
                <p class="empty-state-text">暂无站内信，点击上方按钮新增</p>
                <a href="/<?= $adminPrefix ?>/finance/site-message/new" class="action-btn action-btn-primary">立即新增</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="finance-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>标题</th>
                            <th>目标</th>
                            <th>发送状态</th>
                            <th>发送时间</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $m): ?>
                            <tr>
                                <td><?= (int)$m['id'] ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="width: 8px; height: 8px; border-radius: 50%; background: <?= empty($m['sent_at']) ? '#f59e0b' : '#22c55e' ?>;"></div>
                                        <span style="font-weight: 500; color: #fff;"><?= htmlspecialchars($m['title'] ?? '') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $target = $m['target_type'] ?? '';
                                    $targetConfig = [
                                        'all' => ['label' => '全部用户', 'color' => '#0ea5e9', 'icon' => '👥'],
                                        'vip' => ['label' => 'VIP用户', 'color' => '#fbbf24', 'icon' => '⭐'],
                                        'new' => ['label' => '新用户', 'color' => '#22c55e', 'icon' => '🆕'],
                                        'single' => ['label' => '单个用户', 'color' => '#a855f7', 'icon' => '👤'],
                                    ];
                                    $config = $targetConfig[$target] ?? ['label' => $target, 'color' => '#8b5cf6', 'icon' => '📌'];
                                    ?>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 4px; background: <?= $config['color'] ?>20; color: <?= $config['color'] ?>; font-size: 12px;">
                                        <span><?= $config['icon'] ?></span>
                                        <?= htmlspecialchars($config['label']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($m['sent_at'])): ?>
                                        <span class="status-badge active">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                            已发送
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge pending">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12 6 12 12 16 14"/>
                                            </svg>
                                            待发送
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($m['sent_at'])): ?>
                                        <div style="font-size: 13px; color: rgba(255,255,255,0.8);"><?= date('Y-m-d', strtotime($m['sent_at'])) ?></div>
                                        <div style="font-size: 11px; color: rgba(255,255,255,0.5);"><?= date('H:i', strtotime($m['sent_at'])) ?></div>
                                    <?php else: ?>
                                        <span style="color: rgba(255,255,255,0.3);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($m['created_at'])): ?>
                                        <div style="font-size: 13px; color: rgba(255,255,255,0.8);"><?= date('Y-m-d', strtotime($m['created_at'])) ?></div>
                                        <div style="font-size: 11px; color: rgba(255,255,255,0.5);"><?= date('H:i', strtotime($m['created_at'])) ?></div>
                                    <?php else: ?>
                                        <span style="color: rgba(255,255,255,0.3);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <a href="/<?= $adminPrefix ?>/finance/site-message/<?= (int)$m['id'] ?>" class="action-btn action-btn-secondary">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                            查看
                                        </a>
                                        <?php if (empty($m['sent_at'])): ?>
                                            <a href="/<?= $adminPrefix ?>/finance/site-message/<?= (int)$m['id'] ?>/send" class="action-btn action-btn-primary">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <line x1="22" y1="2" x2="11" y2="13"/>
                                                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                                                </svg>
                                                发送
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div style="margin-top: 20px; padding: 16px; background: rgba(34, 197, 94, 0.1); border-radius: 8px; border: 1px solid rgba(34, 197, 94, 0.2);">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <span style="color: #22c55e; font-weight: 500;">站内信功能</span>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-top: 12px;">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: rgba(255,255,255,0.7);">
                    <span>👥</span> 全部用户群发
                </div>
                <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: rgba(255,255,255,0.7);">
                    <span>⭐</span> VIP用户群发
                </div>
                <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: rgba(255,255,255,0.7);">
                    <span>👤</span> 单个用户发送
                </div>
                <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: rgba(255,255,255,0.7);">
                    <span>📅</span> 定时发送
                </div>
            </div>
            <p style="margin: 12px 0 0 0; font-size: 13px; color: rgba(255,255,255,0.5); line-height: 1.6;">
                创建、发送、管理站内信，支持群发和单发功能，可在后续迭代中扩展更多功能。
            </p>
        </div>
    </div>
</div>
