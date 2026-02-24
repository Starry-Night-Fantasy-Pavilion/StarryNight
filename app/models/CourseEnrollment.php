<?php

namespace app\models;

use app\services\Database;
use PDO;

class CourseEnrollment
{
    /**
     * 创建课程学习记录
     */
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}course_enrollments` (
            course_id, user_id, progress, completed_at, certificate_url
        ) VALUES (
            :course_id, :user_id, :progress, :completed_at, :certificate_url
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':course_id' => $data['course_id'],
            ':user_id' => $data['user_id'],
            ':progress' => $data['progress'] ?? 0.00,
            ':completed_at' => $data['completed_at'] ?? null,
            ':certificate_url' => $data['certificate_url'] ?? null
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 检查用户是否已报名课程
     */
    public static function isEnrolled(int $courseId, int $userId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT COUNT(*) FROM `{$prefix}course_enrollments` 
                WHERE course_id = :course_id AND user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':course_id' => $courseId,
            ':user_id' => $userId
        ]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * 获取学习记录
     */
    public static function find(int $courseId, int $userId): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT e.*, c.title as course_title, c.thumbnail as course_thumbnail,
                       u.username as user_name, u.avatar as user_avatar
                FROM `{$prefix}course_enrollments` e 
                LEFT JOIN `{$prefix}education_courses` c ON e.course_id = c.id 
                LEFT JOIN `{$prefix}users` u ON e.user_id = u.id 
                WHERE e.course_id = :course_id AND e.user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':course_id' => $courseId,
            ':user_id' => $userId
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * 获取用户的课程学习记录
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = ["e.user_id = :user_id"];
        $params = [':user_id' => $userId];
        
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'in_progress':
                    $where[] = "e.progress > 0 AND e.progress < 100";
                    break;
                case 'completed':
                    $where[] = "e.progress >= 100";
                    break;
                case 'not_started':
                    $where[] = "e.progress = 0";
                    break;
            }
        }
        
        if (!empty($filters['course_type'])) {
            $where[] = "c.type = :course_type";
            $params[':course_type'] = $filters['course_type'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(c.title LIKE :search OR c.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}course_enrollments` e 
                    LEFT JOIN `{$prefix}education_courses` c ON e.course_id = c.id 
                    WHERE {$whereClause}";
        
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT e.*, c.title as course_title, c.thumbnail as course_thumbnail,
                       c.type as course_type, c.level as course_level
                FROM `{$prefix}course_enrollments` e 
                LEFT JOIN `{$prefix}education_courses` c ON e.course_id = c.id 
                WHERE {$whereClause} 
                ORDER BY e.last_accessed_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $enrollments,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取课程的学习记录
     */
    public static function getByCourse(int $courseId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}course_enrollments` WHERE course_id = :course_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':course_id' => $courseId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT e.*, u.username as user_name, u.avatar as user_avatar
                FROM `{$prefix}course_enrollments` e 
                LEFT JOIN `{$prefix}users` u ON e.user_id = u.id 
                WHERE e.course_id = :course_id 
                ORDER BY e.progress DESC, e.last_accessed_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':course_id', $courseId);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $enrollments,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 更新学习进度
     */
    public static function updateProgress(int $courseId, int $userId, float $progress): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}course_enrollments` 
                SET progress = :progress, last_accessed_at = CURRENT_TIMESTAMP 
                WHERE course_id = :course_id AND user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':progress' => $progress,
            ':course_id' => $courseId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * 完成课程
     */
    public static function complete(int $courseId, int $userId, string $certificateUrl = null): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}course_enrollments` 
                SET progress = 100, completed_at = CURRENT_TIMESTAMP, 
                    certificate_url = :certificate_url 
                WHERE course_id = :course_id AND user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':certificate_url' => $certificateUrl,
            ':course_id' => $courseId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * 删除学习记录
     */
    public static function delete(int $courseId, int $userId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}course_enrollments` 
                WHERE course_id = :course_id AND user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':course_id' => $courseId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * 获取学习统计
     */
    public static function getStats(int $courseId = null, int $userId = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = [];
        $params = [];
        
        if ($courseId) {
            $where[] = "course_id = :course_id";
            $params[':course_id'] = $courseId;
        }
        
        if ($userId) {
            $where[] = "user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        $whereClause = empty($where) ? "1" : implode(' AND ', $where);
        
        $sql = "SELECT 
                    COUNT(*) as total_enrollments,
                    SUM(CASE WHEN progress >= 100 THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN progress > 0 AND progress < 100 THEN 1 ELSE 0 END) as in_progress_count,
                    SUM(CASE WHEN progress = 0 THEN 1 ELSE 0 END) as not_started_count,
                    AVG(progress) as avg_progress,
                    MAX(progress) as max_progress,
                    MIN(progress) as min_progress,
                    SUM(CASE WHEN certificate_url IS NOT NULL THEN 1 ELSE 0 END) as certificate_count
                FROM `{$prefix}course_enrollments` 
                WHERE {$whereClause}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取完成率最高的课程
     */
    public static function getTopCompletionRate(int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT c.id, c.title, c.thumbnail,
                       COUNT(e.id) as enrollment_count,
                       SUM(CASE WHEN e.progress >= 100 THEN 1 ELSE 0 END) as completed_count,
                       ROUND(SUM(CASE WHEN e.progress >= 100 THEN 1 ELSE 0 END) * 100.0 / COUNT(e.id), 2) as completion_rate
                FROM `{$prefix}education_courses` c 
                LEFT JOIN `{$prefix}course_enrollments` e ON c.id = e.course_id 
                WHERE c.status = 'published' 
                GROUP BY c.id 
                HAVING enrollment_count >= 5 
                ORDER BY completion_rate DESC, enrollment_count DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取活跃学习者
     */
    public static function getActiveLearners(int $courseId = null, int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $whereClause = $courseId ? "WHERE e.course_id = :course_id" : "";
        $params = $courseId ? [':course_id' => $courseId] : [];
        
        $sql = "SELECT u.id, u.username, u.avatar,
                       COUNT(e.id) as course_count,
                       SUM(e.progress) as total_progress,
                       AVG(e.progress) as avg_progress,
                       MAX(e.last_accessed_at) as last_access
                FROM `{$prefix}course_enrollments` e 
                LEFT JOIN `{$prefix}users` u ON e.user_id = u.id 
                {$whereClause}
                GROUP BY u.id 
                ORDER BY avg_progress DESC, total_progress DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}