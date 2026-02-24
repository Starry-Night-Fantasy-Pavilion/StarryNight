<?php

declare(strict_types=1);

namespace app\services;

/**
 * 队列服务
 * 
 * 简化消息队列的使用，提供便捷的静态方法
 * 
 * @package app\services
 */
class QueueService
{
    /**
     * 默认队列名称
     */
    const DEFAULT_QUEUE = 'default';

    /**
     * 队列优先级
     */
    const PRIORITY_HIGH = 'high';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_LOW = 'low';

    /**
     * 推送任务到队列
     *
     * @param string $jobClass 任务类名
     * @param array $data 任务数据
     * @param string $queue 队列名称
     * @param int $delay 延迟秒数
     * @return string|null 任务ID
     */
    public static function push(string $jobClass, array $data = [], string $queue = self::DEFAULT_QUEUE, int $delay = 0): ?string
    {
        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();
            
            $jobId = self::generateJobId();
            $payload = json_encode([
                'job' => $jobClass,
                'data' => $data,
                'max_tries' => 3,
                'timeout' => 60,
            ], JSON_UNESCAPED_UNICODE);

            $availableAt = $delay > 0 ? date('Y-m-d H:i:s', time() + $delay) : date('Y-m-d H:i:s');

            $sql = "INSERT INTO `{$prefix}jobs` (id, queue, payload, attempts, available_at, created_at) 
                    VALUES (?, ?, ?, 0, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$jobId, $queue, $payload, $availableAt]);

