<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 向量数据库使用日志模型
 */
class VectorDbUsageLog
{
    /**
     * 创建向量数据库使用日志
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}vector_db_usage_logs` 
                (user_id, operation_type, vector_db_type, embedding_model, content_type, content_id, vector_id, tokens_used, execution_time, status, error_message) 
                VALUES (:user_id, :operation_type, :vector_db_type, :embedding_model, :content_type, :content_id, :vector_id, :tokens_used, :execution_time, :status, :error_message)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':operation_type' => $data['operation_type'],
            ':vector_db_type' => $data['vector_db_type'],
            ':embedding_model' => $data['embedding_model'],
            ':content_type' => $data['content_type'] ?? null,
            ':content_id' => $data['content_id'] ?? null,
            ':vector_id' => $data['vector_id'] ?? null,
            ':tokens_used' => $data['tokens_used'] ?? null,
            ':execution_time' => $data['execution_time'] ?? null,
            ':status' => $data['status'] ?? 'success',
            ':error_message' => $data['error_message'] ?? null
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取向量数据库使用日志
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT vdl.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}vector_db_usage_logs` vdl
                LEFT JOIN `{$prefix}users` u ON vdl.user_id = u.id
                WHERE vdl.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 获取向量数据库使用日志列表
     *
     * @param int $page
     * @param int $perPage
     * @param int|null $userId
     * @param string|null $operationType
     * @param string|null $vectorDbType
     * @param string|null $embeddingModel
     * @param string|null $contentType
     * @param string|null $status
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public static function getAll(int $page = 1, int $perPage = 15, ?int $userId = null, ?string $operationType = null, ?string $vectorDbType = null, ?string $embeddingModel = null, ?string $contentType = null, ?string $status = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT vdl.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}vector_db_usage_logs` vdl
                LEFT JOIN `{$prefix}users` u ON vdl.user_id = u.id";

        $where = [];
        $params = [];

        if ($userId) {
            $where[] = "vdl.user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        if ($operationType) {
            $where[] = "vdl.operation_type = :operation_type";
            $params[':operation_type'] = $operationType;
        }

        if ($vectorDbType) {
            $where[] = "vdl.vector_db_type = :vector_db_type";
            $params[':vector_db_type'] = $vectorDbType;
        }

        if ($embeddingModel) {
            $where[] = "vdl.embedding_model = :embedding_model";
            $params[':embedding_model'] = $embeddingModel;
        }

        if ($contentType) {
            $where[] = "vdl.content_type = :content_type";
            $params[':content_type'] = $contentType;
        }

        if ($status) {
            $where[] = "vdl.status = :status";
            $params[':status'] = $status;
        }

        if ($dateFrom) {
            $where[] = "DATE(vdl.created_at) >= :date_from";
            $params[':date_from'] = $dateFrom;
        }

        if ($dateTo) {
            $where[] = "DATE(vdl.created_at) <= :date_to";
            $params[':date_to'] = $dateTo;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY vdl.created_at DESC";

        // 获取总数
        $countSql = str_replace(
            "SELECT vdl.*, u.username, u.nickname as user_nickname",
            "SELECT COUNT(DISTINCT vdl.id)",
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
     * 获取用户的使用日志
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @param string|null $operationType
     * @param string|null $status
     * @return array
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 15, ?string $operationType = null, ?string $status = null): array
    {
        return self::getAll($page, $perPage, $userId, $operationType, null, null, null, $status);
    }

    /**
     * 获取操作类型的使用日志
     *
     * @param string $operationType
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getByOperationType(string $operationType, int $page = 1, int $perPage = 15): array
    {
        return self::getAll($page, $perPage, null, $operationType);
    }

    /**
     * 获取向量数据库类型的使用日志
     *
     * @param string $vectorDbType
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getByVectorDbType(string $vectorDbType, int $page = 1, int $perPage = 15): array
    {
        return self::getAll($page, $perPage, null, null, $vectorDbType);
    }

    /**
     * 获取嵌入式模型的使用日志
     *
     * @param string $embeddingModel
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getByEmbeddingModel(string $embeddingModel, int $page = 1, int $perPage = 15): array
    {
        return self::getAll($page, $perPage, null, null, null, $embeddingModel);
    }

    /**
     * 删除向量数据库使用日志
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}vector_db_usage_logs` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 批量删除使用日志
     *
     * @param int|null $userId
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return bool
     */
    public static function deleteBatch(?int $userId = null, ?string $dateFrom = null, ?string $dateTo = null): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}vector_db_usage_logs`";
        $where = [];
        $params = [];

        if ($userId) {
            $where[] = "user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        if ($dateFrom) {
            $where[] = "DATE(created_at) >= :date_from";
            $params[':date_from'] = $dateFrom;
        }

        if ($dateTo) {
            $where[] = "DATE(created_at) <= :date_to";
            $params[':date_to'] = $dateTo;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 获取使用统计
     *
     * @param int|null $userId
     * @param string|null $dateRange
     * @return array
     */
    public static function getUsageStats(?int $userId = null, ?string $dateRange = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    operation_type,
                    vector_db_type,
                    embedding_model,
                    content_type,
                    COUNT(*) as total_operations,
                    SUM(tokens_used) as total_tokens_used,
                    AVG(execution_time) as avg_execution_time,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_operations,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_operations
                FROM `{$prefix}vector_db_usage_logs`";

        $params = [];
        $where = [];

        if ($userId) {
            $where[] = "user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        if ($dateRange) {
            switch ($dateRange) {
                case 'today':
                    $where[] = "DATE(created_at) = CURDATE()";
                    break;
                case 'week':
                    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                case 'year':
                    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                    break;
            }
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " GROUP BY operation_type, vector_db_type, embedding_model, content_type";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [];
        foreach ($results as $result) {
            $key = $result['operation_type'] . '_' . $result['vector_db_type'] . '_' . $result['embedding_model'];
            $stats[$key] = [
                'operation_type' => $result['operation_type'],
                'vector_db_type' => $result['vector_db_type'],
                'embedding_model' => $result['embedding_model'],
                'content_type' => $result['content_type'],
                'total_operations' => (int)$result['total_operations'],
                'total_tokens_used' => (int)$result['total_tokens_used'],
                'avg_execution_time' => round((float)$result['avg_execution_time'], 2),
                'success_operations' => (int)$result['success_operations'],
                'failed_operations' => (int)$result['failed_operations'],
                'success_rate' => round((int)$result['success_operations'] / (int)$result['total_operations'] * 100, 2)
            ];
        }

        return $stats;
    }

    /**
     * 获取热门向量数据库
     *
     * @param int $limit
     * @param string|null $dateRange
     * @return array
     */
    public static function getPopularVectorDbs(int $limit = 10, ?string $dateRange = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    vector_db_type,
                    COUNT(*) as usage_count,
                    SUM(tokens_used) as total_tokens_used,
                    AVG(execution_time) as avg_execution_time
                FROM `{$prefix}vector_db_usage_logs`";

        $params = [];
        $where = [];

        if ($dateRange) {
            switch ($dateRange) {
                case 'today':
                    $where[] = "DATE(created_at) = CURDATE()";
                    break;
                case 'week':
                    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                case 'year':
                    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                    break;
            }
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " GROUP BY vector_db_type 
                  ORDER BY usage_count DESC 
                  LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $vectorDbs = [];
        foreach ($results as $result) {
            $vectorDbs[] = [
                'vector_db_type' => $result['vector_db_type'],
                'usage_count' => (int)$result['usage_count'],
                'total_tokens_used' => (int)$result['total_tokens_used'],
                'avg_execution_time' => round((float)$result['avg_execution_time'], 2)
            ];
        }

        return $vectorDbs;
    }

    /**
     * 获取热门嵌入式模型
     *
     * @param int $limit
     * @param string|null $dateRange
     * @return array
     */
    public static function getPopularEmbeddingModels(int $limit = 10, ?string $dateRange = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    embedding_model,
                    COUNT(*) as usage_count,
                    SUM(tokens_used) as total_tokens_used,
                    AVG(execution_time) as avg_execution_time
                FROM `{$prefix}vector_db_usage_logs`";

        $params = [];
        $where = [];

        if ($dateRange) {
            switch ($dateRange) {
                case 'today':
                    $where[] = "DATE(created_at) = CURDATE()";
                    break;
                case 'week':
                    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                case 'year':
                    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                    break;
            }
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " GROUP BY embedding_model 
                  ORDER BY usage_count DESC 
                  LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $models = [];
        foreach ($results as $result) {
            $models[] = [
                'embedding_model' => $result['embedding_model'],
                'usage_count' => (int)$result['usage_count'],
                'total_tokens_used' => (int)$result['total_tokens_used'],
                'avg_execution_time' => round((float)$result['avg_execution_time'], 2)
            ];
        }

        return $models;
    }

    /**
     * 获取活跃用户排行
     *
     * @param int $limit
     * @param string|null $dateRange
     * @return array
     */
    public static function getActiveUsers(int $limit = 10, ?string $dateRange = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    u.id,
                    u.username,
                    u.nickname,
                    COUNT(vdl.id) as usage_count,
                    SUM(vdl.tokens_used) as total_tokens_used
                FROM `{$prefix}users` u
                LEFT JOIN `{$prefix}vector_db_usage_logs` vdl ON u.id = vdl.user_id";

        $params = [];
        $where = [];

        if ($dateRange) {
            switch ($dateRange) {
                case 'today':
                    $where[] = "DATE(vdl.created_at) = CURDATE()";
                    break;
                case 'week':
                    $where[] = "vdl.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $where[] = "vdl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                case 'year':
                    $where[] = "vdl.created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                    break;
            }
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " GROUP BY u.id, u.username, u.nickname 
                  HAVING usage_count > 0 
                  ORDER BY usage_count DESC, total_tokens_used DESC 
                  LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $users = [];
        foreach ($results as $result) {
            $users[] = [
                'id' => (int)$result['id'],
                'username' => $result['username'],
                'nickname' => $result['nickname'],
                'usage_count' => (int)$result['usage_count'],
                'total_tokens_used' => (int)$result['total_tokens_used']
            ];
        }

        return $users;
    }

    /**
     * 获取操作类型选项
     */
    public static function getOperationTypeOptions(): array
    {
        return [
            'insert' => '插入',
            'update' => '更新',
            'delete' => '删除',
            'search' => '搜索'
        ];
    }

    /**
     * 获取向量数据库类型选项
     */
    public static function getVectorDbTypeOptions(): array
    {
        return [
            'Pinecone' => 'Pinecone',
            'Weaviate' => 'Weaviate',
            'Milvus' => 'Milvus',
            'Chroma' => 'Chroma'
        ];
    }

    /**
     * 获取内容类型选项
     */
    public static function getContentTypeOptions(): array
    {
        return [
            'novel_chapter' => '小说章节',
            'anime_scene' => '动漫场景',
            'music_segment' => '音乐片段',
            'character' => '角色',
            'setting' => '设定',
            'plot' => '情节'
        ];
    }

    /**
     * 获取状态选项
     */
    public static function getStatusOptions(): array
    {
        return [
            'success' => '成功',
            'failed' => '失败'
        ];
    }

    /**
     * 记录成功操作
     *
     * @param int $userId
     * @param string $operationType
     * @param string $vectorDbType
     * @param string $embeddingModel
     * @param array $data
     * @return int|false
     */
    public static function logSuccess(int $userId, string $operationType, string $vectorDbType, string $embeddingModel, array $data = [])
    {
        $logData = [
            'user_id' => $userId,
            'operation_type' => $operationType,
            'vector_db_type' => $vectorDbType,
            'embedding_model' => $embeddingModel,
            'status' => 'success'
        ];

        $logData = array_merge($logData, $data);

        return self::create($logData);
    }

    /**
     * 记录失败操作
     *
     * @param int $userId
     * @param string $operationType
     * @param string $vectorDbType
     * @param string $embeddingModel
     * @param string $errorMessage
     * @param array $data
     * @return int|false
     */
    public static function logFailure(int $userId, string $operationType, string $vectorDbType, string $embeddingModel, string $errorMessage, array $data = [])
    {
        $logData = [
            'user_id' => $userId,
            'operation_type' => $operationType,
            'vector_db_type' => $vectorDbType,
            'embedding_model' => $embeddingModel,
            'status' => 'failed',
            'error_message' => $errorMessage
        ];

        $logData = array_merge($logData, $data);

        return self::create($logData);
    }
}