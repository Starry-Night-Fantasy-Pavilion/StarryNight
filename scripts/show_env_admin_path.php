<?php

$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    echo ".env not found\n";
    exit(0);
}

$env = [];
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) {
        continue;
    }
    if (!str_contains($line, '=')) {
        continue;
    }
    list($name, $value) = explode('=', $line, 2);
    $name = trim($name);
    $value = trim($value);
    if (preg_match('/^"(.*)"$/', $value, $m)) {
        $value = $m[1];
    } elseif (preg_match("/^'(.*)'$/", $value, $m)) {
        $value = $m[1];
    }
    $env[$name] = $value;
}

echo "ADMIN_PATH=" . ($env['ADMIN_PATH'] ?? '') . PHP_EOL;
echo "APP_URL=" . ($env['APP_URL'] ?? '') . PHP_EOL;

