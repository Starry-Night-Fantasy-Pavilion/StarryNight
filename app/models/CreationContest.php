<?php

namespace app\models;

use app\services\Database;
use PDO;

class CreationContest
{
    /**
     * 创建创作大赛
     */
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}creation_contests` (
            title, description, type, category, prize_pool, prize_distribution,
            submission_start, submission_end, voting_start, voting_end, announcement_at,
            max_submissions, rules, judges, status, is_featured
        ) VALUES (
            :title, :description, :type, :category, :prize_pool, :prize_distribution,
            :submission_start, :submission_end, :voting_start, :voting_end, :announcement_at,
            :max_submissions, :rules, :judges, :status, :is_featured
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':type' => $data['type'],
            ':category' => $data['category'] ?? null,
            ':prize_pool' => $data['prize_pool'] ?? 0.00,
            ':prize_distribution' => json_encode($data['prize_distribution'] ?? []),
            ':submission_start' => $data['submission_start'],
            ':submission_end' => $data['submission_end'],
            ':voting_start' => $data['voting_start'] ?? null,
            ':voting_end' => $data['voting_end'] ?? null,
            ':announcement_at' => $data['announcement_at'] ?? null,
            ':max_submissions' => $data['max_submissions'] ?? null,
            ':rules' => $data['rules'] ?? null,
            ':judges' => json_encode($data['judges'] ?? []),
            ':status' => $data['status'] ?? 'draft',
            ':is_featured' => $data['is_featured'] ?? 0
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 根据ID获取大赛
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT * FROM `{$prefix}creation_contests` WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $contest = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($contest) {
            $contest['prize_distribution'] = json_decode($contest['prize_distribution'], true);
            $contest['judges'] = json_decode($contest['judges'], true);
        }
        
        return $contest ?: null;
    }
    
    /**
     * 获取大赛列表
     */
    public static function getList(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = [];
        $params = [];
        
        if (!empty($filters['type'])) {
            $where[] = "type = :type";
            $params[':type'] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['category'])) {
            $where[] = "category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['is_featured'])) {
            $where[] = "is_featured = :is_featured";
            $params[':is_featured'] = $filters['is_featured'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(title LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = empty($where) ? "1" : implode(' AND ', $where);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}creation_contests` WHERE {$whereClause}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM `{$prefix}creation_contests` 
                WHERE {$whereClause} 
                ORDER BY is_featured DESC, created_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $contests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($contests as &$contest) {
            $contest['prize_distribution'] = json_decode($contest['prize_distribution'], true);
            $contest['judges'] = json_decode($contest['judges'], true);
        }
        
        return [
            'data' => $contests,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取活跃大赛
     */
    public static function getActive(int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT * FROM `{$prefix}creation_contests` 
                WHERE status IN ('upcoming', 'active', 'voting') 
                ORDER BY is_featured DESC, submission_start DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $contests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($contests as &$contest) {
            $contest['prize_distribution'] = json_decode($contest['prize_distribution'], true);
            $contest['judges'] = json_decode($contest['judges'], true);
        }
        
        return $contests;
    }
    
    /**
     * 获取推荐大赛
     */
    public static function getFeatured(int $limit = 5): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT * FROM `{$prefix}creation_contests` 
                WHERE is_featured = 1 AND status IN ('upcoming', 'active') 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $contests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($contests as &$contest) {
            $contest['prize_distribution'] = json_decode($contest['prize_distribution'], true);
            $contest['judges'] = json_decode($contest['judges'], true);
        }
        
        return $contests;
    }
    
    /**
     * 更新大赛
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $fields = [];
        $params = [':id' => $id];
        
        foreach (['title', 'description', 'type', 'category', 'prize_pool', 'submission_start', 
                 'submission_end', 'voting_start', 'voting_end', 'announcement_at', 
                 'max_submissions', 'rules', 'status', 'is_featured'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (isset($data['prize_distribution'])) {
            $fields[] = "prize_distribution = :prize_distribution";
            $params[':prize_distribution'] = json_encode($data['prize_distribution']);
        }
        
        if (isset($data['judges'])) {
            $fields[] = "judges = :judges";
            $params[':judges'] = json_encode($data['judges']);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE `{$prefix}creation_contests` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * 删除大赛
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}creation_contests` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 检查大赛状态
     */
    public static function checkAndUpdateStatus(): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $updatedCount = 0;
        
        // 检查即将开始的大赛
        $sql = "UPDATE `{$prefix}creation_contests` 
                SET status = 'active' 
                WHERE status = 'upcoming' 
                AND submission_start <= NOW()";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $updatedCount += $stmt->rowCount();
        
        // 检查投稿结束的大赛
        $sql = "UPDATE `{$prefix}creation_contests` 
                SET status = CASE 
                    WHEN voting_start IS NOT NULL AND voting_start <= NOW() THEN 'voting'
                    ELSE 'judging'
                END
                WHERE status = 'active' 
                AND submission_end < NOW()";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $updatedCount += $stmt->rowCount();
        
        // 检查投票结束的大赛
        $sql = "UPDATE `{$prefix}creation_contests` 
                SET status = 'judging' 
                WHERE status = 'voting' 
                AND voting_end < NOW()";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $updatedCount += $stmt->rowCount();
        
        return $updatedCount;
    }
    
    /**
     * 获取大赛统计
     */
    public static function getStats(int $contestId = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $whereClause = $contestId ? "WHERE id = :contest_id" : "";
        $params = $contestId ? [':contest_id' => $contestId] : [];
        
        $sql = "SELECT 
                    COUNT(*) as total_contests,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
                    SUM(CASE WHEN status = 'upcoming' THEN 1 ELSE 0 END) as upcoming_count,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                    SUM(CASE WHEN status = 'voting' THEN 1 ELSE 0 END) as voting_count,
                    SUM(CASE WHEN status = 'judging' THEN 1 ELSE 0 END) as judging_count,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                    SUM(prize_pool) as total_prize_pool,
                    SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured_count
                FROM `{$prefix}creation_contests` 
                {$whereClause}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取大赛类型统计
     */
    public static function getTypeStats(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT 
                    type,
                    COUNT(*) as count,
                    SUM(prize_pool) as total_prize_pool,
                    AVG(prize_pool) as avg_prize_pool
                FROM `{$prefix}creation_contests` 
                WHERE status != 'draft'
                GROUP BY type 
                ORDER BY count DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}