            return $jobId;
        } catch (\Throwable $e) {
            error_log("队列推送失败: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 推送高优先级任务
     *
     * @param string $jobClass 任务类名
     * @param array $data 任务数据
     * @return string|null
     */
    public static function highPriority(string $jobClass, array $data = []): ?string
    {
        return self::push($jobClass, $data, self::PRIORITY_HIGH);
    }

    /**
     * 推送低优先级任务
     *
     * @param string $jobClass 任务类名
     * @param array $data 任务数据
     * @return string|null
     */
    public static function lowPriority(string $jobClass, array $data = []): ?string
    {
        return self::push($jobClass, $data, self::PRIORITY_LOW);
    }

    /**
     * 延迟推送任务
     *
     * @param int $delay 延迟秒数
     * @param string $jobClass 任务类名
     * @param array $data 任务数据
     * @param string $queue 队列名称
     * @return string|null
     */
    public static function later(int $delay, string $jobClass, array $data = [], string $queue = self::DEFAULT_QUEUE): ?string
    {
        return self::push($jobClass, $data, $queue, $delay);
    }

    /**
     * 发送邮件任务
     *
     * @param string $to 收件人
     * @param string $subject 主题
     * @param string $content 内容
     * @param int $delay 延迟秒数
     * @return string|null
     */
    public static function sendEmail(string $to, string $subject, string $content, int $delay = 0): ?string
    {
        return self::push(\app\jobs\SendEmailJob::class, [
            'to' => $to,
            'subject' => $subject,
            'content' => $content,
        ], self::DEFAULT_QUEUE, $delay);
    }

    /**
     * 发送欢迎邮件
     *
     * @param string $email 用户邮箱
     * @param string $username 用户名
     * @return string|null
     */
    public static function sendWelcomeEmail(string $email, string $username): ?string
    {
        $siteName = get_env('APP_NAME', '星夜阁');
        $subject = "欢迎注册 {$siteName}";
        $content = self::renderTemplate('welcome', [
            'username' => $username,
            'site_name' => $siteName,
        ]);

        return self::sendEmail($email, $subject, $content);
    }

    /**
     * 发送密码重置邮件
     *
     * @param string $email 用户邮箱
     * @param string $resetLink 重置链接
     * @return string|null
     */
    public static function sendPasswordResetEmail(string $email, string $resetLink): ?string
    {
        $siteName = get_env('APP_NAME', '星夜阁');
        $subject = "【{$siteName}】密码重置";
        $content = self::renderTemplate('password_reset', [
            'reset_link' => $resetLink,
            'site_name' => $siteName,
        ]);

        return self::sendEmail($email, $subject, $content);
    }

    /**
     * 发送通知邮件
     *
     * @param int $userId 用户ID
     * @param string $title 标题
     * @param string $message 消息内容
     * @return string|null
     */
    public static function sendNotification(int $userId, string $title, string $message): ?string
    {
        // 获取用户邮箱
        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();
            
            $sql = "SELECT email, username FROM `{$prefix}users` WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$user || empty($user['email'])) {
                return null;
            }

            $siteName = get_env('APP_NAME', '星夜阁');
            $subject = "【{$siteName}】{$title}";
            $content = self::renderTemplate('notification', [
                'username' => $user['username'],
                'title' => $title,
                'message' => $message,
                'site_name' => $siteName,
            ]);

            return self::sendEmail($user['email'], $subject, $content);
        } catch (\Throwable $e) {
            error_log("发送通知失败: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 获取队列统计信息
     *
     * @return array
     */
    public static function stats(): array
    {
        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();

            $sql = "SELECT queue, COUNT(*) as count, 
                    SUM(CASE WHEN available_at > NOW() THEN 1 ELSE 0 END) as delayed,
                    SUM(CASE WHEN attempts > 0 THEN 1 ELSE 0 END) as retried
                    FROM `{$prefix}jobs` 
                    GROUP BY queue";
            
            $stmt = $pdo->query($sql);
            $stats = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return [
                'queues' => $stats,
                'total' => array_sum(array_column($stats, 'count')),
            ];
        } catch (\Throwable $e) {
            return [
                'queues' => [],
                'total' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 清理已完成的任务
     *
     * @param int $days 保留天数
     * @return int 删除的记录数
     */
    public static function cleanup(int $days = 7): int
    {
        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();

            $sql = "DELETE FROM `{$prefix}jobs` 
                    WHERE reserved_at IS NOT NULL 
                    AND reserved_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$days]);

            return $stmt->rowCount();
        } catch (\Throwable $e) {
            error_log("队列清理失败: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 生成任务ID
     *
     * @return string
     */
    private static function generateJobId(): string
    {
        return uniqid('job_', true) . '_' . bin2hex(random_bytes(4));
    }

    /**
     * 渲染邮件模板
     *
     * @param string $template 模板名称
     * @param array $data 模板数据
     * @return string
     */
    private static function renderTemplate(string $template, array $data = []): string
    {
        $templatePath = __DIR__ . '/../views/emails/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            // 返回简单的文本内容
            return self::renderSimpleTemplate($template, $data);
        }

        extract($data);
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    /**
     * 渲染简单模板
     *
     * @param string $template 模板名称
     * @param array $data 模板数据
     * @return string
     */
    private static function renderSimpleTemplate(string $template, array $data): string
    {
        $siteName = $data['site_name'] ?? get_env('APP_NAME', '星夜阁');
        
        switch ($template) {
            case 'welcome':
                $username = $data['username'] ?? '用户';
                return "亲爱的 {$username}，\n\n欢迎注册 {$siteName}！\n\n我们很高兴您加入我们的大家庭。\n\n祝您使用愉快！\n\n{$siteName} 团队";

            case 'password_reset':
                $resetLink = $data['reset_link'] ?? '#';
                return "您好，\n\n您收到这封邮件是因为您请求重置密码。\n\n请点击以下链接重置密码：\n{$resetLink}\n\n如果您没有请求重置密码，请忽略此邮件。\n\n{$siteName} 团队";

            case 'notification':
                $username = $data['username'] ?? '用户';
                $title = $data['title'] ?? '通知';
                $message = $data['message'] ?? '';
                return "亲爱的 {$username}，\n\n{$title}\n\n{$message}\n\n{$siteName} 团队";

            default:
                return '';
        }
    }
}
