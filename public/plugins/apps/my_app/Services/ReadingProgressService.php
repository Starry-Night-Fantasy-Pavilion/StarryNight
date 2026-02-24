<?php

namespace plugins\apps\my_app\Services;

use PDO;
use PDOException;

class ReadingProgressService
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
            die('数据库连接失败。');
        }
    }

    protected function getTableName(string $name): string
    {
        return '`' . $this->db_prefix . $name . '`';
    }

    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    protected function insert(string $table, array $data): ?int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$this->getTableName($table)} ($columns) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        return $this->db->lastInsertId();
    }

    protected function update(string $table, array $data, array $where): int
    {
        $setParts = [];
        foreach ($data as $column => $value) {
            $setParts[] = "$column = ?";
        }
        $setClause = implode(', ', $setParts);
        
        $whereParts = [];
        foreach ($where as $column => $value) {
            $whereParts[] = "$column = ?";
        }
        $whereClause = implode(' AND ', $whereParts);
        
        $sql = "UPDATE {$this->getTableName($table)} SET $setClause WHERE $whereClause";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(array_values($data), array_values($where)));
        return $stmt->rowCount();
    }

    protected function delete(string $table, array $where): int
    {
        $whereParts = [];
        foreach ($where as $column => $value) {
            $whereParts[] = "$column = ?";
        }
        $whereClause = implode(' AND ', $whereParts);
        
        $sql = "DELETE FROM {$this->getTableName($table)} WHERE $whereClause";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($where));
        return $stmt->rowCount();
    }

    /**
     * 更新用户阅读进度
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @param int $chapterId 章节ID
     * @param float $progress 阅读进度百分比 (0-100)
     * @return bool
     */
    public function updateProgress(int $userId, int $bookId, int $chapterId, float $progress): bool
    {
        // 检查是否已有记录
        $existing = $this->query(
            "SELECT id FROM " . $this->getTableName('reading_progress') . " WHERE user_id = :user_id AND book_id = :book_id",
            [':user_id' => $userId, ':book_id' => $bookId]
        );

        if (empty($existing)) {
            // 插入新记录
            return $this->insert('reading_progress', [
                'user_id' => $userId,
                'book_id' => $bookId,
                'chapter_id' => $chapterId,
                'progress' => min(100, max(0, $progress))
            ]) > 0;
        } else {
            // 更新现有记录
            return $this->update('reading_progress', [
                'chapter_id' => $chapterId,
                'progress' => min(100, max(0, $progress)),
                'last_read_at' => date('Y-m-d H:i:s')
            ], ['user_id' => $userId, 'book_id' => $bookId]) > 0;
        }
    }

    /**
     * 获取用户阅读进度
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @return array|null
     */
    public function getProgress(int $userId, int $bookId): ?array
    {
        $result = $this->query(
            "SELECT rp.*, c.title as chapter_title, c.chapter_number 
             FROM " . $this->getTableName('reading_progress') . " rp
             LEFT JOIN " . $this->getTableName('chapters') . " c ON rp.chapter_id = c.id
             WHERE rp.user_id = :user_id AND rp.book_id = :book_id",
            [':user_id' => $userId, ':book_id' => $bookId]
        );

        return $result[0] ?? null;
    }

    /**
     * 获取用户所有阅读进度
     * @param int $userId 用户ID
     * @param int $limit 限制数量
     * @return array
     */
    public function getAllProgress(int $userId, int $limit = 20): array
    {
        return $this->query(
            "SELECT rp.*, b.title as book_title, b.author, b.cover_image, c.title as chapter_title
             FROM " . $this->getTableName('reading_progress') . " rp
             LEFT JOIN " . $this->getTableName('books') . " b ON rp.book_id = b.id
             LEFT JOIN " . $this->getTableName('chapters') . " c ON rp.chapter_id = c.id
             WHERE rp.user_id = :user_id
             ORDER BY rp.last_read_at DESC
             LIMIT :limit",
            [':user_id' => $userId, ':limit' => $limit]
        );
    }

    /**
     * 删除阅读进度
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @return bool
     */
    public function deleteProgress(int $userId, int $bookId): bool
    {
        return $this->delete('reading_progress', ['user_id' => $userId, 'book_id' => $bookId]) > 0;
    }

    /**
     * 计算用户在书籍中的阅读进度百分比
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @return float
     */
    public function calculateProgress(int $userId, int $bookId): float
    {
        // 获取书籍总章节数
        $totalChapters = $this->query(
            "SELECT COUNT(*) as count FROM " . $this->getTableName('chapters') . " WHERE book_id = :book_id",
            [':book_id' => $bookId]
        )[0]['count'] ?? 0;

        if ($totalChapters == 0) {
            return 0;
        }

        // 获取当前阅读章节
        $progress = $this->getProgress($userId, $bookId);
        if (!$progress || !$progress['chapter_id']) {
            return 0;
        }

        // 获取当前章节编号
        $currentChapter = $this->query(
            "SELECT chapter_number FROM " . $this->getTableName('chapters') . " WHERE id = :chapter_id",
            [':chapter_id' => $progress['chapter_id']]
        )[0]['chapter_number'] ?? 0;

        // 计算进度百分比
        return min(100, ($currentChapter / $totalChapters) * 100);
    }

    /**
     * 记录用户阅读行为
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @param string $actionType 行为类型
     * @param int|null $duration 阅读时长（秒）
     * @return bool
     */
    public function recordBehavior(int $userId, int $bookId, string $actionType, ?int $duration = null): bool
    {
        return $this->insert('user_behaviors', [
            'user_id' => $userId,
            'book_id' => $bookId,
            'action_type' => $actionType,
            'duration' => $duration
        ]) > 0;
    }
}