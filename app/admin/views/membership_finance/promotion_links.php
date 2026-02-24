<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="finance-card">
    <div class="finance-card-header">
        <h2>推广链接管理</h2>
        <a href="/<?= $adminPrefix ?>/finance/promotion-link/new" class="action-btn action-btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            新增推广
        </a>
    </div>
    <div class="finance-card-body">
        <?php if (empty($list)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🔗</div>
                <p class="empty-state-text">暂无推广链接，点击上方按钮新增</p>
                <a href="/<?= $adminPrefix ?>/finance/promotion-link/new" class="action-btn action-btn-primary">立即新增</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="finance-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>推广名称</th>
                            <th>推广码</th>
                            <th>链接</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $l): ?>
                            <tr>
                                <td><?= (int)$l['id'] ?></td>
                                <td>
                                    <span style="font-weight: 500; color: #fff;"><?= htmlspecialchars($l['name'] ?? '') ?></span>
                                </td>
                                <td>
                                    <code style="background: linear-gradient(135deg, rgba(14, 165, 233, 0.2), rgba(139, 92, 246, 0.2)); padding: 4px 10px; border-radius: 6px; font-size: 13px; color: #0ea5e9; font-family: 'SF Mono', monospace;">
                                        <?= htmlspecialchars($l['code'] ?? '') ?>
                                    </code>
                                </td>
                                <td>
                                    <?php if (!empty($l['link_url'])): ?>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <code style="background: rgba(255,255,255,0.08); padding: 4px 8px; border-radius: 4px; font-size: 11px; color: rgba(255,255,255,0.7); max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?= htmlspecialchars($l['link_url']) ?>
                                            </code>
                                            <button type="button" class="action-btn action-btn-secondary" style="padding: 4px 8px; font-size: 11px;" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($l['link_url']) ?>').then(() => alert('链接已复制'));">
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                                </svg>
                                                复制
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: rgba(255,255,255,0.3);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($l['created_at'])): ?>
                                        <div style="font-size: 13px; color: rgba(255,255,255,0.8);"><?= date('Y-m-d', strtotime($l['created_at'])) ?></div>
                                        <div style="font-size: 11px; color: rgba(255,255,255,0.5);"><?= date('H:i', strtotime($l['created_at'])) ?></div>
                                    <?php else: ?>
                                        <span style="color: rgba(255,255,255,0.3);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <a href="/<?= $adminPrefix ?>/finance/promotion-link/<?= (int)$l['id'] ?>" class="action-btn action-btn-secondary">
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
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                </svg>
                <span style="color: #0ea5e9; font-weight: 500;">推广链接功能</span>
            </div>
            <p style="margin: 0; font-size: 13px; color: rgba(255,255,255,0.7); line-height: 1.6;">
                生成带参数的推广链接，追踪推广效果（点击量、注册量、转化率等）可在后续迭代中扩展。
            </p>
        </div>
    </div>
</div>
