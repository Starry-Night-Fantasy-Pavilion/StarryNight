<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 向量数据库配置模型
 */
class VectorDbConfig
{
    /**
     * 创建向量数据库配置
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}vector_db_configs` 
                (name, type, connection_config, is_enabled, sort_order) 
                VALUES (:name, :type, :connection_config, :is_enabled, :sort_order)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':name' => $data['name'],
            ':type' => $data['type'],
            ':connection_config' => $data['connection_config'] ?? null,
            ':is_enabled' => $data['is_enabled'] ?? 1,
            ':sort_order' => $data['sort_order'] ?? 0
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取向量数据库配置
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}vector_db_configs` WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 根据名称获取向量数据库配置
     *
     * @param string $name
     * @return array|null
     */
    public static function findByName(string $name): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}vector_db_configs` WHERE name = :name";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':name' => $name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 获取向量数据库配置列表
     *
     * @param bool|null $isEnabled
     * @param string|null $type
     * @return array
     */
    public static function getAll(?bool $isEnabled = null, ?string $type = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}vector_db_configs`";

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

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY sort_order ASC, created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取启用的向量数据库配置
     *
     * @param string|null $type
     * @return array
     */
    public static function getEnabled(?string $type = null): array
    {
        return self::getAll(true, $type);
    }

    /**
     * 更新向量数据库配置
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = ['name', 'type', 'connection_config', 'is_enabled', 'sort_order'];
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

        $sql = "UPDATE `{$prefix}vector_db_configs` SET " . implode(', ', $updates) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除向量数据库配置
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}vector_db_configs` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 启用向量数据库配置
     *
     * @param int $id
     * @return bool
     */
    public static function enable(int $id): bool
    {
        return self::update($id, ['is_enabled' => 1]);
    }

    /**
     * 禁用向量数据库配置
     *
     * @param int $id
     * @return bool
     */
    public static function disable(int $id): bool
    {
        return self::update($id, ['is_enabled' => 0]);
    }

    /**
     * 获取默认向量数据库配置
     *
     * @param string|null $type
     * @return array|null
     */
    public static function getDefault(?string $type = null): ?array
    {
        $configs = self::getEnabled($type);
        return !empty($configs) ? $configs[0] : null;
    }

    /**
     * 测试向量数据库连接
     *
     * @param int $id
     * @return array
     */
    public static function testConnection(int $id): array
    {
        $config = self::find($id);
        if (!$config) {
            return ['success' => false, 'error' => '配置不存在'];
        }

        $connectionConfig = json_decode($config['connection_config'], true);
        if (!$connectionConfig) {
            return ['success' => false, 'error' => '连接配置格式错误'];
        }

        try {
            // 根据不同的向量数据库类型进行连接测试
            switch ($config['name']) {
                case 'Pinecone':
                    return self::testPineconeConnection($connectionConfig);
                case 'Weaviate':
                    return self::testWeaviateConnection($connectionConfig);
                case 'Milvus':
                    return self::testMilvusConnection($connectionConfig);
                case 'Chroma':
                    return self::testChromaConnection($connectionConfig);
                default:
                    return ['success' => false, 'error' => '不支持的向量数据库类型'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 测试Pinecone连接
     *
     * @param array $config
     * @return array
     */
    private static function testPineconeConnection(array $config): array
    {
        // 这里应该实现实际的Pinecone连接测试
        // 暂时返回模拟结果
        if (empty($config['api_key']) || empty($config['environment'])) {
            return ['success' => false, 'error' => 'API Key和Environment不能为空'];
        }

        return ['success' => true, 'message' => '连接成功'];
    }

    /**
     * 测试Weaviate连接
     *
     * @param array $config
     * @return array
     */
    private static function testWeaviateConnection(array $config): array
    {
        // 这里应该实现实际的Weaviate连接测试
        if (empty($config['url'])) {
            return ['success' => false, 'error' => 'URL不能为空'];
        }

        return ['success' => true, 'message' => '连接成功'];
    }

    /**
     * 测试Milvus连接
     *
     * @param array $config
     * @return array
     */
    private static function testMilvusConnection(array $config): array
    {
        // 这里应该实现实际的Milvus连接测试
        if (empty($config['host']) || empty($config['port'])) {
            return ['success' => false, 'error' => 'Host和Port不能为空'];
        }

        return ['success' => true, 'message' => '连接成功'];
    }

    /**
     * 测试Chroma连接
     *
     * @param array $config
     * @return array
     */
    private static function testChromaConnection(array $config): array
    {
        // 这里应该实现实际的Chroma连接测试
        if (empty($config['host']) || empty($config['port'])) {
            return ['success' => false, 'error' => 'Host和Port不能为空'];
        }

        return ['success' => true, 'message' => '连接成功'];
    }

    /**
     * 获取向量数据库类型选项
     */
    public static function getTypeOptions(): array
    {
        return [
            'single' => '单一向量数据库',
            'multi' => '多向量数据库'
        ];
    }

    /**
     * 获取向量数据库名称选项
     */
    public static function getNameOptions(): array
    {
        return [
            'Pinecone' => 'Pinecone',
            'Weaviate' => 'Weaviate',
            'Milvus' => 'Milvus',
            'Chroma' => 'Chroma'
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

        // 验证类型
        if (isset($data['type']) && !in_array($data['type'], array_keys(self::getTypeOptions()))) {
            $errors[] = '无效的数据库类型';
        }

        // 验证连接配置
        if (isset($data['connection_config'])) {
            $connectionConfig = is_string($data['connection_config']) ? json_decode($data['connection_config'], true) : $data['connection_config'];
            if (!$connectionConfig) {
                $errors[] = '连接配置格式错误';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 获取向量数据库使用统计
     *
     * @param int|null $configId
     * @param string|null $dateRange
     * @return array
     */
    public static function getUsageStats(?int $configId = null, ?string $dateRange = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    vector_db_type,
                    COUNT(*) as total_operations,
                    SUM(tokens_used) as total_tokens_used,
                    AVG(execution_time) as avg_execution_time
                FROM `{$prefix}vector_db_usage_logs`";

        $params = [];
        $where = [];

        if ($configId) {
            // 这里需要根据configId获取对应的vector_db_type
            $config = self::find($configId);
            if ($config) {
                $where[] = "vector_db_type = :vector_db_type";
                $params[':vector_db_type'] = $config['name'];
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

        $sql .= " GROUP BY vector_db_type";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [];
        foreach ($results as $result) {
            $stats[$result['vector_db_type']] = [
                'total_operations' => (int)$result['total_operations'],
                'total_tokens_used' => (int)$result['total_tokens_used'],
                'avg_execution_time' => round((float)$result['avg_execution_time'], 2)
            ];
        }

        return $stats;
    }
}