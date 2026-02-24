<?php

namespace app\models;

use app\services\Database;
use PDO;

class ContestSubmission
{
    /**
     * 创建大赛投稿
     */
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}contest_submissions` (
            contest_id, user_id, title, description, content_type, content_data, 
            file_urls, thumbnail, status
        ) VALUES (
            :contest_id, :user_id, :title, :description, :content_type, :content_data,
            :file_urls, :thumbnail, :status
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':contest_id' => $data['contest_id'],
            ':user_id' => $data['user_id'],
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':content_type' => $data['content_type'],
            ':content_data' => $data['content_data'] ?? null,
            ':file_urls' => json_encode($data['file_urls'] ?? []),
            ':thumbnail' => $data['thumbnail'] ?? null,
            ':status' => $data['status'] ?? 'submitted'
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 根据ID获取投稿
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT s.*, u.username as user_name, u.avatar as user_avatar,
                       c.title as contest_title, c.type as contest_type
                FROM `{$prefix}contest_submissions` s 
                LEFT JOIN `{$prefix}users` u ON s.user_id = u.id 
                LEFT JOIN `{$prefix}creation_contests` c ON s.contest_id = c.id 
                WHERE s.id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($submission) {
            $submission['file_urls'] = json_decode($submission['file_urls'], true);
        }
        
        return $submission ?: null;
    }
    
    /**
     * 获取大赛投稿列表
     */
    public static function getByContest(int $contestId, int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = ["s.contest_id = :contest_id"];
        $params = [':contest_id' => $contestId];
        
        if (!empty($filters['content_type'])) {
            $where[] = "s.content_type = :content_type";
            $params[':content_type'] = $filters['content_type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "s.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = "s.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(s.title LIKE :search OR s.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}contest_submissions` s WHERE {$whereClause}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT s.*, u.username as user_name, u.avatar as user_avatar
                FROM `{$prefix}contest_submissions` s 
                LEFT JOIN `{$prefix}users` u ON s.user_id = u.id 
                WHERE {$whereClause} 
                ORDER BY s.vote_count DESC, s.judge_score DESC, s.submitted_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($submissions as &$submission) {
            $submission['file_urls'] = json_decode($submission['file_urls'], true);
        }
        
        return [
            'data' => $submissions,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取用户的投稿列表
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}contest_submissions` WHERE user_id = :user_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':user_id' => $userId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT s.*, c.title as contest_title, c.type as contest_type, c.status as contest_status
                FROM `{$prefix}contest_submissions` s 
                LEFT JOIN `{$prefix}creation_contests` c ON s.contest_id = c.id 
                WHERE s.user_id = :user_id 
                ORDER BY s.submitted_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($submissions as &$submission) {
            $submission['file_urls'] = json_decode($submission['file_urls'], true);
        }
        
        return [
            'data' => $submissions,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 更新投稿
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $fields = [];
        $params = [':id' => $id];
        
        foreach (['title', 'description', 'content_data', 'thumbnail', 'status', 'vote_count', 
                 'judge_score', 'final_rank', 'prize_amount', 'reviewed_at'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (isset($data['file_urls'])) {
            $fields[] = "file_urls = :file_urls";
            $params[':file_urls'] = json_encode($data['file_urls']);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE `{$prefix}contest_submissions` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * 删除投稿
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}contest_submissions` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 增加投票数
     */
    public static function incrementVote(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}contest_submissions` 
                SET vote_count = vote_count + 1 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 获取获奖投稿
     */
    public static function getWinners(int $contestId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT s.*, u.username as user_name, u.avatar as user_avatar
                FROM `{$prefix}contest_submissions` s 
                LEFT JOIN `{$prefix}users` u ON s.user_id = u.id 
                WHERE s.contest_id = :contest_id 
                AND s.status = 'winner' 
                AND s.final_rank IS NOT NULL 
                ORDER BY s.final_rank ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':contest_id' => $contestId]);
        
        $winners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($winners as &$winner) {
            $winner['file_urls'] = json_decode($winner['file_urls'], true);
        }
        
        return $winners;
    }
    
    /**
     * 获取热门投稿
     */
    public static function getPopular(int $limit = 10, string $contentType = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = [];
        $params = [];
        
        if ($contentType) {
            $where[] = "s.content_type = :content_type";
            $params[':content_type'] = $contentType;
        }
        
        $whereClause = empty($where) ? "1" : implode(' AND ', $where);
        
        $sql = "SELECT s.*, u.username as user_name, u.avatar as user_avatar,
                       c.title as contest_title
                FROM `{$prefix}contest_submissions` s 
                LEFT JOIN `{$prefix}users` u ON s.user_id = u.id 
                LEFT JOIN `{$prefix}creation_contests` c ON s.contest_id = c.id 
                WHERE {$whereClause} 
                AND s.status IN ('submitted', 'approved', 'winner')
                ORDER BY s.vote_count DESC, s.judge_score DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($submissions as &$submission) {
            $submission['file_urls'] = json_decode($submission['file_urls'], true);
        }
        
        return $submissions;
    }
    
    /**
     * 获取投稿统计
     */
    public static function getStats(int $contestId = null, int $userId = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = [];
        $params = [];
        
        if ($contestId) {
            $where[] = "contest_id = :contest_id";
            $params[':contest_id'] = $contestId;
        }
        
        if ($userId) {
            $where[] = "user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        $whereClause = empty($where) ? "1" : implode(' AND ', $where);
        
        $sql = "SELECT 
                    COUNT(*) as total_submissions,
                    SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted_count,
                    SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review_count,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                    SUM(CASE WHEN status = 'winner' THEN 1 ELSE 0 END) as winner_count,
                    SUM(vote_count) as total_votes,
                    AVG(vote_count) as avg_votes,
                    AVG(judge_score) as avg_judge_score,
                    MAX(judge_score) as max_judge_score,
                    SUM(prize_amount) as total_prizes
                FROM `{$prefix}contest_submissions` 
                WHERE {$whereClause}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取内容类型统计
     */
    public static function getContentTypeStats(int $contestId = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $whereClause = $contestId ? "WHERE contest_id = :contest_id" : "";
        $params = $contestId ? [':contest_id' => $contestId] : [];
        
        $sql = "SELECT 
                    content_type,
                    COUNT(*) as count,
                    AVG(vote_count) as avg_votes,
                    AVG(judge_score) as avg_judge_score,
                    SUM(CASE WHEN status = 'winner' THEN 1 ELSE 0 END) as winner_count
                FROM `{$prefix}contest_submissions` 
                {$whereClause}
                GROUP BY content_type 
                ORDER BY count DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}