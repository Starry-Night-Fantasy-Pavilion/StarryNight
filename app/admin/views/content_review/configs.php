<div class="dashboard-v2">
    <div class="dashboard-header-v2 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="dashboard-title-v2">审查流程配置</h1>
            <p class="dashboard-subtitle-v2">配置不同内容类型的审查流程（如机审、人审）</p>
        </div>
        <div class="dashboard-actions-v2">
            <a href="/<?= $adminPrefix ?>/content-review" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>返回队列</a>
        </div>
    </div>

    <div class="row">
        <?php foreach ($configs as $config): ?>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <?php
                    $typeIcons = [
                        'knowledge_base' => 'bi-book',
                        'ai_generated' => 'bi-robot',
                        'community' => 'bi-people',
                        'plugin' => 'bi-puzzle'
                    ];
                    $typeNames = [
                        'knowledge_base' => '知识库资料',
                        'ai_generated' => 'AI 生成内容',
                        'community' => '社区内容',
                        'plugin' => '插件内容'
                    ];
                    $icon = $typeIcons[$config['content_type']] ?? 'bi-file-text';
                    $name = $typeNames[$config['content_type']] ?? $config['content_type'];
                    ?>
                    <h5 class="card-title mb-0 d-flex align-items-center">
                        <i class="bi <?= $icon ?> me-2 text-primary"></i>
                        <?= htmlspecialchars($name) ?>
                    </h5>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" disabled <?= $config['is_enabled'] ? 'checked' : '' ?>>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="content_type" value="<?= htmlspecialchars($config['content_type']) ?>">
                        
                        <div class="mb-4 bg-light p-3 rounded">
                            <label class="form-label fw-medium mb-3"><i class="bi bi-diagram-3 me-2"></i>审查步骤</label>
                            <?php
                            $steps = json_decode($config['flow_steps_json'], true) ?: [];
                            ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="steps[]" value="machine" id="step_machine_<?= $config['id'] ?>" <?= in_array('machine', $steps) ? 'checked' : '' ?>>
                                <label class="form-check-label d-flex align-items-center" for="step_machine_<?= $config['id'] ?>">
                                    机器审查 <span class="badge bg-secondary ms-2">AI/敏感词检测</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="steps[]" value="human" id="step_human_<?= $config['id'] ?>" <?= in_array('human', $steps) ? 'checked' : '' ?>>
                                <label class="form-check-label d-flex align-items-center" for="step_human_<?= $config['id'] ?>">
                                    人工审查 <span class="badge bg-secondary ms-2">管理员审核</span>
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_enabled" id="enabled_<?= $config['id'] ?>" <?= $config['is_enabled'] ? 'checked' : '' ?>>
                                <label class="form-check-label fw-medium" for="enabled_<?= $config['id'] ?>">启用此类型的审查</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save me-1"></i>保存配置</button>
                    </form>
                </div>
                <div class="card-footer small text-muted text-center">
                    <i class="bi bi-clock-history me-1"></i>最后更新: <?= $config['updated_at'] ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="col-md-6 mb-4">
            <div class="card h-100 border-dashed bg-light">
                <div class="card-body d-flex flex-column justify-content-center align-items-center text-muted py-5">
                    <i class="bi bi-plus-circle fs-1 mb-3 text-secondary"></i>
                    <h5 class="fw-medium text-dark">添加新内容类型</h5>
                    <p class="small text-center mb-4">为新的业务模块配置审查流程</p>
                    <button class="btn btn-outline-secondary px-4" disabled>即将推出</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-dashed {
    border-style: dashed !important;
    border-width: 2px !important;
    border-color: #dee2e6 !important;
}
</style>
