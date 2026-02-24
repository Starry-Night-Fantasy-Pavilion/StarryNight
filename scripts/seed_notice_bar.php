<?php

// 一次性脚本：向通知栏表植入示例数据
// 使用方式（在项目根目录执行）：
//   php scripts/seed_notice_bar.php

require_once __DIR__ . '/../vendor/autoload.php';

use app\services\Database;

echo "[seed_notice_bar] 开始插入示例通知...\n";

try {
    // 高优先级：重要通知
    Database::insert('notice_bar', [
        'content'      => '【系统维护公告】今晚 23:00 - 24:00 将进行系统升级，期间部分功能可能短暂不可用。',
        'link'         => '/announcement',
        'priority'     => 90,
        'display_from' => date('Y-m-d H:i:s'),
        'display_to'   => date('Y-m-d H:i:s', strtotime('+7 days')),
        'status'       => 'enabled',
        'lang'         => 'zh-CN',
    ]);

    // 中优先级：提醒
    Database::insert('notice_bar', [
        'content'      => '建议完善个人资料并绑定邮箱，以便在密码找回和安全通知时使用。',
        'link'         => '/user_center/profile',
        'priority'     => 60,
        'display_from' => date('Y-m-d H:i:s'),
        'display_to'   => date('Y-m-d H:i:s', strtotime('+30 days')),
        'status'       => 'enabled',
        'lang'         => 'zh-CN',
    ]);

    // 低优先级：提示
    Database::insert('notice_bar', [
        'content'      => '每天登录可同步作品进度，记得常回来看看你的创作哦～',
        'link'         => '/novel',
        'priority'     => 10,
        'display_from' => date('Y-m-d H:i:s'),
        'display_to'   => date('Y-m-d H:i:s', strtotime('+365 days')),
        'status'       => 'enabled',
        'lang'         => 'zh-CN',
    ]);

    echo "[seed_notice_bar] 插入完成。\n";
} catch (\Throwable $e) {
    echo "[seed_notice_bar] 发生错误：{$e->getMessage()}\n";
    exit(1);
}

echo "[seed_notice_bar] OK\n";

