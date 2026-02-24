<?php

namespace plugins\apps\my_app\Services;

use PDO;
use PDOException;

class RecommendationService
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
     * 记录用户行为
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

    /**
     * 基于用户行为生成推荐
     * @param int $userId 用户ID
     * @param int $limit 推荐数量
     * @return array
     */
    public function generateRecommendations(int $userId, int $limit = 10): array
    {
        // 清除过期推荐
        $this->execute(
            "DELETE FROM " . $this->getTableName('recommendations') . " 
             WHERE user_id = :user_id AND expires_at < NOW()",
            [':user_id' => $userId]
        );

        // 检查是否已有有效推荐
        $sql1 = "SELECT * FROM " . $this->getTableName('recommendations') . "
             WHERE user_id = :user_id AND expires_at > NOW()
             ORDER BY score DESC LIMIT :limit";
        $stmt1 = $this->db->prepare($sql1);
        $stmt1->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt1->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt1->execute();
        $existingRecommendations = $stmt1->fetchAll();

        if (count($existingRecommendations) >= $limit) {
            return $existingRecommendations;
        }

        // 生成新推荐
        $this->generateCollaborativeRecommendations($userId);
        $this->generateContentBasedRecommendations($userId);

        // 返回最新的推荐
        $sql2 = "SELECT r.*, b.title, b.author, b.cover_image, b.category, b.description
             FROM " . $this->getTableName('recommendations') . " r
             LEFT JOIN " . $this->getTableName('books') . " b ON r.book_id = b.id
             WHERE r.user_id = :user_id AND r.expires_at > NOW()
             ORDER BY r.score DESC LIMIT :limit";
        $stmt2 = $this->db->prepare($sql2);
        $stmt2->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt2->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt2->execute();
        return $stmt2->fetchAll();
    }

    /**
     * 基于协同过滤生成推荐
     * @param int $userId 用户ID
     * @return void
     */
    private function generateCollaborativeRecommendations(int $userId): void
    {
        // 找到与当前用户行为相似的其他用户
        $similarUsers = $this->query(
            "SELECT ub2.user_id, COUNT(*) as common_actions
             FROM " . $this->getTableName('user_behaviors') . " ub1
             JOIN " . $this->getTableName('user_behaviors') . " ub2 ON ub1.book_id = ub2.book_id AND ub1.action_type = ub2.action_type
             WHERE ub1.user_id = :user_id AND ub2.user_id != :user_id
             GROUP BY ub2.user_id
             HAVING common_actions >= 3
             ORDER BY common_actions DESC
             LIMIT 10",
            [':user_id' => $userId]
        );

        if (empty($similarUsers)) {
            return;
        }

        // 获取这些相似用户喜欢但当前用户未阅读的书籍
        $similarUserIds = array_column($similarUsers, 'user_id');
        $placeholders = implode(',', array_fill(0, count($similarUserIds), '?'));
        
        $recommendedBooks = $this->query(
            "SELECT ub.book_id, COUNT(*) as recommendation_count, AVG(ub.duration) as avg_duration
             FROM " . $this->getTableName('user_behaviors') . " ub
             WHERE ub.user_id IN ($placeholders) AND ub.action_type IN ('view', 'like', 'bookmark')
             AND ub.book_id NOT IN (
                 SELECT book_id FROM " . $this->getTableName('user_behaviors') . " 
                 WHERE user_id = :user_id AND action_type IN ('view', 'like', 'bookmark')
             )
             GROUP BY ub.book_id
             HAVING recommendation_count >= 2
             ORDER BY recommendation_count DESC, avg_duration DESC
             LIMIT 20",
            array_merge($similarUserIds, [':user_id' => $userId])
        );

        // 保存推荐结果
        foreach ($recommendedBooks as $book) {
            $score = min(100, ($book['recommendation_count'] * 20) + ($book['avg_duration'] > 300 ? 10 : 0));
            
            $this->insert('recommendations', [
                'user_id' => $userId,
                'book_id' => $book['book_id'],
                'score' => $score,
                'reason' => '基于相似用户的阅读偏好推荐',
                'algorithm' => 'collaborative',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
            ]);
        }
    }

    /**
     * 基于内容生成推荐
     * @param int $userId 用户ID
     * @return void
     */
    private function generateContentBasedRecommendations(int $userId): void
    {
        // 获取用户喜欢的书籍类别
        $preferredCategories = $this->query(
            "SELECT b.category, COUNT(*) as count
             FROM " . $this->getTableName('user_behaviors') . " ub
             JOIN " . $this->getTableName('books') . " b ON ub.book_id = b.id
             WHERE ub.user_id = :user_id AND ub.action_type IN ('view', 'like', 'bookmark')
             AND b.category IS NOT NULL AND b.category != ''
             GROUP BY b.category
             ORDER BY count DESC
             LIMIT 3",
            [':user_id' => $userId]
        );

        if (empty($preferredCategories)) {
            return;
        }

        // 获取用户已阅读的书籍ID
        $readBooks = $this->query(
            "SELECT DISTINCT book_id FROM " . $this->getTableName('user_behaviors') . " 
             WHERE user_id = :user_id AND action_type IN ('view', 'like', 'bookmark')",
            [':user_id' => $userId]
        );
        $readBookIds = array_column($readBooks, 'book_id');

        // 为每个偏好类别推荐书籍
        foreach ($preferredCategories as $category) {
            $sql = "SELECT id, views, likes
                 FROM " . $this->getTableName('books') . "
                 WHERE category = :category AND status = 'published'";
            
            $params = [':category' => $category['category']];

            if (!empty($readBookIds)) {
                $placeholders = implode(',', array_fill(0, count($readBookIds), '?'));
                $sql .= " AND id NOT IN ($placeholders)";
                $params = array_merge($params, $readBookIds);
            }
            
            $sql .= " ORDER BY views DESC, likes DESC LIMIT 10";

            $recommendedBooks = $this->query($sql, $params);

            foreach ($recommendedBooks as $book) {
                // 检查是否已推荐
                $existing = $this->query(
                    "SELECT id FROM " . $this->getTableName('recommendations') . "
                     WHERE user_id = :user_id AND book_id = :book_id",
                    [':user_id' => $userId, ':book_id' => $book['id']]
                );

                if (empty($existing)) {
                    $score = min(100, ($book['views'] > 1000 ? 30 : 15) + ($book['likes'] > 50 ? 20 : 10));
                    
                    $this->insert('recommendations', [
                        'user_id' => $userId,
                        'book_id' => $book['id'],
                        'score' => $score,
                        'reason' => "基于您对「{$category['category']}」类别的偏好推荐",
                        'algorithm' => 'content',
                        'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
                    ]);
                }
            }
        }
    }

    /**
     * 计算书籍相似度
     * @param int $bookId1 书籍ID1
     * @param int $bookId2 书籍ID2
     * @return float 相似度分数 (0-100)
     */
    public function calculateBookSimilarity(int $bookId1, int $bookId2): float
    {
        // 检查是否已计算过相似度
        $existing = $this->query(
            "SELECT similarity FROM " . $this->getTableName('book_similarity') . " 
             WHERE (book_id_1 = :book1 AND book_id_2 = :book2) OR (book_id_1 = :book2 AND book_id_2 = :book1)",
            [':book1' => $bookId1, ':book2' => $bookId2]
        );

        if (!empty($existing)) {
            return (float)$existing[0]['similarity'];
        }

        // 获取书籍信息
        $books = $this->query(
            "SELECT id, category, author, tags FROM " . $this->getTableName('books') . " 
             WHERE id IN (:book1, :book2)",
            [':book1' => $bookId1, ':book2' => $bookId2]
        );

        if (count($books) < 2) {
            return 0;
        }

        $book1 = $books[0]['id'] == $bookId1 ? $books[0] : $books[1];
        $book2 = $books[0]['id'] == $bookId2 ? $books[0] : $books[1];

        // 计算相似度
        $similarity = 0;

        // 类别相似度
        if ($book1['category'] && $book2['category'] && $book1['category'] === $book2['category']) {
            $similarity += 40;
        }

        // 作者相似度
        if ($book1['author'] && $book2['author'] && $book1['author'] === $book2['author']) {
            $similarity += 30;
        }

        // 标签相似度（如果有标签字段）
        if ($book1['tags'] && $book2['tags']) {
            $tags1 = explode(',', $book1['tags']);
            $tags2 = explode(',', $book2['tags']);
            $commonTags = array_intersect($tags1, $tags2);
            $similarity += count($commonTags) * 10;
        }

        // 保存相似度结果
        $this->insert('book_similarity', [
            'book_id_1' => $bookId1,
            'book_id_2' => $bookId2,
            'similarity' => min(100, $similarity),
            'algorithm' => 'content'
        ]);

        return min(100, $similarity);
    }

    /**
     * 获取相似书籍
     * @param int $bookId 书籍ID
     * @param int $limit 限制数量
     * @return array
     */
    public function getSimilarBooks(int $bookId, int $limit = 5): array
    {
        $sql = "SELECT bs.similarity, b.id, b.title, b.author, b.cover_image, b.category
             FROM " . $this->getTableName('book_similarity') . " bs
             JOIN " . $this->getTableName('books') . " b ON (
                 (bs.book_id_1 = :book_id AND b.id = bs.book_id_2) OR
                 (bs.book_id_2 = :book_id AND b.id = bs.book_id_1)
             )
             WHERE b.status = 'published' AND b.id != :book_id
             ORDER BY bs.similarity DESC, b.views DESC
             LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':book_id', $bookId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 获取热门书籍
     * @param int $limit 限制数量
     * @return array
     */
    public function getPopularBooks(int $limit = 10): array
    {
        $sql = "SELECT id, title, author, cover_image, category, views, likes
             FROM " . $this->getTableName('books') . "
             WHERE status = 'published'
             ORDER BY views DESC, likes DESC
             LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}