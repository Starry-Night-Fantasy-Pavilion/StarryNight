<?php

namespace app\models;

use app\services\Database;
use PDO;
use app\models\MembershipPurchaseRecord;

class MembershipPackage
{
    /**
     * 获取所有会员套餐
     *
     * @param bool $enabledOnly 是否只获取启用的套餐
     * @return array
     */
    public static function getAll(bool $enabledOnly = true): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}membership_packages`";
        $params = [];
        $conditions = [];

        if ($enabledOnly) {
            $conditions[] = "is_enabled = 1";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY sort_order ASC, original_price ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 根据ID获取会员套餐
     *
     * @param int $id
     * @return array|null
     */
    public static function getById(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}membership_packages` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $package = $stmt->fetch(PDO::FETCH_ASSOC);

        return $package ?: null;
    }

    /**
     * 根据类型获取会员套餐
     *
     * @param int $type
     * @return array
     */
    public static function getByType(int $type): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}membership_packages` WHERE type = :type AND is_enabled = 1 ORDER BY sort_order ASC, original_price ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':type' => $type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取推荐套餐
     *
     * @return array
     */
    public static function getRecommended(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}membership_packages` WHERE is_recommended = 1 AND is_enabled = 1 ORDER BY sort_order ASC, original_price ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取用户实际支付价格
     *
     * @param int $userId
     * @param int $packageId
     * @return array|null
     */
    public static function getActualPrice(int $userId, int $packageId): ?array
    {
        $package = self::getById($packageId);
        
        if (!$package || !$package['is_enabled']) {
            return null;
        }

        $user = User::find($userId);
        if (!$user) {
            return null;
        }

        // 如果有优惠价，使用优惠价
        if ($package['discount_price']) {
            return [
                'price' => $package['discount_price'],
                'original_price' => $package['original_price'],
                'discount' => '限时优惠',
                'saved' => $package['original_price'] - $package['discount_price']
            ];
        }
        
        // 使用折扣率计算
        if ($package['discount_rate']) {
            $discountPrice = $package['original_price'] * $package['discount_rate'];
            return [
                'price' => round($discountPrice, 2),
                'original_price' => $package['original_price'],
                'discount' => (1 - $package['discount_rate']) * 10 . '折',
                'saved' => $package['original_price'] - $discountPrice
            ];
        }

        // 原价
        return [
            'price' => $package['original_price'],
            'original_price' => $package['original_price'],
            'discount' => null,
            'saved' => 0
        ];
    }

    /**
     * 创建或更新会员套餐
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
                $sql = "UPDATE `{$prefix}membership_packages` SET 
                        name = :name,
                        type = :type,
                        membership_level_id = :membership_level_id,
                        duration_days = :duration_days,
                        original_price = :original_price,
                        discount_price = :discount_price,
                        discount_rate = :discount_rate,
                        features = :features,
                        description = :description,
                        is_recommended = :is_recommended,
                        is_enabled = :is_enabled,
                        sort_order = :sort_order,
                        icon = :icon,
                        badge = :badge
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                return $stmt->execute([
                    ':name' => $data['name'],
                    ':type' => $data['type'],
                    ':membership_level_id' => $data['membership_level_id'],
                    ':duration_days' => $data['duration_days'] ?? null,
                    ':original_price' => $data['original_price'],
                    ':discount_price' => $data['discount_price'] ?? null,
                    ':discount_rate' => $data['discount_rate'] ?? null,
                    ':features' => isset($data['features']) ? json_encode($data['features']) : null,
                    ':description' => $data['description'] ?? null,
                    ':is_recommended' => $data['is_recommended'] ?? 0,
                    ':is_enabled' => $data['is_enabled'] ?? 1,
                    ':sort_order' => $data['sort_order'] ?? 0,
                    ':icon' => $data['icon'] ?? null,
                    ':badge' => $data['badge'] ?? null,
                    ':id' => $data['id']
                ]);
            } else {
                // 创建
                $sql = "INSERT INTO `{$prefix}membership_packages` 
                        (name, type, membership_level_id, duration_days, original_price, discount_price, discount_rate, 
                         features, description, is_recommended, is_enabled, sort_order, icon, badge) 
                        VALUES 
                        (:name, :type, :membership_level_id, :duration_days, :original_price, :discount_price, :discount_rate, 
                         :features, :description, :is_recommended, :is_enabled, :sort_order, :icon, :badge)";
                $stmt = $pdo->prepare($sql);
                return $stmt->execute([
                    ':name' => $data['name'],
                    ':type' => $data['type'],
                    ':membership_level_id' => $data['membership_level_id'],
                    ':duration_days' => $data['duration_days'] ?? null,
                    ':original_price' => $data['original_price'],
                    ':discount_price' => $data['discount_price'] ?? null,
                    ':discount_rate' => $data['discount_rate'] ?? null,
                    ':features' => isset($data['features']) ? json_encode($data['features']) : null,
                    ':description' => $data['description'] ?? null,
                    ':is_recommended' => $data['is_recommended'] ?? 0,
                    ':is_enabled' => $data['is_enabled'] ?? 1,
                    ':sort_order' => $data['sort_order'] ?? 0,
                    ':icon' => $data['icon'] ?? null,
                    ':badge' => $data['badge'] ?? null
                ]);
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 删除会员套餐
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}membership_packages` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 切换套餐启用状态
     *
     * @param int $id
     * @return bool
     */
    public static function toggleStatus(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}membership_packages` SET is_enabled = CASE WHEN is_enabled = 1 THEN 0 ELSE 1 END WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 切换推荐状态
     *
     * @param int $id
     * @return bool
     */
    public static function toggleRecommended(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}membership_packages` SET is_recommended = CASE WHEN is_recommended = 1 THEN 0 ELSE 1 END WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 获取会员类型描述
     *
     * @param int $type
     * @return string
     */
    public static function getTypeDescription(int $type): string
    {
        $descriptions = [
            1 => '月度会员',
            2 => '年度会员',
            3 => '终身会员'
        ];

        return $descriptions[$type] ?? '未知类型';
    }

    /**
     * 获取套餐功能特性
     *
     * @param int $packageId
     * @return array
     */
    public static function getPackageFeatures(int $packageId): array
    {
        $package = self::getById($packageId);
        if (!$package || !$package['features']) {
            return [];
        }

        $features = json_decode($package['features'], true);
        return is_array($features) ? $features : [];
    }

    /**
     * 获取用户当前会员信息
     *
     * @param int $userId
     * @return array|null
     */
    public static function getUserMembership(int $userId): ?array
    {
        $user = User::find($userId);
        if (!$user || $user['vip_type'] == 0) {
            return null;
        }

        return [
            'type' => $user['vip_type'],
            'type_name' => self::getTypeDescription($user['vip_type']),
            'start_time' => $user['vip_start_at'],
            'end_time' => $user['vip_expire_at'],
            'is_lifetime' => $user['vip_type'] == 3,
            'is_expired' => $user['vip_type'] != 3 && ($user['vip_expire_at'] === null || strtotime($user['vip_expire_at']) < time()),
            'days_remaining' => $user['vip_type'] != 3 && $user['vip_expire_at'] ? max(0, ceil((strtotime($user['vip_expire_at']) - time()) / 86400)) : null,
            'auto_renew' => $user['auto_renew'],
            'membership_source' => $user['membership_source']
        ];
    }

    /**
     * 激活用户会员
     *
     * @param int $userId
     * @param int $packageId
     * @param string $source
     * @param string|null $transactionId
     * @param float|null $amount
     * @return bool
     */
    public static function activateMembership(int $userId, int $packageId, string $source = 'purchase', ?string $transactionId = null, ?float $amount = null): bool
    {
        $package = self::getById($packageId);
        if (!$package) {
            return false;
        }

        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            // 计算会员时间
            $startTime = date('Y-m-d H:i:s');
            $endTime = null;
            $originalVipExpireAt = $user['vip_expire_at'];

            if ($package['type'] != 3) { // 非终身会员
                $endTime = date('Y-m-d H:i:s', strtotime("+{$package['duration_days']} days"));
            }

            // 更新用户会员信息
            $sql = "UPDATE `{$prefix}users` SET
                    vip_type = :vip_type,
                    vip_start_at = :vip_start_at,
                    vip_expire_at = :vip_expire_at,
                    membership_source = :membership_source,
                    membership_level_id = :membership_level_id
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':vip_type' => $package['type'],
                ':vip_start_at' => $startTime,
                ':vip_expire_at' => $endTime,
                ':membership_source' => $source,
                ':membership_level_id' => $package['membership_level_id'],
                ':id' => $userId
            ]);

            // 创建购买记录
            $actualPriceInfo = self::getActualPrice($userId, $packageId);
            $actualPrice = $amount ?? $actualPriceInfo['price'];
            $originalPrice = $package['original_price'];
            $discountAmount = $originalPrice - $actualPrice;

            MembershipPurchaseRecord::create([
                'user_id' => $userId,
                'package_id' => $packageId,
                'membership_level_id' => $package['membership_level_id'],
                'membership_type' => $package['type'],
                'membership_name' => $package['name'],
                'original_price' => $originalPrice,
                'actual_price' => $actualPrice,
                'discount_amount' => $discountAmount,
                'duration_days' => $package['duration_days'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'payment_method' => $source,
                'payment_status' => 'completed',
                'transaction_id' => $transactionId,
                'original_vip_expire_at' => $originalVipExpireAt
            ]);

            // 更新用户限制配置
            if (class_exists(UserLimit::class)) {
                $defaultLimits = UserLimit::getVipDefaultLimits();
                UserLimit::save($userId, $defaultLimits);
            }

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log('Activate membership error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 续费会员
     *
     * @param int $userId
     * @param int $packageId
     * @param string|null $transactionId
     * @param float|null $amount
     * @return bool
     */
    public static function renewMembership(int $userId, int $packageId, ?string $transactionId = null, ?float $amount = null): bool
    {
        $package = self::getById($packageId);
        if (!$package) {
            return false;
        }

        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            // 计算新的结束时间
            $originalVipExpireAt = $user['vip_expire_at'];
            $startTime = date('Y-m-d H:i:s');
            $baseTime = $originalVipExpireAt && strtotime($originalVipExpireAt) > time()
                ? strtotime($originalVipExpireAt)
                : time();
            
            $newEndTime = null;
            if ($package['type'] != 3) { // 非终身会员
                $newEndTime = date('Y-m-d H:i:s', $baseTime + ($package['duration_days'] * 86400));
            }

            // 更新用户会员信息
            $sql = "UPDATE `{$prefix}users` SET
                    vip_type = :vip_type,
                    vip_expire_at = :vip_expire_at,
                    vip_start_at = CASE WHEN vip_start_at IS NULL THEN :start_at ELSE vip_start_at END
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':vip_type' => $package['type'],
                ':vip_expire_at' => $newEndTime,
                ':start_at' => $startTime,
                ':id' => $userId
            ]);

            // 创建购买记录
            $actualPriceInfo = self::getActualPrice($userId, $packageId);
            $actualPrice = $amount ?? $actualPriceInfo['price'];
            $originalPrice = $package['original_price'];
            $discountAmount = $originalPrice - $actualPrice;

            MembershipPurchaseRecord::create([
                'user_id' => $userId,
                'package_id' => $packageId,
                'membership_level_id' => $package['membership_level_id'],
                'membership_type' => $package['type'],
                'membership_name' => $package['name'],
                'original_price' => $originalPrice,
                'actual_price' => $actualPrice,
                'discount_amount' => $discountAmount,
                'duration_days' => $package['duration_days'],
                'start_time' => $startTime,
                'end_time' => $newEndTime,
                'payment_method' => 'renewal',
                'payment_status' => 'completed',
                'transaction_id' => $transactionId,
                'original_vip_expire_at' => $originalVipExpireAt
            ]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log('Renew membership error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 取消会员
     *
     * @param int $userId
     * @return bool
     */
    public static function cancelMembership(int $userId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            // 更新用户会员信息
            $sql = "UPDATE `{$prefix}users` SET
                    vip_type = 0,
                    vip_start_at = NULL,
                    vip_expire_at = NULL,
                    auto_renew = 0,
                    membership_source = 'cancelled',
                    membership_level_id = NULL
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $userId]);

            // 更新用户限制配置为普通用户
            // 注意：需要确保 UserLimit 模型存在且有 getFreeDefaultLimits 和 update 方法
            if (class_exists(UserLimit::class)) {
                $defaultLimits = UserLimit::getFreeDefaultLimits();
                UserLimit::save($userId, $defaultLimits);
            }

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log('Cancel membership error: ' . $e->getMessage());
            return false;
        }
    }
}
