<?php
$adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
?>

<link rel="stylesheet" href="/static/frontend/views/css/consistency-config.css?v=<?= time() ?>">

<div class="consistency-check-container" data-admin-prefix="<?= htmlspecialchars($adminPrefix, ENT_QUOTES, 'UTF-8') ?>">
    <div class="page-header">
        <h1 class="page-title">一致性检查系统</h1>
        <p class="page-description">管理星夜创作引擎的一致性检查功能，确保创作内容的连贯性和一致性</p>
    </div>

    <div class="consistency-nav-tabs">
        <ul class="nav-tabs">
            <li class="nav-tab <?= ($currentPage === 'consistency-config') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/config">系统配置</a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-core-settings') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/core-settings">核心设定</a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-check') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/check">一致性检查</a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-reports') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/reports">检查报告</a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-analytics') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/analytics">分析统计</a>
            </li>
        </ul>
    </div>

    <div class="consistency-content">
        <div class="config-section">
            <div class="section-header">
                <h2>系统配置</h2>
                <p>配置一致性检查系统的基本参数和设置</p>
            </div>

            <form class="config-form" method="POST" action="/<?= $adminPrefix ?>/consistency/config">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="is_enabled" class="form-label">启用一致性检查</label>
                        <div class="switch-container">
                            <input type="checkbox" id="is_enabled" name="is_enabled" class="switch-input" <?= $config['is_enabled'] ? 'checked' : '' ?>>
                            <label for="is_enabled" class="switch-label"></label>
                        </div>
                        <p class="form-help">开启后将自动检查新内容与核心设定的一致性</p>
                    </div>

                    <div class="form-group">
                        <label for="vector_db_mode" class="form-label">向量数据库模式</label>
                        <select id="vector_db_mode" name="vector_db_mode" class="form-select">
                            <option value="single" <?= ($config['vector_db_mode'] ?? 'single') === 'single' ? 'selected' : '' ?>>单一数据库</option>
                            <option value="multi" <?= ($config['vector_db_mode'] ?? 'single') === 'multi' ? 'selected' : '' ?>>多数据库</option>
                        </select>
                        <p class="form-help">选择向量数据库的运行模式</p>
                    </div>

                    <div class="form-group">
                        <label for="primary_vector_db_id" class="form-label">主向量数据库</label>
                        <select id="primary_vector_db_id" name="primary_vector_db_id" class="form-select">
                            <option value="">请选择向量数据库</option>
                            <?php foreach ($vectorDbs as $db): ?>
                            <option value="<?= $db['id'] ?>" <?= ($config['primary_vector_db_id'] ?? '') == $db['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($db['name']) ?> (<?= htmlspecialchars($db['type']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="form-help">选择用于存储和检索向量的主数据库</p>
                    </div>

                    <div class="form-group">
                        <label for="embedding_model_id" class="form-label">嵌入模型</label>
                        <select id="embedding_model_id" name="embedding_model_id" class="form-select">
                            <option value="">请选择嵌入模型</option>
                            <?php foreach ($embeddingModels as $model): ?>
                            <option value="<?= $model['id'] ?>" <?= ($config['embedding_model_id'] ?? '') == $model['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($model['name']) ?> (<?= htmlspecialchars($model['provider']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="form-help">选择用于文本向量化的嵌入模型</p>
                    </div>

                    <div class="form-group">
                        <label for="check_frequency" class="form-label">检查频率</label>
                        <select id="check_frequency" name="check_frequency" class="form-select">
                            <option value="realtime" <?= ($config['check_frequency'] ?? 'manual') === 'realtime' ? 'selected' : '' ?>>实时检查</option>
                            <option value="scheduled" <?= ($config['check_frequency'] ?? 'manual') === 'scheduled' ? 'selected' : '' ?>>定时检查</option>
                            <option value="manual" <?= ($config['check_frequency'] ?? 'manual') === 'manual' ? 'selected' : '' ?>>手动检查</option>
                        </select>
                        <p class="form-help">设置一致性检查的执行频率</p>
                    </div>

                    <div class="form-group">
                        <label for="sensitivity_level" class="form-label">敏感度级别</label>
                        <div class="range-container">
                            <input type="range" id="sensitivity_level" name="sensitivity_level" class="form-range" 
                                   min="0.1" max="1.0" step="0.1" value="<?= $config['sensitivity_level'] ?? 0.7 ?>">
                            <span class="range-value"><?= $config['sensitivity_level'] ?? 0.7 ?></span>
                        </div>
                        <p class="form-help">调整冲突检测的敏感度，数值越高检测越严格</p>
                    </div>
                </div>

                <div class="form-section">
                    <h3>检查范围配置</h3>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="check_scope[worldview]" value="1" 
                                   <?= (isset($config['check_scope']['worldview']) && $config['check_scope']['worldview']) ? 'checked' : '' ?>>
                            <span class="checkbox-text">世界观设定</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="check_scope[character]" value="1"
                                   <?= (isset($config['check_scope']['character']) && $config['check_scope']['character']) ? 'checked' : '' ?>>
                            <span class="checkbox-text">角色设定</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="check_scope[event]" value="1"
                                   <?= (isset($config['check_scope']['event']) && $config['check_scope']['event']) ? 'checked' : '' ?>>
                            <span class="checkbox-text">事件设定</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="check_scope[rule]" value="1"
                                   <?= (isset($config['check_scope']['rule']) && $config['check_scope']['rule']) ? 'checked' : '' ?>>
                            <span class="checkbox-text">规则设定</span>
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h3>通知设置</h3>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="notification_settings[email]" value="1"
                                   <?= (isset($config['notification_settings']['email']) && $config['notification_settings']['email']) ? 'checked' : '' ?>>
                            <span class="checkbox-text">邮件通知</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="notification_settings[system]" value="1"
                                   <?= (isset($config['notification_settings']['system']) && $config['notification_settings']['system']) ? 'checked' : '' ?>>
                            <span class="checkbox-text">系统通知</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="notification_settings[auto_fix]" value="1"
                                   <?= (isset($config['notification_settings']['auto_fix']) && $config['notification_settings']['auto_fix']) ? 'checked' : '' ?>>
                            <span class="checkbox-text">自动修复建议</span>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">保存配置</button>
                    <button type="button" class="btn btn-secondary" id="btnTestConnection">测试连接</button>
                    <button type="reset" class="btn btn-outline">重置</button>
                </div>
            </form>
        </div>

        <div class="status-section">
            <div class="section-header">
                <h2>系统状态</h2>
                <p>查看一致性检查系统的运行状态和健康指标</p>
            </div>

            <div class="status-grid">
                <div class="status-card">
                    <div class="status-icon status-<?= $systemStatus['vector_db'] ? 'success' : 'error' ?>">
                        <?= icon('database') ?>
                    </div>
                    <div class="status-content">
                        <h3>向量数据库</h3>
                        <p class="status-text"><?= $systemStatus['vector_db'] ? '连接正常' : '连接失败' ?></p>
                    </div>
                </div>

                <div class="status-card">
                    <div class="status-icon status-<?= $systemStatus['embedding_model'] ? 'success' : 'error' ?>">
                        <?= icon('cpu') ?>
                    </div>
                    <div class="status-content">
                        <h3>嵌入模型</h3>
                        <p class="status-text"><?= $systemStatus['embedding_model'] ? '运行正常' : '模型异常' ?></p>
                    </div>
                </div>

                <div class="status-card">
                    <div class="status-icon status-<?= $systemStatus['core_settings'] ? 'success' : 'warning' ?>">
                        <?= icon('settings') ?>
                    </div>
                    <div class="status-content">
                        <h3>核心设定</h3>
                        <p class="status-text"><?= $systemStatus['core_settings_count'] ?? 0 ?> 条设定</p>
                    </div>
                </div>

                <div class="status-card">
                    <div class="status-icon status-<?= $systemStatus['recent_checks'] ? 'success' : 'warning' ?>">
                        <?= icon('check-circle') ?>
                    </div>
                    <div class="status-content">
                        <h3>最近检查</h3>
                        <p class="status-text"><?= $systemStatus['recent_checks_count'] ?? 0 ?> 次检查</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/static/frontend/views/js/consistency-config.js?v=<?= time() ?>"></script>