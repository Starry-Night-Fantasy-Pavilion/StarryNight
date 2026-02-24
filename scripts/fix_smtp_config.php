<?php
/**
 * 修复 SMTP 配置存储格式问题
 * 
 * 问题：配置值被存储为数组格式而不是字符串格式
 * 例如："port":[465] 而不是 "port":"465"
 */

require_once __DIR__ . '/../vendor/autoload.php';

$host = '127.0.0.1';
$port = 3306;
$dbName = '51111';
$username = '51111';
$password = 'xpbsB6RxB6bhATjG';
$prefix = 'sn_';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "数据库连接成功！\n";
    
    // 获取当前配置
    $tableName = $prefix . 'admin_plugins';
    $stmt = $pdo->prepare("SELECT config_json FROM $tableName WHERE plugin_id = ?");
    $stmt->execute(['email/asiayun_smtp_pro']);
    $result = $stmt->fetch();
    
    if (!$result) {
        echo "未找到配置！\n";
        exit(1);
    }
    
    echo "原始配置:\n";
    echo $result['config_json'] . "\n\n";
    
    // 解析配置
    $config = json_decode($result['config_json'], true);
    
    if (!$config) {
        echo "JSON解析失败！\n";
        exit(1);
    }
    
    // 修复：将数组值转换为字符串值
    $fixedConfig = [];
    foreach ($config as $key => $value) {
        if (is_array($value) && count($value) === 1) {
            // 处理单元素数组，如 ["465"] -> "465"
            $fixedConfig[$key] = reset($value);
        } elseif (is_array($value)) {
            // 多元素数组保持不变
            $fixedConfig[$key] = $value;
        } else {
            // 普通值保持不变
            $fixedConfig[$key] = $value;
        }
    }
    
    echo "修复后的配置:\n";
    $fixedJson = json_encode($fixedConfig, JSON_UNESCAPED_UNICODE);
    echo $fixedJson . "\n\n";
    
    // 更新数据库
    $stmt = $pdo->prepare("UPDATE $tableName SET config_json = ? WHERE plugin_id = ?");
    $stmt->execute([$fixedJson, 'email/asiayun_smtp_pro']);
    
    echo "配置已修复！\n";
    
} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
    exit(1);
}
