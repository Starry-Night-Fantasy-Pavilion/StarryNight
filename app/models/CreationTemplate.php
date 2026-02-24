<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 创作模板模型
 */
class CreationTemplate
{
    /**
     * 创建创作模板
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}creation_templates` 
                (user_id, title, category, type, content, structure, description, tags, usage_count, is_public, price, download_count, rating, rating_count, status) 
                VALUES (:user_id, :title, :category, :type, :content, :structure, :description, :tags, :usage_count, :is_public, :price, :download_count, :rating, :rating_count, :status)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':title' => $data['title'],
            ':category' => $data['category'],
            ':type' => $data['type'],
            ':content' => $data['content'],
            ':structure' => $data['structure'] ?? null,
            ':description' => $data['description'] ?? null,
            ':tags' => $data['tags'] ?? null,
            ':usage_count' => $data['usage_count'] ?? 0,
            ':is_public' => $data['is_public'] ?? 0,
            ':price' => $data['price'] ?? 0.00,
            ':download_count' => $data['download_count'] ?? 0,
            ':rating' => $data['rating'] ?? 0.00,
            ':rating_count' => $data['rating_count'] ?? 0,
            ':status' => $data['status'] ?? 1
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取创作模板
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ct.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}creation_templates` ct
                LEFT JOIN `{$prefix}users` u ON ct.user_id = u.id
                WHERE ct.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 获取创作模板列表
     *
     * @param int $page
     * @param int $perPage
     * @param string|null $searchTerm
     * @param string|null $category
     * @param string|null $type
     * @param bool|null $isPublic
     * @param string|null $sortBy
     * @param string|null $sortOrder
     * @return array
     */
    public static function getAll(int $page = 1, int $perPage = 15, ?string $searchTerm = null, ?string $category = null, ?string $type = null, ?bool $isPublic = null, ?string $sortBy = 'created_at', ?string $sortOrder = 'desc'): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT ct.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}creation_templates` ct
                LEFT JOIN `{$prefix}users` u ON ct.user_id = u.id";

        $where = [];
        $params = [];

        if ($searchTerm) {
            $where[] = "(ct.title LIKE :term OR ct.description LIKE :term OR ct.tags LIKE :term)";
            $params[':term'] = '%' . $searchTerm . '%';
        }

        if ($category) {
            $where[] = "ct.category = :category";
            $params[':category'] = $category;
        }

        if ($type) {
            $where[] = "ct.type = :type";
            $params[':type'] = $type;
        }

        if ($isPublic !== null) {
            $where[] = "ct.is_public = :is_public";
            $params[':is_public'] = $isPublic ? 1 : 0;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        // 排序
        $allowedSortBy = ['id', 'title', 'created_at', 'usage_count', 'download_count', 'rating', 'price'];
        if (!in_array($sortBy, $allowedSortBy)) {
            $sortBy = 'created_at';
        }

        $sortOrder = strtolower($sortOrder) === 'asc' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY ct.{$sortBy} {$sortOrder}";

        // 获取总数
        $countSql = str_replace(
            "SELECT ct.*, u.username, u.nickname as user_nickname",
            "SELECT COUNT(DISTINCT ct.id)",
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
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'templates' => $templates,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 获取用户的创作模板列表
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

        $sql = "SELECT ct.*, COUNT(DISTINCT rr.id) as rating_count
                FROM `{$prefix}creation_templates` ct
                LEFT JOIN `{$prefix}resource_ratings` rr ON ct.id = rr.resource_id AND rr.resource_type = 'template'
                WHERE ct.user_id = :user_id
                GROUP BY ct.id
                ORDER BY ct.created_at DESC";

        // 获取总数
        $countSql = str_replace(
            "SELECT ct.*, COUNT(DISTINCT rr.id) as rating_count",
            "SELECT COUNT(DISTINCT ct.id)",
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
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'templates' => $templates,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 更新创作模板
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = ['title', 'category', 'type', 'content', 'structure', 'description', 'tags', 'is_public', 'price', 'status'];
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

        $sql = "UPDATE `{$prefix}creation_templates` SET " . implode(', ', $updates) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除创作模板
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
            $deleteRatings = $pdo->prepare("DELETE FROM `{$prefix}resource_ratings` WHERE resource_id = ? AND resource_type = 'template'");
            $deleteRatings->execute([$id]);

            // 删除相关的收藏
            $deleteFavorites = $pdo->prepare("DELETE FROM `{$prefix}resource_favorites` WHERE resource_id = ? AND resource_type = 'template'");
            $deleteFavorites->execute([$id]);

            // 删除相关的分享记录
            $deleteShares = $pdo->prepare("DELETE FROM `{$prefix}resource_shares` WHERE resource_id = ? AND resource_type = 'template'");
            $deleteShares->execute([$id]);

            // 删除相关的购买记录
            $deletePurchases = $pdo->prepare("DELETE FROM `{$prefix}resource_purchases` WHERE resource_id = ? AND resource_type = 'template'");
            $deletePurchases->execute([$id]);

            // 删除创作模板
            $deleteTemplate = $pdo->prepare("DELETE FROM `{$prefix}creation_templates` WHERE id = ?");
            $deleteTemplate->execute([$id]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 增加使用次数
     *
     * @param int $id
     * @return bool
     */
    public static function incrementUsage(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}creation_templates` SET usage_count = usage_count + 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 增加下载次数
     *
     * @param int $id
     * @return bool
     */
    public static function incrementDownload(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}creation_templates` SET download_count = download_count + 1 WHERE id = ?";
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

        $sql = "UPDATE `{$prefix}creation_templates` ct 
                SET rating = (
                    SELECT COALESCE(AVG(rating), 0) 
                    FROM `{$prefix}resource_ratings` 
                    WHERE resource_id = ? AND resource_type = 'template'
                ),
                rating_count = (
                    SELECT COUNT(*) 
                    FROM `{$prefix}resource_ratings` 
                    WHERE resource_id = ? AND resource_type = 'template'
                )
                WHERE ct.id = ?";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id, $id, $id]);
    }

    /**
     * 获取热门模板
     *
     * @param int $limit
     * @param string|null $category
     * @return array
     */
    public static function getPopularTemplates(int $limit = 10, ?string $category = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ct.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}creation_templates` ct
                LEFT JOIN `{$prefix}users` u ON ct.user_id = u.id
                WHERE ct.is_public = 1 AND ct.status = 1";

        $params = [];

        if ($category) {
            $sql .= " AND ct.category = :category";
            $params[':category'] = $category;
        }

        $sql .= " ORDER BY ct.usage_count DESC, ct.rating DESC LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取最新模板
     *
     * @param int $limit
     * @param string|null $category
     * @return array
     */
    public static function getLatestTemplates(int $limit = 10, ?string $category = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ct.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}creation_templates` ct
                LEFT JOIN `{$prefix}users` u ON ct.user_id = u.id
                WHERE ct.is_public = 1 AND ct.status = 1";

        $params = [];

        if ($category) {
            $sql .= " AND ct.category = :category";
            $params[':category'] = $category;
        }

        $sql .= " ORDER BY ct.created_at DESC LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 检查用户是否有权限访问模板
     *
     * @param int $userId
     * @param int $templateId
     * @return bool
     */
    public static function hasAccess(int $userId, int $templateId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ct.is_public, ct.user_id, rp.id as purchase_id
                FROM `{$prefix}creation_templates` ct
                LEFT JOIN `{$prefix}resource_purchases` rp ON ct.id = rp.resource_id AND rp.resource_type = 'template' AND rp.buyer_id = ?
                WHERE ct.id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $templateId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return false;
        }

        // 所有者可以访问
        if ($result['user_id'] == $userId) {
            return true;
        }

        // 公开的可以访问
        if ($result['is_public'] == 1) {
            return true;
        }

        // 已购买的可以访问
        if ($result['purchase_id']) {
            return true;
        }

        return false;
    }

    /**
     * 获取分类列表
     */
    public static function getCategories(): array
    {
        return [
            'novel' => '小说模板',
            'anime' => '动漫模板',
            'music' => '音乐模板'
        ];
    }

    /**
     * 获取类型列表
     */
    public static function getTypes(): array
    {
        return [
            'novel_opening' => '开头模板',
            'novel_plot' => '情节模板',
            'novel_dialogue' => '对话模板',
            'novel_ending' => '结局模板',
            'anime_plan' => '企划模板',
            'anime_script' => '脚本模板',
            'anime_character' => '角色设计模板',
            'anime_scene' => '场景设计模板',
            'anime_storyboard' => '分镜模板',
            'music_lyrics' => '歌词模板',
            'music_melody' => '旋律模板',
            'music_arrangement' => '编曲模板',
            'music_mixing' => '混音模板'
        ];
    }
}