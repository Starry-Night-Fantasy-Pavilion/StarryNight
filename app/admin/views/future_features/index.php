<div class="dashboard-section">
    <div class="dashboard-header">
        <div>
            <h1 class="dashboard-title">未来功能管理</h1>
            <p class="dashboard-subtitle">管理和配置星夜创作引擎的未来功能模块</p>
        </div>
    </div>

    <div class="dashboard-content-grid">
        <div class="dashboard-card">
            <div class="card-body">
                    <!-- 功能状态概览 -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-cogs"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">总功能数</span>
                                    <span class="info-box-number"><?php echo count($features); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">已启用</span>
                                    <span class="info-box-number"><?php echo count(array_filter($features, function($f) { return $f['enabled']; })); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-pause-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">已禁用</span>
                                    <span class="info-box-number"><?php echo count(array_filter($features, function($f) { return !$f['enabled']; })); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary"><i class="fas fa-chart-line"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">启用率</span>
                                    <span class="info-box-number"><?php 
                                        $total = count($features);
                                        $enabled = count(array_filter($features, function($f) { return $f['enabled']; }));
                                        echo $total > 0 ? round(($enabled / $total) * 100, 1) : 0; 
                                    ?>%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 功能列表 -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>功能名称</th>
                                    <th>描述</th>
                                    <th>状态</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($features as $key => $feature): ?>
                                <tr>
                                    <td>
                                        <strong><?php 
                                            switch ($key) {
                                                case 'feature_ai_agent_market':
                                                    echo 'AI智能体市场';
                                                    break;
                                                case 'feature_collaboration':
                                                    echo '多模态创作协作';
                                                    break;
                                                case 'feature_copyright_protection':
                                                    echo '版权保护与溯源';
                                                    break;
                                                case 'feature_recommendation_system':
                                                    echo '个性化推荐系统';
                                                    break;
                                                case 'feature_creation_contests':
                                                    echo 'AI创作大赛';
                                                    break;
                                                case 'feature_education_training':
                                                    echo '教育与培训模块';
                                                    break;
                                                default:
                                                    echo $key;
                                                    break;
                                            }
                                        ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($feature['description'] ?? ''); ?></td>
                                    <td>
                                        <?php if ($feature['enabled']): ?>
                                            <span class="badge badge-success">已启用</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">已禁用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" 
                                                    class="btn btn-sm <?php echo $feature['enabled'] ? 'btn-warning' : 'btn-success'; ?>"
                                                    onclick="toggleFeature('<?php echo $key; ?>', <?php echo $feature['enabled'] ? 'false' : 'true'; ?>)">
                                                <i class="fas <?php echo $feature['enabled'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                                                <?php echo $feature['enabled'] ? '禁用' : '启用'; ?>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info"
                                                    onclick="testFeature('<?php echo $key; ?>')">
                                                <i class="fas fa-plug"></i>
                                                测试连接
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-primary"
                                                    onclick="viewFeatureStats('<?php echo $key; ?>')">
                                                <i class="fas fa-chart-bar"></i>
                                                查看统计
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 批量操作 -->
                    <div class="mt-4">
                        <h5>批量操作</h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-success" onclick="enableAllFeatures()">
                                <i class="fas fa-check-circle"></i> 启用所有功能
                            </button>
                            <button type="button" class="btn btn-warning" onclick="disableAllFeatures()">
                                <i class="fas fa-pause-circle"></i> 禁用所有功能
                            </button>
                            <button type="button" class="btn btn-info" onclick="refreshFeatureStats()">
                                <i class="fas fa-sync-alt"></i> 刷新统计
                            </button>
                        </div>
                    </div>
            </div>
        </div>
    </div>
</div>

<!-- 功能统计模态框（保留结构，样式与JS由全局CSS/JS负责） -->
<div class="modal fade" id="featureStatsModal" tabindex="-1" role="dialog" aria-hidden="true"></div>