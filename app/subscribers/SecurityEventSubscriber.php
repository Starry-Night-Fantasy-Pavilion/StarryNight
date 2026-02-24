<?php

declare(strict_types=1);

namespace app\subscribers;

use app\services\SystemEvents;
use app\services\Database;

/**
 * 安全事件订阅者
 * 
 * 监听安全相关事件并执行相应操作
 * 
 * @package app\subscribers
 */
class SecurityEventSubscriber
{
    /**
     * 登录失败最大次数
     */
    const MAX_LOGIN_FAILURES = 5;

    /**
     * IP封禁时间（秒）
     */
    const IP_BAN_DURATION = 3600;

    /**
     * 获取订阅的事件
     *
     * @return array
     */
    public function getSubscribedEvents(): array
    {
        return [
            SystemEvents::SECURITY_LOGIN_FAILED => 'onLoginFailed',
            SystemEvents::SECURITY_SUSPICIOUS_ACTIVITY => 'onSuspiciousActivity',
            SystemEvents::SECURITY_IP_BLOCKED => 'onIpBlocked',
            SystemEvents::SECURITY_CSRF_FAILED => 'onCsrfFailed',
            SystemEvents::SECURITY_XSS_DETECTED => 'onXssDetected',
        ];
    }

    /**
     * 登录失败事件处理
     *
     * @param array $data 事件数据
     * @return void
     */
    public function onLoginFailed(array $data): void
    {
        $ip = $data['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $username = $data['username'] ?? 'unknown';
        $reason = $data['reason'] ?? 'unknown';

        // 记录登录失败日志
        $this->recordFailedLogin($ip, $username, $reason);

        // 检查是否需要封禁IP
        $failCount = $this->getFailedLoginCount($ip);
        if ($failCount >= self::MAX_LOGIN_FAILURES) {
            $this->banIp($ip, '登录失败次数过多');
        }

        error_log("登录失败: IP={$ip}, 用户={$username}, 原因={$reason}, 次数={$failCount}");
    }

    /**
     * 可疑活动事件处理
     *
     * @param array $data 事件数据
     * @return void
     */
    public function onSuspiciousActivity(array $data): void
    {
        $ip = $data['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $type = $data['type'] ?? 'unknown';
        $details = $data['details'] ?? '';

        // 记录可疑活动
        $this->recordSuspiciousActivity($ip, $type, $details);

        // 发送管理员通知（如果配置了）
        $this->notifyAdmins('可疑活动检测', "IP: {$ip}, 类型: {$type}, 详情: {$details}");

        error_log("可疑活动: IP={$ip}, 类型={$type}, 详情={$details}");
    }

    /**
     * IP封禁事件处理
     *
     * @param array $data 事件数据
     * @return void
     */
    public function onIpBlocked(array $data): void
    {
        $ip = $data['ip'] ?? 'unknown';
        $reason = $data['reason'] ?? 'unknown';
        $duration = $data['duration'] ?? self::IP_BAN_DURATION;

        // 记录IP封禁
        $this->recordIpBan($ip, $reason, $duration);

        // 发送管理员通知
        $this->notifyAdmins('IP已封禁', "IP: {$ip}, 原因: {$reason}, 时长: {$duration}秒");

        error_log("IP封禁: IP={$ip}, 原因={$reason}, 时长={$duration}秒");
    }

    /**
     * CSRF验证失败事件处理
     *
     * @param array $data 事件数据
     * @return void
     */
    public function onCsrfFailed(array $data): void
    {
        $ip = $data['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $url = $_SERVER['REQUEST_URI'] ?? 'unknown';

        // 记录CSRF失败
        $this->recordSecurityEvent('csrf_failed', $ip, [
            'url' => $url,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]);

        error_log("CSRF验证失败: IP={$ip}, URL={$url}");
    }

    /**
     * XSS检测事件处理
     *
     * @param array $data 事件数据
     * @return void
     */
    public function onXssDetected(array $data): void
    {
        $ip = $data['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $source = $data['source'] ?? 'unknown';
        $input = $data['input'] ?? '';

        // 记录XSS检测
        $this->recordSecurityEvent('xss_detected', $ip, [
            'source' => $source,
            'input_preview' => substr($input, 0, 200),
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        ]);

        error_log("XSS检测: IP={$ip}, 来源={$source}");
    }

    /**
     * 记录登录失败
     *
     * @param string $ip IP地址
     * @param string $username 用户名
     * @param string $reason 原因
     * @return void
     */
    protected function recordFailedLogin(string $ip, string $username, string $reason): void
    {
        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();

            $sql = "INSERT INTO `{$prefix}login_logs` (ip, username, status, reason, login_at) 
                    VALUES (?, ?, 'failed', ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$ip, $username, $reason]);
        } catch (\Throwable $e) {
            error_log("记录登录失败日志失败: " . $e->getMessage());
        }
    }

    /**
     * 获取登录失败次数
     *
     * @param string $ip IP地址
     * @return int
     */
    protected function getFailedLoginCount(string $ip): int
    {
        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();

            // 统计最近1小时内的失败次数
            $sql = "SELECT COUNT(*) FROM `{$prefix}login_logs` 
                    WHERE ip = ? AND status = 'failed' AND login_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$ip]);
            
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 封禁IP
     *
     * @param string $ip IP地址
     * @param string $reason 原因
     * @return void
     */
    protected function banIp(string $ip, string $reason): void
    {
        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();

            $sql = "INSERT INTO `{$prefix}ip_bans` (ip, reason, banned_at, expires_at) 
                    VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND))
                    ON DUPLICATE KEY UPDATE 
                    reason = VALUES(reason), 
                    banned_at = NOW(), 
                    expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$ip, $reason, self::IP_BAN_DURATION, self::IP_BAN_DURATION]);
        } catch (\Throwable $e) {
            error_log("封禁IP失败: " . $e->getMessage());
        }
    }

    /**
     * 记录可疑活动
     *
     * @param string $ip IP地址
     * @param string $type 类型
     * @param string $details 详情
     * @return void
     */
    protected function recordSuspiciousActivity(string $ip, string $type, string $details): void
    {
        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();

            $sql = "INSERT INTO `{$prefix}security_logs` (ip, type, details, user_agent, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $ip,
                $type,
                $details,
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            ]);
        } catch (\Throwable $e) {
            error_log("记录可疑活动失败: " . $e->getMessage());
        }
    }

    /**
     * 记录IP封禁
     *
     * @param string $ip IP地址
     * @param string $reason 原因
     * @param int $duration 时长（秒）
     * @return void
     */
    protected function recordIpBan(string $ip, string $reason, int $duration): void
    {
        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();

            $sql = "INSERT INTO `{$prefix}ip_ban_history` (ip, reason, duration, banned_at, created_at) 
                    VALUES (?, ?, ?, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$ip, $reason, $duration]);
        } catch (\Throwable $e) {
            error_log("记录IP封禁历史失败: " . $e->getMessage());
        }
    }

    /**
     * 记录安全事件
     *
     * @param string $event 事件类型
     * @param string $ip IP地址
     * @param array $data 事件数据
     * @return void
     */
    protected function recordSecurityEvent(string $event, string $ip, array $data): void
    {
        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();

            $sql = "INSERT INTO `{$prefix}security_logs` (ip, type, details, user_agent, created_at) 
                    VALUES ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $ip,
                $event,
                json_encode($data, JSON_UNESCAPED_UNICODE),
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            ]);
        } catch (\Throwable $e) {
            error_log("记录安全事件失败: " . $e->getMessage());
        }
    }

    /**
     * 通知管理员
     *
     * @param string $title 标题
     * @param string $message 消息
     * @return void
     */
    protected function notifyAdmins(string $title, string $message): void
    {
        // 可以通过邮件、站内信等方式通知管理员
        // 这里暂时只记录日志
        error_log("[管理员通知] {$title}: {$message}");
    }
}
