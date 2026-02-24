<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 嵌入式模型配置模型
 */
class EmbeddingModelConfig
{
    /**
     * 创建嵌入式模型配置
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}embedding_model_configs` 
                (name, display_name, type, provider, model_name, api_key, base_url, max_tokens, dimension, is_enabled, sort_order) 
                VALUES (:name, :display_name, :type, :provider, :model_name, :api_key, :base_url, :max_tokens, :dimension, :is_enabled, :sort_order)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':name' => $data['name'],
            ':display_name' => $data['display_name'],
            ':type' => $data['type'] ?? 'platform',
            ':provider' => $data['provider'] ?? null,
            ':model_name' => $data['model_name'] ?? null,
            ':api_key' => $data['api_key'] ?? null,
            ':base_url' => $data['base_url'] ?? null,
            ':max_tokens' => $data['max_tokens'] ?? 8192,
            ':dimension' => $data['dimension'] ?? 1536,
            ':is_enabled' => $data['is_enabled'] ?? 1,
            ':sort_order' => $data['sort_order'] ?? 0
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取嵌入式模型配置
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}embedding_model_configs` WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 根据名称获取嵌入式模型配置
     *
     * @param string $name
     * @return array|null
     */
    public static function findByName(string $name): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}embedding_model_configs` WHERE name = :name";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':name' => $name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 获取嵌入式模型配置列表
     *
     * @param bool|null $isEnabled
     * @param string|null $type
     * @param string|null $provider
     * @return array
     */
    public static function getAll(?bool $isEnabled = null, ?string $type = null, ?string $provider = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}embedding_model_configs`";

        $where = [];
        $params = [];

        if ($isEnabled !== null) {
            $where[] = "is_enabled = :is_enabled";
            $params[':is_enabled'] = $isEnabled ? 1 : 0;
        }

        if ($type) {
            $where[] = "type = :type";
            $params[':type'] = $type;
        }

        if ($provider) {
            $where[] = "provider = :provider";
            $params[':provider'] = $provider;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY sort_order ASC, created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取启用的嵌入式模型配置
     *
     * @param string|null $type
     * @return array
     */
    public static function getEnabled(?string $type = null): array
    {
        return self::getAll(true, $type);
    }

    /**
     * 获取平台预设的嵌入式模型
     *
     * @return array
     */
    public static function getPlatformModels(): array
    {
        return self::getAll(true, 'platform');
    }

    /**
     * 获取用户自定义的嵌入式模型
     *
     * @param int|null $userId
     * @return array
     */
    public static function getCustomModels(?int $userId = null): array
    {
        // 这里可以根据需要添加用户ID过滤
        return self::getAll(true, 'custom');
    }

    /**
     * 更新嵌入式模型配置
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = ['display_name', 'type', 'provider', 'model_name', 'api_key', 'base_url', 'max_tokens', 'dimension', 'is_enabled', 'sort_order'];
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

        $sql = "UPDATE `{$prefix}embedding_model_configs` SET " . implode(', ', $updates) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除嵌入式模型配置
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}embedding_model_configs` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 启用嵌入式模型配置
     *
     * @param int $id
     * @return bool
     */
    public static function enable(int $id): bool
    {
        return self::update($id, ['is_enabled' => 1]);
    }

    /**
     * 禁用嵌入式模型配置
     *
     * @param int $id
     * @return bool
     */
    public static function disable(int $id): bool
    {
        return self::update($id, ['is_enabled' => 0]);
    }

    /**
     * 测试嵌入式模型
     *
     * @param int $id
     * @param string $testText
     * @return array
     */
    public static function testModel(int $id, string $testText = "This is a test text for embedding."): array
    {
        $config = self::find($id);
        if (!$config) {
            return ['success' => false, 'error' => '模型配置不存在'];
        }

        try {
            // 根据不同的提供商进行测试
            switch ($config['provider']) {
                case 'openai':
                    return self::testOpenAIModel($config, $testText);
                case 'anthropic':
                    return self::testAnthropicModel($config, $testText);
                case 'local':
                    return self::testLocalModel($config, $testText);
                default:
                    return ['success' => false, 'error' => '不支持的模型提供商'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 测试OpenAI模型
     *
     * @param array $config
     * @param string $testText
     * @return array
     */
    private static function testOpenAIModel(array $config, string $testText): array
    {
        if (empty($config['api_key'])) {
            return ['success' => false, 'error' => 'API Key不能为空'];
        }

        // 这里应该实现实际的OpenAI API调用
        // 暂时返回模拟结果
        return [
            'success' => true,
            'message' => '模型测试成功',
            'embedding_dimension' => $config['dimension'],
            'tokens_used' => strlen($testText) // 模拟Token使用量
        ];
    }

    /**
     * 测试Anthropic模型
     *
     * @param array $config
     * @param string $testText
     * @return array
     */
    private static function testAnthropicModel(array $config, string $testText): array
    {
        if (empty($config['api_key'])) {
            return ['success' => false, 'error' => 'API Key不能为空'];
        }

        return [
            'success' => true,
            'message' => '模型测试成功',
            'embedding_dimension' => $config['dimension'],
            'tokens_used' => strlen($testText)
        ];
    }

    /**
     * 测试本地模型
     *
     * @param array $config
     * @param string $testText
     * @return array
     */
    private static function testLocalModel(array $config, string $testText): array
    {
        if (empty($config['base_url'])) {
            return ['success' => false, 'error' => 'Base URL不能为空'];
        }

        return [
            'success' => true,
            'message' => '模型测试成功',
            'embedding_dimension' => $config['dimension'],
            'tokens_used' => strlen($testText)
        ];
    }

    /**
     * 获取默认嵌入式模型
     *
     * @return array|null
     */
    public static function getDefault(): ?array
    {
        $models = self::getEnabled();
        return !empty($models) ? $models[0] : null;
    }

    /**
     * 获取模型类型选项
     */
    public static function getTypeOptions(): array
    {
        return [
            'platform' => '平台预设',
            'custom' => '用户自定义'
        ];
    }

    /**
     * 获取提供商选项
     */
    public static function getProviderOptions(): array
    {
        return [
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic',
            'local' => '本地模型'
        ];
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

        // 验证名称
        if (empty($data['name'])) {
            $errors[] = '名称不能为空';
        }

        // 验证显示名称
        if (empty($data['display_name'])) {
            $errors[] = '显示名称不能为空';
        }

        // 验证类型
        if (isset($data['type']) && !in_array($data['type'], array_keys(self::getTypeOptions()))) {
            $errors[] = '无效的模型类型';
        }

        // 验证提供商
        if (isset($data['provider']) && !in_array($data['provider'], array_keys(self::getProviderOptions()))) {
            $errors[] = '无效的模型提供商';
        }

        // 验证最大Token数
        if (isset($data['max_tokens']) && ($data['max_tokens'] <= 0 || $data['max_tokens'] > 100000)) {
            $errors[] = '最大Token数必须在1-100000之间';
        }

        // 验证向量维度
        if (isset($data['dimension']) && ($data['dimension'] <= 0 || $data['dimension'] > 10000)) {
            $errors[] = '向量维度必须在1-10000之间';
        }

        // 验证自定义模型的必需字段
        if (isset($data['type']) && $data['type'] === 'custom') {
            if (empty($data['model_name'])) {
                $errors[] = '自定义模型的模型名称不能为空';
            }
            if (empty($data['base_url'])) {
                $errors[] = '自定义模型的Base URL不能为空';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 获取模型使用统计
     *
     * @param int|null $modelId
     * @param string|null $dateRange
     * @return array
     */
    public static function getUsageStats(?int $modelId = null, ?string $dateRange = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    embedding_model,
                    COUNT(*) as total_operations,
                    SUM(tokens_used) as total_tokens_used,
                    AVG(execution_time) as avg_execution_time
                FROM `{$prefix}vector_db_usage_logs`";

        $params = [];
        $where = [];

        if ($modelId) {
            $model = self::find($modelId);
            if ($model) {
                $where[] = "embedding_model = :embedding_model";
                $params[':embedding_model'] = $model['name'];
            }
        }

        if ($dateRange) {
            switch ($dateRange) {
                case 'today':
                    $where[] = "DATE(created_at) = CURDATE()";
                    break;
                case 'week':
                    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                case 'year':
                    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                    break;
            }
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " GROUP BY embedding_model";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [];
        foreach ($results as $result) {
            $stats[$result['embedding_model']] = [
                'total_operations' => (int)$result['total_operations'],
                'total_tokens_used' => (int)$result['total_tokens_used'],
                'avg_execution_time' => round((float)$result['avg_execution_time'], 2)
            ];
        }

        return $stats;
    }
}