<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * AI智能体模型
 */
class AIAgent
{
    /**
     * 创建AI智能体
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}ai_agents` 
                (user_id, name, avatar, category, type, description, system_prompt, model_config, capabilities, usage_count, is_public, price, download_count, rating, rating_count, status) 
                VALUES (:user_id, :name, :avatar, :category, :type, :description, :system_prompt, :model_config, :capabilities, :usage_count, :is_public, :price, :download_count, :rating, :rating_count, :status)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':name' => $data['name'],
            ':avatar' => $data['avatar'] ?? null,
            ':category' => $data['category'],
            ':type' => $data['type'],
            ':description' => $data['description'] ?? null,
            ':system_prompt' => $data['system_prompt'],
            ':model_config' => $data['model_config'] ?? null,
            ':capabilities' => $data['capabilities'] ?? null,
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
     * 根据ID获取AI智能体
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ag.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}ai_agents` ag
                LEFT JOIN `{$prefix}users` u ON ag.user_id = u.id
                WHERE ag.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 获取AI智能体列表
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

        $sql = "SELECT ag.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}ai_agents` ag
                LEFT JOIN `{$prefix}users` u ON ag.user_id = u.id";

        $where = [];
        $params = [];

        if ($searchTerm) {
            $where[] = "(ag.name LIKE :term OR ag.description LIKE :term)";
            $params[':term'] = '%' . $searchTerm . '%';
        }

        if ($category) {
            $where[] = "ag.category = :category";
            $params[':category'] = $category;
        }

        if ($type) {
            $where[] = "ag.type = :type";
            $params[':type'] = $type;
        }

        if ($isPublic !== null) {
            $where[] = "ag.is_public = :is_public";
            $params[':is_public'] = $isPublic ? 1 : 0;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        // 排序
        $allowedSortBy = ['id', 'name', 'created_at', 'usage_count', 'download_count', 'rating', 'price'];
        if (!in_array($sortBy, $allowedSortBy)) {
            $sortBy = 'created_at';
        }

        $sortOrder = strtolower($sortOrder) === 'asc' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY ag.{$sortBy} {$sortOrder}";

        // 获取总数
        $countSql = str_replace(
            "SELECT ag.*, u.username, u.nickname as user_nickname",
            "SELECT COUNT(DISTINCT ag.id)",
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
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'agents' => $agents,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 获取用户的AI智能体列表
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

        $sql = "SELECT ag.*, COUNT(DISTINCT rr.id) as rating_count
                FROM `{$prefix}ai_agents` ag
                LEFT JOIN `{$prefix}resource_ratings` rr ON ag.id = rr.resource_id AND rr.resource_type = 'agent'
                WHERE ag.user_id = :user_id
                GROUP BY ag.id
                ORDER BY ag.created_at DESC";

        // 获取总数
        $countSql = str_replace(
            "SELECT ag.*, COUNT(DISTINCT rr.id) as rating_count",
            "SELECT COUNT(DISTINCT ag.id)",
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
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'agents' => $agents,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 更新AI智能体
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = ['name', 'avatar', 'category', 'type', 'description', 'system_prompt', 'model_config', 'capabilities', 'is_public', 'price', 'status'];
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

        $sql = "UPDATE `{$prefix}ai_agents` SET " . implode(', ', $updates) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除AI智能体
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
            $deleteRatings = $pdo->prepare("DELETE FROM `{$prefix}resource_ratings` WHERE resource_id = ? AND resource_type = 'agent'");
            $deleteRatings->execute([$id]);

            // 删除相关的收藏
            $deleteFavorites = $pdo->prepare("DELETE FROM `{$prefix}resource_favorites` WHERE resource_id = ? AND resource_type = 'agent'");
            $deleteFavorites->execute([$id]);

            // 删除相关的分享记录
            $deleteShares = $pdo->prepare("DELETE FROM `{$prefix}resource_shares` WHERE resource_id = ? AND resource_type = 'agent'");
            $deleteShares->execute([$id]);

            // 删除相关的购买记录
            $deletePurchases = $pdo->prepare("DELETE FROM `{$prefix}resource_purchases` WHERE resource_id = ? AND resource_type = 'agent'");
            $deletePurchases->execute([$id]);

            // 删除AI智能体
            $deleteAgent = $pdo->prepare("DELETE FROM `{$prefix}ai_agents` WHERE id = ?");
            $deleteAgent->execute([$id]);

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

        $sql = "UPDATE `{$prefix}ai_agents` SET usage_count = usage_count + 1 WHERE id = ?";
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

        $sql = "UPDATE `{$prefix}ai_agents` SET download_count = download_count + 1 WHERE id = ?";
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

        $sql = "UPDATE `{$prefix}ai_agents` ag 
                SET rating = (
                    SELECT COALESCE(AVG(rating), 0) 
                    FROM `{$prefix}resource_ratings` 
                    WHERE resource_id = ? AND resource_type = 'agent'
                ),
                rating_count = (
                    SELECT COUNT(*) 
                    FROM `{$prefix}resource_ratings` 
                    WHERE resource_id = ? AND resource_type = 'agent'
                )
                WHERE ag.id = ?";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id, $id, $id]);
    }

    /**
     * 获取热门智能体
     *
     * @param int $limit
     * @param string|null $category
     * @return array
     */
    public static function getPopularAgents(int $limit = 10, ?string $category = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ag.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}ai_agents` ag
                LEFT JOIN `{$prefix}users` u ON ag.user_id = u.id
                WHERE ag.is_public = 1 AND ag.status = 1";

        $params = [];

        if ($category) {
            $sql .= " AND ag.category = :category";
            $params[':category'] = $category;
        }

        $sql .= " ORDER BY ag.usage_count DESC, ag.rating DESC LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取最新智能体
     *
     * @param int $limit
     * @param string|null $category
     * @return array
     */
    public static function getLatestAgents(int $limit = 10, ?string $category = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ag.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}ai_agents` ag
                LEFT JOIN `{$prefix}users` u ON ag.user_id = u.id
                WHERE ag.is_public = 1 AND ag.status = 1";

        $params = [];

        if ($category) {
            $sql .= " AND ag.category = :category";
            $params[':category'] = $category;
        }

        $sql .= " ORDER BY ag.created_at DESC LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 检查用户是否有权限访问智能体
     *
     * @param int $userId
     * @param int $agentId
     * @return bool
     */
    public static function hasAccess(int $userId, int $agentId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ag.is_public, ag.user_id, rp.id as purchase_id
                FROM `{$prefix}ai_agents` ag
                LEFT JOIN `{$prefix}resource_purchases` rp ON ag.id = rp.resource_id AND rp.resource_type = 'agent' AND rp.buyer_id = ?
                WHERE ag.id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $agentId]);
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
            'general' => '通用助手',
            'novel' => '小说智能体',
            'anime' => '动漫智能体',
            'music' => '音乐智能体'
        ];
    }

    /**
     * 获取类型列表
     */
    public static function getTypes(): array
    {
        return [
            'text_polish' => '文字润色',
            'typo_check' => '错别字检查',
            'grammar_correct' => '语法修正',
            'plot_advisor' => '情节顾问',
            'character_expert' => '角色专家',
            'worldview_guardian' => '世界观守护者',
            'style_analyzer' => '文风分析师',
            'storyboard_guide' => '分镜指导',
            'scene_builder' => '场景构建师',
            'character_designer' => '角色形象设计师',
            'composition_assistant' => '作曲助手',
            'arrangement_advisor' => '编曲顾问',
            'mixing_engineer' => '混音工程师'
        ];
    }
}
