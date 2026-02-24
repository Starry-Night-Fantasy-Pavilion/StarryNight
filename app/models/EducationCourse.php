<?php

namespace app\models;

use app\services\Database;
use PDO;

class EducationCourse
{
    /**
     * 创建教育课程
     */
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}education_courses` (
            title, description, instructor_id, category, type, level, price_type, price,
            duration, thumbnail, content, video_url, materials, status, is_featured
        ) VALUES (
            :title, :description, :instructor_id, :category, :type, :level, :price_type, :price,
            :duration, :thumbnail, :content, :video_url, :materials, :status, :is_featured
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':instructor_id' => $data['instructor_id'],
            ':category' => $data['category'] ?? null,
            ':type' => $data['type'],
            ':level' => $data['level'] ?? 'beginner',
            ':price_type' => $data['price_type'] ?? 'free',
            ':price' => $data['price'] ?? 0.00,
            ':duration' => $data['duration'] ?? null,
            ':thumbnail' => $data['thumbnail'] ?? null,
            ':content' => $data['content'] ?? null,
            ':video_url' => $data['video_url'] ?? null,
            ':materials' => json_encode($data['materials'] ?? []),
            ':status' => $data['status'] ?? 'draft',
            ':is_featured' => $data['is_featured'] ?? 0
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 根据ID获取课程
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT c.*, u.username as instructor_name, u.avatar as instructor_avatar,
                       u.bio as instructor_bio
                FROM `{$prefix}education_courses` c 
                LEFT JOIN `{$prefix}users` u ON c.instructor_id = u.id 
                WHERE c.id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($course) {
            $course['materials'] = json_decode($course['materials'], true);
        }
        
        return $course ?: null;
    }
    
    /**
     * 获取课程列表
     */
    public static function getList(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = [];
        $params = [];
        
        if (!empty($filters['instructor_id'])) {
            $where[] = "c.instructor_id = :instructor_id";
            $params[':instructor_id'] = $filters['instructor_id'];
        }
        
        if (!empty($filters['category'])) {
            $where[] = "c.category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['type'])) {
            $where[] = "c.type = :type";
            $params[':type'] = $filters['type'];
        }
        
        if (!empty($filters['level'])) {
            $where[] = "c.level = :level";
            $params[':level'] = $filters['level'];
        }
        
        if (!empty($filters['price_type'])) {
            $where[] = "c.price_type = :price_type";
            $params[':price_type'] = $filters['price_type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "c.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['is_featured'])) {
            $where[] = "c.is_featured = :is_featured";
            $params[':is_featured'] = $filters['is_featured'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(c.title LIKE :search OR c.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = empty($where) ? "1" : implode(' AND ', $where);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}education_courses` c WHERE {$whereClause}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT c.*, u.username as instructor_name 
                FROM `{$prefix}education_courses` c 
                LEFT JOIN `{$prefix}users` u ON c.instructor_id = u.id 
                WHERE {$whereClause} 
                ORDER BY c.is_featured DESC, c.rating DESC, c.created_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($courses as &$course) {
            $course['materials'] = json_decode($course['materials'], true);
        }
        
        return [
            'data' => $courses,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取讲师的课程列表
     */
    public static function getByInstructor(int $instructorId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}education_courses` WHERE instructor_id = :instructor_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':instructor_id' => $instructorId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT * FROM `{$prefix}education_courses` 
                WHERE instructor_id = :instructor_id 
                ORDER BY created_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':instructor_id', $instructorId);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($courses as &$course) {
            $course['materials'] = json_decode($course['materials'], true);
        }
        
        return [
            'data' => $courses,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取推荐课程
     */
    public static function getFeatured(int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT c.*, u.username as instructor_name 
                FROM `{$prefix}education_courses` c 
                LEFT JOIN `{$prefix}users` u ON c.instructor_id = u.id 
                WHERE c.is_featured = 1 AND c.status = 'published' 
                ORDER BY c.rating DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($courses as &$course) {
            $course['materials'] = json_decode($course['materials'], true);
        }
        
        return $courses;
    }
    
    /**
     * 获取热门课程
     */
    public static function getPopular(int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT c.*, u.username as instructor_name 
                FROM `{$prefix}education_courses` c 
                LEFT JOIN `{$prefix}users` u ON c.instructor_id = u.id 
                WHERE c.status = 'published' 
                ORDER BY c.view_count DESC, c.rating DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($courses as &$course) {
            $course['materials'] = json_decode($course['materials'], true);
        }
        
        return $courses;
    }
    
    /**
     * 更新课程
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $fields = [];
        $params = [':id' => $id];
        
        foreach (['title', 'description', 'category', 'type', 'level', 'price_type', 'price', 
                 'duration', 'thumbnail', 'content', 'video_url', 'status', 'is_featured'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (isset($data['materials'])) {
            $fields[] = "materials = :materials";
            $params[':materials'] = json_encode($data['materials']);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE `{$prefix}education_courses` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * 删除课程
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}education_courses` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 增加查看次数
     */
    public static function incrementView(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}education_courses` SET view_count = view_count + 1 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 更新评分
     */
    public static function updateRating(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}education_courses` c 
                SET c.rating = (
                    SELECT COALESCE(AVG(rating), 0) 
                    FROM `{$prefix}course_reviews` r 
                    WHERE r.course_id = c.id
                ),
                c.rating_count = (
                    SELECT COUNT(*) 
                    FROM `{$prefix}course_reviews` r 
                    WHERE r.course_id = c.id
                )
                WHERE c.id = :id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 获取课程统计
     */
    public static function getStats(int $instructorId = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $whereClause = $instructorId ? "WHERE instructor_id = :instructor_id" : "";
        $params = $instructorId ? [':instructor_id' => $instructorId] : [];
        
        $sql = "SELECT 
                    COUNT(*) as total_courses,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_count,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
                    SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_count,
                    SUM(CASE WHEN type = 'tutorial' THEN 1 ELSE 0 END) as tutorial_count,
                    SUM(CASE WHEN type = 'masterclass' THEN 1 ELSE 0 END) as masterclass_count,
                    SUM(CASE WHEN type = 'workshop' THEN 1 ELSE 0 END) as workshop_count,
                    SUM(view_count) as total_views,
                    AVG(rating) as avg_rating,
                    SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured_count
                FROM `{$prefix}education_courses` 
                {$whereClause}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取分类统计
     */
    public static function getCategoryStats(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT 
                    category,
                    COUNT(*) as course_count,
                    AVG(rating) as avg_rating,
                    SUM(view_count) as total_views
                FROM `{$prefix}education_courses` 
                WHERE status = 'published' AND category IS NOT NULL 
                GROUP BY category 
                ORDER BY course_count DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}