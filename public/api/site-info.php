<?php
/**
 * 站点信息API
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use app\models\Setting;

header('Content-Type: application/json; charset=utf-8');

$siteName = Setting::get('site_name') ?: (string)get_env('APP_NAME', '星夜阁');

echo json_encode([
    'success' => true,
    'siteName' => $siteName
], JSON_UNESCAPED_UNICODE);
