<?php
// 该脚本用于手动修复旧版 SMTP 插件未能在数据库中正确注册的问题。
// 执行此脚本会将 SMTP 插件信息插入 admin_plugins 表，并将其状态设置为 'installed'。

// 假设此脚本从项目根目录执行
// 尝试加载框架的引导文件或自动加载器
$bootstrap_files = [
    __DIR__ . '/../app/bootstrap.php', // 常见的引导文件位置
    __DIR__ . '/../vendor/autoload.php'
];

$loaded = false;
foreach ($bootstrap_files as $file) {
    if (file_exists($file)) {
        require_once $file;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    echo "错误：无法加载框架环境。请确保 vendor/autoload.php 或 app/bootstrap.php 路径正确。
";
    exit(1);
}

echo "框架环境加载成功。
";

try {
    // 使用框架的数据库服务
    $pdo = \app\services\Database::pdo();
    $prefix = \app\services\Database::prefix();
    $table = $prefix . 'admin_plugins';

    echo "数据库连接成功。
";

    // 定义插件信息
    $pluginId = 'email/smtp'; // 旧版插件的ID通常是 '类型/插件名'
    $name = 'Smtp';
    $status = 'installed';
    $type = 'email';

    // 检查插件是否已存在于数据库中
    $stmt = $pdo->prepare("SELECT `id`, `status` FROM `{$table}` WHERE `plugin_id` = ?");
    $stmt->execute([$pluginId]);
    $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($existing) {
        echo "插件 '{$pluginId}' 已存在于数据库中。
";
        // 如果已存在但状态不是 'installed'，则更新它
        if ($existing['status'] !== $status) {
            $updateStmt = $pdo->prepare("UPDATE `{$table}` SET `status` = ? WHERE `id` = ?");
            $updateStmt->execute([$status, $existing['id']]);
            echo "已将插件 '{$pluginId}' 的状态更新为 'installed'。
";
        } else {
            echo "插件状态已经是 'installed'，无需操作。
";
        }
    } else {
        // 如果插件不存在，则插入新记录
        echo "插件 '{$pluginId}' 不存在于数据库中，正在插入新记录...
";
        $insertStmt = $pdo->prepare(
            "INSERT INTO `{$table}` (`plugin_id`, `name`, `status`, `type`, `installed_at`) VALUES (?, ?, ?, ?, ?)"
        );
        $insertStmt->execute([$pluginId, $name, $status, $type, date('Y-m-d H:i:s')]);
        echo "成功将插件 '{$pluginId}' 注册到数据库。
";
    }

    // 额外步骤：确保旧版插件状态文件也同步
    // 这会让后台UI正确显示插件状态
    $state_file = __DIR__ . '/../storage/framework/legacy_plugins.json';
    $states = [];
    if (file_exists($state_file)) {
        $states = json_decode(file_get_contents($state_file), true) ?: [];
    }
    $states[$pluginId] = ['installed' => true, 'status' => 'enabled']; // 在UI中显示为“已启用”
    
    $state_dir = dirname($state_file);
    if (!is_dir($state_dir)) {
        mkdir($state_dir, 0755, true);
    }
    file_put_contents($state_file, json_encode($states, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "已同步更新旧版插件状态文件 'legacy_plugins.json'。
";


} catch (\PDOException $e) {
    echo "数据库操作失败: " . $e->getMessage() . "
";
    echo "请检查您的数据库连接配置以及 'admin_plugins' 表是否存在。
";
    exit(1);
} catch (\Exception $e) {
    echo "发生未知错误: " . $e->getMessage() . "
";
    exit(1);
}

echo "
修复操作完成。请刷新您的后台插件页面查看效果。
";
