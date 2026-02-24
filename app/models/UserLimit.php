<?php

namespace app\models;

use app\services\Database;
use PDO;

class UserLimit
{
    // Default limits for free users.
    private static $defaultFreeLimits = [
        'max_novels' => 5,
        'max_chapters_per_novel' => 100,
        'max_prompts' => 20,
        'max_agents' => 5,
        'max_workflows' => 3,
        'max_folders' => 10,
        'daily_word_limit' => 10000,
        'monthly_word_limit' => 300000,
        'max_ai_generations_per_day' => 50,
        'max_file_upload_size' => 10, // MB
        'max_storage_space' => 100, // MB
    ];

    // Default limits for VIP users.
    private static $defaultVipLimits = [
        'max_novels' => 50,
        'max_chapters_per_novel' => -1, // Unlimited
        'max_prompts' => 200,
        'max_agents' => 50,
        'max_workflows' => 30,
        'max_folders' => 100,
        'daily_word_limit' => 100000,
        'monthly_word_limit' => 3000000,
        'max_ai_generations_per_day' => 500,
        'max_file_upload_size' => 100, // MB
        'max_storage_space' => 10240, // 10GB in MB
    ];

    public static function getFreeDefaultLimits(): array
    {
        return self::$defaultFreeLimits;
    }

    public static function getVipDefaultLimits(): array
    {
        return self::$defaultVipLimits;
    }

    /**
     * Get the limits for a user, falling back to defaults.
     *
     * @param int $userId
     * @return array
     */
    public static function getLimitsByUserId(int $userId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}user_limits` WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $userLimits = $stmt->fetch(PDO::FETCH_ASSOC);

        $membership = MembershipPackage::getUserMembership($userId);
        $defaultLimits = ($membership && empty($membership['is_expired'])) 
            ? self::$defaultVipLimits 
            : self::$defaultFreeLimits;

        if (!$userLimits) {
            return $defaultLimits;
        }

        // Merge user-specific limits with defaults
        return array_merge($defaultLimits, $userLimits);
    }

    /**
     * Creates or updates the limits for a user.
     *
     * @param int $userId
     * @param array $limits
     * @return bool
     */
    public static function save(int $userId, array $limits): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        // Check if user limits already exist
        $existingLimits = self::getLimitsByUserId($userId);

        $columns = array_keys(self::$defaultFreeLimits);
        $updateData = [];

        foreach ($columns as $column) {
            if (isset($limits[$column])) {
                $updateData[$column] = $limits[$column];
            }
        }

        if (empty($updateData)) {
            return true; // Nothing to update
        }

        try {
            if ($existingLimits && isset($existingLimits['id'])) {
                // Update existing record
                $setClauses = [];
                foreach ($updateData as $key => $value) {
                    $setClauses[] = "`$key` = :$key";
                }
                $sql = "UPDATE `{$prefix}user_limits` SET " . implode(', ', $setClauses) . " WHERE user_id = :user_id";
                $updateData['user_id'] = $userId;
                
                $stmt = $pdo->prepare($sql);
                return $stmt->execute($updateData);
            } else {
                // Insert new record
                $updateData['user_id'] = $userId;
                $cols = implode(', ', array_keys($updateData));
                $placeholders = ':' . implode(', :', array_keys($updateData));

                $sql = "INSERT INTO `{$prefix}user_limits` ($cols) VALUES ($placeholders)";
                $stmt = $pdo->prepare($sql);
                return $stmt->execute($updateData);
            }
        } catch (\Exception $e) {
            error_log('Error saving user limits: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a user has access for a specific limit type.
     * Note: This is a simplified check. Real implementation needs to count actual usage.
     *
     * @param int $userId
     * @param string $limitType
     * @param int $currentUsage
     * @return bool
     */
    public static function hasAccess(int $userId, string $limitType, int $currentUsage): bool
    {
        $limits = self::getLimitsByUserId($userId);
        
        if (!isset($limits[$limitType])) {
            return false; // Limit type not defined
        }

        $limitValue = $limits[$limitType];

        if ($limitValue == -1) {
            return true; // Unlimited
        }

        return $currentUsage < $limitValue;
    }
}
