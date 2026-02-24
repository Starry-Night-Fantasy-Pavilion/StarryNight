<?php

namespace app\models;

use app\services\Database;
use PDO;

class CopyrightRegistration
{
    /**
     * 创建版权登记
     */
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}copyright_registrations` (
            user_id, content_type, content_id, title, description, content_hash, 
            blockchain_tx_hash, blockchain_address, registration_number, metadata, status
        ) VALUES (
            :user_id, :content_type, :content_id, :title, :description, :content_hash,
            :blockchain_tx_hash, :blockchain_address, :registration_number, :metadata, :status
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':content_type' => $data['content_type'],
            ':content_id' => $data['content_id'] ?? null,
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':content_hash' => $data['content_hash'],
            ':blockchain_tx_hash' => $data['blockchain_tx_hash'] ?? null,
            ':blockchain_address' => $data['blockchain_address'] ?? null,
            ':registration_number' => $data['registration_number'] ?? null,
            ':metadata' => json_encode($data['metadata'] ?? []),
            ':status' => $data['status'] ?? 'pending'
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 根据ID获取版权登记
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT cr.*, u.username as owner_name, u.avatar as owner_avatar
                FROM `{$prefix}copyright_registrations` cr 
                LEFT JOIN `{$prefix}users` u ON cr.user_id = u.id 
                WHERE cr.id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($registration) {
            $registration['metadata'] = json_decode($registration['metadata'], true);
        }
        
        return $registration ?: null;
    }
    
    /**
     * 根据内容哈希获取版权登记
     */
    public static function findByHash(string $contentHash): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT cr.*, u.username as owner_name, u.avatar as owner_avatar
                FROM `{$prefix}copyright_registrations` cr 
                LEFT JOIN `{$prefix}users` u ON cr.user_id = u.id 
                WHERE cr.content_hash = :content_hash";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':content_hash' => $contentHash]);
        
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($registration) {
            $registration['metadata'] = json_decode($registration['metadata'], true);
        }
        
        return $registration ?: null;
    }
    
    /**
     * 获取用户的版权登记列表
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = ["cr.user_id = :user_id"];
        $params = [':user_id' => $userId];
        
        if (!empty($filters['content_type'])) {
            $where[] = "cr.content_type = :content_type";
            $params[':content_type'] = $filters['content_type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "cr.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(cr.title LIKE :search OR cr.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}copyright_registrations` cr WHERE {$whereClause}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT cr.* 
                FROM `{$prefix}copyright_registrations` cr 
                WHERE {$whereClause} 
                ORDER BY cr.created_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($registrations as &$registration) {
            $registration['metadata'] = json_decode($registration['metadata'], true);
        }
        
        return [
            'data' => $registrations,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取版权登记列表
     */
    public static function getList(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = [];
        $params = [];
        
        if (!empty($filters['content_type'])) {
            $where[] = "cr.content_type = :content_type";
            $params[':content_type'] = $filters['content_type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "cr.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = "cr.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(cr.title LIKE :search OR cr.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = empty($where) ? "1" : implode(' AND ', $where);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}copyright_registrations` cr WHERE {$whereClause}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT cr.*, u.username as owner_name 
                FROM `{$prefix}copyright_registrations` cr 
                LEFT JOIN `{$prefix}users` u ON cr.user_id = u.id 
                WHERE {$whereClause} 
                ORDER BY cr.created_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($registrations as &$registration) {
            $registration['metadata'] = json_decode($registration['metadata'], true);
        }
        
        return [
            'data' => $registrations,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 更新版权登记
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $fields = [];
        $params = [':id' => $id];
        
        foreach (['title', 'description', 'blockchain_tx_hash', 'blockchain_address', 'registration_number', 'status', 'registered_at'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (isset($data['metadata'])) {
            $fields[] = "metadata = :metadata";
            $params[':metadata'] = json_encode($data['metadata']);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE `{$prefix}copyright_registrations` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * 删除版权登记
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}copyright_registrations` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 生成登记编号
     */
    public static function generateRegistrationNumber(): string
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $date = date('Ymd');
        $sequence = 1;
        
        // 获取当天的序列号
        $sql = "SELECT COUNT(*) as count FROM `{$prefix}copyright_registrations` 
                WHERE DATE(created_at) = CURDATE()";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $sequence = ($result['count'] ?? 0) + 1;
        
        return "CR{$date}" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * 检查内容哈希是否已存在
     */
    public static function hashExists(string $contentHash): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT COUNT(*) FROM `{$prefix}copyright_registrations` WHERE content_hash = :content_hash";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':content_hash' => $contentHash]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * 获取版权统计
     */
    public static function getStats(int $userId = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $whereClause = $userId ? "WHERE user_id = :user_id" : "";
        $params = $userId ? [':user_id' => $userId] : [];
        
        $sql = "SELECT 
                    COUNT(*) as total_registrations,
                    SUM(CASE WHEN status = 'registered' THEN 1 ELSE 0 END) as registered_count,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                    SUM(CASE WHEN content_type = 'novel' THEN 1 ELSE 0 END) as novel_count,
                    SUM(CASE WHEN content_type = 'anime' THEN 1 ELSE 0 END) as anime_count,
                    SUM(CASE WHEN content_type = 'music' THEN 1 ELSE 0 END) as music_count,
                    SUM(CASE WHEN content_type = 'image' THEN 1 ELSE 0 END) as image_count
                FROM `{$prefix}copyright_registrations` 
                {$whereClause}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}