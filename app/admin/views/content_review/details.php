<div class="review-container">
    <div class="dashboard-header-v2 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="dashboard-title-v2">审查工作台</h1>
            <p class="dashboard-subtitle-v2">内容 ID: #<?= $item['id'] ?> | 类型: <?= htmlspecialchars($item['content_type']) ?></p>
        </div>
        <div class="dashboard-actions-v2">
            <a href="/<?= $adminPrefix ?>/content-review" class="btn btn-outline-secondary rounded-pill px-4" style="border-color: rgba(255,255,255,0.2); color: #fff;">
                <i class="bi bi-arrow-left me-2"></i>返回队列
            </a>
        </div>
    </div>

    <div class="review-workspace">
        <!-- 内容查看器 -->
        <div class="content-viewer dashboard-card-v2" style="display: block; min-height: auto;">
            <div class="mb-5 position-relative z-2">
                <div class="d-flex align-items-center mb-3">
                    <span class="content-type-tag me-3"><?= htmlspecialchars($item['content_type']) ?></span>
                    <h2 class="text-white mb-0"><?= htmlspecialchars($item['title'] ?: '无标题内容') ?></h2>
                </div>
                <hr class="border-white border-opacity-10">
            </div>

            <div class="content-body text-white-50 position-relative z-2" style="font-size: 1.1rem; line-height: 2;">
                <?= nl2br(htmlspecialchars($item['content_preview'])) ?>
            </div>

            <?php if ($item['full_content_json']): ?>
            <div class="mt-5 position-relative z-2">
                <h5 class="text-white mb-3"><i class="bi bi-code-slash me-2" style="color: #5eead4;"></i>完整数据结构</h5>
                <pre class="p-4 rounded-4 border" style="background: rgba(0,0,0,0.3); border-color: rgba(255,255,255,0.1) !important; font-size: 0.9rem; color: #5eead4;"><code><?= htmlspecialchars(json_encode(json_decode($item['full_content_json']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></code></pre>
            </div>
            <?php endif; ?>
        </div>

        <!-- 审查面板 -->
        <div class="review-panel">
            <!-- 操作卡片 -->
            <div class="panel-card dashboard-card-v2" style="display: block; min-height: auto;">
                <h5 class="text-white mb-4 position-relative z-2"><i class="bi bi-shield-check me-2" style="color: #5eead4;"></i>执行审查</h5>
                <form method="POST" class="position-relative z-2">
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">审核意见</label>
                        <textarea name="comment" class="form-control text-white rounded-4" rows="4" placeholder="在此输入您的审查意见或修改建议..." style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1);"></textarea>
                    </div>
                    <div class="action-btn-group">
                        <button type="submit" name="action" value="approve" class="btn-review btn-review-approve">
                            <i class="bi bi-check-circle-fill"></i> 批准发布
                        </button>
                        <button type="submit" name="action" value="request_revision" class="btn-review btn-review-modify">
                            <i class="bi bi-pencil-square"></i> 要求修改
                        </button>
                        <button type="submit" name="action" value="reject" class="btn-review btn-review-reject">
                            <i class="bi bi-x-circle-fill"></i> 拒绝并屏蔽
                        </button>
                    </div>
                </form>
            </div>

            <!-- 元数据卡片 -->
            <div class="panel-card dashboard-card-v2" style="display: block; min-height: auto;">
                <h5 class="text-white mb-4 position-relative z-2"><i class="bi bi-info-circle me-2" style="color: #5eead4;"></i>内容元数据</h5>
                <div class="space-y-3 position-relative z-2">
                    <div class="d-flex justify-content-between border-bottom border-white border-opacity-5 pb-2 mb-2">
                        <span class="text-muted">提交者</span>
                        <span class="text-white"><?= htmlspecialchars($item['submitter_name'] ?: '系统/游客') ?></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom border-white border-opacity-5 pb-2 mb-2">
                        <span class="text-muted">提交时间</span>
                        <span class="text-white"><?= $item['created_at'] ?></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom border-white border-opacity-5 pb-2 mb-2">
                        <span class="text-muted">当前环节</span>
                        <span class="badge" style="background: rgba(94, 234, 212, 0.1); color: #5eead4; border: 1px solid rgba(94, 234, 212, 0.2);"><?= htmlspecialchars($item['current_step']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">关联 ID</span>
                        <span class="font-monospace" style="color: #5eead4;"><?= htmlspecialchars($item['content_id']) ?></span>
                    </div>
                </div>
            </div>

            <!-- 历史日志 -->
            <div class="panel-card dashboard-card-v2 flex-grow-1 overflow-hidden d-flex flex-column" style="display: flex; min-height: auto;">
                <h5 class="text-white mb-4 position-relative z-2"><i class="bi bi-clock-history me-2" style="color: #5eead4;"></i>审查轨迹</h5>
                <div class="overflow-auto pe-2 position-relative z-2" style="max-height: 300px;">
                    <?php foreach ($item['logs'] as $log): ?>
                    <div class="mb-4 ps-3 border-start border-white border-opacity-10 position-relative">
                        <div class="position-absolute start-0 top-0 translate-middle-x rounded-circle" style="width: 8px; height: 8px; margin-left: -1px; background: #5eead4;"></div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-bold text-white small"><?= htmlspecialchars($log['reviewer_name'] ?: '系统') ?></span>
                            <span class="text-muted" style="font-size: 0.75rem;"><?= date('m-d H:i', strtotime($log['created_at'])) ?></span>
                        </div>
                        <div class="small text-white-50"><?= htmlspecialchars($log['comment'] ?: '无备注') ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($item['logs'])): ?>
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-journal-x d-block fs-3 mb-2 opacity-25"></i>
                        暂无历史记录
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
