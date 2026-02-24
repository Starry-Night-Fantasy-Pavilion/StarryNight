<?php

namespace app\models;

use app\services\Database;
use PDO;

class Feature
{
    /**
     * 获取所有功能权限
     *
     * @param bool $enabledOnly 是否只获取启用的功能
     * @param string|null $category 功能分类
     * @return array
     */
    public static function getAll(bool $enabledOnly = true, ?string $category = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}features`";
        $params = [];
        $conditions = [];

        if ($enabledOnly) {
            $conditions[] = "is_enabled = 1";
        }

        if ($category) {
            $conditions[] = "category = :category";
            $params[':category'] = $category;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY sort_order ASC, id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 根据功能键获取功能信息
     *
     * @param string $featureKey
     * @return array|null
     */
    public static function getByKey(string $featureKey): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}features` WHERE feature_key = :feature_key AND is_enabled = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':feature_key' => $featureKey]);
        $feature = $stmt->fetch(PDO::FETCH_ASSOC);

        return $feature ?: null;
    }

    /**
     * 检查用户是否有权限使用某功能
     *
     * @param int $userId
     * @param string $featureKey
     * @return array
     */
    public static function checkUserAccess(int $userId, string $featureKey): array
    {
        $feature = self::getByKey($featureKey);
        
        if (!$feature) {
            return [
                'status' => false,
                'message' => '功能不存在或已禁用',
                'require_vip' => false
            ];
        }

        // 不需要会员权限
        if ($feature['require_vip'] == 0) {
            return ['status' => true];
        }

        // 需要会员权限，检查用户会员状态
        $user = User::find($userId);
        if (!$user) {
            return [
                'status' => false,
                'message' => '用户不存在',
                'require_vip' => true
            ];
        }

        if (!self::isVip($user)) {
            return [
                'status' => false,
                'message' => '此功能需要会员权限，请升级会员后使用',
                'require_vip' => true
            ];
        }

        return ['status' => true];
    }

    /**
     * 判断用户是否为会员
     *
     * @param array $user
     * @return bool
     */
    public static function isVip(array $user): bool
    {
        if ($user['vip_type'] == 3) {
            // 终身会员永久有效
            return true;
        }

        if (in_array($user['vip_type'], [1, 2])) {
            // 月度/年度会员检查过期时间
            return $user['vip_expire_at'] && strtotime($user['vip_expire_at']) > time();
        }

        return false; // 普通用户
    }

    /**
     * 获取用户可使用的功能列表
     *
     * @param int $userId
     * @return array
     */
    public static function getUserAvailableFeatures(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            return [];
        }

        $allFeatures = self::getAll(true);
        $availableFeatures = [];
        $isVip = self::isVip($user);

        foreach ($allFeatures as $feature) {
            if ($feature['require_vip'] == 0 || $isVip) {
                $availableFeatures[] = $feature;
            }
        }

        return $availableFeatures;
    }

    /**
     * 根据ID获取功能信息
     *
     * @param int $id
     * @return array|null
     */
    public static function getById(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}features` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $feature = $stmt->fetch(PDO::FETCH_ASSOC);

        return $feature ?: null;
    }

    /**
     * 创建或更新功能权限
     *
     * @param array $data
     * @return bool
     */
    public static function save(array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            if (isset($data['id']) && $data['id']) {
                // 更新
                $sql = "UPDATE `{$prefix}features` SET 
                        feature_name = :feature_name,
                        category = :category,
                        description = :description,
                        require_vip = :require_vip,
                        is_enabled = :is_enabled,
                        sort_order = :sort_order
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                return $stmt->execute([
                    ':feature_name' => $data['feature_name'],
                    ':category' => $data['category'] ?? null,
                    ':description' => $data['description'] ?? null,
                    ':require_vip' => $data['require_vip'] ?? 0,
                    ':is_enabled' => $data['is_enabled'] ?? 1,
                    ':sort_order' => $data['sort_order'] ?? 0,
                    ':id' => $data['id']
                ]);
            } else {
                // 创建
                $sql = "INSERT INTO `{$prefix}features` 
                        (feature_key, feature_name, category, description, require_vip, is_enabled, sort_order) 
                        VALUES 
                        (:feature_key, :feature_name, :category, :description, :require_vip, :is_enabled, :sort_order)";
                $stmt = $pdo->prepare($sql);
                return $stmt->execute([
                    ':feature_key' => $data['feature_key'],
                    ':feature_name' => $data['feature_name'],
                    ':category' => $data['category'] ?? null,
                    ':description' => $data['description'] ?? null,
                    ':require_vip' => $data['require_vip'] ?? 0,
                    ':is_enabled' => $data['is_enabled'] ?? 1,
                    ':sort_order' => $data['sort_order'] ?? 0
                ]);
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 删除功能权限
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}features` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 获取功能分类列表
     *
     * @return array
     */
    public static function getCategories(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT DISTINCT category FROM `{$prefix}features` WHERE category IS NOT NULL AND category != '' ORDER BY category";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}