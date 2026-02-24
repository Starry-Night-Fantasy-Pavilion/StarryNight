<?php

// PSR-4-like autoloader
spl_autoload_register(function ($class) {
    // Define namespace prefixes and their base directories
    $prefixes = [
        "app\\" => __DIR__ . '/../../../app/',
        "api\\v1\\controllers\\" => __DIR__ . '/controllers/',
        "Core\\" => __DIR__ . '/../../../core/',
    ];

    foreach ($prefixes as $prefix => $base_dir) {
        // Does the class use the namespace prefix?
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            // No, move to the next registered autoloader
            continue;
        }

        // Get the relative class name
        $relative_class = substr($class, $len);

        // Replace the namespace prefix with the base directory,
        // replace namespace separators with directory separators,
        // and append with .php
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        // If the file exists, require it
        if (file_exists($file)) {
            require $file;
            return; // Class found, no need to check other prefixes
        }
    }
});

// 加载辅助函数
$helpersFile = __DIR__ . '/../../../app/helpers.php';
if (file_exists($helpersFile)) {
    require_once $helpersFile;
}

// Basic routing
header("Content-Type: application/json; charset=UTF-8");

// A simple router
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';

// This simple router assumes the api/v1/index.php is the entry point.
// e.g. /api/v1/membership/info
$path = trim(str_replace(dirname($script_name), '', $request_uri), '/');
$segments = explode('/', $path);

$controllerName = $segments[0] ?? null;
$methodName = $segments[1] ?? 'index';

if ($controllerName !== 'membership') {
    http_response_code(404);
    echo json_encode([
        'code' => 404,
        'message' => 'Endpoint not found.',
        'data' => null,
        'timestamp' => time(),
    ]);
    exit;
}

$controllerClass = 'api\v1\controllers\MembershipController';

if (!class_exists($controllerClass)) {
    http_response_code(404);
    echo json_encode([
        'code' => 404,
        'message' => "Controller class not found: {$controllerClass}",
        'data' => null,
        'timestamp' => time(),
    ]);
    exit;
}

$controller = new $controllerClass();

// A very basic mapping from URL segment to method name.
$routeMap = [
    'info' => 'getInfo',
    'limits' => 'getLimits',
    'packages' => 'getMembershipPackages',
    'purchase' => 'purchaseMembership',
    'recharge-packages' => 'getRechargePackages',
    'recharge' => 'recharge',
    'orders' => 'getOrders',
    'token-records' => 'getTokenRecords',
    'check-feature' => 'checkFeature',
    'features' => 'getFeatures',
    'payment-callback' => 'handlePaymentCallback'
];

$action = $routeMap[$methodName] ?? null;

if ($action && method_exists($controller, $action)) {
    // Start session to get user ID
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // For testing, let's assume user_id is 1 if not set
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 1;
    }
    
    try {
        $controller->$action();
    } catch (\Core\Exceptions\AppException $e) {
        http_response_code($e->getHttpStatus());
        echo json_encode($e->toArray());
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode([
            'code' => \Core\Exceptions\ErrorCode::SYSTEM_ERROR,
            'message' => $e->getMessage(),
            'data' => null,
            'timestamp' => time(),
        ]);
    }
} else {
    http_response_code(404);
    echo json_encode([
        'code' => 404,
        'message' => "Action not found for method: {$methodName}",
        'data' => null,
        'timestamp' => time(),
    ]);
}
