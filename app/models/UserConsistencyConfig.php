<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 用户一致性配置模型
 */
class UserConsistencyConfig
{
    /**
     * 创建用户一致性配置
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}user_consistency_configs` 
                (user_id, db_mode, embedding_model, custom_embedding_api_key, custom_embedding_base_url, custom_embedding_model_name, check_frequency, check_scope, sensitivity) 
                VALUES (:user_id, :db_mode, :embedding_model, :custom_embedding_api_key, :custom_embedding_base_url, :custom_embedding_model_name, :check_frequency, :check_scope, :sensitivity)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':db_mode' => $data['db_mode'] ?? 'single',
            ':embedding_model' => $data['embedding_model'] ?? 'openai',
            ':custom_embedding_api_key' => $data['custom_embedding_api_key'] ?? null,
            ':custom_embedding_base_url' => $data['custom_embedding_base_url'] ?? null,
            ':custom_embedding_model_name' => $data['custom_embedding_model_name'] ?? null,
            ':check_frequency' => $data['check_frequency'] ?? 'realtime',
            ':check_scope' => $data['check_scope'] ?? 'chapter',
            ':sensitivity' => $data['sensitivity'] ?? 0.70
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取用户一致性配置
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}user_consistency_configs` WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 根据用户ID获取一致性配置
     *
     * @param int $userId
     * @return array|null
     */
    public static function findByUserId(int $userId): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}user_consistency_configs` WHERE user_id = :user_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 获取用户一致性配置（如果不存在则创建默认配置）
     *
     * @param int $userId
     * @return array
     */
    public static function getOrCreateByUserId(int $userId): array
    {
        $config = self::findByUserId($userId);
        
        if (!$config) {
            $defaultConfig = [
                'user_id' => $userId,
                'db_mode' => 'single',
                'embedding_model' => 'openai',
                'check_frequency' => 'realtime',
                'check_scope' => 'chapter',
                'sensitivity' => 0.70
            ];
            
            $id = self::create($defaultConfig);
            $config = self::find($id);
        }
        
        return $config;
    }

    /**
     * 更新用户一致性配置
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = ['db_mode', 'embedding_model', 'custom_embedding_api_key', 'custom_embedding_base_url', 'custom_embedding_model_name', 'check_frequency', 'check_scope', 'sensitivity'];
        $updates = [];
        $params = [':id' => $id];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updates[] = "`$field` = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE `{$prefix}user_consistency_configs` SET " . implode(', ', $updates) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 根据用户ID更新配置
     *
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public static function updateByUserId(int $userId, array $data): bool
    {
        $config = self::findByUserId($userId);
        
        if (!$config) {
            $data['user_id'] = $userId;
            return self::create($data) !== false;
        }
        
        return self::update($config['id'], $data);
    }

    /**
     * 删除用户一致性配置
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}user_consistency_configs` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 检查用户是否开启了一致性检查
     *
     * @param int $userId
     * @return bool
     */
    public static function isEnabled(int $userId): bool
    {
        // 由于数据库中没有 is_enabled 字段，暂时返回 false
        // 可以根据其他逻辑来判断是否启用
        $config = self::findByUserId($userId);
        return $config && !empty($config['embedding_model']);
    }

    /**
     * 获取用户的嵌入式模型配置
     *
     * @param int $userId
     * @return array
     */
    public static function getEmbeddingModelConfig(int $userId): array
    {
        $config = self::findByUserId($userId);
        
        if (!$config) {
            return [];
        }

        if ($config['embedding_model'] === 'custom') {
            return [
                'type' => 'custom',
                'api_key' => $config['custom_embedding_api_key'],
                'base_url' => $config['custom_embedding_base_url'],
                'model_name' => $config['custom_embedding_model_name']
            ];
        }

        return [
            'type' => 'platform',
            'model_name' => $config['embedding_model']
        ];
    }

    /**
     * 获取数据库模式选项
     */
    public static function getDbModeOptions(): array
    {
        return [
            'single' => '单一向量数据库',
            'multi' => '多向量数据库'
        ];
    }

    /**
     * 获取检查频率选项
     */
    public static function getCheckFrequencyOptions(): array
    {
        return [
            'realtime' => '实时检查',
            '5_minutes' => '每5分钟',
            '15_minutes' => '每15分钟',
            '30_minutes' => '每30分钟',
            '1_hour' => '每小时',
            'manual' => '手动触发'
        ];
    }

    /**
     * 获取检查范围选项
     */
    public static function getCheckScopeOptions(): array
    {
        return [
            'chapter' => '当前章节',
            'project' => '当前作品',
            'all' => '所有作品'
        ];
    }

    /**
     * 获取可用的嵌入式模型选项
     */
    public static function getEmbeddingModelOptions(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT name, display_name FROM `{$prefix}embedding_model_configs` WHERE is_enabled = 1 ORDER BY sort_order ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $options = [];
        foreach ($models as $model) {
            $options[$model['name']] = $model['display_name'];
        }

        $options['custom'] = '自定义模型';

        return $options;
    }

    /**
     * 验证配置数据
     *
     * @param array $data
     * @return array
     */
    public static function validateConfig(array $data): array
    {
        $errors = [];

        // 验证数据库模式
        if (isset($data['db_mode']) && !in_array($data['db_mode'], array_keys(self::getDbModeOptions()))) {
            $errors[] = '无效的数据库模式';
        }

        // 验证检查频率
        if (isset($data['check_frequency']) && !in_array($data['check_frequency'], array_keys(self::getCheckFrequencyOptions()))) {
            $errors[] = '无效的检查频率';
        }

        // 验证检查范围
        if (isset($data['check_scope']) && !in_array($data['check_scope'], array_keys(self::getCheckScopeOptions()))) {
            $errors[] = '无效的检查范围';
        }

        // 验证敏感度
        if (isset($data['sensitivity']) && ($data['sensitivity'] < 0 || $data['sensitivity'] > 1)) {
            $errors[] = '敏感度必须在0-1之间';
        }

        // 验证自定义模型配置
        if (isset($data['embedding_model']) && $data['embedding_model'] === 'custom') {
            if (empty($data['custom_embedding_api_key'])) {
                $errors[] = '自定义模型API Key不能为空';
            }
            if (empty($data['custom_embedding_base_url'])) {
                $errors[] = '自定义模型Base URL不能为空';
            }
            if (empty($data['custom_embedding_model_name'])) {
                $errors[] = '自定义模型名称不能为空';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 获取用户统计信息
     *
     * @param int $userId
     * @return array
     */
    public static function getUserStats(int $userId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_reports,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_reports,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_reports,
                    AVG(execution_time) as avg_execution_time,
                    SUM(tokens_used) as total_tokens_used
                FROM `{$prefix}consistency_reports` 
                WHERE user_id = :user_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_reports' => (int)($result['total_reports'] ?? 0),
            'completed_reports' => (int)($result['completed_reports'] ?? 0),
            'failed_reports' => (int)($result['failed_reports'] ?? 0),
            'avg_execution_time' => round((float)($result['avg_execution_time'] ?? 0), 2),
            'total_tokens_used' => (int)($result['total_tokens_used'] ?? 0)
        ];
    }
}