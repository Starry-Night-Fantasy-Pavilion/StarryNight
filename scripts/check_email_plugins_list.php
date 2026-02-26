<?php
/**
 * 检查邮件插件列表
 * 从数据库获取并显示所有邮件插件的详细信息
 */

// 加载兼容层和依赖
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/compat.php';

// db
try {
    // db


    $pdo = app\services\Database::pdo();
    
    // 查询所有邮件相关插件
    $stmt = $pdo->prepare('
        SELECT plugin_id, status, config_json, created_at, updated_at 
        FROM admin_plugins 
        WHERE plugin_id LIKE "email/%" 
        ORDER BY created_at DESC
    ');
    $stmt->execute();
    $plugins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "====================================\n";
    echo "邮件插件列表\n";
    echo "====================================\n";
    echo "总计: " . count($plugins) . " 个插件\n\n";
    
    if (empty($plugins)) {
        echo "未找到任何邮件插件。\n";
        echo "提示: 请检查 admin_plugins 表是否存在，或插件是否已安装。\n";
    } else {
        foreach ($plugins as $index => $plugin) {
            echo "【" . ($index + 1) . "】" . $plugin['plugin_id'] . "\n";
            echo "    状态: " . ($plugin['status'] ?? '未知') . "\n";
            echo "    创建时间: " . ($plugin['created_at'] ?? '未知') . "\n";
            echo "    更新时间: " . ($plugin['updated_at'] ?? '未知') . "\n";
            
            if (!empty($plugin['config_json'])) {
                $config = json_decode($plugin['config_json'], true);
                if ($config) {
                    echo "    配置信息:\n";
                    if (isset($config['host'])) {
                        echo "      - 主机: " . $config['host'] . "\n";
                    }
                    if (isset($config['port'])) {
                        echo "      - 端口: " . $config['port'] . "\n";
                    }
                    if (isset($config['username'])) {
                        echo "      - 用户名: " . $config['username'] . "\n";
                    }
                    if (isset($config['encryption'])) {
                        echo "      - 加密方式: " . $config['encryption'] . "\n";
                    }
                    if (isset($config['from_address'])) {
                        echo "      - 发件地址: " . $config['from_address'] . "\n";
                    }
                    if (isset($config['from_name'])) {
                        echo "      - 发件人名称: " . $config['from_name'] . "\n";
                    }
                }
            }
            echo "\n";
        }
    }
    
    echo "====================================\n";
    echo "检查完成\n";
    echo "====================================\n";
    
} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
    echo "错误代码: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
