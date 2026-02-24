<?php

namespace app\models;

use app\services\Database;
use PDO;
use app\models\VipBenefit;

class RechargePackage
{
    /**
     * 获取所有充值套餐
     *
     * @param bool $enabledOnly 是否只获取启用的套餐
     * @return array
     */
    public static function getAll(bool $enabledOnly = true): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}recharge_packages`";
        if ($enabledOnly) {
            $sql .= " WHERE is_enabled = 1";
        }
        $sql .= " ORDER BY sort_order ASC, price ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 根据ID获取充值套餐
     *
     * @param int $id
     * @return array|null
     */
    public static function getById(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}recharge_packages` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $package = $stmt->fetch(PDO::FETCH_ASSOC);

        return $package ?: null;
    }

    /**
     * 创建或更新充值套餐
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
                $sql = "UPDATE `{$prefix}recharge_packages` SET
                        name = :name,
                        tokens = :tokens,
                        price = :price,
                        vip_price = :vip_price,
                        discount_rate = :discount_rate,
                        bonus_tokens = :bonus_tokens,
                        is_hot = :is_hot,
                        sort_order = :sort_order,
                        is_enabled = :is_enabled,
                        description = :description,
                        icon = :icon,
                        badge = :badge
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                return $stmt->execute([
                    ':id' => $data['id'],
                    ':name' => $data['name'],
                    ':tokens' => $data['tokens'],
                    ':price' => $data['price'],
                    ':vip_price' => $data['vip_price'] ?? null,
                    ':discount_rate' => $data['discount_rate'] ?? null,
                    ':bonus_tokens' => $data['bonus_tokens'] ?? 0,
                    ':is_hot' => $data['is_hot'] ?? 0,
                    ':sort_order' => $data['sort_order'] ?? 0,
                    ':is_enabled' => $data['is_enabled'] ?? 1,
                    ':description' => $data['description'] ?? null,
                    ':icon' => $data['icon'] ?? null,
                    ':badge' => $data['badge'] ?? null
                ]);
            } else {
                // 创建
                $sql = "INSERT INTO `{$prefix}recharge_packages`
                        (name, tokens, price, vip_price, discount_rate, bonus_tokens, is_hot, sort_order, is_enabled, description, icon, badge)
                        VALUES
                        (:name, :tokens, :price, :vip_price, :discount_rate, :bonus_tokens, :is_hot, :sort_order, :is_enabled, :description, :icon, :badge)";
                $stmt = $pdo->prepare($sql);
                return $stmt->execute([
                    ':name' => $data['name'],
                    ':tokens' => $data['tokens'],
                    ':price' => $data['price'],
                    ':vip_price' => $data['vip_price'] ?? null,
                    ':discount_rate' => $data['discount_rate'] ?? null,
                    ':bonus_tokens' => $data['bonus_tokens'] ?? 0,
                    ':is_hot' => $data['is_hot'] ?? 0,
                    ':sort_order' => $data['sort_order'] ?? 0,
                    ':is_enabled' => $data['is_enabled'] ?? 1,
                    ':description' => $data['description'] ?? null,
                    ':icon' => $data['icon'] ?? null,
                    ':badge' => $data['badge'] ?? null
                ]);
            }
        } catch (\Exception $e) {
            error_log('Error saving recharge package: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 删除充值套餐
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}recharge_packages` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 获取用户购买套餐的实际价格和可得星夜币
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

        $userMembership = MembershipPackage::getUserMembership($userId);
        $finalPrice = $package['price'];
        $finalTokens = $package['tokens'] + $package['bonus_tokens'];
        $discountInfo = '无折扣';

        if ($userMembership && !$userMembership['is_expired']) {
            // 假设会员有折扣，这里需要从 vip_benefits 表中获取具体折扣率
            // 为简化，此处使用硬编码的示例折扣
            $vipBenefit = VipBenefit::getBenefitByKey('recharge_discount');
            
            if ($vipBenefit && $vipBenefit['is_enabled']) {
                $discountRate = (float) $vipBenefit['value']; // e.g., 0.9 for 90%
                if ($discountRate > 0 && $discountRate < 1) {
                    $finalPrice = round($package['price'] * $discountRate, 2);
                    $discountInfo = "会员专享 " . ($discountRate * 10) . " 折";
                }
            }
            
            // 假设会员有额外赠送
            $vipBonusBenefit = VipBenefit::getBenefitByKey('extra_bonus');
            if ($vipBonusBenefit && $vipBonusBenefit['is_enabled']) {
                $bonusRate = (float) $vipBonusBenefit['value']; // e.g., 0.1 for 10%
                if ($bonusRate > 0) {
                    $finalTokens += floor($package['tokens'] * $bonusRate);
                }
            }
        }

        return [
            'package_id' => $package['id'],
            'package_name' => $package['name'],
            'original_price' => (float) $package['price'],
            'actual_price' => (float) $finalPrice,
            'total_tokens' => (int) $finalTokens,
            'base_tokens' => (int) $package['tokens'],
            'bonus_tokens' => (int) ($finalTokens - $package['tokens']),
            'discount_info' => $discountInfo,
            'saved_amount' => (float) ($package['price'] - $finalPrice)
        ];
    }
}
