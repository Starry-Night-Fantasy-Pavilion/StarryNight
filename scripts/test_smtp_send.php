<?php
/**
 * 测试 SMTP 邮件发送功能
 */
require_once __DIR__ . '/../vendor/autoload.php';

use app\services\Database;

// 模拟发送测试邮件
function testSendMail() {
    // 加载 helpers.php 以获取 send_system_mail 函数
    require_once __DIR__ . '/../app/helpers.php';
    
    $testEmail = 'test@example.com';  // 替换为实际接收测试邮件的邮箱
    $subject = '测试邮件 - 星夜阁';
    $content = '这是一封测试邮件，用于验证 SMTP 配置是否正确。<br><br>如果收到此邮件，说明邮件系统工作正常。';
    
    $errorMsg = null;
    $result = send_system_mail($testEmail, $subject, $content, $errorMsg);
    
    if ($result) {
        echo "✅ 邮件发送成功！\n";
        echo "已发送到: $testEmail\n";
    } else {
        echo "❌ 邮件发送失败\n";
        echo "错误信息: $errorMsg\n";
    }
    
    return $result;
}

// 运行测试
echo "====================================\n";
echo "SMTP 邮件发送测试\n";
echo "====================================\n\n";

// 检查插件是否启用
$legacyFile = __DIR__ . '/../storage/framework/legacy_plugins.json';
if (!is_file($legacyFile)) {
    echo "❌ 插件配置文件不存在\n";
    exit(1);
}

$json = file_get_contents($legacyFile);
$plugins = json_decode($json, true);

if (empty($plugins['email/asiayun_smtp_pro']['installed']) || $plugins['email/asiayun_smtp_pro']['status'] !== 'enabled') {
    echo "❌ 邮件插件未启用\n";
    exit(1);
}

echo "✓ 邮件插件已启用\n";

// 检查数据库配置
try {
    $pdo = Database::pdo();
    $table = Database::prefix() . 'admin_plugins';
    $stmt = $pdo->prepare("SELECT `config_json` FROM `{$table}` WHERE `plugin_id` = ?");
    $stmt->execute(['email/asiayun_smtp_pro']);
    $pluginData = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if ($pluginData && !empty($pluginData['config_json'])) {
        $config = json_decode($pluginData['config_json'], true);
        echo "✓ 数据库配置已加载\n";
        echo "\n当前 SMTP 配置:\n";
        echo "  主机: " . ($config['host'] ?? '未设置') . "\n";
        echo "  端口: " . ($config['port'] ?? '未设置') . "\n";
        echo "  用户名: " . ($config['username'] ?? '未设置') . "\n";
        echo "  SSL: " . ($config['smtpsecure'] ?? '未设置') . "\n";
        echo "  系统邮箱: " . ($config['systememail'] ?? '未设置') . "\n";
        echo "  发件人: " . ($config['fromname'] ?? '未设置') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ 读取配置失败: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n====================================\n";
echo "开始发送测试邮件...\n";
echo "====================================\n\n";

// 注意：由于没有实际的测试邮箱，这里只输出配置信息
echo "⚠️ 注意: 请在后台手动测试邮件发送功能\n";
echo "测试方法: 访问后台 - 插件管理 - 邮件插件 - 发送测试邮件\n";
