<?php

declare(strict_types=1);

namespace app\subscribers;

use app\services\EventService;
use app\services\QueueService;
use app\services\SystemEvents;
use app\services\Database;

/**
 * 用户事件订阅者
 * 
 * 监听用户相关事件并执行相应操作
 * 
 * @package app\subscribers
 */
class UserEventSubscriber
{
    /**
     * 获取订阅的事件
     *
     * @return array
     */
    public function getSubscribedEvents(): array
    {
        return [
            SystemEvents::USER_REGISTERED => 'onUserRegistered',
            SystemEvents::USER_LOGIN => 'onUserLogin',
            SystemEvents::USER_LOGOUT => 'onUserLogout',
            SystemEvents::USER_UPDATED => 'onUserUpdated',
            SystemEvents::USER_DELETED => 'onUserDeleted',
            SystemEvents::USER_PASSWORD_RESET => 'onPasswordReset',
            SystemEvents::USER_EMAIL_VERIFIED => 'onEmailVerified',
        ];
    }

    /**
     * 用户注册事件处理
     *
     * @param array $data 用户数据
     * @return void
     */
    public function onUserRegistered(array $data): void
    {
        $userId = $data['id'] ?? 0;
        $email = $data['email'] ?? '';
        $username = $data['username'] ?? '';

        // 记录日志
        error_log("用户注册: {$username} ({$email})");

        // 发送欢迎邮件（异步队列）
        if (!empty($email)) {
            QueueService::sendWelcomeEmail($email, $username);
        }

        // 初始化用户数据
        $this->initializeUserData($userId);

        // 记录注册统计
        $this->recordRegistrationStats($userId);
    }

    /**
     * 用户登录事件处理
     *
     * @param array $data 用户数据
     * @return void
     */
    public function onUserLogin(array $data): void
    {
        $userId = $data['id'] ?? 0;
        $username = $data['username'] ?? '';

        // 记录日志
        error_log("用户登录: {$username}");

        // 更新最后登录时间
        $this->updateLastLogin($userId);

        // 记录登录日志
        $this->recordLoginLog($userId, $data);
    }

    /**
     * 用户登出事件处理
     *
     * @param array $data 用户数据
     * @return void
     */
    public function onUserLogout(array $data): void
    {
        $userId = $data['user_id'] ?? 0;
        error_log("用户登出: ID={$userId}");
    }

    /**
     * 用户更新事件处理
     *
     * @param array $data 用户数据
     * @return void
     */
    public function onUserUpdated(array $data): void
    {
        $userId = $data['user_id'] ?? 0;
        $changes = $data['changes'] ?? [];
        
        error_log("用户信息更新: ID={$userId}, 变更: " . implode(',', array_keys($changes)));
    }

    /**
     * 用户删除事件处理
     *
     * @param array $data 用户数据
     * @return void
     */
    public function onUserDeleted(array $data): void
    {
        $userId = $data['id'] ?? 0;
        $username = $data['username'] ?? '';

        error_log("用户删除: {$username} (ID={$userId})");

        // 清理用户相关数据
        $this->cleanupUserData($userId);
    }

    /**
     * 密码重置事件处理
     *
     * @param array $data 用户数据
     * @return void
     */
    public function onPasswordReset(array $data): void
    {
        $email = $data['email'] ?? '';
        $resetLink = $data['reset_link'] ?? '';

        if (!empty($email) && !empty($resetLink)) {
            QueueService::sendPasswordResetEmail($email, $resetLink);
        }
    }

    /**
     * 邮箱验证事件处理
     *
     * @param array $data 用户数据
     * @return void
     */
    public function onEmailVerified(array $data): void
    {
        $userId = $data['user_id'] ?? 0;
        
        // 更新用户状态
        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();
            
            $sql = "UPDATE `{$prefix}users` SET email_verified = 1, email_verified_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        } catch (\Throwable $e) {
            error_log("更新邮箱验证状态失败: " . $e->getMessage());
        }
    }

    /**
     * 初始化用户数据
     *
     * @param int $userId 用户ID
     * @return void
     */
    protected function initializeUserData(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();

            // 初始化用户Token余额
            $sql = "INSERT IGNORE INTO `{$prefix}user_token_balance` (user_id, balance, created_at) VALUES (?, 0, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);

            // 初始化用户限制
            $sql = "INSERT IGNORE INTO `{$prefix}user_limits` (user_id, created_at) VALUES (?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);

            // 赠送新用户初始Token（可配置）
            $initialTokens = (int)get_env('INITIAL_TOKENS', 100);
            if ($initialTokens > 0) {
                $sql = "UPDATE `{$prefix}user_token_balance` SET balance = balance + ? WHERE user_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$initialTokens, $userId]);

                // 记录赠送记录
                $sql = "INSERT INTO `{$prefix}token_consumption_records` (user_id, amount, type, description, created_at) 
                        VALUES (?, ?, 'gift', '新用户注册赠送', NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$userId, $initialTokens]);
            }
        } catch (\Throwable $e) {
            error_log("初始化用户数据失败: " . $e->getMessage());
        }
    }

    /**
     * 更新最后登录时间
     *
     * @param int $userId 用户ID
     * @return void
     */
    protected function updateLastLogin(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();

            $sql = "UPDATE `{$prefix}users` SET last_login_at = NOW(), login_count = login_count + 1 WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        } catch (\Throwable $e) {
            error_log("更新登录时间失败: " . $e->getMessage());
        }
    }

    /**
     * 记录登录日志
     *
     * @param int $userId 用户ID
     * @param array $data 登录数据
     * @return void
     */
    protected function recordLoginLog(int $userId, array $data): void
    {
        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();

            $sql = "INSERT INTO `{$prefix}login_logs` (user_id, ip, user_agent, login_at) VALUES (?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $userId,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            ]);
        } catch (\Throwable $e) {
            error_log("记录登录日志失败: " . $e->getMessage());
        }
    }

    /**
     * 记录注册统计
     *
     * @param int $userId 用户ID
     * @return void
     */
    protected function recordRegistrationStats(int $userId): void
    {
        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();

            $date = date('Y-m-d');
            $sql = "INSERT INTO `{$prefix}user_statistics` (stat_date, new_users, created_at) 
                    VALUES (?, 1, NOW()) 
                    ON DUPLICATE KEY UPDATE new_users = new_users + 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$date]);
        } catch (\Throwable $e) {
            error_log("记录注册统计失败: " . $e->getMessage());
        }
    }

    /**
     * 清理用户数据
     *
     * @param int $userId 用户ID
     * @return void
     */
    protected function cleanupUserData(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();

            // 软删除用户的小说
            $sql = "UPDATE `{$prefix}novels` SET deleted_at = NOW() WHERE user_id = ? AND deleted_at IS NULL";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);

            // 可以添加更多清理逻辑
        } catch (\Throwable $e) {
            error_log("清理用户数据失败: " . $e->getMessage());
        }
    }
}
