<?php
$adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '');
?>

<link rel="stylesheet" href="/static/frontend/views/css/consistency-check.css?v=<?= time() ?>">

<div class="consistency-check-container" data-admin-prefix="<?= htmlspecialchars($adminPrefix, ENT_QUOTES, 'UTF-8') ?>">
    <div class="page-header">
        <h1 class="page-title">一致性检�?/h1>
        <p class="page-description">执行内容一致性检查，检测与核心设定的冲�?/p>
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
                <a href="/<?= $adminPrefix ?>/consistency/check">一致性检�?/a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-reports') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/reports">检查报�?/a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-analytics') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/analytics">分析统计</a>
            </li>
        </ul>
    </div>

    <div class="check-content">
        <div class="check-form-section">
            <div class="section-header">
                <h2>执行一致性检�?/h2>
                <p>输入要检查的内容，系统将检测与核心设定的冲�?/p>
            </div>

            <form id="checkForm" class="check-form">
                <div class="form-group">
                    <label for="checkContent" class="form-label">检查内�?/label>
                    <textarea id="checkContent" name="content" class="form-textarea" rows="12" 
                              placeholder="请输入要检查的内容，可以是章节、对话、描述等..."></textarea>
                    <div class="form-help">
                        <span id="charCount">0</span> 字符 | 
                        <span id="wordCount">0</span> �?                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="checkType" class="form-label">检查类�?/label>
                        <select id="checkType" name="check_type" class="form-select">
                            <option value="full">全面检�?/option>
                            <option value="worldview">世界观检�?/option>
                            <option value="character">角色检�?/option>
                            <option value="event">事件检�?/option>
                            <option value="rule">规则检�?/option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="checkScope" class="form-label">检查范�?/label>
                        <select id="checkScope" name="check_scope" class="form-select">
                            <option value="all">所有核心设�?/option>
                            <option value="active">仅激活设�?/option>
                            <option value="recent">最�?0天设�?/option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sensitivity" class="form-label">敏感�?/label>
                        <div class="range-container">
                            <input type="range" id="sensitivity" name="sensitivity" class="form-range" 
                                   min="0.1" max="1.0" step="0.1" value="0.7">
                            <span class="range-value">0.7</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="autoFix" name="auto_fix">
                        <span class="checkbox-text">自动生成修复建议</span>
                    </label>
                </div>

                <?php if (!empty($captchaHtml ?? '')): ?>
                <div class="form-group">
                    <label class="form-label">安全验证<span style="color:#f97373;"> *</span></label>
                    <div class="captcha-inner">
                        <?= $captchaHtml ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="checkBtn">
                        <i class="icon">🔍</i> 开始检�?                    </button>
                    <button type="button" class="btn btn-secondary" id="btnLoadExample">
                        <i class="icon">📄</i> 加载示例
                    </button>
                    <button type="reset" class="btn btn-outline">
                        <i class="icon">🔄</i> 清空
                    </button>
                </div>
            </form>
        </div>

        <div class="check-result-section is-hidden" id="resultSection">
            <div class="section-header">
                <h2>检查结�?/h2>
                <p>一致性检查的详细结果和冲突分�?/p>
            </div>

            <div class="result-summary">
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="summary-icon status-success">
                            <i class="icon">�?/i>
                        </div>
                        <div class="summary-content">
                            <h3>整体状�?/h3>
                            <p class="summary-value">通过</p>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="icon">⚠️</i>
                        </div>
                        <div class="summary-content">
                            <h3>发现冲突</h3>
                            <p class="summary-value">0 �?/p>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="icon">🎯</i>
                        </div>
                        <div class="summary-content">
                            <h3>相似�?/h3>
                            <p class="summary-value">0%</p>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="icon">�?/i>
                        </div>
                        <div class="summary-content">
                            <h3>检查耗时</h3>
                            <p class="summary-value">0s</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="conflicts-list" id="conflictsList">
                <div class="no-conflicts">
                    <p>未发现冲突，内容与核心设定一致！</p>
                </div>
            </div>

            <div class="result-actions">
                <button class="btn btn-primary" id="btnSaveReport">
                    <i class="icon">💾</i> 保存报告
                </button>
                <button class="btn btn-secondary" id="btnExportResult">
                    <i class="icon">📥</i> 导出结果
                </button>
                <button class="btn btn-outline" id="btnRetryCheck">
                    <i class="icon">🔄</i> 重新检�?                </button>
            </div>
        </div>

        <div class="recent-checks-section">
            <div class="section-header">
                <h2>最近检查记�?/h2>
                <p>查看最近执行的一致性检查记�?/p>
            </div>

            <div class="checks-list">
                <?php if (empty($recentChecks)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🔍</div>
                    <h3>暂无检查记�?/h3>
                    <p>执行一致性检查后，记录将显示在这�?/p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="/static/frontend/views/js/consistency-check.js?v=<?= time() ?>"></script>
