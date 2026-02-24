<?php

namespace plugins\apps\my_app\Services;

use PDO;
use PDOException;

/**
 * 评论评分服务
 * 提供评论管理、评分统计、评论审核等功能
 */
class CommentRatingService
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
     * 添加评论
     *
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @param string $content 评论内容
     * @param int|null $parentId 父评论ID（回复评论时）
     * @return int|false 评论ID或false
     */
    public function addComment(int $userId, int $bookId, string $content, ?int $parentId = null)
    {
        if (empty(trim($content))) {
            throw new \InvalidArgumentException('评论内容不能为空');
        }

        $sql = "INSERT INTO " . $this->getTableName('book_comments') . " 
                (user_id, book_id, content, parent_id, status, created_at) 
                VALUES (:user_id, :book_id, :content, :parent_id, 'pending', NOW())";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':user_id' => $userId,
            ':book_id' => $bookId,
            ':content' => trim($content),
            ':parent_id' => $parentId,
        ]);

        if ($result) {
            $commentId = $this->db->lastInsertId();
            // 更新书籍评论数
            $this->updateBookCommentCount($bookId);
            return $commentId;
        }

        return false;
    }

    /**
     * 添加评分
     *
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @param int $rating 评分（1-5）
     * @return bool
     */
    public function addRating(int $userId, int $bookId, int $rating): bool
    {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('评分必须在1-5之间');
        }

        // 检查是否已评分
        $existing = $this->getRating($userId, $bookId);
        if ($existing) {
            // 更新评分
            $sql = "UPDATE " . $this->getTableName('book_ratings') . " 
                    SET rating = :rating, updated_at = NOW() 
                    WHERE user_id = :user_id AND book_id = :book_id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':rating' => $rating,
                ':user_id' => $userId,
                ':book_id' => $bookId,
            ]);
        } else {
            // 新增评分
            $sql = "INSERT INTO " . $this->getTableName('book_ratings') . " 
                    (user_id, book_id, rating, created_at) 
                    VALUES (:user_id, :book_id, :rating, NOW())";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':user_id' => $userId,
                ':book_id' => $bookId,
                ':rating' => $rating,
            ]);
        }

        if ($result) {
            // 更新书籍平均评分
            $this->updateBookRating($bookId);
            return true;
        }

        return false;
    }

    /**
     * 获取评论列表
     *
     * @param int $bookId 书籍ID
     * @param int $page 页码
     * @param int $limit 每页数量
     * @param string $status 状态筛选（pending, approved, rejected）
     * @return array
     */
    public function getComments(int $bookId, int $page = 1, int $limit = 20, string $status = 'approved'): array
    {
        $offset = ($page - 1) * $limit;

        $sql = "SELECT c.*, u.username, u.avatar,
                       (SELECT COUNT(*) FROM " . $this->getTableName('book_comments') . " WHERE parent_id = c.id) as reply_count
                FROM " . $this->getTableName('book_comments') . " c
                LEFT JOIN " . $this->getTableName('users') . " u ON c.user_id = u.id
                WHERE c.book_id = :book_id AND c.status = :status AND c.parent_id IS NULL
                ORDER BY c.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':book_id', $bookId, PDO::PARAM_INT);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $comments = $stmt->fetchAll();

        // 获取回复
        foreach ($comments as &$comment) {
            $comment['replies'] = $this->getReplies($comment['id'], $status);
        }

        return $comments;
    }

    /**
     * 获取回复列表
     *
     * @param int $parentId 父评论ID
     * @param string $status 状态
     * @return array
     */
    public function getReplies(int $parentId, string $status = 'approved'): array
    {
        $sql = "SELECT c.*, u.username, u.avatar
                FROM " . $this->getTableName('book_comments') . " c
                LEFT JOIN " . $this->getTableName('users') . " u ON c.user_id = u.id
                WHERE c.parent_id = :parent_id AND c.status = :status
                ORDER BY c.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':parent_id' => $parentId,
            ':status' => $status,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * 获取评分统计
     *
     * @param int $bookId 书籍ID
     * @return array
     */
    public function getRatingStats(int $bookId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_ratings,
                    AVG(rating) as average_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1
                FROM " . $this->getTableName('book_ratings') . "
                WHERE book_id = :book_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':book_id' => $bookId]);
        $stats = $stmt->fetch();

        return [
            'total_ratings' => (int)($stats['total_ratings'] ?? 0),
            'average_rating' => round((float)($stats['average_rating'] ?? 0), 2),
            'distribution' => [
                5 => (int)($stats['rating_5'] ?? 0),
                4 => (int)($stats['rating_4'] ?? 0),
                3 => (int)($stats['rating_3'] ?? 0),
                2 => (int)($stats['rating_2'] ?? 0),
                1 => (int)($stats['rating_1'] ?? 0),
            ],
        ];
    }

    /**
     * 获取用户评分
     *
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @return array|null
     */
    public function getRating(int $userId, int $bookId): ?array
    {
        $sql = "SELECT * FROM " . $this->getTableName('book_ratings') . "
                WHERE user_id = :user_id AND book_id = :book_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':book_id' => $bookId,
        ]);

        $rating = $stmt->fetch();
        return $rating ?: null;
    }

    /**
     * 审核评论
     *
     * @param int $commentId 评论ID
     * @param string $status 状态（approved, rejected）
     * @return bool
     */
    public function moderateComment(int $commentId, string $status): bool
    {
        if (!in_array($status, ['approved', 'rejected'])) {
            throw new \InvalidArgumentException('无效的状态');
        }

        $sql = "UPDATE " . $this->getTableName('book_comments') . " 
                SET status = :status, moderated_at = NOW() 
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':status' => $status,
            ':id' => $commentId,
        ]);

        if ($result) {
            // 获取书籍ID并更新评论数
            $comment = $this->getCommentById($commentId);
            if ($comment) {
                $this->updateBookCommentCount($comment['book_id']);
            }
        }

        return $result;
    }

    /**
     * 删除评论
     *
     * @param int $commentId 评论ID
     * @return bool
     */
    public function deleteComment(int $commentId): bool
    {
        // 先删除回复
        $sql1 = "DELETE FROM " . $this->getTableName('book_comments') . " WHERE parent_id = :id";
        $stmt1 = $this->db->prepare($sql1);
        $stmt1->execute([':id' => $commentId]);

        // 删除评论
        $comment = $this->getCommentById($commentId);
        $sql2 = "DELETE FROM " . $this->getTableName('book_comments') . " WHERE id = :id";
        $stmt2 = $this->db->prepare($sql2);
        $result = $stmt2->execute([':id' => $commentId]);

        if ($result && $comment) {
            $this->updateBookCommentCount($comment['book_id']);
        }

        return $result;
    }

    /**
     * 获取评论详情
     *
     * @param int $commentId
     * @return array|null
     */
    private function getCommentById(int $commentId): ?array
    {
        $sql = "SELECT * FROM " . $this->getTableName('book_comments') . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $commentId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * 更新书籍评论数
     *
     * @param int $bookId
     * @return void
     */
    private function updateBookCommentCount(int $bookId): void
    {
        $sql = "UPDATE " . $this->getTableName('books') . " 
                SET comment_count = (
                    SELECT COUNT(*) FROM " . $this->getTableName('book_comments') . "
                    WHERE book_id = :book_id AND status = 'approved'
                )
                WHERE id = :book_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':book_id' => $bookId]);
    }

    /**
     * 更新书籍平均评分
     *
     * @param int $bookId
     * @return void
     */
    private function updateBookRating(int $bookId): void
    {
        $stats = $this->getRatingStats($bookId);
        
        $sql = "UPDATE " . $this->getTableName('books') . " 
                SET rating = :rating, rating_count = :rating_count
                WHERE id = :book_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':rating' => $stats['average_rating'],
            ':rating_count' => $stats['total_ratings'],
            ':book_id' => $bookId,
        ]);
    }
}
