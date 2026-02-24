<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * AI提示词模板模型
 */
class AIPromptTemplate
{
    /**
     * 创建提示词模板
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}ai_prompt_templates` 
                (user_id, title, category, type, content, variables, description, tags, usage_count, is_public, price, download_count, rating, rating_count, status) 
                VALUES (:user_id, :title, :category, :type, :content, :variables, :description, :tags, :usage_count, :is_public, :price, :download_count, :rating, :rating_count, :status)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':title' => $data['title'],
            ':category' => $data['category'] ?? 'general',
            ':type' => $data['type'] ?? 'custom',
            ':content' => $data['content'],
            ':variables' => $data['variables'] ?? null,
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
     * 根据ID获取提示词模板
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT pt.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}ai_prompt_templates` pt
                LEFT JOIN `{$prefix}users` u ON pt.user_id = u.id
                WHERE pt.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 获取提示词模板列表
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

        $sql = "SELECT pt.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}ai_prompt_templates` pt
                LEFT JOIN `{$prefix}users` u ON pt.user_id = u.id";

        $where = [];
        $params = [];

        if ($searchTerm) {
            $where[] = "(pt.title LIKE :term OR pt.description LIKE :term OR pt.tags LIKE :term)";
            $params[':term'] = '%' . $searchTerm . '%';
        }

        if ($category) {
            $where[] = "pt.category = :category";
            $params[':category'] = $category;
        }

        if ($type) {
            $where[] = "pt.type = :type";
            $params[':type'] = $type;
        }

        if ($isPublic !== null) {
            $where[] = "pt.is_public = :is_public";
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
        $sql .= " ORDER BY pt.{$sortBy} {$sortOrder}";

        // 获取总数
        $countSql = str_replace(
            "SELECT pt.*, u.username, u.nickname as user_nickname",
            "SELECT COUNT(DISTINCT pt.id)",
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
     * 获取用户的提示词模板列表
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

        $sql = "SELECT pt.*, COUNT(DISTINCT rr.id) as rating_count
                FROM `{$prefix}ai_prompt_templates` pt
                LEFT JOIN `{$prefix}resource_ratings` rr ON pt.id = rr.resource_id AND rr.resource_type = 'prompt'
                WHERE pt.user_id = :user_id
                GROUP BY pt.id
                ORDER BY pt.created_at DESC";

        // 获取总数
        $countSql = str_replace(
            "SELECT pt.*, COUNT(DISTINCT rr.id) as rating_count",
            "SELECT COUNT(DISTINCT pt.id)",
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
     * 更新提示词模板
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = ['title', 'category', 'type', 'content', 'variables', 'description', 'tags', 'is_public', 'price', 'status'];
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

        $sql = "UPDATE `{$prefix}ai_prompt_templates` SET " . implode(', ', $updates) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除提示词模板
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
            $deleteRatings = $pdo->prepare("DELETE FROM `{$prefix}resource_ratings` WHERE resource_id = ? AND resource_type = 'prompt'");
            $deleteRatings->execute([$id]);

            // 删除相关的收藏
            $deleteFavorites = $pdo->prepare("DELETE FROM `{$prefix}resource_favorites` WHERE resource_id = ? AND resource_type = 'prompt'");
            $deleteFavorites->execute([$id]);

            // 删除相关的分享记录
            $deleteShares = $pdo->prepare("DELETE FROM `{$prefix}resource_shares` WHERE resource_id = ? AND resource_type = 'prompt'");
            $deleteShares->execute([$id]);

            // 删除相关的购买记录
            $deletePurchases = $pdo->prepare("DELETE FROM `{$prefix}resource_purchases` WHERE resource_id = ? AND resource_type = 'prompt'");
            $deletePurchases->execute([$id]);

            // 删除提示词模板
            $deleteTemplate = $pdo->prepare("DELETE FROM `{$prefix}ai_prompt_templates` WHERE id = ?");
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

        $sql = "UPDATE `{$prefix}ai_prompt_templates` SET usage_count = usage_count + 1 WHERE id = ?";
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

        $sql = "UPDATE `{$prefix}ai_prompt_templates` SET download_count = download_count + 1 WHERE id = ?";
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

        $sql = "UPDATE `{$prefix}ai_prompt_templates` pt 
                SET rating = (
                    SELECT COALESCE(AVG(rating), 0) 
                    FROM `{$prefix}resource_ratings` 
                    WHERE resource_id = ? AND resource_type = 'prompt'
                ),
                rating_count = (
                    SELECT COUNT(*) 
                    FROM `{$prefix}resource_ratings` 
                    WHERE resource_id = ? AND resource_type = 'prompt'
                )
                WHERE pt.id = ?";

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

        $sql = "SELECT pt.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}ai_prompt_templates` pt
                LEFT JOIN `{$prefix}users` u ON pt.user_id = u.id
                WHERE pt.is_public = 1 AND pt.status = 1";

        $params = [];

        if ($category) {
            $sql .= " AND pt.category = :category";
            $params[':category'] = $category;
        }

        $sql .= " ORDER BY pt.usage_count DESC, pt.rating DESC LIMIT :limit";

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

        $sql = "SELECT pt.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}ai_prompt_templates` pt
                LEFT JOIN `{$prefix}users` u ON pt.user_id = u.id
                WHERE pt.is_public = 1 AND pt.status = 1";

        $params = [];

        if ($category) {
            $sql .= " AND pt.category = :category";
            $params[':category'] = $category;
        }

        $sql .= " ORDER BY pt.created_at DESC LIMIT :limit";

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

        $sql = "SELECT pt.is_public, pt.user_id, rp.id as purchase_id
                FROM `{$prefix}ai_prompt_templates` pt
                LEFT JOIN `{$prefix}resource_purchases` rp ON pt.id = rp.resource_id AND rp.resource_type = 'prompt' AND rp.buyer_id = ?
                WHERE pt.id = ?";

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
     * 渲染模板
     *
     * @param int $templateId
     * @param array $variables
     * @return string
     */
    public static function render(int $templateId, array $variables = []): string
    {
        $template = self::find($templateId);
        if (!$template) {
            return '';
        }

        $content = $template['content'];
        
        // 替换变量
        foreach ($variables as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }

        return $content;
    }

    /**
     * 获取分类列表
     */
    public static function getCategories(): array
    {
        return [
            'continue' => '续写类',
            'rewrite' => '改写类',
            'expand' => '扩写类',
            'analyze' => '分析类',
            'generate' => '生成类',
            'anime' => '动漫创作类',
            'music' => '音乐创作类'
        ];
    }

    /**
     * 获取类型列表
     */
    public static function getTypes(): array
    {
        return [
            'system' => '系统预设',
            'custom' => '用户自定义'
        ];
    }
}
