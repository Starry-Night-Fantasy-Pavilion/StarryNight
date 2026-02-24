<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<?php
$versionNames = [
    'basic' => '基础版',
    'standard' => '标准版',
    'premium' => '高级版',
    'enterprise' => '企业版'
];
$isNew = $id === 'new';
$customConfig = $permission ? ($permission['custom_config'] ?? '{}') : '{}';
?>
<div class="dashboard-section">
    <div class="section-header">
        <h2><?= $isNew ? '新增配置' : '编辑配置' ?></h2>
        <a href="/<?= trim((string)get_env('ADMIN_PATH', 'admin'), '/') ?>/system/starry-night-engine" class="btn-secondary">返回</a>
    </div>

    <form method="POST" class="form-container">
        <input type="hidden" name="action" value="save">
        <?php if (!$isNew): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="engine_version">引擎版本 <span class="required">*</span></label>
            <select name="engine_version" id="engine_version" required>
                <option value="">请选择</option>
                <?php foreach ($versionNames as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value) ?>" 
                            <?= ($permission && $permission['engine_version'] === $value) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="membership_level_id">会员等级</label>
            <select name="membership_level_id" id="membership_level_id">
                <option value="">非会员（普通用户）</option>
                <?php foreach ($membership_levels as $level): ?>
                    <option value="<?= $level['id'] ?>" 
                            <?= ($permission && $permission['membership_level_id'] == $level['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($level['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="form-text">选择NULL表示非会员用户</small>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_enabled" value="1" 
                       <?= ($isNew || ($permission && $permission['is_enabled'])) ? 'checked' : '' ?>>
                启用此配置
            </label>
        </div>

        <div class="form-group">
            <label for="description">描述</label>
            <textarea name="description" id="description" rows="3" 
                      placeholder="请输入配置描述"><?= htmlspecialchars($permission['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="custom_config">自定义配置（JSON格式）</label>
            <textarea name="custom_config" id="custom_config" rows="10" 
                      placeholder='{"model": "gpt-4", "temperature": 0.7, ...}'><?= htmlspecialchars($customConfig) ?></textarea>
            <small class="form-text">可以配置模型、温度等参数，JSON格式</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">保存</button>
            <a href="/<?= trim((string)get_env('ADMIN_PATH', 'admin'), '/') ?>/system/starry-night-engine" class="btn-secondary">取消</a>
        </div>
    </form>

    <div class="info-box sysconfig-info-box">
        <h3>配置说明</h3>
        <ul class="sysconfig-info-list">
            <li><strong>引擎版本</strong>：选择要配置的星夜创作引擎版本</li>
            <li><strong>会员等级</strong>：选择可以使用此版本的会员等级，留空表示非会员用户</li>
            <li><strong>自定义配置</strong>：可以配置模型参数、温度等，格式为JSON</li>
            <li>如果用户在前端启用了自定义配置，将优先使用用户的配置</li>
            <li>如果用户没有自定义配置，则使用此处的后台配置</li>
        </ul>
    </div>
</div>

<script src="/static/admin/js/starry-night-engine-config-edit.js?v=<?= time() ?>"></script>
