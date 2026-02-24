<?php
$adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/');

$categoryLabelMap = [
    'apps' => '应用',
    'payment' => '支付',
    'sms' => '短信',
    'email' => '邮箱',
    'notification' => '第三方登录',
    'identity' => '身份认证',
    'verification' => '验证',
    'certification' => '实名认证',

    // 子分类（更精细的展示）
    'verification/basic' => '基础验证插件',
    'verification/thirdparty' => '云人机验证',
    
];

$selectedCategory = trim((string)($_GET['cat'] ?? ''), '/');

$categoryOptions = [];
foreach ($plugins as $p) {
    $key = (string)($p['category_path'] ?? '');
    if ($key === '') {
        $key = (string)($p['category'] ?? '未分类');
    }
    $categoryOptions[$key] = true;
}
$categoryOptions = array_keys($categoryOptions);
sort($categoryOptions, SORT_STRING);

$filteredPlugins = array_values(array_filter($plugins, function ($p) use ($selectedCategory) {
    if ($selectedCategory === '') {
        return true;
    }
    $key = (string)($p['category_path'] ?? '');
    if ($key === '') {
        $key = (string)($p['category'] ?? '未分类');
    }
    return $key === $selectedCategory;
}));

$installedPlugins = array_values(array_filter($filteredPlugins, function ($p) {
    return !empty($p['installed']);
}));
$uninstalledPlugins = array_values(array_filter($filteredPlugins, function ($p) {
    return empty($p['installed']);
}));

function group_plugins_by_category_path(array $plugins): array
{
    $groups = [];
    foreach ($plugins as $p) {
        $key = (string)($p['category_path'] ?? '');
        if ($key === '') {
            $key = (string)($p['category'] ?? '未分类');
        }
        if (!isset($groups[$key])) {
            $groups[$key] = [];
        }
        $groups[$key][] = $p;
    }
    ksort($groups, SORT_STRING);
    return $groups;
}

$installedGroups = group_plugins_by_category_path($installedPlugins);
$uninstalledGroups = group_plugins_by_category_path($uninstalledPlugins);

function plugin_badge(string $text, string $variant = ''): string
{
    $classes = 'badge';
    if ($variant !== '') {
        $classes .= ' ' . $variant;
    }
    return '<span class="' . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</span>';
}

function plugin_category_label(string $path, array $labelMap): string
{
    // 统一路径格式（兼容 Windows 反斜杠）
    $path = str_replace('\\', '/', $path);
    $path = trim($path, '/');

    // 优先匹配完整路径（如 verification/basic）
    if ($path !== '' && isset($labelMap[$path])) {
        return (string)$labelMap[$path];
    }

    $segments = array_values(array_filter(explode('/', $path), function ($s) {
        return $s !== '';
    }));
    if (count($segments) === 0) {
        return '未分类';
    }

    $top = (string)($segments[0] ?? '');
    $topLabel = $labelMap[$top] ?? $top;
    // 默认仍只展示顶级分类；需要更精细展示的子分类，用上面的完整路径映射覆盖。
    return $topLabel;
}

function plugin_card(array $p, string $adminPrefix, array $categoryLabelMap, string $selectedCategory = ''): void
{
    $name = (string)($p['name'] ?? '');
    $id = (string)($p['id'] ?? '');
    $version = (string)($p['version'] ?? '');
    $description = (string)($p['description'] ?? '');
    $categoryPath = (string)($p['category_path'] ?? ($p['category'] ?? ''));
    $categoryLabel = plugin_category_label($categoryPath, $categoryLabelMap);

    $installed = !empty($p['installed']);
    $status = (string)($p['status'] ?? 'disabled');

    $frontendEntry = (string)($p['frontend_entry'] ?? '');
    $adminEntry = (string)($p['admin_entry'] ?? '');
    $hasConfig = !empty($p['has_config'] ?? false);
    $isLegacy = !empty($p['legacy'] ?? false);

    ?>
    <div class="pm-item">
        <div class="pm-title">
            <div class="pm-title-left">
                <div class="pm-name"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="pm-tags">
                    <?= plugin_badge($id) ?>
                    <?php if ($version !== ''): ?>
                        <?= plugin_badge('v' . $version) ?>
                    <?php endif; ?>
                    <?php if ($categoryLabel !== ''): ?>
                        <?= plugin_badge($categoryLabel) ?>
                    <?php endif; ?>
                    <?php if ($installed): ?>
                        <?= plugin_badge('已安装', 'bg-success') ?>
                    <?php else: ?>
                        <?= plugin_badge('未安装', 'bg-danger') ?>
                    <?php endif; ?>
                    <?= plugin_badge($status === 'enabled' ? '已启用' : '未启用', $status === 'enabled' ? 'bg-success' : 'bg-danger') ?>
                </div>
            </div>

            <div class="pm-actions">
                <?php if (!$installed): ?>
                    <form method="post" action="/<?= htmlspecialchars($adminPrefix, ENT_QUOTES, 'UTF-8') ?>/plugins/install">
                        <input type="hidden" name="plugin" value="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($selectedCategory !== ''): ?>
                            <input type="hidden" name="redirect_cat" value="<?= htmlspecialchars($selectedCategory, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                        <button class="pm-btn pm-btn-primary" type="submit">安装并启用</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="/<?= htmlspecialchars($adminPrefix, ENT_QUOTES, 'UTF-8') ?>/plugins/toggle">
                        <input type="hidden" name="plugin" value="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($selectedCategory !== ''): ?>
                            <input type="hidden" name="redirect_cat" value="<?= htmlspecialchars($selectedCategory, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                        <button class="pm-btn <?= $status === 'enabled' ? 'pm-btn-danger' : 'pm-btn-primary' ?>" type="submit">
                            <?= $status === 'enabled' ? '禁用' : '启用' ?>
                        </button>
                    </form>
                    <?php if ($status !== 'enabled'): ?>
                        <form method="post" action="/<?= htmlspecialchars($adminPrefix, ENT_QUOTES, 'UTF-8') ?>/plugins/uninstall">
                            <input type="hidden" name="plugin" value="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ($selectedCategory !== ''): ?>
                                <input type="hidden" name="redirect_cat" value="<?= htmlspecialchars($selectedCategory, ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif; ?>
                            <button class="pm-btn" type="submit">卸载</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($frontendEntry !== ''): ?>
                    <a class="pm-btn" href="<?= htmlspecialchars($frontendEntry, ENT_QUOTES, 'UTF-8') ?>" target="_blank">用户端入口</a>
                <?php endif; ?>
                <?php if ($adminEntry !== ''): ?>
                    <button class="pm-btn open-plugin-modal" data-url="<?= htmlspecialchars($adminEntry, ENT_QUOTES, 'UTF-8') ?>" data-title="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">后台配置页</button>
                <?php endif; ?>
                <?php if ($hasConfig && $installed): ?>
                    <button class="pm-btn open-plugin-modal" data-url="/<?= htmlspecialchars($adminPrefix, ENT_QUOTES, 'UTF-8') ?>/plugins/config?plugin=<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" data-title="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">配置</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($description !== ''): ?>
            <div class="pm-desc"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>
    <?php
}
?>

