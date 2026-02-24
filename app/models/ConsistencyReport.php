<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 一致性检查报告模型
 */
class ConsistencyReport
{
    /**
     * 创建一致性检查报告
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}consistency_reports` 
                (user_id, project_id, project_type, content_id, content_type, report_data, status, error_message, execution_time, tokens_used) 
                VALUES (:user_id, :project_id, :project_type, :content_id, :content_type, :report_data, :status, :error_message, :execution_time, :tokens_used)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':project_id' => $data['project_id'],
            ':project_type' => $data['project_type'],
            ':content_id' => $data['content_id'] ?? null,
            ':content_type' => $data['content_type'] ?? null,
            ':report_data' => $data['report_data'] ?? null,
            ':status' => $data['status'] ?? 'pending',
            ':error_message' => $data['error_message'] ?? null,
            ':execution_time' => $data['execution_time'] ?? null,
            ':tokens_used' => $data['tokens_used'] ?? null
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取一致性检查报告
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT cr.*, u.username, u.nickname as user_nickname,
                       CASE 
                           WHEN cr.project_type = 'novel' THEN (SELECT title FROM `{$prefix}novels` WHERE id = cr.project_id)
                           WHEN cr.project_type = 'anime' THEN (SELECT title FROM `{$prefix}anime_projects` WHERE id = cr.project_id)
                           WHEN cr.project_type = 'music' THEN (SELECT title FROM `{$prefix}music_projects` WHERE id = cr.project_id)
                       END as project_title
                FROM `{$prefix}consistency_reports` cr
                LEFT JOIN `{$prefix}users` u ON cr.user_id = u.id
                WHERE cr.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 获取一致性检查报告列表
     *
     * @param int $page
     * @param int $perPage
     * @param int|null $userId
     * @param string|null $projectType
     * @param string|null $status
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public static function getAll(int $page = 1, int $perPage = 15, ?int $userId = null, ?string $projectType = null, ?string $status = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT cr.*, u.username, u.nickname as user_nickname,
                       CASE 
                           WHEN cr.project_type = 'novel' THEN (SELECT title FROM `{$prefix}novels` WHERE id = cr.project_id)
                           WHEN cr.project_type = 'anime' THEN (SELECT title FROM `{$prefix}anime_projects` WHERE id = cr.project_id)
                           WHEN cr.project_type = 'music' THEN (SELECT title FROM `{$prefix}music_projects` WHERE id = cr.project_id)
                       END as project_title
                FROM `{$prefix}consistency_reports` cr
                LEFT JOIN `{$prefix}users` u ON cr.user_id = u.id";

        $where = [];
        $params = [];

        if ($userId) {
            $where[] = "cr.user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        if ($projectType) {
            $where[] = "cr.project_type = :project_type";
            $params[':project_type'] = $projectType;
        }

        if ($status) {
            $where[] = "cr.status = :status";
            $params[':status'] = $status;
        }

        if ($dateFrom) {
            $where[] = "DATE(cr.created_at) >= :date_from";
            $params[':date_from'] = $dateFrom;
        }

        if ($dateTo) {
            $where[] = "DATE(cr.created_at) <= :date_to";
            $params[':date_to'] = $dateTo;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY cr.created_at DESC";

        // 获取总数
        $countSql = str_replace(
            "SELECT cr.*, u.username, u.nickname as user_nickname, CASE WHEN cr.project_type = 'novel' THEN (SELECT title FROM `{$prefix}novels` WHERE id = cr.project_id) WHEN cr.project_type = 'anime' THEN (SELECT title FROM `{$prefix}anime_projects` WHERE id = cr.project_id) WHEN cr.project_type = 'music' THEN (SELECT title FROM `{$prefix}music_projects` WHERE id = cr.project_id) END as project_title",
            "SELECT COUNT(DISTINCT cr.id)",
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
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'reports' => $reports,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 获取用户的一致性检查报告
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @param string|null $projectType
     * @param string|null $status
     * @return array
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 15, ?string $projectType = null, ?string $status = null): array
    {
        return self::getAll($page, $perPage, $userId, $projectType, $status);
    }

    /**
     * 获取项目的一致性检查报告
     *
     * @param int $projectId
     * @param string $projectType
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getByProject(int $projectId, string $projectType, int $page = 1, int $perPage = 15): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT cr.*, u.username, u.nickname as user_nickname
                FROM `{$prefix}consistency_reports` cr
                LEFT JOIN `{$prefix}users` u ON cr.user_id = u.id
                WHERE cr.project_id = :project_id AND cr.project_type = :project_type
                ORDER BY cr.created_at DESC";

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}consistency_reports` 
                     WHERE project_id = :project_id AND project_type = :project_type";

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([
            ':project_id' => $projectId,
            ':project_type' => $projectType
        ]);
        $totalRecords = $countStmt->fetchColumn();

        // 获取分页数据
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->bindValue(':project_type', $projectType);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'reports' => $reports,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 更新一致性检查报告
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = ['report_data', 'status', 'error_message', 'execution_time', 'tokens_used'];
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

        $sql = "UPDATE `{$prefix}consistency_reports` SET " . implode(', ', $updates) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 标记报告为完成
     *
     * @param int $id
     * @param array $reportData
     * @param int $executionTime
     * @param int $tokensUsed
     * @return bool
     */
    public static function markCompleted(int $id, array $reportData, int $executionTime, int $tokensUsed): bool
    {
        return self::update($id, [
            'report_data' => json_encode($reportData),
            'status' => 'completed',
            'execution_time' => $executionTime,
            'tokens_used' => $tokensUsed
        ]);
    }

