<?php
/**
 * 最终修复 SMTP 配置
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
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // 获取当前配置
    $tableName = $prefix . 'admin_plugins';
    $stmt = $pdo->prepare("SELECT config_json FROM $tableName WHERE plugin_id = ?");
    $stmt->execute(['email/asiayun_smtp_pro']);
    $result = $stmt->fetch();
    
    if (!$result) {
        echo "未找到配置！\n";
        exit(1);
    }
    
    // 解析并修复配置
    $config = json_decode($result['config_json'], true);
    
    // 设置正确的值
    $config['smtpsecure'] = 'ssl';  // 端口 465 需要 SSL
    $config['systememail'] = 'fazyaldzvh@fazyaldzvh.serv00.net';  // 正确的系统邮箱
    
    $fixedJson = json_encode($config, JSON_UNESCAPED_UNICODE);
    
    // 更新数据库
    $stmt = $pdo->prepare("UPDATE $tableName SET config_json = ? WHERE plugin_id = ?");
    $stmt->execute([$fixedJson, 'email/asiayun_smtp_pro']);
    
    echo "SMTP 配置已修复！\n";
    echo "当前配置:\n";
    echo $fixedJson . "\n";
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}
