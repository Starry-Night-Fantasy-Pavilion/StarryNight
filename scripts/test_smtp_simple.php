<?php
/**
 * 简化的SMTP连接测试
 */

$host = 'mail15.serv00.com';
$port = 465;
$timeout = 10;

echo "Testing basic socket connection to {$host}:{$port}...\n";

$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
    ]
]);

$socket = @stream_socket_client(
    "ssl://{$host}:{$port}",
    $errno,
    $errstr,
    $timeout,
    STREAM_CLIENT_CONNECT,
    $context
);

if ($socket) {
    echo "SUCCESS: Socket connection established\n";
    
    // 读取服务器欢迎消息
    $welcome = fread($socket, 1024);
    echo "Server response: " . trim($welcome) . "\n";
    
    fclose($socket);
} else {
    echo "ERROR: Connection failed - {$errno}: {$errstr}\n";
}
