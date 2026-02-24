<?php
/**
 * 测试其他可能的SMTP配置
 */

$configs = [
    [
        'name' => 'SSL 465 (当前配置)',
        'host' => 'mail15.serv00.com',
        'port' => 465,
        'secure' => 'ssl'
    ],
    [
        'name' => 'TLS 587',
        'host' => 'mail15.serv00.com', 
        'port' => 587,
        'secure' => 'tls'
    ],
    [
        'name' => '无加密 25',
        'host' => 'mail15.serv00.com',
        'port' => 25,
        'secure' => ''
    ],
    [
        'name' => 'SSL 994',
        'host' => 'mail15.serv00.com',
        'port' => 994,
        'secure' => 'ssl'
    ]
];

foreach ($configs as $config) {
    echo "\n=== 测试配置: {$config['name']} ===\n";
    
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ]
    ]);
    
    $protocol = $config['secure'] ? $config['secure'] . '://' : 'tcp://';
    $socket = @stream_socket_client(
        $protocol . $config['host'] . ':' . $config['port'],
        $errno,
        $errstr,
        10,
        STREAM_CLIENT_CONNECT,
        $context
    );
    
    if ($socket) {
        echo "✓ 连接成功\n";
        $response = fread($socket, 1024);
        if ($response) {
            echo "服务器响应: " . trim($response) . "\n";
        } else {
            echo "无服务器响应\n";
        }
        fclose($socket);
    } else {
        echo "✗ 连接失败: {$errno} - {$errstr}\n";
    }
}
