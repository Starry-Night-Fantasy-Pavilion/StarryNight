<?php
/**
 * 修复 SMTP SSL 配置
 */
require_once __DIR__ . '/../vendor/autoload.php';

use app\services\Database;

$pdo = Database::pdo();
$table = Database::prefix() . 'admin_plugins';

$stmt = $pdo->prepare("SELECT config_json FROM `{$table}` WHERE plugin_id = ?");
$stmt->execute(['email/asiayun_smtp_pro']);
$result = $stmt->fetch();

if ($result) {
    $config = json_decode($result['config_json'], true);
    
    echo "当前配置:\n";
    print_r($config);
    
    // 修复 smtpsecure 为 ssl (端口 465 需要 SSL)
    $config['smtpsecure'] = 'ssl';
    
    // 确保 systememail 正确
    $config['systememail'] = 'fazyaldzvh@fazyaldzvh.serv00.net';
    
    $stmt = $pdo->prepare("UPDATE `{$table}` SET config_json = ? WHERE plugin_id = ?");
    $stmt->execute([json_encode($config, JSON_UNESCAPED_UNICODE), 'email/asiayun_smtp_pro']);
    
    echo "\n配置已修复！\n";
    echo "smtpsecure: ssl\n";
    echo "systememail: fazyaldzvh@fazyaldzvh.serv00.net\n";
} else {
    echo "未找到插件配置\n";
}
