<div class="review-container">
    <div class="dashboard-header-v2 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="dashboard-title-v2">审查流程配置</h1>
            <p class="dashboard-subtitle-v2">定义不同业务模块的合规性校验路径</p>
        </div>
        <div class="dashboard-actions-v2">
            <a href="/<?= $adminPrefix ?>/content-review" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="bi bi-arrow-left me-2"></i>返回队列
            </a>
        </div>
    </div>

    <div class="row">
        <?php foreach ($configs as $config): ?>
        <div class="col-xl-6 mb-5">
            <div class="panel-card dashboard-card-v2 h-100 d-flex flex-column" style="display: flex; min-height: auto;">
                <div class="d-flex justify-content-between align-items-center mb-4 position-relative z-2 w-100">
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
                    <div class="d-flex align-items-center">
                        <div class="card-icon-v2 bg-coin me-3" style="width: 48px; height: 48px; font-size: 20px;">
                            <i class="bi <?= $icon ?>"></i>
                        </div>
                        <h4 class="text-white mb-0"><?= htmlspecialchars($name) ?></h4>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" disabled <?= $config['is_enabled'] ? 'checked' : '' ?>>
                    </div>
                </div>

                <form method="POST" class="flex-grow-1 d-flex flex-column position-relative z-2 w-100">
                    <input type="hidden" name="content_type" value="<?= htmlspecialchars($config['content_type']) ?>">
                    
                    <div class="config-flow py-3 flex-grow-1">
                        <!-- 开始节点 -->
                        <div class="flow-node text-center py-2 dashboard-card-v2" style="display: block; min-height: auto; border-color: rgba(94, 234, 212, 0.3); background: rgba(94, 234, 212, 0.08);">
                            <span class="small fw-bold text-uppercase position-relative z-2" style="color: #5eead4;">内容提交入口</span>
                        </div>
                        
                        <div class="flow-connector"></div>

                        <?php $steps = json_decode($config['flow_steps_json'], true) ?: []; ?>
                        
                        <!-- 机器审查节点 -->
                        <div class="flow-node dashboard-card-v2" style="display: block; min-height: auto;">
                            <div class="form-check form-switch d-flex align-items-center justify-content-between p-0 position-relative z-2">
                                <label class="form-check-label text-white d-flex align-items-center" for="step_machine_<?= $config['id'] ?>">
                                    <i class="bi bi-cpu me-2" style="color: #5eead4;"></i> 机器智能审查
                                </label>
                                <input class="form-check-input ms-0" type="checkbox" name="steps[]" value="machine" id="step_machine_<?= $config['id'] ?>" <?= in_array('machine', $steps) ? 'checked' : '' ?>>
                            </div>
                            <div class="small text-muted mt-2 ps-4 position-relative z-2">自动检测敏感词、AI 生成痕迹及合规性评分</div>
                        </div>

                        <div class="flow-connector"></div>

                        <!-- 人工审查节点 -->
                        <div class="flow-node dashboard-card-v2" style="display: block; min-height: auto;">
                            <div class="form-check form-switch d-flex align-items-center justify-content-between p-0 position-relative z-2">
                                <label class="form-check-label text-white d-flex align-items-center" for="step_human_<?= $config['id'] ?>">
                                    <i class="bi bi-person-badge me-2" style="color: #5eead4;"></i> 人工终审环节
                                </label>
                                <input class="form-check-input ms-0" type="checkbox" name="steps[]" value="human" id="step_human_<?= $config['id'] ?>" <?= in_array('human', $steps) ? 'checked' : '' ?>>
                            </div>
                            <div class="small text-muted mt-2 ps-4 position-relative z-2">由管理员进行最终合规性判定与意见反馈</div>
                        </div>

                        <div class="flow-connector"></div>

                        <!-- 结束节点 -->
                        <div class="flow-node text-center py-2 dashboard-card-v2" style="display: block; min-height: auto; border-color: rgba(16, 185, 129, 0.3); background: rgba(16, 185, 129, 0.08);">
                            <span class="text-success small fw-bold text-uppercase position-relative z-2">发布 / 归档</span>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top border-white border-opacity-5">
                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" name="is_enabled" id="enabled_<?= $config['id'] ?>" <?= $config['is_enabled'] ? 'checked' : '' ?>>
                            <label class="form-check-label text-secondary" for="enabled_<?= $config['id'] ?>">激活此业务模块的审查流程</label>
                        </div>
                        <button type="submit" class="btn w-100 py-2 rounded-pill" style="background: linear-gradient(135deg, rgba(94, 234, 212, 0.2), rgba(94, 234, 212, 0.1)); color: #5eead4; border: 1px solid rgba(94, 234, 212, 0.3);">
                            <i class="bi bi-shield-lock me-2"></i>更新流程配置
                        </button>
                    </div>
                </form>
                
                <div class="mt-3 text-center position-relative z-2 w-100">
                    <span class="text-muted" style="font-size: 0.7rem;">最后同步时间: <?= $config['updated_at'] ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="col-xl-6 mb-5">
            <div class="panel-card dashboard-card-v2 h-100 border-dashed d-flex flex-column justify-content-center align-items-center py-5" style="display: flex; min-height: auto; background: rgba(255,255,255,0.02);">
                <div class="review-stat-icon bg-white bg-opacity-5 text-muted mb-3 position-relative z-2" style="width: 60px; height: 60px; font-size: 30px; border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-plus-lg"></i>
                </div>
                <h5 class="text-white-50 position-relative z-2">扩展新业务模块</h5>
                <p class="small text-muted px-4 text-center position-relative z-2">系统检测到新插件或模块时将自动在此生成默认配置</p>
                <button class="btn btn-outline-secondary btn-sm rounded-pill px-4 opacity-50 position-relative z-2" disabled>手动添加 (开发中)</button>
            </div>
        </div>
    </div>
</div>

<style>
.border-dashed {
    border-style: dashed !important;
    border-width: 2px !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}
</style>
