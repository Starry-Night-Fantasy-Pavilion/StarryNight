<?php
/**
 * Standalone SMTP Connection Test Script
 *
 * This script attempts to connect to the SMTP server using the configuration
 * from the database to diagnose connection issues. It enables detailed
 * debugging output from PHPMailer.
 *
 * Usage: php scripts/test_smtp_connection.php
 */

// Go to the project root
if (php_sapi_name() === 'cli') {
    chdir(dirname(__DIR__));
}

// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Configuration ---
// These values are taken directly from the output of check_smtp_config.php
$config = [
    'host' => 'mail15.serv00.com',
    'port' => 465,
    'username' => 'fazyaldzvh@fazyaldzvh.serv00.net',
    'password' => '0Y0dkjuLF(*#k5(ZhOu)', // IMPORTANT: This password was taken from a previous file. It might be incorrect.
    'smtpsecure' => 'ssl',
    'fromname' => '星夜阁',
    'systememail' => 'fazyaldzvh@fazyaldzvh.serv00.net',
    'charset' => 'utf-8'
];

// --- Test Details ---
$testRecipientEmail = 'test@example.com'; // An email address to send the test to
$testSubject = 'SMTP Connection Test';
$testBody = 'This is a test email from the SMTP diagnostic script.';

// --- PHPMailer Initialization ---
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = 2; // Enable verbose debug output (2 = client/server messages)
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->SMTPSecure = $config['smtpsecure'];
    $mail->Port = $config['port'];
    $mail->CharSet = $config['charset'];

    // This option is from the original plugin and can be useful for debugging
    // SSL/TLS issues. It disables certificate verification.
    // Adding a specific crypto method can resolve handshake issues on some servers.
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];

    // Recipients
    $mail->setFrom($config['systememail'], $config['fromname']);
    $mail->addAddress($testRecipientEmail);

    // Content
    $mail->isHTML(true);
    $mail->Subject = $testSubject;
    $mail->Body = $testBody;

    echo "Attempting to connect to {$config['host']}:{$config['port']}...

";
    $mail->send();
    echo "
SUCCESS: Test email sent successfully!
";

} catch (Exception $e) {
    echo "
ERROR: Failed to send email.
";
    echo "PHPMailer Exception: {$mail->ErrorInfo}
";
    echo "Underlying Exception: {$e->getMessage()}
";
}
