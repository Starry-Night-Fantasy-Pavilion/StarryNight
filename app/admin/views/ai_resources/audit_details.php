<?php
/** @var array $item */
?>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="review-container">
    <div class="dashboard-header-v2 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="dashboard-title-v2">资源审核工作台</h1>
            <p class="dashboard-subtitle-v2">审核 ID: #<?= (int)$item['id'] ?> | 资源类型: <?= htmlspecialchars($item['resource_type'] ?? '') ?></p>
        </div>
        <div class="dashboard-actions-v2">
            <?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
            <a href="/<?= $adminPrefix ?>/ai/audits" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="bi bi-arrow-left me-2"></i>返回列表
            </a>
        </div>
    </div>

    <div class="review-workspace">
        <!-- 资源详情查看器 -->
        <div class="content-viewer">
            <div class="mb-5">
                <div class="d-flex align-items-center mb-3">
                    <span class="content-type-tag me-3"><?= htmlspecialchars($item['resource_type'] ?? '') ?></span>
                    <h2 class="text-white mb-0">资源 ID: <?= htmlspecialchars($item['resource_id'] ?? '') ?></h2>
                </div>
                <hr class="border-white border-opacity-10">
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="panel-card bg-white bg-opacity-5 border-white border-opacity-5">
                        <div class="text-muted small mb-1">提交人</div>
                        <div class="text-white fs-5"><?= htmlspecialchars($item['submitter_name'] ?? '-') ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="panel-card bg-white bg-opacity-5 border-white border-opacity-5 text-center">
                        <div class="text-muted small mb-1">期望公开</div>
                        <div class="text-white fs-5"><?= ((int)($item['desired_public'] ?? 0) === 1) ? '<i class="bi bi-eye text-success me-1"></i> 是' : '<i class="bi bi-eye-slash text-muted me-1"></i> 否' ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="panel-card bg-white bg-opacity-5 border-white border-opacity-5 text-center">
                        <div class="text-muted small mb-1">期望定价</div>
                        <div class="text-primary fs-5 fw-bold"><?= htmlspecialchars((string)($item['desired_price_coin'] ?? '0')) ?> <span class="small">星币</span></div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <h5 class="text-white mb-3"><i class="bi bi-database-fill-gear me-2 text-primary"></i>资源元数据 (Metadata)</h5>
                <pre class="bg-black bg-opacity-50 text-info p-4 rounded-4 border border-white border-opacity-5" style="font-size: 0.9rem; white-space: pre-wrap;"><code><?= htmlspecialchars($item['metadata_json'] ?? '{}') ?></code></pre>
            </div>
        </div>

        <!-- 审核面板 -->
        <div class="review-panel">
            <!-- 审核操作 -->
            <div class="panel-card">
                <h5 class="text-white mb-4"><i class="bi bi-shield-check me-2 text-primary"></i>审核决策</h5>
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">审核备注</label>
                        <textarea name="comment" class="form-control bg-white bg-opacity-5 border-white border-opacity-10 text-white rounded-4" rows="3" placeholder="输入审核意见，将反馈给提交者..."></textarea>
                    </div>
                    <div class="action-btn-group">
                        <button type="submit" name="action" value="approve" class="btn-review btn-review-approve">
                            <i class="bi bi-check-circle-fill"></i> 审核通过
                        </button>
                        <button type="submit" name="action" value="reject" class="btn-review btn-review-reject">
                            <i class="bi bi-x-circle-fill"></i> 拒绝申请
                        </button>
                        <button type="submit" name="action" value="comment" class="btn-review btn-review-modify">
                            <i class="bi bi-chat-dots"></i> 仅记录备注
                        </button>
                    </div>
                </form>
            </div>

            <!-- 状态信息 -->
            <div class="panel-card">
                <h5 class="text-white mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>当前状态</h5>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-muted">审核状态</span>
                    <?php
                    $statusConfig = [
                        'pending' => ['class' => 'bg-warning bg-opacity-10 text-warning border-warning', 'text' => '待审核'],
                        'approved' => ['class' => 'bg-success bg-opacity-10 text-success border-success', 'text' => '已通过'],
                        'rejected' => ['class' => 'bg-danger bg-opacity-10 text-danger border-danger', 'text' => '已拒绝']
                    ];
                    $status = $statusConfig[$item['status']] ?? ['class' => 'bg-secondary bg-opacity-10 text-secondary border-secondary', 'text' => $item['status']];
                    ?>
                    <span class="badge border border-opacity-25 <?= $status['class'] ?>"><?= $status['text'] ?></span>
                </div>
            </div>

            <!-- 审核日志 -->
            <div class="panel-card flex-grow-1 overflow-hidden d-flex flex-column">
                <h5 class="text-white mb-4"><i class="bi bi-clock-history me-2 text-primary"></i>审核日志</h5>
                <div class="overflow-auto pe-2" style="max-height: 400px;">
                    <?php foreach (($item['logs'] ?? []) as $log): ?>
                    <div class="mb-4 ps-3 border-start border-white border-opacity-10 position-relative">
                        <div class="position-absolute start-0 top-0 translate-middle-x bg-primary rounded-circle" style="width: 8px; height: 8px; margin-left: -1px;"></div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-bold text-white small"><?= htmlspecialchars($log['reviewer_name'] ?: '系统') ?></span>
                            <span class="text-muted" style="font-size: 0.75rem;"><?= date('m-d H:i', strtotime($log['created_at'])) ?></span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-white bg-opacity-5 text-white-50 border border-white border-opacity-10" style="font-size: 0.65rem;"><?= htmlspecialchars($log['action'] ?? '') ?></span>
                        </div>
                        <div class="small text-white-50"><?= htmlspecialchars($log['comment'] ?: '无备注') ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($item['logs'])): ?>
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-journal-x d-block fs-3 mb-2 opacity-25"></i>
                        暂无审核记录
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
