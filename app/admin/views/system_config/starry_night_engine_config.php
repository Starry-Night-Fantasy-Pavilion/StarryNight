<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<?php
$versionNames = [
    'basic' => '基础版',
    'standard' => '标准版',
    'premium' => '高级版',
    'enterprise' => '企业版'
];
?>
<div class="dashboard-section">
    <div class="section-header">
        <h2>星夜创作引擎权限配置</h2>
        <a href="/<?= trim((string)get_env('ADMIN_PATH', 'admin'), '/') ?>/system/starry-night-engine/new" class="btn-primary">新增配置</a>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>引擎版本</th>
                    <th>会员等级</th>
                    <th>状态</th>
                    <th>描述</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($permissions)): ?>
                    <tr>
                        <td colspan="6" class="sysconfig-empty-state">
                            暂无配置，请点击"新增配置"添加
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($permissions as $perm): ?>
                        <tr>
                            <td><?= htmlspecialchars($perm['id']) ?></td>
                            <td>
                                <span class="badge badge-info">
                                    <?= htmlspecialchars($versionNames[$perm['engine_version']] ?? $perm['engine_version']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($perm['membership_level_id']): ?>
                                    <?php
                                    $levelName = '未知';
                                    foreach ($membership_levels as $level) {
                                        if ($level['id'] == $perm['membership_level_id']) {
                                            $levelName = $level['name'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($levelName);
                                    ?>
                                <?php else: ?>
                                    <span class="badge badge-secondary">非会员</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($perm['is_enabled']): ?>
                                    <span class="badge badge-success">启用</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">禁用</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($perm['description'] ?? '') ?></td>
                            <td>
                                <a href="/<?= trim((string)get_env('ADMIN_PATH', 'admin'), '/') ?>/system/starry-night-engine/edit/<?= $perm['id'] ?>" class="btn-sm btn-primary">编辑</a>
                                <form method="POST" class="sysconfig-inline-form" onsubmit="return confirm('确定要删除此配置吗？');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $perm['id'] ?>">
                                    <button type="submit" class="btn-sm btn-danger">删除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="info-box sysconfig-info-box">
        <h3>说明</h3>
        <ul class="sysconfig-info-list">
            <li>可以为不同会员等级配置不同版本的星夜创作引擎</li>
            <li>如果用户有自定义配置，将优先使用用户的自定义配置</li>
            <li>如果没有自定义配置，则使用后台配置</li>
            <li>非会员用户（membership_level_id为NULL）表示普通用户</li>
            <li>同一版本可以为多个会员等级配置，但建议每个等级只配置一个版本</li>
        </ul>
    </div>
</div>
