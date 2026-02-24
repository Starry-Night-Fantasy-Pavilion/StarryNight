<?php
require_once 'app/helpers.php';
use app\services\Database;

try {
    $pdo = Database::pdo();
    $prefix = Database::prefix();
    
    echo "检查关键表存在情况：\n";
    echo "=====================\n";
    
    $requiredTables = [
        'ai_agents' => 'AI智能体表',
        'ai_agent_market' => 'AI智能体市场表',
        'user_feedback' => '用户反馈表',
        'notice_bar' => '通知栏表',
        'announcements' => '公告表',
        'announcement_categories' => '公告分类表',
        'user_announcement_reads' => '用户公告阅读记录表'
    ];
    
    foreach ($requiredTables as $table => $description) {
        $fullTable = $prefix . $table;
        $stmt = $pdo->query("SHOW TABLES LIKE '$fullTable'");
        if ($stmt->rowCount() > 0) {
            echo "✓ $table ($description) - 存在\n";
        } else {
            echo "✗ $table ($description) - 缺失\n";
        }
    }
    
    echo "\n检查完成！\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}