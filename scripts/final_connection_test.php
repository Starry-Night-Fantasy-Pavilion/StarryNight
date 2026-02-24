<?php
/**
 * Final Connection Test Script
 *
 * This script bypasses PHPMailer entirely and uses PHP's low-level
 * stream functions to attempt a direct connection to the SMTP server's
 * SSL port.
 *
 * This is the definitive test to determine if a network connection
 * from this server to the mail server is possible.
 *
 * Usage: php scripts/final_connection_test.php
 */

// --- Configuration ---
$host = 'mail15.serv00.com';
$port = 465;
$timeout = 15; // seconds

$target = "ssl://{$host}:{$port}";

echo "====================================================
";
echo "Final Connection Test
";
echo "====================================================
";
echo "Attempting to open a direct, low-level SSL socket connection...
";
echo "Target: {$target}
";
echo "Timeout: {$timeout} seconds
";
echo "----------------------------------------------------
";

// Use error handler to capture connection errors
set_error_handler(function($errno, $errstr) {
    echo "Caught Low-Level Error:
";
    echo "  - Error Number: {$errno}
";
    echo "  - Error String: {$errstr}
";
    echo "
";
});

$socket = @stream_socket_client($target, $errno, $errstr, $timeout);

restore_error_handler();

echo "----------------------------------------------------
";

if ($socket) {
    echo "RESULT: SUCCESS!
";
    echo "A low-level SSL connection was successfully established.
";
    echo "This indicates that there is no firewall block. The issue might be
";
    echo "a very specific incompatibility between PHPMailer and the server's PHP SSL configuration.
";
    fclose($socket);
} else {
    echo "RESULT: FAILURE.
";
    echo "A low-level SSL connection could NOT be established.
";
    echo "Error Number: {$errno}
";
    echo "Error String: {$errstr}
";
    echo "
";
    echo "CONCLUSION: This definitively proves the problem is at the
";
    echo "network level. Your server cannot reach the mail server at
";
    echo "{$host} on port {$port}. This is most likely due to a
";
    echo "firewall or a hosting provider policy.
";
}

echo "====================================================
";