<link rel="stylesheet" href="/static/admin/css/plugins.css?v=<?= time() ?>">

<div class="pm-page">
    <div class="card pm-header">
        <div class="card-header">
            <div class="pm-toolbar">
                <div class="pm-section-title">
                    <h2>插件管理</h2>
                </div>
                <div class="pm-filters">
                    <div class="pm-chips">
                        <?php foreach ($categoryOptions as $cat): ?>
                            <?php
                            $label = plugin_category_label($cat, $categoryLabelMap);
                            $href = '/' . $adminPrefix . '/plugins?cat=' . rawurlencode($cat);
                            ?>
                            <a class="pm-chip <?= $selectedCategory === $cat ? 'pm-chip-active' : '' ?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endforeach; ?>
                        <?php if ($selectedCategory !== ''): ?>
                            <a class="pm-btn" href="/<?= htmlspecialchars($adminPrefix, ENT_QUOTES, 'UTF-8') ?>/plugins">重置</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="pm-summary" style="margin-top: 10px;">
                安装会执行插件内置 SQL，并写入插件的 plugin.json（installed/status）。
            </div>
        </div>
    </div>

    <div class="pm-columns">
    <div class="card pm-col-card">
        <div class="card-header">
            <div class="pm-section-title">
                <h2>已安装（<?= count($installedPlugins) ?>）</h2>
            </div>
        </div>
        <div class="card-body pm-scroll">
            <?php if (count($installedPlugins) === 0): ?>
                <div class="pm-summary">当前筛选条件下没有已安装插件。</div>
            <?php else: ?>
                <div class="pm-groups">
                    <?php foreach ($installedGroups as $groupKey => $groupPlugins): ?>
                        <div class="pm-group">
                            <div class="pm-group-header">
                                <div class="pm-group-title"><?= htmlspecialchars(plugin_category_label($groupKey, $categoryLabelMap), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="pm-summary"><?= count($groupPlugins) ?> 个</div>
                            </div>
                            <div class="pm-group-body">
                                <?php foreach ($groupPlugins as $p): ?>
                                        <?php plugin_card($p, $adminPrefix, $categoryLabelMap, $selectedCategory); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card pm-col-card">
        <div class="card-header">
            <div class="pm-section-title">
                <h2>未安装（<?= count($uninstalledPlugins) ?>）</h2>
            </div>
        </div>
        <div class="card-body pm-scroll">
            <?php if (count($uninstalledPlugins) === 0): ?>
                <div class="pm-summary">当前筛选条件下没有未安装插件。</div>
            <?php else: ?>
                <div class="pm-groups">
                    <?php foreach ($uninstalledGroups as $groupKey => $groupPlugins): ?>
                        <div class="pm-group">
                            <div class="pm-group-header">
                                <div class="pm-group-title"><?= htmlspecialchars(plugin_category_label($groupKey, $categoryLabelMap), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="pm-summary"><?= count($groupPlugins) ?> 个</div>
                            </div>
                            <div class="pm-group-body">
                                <?php foreach ($groupPlugins as $p): ?>
                                        <?php plugin_card($p, $adminPrefix, $categoryLabelMap, $selectedCategory); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
    </div>

<script src="/static/admin/js/plugins.js?v=<?= time() ?>"></script>

<div id="plugin-config-modal" class="pm-modal-overlay">
    <div class="pm-modal">
        <div class="pm-modal-header">
            <h2 id="plugin-modal-title" class="pm-modal-title">插件配置</h2>
            <button id="plugin-modal-close" class="pm-modal-close">&times;</button>
        </div>
        <div class="pm-modal-body">
            <iframe id="plugin-modal-iframe" src="about:blank" class="pm-modal-iframe"></iframe>
        </div>
    </div>
</div>
