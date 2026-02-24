<?php

namespace app\models;

use app\services\Database;
use PDO;

class KnowledgeBase
{
    /**
     * 创建知识库
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}knowledge_bases` 
                (user_id, title, description, category, tags, visibility, price, status) 
                VALUES (:user_id, :title, :description, :category, :tags, :visibility, :price, :status)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':category' => $data['category'] ?? null,
            ':tags' => $data['tags'] ?? null,
            ':visibility' => $data['visibility'] ?? 'private',
            ':price' => $data['price'] ?? 0.00,
            ':status' => $data['status'] ?? 'draft'
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取知识库
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            // 优先尝试带统计信息的查询，如果表结构不匹配则降级为简单查询，避免 500
            $sql = "SELECT kb.*, u.username, u.nickname as user_nickname
                    FROM `{$prefix}knowledge_bases` kb
                    LEFT JOIN `{$prefix}users` u ON kb.user_id = u.id
                    WHERE kb.id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('KnowledgeBase::find SQL error: ' . $e->getMessage());
            $stmt = $pdo->prepare("SELECT * FROM `{$prefix}knowledge_bases` WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $result ?: null;
    }

    /**
     * 获取知识库列表
     *
     * @param int $page
     * @param int $perPage
     * @param string|null $searchTerm
     * @param string|null $category
     * @param string|null $visibility
     * @param string|null $sortBy
     * @param string|null $sortOrder
     * @return array
     */
    public static function getAll(int $page = 1, int $perPage = 15, ?string $searchTerm = null, ?string $category = null, ?string $visibility = null, ?string $sortBy = 'created_at', ?string $sortOrder = 'desc'): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        // 由于实际迁移 (001/012) 中知识库表字段较少，这里采用兼容性更好的简单查询，避免引用不存在字段导致 500
        $sql = "SELECT kb.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}knowledge_bases` kb
                LEFT JOIN `{$prefix}users` u ON kb.user_id = u.id";

        $where = [];
        $params = [];

        if ($searchTerm) {
            $where[] = "(kb.title LIKE :term OR kb.description LIKE :term)";
            $params[':term'] = '%' . $searchTerm . '%';
        }

        if ($category) {
            $where[] = "kb.category = :category";
            $params[':category'] = $category;
        }

        if ($visibility) {
            $where[] = "kb.visibility = :visibility";
            $params[':visibility'] = $visibility;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        // 排序字段限制为实际存在的字段，避免 Unknown column 报错
        $allowedSortBy = ['id', 'name', 'created_at'];
        if (!in_array($sortBy, $allowedSortBy)) {
            $sortBy = 'created_at';
        }

        $sortOrder = strtolower($sortOrder) === 'asc' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY kb.{$sortBy} {$sortOrder}";

        try {
            // 获取总数
            $countSql = "SELECT COUNT(*) FROM `{$prefix}knowledge_bases` kb";
            if (!empty($where)) {
                $countSql .= " WHERE " . implode(" AND ", $where);
            }
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalRecords = (int)$countStmt->fetchColumn();

            // 获取分页数据
            $sql .= " LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $knowledgeBases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('KnowledgeBase::getAll SQL error: ' . $e->getMessage());
            // 兜底：如果连简单查询都失败，返回空结果，避免 500
            $knowledgeBases = [];
            $totalRecords = 0;
        }

        return [
            'knowledge_bases' => $knowledgeBases,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 获取用户的知识库列表
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

        $sql = "SELECT kb.*
                FROM `{$prefix}knowledge_bases` kb
                WHERE kb.user_id = :user_id
                ORDER BY kb.created_at DESC";

        try {
            // 获取总数
            $countSql = "SELECT COUNT(*) FROM `{$prefix}knowledge_bases` kb WHERE kb.user_id = :user_id";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute([':user_id' => $userId]);
            $totalRecords = (int)$countStmt->fetchColumn();

            // 获取分页数据
            $sql .= " LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $knowledgeBases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('KnowledgeBase::getByUser SQL error: ' . $e->getMessage());
            $knowledgeBases = [];
            $totalRecords = 0;
        }

        return [
            'knowledge_bases' => $knowledgeBases,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 更新知识库
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = ['title', 'description', 'category', 'tags', 'visibility', 'price', 'status'];
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

        $sql = "UPDATE `{$prefix}knowledge_bases` SET " . implode(', ', $updates) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除知识库
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

            // 删除相关的知识条目
            $deleteItems = $pdo->prepare("DELETE FROM `{$prefix}knowledge_items` WHERE knowledge_base_id = ?");
            $deleteItems->execute([$id]);

            // 删除相关的评分
            $deleteRatings = $pdo->prepare("DELETE FROM `{$prefix}knowledge_ratings` WHERE knowledge_base_id = ?");
            $deleteRatings->execute([$id]);

            // 删除相关的购买记录
            $deletePurchases = $pdo->prepare("DELETE FROM `{$prefix}knowledge_purchases` WHERE knowledge_base_id = ?");
            $deletePurchases->execute([$id]);

            // 删除知识库
            $deleteKB = $pdo->prepare("DELETE FROM `{$prefix}knowledge_bases` WHERE id = ?");
            $deleteKB->execute([$id]);

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

        $sql = "UPDATE `{$prefix}knowledge_bases` SET view_count = view_count + 1 WHERE id = ?";
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

        $sql = "UPDATE `{$prefix}knowledge_bases` SET download_count = download_count + 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 检查用户是否有权限访问知识库
     *
     * @param int $userId
     * @param int $knowledgeBaseId
     * @return bool
     */
    public static function hasAccess(int $userId, int $knowledgeBaseId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT kb.visibility, kb.user_id, kp.id as purchase_id
                FROM `{$prefix}knowledge_bases` kb
                LEFT JOIN `{$prefix}knowledge_purchases` kp ON kb.id = kp.knowledge_base_id AND kp.user_id = ? AND kp.status = 'completed'
                WHERE kb.id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $knowledgeBaseId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return false;
        }

        // 所有者可以访问
        if ($result['user_id'] == $userId) {
            return true;
        }

        // 公开的可以访问
        if ($result['visibility'] == 'public') {
            return true;
        }

        // 已购买的可以访问
        if ($result['purchase_id']) {
            return true;
        }

        return false;
    }
}