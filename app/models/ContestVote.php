<?php

namespace app\models;

use app\services\Database;
use PDO;

class ContestVote
{
    /**
     * 创建投票记录
     */
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}contest_votes` (
            contest_id, submission_id, user_id, score
        ) VALUES (
            :contest_id, :submission_id, :user_id, :score
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':contest_id' => $data['contest_id'],
            ':submission_id' => $data['submission_id'],
            ':user_id' => $data['user_id'],
            ':score' => $data['score'] ?? null
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 检查用户是否已投票
     */
    public static function hasVoted(int $contestId, int $userId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT COUNT(*) FROM `{$prefix}contest_votes` 
                WHERE contest_id = :contest_id AND user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':contest_id' => $contestId,
            ':user_id' => $userId
        ]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * 获取用户投票记录
     */
    public static function getByUser(int $userId, int $contestId = null, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = ["v.user_id = :user_id"];
        $params = [':user_id' => $userId];
        
        if ($contestId) {
            $where[] = "v.contest_id = :contest_id";
            $params[':contest_id'] = $contestId;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}contest_votes` v WHERE {$whereClause}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT v.*, s.title as submission_title, s.thumbnail as submission_thumbnail,
                       c.title as contest_title
                FROM `{$prefix}contest_votes` v 
                LEFT JOIN `{$prefix}contest_submissions` s ON v.submission_id = s.id 
                LEFT JOIN `{$prefix}creation_contests` c ON v.contest_id = c.id 
                WHERE {$whereClause} 
                ORDER BY v.voted_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $votes,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取投稿的投票记录
     */
    public static function getBySubmission(int $submissionId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}contest_votes` WHERE submission_id = :submission_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':submission_id' => $submissionId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT v.*, u.username as user_name, u.avatar as user_avatar
                FROM `{$prefix}contest_votes` v 
                LEFT JOIN `{$prefix}users` u ON v.user_id = u.id 
                WHERE v.submission_id = :submission_id 
                ORDER BY v.voted_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':submission_id', $submissionId);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $votes,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取大赛的投票统计
     */
    public static function getContestStats(int $contestId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT 
                    COUNT(*) as total_votes,
                    COUNT(DISTINCT user_id) as unique_voters,
                    AVG(score) as avg_score,
                    MAX(score) as max_score,
                    MIN(score) as min_score,
                    COUNT(CASE WHEN score = 5 THEN 1 ELSE 0 END) as five_star_votes,
                    COUNT(CASE WHEN score = 4 THEN 1 ELSE 0 END) as four_star_votes,
                    COUNT(CASE WHEN score = 3 THEN 1 ELSE 0 END) as three_star_votes,
                    COUNT(CASE WHEN score = 2 THEN 1 ELSE 0 END) as two_star_votes,
                    COUNT(CASE WHEN score = 1 THEN 1 ELSE 0 END) as one_star_votes
                FROM `{$prefix}contest_votes` 
                WHERE contest_id = :contest_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':contest_id' => $contestId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取投稿的投票统计
     */
    public static function getSubmissionStats(int $submissionId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT 
                    COUNT(*) as total_votes,
                    COUNT(DISTINCT user_id) as unique_voters,
                    AVG(score) as avg_score,
                    MAX(score) as max_score,
                    MIN(score) as min_score,
                    COUNT(CASE WHEN score = 5 THEN 1 ELSE 0 END) as five_star_votes,
                    COUNT(CASE WHEN score = 4 THEN 1 ELSE 0 END) as four_star_votes,
                    COUNT(CASE WHEN score = 3 THEN 1 ELSE 0 END) as three_star_votes,
                    COUNT(CASE WHEN score = 2 THEN 1 ELSE 0 END) as two_star_votes,
                    COUNT(CASE WHEN score = 1 THEN 1 ELSE 0 END) as one_star_votes
                FROM `{$prefix}contest_votes` 
                WHERE submission_id = :submission_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':submission_id' => $submissionId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 更新投票
     */
    public static function update(int $contestId, int $userId, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $fields = [];
        $params = [
            ':contest_id' => $contestId,
            ':user_id' => $userId
        ];
        
        if (isset($data['score'])) {
            $fields[] = "score = :score";
            $params[':score'] = $data['score'];
        }
        
        if (isset($data['submission_id'])) {
            $fields[] = "submission_id = :submission_id";
            $params[':submission_id'] = $data['submission_id'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE `{$prefix}contest_votes` SET " . implode(', ', $fields) . 
                " WHERE contest_id = :contest_id AND user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * 删除投票
     */
    public static function delete(int $contestId, int $userId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}contest_votes` 
                WHERE contest_id = :contest_id AND user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':contest_id' => $contestId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * 获取投票排行榜
     */
    public static function getLeaderboard(int $contestId, int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT s.id, s.title, s.thumbnail, s.vote_count, s.judge_score,
                       u.username as user_name, u.avatar as user_avatar,
                       COUNT(v.id) as vote_count_detail,
                       AVG(v.score) as avg_score
                FROM `{$prefix}contest_submissions` s 
                LEFT JOIN `{$prefix}users` u ON s.user_id = u.id 
                LEFT JOIN `{$prefix}contest_votes` v ON s.id = v.submission_id 
                WHERE s.contest_id = :contest_id 
                AND s.status IN ('submitted', 'approved', 'winner')
                GROUP BY s.id 
                ORDER BY vote_count DESC, avg_score DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':contest_id', $contestId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取活跃投票者
     */
    public static function getActiveVoters(int $contestId = null, int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $whereClause = $contestId ? "WHERE v.contest_id = :contest_id" : "";
        $params = $contestId ? [':contest_id' => $contestId] : [];
        
        $sql = "SELECT u.id, u.username, u.avatar,
                       COUNT(v.id) as vote_count,
                       COUNT(DISTINCT v.contest_id) as contest_count
                FROM `{$prefix}contest_votes` v 
                LEFT JOIN `{$prefix}users` u ON v.user_id = u.id 
                {$whereClause}
                GROUP BY u.id 
                ORDER BY vote_count DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取投票时间分布
     */
    public static function getVoteTimeDistribution(int $contestId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT 
                    DATE(voted_at) as vote_date,
                    COUNT(*) as daily_votes,
                    COUNT(DISTINCT user_id) as daily_voters
                FROM `{$prefix}contest_votes` 
                WHERE contest_id = :contest_id 
                GROUP BY DATE(voted_at) 
                ORDER BY vote_date ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':contest_id' => $contestId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}