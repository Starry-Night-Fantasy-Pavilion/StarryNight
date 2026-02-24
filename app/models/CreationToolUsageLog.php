<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 创作工具使用日志模型
 */
class CreationToolUsageLog
{
    /**
     * 创建创作工具使用日志
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}creation_tool_usage_logs` 
                (tool_id, user_id, input_data, output_data, execution_time, tokens_used, coins_spent) 
                VALUES (:tool_id, :user_id, :input_data, :output_data, :execution_time, :tokens_used, :coins_spent)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':tool_id' => $data['tool_id'],
            ':user_id' => $data['user_id'],
            ':input_data' => $data['input_data'] ?? null,
            ':output_data' => $data['output_data'] ?? null,
            ':execution_time' => $data['execution_time'] ?? null,
            ':tokens_used' => $data['tokens_used'] ?? null,
            ':coins_spent' => $data['coins_spent'] ?? null
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取创作工具使用日志
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ctul.*, ct.name as tool_name, ct.code as tool_code,
                       u.username, u.nickname as user_nickname
                FROM `{$prefix}creation_tool_usage_logs` ctul
                LEFT JOIN `{$prefix}creation_tools` ct ON ctul.tool_id = ct.id
                LEFT JOIN `{$prefix}users` u ON ctul.user_id = u.id
                WHERE ctul.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 获取创作工具使用日志列表
     *
     * @param int $page
     * @param int $perPage
     * @param int|null $toolId
     * @param int|null $userId
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public static function getAll(int $page = 1, int $perPage = 15, ?int $toolId = null, ?int $userId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT ctul.*, ct.name as tool_name, ct.code as tool_code,
                       u.username, u.nickname as user_nickname
                FROM `{$prefix}creation_tool_usage_logs` ctul
                LEFT JOIN `{$prefix}creation_tools` ct ON ctul.tool_id = ct.id
                LEFT JOIN `{$prefix}users` u ON ctul.user_id = u.id";

        $where = [];
        $params = [];

        if ($toolId) {
            $where[] = "ctul.tool_id = :tool_id";
            $params[':tool_id'] = $toolId;
        }

        if ($userId) {
            $where[] = "ctul.user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        if ($dateFrom) {
            $where[] = "DATE(ctul.created_at) >= :date_from";
            $params[':date_from'] = $dateFrom;
        }

        if ($dateTo) {
            $where[] = "DATE(ctul.created_at) <= :date_to";
            $params[':date_to'] = $dateTo;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY ctul.created_at DESC";

        // 获取总数
        $countSql = str_replace(
            "SELECT ctul.*, ct.name as tool_name, ct.code as tool_code, u.username, u.nickname as user_nickname",
            "SELECT COUNT(DISTINCT ctul.id)",
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
     * @param int|null $toolId
     * @return array
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 15, ?int $toolId = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT ctul.*, ct.name as tool_name, ct.code as tool_code
                FROM `{$prefix}creation_tool_usage_logs` ctul
                LEFT JOIN `{$prefix}creation_tools` ct ON ctul.tool_id = ct.id
                WHERE ctul.user_id = :user_id";

        $params = [':user_id' => $userId];

        if ($toolId) {
            $sql .= " AND ctul.tool_id = :tool_id";
            $params[':tool_id'] = $toolId;
        }

        $sql .= " ORDER BY ctul.created_at DESC";

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}creation_tool_usage_logs` WHERE user_id = :user_id";
        $countParams = [':user_id' => $userId];

        if ($toolId) {
            $countSql .= " AND tool_id = :tool_id";
            $countParams[':tool_id'] = $toolId;
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
     * 获取工具的使用日志
     *
     * @param int $toolId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getByTool(int $toolId, int $page = 1, int $perPage = 15): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT ctul.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}creation_tool_usage_logs` ctul
                LEFT JOIN `{$prefix}users` u ON ctul.user_id = u.id
                WHERE ctul.tool_id = :tool_id
                ORDER BY ctul.created_at DESC";

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}creation_tool_usage_logs` WHERE tool_id = :tool_id";

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':tool_id' => $toolId]);
        $totalRecords = $countStmt->fetchColumn();

        // 获取分页数据
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':tool_id', $toolId, PDO::PARAM_INT);
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
     * 删除创作工具使用日志
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}creation_tool_usage_logs` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 批量删除创作工具使用日志
     *
     * @param int|null $toolId
     * @param int|null $userId
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return bool
     */
    public static function deleteBatch(?int $toolId = null, ?int $userId = null, ?string $dateFrom = null, ?string $dateTo = null): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}creation_tool_usage_logs`";
        $where = [];
        $params = [];

        if ($toolId) {
            $where[] = "tool_id = :tool_id";
            $params[':tool_id'] = $toolId;
        }

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
     * @param int|null $toolId
     * @param int|null $userId
     * @param string|null $dateRange
     * @return array
     */
    public static function getUsageStats(?int $toolId = null, ?int $userId = null, ?string $dateRange = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_usage,
                    AVG(execution_time) as avg_execution_time,
                    SUM(tokens_used) as total_tokens_used,
                    SUM(coins_spent) as total_coins_spent,
                    DATE(created_at) as date
                FROM `{$prefix}creation_tool_usage_logs`";

        $params = [];
        $where = [];

        if ($toolId) {
            $where[] = "tool_id = :tool_id";
            $params[':tool_id'] = $toolId;
        }

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

        $sql .= " GROUP BY DATE(created_at) ORDER BY date DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [];
        foreach ($results as $result) {
            $stats[$result['date']] = [
                'total_usage' => (int)$result['total_usage'],
                'avg_execution_time' => round((float)$result['avg_execution_time'], 2),
                'total_tokens_used' => (int)$result['total_tokens_used'],
                'total_coins_spent' => (int)$result['total_coins_spent']
            ];
        }

        return $stats;
    }

    /**
     * 获取热门工具使用排行
     *
     * @param int $limit
     * @param string|null $dateRange
     * @return array
     */
    public static function getPopularTools(int $limit = 10, ?string $dateRange = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    ct.id,
                    ct.name,
                    ct.code,
                    COUNT(ctul.id) as usage_count,
                    AVG(ctul.execution_time) as avg_execution_time,
                    SUM(ctul.tokens_used) as total_tokens_used,
                    SUM(ctul.coins_spent) as total_coins_spent
                FROM `{$prefix}creation_tools` ct
                LEFT JOIN `{$prefix}creation_tool_usage_logs` ctul ON ct.id = ctul.tool_id";

        $params = [];
        $where = [];

        if ($dateRange) {
            switch ($dateRange) {
                case 'today':
                    $where[] = "DATE(ctul.created_at) = CURDATE()";
                    break;
                case 'week':
                    $where[] = "ctul.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $where[] = "ctul.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                case 'year':
                    $where[] = "ctul.created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                    break;
            }
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " GROUP BY ct.id, ct.name, ct.code 
                  HAVING usage_count > 0 
                  ORDER BY usage_count DESC, total_coins_spent DESC 
                  LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tools = [];
        foreach ($results as $result) {
            $tools[] = [
                'id' => (int)$result['id'],
                'name' => $result['name'],
                'code' => $result['code'],
                'usage_count' => (int)$result['usage_count'],
                'avg_execution_time' => round((float)$result['avg_execution_time'], 2),
                'total_tokens_used' => (int)$result['total_tokens_used'],
                'total_coins_spent' => (int)$result['total_coins_spent']
            ];
        }

        return $tools;
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
                    COUNT(ctul.id) as usage_count,
                    SUM(ctul.coins_spent) as total_coins_spent
                FROM `{$prefix}users` u
                LEFT JOIN `{$prefix}creation_tool_usage_logs` ctul ON u.id = ctul.user_id";

        $params = [];
        $where = [];

        if ($dateRange) {
            switch ($dateRange) {
                case 'today':
                    $where[] = "DATE(ctul.created_at) = CURDATE()";
                    break;
                case 'week':
                    $where[] = "ctul.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $where[] = "ctul.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                case 'year':
                    $where[] = "ctul.created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                    break;
            }
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " GROUP BY u.id, u.username, u.nickname 
                  HAVING usage_count > 0 
                  ORDER BY usage_count DESC, total_coins_spent DESC 
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
                'total_coins_spent' => (int)$result['total_coins_spent']
            ];
        }

        return $users;
    }
}