<?php

// 通知模板数据植入脚本
// 为通知模板表植入丰富的示例数据
// 使用方式（在项目根目录执行）：
//   php scripts/seed_notification_templates.php

require_once __DIR__ . '/../vendor/autoload.php';

use app\services\Database;

echo "[seed_notification_templates] 开始植入通知模板数据...\n";

try {
    // 邮件模板
    echo "\n=== 植入邮件模板 ===\n";
    
    $emailTemplates = [
        [
            'channel' => 'email',
            'code' => 'welcome_email',
            'title' => '欢迎加入星夜阁',
            'content' => 'welcome_email.html',
        ],
        [
            'channel' => 'email',
            'code' => 'register_verify_email',
            'title' => '注册验证码',
            'content' => 'register_verify_email.html',
        ],
        [
            'channel' => 'email',
            'code' => 'password_reset',
            'title' => '密码重置',
            'content' => 'reset_password_email.html',
        ],
        [
            'channel' => 'email',
            'code' => 'password_changed_notice',
            'title' => '密码修改通知',
            'content' => 'email_password_changed_notice_email.html',
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
            'code' => 'order_confirmation',
            'title' => '订单确认',
            'content' => 'order_confirmation.html',
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
        ],
        [
            'channel' => 'email',
            'code' => 'email_marketing_general',
            'title' => '营销邮件',
            'content' => 'email_marketing_general_email.html',
        ]
    ];

    foreach ($emailTemplates as $template) {
        Database::insert('notification_templates', $template);
        echo "  ✓ 已添加邮件模板: {$template['title']}\n";
    }

    // 短信模板
    echo "\n=== 植入短信模板 ===\n";
    
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
        ],
        [
            'channel' => 'sms',
            'code' => 'invoice_pay',
            'title' => '账单支付',
            'content' => '您好,您已成功支付账单号{{invoiceid}},账单金额{{total}},谢谢支持.',
        ],
        [
            'channel' => 'sms',
            'code' => 'invoice_overdue_pay',
            'title' => '账单支付逾期',
            'content' => '您有一笔账单已过期,账单号{{invoiceid}},金额{{total}},请及时关注.',
        ],
        [
            'channel' => 'sms',
            'code' => 'submit_ticket',
            'title' => '提交工单',
            'content' => '您好,我们已经收到您提交的工单：{{subject}}.团队将火速处理您的问题.请耐心等待.',
        ],
        [
            'channel' => 'sms',
            'code' => 'ticket_reply',
            'title' => '工单回复',
            'content' => '您提交的工单{{subject}}有新的回复,请注意查收.',
        ],
        [
            'channel' => 'sms',
            'code' => 'product_pause',
            'title' => '产品暂停',
            'content' => '您好,您购买的产品{{product_name}}由于{{description}}的缘故,现已被暂停所有功能.如需恢复使用,请尽快处理.',
        ]
    ];

    foreach ($smsTemplates as $template) {
        Database::insert('notification_templates', $template);
        echo "  ✓ 已添加短信模板: {$template['title']}\n";
    }

    // 系统通知模板
    echo "\n=== 植入系统通知模板 ===\n";
    
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
            'code' => 'content_returned',
            'title' => '内容退回修改',
            'content' => '您提交的《{{content_title}}》需要修改，请根据以下要求进行调整：{{requirements}}。',
        ],
        [
            'channel' => 'system',
            'code' => 'membership_expired',
            'title' => '会员到期提醒',
            'content' => '您的会员资格将于{{days}}天后到期，请及时续费。',
        ],
        [
            'channel' => 'system',
            'code' => 'membership_renewed',
            'title' => '会员续费成功',
            'content' => '您的会员已成功续费{{days}}天，感谢您的支持！',
        ],
        [
            'channel' => 'system',
            'code' => 'ai_quota_warning',
            'title' => 'AI配额不足提醒',
            'content' => '您的AI创作配额剩余{{remaining}}次，请及时充值。',
        ],
        [
            'channel' => 'system',
            'code' => 'ai_quota_recharged',
            'title' => 'AI配额充值成功',
            'content' => '您已成功充值{{amount}}次AI创作配额，当前总配额：{{total}}次。',
        ],
        [
            'channel' => 'system',
            'code' => 'novel_chapter_update',
            'title' => '小说章节更新通知',
            'content' => '您订阅的《{{novel_title}}》已更新第{{chapter_number}}章：{{chapter_title}}',
        ],
        [
            'channel' => 'system',
            'code' => 'anime_episode_update',
            'title' => '动画剧集更新通知',
            'content' => '您关注的《{{anime_title}}》已更新第{{episode_number}}集：{{episode_title}}',
        ],
        [
            'channel' => 'system',
            'code' => 'music_work_published',
            'title' => '音乐作品发布通知',
            'content' => '您创作的音乐作品《{{music_title}}》已成功发布！',
        ],
        [
            'channel' => 'system',
            'code' => 'system_update',
            'title' => '系统更新通知',
            'content' => '系统已更新到{{version}}版本，新增功能：{{features}}',
        ],
        [
            'channel' => 'system',
            'code' => 'security_alert',
            'title' => '安全提醒',
            'content' => '检测到您的账户在{{location}}有异常登录，如非本人操作请及时修改密码。',
        ],
        [
            'channel' => 'system',
            'code' => 'activity_invitation',
            'title' => '活动邀请',
            'content' => '诚邀您参加{{activity_name}}活动，活动时间：{{activity_time}}，丰厚奖品等您来拿！',
        ],
        [
            'channel' => 'system',
            'code' => 'reward_received',
            'title' => '奖励到账通知',
            'content' => '恭喜您获得{{reward_name}}，已发放到您的账户，请查收。',
        ]
    ];

    foreach ($systemTemplates as $template) {
        Database::insert('notification_templates', $template);
        echo "  ✓ 已添加系统模板: {$template['title']}\n";
    }

    // 应用内通知模板
    echo "\n=== 植入应用内通知模板 ===\n";
    
    $appTemplates = [
        [
            'channel' => 'app',
            'code' => 'daily_checkin',
            'title' => '每日签到提醒',
            'content' => '今天还没有签到哦，连续签到可获得更多奖励！',
        ],
        [
            'channel' => 'app',
            'code' => 'creation_reminder',
            'title' => '创作提醒',
            'content' => '您已经{{days}}天没有创作了，灵感来了吗？',
        ],
        [
            'channel' => 'app',
            'code' => 'new_follower',
            'title' => '新粉丝通知',
            'content' => '恭喜！{{username}}关注了您，快去看看TA的作品吧。',
        ],
        [
            'channel' => 'app',
            'code' => 'work_liked',
            'title' => '作品获赞',
            'content' => '您的作品《{{work_title}}》获得了{{count}}个赞，继续加油！',
        ],
        [
            'channel' => 'app',
            'code' => 'comment_received',
            'title' => '收到评论',
            'content' => '{{username}}评论了您的作品：《{{work_title}}》',
        ],
        [
            'channel' => 'app',
            'code' => 'achievement_unlocked',
            'title' => '成就解锁',
            'content' => '恭喜您解锁成就：{{achievement_name}}！',
        ],
        [
            'channel' => 'app',
            'code' => 'level_up',
            'title' => '等级提升',
            'content' => '恭喜您升到{{level}}级，获得{{reward}}奖励！',
        ]
    ];

    foreach ($appTemplates as $template) {
        Database::insert('notification_templates', $template);
        echo "  ✓ 已添加应用内模板: {$template['title']}\n";
    }

    echo "\n[seed_notification_templates] 通知模板数据植入完成！\n";
    echo "总计植入：\n";
    echo "  - 邮件模板：" . count($emailTemplates) . " 个\n";
    echo "  - 短信模板：" . count($smsTemplates) . " 个\n";
    echo "  - 系统模板：" . count($systemTemplates) . " 个\n";
    echo "  - 应用内模板：" . count($appTemplates) . " 个\n";
    echo "  - 总计：" . (count($emailTemplates) + count($smsTemplates) + count($systemTemplates) + count($appTemplates)) . " 个模板\n";
    
} catch (\Throwable $e) {
    echo "[seed_notification_templates] 发生错误：{$e->getMessage()}\n";
    echo "错误位置：{$e->getFile()}:{$e->getLine()}\n";
    exit(1);
}

echo "[seed_notification_templates] OK\n";