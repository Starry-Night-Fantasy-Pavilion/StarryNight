<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="finance-card">
    <div class="finance-card-header">
        <h2>星夜币消耗记录</h2>
    </div>
    <div class="finance-card-body">
        <!-- 筛选表单 -->
        <form method="GET" class="filter-form">
            <input type="text" name="user_id" class="form-control" placeholder="用户ID" value="<?= htmlspecialchars($_GET['user_id'] ?? '') ?>" style="width: 100px;">
            <input type="text" name="related_type" class="form-control" placeholder="功能类型" value="<?= htmlspecialchars($_GET['related_type'] ?? '') ?>" style="width: 120px;">
            <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>" title="开始日期">
            <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>" title="结束日期">
            <button type="submit" class="action-btn action-btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                筛选
            </button>
            <a href="/<?= $adminPrefix ?>/finance/coin-spend-records" class="action-btn action-btn-secondary">重置</a>
        </form>

        <?php if (empty($records)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">💸</div>
                <p class="empty-state-text">暂无消耗记录</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="finance-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户</th>
                            <th>功能/关联</th>
                            <th>消耗数量</th>
                            <th>成本</th>
                            <th>所属订单</th>
                            <th>时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $r): ?>
                            <tr>
                                <td><?= (int)$r['id'] ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #0ea5e9, #8b5cf6); display: flex; align-items: center; justify-content: center; font-size: 11px; color: #fff;">
                                            <?= mb_substr(htmlspecialchars($r['nickname'] ?? $r['username'] ?? 'U'), 0, 1) ?>
                                        </div>
                                        <div>
                                            <div style="font-size: 13px; color: #fff;"><?= htmlspecialchars($r['nickname'] ?? $r['username'] ?? '') ?></div>
                                            <div style="font-size: 11px; color: rgba(255,255,255,0.5);">ID: <?= (int)$r['user_id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 13px;">
                                        <span style="color: #0ea5e9; font-weight: 500;"><?= htmlspecialchars($r['related_type'] ?? '-') ?></span>
                                        <?php if (!empty($r['description'])): ?>
                                            <div style="color: rgba(255,255,255,0.5); font-size: 12px; margin-top: 2px;"><?= htmlspecialchars($r['description']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="color: #f59e0b; font-weight: 600; font-family: 'SF Mono', monospace;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: -1px; margin-right: 2px;">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        -<?= number_format((float)($r['amount'] ?? 0), 0) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (isset($r['cost']) && $r['cost'] !== null): ?>
                                        <span style="color: rgba(255,255,255,0.7); font-family: 'SF Mono', monospace;">¥<?= number_format((float)$r['cost'], 4) ?></span>
                                    <?php else: ?>
                                        <span style="color: rgba(255,255,255,0.3);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($r['related_id'])): ?>
                                        <code style="background: rgba(255,255,255,0.08); padding: 3px 6px; border-radius: 4px; font-size: 11px; color: rgba(255,255,255,0.7);">
                                            <?= htmlspecialchars($r['related_id']) ?>
                                        </code>
                                    <?php else: ?>
                                        <span style="color: rgba(255,255,255,0.3);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($r['created_at'])): ?>
                                        <div style="font-size: 13px; color: rgba(255,255,255,0.8);"><?= date('Y-m-d', strtotime($r['created_at'])) ?></div>
                                        <div style="font-size: 11px; color: rgba(255,255,255,0.5);"><?= date('H:i:s', strtotime($r['created_at'])) ?></div>
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
