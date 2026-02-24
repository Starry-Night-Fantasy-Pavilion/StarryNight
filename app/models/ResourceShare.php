<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 资源分享模型
 */
class ResourceShare
{
    /**
     * 创建资源分享
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}resource_shares` 
                (resource_type, resource_id, user_id, title, description, tags, is_public, price, download_count, view_count, rating, rating_count, favorite_count, status) 
                VALUES (:resource_type, :resource_id, :user_id, :title, :description, :tags, :is_public, :price, :download_count, :view_count, :rating, :rating_count, :favorite_count, :status)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':resource_type' => $data['resource_type'],
            ':resource_id' => $data['resource_id'],
            ':user_id' => $data['user_id'],
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':tags' => $data['tags'] ?? null,
            ':is_public' => $data['is_public'] ?? 1,
            ':price' => $data['price'] ?? 0.00,
            ':download_count' => $data['download_count'] ?? 0,
            ':view_count' => $data['view_count'] ?? 0,
            ':rating' => $data['rating'] ?? 0.00,
            ':rating_count' => $data['rating_count'] ?? 0,
            ':favorite_count' => $data['favorite_count'] ?? 0,
            ':status' => $data['status'] ?? 1
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取资源分享
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT rs.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}resource_shares` rs
                LEFT JOIN `{$prefix}users` u ON rs.user_id = u.id
                WHERE rs.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 根据资源类型和ID获取分享记录
     *
     * @param string $resourceType
     * @param int $resourceId
     * @return array|null
     */
    public static function findByResource(string $resourceType, int $resourceId): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT rs.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}resource_shares` rs
                LEFT JOIN `{$prefix}users` u ON rs.user_id = u.id
                WHERE rs.resource_type = :resource_type AND rs.resource_id = :resource_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':resource_type' => $resourceType,
            ':resource_id' => $resourceId
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 获取资源分享列表
     *
     * @param int $page
     * @param int $perPage
     * @param string|null $searchTerm
     * @param string|null $resourceType
     * @param bool|null $isPublic
     * @param string|null $sortBy
     * @param string|null $sortOrder
     * @return array
     */
    public static function getAll(int $page = 1, int $perPage = 15, ?string $searchTerm = null, ?string $resourceType = null, ?bool $isPublic = null, ?string $sortBy = 'created_at', ?string $sortOrder = 'desc'): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT rs.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}resource_shares` rs
                LEFT JOIN `{$prefix}users` u ON rs.user_id = u.id";

        $where = [];
        $params = [];

        if ($searchTerm) {
            $where[] = "(rs.title LIKE :term OR rs.description LIKE :term OR rs.tags LIKE :term)";
            $params[':term'] = '%' . $searchTerm . '%';
        }

        if ($resourceType) {
            $where[] = "rs.resource_type = :resource_type";
            $params[':resource_type'] = $resourceType;
        }

        if ($isPublic !== null) {
            $where[] = "rs.is_public = :is_public";
            $params[':is_public'] = $isPublic ? 1 : 0;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        // 排序
        $allowedSortBy = ['id', 'title', 'created_at', 'download_count', 'view_count', 'rating', 'price', 'favorite_count'];
        if (!in_array($sortBy, $allowedSortBy)) {
            $sortBy = 'created_at';
        }

        $sortOrder = strtolower($sortOrder) === 'asc' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY rs.{$sortBy} {$sortOrder}";

        // 获取总数
        $countSql = str_replace(
            "SELECT rs.*, u.username, u.nickname as user_nickname",
            "SELECT COUNT(DISTINCT rs.id)",
            $sql
        );

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetchColumn();

        // 获取分页数据
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $shares = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'shares' => $shares,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 获取用户的资源分享列表
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

        $sql = "SELECT rs.*, COUNT(DISTINCT rr.id) as rating_count, COUNT(DISTINCT rf.id) as favorite_count
                FROM `{$prefix}resource_shares` rs
                LEFT JOIN `{$prefix}resource_ratings` rr ON rs.id = rr.share_id
                LEFT JOIN `{$prefix}resource_favorites` rf ON rs.id = rf.share_id
                WHERE rs.user_id = :user_id
                GROUP BY rs.id
                ORDER BY rs.created_at DESC";

        // 获取总数
        $countSql = str_replace(
            "SELECT rs.*, COUNT(DISTINCT rr.id) as rating_count, COUNT(DISTINCT rf.id) as favorite_count",
            "SELECT COUNT(DISTINCT rs.id)",
            $sql
        );

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
        $shares = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'shares' => $shares,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 更新资源分享
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = ['title', 'description', 'tags', 'is_public', 'price', 'status'];
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

        $sql = "UPDATE `{$prefix}resource_shares` SET " . implode(', ', $updates) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除资源分享
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            // 删除相关的评分
            $deleteRatings = $pdo->prepare("DELETE FROM `{$prefix}resource_ratings` WHERE share_id = ?");
            $deleteRatings->execute([$id]);

            // 删除相关的收藏
            $deleteFavorites = $pdo->prepare("DELETE FROM `{$prefix}resource_favorites` WHERE share_id = ?");
            $deleteFavorites->execute([$id]);

            // 删除相关的购买记录
            $deletePurchases = $pdo->prepare("DELETE FROM `{$prefix}resource_purchases` WHERE share_id = ?");
            $deletePurchases->execute([$id]);

            // 删除资源分享
            $deleteShare = $pdo->prepare("DELETE FROM `{$prefix}resource_shares` WHERE id = ?");
            $deleteShare->execute([$id]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 增加浏览次数
     *
     * @param int $id
     * @return bool
     */
    public static function incrementViewCount(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}resource_shares` SET view_count = view_count + 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 增加下载次数
     *
     * @param int $id
     * @return bool
     */
    public static function incrementDownloadCount(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}resource_shares` SET download_count = download_count + 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 更新评分
     *
     * @param int $id
     * @return bool
     */
    public static function updateRating(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}resource_shares` rs 
                SET rating = (
                    SELECT COALESCE(AVG(rating), 0) 
                    FROM `{$prefix}resource_ratings` 
                    WHERE share_id = ?
                ),
                rating_count = (
                    SELECT COUNT(*) 
                    FROM `{$prefix}resource_ratings` 
                    WHERE share_id = ?
                )
                WHERE rs.id = ?";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id, $id, $id]);
    }

    /**
     * 更新收藏数量
     *
     * @param int $id
     * @return bool
     */
    public static function updateFavoriteCount(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}resource_shares` rs 
                SET favorite_count = (
                    SELECT COUNT(*) 
                    FROM `{$prefix}resource_favorites` 
                    WHERE share_id = ?
                )
                WHERE rs.id = ?";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id, $id]);
    }

    /**
     * 获取热门分享
     *
     * @param int $limit
     * @param string|null $resourceType
     * @return array
     */
    public static function getPopularShares(int $limit = 10, ?string $resourceType = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT rs.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}resource_shares` rs
                LEFT JOIN `{$prefix}users` u ON rs.user_id = u.id
                WHERE rs.is_public = 1 AND rs.status = 1";

        $params = [];

        if ($resourceType) {
            $sql .= " AND rs.resource_type = :resource_type";
            $params[':resource_type'] = $resourceType;
        }

        $sql .= " ORDER BY rs.download_count DESC, rs.rating DESC LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取最新分享
     *
     * @param int $limit
     * @param string|null $resourceType
     * @return array
     */
    public static function getLatestShares(int $limit = 10, ?string $resourceType = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT rs.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}resource_shares` rs
                LEFT JOIN `{$prefix}users` u ON rs.user_id = u.id
                WHERE rs.is_public = 1 AND rs.status = 1";

        $params = [];

        if ($resourceType) {
            $sql .= " AND rs.resource_type = :resource_type";
            $params[':resource_type'] = $resourceType;
        }

        $sql .= " ORDER BY rs.created_at DESC LIMIT :limit";

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