<?php

namespace plugins\apps\my_app\Services;

use PDO;
use PDOException;

/**
 * 章节订阅服务
 * 提供章节订阅、更新通知、订阅管理等功能
 */
class ChapterSubscriptionService
{
    protected ?PDO $db = null;
    protected string $db_prefix = '';

    public function __construct()
    {
        $this->db_prefix = (string)get_env('DB_PREFIX', 'sn_');

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                get_env('DB_HOST'),
                (int)get_env('DB_PORT', 3306),
                get_env('DB_DATABASE')
            );
            $this->db = new PDO(
                $dsn,
                get_env('DB_USERNAME'),
                get_env('DB_PASSWORD'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new \RuntimeException('数据库连接失败');
        }
    }

    protected function getTableName(string $name): string
    {
        return '`' . $this->db_prefix . $name . '`';
    }

    /**
     * 订阅书籍
     *
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @param bool $notifyOnUpdate 更新时是否通知
     * @return int|false 订阅ID或false
     */
    public function subscribe(int $userId, int $bookId, bool $notifyOnUpdate = true)
    {
        // 检查是否已订阅
        $existing = $this->getSubscription($userId, $bookId);
        if ($existing) {
            // 更新订阅设置
            $sql = "UPDATE " . $this->getTableName('book_subscriptions') . " 
                    SET notify_on_update = :notify, updated_at = NOW() 
                    WHERE user_id = :user_id AND book_id = :book_id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':notify' => $notifyOnUpdate ? 1 : 0,
                ':user_id' => $userId,
                ':book_id' => $bookId,
            ]);
            return $existing['id'];
        }

        // 创建新订阅
        $sql = "INSERT INTO " . $this->getTableName('book_subscriptions') . " 
                (user_id, book_id, notify_on_update, created_at) 
                VALUES (:user_id, :book_id, :notify, NOW())";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':user_id' => $userId,
            ':book_id' => $bookId,
            ':notify' => $notifyOnUpdate ? 1 : 0,
        ]);

        if ($result) {
            return $this->db->lastInsertId();
        }

        return false;
    }

    /**
     * 取消订阅
     *
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @return bool
     */
    public function unsubscribe(int $userId, int $bookId): bool
    {
        $sql = "DELETE FROM " . $this->getTableName('book_subscriptions') . " 
                WHERE user_id = :user_id AND book_id = :book_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':book_id' => $bookId,
        ]);
    }

    /**
     * 获取订阅信息
     *
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @return array|null
     */
    public function getSubscription(int $userId, int $bookId): ?array
    {
        $sql = "SELECT * FROM " . $this->getTableName('book_subscriptions') . "
                WHERE user_id = :user_id AND book_id = :book_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':book_id' => $bookId,
        ]);

        $subscription = $stmt->fetch();
        return $subscription ?: null;
    }

    /**
     * 获取用户订阅列表
     *
     * @param int $userId 用户ID
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function getUserSubscriptions(int $userId, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        $sql = "SELECT s.*, b.title, b.author, b.cover_image, b.status as book_status,
                       (SELECT COUNT(*) FROM " . $this->getTableName('chapters') . " 
                        WHERE book_id = b.id AND created_at > s.last_notified_at) as new_chapters
                FROM " . $this->getTableName('book_subscriptions') . " s
                LEFT JOIN " . $this->getTableName('books') . " b ON s.book_id = b.id
                WHERE s.user_id = :user_id
                ORDER BY s.updated_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * 检查是否有新章节
     *
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @return array 新章节列表
     */
    public function checkNewChapters(int $userId, int $bookId): array
    {
        $subscription = $this->getSubscription($userId, $bookId);
        if (!$subscription) {
            return [];
        }

        $lastNotifiedAt = $subscription['last_notified_at'] ?? $subscription['created_at'];

        $sql = "SELECT * FROM " . $this->getTableName('chapters') . "
                WHERE book_id = :book_id AND created_at > :last_notified
                ORDER BY chapter_number ASC, created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':book_id' => $bookId,
            ':last_notified' => $lastNotifiedAt,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * 标记章节已通知
     *
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @return bool
     */
    public function markAsNotified(int $userId, int $bookId): bool
    {
        $sql = "UPDATE " . $this->getTableName('book_subscriptions') . " 
                SET last_notified_at = NOW() 
                WHERE user_id = :user_id AND book_id = :book_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':book_id' => $bookId,
        ]);
    }

    /**
     * 发送更新通知（当有新章节时）
     *
     * @param int $bookId 书籍ID
     * @param int $chapterId 新章节ID
     * @return int 通知发送数量
     */
    public function sendUpdateNotifications(int $bookId, int $chapterId): int
    {
        // 获取需要通知的订阅用户
        $sql = "SELECT s.user_id, u.email, u.username
                FROM " . $this->getTableName('book_subscriptions') . " s
                LEFT JOIN " . $this->getTableName('users') . " u ON s.user_id = u.id
                WHERE s.book_id = :book_id AND s.notify_on_update = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':book_id' => $bookId]);
        $subscribers = $stmt->fetchAll();

        // 获取章节信息
        $chapterSql = "SELECT * FROM " . $this->getTableName('chapters') . " WHERE id = :id";
        $chapterStmt = $this->db->prepare($chapterSql);
        $chapterStmt->execute([':id' => $chapterId]);
        $chapter = $chapterStmt->fetch();

        if (!$chapter) {
            return 0;
        }

        // 获取书籍信息
        $bookSql = "SELECT * FROM " . $this->getTableName('books') . " WHERE id = :id";
        $bookStmt = $this->db->prepare($bookSql);
        $bookStmt->execute([':id' => $bookId]);
        $book = $bookStmt->fetch();

        $notifiedCount = 0;
        
        // 构建通知内容
        $bookTitle = $book['title'] ?? '未知书籍';
        $chapterTitle = $chapter['title'] ?? ('第' . ($chapter['chapter_number'] ?? '') . '章');
        $chapterUrl = '/novel/' . $bookId . '/chapter/' . $chapterId;
        
        $messageTitle = "《{$bookTitle}》更新通知";
        $messageContent = "您订阅的《{$bookTitle}》有新章节更新：\n\n";
        $messageContent .= "章节：{$chapterTitle}\n";
        $messageContent .= "更新时间：" . ($chapter['created_at'] ?? date('Y-m-d H:i:s')) . "\n\n";
        $messageContent .= "<a href=\"{$chapterUrl}\">点击阅读</a>";
        
        $emailSubject = "《{$bookTitle}》新章节更新：{$chapterTitle}";
        $emailContent = "<h2>《{$bookTitle}》更新通知</h2>";
        $emailContent .= "<p>您订阅的《{$bookTitle}》有新章节更新：</p>";
        $emailContent .= "<p><strong>章节：</strong>{$chapterTitle}</p>";
        $emailContent .= "<p><strong>更新时间：</strong>" . ($chapter['created_at'] ?? date('Y-m-d H:i:s')) . "</p>";
        $emailContent .= "<p><a href=\"" . (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : '') . "{$chapterUrl}\">点击阅读新章节</a></p>";

        // 发送通知（站内消息 + 邮件）
        foreach ($subscribers as $subscriber) {
            $userId = (int)$subscriber['user_id'];
            $userEmail = $subscriber['email'] ?? null;
            
            // 1. 发送站内消息
            try {
                $this->sendSiteMessage($userId, $messageTitle, $messageContent);
            } catch (\Exception $e) {
                error_log("发送站内消息失败 (用户ID: {$userId}): " . $e->getMessage());
            }
            
            // 2. 发送邮件（如果用户有邮箱）
            if (!empty($userEmail) && filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                try {
                    $errorMsg = null;
                    if (function_exists('send_system_mail')) {
                        $mailSent = send_system_mail($userEmail, $emailSubject, $emailContent, $errorMsg);
                        if (!$mailSent) {
                            error_log("发送邮件失败 (用户ID: {$userId}, 邮箱: {$userEmail}): " . ($errorMsg ?? '未知错误'));
                        }
                    }
                } catch (\Exception $e) {
                    error_log("发送邮件异常 (用户ID: {$userId}, 邮箱: {$userEmail}): " . $e->getMessage());
                }
            }
            
            // 记录通知日志
            $this->logNotification($userId, $bookId, $chapterId);
            $notifiedCount++;
        }

        // 更新订阅的last_notified_at
        if ($notifiedCount > 0) {
            $updateSql = "UPDATE " . $this->getTableName('book_subscriptions') . " 
                         SET last_notified_at = NOW() 
                         WHERE book_id = :book_id AND notify_on_update = 1";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute([':book_id' => $bookId]);
        }

        return $notifiedCount;
    }

    /**
     * 发送站内消息
     *
     * @param int $userId
     * @param string $title
     * @param string $content
     * @return bool
     */
    private function sendSiteMessage(int $userId, string $title, string $content): bool
    {
        try {
            $sql = "INSERT INTO " . $this->getTableName('site_messages') . " 
                    (user_id, title, content, status, created_at) 
                    VALUES (:user_id, :title, :content, 'unread', NOW())";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':user_id' => $userId,
                ':title' => $title,
                ':content' => $content,
            ]);
        } catch (\Exception $e) {
            error_log("发送站内消息失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 记录通知日志
     *
     * @param int $userId
     * @param int $bookId
     * @param int $chapterId
     * @return void
     */
    private function logNotification(int $userId, int $bookId, int $chapterId): void
    {
        try {
            // 检查表是否存在
            $tableName = $this->getTableName('subscription_notifications');
            $checkSql = "SHOW TABLES LIKE '{$tableName}'";
            $checkStmt = $this->db->query($checkSql);
            
            if ($checkStmt && $checkStmt->rowCount() > 0) {
                $sql = "INSERT INTO {$tableName} 
                        (user_id, book_id, chapter_id, created_at) 
                        VALUES (:user_id, :book_id, :chapter_id, NOW())";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':user_id' => $userId,
                    ':book_id' => $bookId,
                    ':chapter_id' => $chapterId,
                ]);
            }
        } catch (\Exception $e) {
            // 记录日志失败不影响主流程
            error_log("记录通知日志失败: " . $e->getMessage());
        }
    }

    /**
     * 获取订阅统计
     *
     * @param int $bookId 书籍ID
     * @return array
     */
    public function getSubscriptionStats(int $bookId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_subscribers,
                    SUM(CASE WHEN notify_on_update = 1 THEN 1 ELSE 0 END) as notify_enabled
                FROM " . $this->getTableName('book_subscriptions') . "
                WHERE book_id = :book_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':book_id' => $bookId]);
        $stats = $stmt->fetch();

        return [
            'total_subscribers' => (int)($stats['total_subscribers'] ?? 0),
            'notify_enabled' => (int)($stats['notify_enabled'] ?? 0),
        ];
    }
}
