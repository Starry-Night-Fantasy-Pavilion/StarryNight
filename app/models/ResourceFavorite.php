<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 资源收藏模型
 */
class ResourceFavorite
{
    /**
     * 创建资源收藏
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}resource_favorites` 
                (resource_type, resource_id, share_id, user_id) 
                VALUES (:resource_type, :resource_id, :share_id, :user_id)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':resource_type' => $data['resource_type'],
            ':resource_id' => $data['resource_id'],
            ':share_id' => $data['share_id'] ?? null,
            ':user_id' => $data['user_id']
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取资源收藏
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT rf.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}resource_favorites` rf
                LEFT JOIN `{$prefix}users` u ON rf.user_id = u.id
                WHERE rf.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 检查用户是否已收藏资源
     *
     * @param int $userId
     * @param string $resourceType
     * @param int $resourceId
     * @return array|null
     */
    public static function getUserFavorite(int $userId, string $resourceType, int $resourceId): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}resource_favorites` 
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
     * 获取用户的收藏列表
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @param string|null $resourceType
     * @return array
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 15, ?string $resourceType = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT rf.*, u.username, u.nickname as user_nickname,
                       CASE 
                           WHEN rf.resource_type = 'knowledge' THEN (SELECT title FROM `{$prefix}knowledge_bases` WHERE id = rf.resource_id)
                           WHEN rf.resource_type = 'prompt' THEN (SELECT title FROM `{$prefix}ai_prompt_templates` WHERE id = rf.resource_id)
                           WHEN rf.resource_type = 'template' THEN (SELECT title FROM `{$prefix}creation_templates` WHERE id = rf.resource_id)
                           WHEN rf.resource_type = 'agent' THEN (SELECT name FROM `{$prefix}ai_agents` WHERE id = rf.resource_id)
                       END as resource_title,
                       CASE 
                           WHEN rf.resource_type = 'knowledge' THEN (SELECT description FROM `{$prefix}knowledge_bases` WHERE id = rf.resource_id)
                           WHEN rf.resource_type = 'prompt' THEN (SELECT description FROM `{$prefix}ai_prompt_templates` WHERE id = rf.resource_id)
                           WHEN rf.resource_type = 'template' THEN (SELECT description FROM `{$prefix}creation_templates` WHERE id = rf.resource_id)
                           WHEN rf.resource_type = 'agent' THEN (SELECT description FROM `{$prefix}ai_agents` WHERE id = rf.resource_id)
                       END as resource_description
                FROM `{$prefix}resource_favorites` rf
                LEFT JOIN `{$prefix}users` u ON rf.user_id = u.id
                WHERE rf.user_id = :user_id";

        $params = [':user_id' => $userId];

        if ($resourceType) {
            $sql .= " AND rf.resource_type = :resource_type";
            $params[':resource_type'] = $resourceType;
        }

        $sql .= " ORDER BY rf.created_at DESC";

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}resource_favorites` WHERE user_id = :user_id";
        $countParams = [':user_id' => $userId];

        if ($resourceType) {
            $countSql .= " AND resource_type = :resource_type";
            $countParams[':resource_type'] = $resourceType;
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $totalRecords = $countStmt->fetchColumn();

        // 获取分页数据
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            if ($key !== ':user_id') {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'favorites' => $favorites,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 获取资源的收藏列表
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

        $sql = "SELECT rf.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}resource_favorites` rf
                LEFT JOIN `{$prefix}users` u ON rf.user_id = u.id
                WHERE rf.resource_type = :resource_type AND rf.resource_id = :resource_id
                ORDER BY rf.created_at DESC";

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}resource_favorites` 
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
        $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'favorites' => $favorites,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 删除资源收藏
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}resource_favorites` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 根据用户和资源删除收藏
     *
     * @param int $userId
     * @param string $resourceType
     * @param int $resourceId
     * @return bool
     */
    public static function deleteByUserAndResource(int $userId, string $resourceType, int $resourceId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}resource_favorites` 
                WHERE user_id = :user_id AND resource_type = :resource_type AND resource_id = :resource_id";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':resource_type' => $resourceType,
            ':resource_id' => $resourceId
        ]);
    }

    /**
     * 获取收藏统计
     *
     * @param string|null $resourceType
     * @return array
     */
    public static function getFavoriteStats(?string $resourceType = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    resource_type,
                    COUNT(*) as total_favorites
                FROM `{$prefix}resource_favorites`";

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
            $stats[$result['resource_type']] = (int)$result['total_favorites'];
        }

        return $stats;
    }

    /**
     * 获取热门收藏资源
     *
     * @param int $limit
     * @param string|null $resourceType
     * @return array
     */
    public static function getPopularFavorites(int $limit = 10, ?string $resourceType = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    rf.resource_type,
                    rf.resource_id,
                    COUNT(*) as favorite_count,
                    CASE 
                        WHEN rf.resource_type = 'knowledge' THEN (SELECT title FROM `{$prefix}knowledge_bases` WHERE id = rf.resource_id)
                        WHEN rf.resource_type = 'prompt' THEN (SELECT title FROM `{$prefix}ai_prompt_templates` WHERE id = rf.resource_id)
                        WHEN rf.resource_type = 'template' THEN (SELECT title FROM `{$prefix}creation_templates` WHERE id = rf.resource_id)
                        WHEN rf.resource_type = 'agent' THEN (SELECT name FROM `{$prefix}ai_agents` WHERE id = rf.resource_id)
                    END as resource_title
                FROM `{$prefix}resource_favorites` rf";

        $params = [];

        if ($resourceType) {
            $sql .= " WHERE rf.resource_type = :resource_type";
            $params[':resource_type'] = $resourceType;
        }

        $sql .= " GROUP BY rf.resource_type, rf.resource_id
                  ORDER BY favorite_count DESC LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取最新收藏
     *
     * @param int $limit
     * @param string|null $resourceType
     * @return array
     */
    public static function getLatestFavorites(int $limit = 10, ?string $resourceType = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT rf.*, u.username, u.nickname as user_nickname,
                       CASE 
                           WHEN rf.resource_type = 'knowledge' THEN (SELECT title FROM `{$prefix}knowledge_bases` WHERE id = rf.resource_id)
                           WHEN rf.resource_type = 'prompt' THEN (SELECT title FROM `{$prefix}ai_prompt_templates` WHERE id = rf.resource_id)
                           WHEN rf.resource_type = 'template' THEN (SELECT title FROM `{$prefix}creation_templates` WHERE id = rf.resource_id)
                           WHEN rf.resource_type = 'agent' THEN (SELECT name FROM `{$prefix}ai_agents` WHERE id = rf.resource_id)
                       END as resource_title
                FROM `{$prefix}resource_favorites` rf
                LEFT JOIN `{$prefix}users` u ON rf.user_id = u.id";

        $params = [];

        if ($resourceType) {
            $sql .= " WHERE rf.resource_type = :resource_type";
            $params[':resource_type'] = $resourceType;
        }

        $sql .= " ORDER BY rf.created_at DESC LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取资源类型列表
     */
    public static function getResourceTypes(): array
    {
        return [
            'knowledge' => '知识库',
            'prompt' => '提示词模板',
            'template' => '创作模板',
            'agent' => 'AI智能体'
        ];
    }
}