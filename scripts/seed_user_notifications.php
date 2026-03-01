<?php

// 用户通知数据植入脚本
// 为用户通知记录表植入示例数据
// 使用方式（在项目根目录执行）：
//   php scripts/seed_user_notifications.php

require_once __DIR__ . '/../vendor/autoload.php';

use app\services\Database;

echo "[seed_user_notifications] 开始植入用户通知数据...\n";

try {
    // 首先检查表是否存在
    $pdo = Database::pdo();
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_notifications'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "  ⚠ user_notifications 表不存在，请先执行迁移 021_user_notifications.sql\n";
        exit(1);
    }
    
    // 获取用户ID
    $stmt = $pdo->prepare("SELECT id FROM users LIMIT 10");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($users)) {
        echo "  ⚠ 未找到用户，请先创建用户\n";
        exit(1);
    }
    
    echo "\n=== 植入用户通知数据 ===\n";
    
    // 为每个用户创建不同类型的通知
    foreach ($users as $index => $userId) {
        echo "\n用户 ID: {$userId}\n";
        
        // 系统通知
        $systemNotifications = [
            [
                'user_id' => $userId,
                'type' => 'system',
                'category' => 'general',
                'title' => '欢迎使用星夜阁',
                'content' => '感谢您注册星夜阁！我们是一个专注于AI创作的平台，您可以在这里创作小说、音乐、动画等多种内容。如有任何问题，请随时联系我们的客服团队。',
                'data' => json_encode(['welcome_bonus' => 100, 'tutorial_url' => '/tutorial']),
                'priority' => 2,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s', strtotime("-{$index} days"))
            ],
            [
                'user_id' => $userId,
                'type' => 'system',
                'category' => 'security',
                'title' => '登录安全提醒',
                'content' => '您的账户在新设备上登录，如非本人操作请及时修改密码。',
                'data' => json_encode(['ip' => '192.168.1.' . ($index + 1), 'device' => 'Chrome/Windows']),
                'priority' => 2,
                'is_read' => $index % 2 === 0 ? 1 : 0,
                'read_at' => $index % 2 === 0 ? date('Y-m-d H:i:s', strtotime("-1 hour")) : null,
                'created_at' => date('Y-m-d H:i:s', strtotime("-" . ($index + 1) . " days"))
            ],
            [
                'user_id' => $userId,
                'type' => 'system',
                'category' => 'content',
                'title' => '作品审核通过',
                'content' => '您提交的作品《星夜之旅》已通过审核，现已发布到平台。您可以在个人中心查看作品详情和阅读数据。',
                'data' => json_encode(['work_id' => 100 + $index, 'work_title' => '星夜之旅']),
                'related_id' => 100 + $index,
                'related_type' => 'novel',
                'priority' => 1,
                'is_read' => $index % 3 === 0 ? 1 : 0,
                'read_at' => $index % 3 === 0 ? date('Y-m-d H:i:s', strtotime("-2 hours")) : null,
                'created_at' => date('Y-m-d H:i:s', strtotime("-" . ($index + 2) . " hours"))
            ]
        ];
        
        foreach ($systemNotifications as $notification) {
            Database::insert('user_notifications', $notification);
            echo "  ✓ 已添加系统通知: {$notification['title']}\n";
        }
        
        // 应用内通知
        $appNotifications = [
            [
                'user_id' => $userId,
                'type' => 'app',
                'category' => 'general',
                'title' => '每日签到提醒',
                'content' => '今天还没有签到哦，连续签到可获得更多奖励！',
                'data' => json_encode(['streak' => 5 + $index, 'bonus' => 50 + $index * 10]),
                'priority' => 0,
                'is_read' => $index % 4 === 0 ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s', strtotime("-30 minutes"))
            ],
            [
                'user_id' => $userId,
                'type' => 'app',
                'category' => 'activity',
                'title' => '新粉丝通知',
                'content' => "恭喜！user{$index}关注了您，快去看看TA的作品吧。",
                'data' => json_encode(['follower_id' => 200 + $index, 'follower_name' => "user{$index}"]),
                'related_id' => 200 + $index,
                'related_type' => 'user',
                'priority' => 1,
                'is_read' => $index % 3 === 0 ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s', strtotime("-" . ($index + 3) . " hours"))
            ],
            [
                'user_id' => $userId,
                'type' => 'app',
                'category' => 'content',
                'title' => '作品获赞',
                'content' => "您的作品《星夜之旅》获得了" . (10 + $index * 5) . "个赞，继续加油！",
                'data' => json_encode(['work_id' => 100 + $index, 'work_title' => '星夜之旅', 'likes' => 10 + $index * 5]),
                'related_id' => 100 + $index,
                'related_type' => 'novel',
                'priority' => 1,
                'is_read' => $index % 2 === 0 ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s', strtotime("-" . ($index + 5) . " hours"))
            ]
        ];
        
        foreach ($appNotifications as $notification) {
            Database::insert('user_notifications', $notification);
            echo "  ✓ 已添加应用通知: {$notification['title']}\n";
        }
        
        // 支付和会员通知
        if ($index < 5) {
            $paymentNotifications = [
                [
                    'user_id' => $userId,
                    'type' => 'email',
                    'category' => 'payment',
                    'title' => '支付成功',
                    'content' => '您已成功购买VIP会员月卡，感谢您的支持！',
                    'data' => json_encode(['order_id' => 'ORD20230228' . str_pad($index + 1, 3, '0', STR_PAD_LEFT), 'amount' => 29.99, 'product' => 'VIP月卡']),
                    'related_id' => 300 + $index,
                    'related_type' => 'order',
                    'priority' => 2,
                    'is_read' => 1,
                    'read_at' => date('Y-m-d H:i:s', strtotime("-1 day")),
                    'created_at' => date('Y-m-d H:i:s', strtotime("-1 day"))
                ],
                [
                    'user_id' => $userId,
                    'type' => 'system',
                    'category' => 'membership',
                    'title' => '会员到期提醒',
                    'content' => '您的会员资格将于' . (7 + $index) . '天后到期，请及时续费。',
                    'data' => json_encode(['expire_date' => date('Y-m-d', strtotime("+" . (7 + $index) . " days")), 'days_left' => 7 + $index]),
                    'priority' => 1,
                    'is_read' => $index % 2 === 0 ? 1 : 0,
                    'created_at' => date('Y-m-d H:i:s', strtotime("-2 days"))
                ]
            ];
            
            foreach ($paymentNotifications as $notification) {
                Database::insert('user_notifications', $notification);
                echo "  ✓ 已添加支付/会员通知: {$notification['title']}\n";
            }
        }
        
        // 活动通知
        if ($index < 3) {
            $activityNotifications = [
                [
                    'user_id' => $userId,
                    'type' => 'push',
                    'category' => 'activity',
                    'title' => '创作活动邀请',
                    'content' => '诚邀您参加我们的春季创作大赛！本次活动设有多个奖项，最高可获得10000星夜币奖励。',
                    'data' => json_encode(['activity_id' => 10, 'activity_name' => '春季创作大赛', 'end_date' => '2023-03-31']),
                    'related_id' => 10,
                    'related_type' => 'activity',
                    'priority' => 2,
                    'is_read' => $index % 2 === 0 ? 1 : 0,
                    'created_at' => date('Y-m-d H:i:s', strtotime("-6 hours"))
                ]
            ];
            
            foreach ($activityNotifications as $notification) {
                Database::insert('user_notifications', $notification);
                echo "  ✓ 已添加活动通知: {$notification['title']}\n";
            }
        }
        
        // AI配额提醒
        if ($index < 4) {
            $aiNotifications = [
                [
                    'user_id' => $userId,
                    'type' => 'system',
                    'category' => 'payment',
                    'title' => 'AI配额不足提醒',
                    'content' => '您的AI创作配额剩余' . (5 + $index) . '次，请及时充值。',
                    'data' => json_encode(['remaining' => 5 + $index, 'total' => 100]),
                    'priority' => 1,
                    'is_read' => $index % 3 === 0 ? 1 : 0,
                    'created_at' => date('Y-m-d H:i:s', strtotime("-1 hour"))
                ]
            ];
            
            foreach ($aiNotifications as $notification) {
                Database::insert('user_notifications', $notification);
                echo "  ✓ 已添加AI配额通知: {$notification['title']}\n";
            }
        }
    }
    
    // 统计插入的数据
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_notifications");
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "\n[seed_user_notifications] 用户通知数据植入完成！\n";
    echo "总计插入 {$total} 条通知记录\n";
    
} catch (\Throwable $e) {
    echo "[seed_user_notifications] 发生错误：{$e->getMessage()}\n";
    echo "错误位置：{$e->getFile()}:{$e->getLine()}\n";
    exit(1);
}

echo "[seed_user_notifications] OK\n";