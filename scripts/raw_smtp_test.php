<?php
/**
 * Raw SMTP Sending Test
 *
 * This script connects to the SMTP server and sends an email by
 * speaking the SMTP protocol manually, without using any libraries
 * like PHPMailer. This is the ultimate test of connectivity and
 * authentication.
 *
 * Usage: php scripts/raw_smtp_test.php
 */

// --- Configuration ---
$host = 'mail15.serv00.com';
$port = 465;
$timeout = 30;
$target = "ssl://{$host}:{$port}";

$username = 'fazyaldzvh@fazyaldzvh.serv00.net';
$password = '0Y0dkjuLF(*#k5(ZhOu)'; // From previous files
$fromEmail = 'fazyaldzvh@fazyaldzvh.serv00.net';
$fromName = 'Starry Night Test';

$toEmail = 'test-recipient@example.com'; // Change this to a real email address to test
$subject = 'Raw SMTP Test';
$body = "This is a test email sent via a raw SMTP conversation.

If you are seeing this, it means the server can successfully authenticate and send mail without using PHPMailer.";

// --- Helper Functions ---
function read_socket($socket, $log = true) {
    $response = '';
    while (($line = fgets($socket, 512)) !== false) {
        $response .= $line;
        if (substr($line, 3, 1) === ' ') {
            break;
        }
    }
    if ($log) {
        echo "S: " . trim($response) . "
";
    }
    return $response;
}

function write_socket($socket, $command, $log = true) {
    if ($log) {
        // Don't log the actual password
        if (strpos($command, 'AUTH LOGIN') !== false || strlen($command) > 40) {
             echo "C: [omitted for security]
";
        } else {
             echo "C: {$command}";
        }
    }
    fputs($socket, $command . "
");
}

// --- Main Logic ---
echo "====================================================
";
echo "Raw SMTP Test
";
echo "====================================================
";
echo "Attempting to open socket to {$target}...
";

$socket = @stream_socket_client($target, $errno, $errstr, $timeout);

if (!$socket) {
    echo "
ERROR: Failed to connect.
";
    echo "Error: {$errno} - {$errstr}
";
    exit(1);
}

echo "Socket connected. Beginning SMTP conversation...
";
echo "----------------------------------------------------
";

// Read welcome message
read_socket($socket);

// Send EHLO
write_socket($socket, "EHLO " . gethostname());
read_socket($socket);

// Send AUTH LOGIN
write_socket($socket, "AUTH LOGIN");
read_socket($socket);

// Send username (base64 encoded)
write_socket($socket, base64_encode($username));
read_socket($socket);

// Send password (base64 encoded)
write_socket($socket, base64_encode($password));
$authResponse = read_socket($socket);

if (strpos($authResponse, '235') !== 0) {
    echo "----------------------------------------------------
";
    echo "ERROR: Authentication failed.
";
    fclose($socket);
    exit(1);
}

// Send MAIL FROM
write_socket($socket, "MAIL FROM:<{$fromEmail}>");
read_socket($socket);

// Send RCPT TO
write_socket($socket, "RCPT TO:<{$toEmail}>");
read_socket($socket);

// Send DATA
write_socket($socket, "DATA");
read_socket($socket);

// Send email headers and body
write_socket($socket, "From: "{$fromName}" <{$fromEmail}>");
write_socket($socket, "To: <{$toEmail}>");
write_socket($socket, "Subject: {$subject}");
write_socket($socket, "MIME-Version: 1.0");
write_socket($socket, "Content-Type: text/plain; charset=utf-8");
write_socket($socket, ""); // Blank line between headers and body
write_socket($socket, $body);

// Send end of data marker
write_socket($socket, ".");
read_socket($socket);

// Send QUIT
write_socket($socket, "QUIT");
read_socket($socket);

// Close the socket
fclose($socket);

echo "----------------------------------------------------
";
echo "SMTP conversation finished.
";
echo "====================================================
";
