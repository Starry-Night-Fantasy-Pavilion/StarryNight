<?php

namespace plugins\apps\my_app\Services;

use PDO;
use PDOException;

class BookmarkService
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
     * 添加书签
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @param int $chapterId 章节ID
     * @param int|null $position 书签在章节中的位置（字符位置）
     * @param string|null $note 书签备注
     * @return int|null
     */
    public function addBookmark(int $userId, int $bookId, int $chapterId, ?int $position = null, ?string $note = null): ?int
    {
        return $this->insert('bookmarks', [
            'user_id' => $userId,
            'book_id' => $bookId,
            'chapter_id' => $chapterId,
            'position' => $position,
            'note' => $note
        ]);
    }

    /**
     * 获取用户在指定书籍中的所有书签
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @return array
     */
    public function getBookmarksByBook(int $userId, int $bookId): array
    {
        return $this->query(
            "SELECT b.*, c.title as chapter_title, c.chapter_number
             FROM " . $this->getTableName('bookmarks') . " b
             LEFT JOIN " . $this->getTableName('chapters') . " c ON b.chapter_id = c.id
             WHERE b.user_id = :user_id AND b.book_id = :book_id
             ORDER BY c.chapter_number ASC, b.position ASC",
            [':user_id' => $userId, ':book_id' => $bookId]
        );
    }

    /**
     * 获取用户所有书签
     * @param int $userId 用户ID
     * @param int $limit 限制数量
     * @return array
     */
    public function getAllBookmarks(int $userId, int $limit = 50): array
    {
        return $this->query(
            "SELECT b.*, book.title as book_title, book.author, c.title as chapter_title, c.chapter_number
             FROM " . $this->getTableName('bookmarks') . " b
             LEFT JOIN " . $this->getTableName('books') . " book ON b.book_id = book.id
             LEFT JOIN " . $this->getTableName('chapters') . " c ON b.chapter_id = c.id
             WHERE b.user_id = :user_id
             ORDER BY b.created_at DESC
             LIMIT :limit",
            [':user_id' => $userId, ':limit' => $limit]
        );
    }

    /**
     * 获取单个书签详情
     * @param int $userId 用户ID
     * @param int $bookmarkId 书签ID
     * @return array|null
     */
    public function getBookmark(int $userId, int $bookmarkId): ?array
    {
        $result = $this->query(
            "SELECT b.*, book.title as book_title, c.title as chapter_title, c.chapter_number
             FROM " . $this->getTableName('bookmarks') . " b
             LEFT JOIN " . $this->getTableName('books') . " book ON b.book_id = book.id
             LEFT JOIN " . $this->getTableName('chapters') . " c ON b.chapter_id = c.id
             WHERE b.user_id = :user_id AND b.id = :bookmark_id",
            [':user_id' => $userId, ':bookmark_id' => $bookmarkId]
        );

        return $result[0] ?? null;
    }

    /**
     * 更新书签
     * @param int $userId 用户ID
     * @param int $bookmarkId 书签ID
     * @param array $data 更新数据
     * @return bool
     */
    public function updateBookmark(int $userId, int $bookmarkId, array $data): bool
    {
        return $this->update('bookmarks', $data, ['user_id' => $userId, 'id' => $bookmarkId]) > 0;
    }

    /**
     * 删除书签
     * @param int $userId 用户ID
     * @param int $bookmarkId 书签ID
     * @return bool
     */
    public function deleteBookmark(int $userId, int $bookmarkId): bool
    {
        return $this->delete('bookmarks', ['user_id' => $userId, 'id' => $bookmarkId]) > 0;
    }

    /**
     * 检查用户是否已在指定位置添加了书签
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @param int $chapterId 章节ID
     * @param int $position 位置
     * @return bool
     */
    public function hasBookmarkAtPosition(int $userId, int $bookId, int $chapterId, int $position): bool
    {
        $result = $this->query(
            "SELECT id FROM " . $this->getTableName('bookmarks') . " 
             WHERE user_id = :user_id AND book_id = :book_id AND chapter_id = :chapter_id AND position = :position",
            [
                ':user_id' => $userId,
                ':book_id' => $bookId,
                ':chapter_id' => $chapterId,
                ':position' => $position
            ]
        );

        return !empty($result);
    }

    /**
     * 获取用户书签总数
     * @param int $userId 用户ID
     * @return int
     */
    public function getBookmarkCount(int $userId): int
    {
        $result = $this->query(
            "SELECT COUNT(*) as count FROM " . $this->getTableName('bookmarks') . " WHERE user_id = :user_id",
            [':user_id' => $userId]
        );

        return (int)($result[0]['count'] ?? 0);
    }
}