    /**
     * 标记报告为失败
     *
     * @param int $id
     * @param string $errorMessage
     * @return bool
     */
    public static function markFailed(int $id, string $errorMessage): bool
    {
        return self::update($id, [
            'status' => 'failed',
            'error_message' => $errorMessage
        ]);
    }

    /**
     * 删除一致性检查报告
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

            // 删除相关的冲突记录
            $deleteConflicts = $pdo->prepare("DELETE FROM `{$prefix}consistency_conflicts` WHERE report_id = ?");
            $deleteConflicts->execute([$id]);

            // 删除报告
            $deleteReport = $pdo->prepare("DELETE FROM `{$prefix}consistency_reports` WHERE id = ?");
            $deleteReport->execute([$id]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 批量删除报告
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

        try {
            $pdo->beginTransaction();

            // 获取要删除的报告ID
            $sql = "SELECT id FROM `{$prefix}consistency_reports`";
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
            $stmt->execute($params);
            $reportIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($reportIds)) {
                // 删除相关的冲突记录
                $deleteConflicts = $pdo->prepare("DELETE FROM `{$prefix}consistency_conflicts` WHERE report_id IN (" . implode(',', $reportIds) . ")");
                $deleteConflicts->execute();

                // 删除报告
                $deleteReports = $pdo->prepare("DELETE FROM `{$prefix}consistency_reports` WHERE id IN (" . implode(',', $reportIds) . ")");
                $deleteReports->execute();
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
     * 获取报告统计
     *
     * @param int|null $userId
     * @param string|null $dateRange
     * @return array
     */
    public static function getReportStats(?int $userId = null, ?string $dateRange = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    project_type,
                    COUNT(*) as total_reports,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_reports,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_reports,
                    AVG(execution_time) as avg_execution_time,
                    SUM(tokens_used) as total_tokens_used
                FROM `{$prefix}consistency_reports`";

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

        $sql .= " GROUP BY project_type";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [];
        foreach ($results as $result) {
            $stats[$result['project_type']] = [
                'total_reports' => (int)$result['total_reports'],
                'completed_reports' => (int)$result['completed_reports'],
                'failed_reports' => (int)$result['failed_reports'],
                'avg_execution_time' => round((float)$result['avg_execution_time'], 2),
                'total_tokens_used' => (int)$result['total_tokens_used']
            ];
        }

        return $stats;
    }

    /**
     * 获取项目类型选项
     */
    public static function getProjectTypeOptions(): array
    {
        return [
            'novel' => '小说',
            'anime' => '动漫',
            'music' => '音乐'
        ];
    }

    /**
     * 获取状态选项
     */
    public static function getStatusOptions(): array
    {
        return [
            'pending' => '待处理',
            'completed' => '已完成',
            'failed' => '失败'
        ];
    }

    /**
     * 获取内容类型选项
     */
    public static function getContentTypeOptions(): array
    {
        return [
            'chapter' => '章节',
            'scene' => '场景',
            'music_segment' => '音乐片段'
        ];
    }
}