<?php
// Ensure the script is run from the project root
if (php_sapi_name() === 'cli') {
    // This helps make relative paths work consistently
    chdir(dirname(__DIR__));
}

// Load the Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Use the application's own database service to ensure we're using the correct configuration
use app\services\Database;

echo "正在检查数据库中的 SMTP 插件配置...

";

try {
    // Get the PDO instance from the application's service
    $pdo = Database::pdo();
    
    // Get the correct table prefix
    $prefix = Database::prefix();
    
    $tableName = $prefix . 'admin_plugins';

    // Check if the table exists
    try {
        $pdo->query("SELECT 1 FROM `{$tableName}` LIMIT 1");
    } catch (\PDOException $e) {
        echo "错误: 表 `{$tableName}` 不存在或无法访问。
";
        echo "数据库错误信息: " . $e->getMessage() . "
";
        exit(1);
    }

    // Fetch all email-related plugin configurations
    $stmt = $pdo->query("SELECT plugin_id, config_json FROM `{$tableName}` WHERE plugin_id LIKE '%email%'");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) {
        echo "错误: 在 `{$tableName}` 表中没有找到任何邮件插件配置。
";
        exit(1);
    }

    foreach ($results as $row) {
        echo "========================================
";
        echo "插件 ID: " . $row['plugin_id'] . "
";
        echo "----------------------------------------
";
        
        $configJson = $row['config_json'];
        
        // The config might be double (or triple) JSON encoded
        $decoded = json_decode($configJson, true);
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        if (is_array($decoded)) {
            foreach ($decoded as $key => $value) {
                // Mask sensitive information
                if (in_array(strtolower($key), ['password', 'smtp_pass', 'smtp_password'])) {
                    $value = '********';
                }
                printf("%-15s: %s
", $key, $value);
            }
        } else {
            echo "配置解析失败。原始 JSON: 
" . $configJson . "
";
        }
        echo "========================================

";
    }

} catch (\PDOException $e) {
    echo "数据库连接失败: " . $e->getMessage() . "
";
    echo "请检查项目根目录下的 .env 文件中的数据库配置是否正确。
";
    exit(1);
} catch (\Throwable $e) {
    echo "发生未知错误: " . $e->getMessage() . "
";
    exit(1);
}
