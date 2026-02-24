<?php

namespace app\models;

use app\services\Database;
use PDO;

class KnowledgeItem
{
    /**
     * 创建知识条目
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}knowledge_items` 
                (knowledge_base_id, title, content, content_type, file_path, file_size, file_type, tags, order_index, embedding_vector) 
                VALUES (:knowledge_base_id, :title, :content, :content_type, :file_path, :file_size, :file_type, :tags, :order_index, :embedding_vector)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':knowledge_base_id' => $data['knowledge_base_id'],
            ':title' => $data['title'],
            ':content' => $data['content'],
            ':content_type' => $data['content_type'] ?? 'text',
            ':file_path' => $data['file_path'] ?? null,
            ':file_size' => $data['file_size'] ?? null,
            ':file_type' => $data['file_type'] ?? null,
            ':tags' => $data['tags'] ?? null,
            ':order_index' => $data['order_index'] ?? 0,
            ':embedding_vector' => isset($data['embedding_vector']) ? json_encode($data['embedding_vector']) : null
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取知识条目
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ki.*, kb.title as knowledge_base_title, kb.user_id as kb_owner_id
                FROM `{$prefix}knowledge_items` ki
                LEFT JOIN `{$prefix}knowledge_bases` kb ON ki.knowledge_base_id = kb.id
                WHERE ki.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['embedding_vector']) {
            $result['embedding_vector'] = json_decode($result['embedding_vector'], true);
        }

        return $result ?: null;
    }

    /**
     * 获取知识库的所有条目
     *
     * @param int $knowledgeBaseId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getByKnowledgeBase(int $knowledgeBaseId, int $page = 1, int $perPage = 50): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM `{$prefix}knowledge_items` 
                WHERE knowledge_base_id = :knowledge_base_id 
                ORDER BY order_index ASC, created_at ASC";

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}knowledge_items` WHERE knowledge_base_id = :knowledge_base_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':knowledge_base_id' => $knowledgeBaseId]);
        $totalRecords = $countStmt->fetchColumn();

        // 获取分页数据
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':knowledge_base_id', $knowledgeBaseId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 处理embedding_vector字段
        foreach ($items as &$item) {
            if ($item['embedding_vector']) {
                $item['embedding_vector'] = json_decode($item['embedding_vector'], true);
            }
        }

        return [
            'items' => $items,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 搜索知识条目
     *
     * @param int $knowledgeBaseId
     * @param string $query
     * @param int $limit
     * @return array
     */
    public static function search(int $knowledgeBaseId, string $query, int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT *, MATCH(title, content) AGAINST(:query IN NATURAL LANGUAGE MODE) as relevance
                FROM `{$prefix}knowledge_items` 
                WHERE knowledge_base_id = :knowledge_base_id 
                AND MATCH(title, content) AGAINST(:query IN NATURAL LANGUAGE MODE)
                ORDER BY relevance DESC
                LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':knowledge_base_id', $knowledgeBaseId, PDO::PARAM_INT);
        $stmt->bindValue(':query', $query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 处理embedding_vector字段
        foreach ($items as &$item) {
            if ($item['embedding_vector']) {
                $item['embedding_vector'] = json_decode($item['embedding_vector'], true);
            }
        }

        return $items;
    }

    /**
     * 全局搜索知识条目（跨多个知识库）
     *
     * @param int $userId
     * @param string $query
     * @param array $knowledgeBaseIds
     * @param int $limit
     * @return array
     */
    public static function globalSearch(int $userId, string $query, array $knowledgeBaseIds = [], int $limit = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ki.*, kb.title as knowledge_base_title, kb.visibility, kb.user_id as kb_owner_id,
                MATCH(ki.title, ki.content) AGAINST(:query IN NATURAL LANGUAGE MODE) as relevance
                FROM `{$prefix}knowledge_items` ki
                LEFT JOIN `{$prefix}knowledge_bases` kb ON ki.knowledge_base_id = kb.id
                WHERE MATCH(ki.title, ki.content) AGAINST(:query IN NATURAL LANGUAGE MODE)";

        $params = [':query' => $query];

        // 限制搜索范围
        if (!empty($knowledgeBaseIds)) {
            $placeholders = str_repeat('?,', count($knowledgeBaseIds) - 1) . '?';
            $sql .= " AND ki.knowledge_base_id IN ($placeholders)";
            $params = array_merge($params, $knowledgeBaseIds);
        } else {
            // 只搜索用户有权限访问的知识库
            $sql .= " AND (kb.user_id = ? OR kb.visibility = 'public' OR 
                     ki.knowledge_base_id IN (
                         SELECT knowledge_base_id FROM `{$prefix}knowledge_purchases` 
                         WHERE user_id = ? AND status = 'completed'
                     ))";
            $params[] = $userId;
            $params[] = $userId;
        }

        $sql .= " ORDER BY relevance DESC LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        
        // 绑定参数
        $index = 1;
        foreach ($params as $param) {
            if (is_int($param)) {
                $stmt->bindValue($index, $param, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($index, $param);
            }
            $index++;
        }
        
        $stmt->bindValue($index, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 处理embedding_vector字段
        foreach ($items as &$item) {
            if ($item['embedding_vector']) {
                $item['embedding_vector'] = json_decode($item['embedding_vector'], true);
            }
        }

        return $items;
    }

    /**
     * 更新知识条目
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = ['title', 'content', 'content_type', 'file_path', 'file_size', 'file_type', 'tags', 'order_index'];
        $updates = [];
        $params = [':id' => $id];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updates[] = "`$field` = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (isset($data['embedding_vector'])) {
            $updates[] = "`embedding_vector` = :embedding_vector";
            $params[":embedding_vector"] = json_encode($data['embedding_vector']);
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE `{$prefix}knowledge_items` SET " . implode(', ', $updates) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除知识条目
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}knowledge_items` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 批量更新排序
     *
     * @param array $itemOrders 格式: [item_id => order_index, ...]
     * @return bool
     */
    public static function updateOrder(array $itemOrders): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            $sql = "UPDATE `{$prefix}knowledge_items` SET order_index = :order_index WHERE id = :id";
            $stmt = $pdo->prepare($sql);

            foreach ($itemOrders as $id => $order) {
                $stmt->execute([
                    ':order_index' => $order,
                    ':id' => $id
                ]);
            }

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 获取知识库条目统计
     *
     * @param int $knowledgeBaseId
     * @return array
     */
    public static function getStats(int $knowledgeBaseId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_items,
                    SUM(CASE WHEN content_type = 'text' THEN 1 ELSE 0 END) as text_items,
                    SUM(CASE WHEN content_type = 'document' THEN 1 ELSE 0 END) as document_items,
                    SUM(CASE WHEN content_type = 'image' THEN 1 ELSE 0 END) as image_items,
                    SUM(CASE WHEN content_type = 'link' THEN 1 ELSE 0 END) as link_items,
                    SUM(file_size) as total_file_size
                FROM `{$prefix}knowledge_items` 
                WHERE knowledge_base_id = :knowledge_base_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':knowledge_base_id' => $knowledgeBaseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}