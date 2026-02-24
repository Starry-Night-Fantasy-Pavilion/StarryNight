<div class="dashboard-v2">
    <div class="dashboard-header-v2">
        <h1 class="dashboard-title-v2">审查详情</h1>
        <p class="dashboard-subtitle-v2">内容 ID: #<?= $item['id'] ?> | 类型: <?= htmlspecialchars($item['content_type']) ?></p>
        <div class="dashboard-actions-v2">
            <a href="/<?= $adminPrefix ?>/content-review" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>返回队列</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <i class="bi bi-file-text me-2"></i>
                    <h5 class="card-title mb-0">内容详情</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="small text-muted text-uppercase fw-bold mb-2">标题</label>
                        <div class="fs-5 fw-medium"><?= htmlspecialchars($item['title'] ?: '无标题') ?></div>
                    </div>
                    <div class="mb-4">
                        <label class="small text-muted text-uppercase fw-bold mb-2">预览内容</label>
                        <div class="p-3 bg-light border rounded" style="line-height: 1.6;">
                            <?= nl2br(htmlspecialchars($item['content_preview'])) ?>
                        </div>
                    </div>
                    <?php if ($item['full_content_json']): ?>
                    <div class="mb-4">
                        <label class="small text-muted text-uppercase fw-bold mb-2">完整数据 (JSON)</label>
                        <pre class="bg-dark text-light p-3 rounded" style="max-height: 400px; overflow: auto; font-size: 0.875rem;"><code><?= htmlspecialchars(json_encode(json_decode($item['full_content_json']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></code></pre>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <i class="bi bi-clock-history me-2"></i>
                    <h5 class="card-title mb-0">审查日志</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($item['logs'] as $log): ?>
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle text-muted me-2"></i>
                                    <span class="fw-bold"><?= htmlspecialchars($log['reviewer_name'] ?: '系统') ?></span>
                                </div>
                                <span class="small text-muted"><i class="bi bi-calendar me-1"></i><?= $log['created_at'] ?></span>
                            </div>
                            <div class="ps-4">
                                <?php
                                $actionClass = [
                                    'approve' => 'bg-success',
                                    'reject' => 'bg-danger',
                                    'request_revision' => 'bg-warning text-dark'
                                ];
                                $actionBadge = $actionClass[$log['action']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $actionBadge ?> me-2 mb-1"><?= htmlspecialchars($log['action']) ?></span>
                                <span class="text-muted"><?= htmlspecialchars($log['comment'] ?: '无备注') ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($item['logs'])): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            暂无审查记录
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <i class="bi bi-shield-check me-2"></i>
                    <h5 class="card-title mb-0">审查操作</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-medium">审查意见</label>
                            <textarea name="comment" class="form-control" rows="4" placeholder="请输入审查意见（拒绝或要求修改时建议填写）..."></textarea>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="action" value="approve" class="btn btn-success">
                                <i class="bi bi-check-circle me-1"></i> 标记为合规 (通过)
                            </button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger">
                                <i class="bi bi-x-circle me-1"></i> 标记为不合规 (拒绝)
                            </button>
                            <button type="submit" name="action" value="request_revision" class="btn btn-warning">
                                <i class="bi bi-pencil-square me-1"></i> 要求修改
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <i class="bi bi-info-circle me-2"></i>
                    <h5 class="card-title mb-0">元数据</h5>
                </div>
                <div class="card-body small">
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                        <span class="text-muted"><i class="bi bi-person me-1"></i>提交者:</span>
                        <span class="fw-medium"><?= htmlspecialchars($item['submitter_name'] ?: '系统/游客') ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                        <span class="text-muted"><i class="bi bi-calendar-plus me-1"></i>提交时间:</span>
                        <span><?= $item['created_at'] ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                        <span class="text-muted"><i class="bi bi-diagram-3 me-1"></i>当前步骤:</span>
                        <span class="badge bg-info text-dark"><?= htmlspecialchars($item['current_step']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted"><i class="bi bi-link-45deg me-1"></i>关联 ID:</span>
                        <span class="font-monospace"><?= htmlspecialchars($item['content_id']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
