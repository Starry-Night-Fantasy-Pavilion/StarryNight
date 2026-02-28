<?php

namespace app\services;

use app\services\Database;
use PDO;

/**
 * 星夜创作引擎权限服务
 * 用于检查用户是否有权限使用特定版本的星夜创作引擎
 */
class StarryNightPermissionService
{
    /**
     * 检查用户是否有权限使用指定版本的星夜创作引擎
     *
     * @param int $userId 用户ID
     * @param string $engineVersion 引擎版本
     * @return array ['has_permission' => bool, 'version' => string, 'config' => array]
     */
    public function checkPermission(int $userId, string $engineVersion): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        // 获取用户的会员等级
        $membershipSql = "SELECT m.level_id 
                         FROM `{$prefix}user_memberships` m 
                         WHERE m.user_id = :user_id AND m.status = 'active' 
                         LIMIT 1";
        $stmt = $pdo->prepare($membershipSql);
        $stmt->execute([':user_id' => $userId]);
        $membership = $stmt->fetch(PDO::FETCH_ASSOC);
        $membershipLevelId = $membership ? $membership['level_id'] : null;

        // 检查用户是否有自定义配置
        // 注意：user_starry_night_configs 表结构只有 user_id 和 config_json，没有 engine_version 和 is_enabled
        $userConfigSql = "SELECT * FROM `{$prefix}user_starry_night_configs` 
                         WHERE user_id = :user_id";
        $userConfigStmt = $pdo->prepare($userConfigSql);
        $userConfigStmt->execute([':user_id' => $userId]);
        $userConfig = $userConfigStmt->fetch(PDO::FETCH_ASSOC);

        // 如果有自定义配置，使用自定义配置
        if ($userConfig && !empty($userConfig['config_json'])) {
            $customConfig = json_decode($userConfig['config_json'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($customConfig)) {
                // 如果配置中指定了版本，检查是否匹配
                if (!isset($customConfig['engine_version']) || $customConfig['engine_version'] === $engineVersion) {
                    return [
                        'has_permission' => true,
                        'version' => $engineVersion,
                        'config' => $customConfig,
                        'source' => 'user_custom'
                    ];
                }
            }
        }

        // 检查后台配置的权限
        $permissionSql = "SELECT * FROM `{$prefix}starry_night_engine_permissions` 
                         WHERE engine_version = :version 
                         AND (membership_level_id = :level_id OR (membership_level_id IS NULL AND :level_id IS NULL))
                         AND is_enabled = 1
                         ORDER BY membership_level_id DESC
                         LIMIT 1";
        $permissionStmt = $pdo->prepare($permissionSql);
        $permissionStmt->execute([
            ':version' => $engineVersion,
            ':level_id' => $membershipLevelId
        ]);
        $permission = $permissionStmt->fetch(PDO::FETCH_ASSOC);

        if ($permission) {
            $config = [];
            if ($permission['custom_config']) {
                $config = json_decode($permission['custom_config'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $config = [];
                }
            }

            return [
                'has_permission' => true,
                'version' => $engineVersion,
                'config' => $config,
                'source' => 'admin_config'
            ];
        }

        // 如果没有找到权限配置，返回无权限
        return [
            'has_permission' => false,
            'version' => $engineVersion,
            'config' => [],
            'source' => 'none'
        ];
    }

    /**
     * 获取用户可用的所有引擎版本
     *
     * @param int $userId 用户ID
     * @param int|null $membershipLevelId 会员等级ID（可选，如果不提供则从数据库查询）
     * @return array
     */
    public function getAvailableVersions(int $userId, ?int $membershipLevelId = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        // 如果没有提供会员等级ID，则从数据库查询
        if ($membershipLevelId === null) {
            $membershipSql = "SELECT m.level_id 
                             FROM `{$prefix}user_memberships` m 
                             WHERE m.user_id = :user_id AND m.status = 'active' 
                             LIMIT 1";
            $stmt = $pdo->prepare($membershipSql);
            $stmt->execute([':user_id' => $userId]);
            $membership = $stmt->fetch(PDO::FETCH_ASSOC);
            $membershipLevelId = $membership ? $membership['level_id'] : null;
        }

        // 查询所有可用的版本配置
        $sql = "SELECT * FROM `{$prefix}starry_night_engine_permissions` 
               WHERE (membership_level_id = :level_id OR (membership_level_id IS NULL AND :level_id IS NULL))
               AND is_enabled = 1
               ORDER BY 
                 CASE engine_version 
                   WHEN 'basic' THEN 1
                   WHEN 'standard' THEN 2
                   WHEN 'premium' THEN 3
                   WHEN 'enterprise' THEN 4
                   ELSE 5
                 END";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':level_id' => $membershipLevelId]);
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($permissions as $permission) {
            $config = [];
            if ($permission['custom_config']) {
                $config = json_decode($permission['custom_config'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $config = [];
                }
            }

            $result[$permission['engine_version']] = [
                'version' => $permission['engine_version'],
                'is_enabled' => true,
                'description' => $permission['description'],
                'config' => $config
            ];
        }

        // 也检查所有可能的版本（即使没有权限）
        $allVersions = ['basic', 'standard', 'premium', 'enterprise'];
        foreach ($allVersions as $version) {
            if (!isset($result[$version])) {
                $result[$version] = [
                    'version' => $version,
                    'is_enabled' => false,
                    'description' => '此版本需要更高等级的会员权限',
                    'config' => []
                ];
            }
        }

        return $result;
    }

    /**
     * 获取用户当前应该使用的引擎版本（最高可用版本）
     *
     * @param int $userId 用户ID
     * @return string|null
     */
    public function getCurrentVersion(int $userId): ?string
    {
        $availableVersions = $this->getAvailableVersions($userId);
        
        // 按优先级返回最高版本
        $priority = ['enterprise' => 4, 'premium' => 3, 'standard' => 2, 'basic' => 1];
        $highestVersion = null;
        $highestPriority = 0;

        foreach ($availableVersions as $version => $info) {
            if ($info['is_enabled'] && isset($priority[$version])) {
                if ($priority[$version] > $highestPriority) {
                    $highestPriority = $priority[$version];
                    $highestVersion = $version;
                }
            }
        }

        return $highestVersion;
    }

    /**
     * 统一错误处理
     */
    protected function handleError(\Exception $e, $operation = '') {
        $errorMessage = $operation ? $operation . '失败: ' . $e->getMessage() : $e->getMessage();
        
        // 记录错误日志
        error_log('Service Error: ' . $errorMessage);
        
        // 抛出自定义异常
        throw new \Exception($errorMessage, $e->getCode(), $e);
    }
}
