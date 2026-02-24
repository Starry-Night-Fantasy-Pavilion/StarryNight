<?php

namespace app\models;

use app\services\Database;
use PDO;

class KnowledgeRating
{
    /**
     * 创建评分
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            // 检查是否已经评分
            $checkExisting = $pdo->prepare("SELECT id FROM `{$prefix}knowledge_ratings` 
                    WHERE user_id = :user_id AND knowledge_base_id = :knowledge_base_id");
            $checkExisting->execute([
                ':user_id' => $data['user_id'],
                ':knowledge_base_id' => $data['knowledge_base_id']
            ]);

            if ($checkExisting->fetch()) {
                $pdo->rollBack();
                return false; // 已经评分
            }

            // 创建评分记录
            $sql = "INSERT INTO `{$prefix}knowledge_ratings` 
                    (knowledge_base_id, user_id, rating, review) 
                    VALUES (:knowledge_base_id, :user_id, :rating, :review)";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                ':knowledge_base_id' => $data['knowledge_base_id'],
                ':user_id' => $data['user_id'],
                ':rating' => $data['rating'],
                ':review' => $data['review'] ?? null
            ]);

            if (!$result) {
                $pdo->rollBack();
                return false;
            }

            $ratingId = $pdo->lastInsertId();

            // 更新知识库的平均评分
            self::updateKnowledgeBaseRating($data['knowledge_base_id']);

            $pdo->commit();
            return $ratingId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 更新评分
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            $fields = ['rating', 'review', 'is_helpful'];
            $updates = [];
            $params = [':id' => $id];

            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "`$field` = :$field";
                    $params[":$field"] = $data[$field];
                }
            }

            if (empty($updates)) {
                $pdo->rollBack();
                return false;
            }

            $sql = "UPDATE `{$prefix}knowledge_ratings` SET " . implode(', ', $updates) . " WHERE `id` = :id";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);

            if (!$result) {
                $pdo->rollBack();
                return false;
            }

            // 获取知识库ID并更新平均评分
            $getKBId = $pdo->prepare("SELECT knowledge_base_id FROM `{$prefix}knowledge_ratings` WHERE id = ?");
            $getKBId->execute([$id]);
            $kbId = $getKBId->fetchColumn();

            if ($kbId) {
                self::updateKnowledgeBaseRating($kbId);
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
     * 删除评分
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

            // 获取知识库ID
            $getKBId = $pdo->prepare("SELECT knowledge_base_id FROM `{$prefix}knowledge_ratings` WHERE id = ?");
            $getKBId->execute([$id]);
            $kbId = $getKBId->fetchColumn();

            // 删除评分
            $deleteRating = $pdo->prepare("DELETE FROM `{$prefix}knowledge_ratings` WHERE id = ?");
            $result = $deleteRating->execute([$id]);

            if (!$result) {
                $pdo->rollBack();
                return false;
            }

            // 更新知识库的平均评分
            if ($kbId) {
                self::updateKnowledgeBaseRating($kbId);
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
     * 获取知识库的评分列表
     *
     * @param int $knowledgeBaseId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getByKnowledgeBase(int $knowledgeBaseId, int $page = 1, int $perPage = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT kr.*, u.username, u.nickname, u.avatar
                FROM `{$prefix}knowledge_ratings` kr
                LEFT JOIN `{$prefix}users` u ON kr.user_id = u.id
                WHERE kr.knowledge_base_id = :knowledge_base_id
                ORDER BY kr.created_at DESC";

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}knowledge_ratings` WHERE knowledge_base_id = :knowledge_base_id";
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
        $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'ratings' => $ratings,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 获取用户对知识库的评分
     *
     * @param int $userId
     * @param int $knowledgeBaseId
     * @return array|null
     */
    public static function getUserRating(int $userId, int $knowledgeBaseId): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}knowledge_ratings` 
                WHERE user_id = :user_id AND knowledge_base_id = :knowledge_base_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':knowledge_base_id' => $knowledgeBaseId
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 更新知识库的平均评分
     *
     * @param int $knowledgeBaseId
     * @return bool
     */
    private static function updateKnowledgeBaseRating(int $knowledgeBaseId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}knowledge_bases` kb
                SET rating = (
                    SELECT COALESCE(AVG(rating), 0) 
                    FROM `{$prefix}knowledge_ratings` 
                    WHERE knowledge_base_id = :knowledge_base_id
                ),
                rating_count = (
                    SELECT COUNT(*) 
                    FROM `{$prefix}knowledge_ratings` 
                    WHERE knowledge_base_id = :knowledge_base_id
                )
                WHERE kb.id = :knowledge_base_id";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':knowledge_base_id' => $knowledgeBaseId]);
    }

    /**
     * 获取评分统计
     *
     * @param int $knowledgeBaseId
     * @return array
     */
    public static function getStats(int $knowledgeBaseId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_ratings,
                    AVG(rating) as avg_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM `{$prefix}knowledge_ratings` 
                WHERE knowledge_base_id = :knowledge_base_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':knowledge_base_id' => $knowledgeBaseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}