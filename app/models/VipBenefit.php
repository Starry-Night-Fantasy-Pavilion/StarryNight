<?php

namespace app\models;

use app\services\Database;
use PDO;

class VipBenefit
{
    /**
     * 根据权益key获取权益详情
     *
     * @param string $key
     * @return array|null
     */
    public static function getBenefitByKey(string $key): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}vip_benefits` WHERE `benefit_key` = :key";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':key' => $key]);
        $benefit = $stmt->fetch(PDO::FETCH_ASSOC);

        return $benefit ?: null;
    }

    /**
     * 获取用户的所有权益
     *
     * @param int $userId
     * @return array
     */
    public static function getUserBenefits(int $userId): array
    {
        $user = User::find($userId);
        if (!$user || !$user['membership_level_id']) {
            return [];
        }

        $levelId = $user['membership_level_id'];

        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT b.* FROM `{$prefix}vip_benefits` b
                JOIN `{$prefix}membership_level_benefits` lb ON b.id = lb.benefit_id
                WHERE lb.membership_level_id = :level_id AND b.is_enabled = 1
                ORDER BY b.sort_order ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':level_id' => $levelId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 检查用户是否拥有特定权益
     *
     * @param int $userId
     * @param string $benefitKey
     * @return bool
     */
    public static function hasBenefit(int $userId, string $benefitKey): bool
    {
        $benefits = self::getUserBenefits($userId);
        foreach ($benefits as $benefit) {
            if ($benefit['benefit_key'] === $benefitKey && $benefit['is_enabled']) {
                return true;
            }
        }
        return false;
    }
}
