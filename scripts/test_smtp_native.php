<?php
/**
 * 使用原生socket测试SMTP协议
 */

$host = 'mail15.serv00.com';
$port = 465;
$username = 'fazyaldzvh@fazyaldzvh.serv00.net';
$password = '0Y0dkjuLF(*#k5(ZhOu)';
$from = 'fazyaldzvh@fazyaldzvh.serv00.net';
$to = 'test@example.com';

echo "Testing native SMTP connection to {$host}:{$port}...\n";

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
    30,
    STREAM_CLIENT_CONNECT,
    $context
);

if (!$socket) {
    echo "ERROR: Connection failed - {$errno}: {$errstr}\n";
    exit(1);
}

echo "SUCCESS: Connected to SMTP server\n";

// 读取欢迎消息
$welcome = fread($socket, 1024);
echo "Server: " . trim($welcome) . "\n";

// EHLO
fwrite($socket, "EHLO localhost\r\n");
$response = fread($socket, 1024);
echo "EHLO: " . trim($response) . "\n";

// AUTH LOGIN
fwrite($socket, "AUTH LOGIN\r\n");
$response = fread($socket, 1024);
echo "AUTH: " . trim($response) . "\n";

// 发送用户名 (base64编码)
fwrite($socket, base64_encode($username) . "\r\n");
$response = fread($socket, 1024);
echo "Username: " . trim($response) . "\n";

// 发送密码 (base64编码)
fwrite($socket, base64_encode($password) . "\r\n");
$response = fread($socket, 1024);
echo "Password: " . trim($response) . "\n";

if (strpos($response, '235') === 0) {
    echo "SUCCESS: Authentication successful\n";
} else {
    echo "ERROR: Authentication failed\n";
}

fclose($socket);
