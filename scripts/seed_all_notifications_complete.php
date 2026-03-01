<?php

// 通知系统完整数据植入脚本
// 为所有通知相关表植入示例数据
// 使用方式（在项目根目录执行）：
//   php scripts/seed_all_notifications_complete.php

require_once __DIR__ . '/../vendor/autoload.php';

use app\services\Database;
use app\models\NoticeBar;
use app\models\Announcement;

echo "[seed_all_notifications_complete] 开始植入所有通知系统数据...\n";

try {
    $pdo = Database::pdo();
    $prefix = Database::prefix();
    
    // 1. 植入通知栏数据 (notice_bar)
    echo "\n=== 1. 植入通知栏数据 (notice_bar) ===\n";
    
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
            'link' => '/music/project/list',
            'priority' => 85,
            'display_from' => date('Y-m-d H:i:s'),
            'display_to' => date('Y-m-d H:i:s', strtotime('+14 days')),
            'status' => 'enabled',
            'lang' => 'zh-CN',
        ],
        [
            'content' => '【活动通知】春季创作大赛开始报名，参与即有机会获得丰厚奖励！',
            'link' => '/crowdfunding',
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
            'link' => '/novel_creation/editor',
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
        ],
        [
            'content' => '【动漫制作】新增一键生成短剧功能，快速创作你的专属动画作品！',
            'link' => '/anime/project/quick_generate',
            'priority' => 72,
            'display_from' => date('Y-m-d H:i:s'),
            'display_to' => date('Y-m-d H:i:s', strtotime('+45 days')),
            'status' => 'enabled',
            'lang' => 'zh-CN',
        ],
        [
            'content' => '【存储优化】系统正在进行存储优化，部分过期缓存已清理。',
            'link' => '/storage',
            'priority' => 50,
            'display_from' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'display_to' => date('Y-m-d H:i:s', strtotime('+3 days')),
            'status' => 'enabled',
            'lang' => 'zh-CN',
        ],
    ];

    foreach ($noticeBarData as $notice) {
        try {
            NoticeBar::create($notice);
            echo "  ✓ 已添加通知栏: " . substr($notice['content'], 0, 40) . "...\n";
        } catch (\Exception $e) {
            echo "  ⚠ 添加通知栏失败: " . $e->getMessage() . "\n";
        }
    }

    // 2. 植入通知模板数据 (notification_templates)
    echo "\n=== 2. 植入通知模板数据 (notification_templates) ===\n";
    
    $emailTemplates = [
        [
            'channel' => 'email',
            'code' => 'welcome_email',
            'title' => '欢迎加入星夜阁',
            'content' => '<h2>欢迎加入星夜阁！</h2><p>感谢您注册星夜阁创作平台。我们致力于为您提供最好的AI辅助创作体验。</p><p>您可以：</p><ul><li>创作小说、音乐、动画等多种内容</li><li>使用AI助手提升创作效率</li><li>与其他创作者分享和交流</li></ul><p>开始您的创作之旅吧！</p>',
        ],
        [
            'channel' => 'email',
            'code' => 'password_reset',
            'title' => '密码重置',
            'content' => '<h2>密码重置请求</h2><p>您请求重置密码，请点击以下链接完成重置：</p><p><a href="{{reset_link}}">重置密码</a></p><p>链接有效期为30分钟。</p><p>如非本人操作，请忽略此邮件。</p>',
        ],
        [
            'channel' => 'email',
            'code' => 'account_suspended',
            'title' => '账户暂停通知',
            'content' => '<h2>账户暂停通知</h2><p>您的账户因违反平台规则已被暂停使用。</p><p>原因：{{reason}}</p><p>如有疑问，请联系客服。</p>',
        ],
        [
            'channel' => 'email',
            'code' => 'payment_success',
            'title' => '支付成功通知',
            'content' => '<h2>支付成功</h2><p>您的订单已支付成功！</p><p>订单号：{{order_id}}</p><p>金额：{{amount}}元</p><p>感谢您的支持！</p>',
        ],
        [
            'channel' => 'email',
            'code' => 'subscription_expired',
            'title' => '订阅到期提醒',
            'content' => '<h2>订阅即将到期</h2><p>您的会员订阅将于{{expire_date}}到期。</p><p>请及时续费以继续享受会员权益。</p><p><a href="{{renew_link}}">立即续费</a></p>',
        ],
        [
            'channel' => 'email',
            'code' => 'system_maintenance',
            'title' => '系统维护通知',
            'content' => '<h2>系统维护通知</h2><p>系统将于{{maintenance_time}}进行维护升级。</p><p>维护期间部分功能可能无法使用，请提前保存您的创作内容。</p><p>给您带来的不便敬请谅解。</p>',
        ],
        [
            'channel' => 'email',
            'code' => 'content_approved',
            'title' => '内容审核通过',
            'content' => '<h2>内容审核通过</h2><p>您提交的作品《{{content_title}}》已通过审核，现已发布。</p><p>您可以前往个人中心查看作品详情。</p>',
        ],
        [
            'channel' => 'email',
            'code' => 'content_rejected',
            'title' => '内容审核未通过',
            'content' => '<h2>内容审核未通过</h2><p>您提交的作品《{{content_title}}》未通过审核。</p><p>原因：{{reason}}</p><p>请修改后重新提交。</p>',
        ],
    ];

    foreach ($emailTemplates as $template) {
        try {
            // 检查是否已存在
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}notification_templates` WHERE channel = :channel AND code = :code");
            $stmt->execute([':channel' => $template['channel'], ':code' => $template['code']]);
            if ($stmt->fetchColumn() == 0) {
                Database::insert('notification_templates', $template);
                echo "  ✓ 已添加邮件模板: {$template['title']}\n";
            } else {
                echo "  - 邮件模板已存在: {$template['title']}\n";
            }
        } catch (\Exception $e) {
            echo "  ⚠ 添加邮件模板失败: " . $e->getMessage() . "\n";
        }
    }

    $smsTemplates = [
        [
            'channel' => 'sms',
            'code' => 'login_verification',
            'title' => '登录验证码',
            'content' => '您的登录验证码是：{{code}}，{{minutes}}分钟内有效。请勿泄露给他人。',
        ],
        [
            'channel' => 'sms',
            'code' => 'register_verify',
            'title' => '注册验证码',
            'content' => '您的注册验证码是：{{code}}，{{minutes}}分钟内有效。请勿泄露给他人。',
        ],
        [
            'channel' => 'sms',
            'code' => 'password_reset_sms',
            'title' => '密码重置验证码',
            'content' => '您的密码重置验证码是：{{code}}，{{minutes}}分钟内有效。如非本人操作，请忽略。',
        ],
        [
            'channel' => 'sms',
            'code' => 'payment_notification',
            'title' => '支付通知',
            'content' => '您已成功支付{{amount}}元，订单号：{{order_id}}。感谢您的支持！',
        ],
    ];

    foreach ($smsTemplates as $template) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}notification_templates` WHERE channel = :channel AND code = :code");
            $stmt->execute([':channel' => $template['channel'], ':code' => $template['code']]);
            if ($stmt->fetchColumn() == 0) {
                Database::insert('notification_templates', $template);
                echo "  ✓ 已添加短信模板: {$template['title']}\n";
            } else {
                echo "  - 短信模板已存在: {$template['title']}\n";
            }
        } catch (\Exception $e) {
            echo "  ⚠ 添加短信模板失败: " . $e->getMessage() . "\n";
        }
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
        ],
        [
            'channel' => 'system',
            'code' => 'novel_chapter_update',
            'title' => '小说章节更新通知',
            'content' => '您关注的小说《{{novel_title}}》已更新第{{chapter_number}}章。',
        ],
        [
            'channel' => 'system',
            'code' => 'anime_episode_update',
            'title' => '动画剧集更新通知',
            'content' => '您关注的动画《{{anime_title}}》已更新第{{episode_number}}集。',
        ],
        [
            'channel' => 'system',
            'code' => 'music_release',
            'title' => '音乐作品发布通知',
            'content' => '您关注的音乐人发布了新作品《{{music_title}}》。',
        ],
        [
            'channel' => 'system',
            'code' => 'system_update',
            'title' => '系统更新通知',
            'content' => '系统已更新至{{version}}版本，新增功能：{{features}}。',
        ],
        [
            'channel' => 'system',
            'code' => 'reward_received',
            'title' => '奖励到账通知',
            'content' => '恭喜！您获得了{{reward_amount}}星夜币奖励，已到账。',
        ],
    ];

    foreach ($systemTemplates as $template) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}notification_templates` WHERE channel = :channel AND code = :code");
            $stmt->execute([':channel' => $template['channel'], ':code' => $template['code']]);
            if ($stmt->fetchColumn() == 0) {
                Database::insert('notification_templates', $template);
                echo "  ✓ 已添加系统模板: {$template['title']}\n";
            } else {
                echo "  - 系统模板已存在: {$template['title']}\n";
            }
        } catch (\Exception $e) {
            echo "  ⚠ 添加系统模板失败: " . $e->getMessage() . "\n";
        }
    }

    // 3. 植入站内公告数据 (announcements)
    echo "\n=== 3. 植入站内公告数据 (announcements) ===\n";
    
    $announcements = [
        [
            'title' => '欢迎使用星夜阁创作平台',
            'content' => '<h2>欢迎使用星夜阁创作平台！</h2><p>我们致力于为您提供最好的AI辅助创作体验。在这里您可以：</p><ul><li>使用AI助手创作小说、音乐、动画等多种内容</li><li>享受智能续写、改写、润色等创作工具</li><li>与其他创作者分享和交流</li><li>参与各种创作活动和比赛</li></ul><p>开始您的创作之旅吧！如有任何问题，请随时联系我们的客服团队。</p>',
            'category' => 'system_update',
            'is_top' => 1,
            'is_popup' => 1,
            'status' => 1,
            'published_at' => date('Y-m-d H:i:s'),
        ],
        [
            'title' => 'AI音乐生成功能全面升级',
            'content' => '<h2>AI音乐生成功能全面升级！</h2><p>我们很高兴地宣布，AI音乐生成功能已全面升级。现在您可以：</p><ul><li>选择更多音乐风格（流行、摇滚、电子、古典等）</li><li>自定义节拍和调性</li><li>AI生成旋律和和弦</li><li>使用专业混音工具</li><li>一键生成音乐视频</li></ul><p>快来体验全新的音乐创作功能吧！</p>',
            'category' => 'system_update',
            'is_top' => 0,
            'is_popup' => 0,
            'status' => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ],
        [
            'title' => '春季创作大赛火热进行中',
            'content' => '<h2>春季创作大赛火热进行中！</h2><p>诚邀您参加我们的春季创作大赛，赢取丰厚奖励！</p><p><strong>比赛类别：</strong></p><ul><li>最佳小说奖</li><li>最佳音乐作品奖</li><li>最佳动画短片奖</li></ul><p><strong>奖品池：</strong>100,000 星夜币</p><p><strong>报名截止：</strong>2024年3月31日</p><p>点击活动页面了解更多详情和报名方式。</p>',
            'category' => 'activity_notice',
            'is_top' => 1,
            'is_popup' => 0,
            'status' => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
        ],
        [
            'title' => '系统维护通知',
            'content' => '<h2>系统维护通知</h2><p>我们将进行定期系统维护：</p><p><strong>维护时间：</strong>每周二凌晨 2:00 - 4:00 (UTC+8)</p><p>维护期间，部分功能可能暂时无法使用。请提前保存您的创作内容，给您带来的不便敬请谅解。</p><p>如有紧急问题，请联系客服。</p>',
            'category' => 'maintenance',
            'is_top' => 0,
            'is_popup' => 0,
            'status' => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
        ],
        [
            'title' => '小说编辑器新增自动保存功能',
            'content' => '<h2>小说编辑器新增自动保存功能</h2><p>我们很高兴地宣布，小说编辑器现已支持自动保存功能！</p><p><strong>新功能特点：</strong></p><ul><li>每30秒自动保存一次</li><li>意外关闭浏览器后内容不丢失</li><li>支持多版本历史记录</li><li>一键恢复历史版本</li></ul><p>再也不用担心丢失创作内容了！快去体验吧。</p>',
            'category' => 'system_update',
            'is_top' => 0,
            'is_popup' => 0,
            'status' => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        ],
        [
            'title' => 'VIP会员权益升级',
            'content' => '<h2>VIP会员权益升级</h2><p>VIP会员现在可以享受更多权益：</p><ul><li>无限AI创作次数</li><li>优先审核服务</li><li>专属客服支持</li><li>免费云存储空间升级至100GB</li><li>专属创作模板和素材库</li></ul><p>快来升级成为VIP会员，享受更多创作便利！</p>',
            'category' => 'activity_notice',
            'is_top' => 1,
            'is_popup' => 0,
            'status' => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
        ],
        [
            'title' => '动漫制作一键生成短剧功能上线',
            'content' => '<h2>动漫制作一键生成短剧功能上线</h2><p>我们推出了全新的短剧快速生成功能，让您能够快速创作专属动画作品！</p><p><strong>功能特点：</strong></p><ul><li>输入剧本即可自动生成分镜</li><li>AI自动生成角色和场景</li><li>一键生成完整短剧视频</li><li>支持多种动画风格</li></ul><p>快来体验全新的短剧创作功能吧！</p>',
            'category' => 'system_update',
            'is_top' => 0,
            'is_popup' => 0,
            'status' => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
        ],
        [
            'title' => '存储空间优化完成',
            'content' => '<h2>存储空间优化完成</h2><p>我们已完成系统存储空间优化，清理了部分过期缓存文件。</p><p>优化后，您的云存储空间使用更加高效，上传和下载速度也有所提升。</p><p>如有任何问题，请前往云存储空间页面查看详情。</p>',
            'category' => 'system_update',
            'is_top' => 0,
            'is_popup' => 0,
            'status' => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ],
    ];

    foreach ($announcements as $announcement) {
        try {
            Announcement::create($announcement);
            echo "  ✓ 已添加公告: {$announcement['title']}\n";
        } catch (\Exception $e) {
            echo "  ⚠ 添加公告失败: " . $e->getMessage() . "\n";
        }
    }

    // 4. 植入用户通知数据 (user_notifications) - 如果表存在
    echo "\n=== 4. 植入用户通知数据 (user_notifications) ===\n";
    
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE '{$prefix}user_notifications'");
        $stmt->execute();
        $tableExists = $stmt->rowCount() > 0;
        
        if ($tableExists) {
            // 获取用户ID
            $stmt = $pdo->prepare("SELECT id FROM `{$prefix}users` LIMIT 10");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($users)) {
                foreach ($users as $index => $userId) {
                    // 系统通知
                    $systemNotifications = [
                        [
                            'user_id' => $userId,
                            'type' => 'system',
                            'category' => 'general',
                            'title' => '欢迎使用星夜阁',
                            'content' => '感谢您注册星夜阁！我们是一个专注于AI创作的平台，您可以在这里创作小说、音乐、动画等多种内容。',
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
                    ];
                    
                    foreach ($systemNotifications as $notification) {
                        try {
                            Database::insert('user_notifications', $notification);
                            echo "  ✓ 已添加用户通知 (用户ID: {$userId}): {$notification['title']}\n";
                        } catch (\Exception $e) {
                            // 忽略重复插入错误
                            if (strpos($e->getMessage(), 'Duplicate') === false) {
                                echo "  ⚠ 添加用户通知失败: " . $e->getMessage() . "\n";
                            }
                        }
                    }
                }
            } else {
                echo "  - 未找到用户，跳过用户通知数据植入\n";
            }
        } else {
            echo "  - user_notifications 表不存在，跳过用户通知数据植入\n";
        }
    } catch (\Exception $e) {
        echo "  ⚠ 检查用户通知表失败: " . $e->getMessage() . "\n";
    }

    // 统计插入的数据
    echo "\n=== 数据统计 ===\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}notice_bar`");
    $stmt->execute();
    $noticeBarCount = $stmt->fetchColumn();
    echo "通知栏记录数: {$noticeBarCount}\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}notification_templates`");
    $stmt->execute();
    $templateCount = $stmt->fetchColumn();
    echo "通知模板数: {$templateCount}\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}announcements`");
    $stmt->execute();
    $announcementCount = $stmt->fetchColumn();
    echo "公告数: {$announcementCount}\n";
    
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE '{$prefix}user_notifications'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}user_notifications`");
            $stmt->execute();
            $userNotificationCount = $stmt->fetchColumn();
            echo "用户通知数: {$userNotificationCount}\n";
        }
    } catch (\Exception $e) {
        // 忽略错误
    }

    echo "\n[seed_all_notifications_complete] 所有通知数据植入完成！\n";
    
} catch (\Throwable $e) {
    echo "\n[seed_all_notifications_complete] 发生错误：{$e->getMessage()}\n";
    echo "错误位置：{$e->getFile()}:{$e->getLine()}\n";
    exit(1);
}

echo "[seed_all_notifications_complete] OK\n";
