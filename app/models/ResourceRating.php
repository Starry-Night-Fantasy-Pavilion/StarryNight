<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 资源评价模型
 */
class ResourceRating
{
    /**
     * 创建资源评价
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}resource_ratings` 
                (resource_type, resource_id, share_id, user_id, rating, comment) 
                VALUES (:resource_type, :resource_id, :share_id, :user_id, :rating, :comment)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':resource_type' => $data['resource_type'],
            ':resource_id' => $data['resource_id'],
            ':share_id' => $data['share_id'] ?? null,
            ':user_id' => $data['user_id'],
            ':rating' => $data['rating'],
            ':comment' => $data['comment'] ?? null
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取资源评价
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT rr.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}resource_ratings` rr
                LEFT JOIN `{$prefix}users` u ON rr.user_id = u.id
                WHERE rr.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 获取资源的评价列表
     *
     * @param string $resourceType
     * @param int $resourceId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getByResource(string $resourceType, int $resourceId, int $page = 1, int $perPage = 15): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT rr.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}resource_ratings` rr
                LEFT JOIN `{$prefix}users` u ON rr.user_id = u.id
                WHERE rr.resource_type = :resource_type AND rr.resource_id = :resource_id
                ORDER BY rr.created_at DESC";

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}resource_ratings` 
                     WHERE resource_type = :resource_type AND resource_id = :resource_id";

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([
            ':resource_type' => $resourceType,
            ':resource_id' => $resourceId
        ]);
        $totalRecords = $countStmt->fetchColumn();

        // 获取分页数据
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':resource_type', $resourceType);
        $stmt->bindValue(':resource_id', $resourceId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'ratings' => $ratings,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 获取用户的评价列表
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 15): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT rr.*, u.username, u.nickname as user_nickname,
                       CASE 
                           WHEN rr.resource_type = 'knowledge' THEN (SELECT title FROM `{$prefix}knowledge_bases` WHERE id = rr.resource_id)
                           WHEN rr.resource_type = 'prompt' THEN (SELECT title FROM `{$prefix}ai_prompt_templates` WHERE id = rr.resource_id)
                           WHEN rr.resource_type = 'template' THEN (SELECT title FROM `{$prefix}creation_templates` WHERE id = rr.resource_id)
                           WHEN rr.resource_type = 'agent' THEN (SELECT name FROM `{$prefix}ai_agents` WHERE id = rr.resource_id)
                       END as resource_title
                FROM `{$prefix}resource_ratings` rr
                LEFT JOIN `{$prefix}users` u ON rr.user_id = u.id
                WHERE rr.user_id = :user_id
                ORDER BY rr.created_at DESC";

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}resource_ratings` WHERE user_id = :user_id";

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':user_id' => $userId]);
        $totalRecords = $countStmt->fetchColumn();

        // 获取分页数据
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'ratings' => $ratings,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 更新资源评价
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = ['rating', 'comment'];
        $updates = [];
        $params = [':id' => $id];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updates[] = "`$field` = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE `{$prefix}resource_ratings` SET " . implode(', ', $updates) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除资源评价
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}resource_ratings` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 检查用户是否已评价资源
     *
     * @param int $userId
     * @param string $resourceType
     * @param int $resourceId
     * @return array|null
     */
    public static function getUserRating(int $userId, string $resourceType, int $resourceId): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}resource_ratings` 
                WHERE user_id = :user_id AND resource_type = :resource_type AND resource_id = :resource_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':resource_type' => $resourceType,
            ':resource_id' => $resourceId
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 获取资源的平均评分
     *
     * @param string $resourceType
     * @param int $resourceId
     * @return array
     */
    public static function getResourceRatingStats(string $resourceType, int $resourceId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_ratings,
                    AVG(rating) as avg_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM `{$prefix}resource_ratings` 
                WHERE resource_type = :resource_type AND resource_id = :resource_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':resource_type' => $resourceType,
            ':resource_id' => $resourceId
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_ratings' => (int)$result['total_ratings'],
            'avg_rating' => round((float)$result['avg_rating'], 2),
            'distribution' => [
                5 => (int)$result['five_star'],
                4 => (int)$result['four_star'],
                3 => (int)$result['three_star'],
                2 => (int)$result['two_star'],
                1 => (int)$result['one_star']
            ]
        ];
    }

    /**
     * 获取评价统计
     *
     * @param string|null $resourceType
     * @return array
     */
    public static function getRatingStats(?string $resourceType = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    resource_type,
                    COUNT(*) as total_ratings,
                    AVG(rating) as avg_rating
                FROM `{$prefix}resource_ratings`";

        $params = [];

        if ($resourceType) {
            $sql .= " WHERE resource_type = :resource_type";
            $params[':resource_type'] = $resourceType;
        }

        $sql .= " GROUP BY resource_type";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [];
        foreach ($results as $result) {
            $stats[$result['resource_type']] = [
                'total_ratings' => (int)$result['total_ratings'],
                'avg_rating' => round((float)$result['avg_rating'], 2)
            ];
        }

        return $stats;
    }

    /**
     * 获取最新评价
     *
     * @param int $limit
     * @param string|null $resourceType
     * @return array
     */
    public static function getLatestRatings(int $limit = 10, ?string $resourceType = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT rr.*, u.username, u.nickname as user_nickname,
                       CASE 
                           WHEN rr.resource_type = 'knowledge' THEN (SELECT title FROM `{$prefix}knowledge_bases` WHERE id = rr.resource_id)
                           WHEN rr.resource_type = 'prompt' THEN (SELECT title FROM `{$prefix}ai_prompt_templates` WHERE id = rr.resource_id)
                           WHEN rr.resource_type = 'template' THEN (SELECT title FROM `{$prefix}creation_templates` WHERE id = rr.resource_id)
                           WHEN rr.resource_type = 'agent' THEN (SELECT name FROM `{$prefix}ai_agents` WHERE id = rr.resource_id)
                       END as resource_title
                FROM `{$prefix}resource_ratings` rr
                LEFT JOIN `{$prefix}users` u ON rr.user_id = u.id";

        $params = [];

        if ($resourceType) {
            $sql .= " WHERE rr.resource_type = :resource_type";
            $params[':resource_type'] = $resourceType;
        }

        $sql .= " ORDER BY rr.created_at DESC LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}