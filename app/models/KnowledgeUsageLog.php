<?php

namespace app\models;

use app\services\Database;
use PDO;

class KnowledgeUsageLog
{
    /**
     * 创建使用记录
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}knowledge_usage_logs` 
                (user_id, knowledge_base_id, knowledge_item_ids, usage_type, context, ai_response) 
                VALUES (:user_id, :knowledge_base_id, :knowledge_item_ids, :usage_type, :context, :ai_response)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':knowledge_base_id' => $data['knowledge_base_id'],
            ':knowledge_item_ids' => $data['knowledge_item_ids'] ?? null,
            ':usage_type' => $data['usage_type'],
            ':context' => $data['context'] ?? null,
            ':ai_response' => $data['ai_response'] ?? null
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 获取用户的使用记录
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @param string|null $usageType
     * @return array
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 20, ?string $usageType = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT kul.*, kb.title as knowledge_base_title
                FROM `{$prefix}knowledge_usage_logs` kul
                LEFT JOIN `{$prefix}knowledge_bases` kb ON kul.knowledge_base_id = kb.id
                WHERE kul.user_id = :user_id";

        $params = [':user_id' => $userId];

        if ($usageType) {
            $sql .= " AND kul.usage_type = :usage_type";
            $params[':usage_type'] = $usageType;
        }

        $sql .= " ORDER BY kul.created_at DESC";

        // 获取总数
        $countSql = str_replace(
            "SELECT kul.*, kb.title as knowledge_base_title",
            "SELECT COUNT(DISTINCT kul.id)",
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
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'logs' => $logs,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 获取知识库的使用记录
     *
     * @param int $knowledgeBaseId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getByKnowledgeBase(int $knowledgeBaseId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT kul.*, u.username, u.nickname
                FROM `{$prefix}knowledge_usage_logs` kul
                LEFT JOIN `{$prefix}users` u ON kul.user_id = u.id
                WHERE kul.knowledge_base_id = :knowledge_base_id
                ORDER BY kul.created_at DESC";

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}knowledge_usage_logs` WHERE knowledge_base_id = :knowledge_base_id";
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
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'logs' => $logs,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 获取使用统计
     *
     * @param int $knowledgeBaseId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public static function getStats(int $knowledgeBaseId, ?string $startDate = null, ?string $endDate = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_usage,
                    COUNT(DISTINCT user_id) as unique_users,
                    SUM(CASE WHEN usage_type = 'search' THEN 1 ELSE 0 END) as search_count,
                    SUM(CASE WHEN usage_type = 'reference' THEN 1 ELSE 0 END) as reference_count,
                    SUM(CASE WHEN usage_type = 'citation' THEN 1 ELSE 0 END) as citation_count,
                    DATE(created_at) as usage_date
                FROM `{$prefix}knowledge_usage_logs` 
                WHERE knowledge_base_id = :knowledge_base_id";

        $params = [':knowledge_base_id' => $knowledgeBaseId];

        if ($startDate) {
            $sql .= " AND created_at >= :start_date";
            $params[':start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND created_at <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $sql .= " GROUP BY DATE(created_at) ORDER BY usage_date DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取热门知识库
     *
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public static function getPopularKnowledgeBases(int $limit = 10, ?string $startDate = null, ?string $endDate = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    kb.id, kb.title, kb.description,
                    COUNT(kul.id) as usage_count,
                    COUNT(DISTINCT kul.user_id) as unique_users
                FROM `{$prefix}knowledge_usage_logs` kul
                LEFT JOIN `{$prefix}knowledge_bases` kb ON kul.knowledge_base_id = kb.id";

        $params = [];

        if ($startDate) {
            $sql .= " WHERE kul.created_at >= :start_date";
            $params[':start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= (empty($params) ? " WHERE" : " AND") . " kul.created_at <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $sql .= " GROUP BY kb.id, kb.title, kb.description
                 ORDER BY usage_count DESC, unique_users DESC
                 LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 删除旧的使用记录
     *
     * @param int $daysOld 删除多少天前的记录
     * @return int 删除的记录数
     */
    public static function deleteOldRecords(int $daysOld = 90): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}knowledge_usage_logs` 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days_old DAY)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':days_old', $daysOld, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}