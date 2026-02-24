<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 一致性检查冲突记录模型
 */
class ConsistencyConflict
{
    /**
     * 创建一致性检查冲突记录
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}consistency_conflicts` 
                (report_id, user_id, project_id, project_type, content_id, content_type, conflict_type, severity, description, conflicting_content, reference_setting, suggestion, similarity_score, is_resolved) 
                VALUES (:report_id, :user_id, :project_id, :project_type, :content_id, :content_type, :conflict_type, :severity, :description, :conflicting_content, :reference_setting, :suggestion, :similarity_score, :is_resolved)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':report_id' => $data['report_id'],
            ':user_id' => $data['user_id'],
            ':project_id' => $data['project_id'],
            ':project_type' => $data['project_type'],
            ':content_id' => $data['content_id'] ?? null,
            ':content_type' => $data['content_type'] ?? null,
            ':conflict_type' => $data['conflict_type'],
            ':severity' => $data['severity'] ?? 'medium',
            ':description' => $data['description'],
            ':conflicting_content' => $data['conflicting_content'] ?? null,
            ':reference_setting' => $data['reference_setting'] ?? null,
            ':suggestion' => $data['suggestion'] ?? null,
            ':similarity_score' => $data['similarity_score'] ?? null,
            ':is_resolved' => $data['is_resolved'] ?? 0
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取一致性检查冲突记录
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT cc.*, u.username, u.nickname as user_nickname,
                       CASE 
                           WHEN cc.project_type = 'novel' THEN (SELECT title FROM `{$prefix}novels` WHERE id = cc.project_id)
                           WHEN cc.project_type = 'anime' THEN (SELECT title FROM `{$prefix}anime_projects` WHERE id = cc.project_id)
                           WHEN cc.project_type = 'music' THEN (SELECT title FROM `{$prefix}music_projects` WHERE id = cc.project_id)
                       END as project_title
                FROM `{$prefix}consistency_conflicts` cc
                LEFT JOIN `{$prefix}users` u ON cc.user_id = u.id
                WHERE cc.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 获取一致性检查冲突记录列表
     *
     * @param int $page
     * @param int $perPage
     * @param int|null $reportId
     * @param int|null $userId
     * @param string|null $projectType
     * @param string|null $conflictType
     * @param string|null $severity
     * @param bool|null $isResolved
     * @return array
     */
    public static function getAll(int $page = 1, int $perPage = 15, ?int $reportId = null, ?int $userId = null, ?string $projectType = null, ?string $conflictType = null, ?string $severity = null, ?bool $isResolved = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT cc.*, u.username, u.nickname as user_nickname,
                       CASE 
                           WHEN cc.project_type = 'novel' THEN (SELECT title FROM `{$prefix}novels` WHERE id = cc.project_id)
                           WHEN cc.project_type = 'anime' THEN (SELECT title FROM `{$prefix}anime_projects` WHERE id = cc.project_id)
                           WHEN cc.project_type = 'music' THEN (SELECT title FROM `{$prefix}music_projects` WHERE id = cc.project_id)
                       END as project_title
                FROM `{$prefix}consistency_conflicts` cc
                LEFT JOIN `{$prefix}users` u ON cc.user_id = u.id";

        $where = [];
        $params = [];

        if ($reportId) {
            $where[] = "cc.report_id = :report_id";
            $params[':report_id'] = $reportId;
        }

        if ($userId) {
            $where[] = "cc.user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        if ($projectType) {
            $where[] = "cc.project_type = :project_type";
            $params[':project_type'] = $projectType;
        }

        if ($conflictType) {
            $where[] = "cc.conflict_type = :conflict_type";
            $params[':conflict_type'] = $conflictType;
        }

        if ($severity) {
            $where[] = "cc.severity = :severity";
            $params[':severity'] = $severity;
        }

        if ($isResolved !== null) {
            $where[] = "cc.is_resolved = :is_resolved";
            $params[':is_resolved'] = $isResolved ? 1 : 0;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY cc.created_at DESC";

        // 获取总数
        $countSql = str_replace(
            "SELECT cc.*, u.username, u.nickname as user_nickname, CASE WHEN cc.project_type = 'novel' THEN (SELECT title FROM `{$prefix}novels` WHERE id = cc.project_id) WHEN cc.project_type = 'anime' THEN (SELECT title FROM `{$prefix}anime_projects` WHERE id = cc.project_id) WHEN cc.project_type = 'music' THEN (SELECT title FROM `{$prefix}music_projects` WHERE id = cc.project_id) END as project_title",
            "SELECT COUNT(DISTINCT cc.id)",
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
        $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'conflicts' => $conflicts,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 根据报告ID获取冲突记录
     *
     * @param int $reportId
     * @return array
     */
    public static function getByReport(int $reportId): array
    {
        return self::getAll(1, 1000, $reportId)['conflicts'];
    }

    /**
     * 获取用户的冲突记录
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @param bool|null $isResolved
     * @return array
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 15, ?bool $isResolved = null): array
    {
        return self::getAll($page, $perPage, null, $userId, null, null, null, $isResolved);
    }

    /**
     * 获取项目的冲突记录
     *
     * @param int $projectId
     * @param string $projectType
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getByProject(int $projectId, string $projectType, int $page = 1, int $perPage = 15): array
    {
        return self::getAll($page, $perPage, null, null, $projectType);
    }

    /**
     * 更新一致性检查冲突记录
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = ['description', 'conflicting_content', 'reference_setting', 'suggestion', 'similarity_score', 'is_resolved'];
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

        $sql = "UPDATE `{$prefix}consistency_conflicts` SET " . implode(', ', $updates) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 标记冲突为已解决
     *
     * @param int $id
     * @return bool
     */
    public static function markResolved(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}consistency_conflicts` SET is_resolved = 1, resolved_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 标记冲突为未解决
     *
     * @param int $id
     * @return bool
     */
    public static function markUnresolved(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}consistency_conflicts` SET is_resolved = 0, resolved_at = NULL WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 删除一致性检查冲突记录
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}consistency_conflicts` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 批量删除冲突记录
     *
     * @param int|null $reportId
     * @param int|null $userId
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return bool
     */
    public static function deleteBatch(?int $reportId = null, ?int $userId = null, ?string $dateFrom = null, ?string $dateTo = null): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}consistency_conflicts`";
        $where = [];
        $params = [];

        if ($reportId) {
            $where[] = "report_id = :report_id";
            $params[':report_id'] = $reportId;
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
     * 获取冲突统计
     *
     * @param int|null $userId
     * @param string|null $projectType
     * @param string|null $dateRange
     * @return array
     */
    public static function getConflictStats(?int $userId = null, ?string $projectType = null, ?string $dateRange = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    project_type,
                    conflict_type,
                    severity,
                    COUNT(*) as total_conflicts,
                    SUM(CASE WHEN is_resolved = 1 THEN 1 ELSE 0 END) as resolved_conflicts,
                    AVG(similarity_score) as avg_similarity_score
                FROM `{$prefix}consistency_conflicts`";

        $params = [];
        $where = [];

        if ($userId) {
            $where[] = "user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        if ($projectType) {
            $where[] = "project_type = :project_type";
            $params[':project_type'] = $projectType;
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

        $sql .= " GROUP BY project_type, conflict_type, severity";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [];
        foreach ($results as $result) {
            $projectType = $result['project_type'];
            if (!isset($stats[$projectType])) {
                $stats[$projectType] = [];
            }
            
            $conflictType = $result['conflict_type'];
            if (!isset($stats[$projectType][$conflictType])) {
                $stats[$projectType][$conflictType] = [];
            }
            
            $stats[$projectType][$conflictType][$result['severity']] = [
                'total_conflicts' => (int)$result['total_conflicts'],
                'resolved_conflicts' => (int)$result['resolved_conflicts'],
                'unresolved_conflicts' => (int)($result['total_conflicts'] - $result['resolved_conflicts']),
                'avg_similarity_score' => round((float)$result['avg_similarity_score'], 4)
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
     * 获取冲突类型选项
     */
    public static function getConflictTypeOptions(): array
    {
        return [
            'logic' => '逻辑冲突',
            'character' => '角色设定冲突',
            'setting' => '世界观设定冲突',
            'timeline' => '时间线冲突',
            'plot' => '情节冲突',
            'style' => '风格冲突',
            'continuity' => '连续性冲突'
        ];
    }

    /**
     * 获取严重程度选项
     */
    public static function getSeverityOptions(): array
    {
        return [
            'low' => '低',
            'medium' => '中',
            'high' => '高',
            'critical' => '严重'
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
            'music_segment' => '音乐片段',
            'character' => '角色',
            'setting' => '设定',
            'plot' => '情节'
        ];
    }

    /**
     * 根据项目类型获取冲突类型选项
     *
     * @param string $projectType
     * @return array
     */
    public static function getConflictTypeOptionsByProject(string $projectType): array
    {
        $allOptions = self::getConflictTypeOptions();
        
        switch ($projectType) {
            case 'novel':
                return array_intersect_key($allOptions, array_flip([
                    'logic', 'character', 'setting', 'timeline', 'plot', 'continuity'
                ]));
            case 'anime':
                return array_intersect_key($allOptions, array_flip([
                    'logic', 'character', 'setting', 'scene', 'continuity'
                ]));
            case 'music':
                return array_intersect_key($allOptions, array_flip([
                    'style', 'continuity'
                ]));
            default:
                return $allOptions;
        }
    }

    /**
     * 验证冲突数据
     *
     * @param array $data
     * @return array
     */
    public static function validateConflict(array $data): array
    {
        $errors = [];

        // 验证项目类型
        if (isset($data['project_type']) && !in_array($data['project_type'], array_keys(self::getProjectTypeOptions()))) {
            $errors[] = '无效的项目类型';
        }

        // 验证冲突类型
        if (isset($data['conflict_type']) && !in_array($data['conflict_type'], array_keys(self::getConflictTypeOptions()))) {
            $errors[] = '无效的冲突类型';
        }

        // 验证严重程度
        if (isset($data['severity']) && !in_array($data['severity'], array_keys(self::getSeverityOptions()))) {
            $errors[] = '无效的严重程度';
        }

        // 验证描述
        if (empty($data['description'])) {
            $errors[] = '冲突描述不能为空';
        }

        // 验证相似度分数
        if (isset($data['similarity_score']) && ($data['similarity_score'] < 0 || $data['similarity_score'] > 1)) {
            $errors[] = '相似度分数必须在0-1之间';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 获取未解决的冲突数量
     *
     * @param int|null $userId
     * @param string|null $projectType
     * @return int
     */
    public static function getUnresolvedCount(?int $userId = null, ?string $projectType = null): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT COUNT(*) FROM `{$prefix}consistency_conflicts` WHERE is_resolved = 0";
        $params = [];

        if ($userId) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        if ($projectType) {
            $sql .= " AND project_type = :project_type";
            $params[':project_type'] = $projectType;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * 获取高严重程度未解决的冲突
     *
     * @param int|null $userId
     * @param int $limit
     * @return array
     */
    public static function getCriticalUnresolved(?int $userId = null, int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT cc.*, 
                       CASE 
                           WHEN cc.project_type = 'novel' THEN (SELECT title FROM `{$prefix}novels` WHERE id = cc.project_id)
                           WHEN cc.project_type = 'anime' THEN (SELECT title FROM `{$prefix}anime_projects` WHERE id = cc.project_id)
                           WHEN cc.project_type = 'music' THEN (SELECT title FROM `{$prefix}music_projects` WHERE id = cc.project_id)
                       END as project_title
                FROM `{$prefix}consistency_conflicts` cc
                WHERE cc.is_resolved = 0 AND cc.severity IN ('high', 'critical')";

        $params = [];

        if ($userId) {
            $sql .= " AND cc.user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        $sql .= " ORDER BY cc.severity DESC, cc.created_at DESC LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}