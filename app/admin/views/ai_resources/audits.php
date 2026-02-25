<?php
/** @var array $data */
?>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="review-container">
    <div class="dashboard-header-v2">
        <h1 class="dashboard-title-v2">AI 资源审核</h1>
        <p class="dashboard-subtitle-v2">管理智能体、提示词及知识库的公开申请</p>
    </div>

    <!-- 筛选栏 -->
    <div class="card dashboard-card-v2 mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">审核状态</label>
                    <select class="form-select bg-transparent border-white border-opacity-10 text-white" name="status">
                        <option value="" class="bg-dark">全部状态</option>
                        <option value="pending" <?= (($_GET['status'] ?? '') === 'pending') ? 'selected' : '' ?> class="bg-dark">待审核</option>
                        <option value="approved" <?= (($_GET['status'] ?? '') === 'approved') ? 'selected' : '' ?> class="bg-dark">已通过</option>
                        <option value="rejected" <?= (($_GET['status'] ?? '') === 'rejected') ? 'selected' : '' ?> class="bg-dark">已拒绝</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">资源类型</label>
                    <select class="form-select bg-transparent border-white border-opacity-10 text-white" name="resource_type">
                        <option value="" class="bg-dark">全部类型</option>
                        <option value="knowledge_base" <?= (($_GET['resource_type'] ?? '') === 'knowledge_base') ? 'selected' : '' ?> class="bg-dark">知识库</option>
                        <option value="prompt" <?= (($_GET['resource_type'] ?? '') === 'prompt') ? 'selected' : '' ?> class="bg-dark">提示词</option>
                        <option value="template" <?= (($_GET['resource_type'] ?? '') === 'template') ? 'selected' : '' ?> class="bg-dark">模板</option>
                        <option value="agent" <?= (($_GET['resource_type'] ?? '') === 'agent') ? 'selected' : '' ?> class="bg-dark">智能体</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100 py-2" type="submit">
                        <i class="bi bi-search me-2"></i>执行筛选
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 资源网格 -->
    <div class="review-grid">
        <?php foreach (($data['items'] ?? []) as $it): ?>
        <div class="review-card">
            <div class="review-card-header">
                <span class="content-type-tag">
                    <?php
                    $typeNames = [
                        'knowledge_base' => '知识库',
                        'prompt' => '提示词',
                        'template' => '模板',
                        'agent' => '智能体'
                    ];
                    echo htmlspecialchars($typeNames[$it['resource_type']] ?? $it['resource_type']);
                    ?>
                </span>
                <span class="text-muted small">#<?= (int)$it['id'] ?></span>
            </div>
            <div class="review-card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="review-stat-icon bg-primary bg-opacity-10 text-primary me-3" style="width: 40px; height: 40px; font-size: 20px;">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <div class="text-white fw-bold">资源 ID: <?= htmlspecialchars($it['resource_id'] ?? '') ?></div>
                        <div class="small text-muted">提交人: <?= htmlspecialchars($it['submitter_name'] ?? '-') ?></div>
                    </div>
                </div>
                
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 rounded bg-white bg-opacity-5 text-center">
                            <div class="text-muted small">期望公开</div>
                            <div class="text-white"><?= ((int)($it['desired_public'] ?? 0) === 1) ? '是' : '否' ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded bg-white bg-opacity-5 text-center">
                            <div class="text-muted small">期望定价</div>
                            <div class="text-primary fw-bold"><?= htmlspecialchars((string)($it['desired_price_coin'] ?? '0')) ?> <span class="small">星币</span></div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mt-auto">
                    <div class="text-muted small">
                        <i class="bi bi-clock me-1"></i>
                        <?= date('m-d H:i', strtotime($it['created_at'])) ?>
                    </div>
                    <?php
                    $statusConfig = [
                        'pending' => ['class' => 'text-warning', 'text' => '待审核'],
                        'approved' => ['class' => 'text-success', 'text' => '已通过'],
                        'rejected' => ['class' => 'text-danger', 'text' => '已拒绝']
                    ];
                    $status = $statusConfig[$it['status']] ?? ['class' => 'text-secondary', 'text' => $it['status']];
                    ?>
                    <div class="<?= $status['class'] ?> small fw-bold">
                        <?= $status['text'] ?>
                    </div>
                </div>
            </div>
            <div class="review-card-footer">
                <?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
                <a href="/<?= $adminPrefix ?>/ai/audits/details/<?= (int)$it['id'] ?>" class="btn btn-primary w-100 rounded-pill">
                    查看详情并审核
                </a>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($data['items'])): ?>
        <div class="col-12 text-center py-5">
            <div class="opacity-25 mb-3">
                <i class="bi bi-shield-check" style="font-size: 5rem;"></i>
            </div>
            <h4 class="text-muted">暂无审核任务</h4>
        </div>
        <?php endif; ?>
    </div>
</div>
