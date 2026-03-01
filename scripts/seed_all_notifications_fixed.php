<?php

// 通知系统数据植入脚本
// 为所有通知相关表植入示例数据
// 使用方式（在项目根目录执行）：
//   php scripts/seed_all_notifications.php

require_once __DIR__ . '/../vendor/autoload.php';

use app\services\Database;

echo "[seed_all_notifications] 开始植入通知系统数据...\n";

try {
    // 1. 植入通知栏数据
    echo "\n=== 植入通知栏数据 ===\n";
    
    $noticeBarData = [
        [
            'content' => '【系统维护公告】今晚 23:00 - 24:00 将进行系统升级，期间部分功能可能短暂不可用。',
            'link' => '/announcement',
            'priority' => 90,
            'display_from' => date('Y-m-d H:i:s'),
            'display_to' => date('Y-m-d H:i:s', strtotime('+7 days')),
            'status' => 'enabled',
            'lang' => 'zh-CN',
        ],
        [
            'content' => '【新功能上线】AI音乐创作功能已全面升级，支持更多音乐风格和更高质量的生成。',
            'link' => '/ai-music',
            'priority' => 85,
            'display_from' => date('Y-m-d H:i:s'),
            'display_to' => date('Y-m-d H:i:s', strtotime('+14 days')),
            'status' => 'enabled',
            'lang' => 'zh-CN',
        ],
        [
            'content' => '【活动通知】春季创作大赛开始报名，参与即有机会获得丰厚奖励！',
            'link' => '/crowdfunding/spring-contest',
            'priority' => 80,
            'display_from' => date('Y-m-d H:i:s'),
            'display_to' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'status' => 'enabled',
            'lang' => 'zh-CN',
        ],
        [
            'content' => '建议完善个人资料并绑定邮箱，以便在密码找回和安全通知时使用。',
            'link' => '/user_center/profile',
            'priority' => 60,
            'display_from' => date('Y-m-d H:i:s'),
            'display_to' => date('Y-m-d H:i:s', strtotime('+90 days')),
            'status' => 'enabled',
            'lang' => 'zh-CN',
        ],
        [
            'content' => '每天登录可同步作品进度，记得常回来看看你的创作哦～',
            'link' => '/novel',
            'priority' => 30,
            'display_from' => date('Y-m-d H:i:s'),
            'display_to' => date('Y-m-d H:i:s', strtotime('+365 days')),
            'status' => 'enabled',
            'lang' => 'zh-CN',
        ],
        [
            'content' => '【系统公告】我们已修复了已知的登录问题，如有问题请联系客服。',
            'link' => '/feedback',
            'priority' => 70,
            'display_from' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'display_to' => date('Y-m-d H:i:s', strtotime('+5 days')),
            'status' => 'enabled',
            'lang' => 'zh-CN',
        ],
        [
            'content' => '【版本更新】小说编辑器新增自动保存功能，再也不怕丢失创作内容了！',
            'link' => '/novel/editor',
            'priority' => 65,
            'display_from' => date('Y-m-d H:i:s'),
            'display_to' => date('Y-m-d H:i:s', strtotime('+21 days')),
            'status' => 'enabled',
            'lang' => 'zh-CN',
        ],
        [
            'content' => '【会员福利】VIP会员现在可以享受无限AI创作次数，快来升级体验吧！',
            'link' => '/membership',
            'priority' => 75,
            'display_from' => date('Y-m-d H:i:s'),
            'display_to' => date('Y-m-d H:i:s', strtotime('+60 days')),
            'status' => 'enabled',
            'lang' => 'zh-CN',
        ]
    ];

    foreach ($noticeBarData as $notice) {
        Database::insert('notice_bar', $notice);
        echo "  ✓ 已添加通知栏: " . substr($notice['content'], 0, 30) . "...\n";
    }

    // 2. 植入通知模板数据
    echo "\n=== 植入通知模板数据 ===\n";
    
    $emailTemplates = [
        [
            'channel' => 'email',
            'code' => 'welcome_email',
            'title' => '欢迎加入星夜阁',
            'content' => 'welcome_email.html',
        ],
        [
            'channel' => 'email',
            'code' => 'password_reset',
            'title' => '密码重置',
            'content' => 'reset_password_email.html',
        ],
        [
            'channel' => 'email',
            'code' => 'account_suspended',
            'title' => '账户暂停通知',
            'content' => 'account_suspended.html',
        ],
        [
            'channel' => 'email',
            'code' => 'payment_success',
            'title' => '支付成功通知',
            'content' => 'payment_success.html',
        ],
        [
            'channel' => 'email',
            'code' => 'subscription_expired',
            'title' => '订阅到期提醒',
            'content' => 'subscription_expired.html',
        ],
        [
            'channel' => 'email',
            'code' => 'system_maintenance',
            'title' => '系统维护通知',
            'content' => 'system_maintenance.html',
        ]
    ];

    foreach ($emailTemplates as $template) {
        Database::insert('notification_templates', $template);
        echo "  ✓ 已添加邮件模板: {$template['title']}\n";
    }

    $smsTemplates = [
        [
            'channel' => 'sms',
            'code' => 'login_verification',
            'title' => '登录验证码',
            'content' => '您的登录验证码是：{{code}}，{{minutes}}分钟内有效。',
        ],
        [
            'channel' => 'sms',
            'code' => 'register_verify',
            'title' => '注册验证码',
            'content' => '您的注册验证码是：{{code}}，{{minutes}}分钟内有效。',
        ],
        [
            'channel' => 'sms',
            'code' => 'password_reset_sms',
            'title' => '密码重置验证码',
            'content' => '您的密码重置验证码是：{{code}}，{{minutes}}分钟内有效。',
        ],
        [
            'channel' => 'sms',
            'code' => 'payment_notification',
            'title' => '支付通知',
            'content' => '您已成功支付{{amount}}元，订单号：{{order_id}}。',
        ]
    ];

    foreach ($smsTemplates as $template) {
        Database::insert('notification_templates', $template);
        echo "  ✓ 已添加短信模板: {$template['title']}\n";
    }

    $systemTemplates = [
        [
            'channel' => 'system',
            'code' => 'new_message',
            'title' => '新消息通知',
            'content' => '您有新的消息：{{message_title}}',
        ],
        [
            'channel' => 'system',
            'code' => 'content_approved',
            'title' => '内容审核通过',
            'content' => '您提交的《{{content_title}}》已通过审核，现已发布。',
        ],
        [
            'channel' => 'system',
            'code' => 'content_rejected',
            'title' => '内容审核未通过',
            'content' => '您提交的《{{content_title}}》未通过审核，原因：{{reason}}。',
        ],
        [
            'channel' => 'system',
            'code' => 'membership_expired',
            'title' => '会员到期提醒',
            'content' => '您的会员资格将于{{days}}天后到期，请及时续费。',
        ],
        [
            'channel' => 'system',
            'code' => 'ai_quota_warning',
            'title' => 'AI配额不足提醒',
            'content' => '您的AI创作配额剩余{{remaining}}次，请及时充值。',
        ]
    ];

    foreach ($systemTemplates as $template) {
        Database::insert('notification_templates', $template);
        echo "  ✓ 已添加系统模板: {$template['title']}\n";
    }

    // 3. 植入站内信数据
    echo "\n=== 植入站内信数据 ===\n";
    
    // 获取一些用户ID作为示例
    $pdo = Database::pdo();
    $stmt = $pdo->prepare("SELECT id FROM users LIMIT 5");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($users)) {
        echo "  ⚠ 未找到用户，跳过站内信数据植入\n";
    } else {
        $siteMessages = [
            [
                'user_id' => $users[0],
                'title' => '欢迎使用星夜阁',
                'content' => '感谢您注册星夜阁！我们是一个专注于AI创作的平台，您可以在这里创作小说、音乐、动画等多种内容。如有任何问题，请随时联系我们的客服团队。',
                'status' => 'unread'
            ],
            [
                'user_id' => $users[0],
                'title' => '您的作品已通过审核',
                'content' => '您提交的作品《星夜之旅》已通过审核，现已发布到平台。您可以在个人中心查看作品详情和阅读数据。',
                'status' => 'unread'
            ],
            [
                'user_id' => $users[1] ?? $users[0],
                'title' => '会员权益说明',
                'content' => '作为我们的VIP会员，您享有无限AI创作次数、优先审核、专属客服等多项权益。如有任何疑问，请查看会员中心或联系客服。',
                'status' => 'read'
            ],
            [
                'user_id' => $users[1] ?? $users[0],
                'title' => '系统维护通知',
                'content' => '系统将于今晚23:00-24:00进行维护升级，期间部分功能可能无法使用。请提前保存您的创作内容，给您带来的不便敬请谅解。',
                'status' => 'read'
            ],
            [
                'user_id' => $users[2] ?? $users[0],
                'title' => '创作活动邀请',
                'content' => '诚邀您参加我们的春季创作大赛！本次活动设有多个奖项，最高可获得10000星夜币奖励。点击活动页面了解更多详情和报名方式。',
                'status' => 'unread'
            ]
        ];

        foreach ($siteMessages as $message) {
            Database::insert('site_messages', $message);
            echo "  ✓ 已添加站内信: {$message['title']}\n";
        }
    }

    echo "\n[seed_all_notifications] 所有通知数据植入完成！\n";
} catch (\Throwable $e) {
    echo "[seed_all_notifications] 发生错误：{$e->getMessage()}\n";
    echo "错误位置：{$e->getFile()}:{$e->getLine()}\n";
    exit(1);
}

echo "[seed_all_notifications] OK\n";