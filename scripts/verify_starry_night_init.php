<?php
require __DIR__ . '/../app/services/Database.php';
$pdo = app\services\Database::pdo();
$prefix = app\services\Database::prefix();
$r = $pdo->query("SELECT id, engine_version, membership_level_id, is_enabled, description FROM {$prefix}starry_night_engine_permissions");
echo "starry_night_engine_permissions:\n";
foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
