<?php

namespace app\models;

use app\services\Database;
use PDO;

class FileHash
{
    public static function findByHash(string $fileHash): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}file_hashes` WHERE file_hash = :file_hash LIMIT 1");
        $stmt->execute([':file_hash' => $fileHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}file_hashes` WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $cols = ['file_hash', 'file_path', 'file_size', 'mime_type', 'upload_user_id', 'reference_count'];
        $data = array_intersect_key($data, array_flip($cols));
        $data['reference_count'] = $data['reference_count'] ?? 1;
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ":{$c}", $cols);
        $sql = "INSERT INTO `{$prefix}file_hashes` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        foreach ($data as $k => $v) $stmt->bindValue(":{$k}", $v);
        $stmt->execute();
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $allowed = ['file_path', 'file_size', 'mime_type', 'upload_user_id', 'reference_count'];
        $updates = [];
        $params = [':id' => $id];
        foreach ($allowed as $col) {
            if (!array_key_exists($col, $data)) continue;
            $updates[] = "`{$col}` = :{$col}";
            $params[":{$col}"] = $data[$col];
        }
        if (empty($updates)) return true;
        $sql = "UPDATE `{$prefix}file_hashes` SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function incrementReference(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("UPDATE `{$prefix}file_hashes` SET reference_count = reference_count + 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function decrementReference(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("UPDATE `{$prefix}file_hashes` SET reference_count = GREATEST(0, reference_count - 1) WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}file_hashes` WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function deleteByHash(string $fileHash): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}file_hashes` WHERE file_hash = :file_hash");
        return $stmt->execute([':file_hash' => $fileHash]);
    }

    public static function getUnreferencedFiles(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}file_hashes` WHERE reference_count = 0 ORDER BY created_at ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getFilesByUser(int $userId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}file_hashes` WHERE upload_user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getTotalSizeByUser(int $userId): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(file_size), 0) as total_size FROM `{$prefix}file_hashes` WHERE upload_user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total_size'] ?? 0);
    }

    public static function getDuplicateFiles(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("
            SELECT file_hash, COUNT(*) as count, SUM(file_size) as total_size 
            FROM `{$prefix}file_hashes` 
            GROUP BY file_hash 
            HAVING COUNT(*) > 1 
            ORDER BY count DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getDuplicatesByHash(string $fileHash): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}file_hashes` WHERE file_hash = :file_hash ORDER BY created_at DESC");
        $stmt->execute([':file_hash' => $fileHash]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function calculateFileHash(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \Exception("文件不存在: {$filePath}");
        }
        
        return hash_file('sha256', $filePath);
    }

    public static function getOrCreateFile(string $filePath, ?int $userId = null): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("文件不存在: {$filePath}");
        }
        
        $fileHash = self::calculateFileHash($filePath);
        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath);
        
        // 检查是否已存在相同哈希的文件
        $existingFile = self::findByHash($fileHash);
        
        if ($existingFile) {
            // 增加引用计数
            self::incrementReference($existingFile['id']);
            return $existingFile;
        } else {
            // 创建新记录
            $data = [
                'file_hash' => $fileHash,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'upload_user_id' => $userId,
                'reference_count' => 1
            ];
            
            $id = self::create($data);
            return self::findById($id);
        }
    }

    public static function cleanupUnreferencedFiles(): array
    {
        $unreferencedFiles = self::getUnreferencedFiles();
        $deletedCount = 0;
        $freedSpace = 0;
        $errors = [];
        
        foreach ($unreferencedFiles as $file) {
            try {
                // 删除物理文件
                if (file_exists($file['file_path'])) {
                    if (unlink($file['file_path'])) {
                        $freedSpace += $file['file_size'];
                        $deletedCount++;
                    } else {
                        $errors[] = "无法删除文件: {$file['file_path']}";
                    }
                }
                
                // 删除数据库记录
                self::delete($file['id']);
            } catch (\Exception $e) {
                $errors[] = "删除文件 {$file['file_path']} 时出错: " . $e->getMessage();
            }
        }
        
        return [
            'deleted_count' => $deletedCount,
            'freed_space' => $freedSpace,
            'errors' => $errors
        ];
    }
}