<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 创作工具模型
 */
class CreationTool
{
    /**
     * 创建创作工具
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}creation_tools` 
                (name, code, category, description, icon, prompt_template, input_schema, output_schema, usage_count, is_active, sort_order) 
                VALUES (:name, :code, :category, :description, :icon, :prompt_template, :input_schema, :output_schema, :usage_count, :is_active, :sort_order)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':name' => $data['name'],
            ':code' => $data['code'],
            ':category' => $data['category'],
            ':description' => $data['description'] ?? null,
            ':icon' => $data['icon'] ?? null,
            ':prompt_template' => $data['prompt_template'],
            ':input_schema' => $data['input_schema'] ?? null,
            ':output_schema' => $data['output_schema'] ?? null,
            ':usage_count' => $data['usage_count'] ?? 0,
            ':is_active' => $data['is_active'] ?? 1,
            ':sort_order' => $data['sort_order'] ?? 0
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取创作工具
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}creation_tools` WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 根据代码获取创作工具
     *
     * @param string $code
     * @return array|null
     */
    public static function findByCode(string $code): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}creation_tools` WHERE code = :code";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':code' => $code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 获取创作工具列表
     *
     * @param int $page
     * @param int $perPage
     * @param string|null $searchTerm
     * @param string|null $category
     * @param bool|null $isActive
     * @return array
     */
    public static function getAll(int $page = 1, int $perPage = 15, ?string $searchTerm = null, ?string $category = null, ?bool $isActive = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM `{$prefix}creation_tools`";

        $where = [];
        $params = [];

        if ($searchTerm) {
            $where[] = "(name LIKE :term OR description LIKE :term)";
            $params[':term'] = '%' . $searchTerm . '%';
        }

        if ($category) {
            $where[] = "category = :category";
            $params[':category'] = $category;
        }

        if ($isActive !== null) {
            $where[] = "is_active = :is_active";
            $params[':is_active'] = $isActive ? 1 : 0;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY sort_order ASC, created_at DESC";

        // 获取总数
        $countSql = str_replace("SELECT *", "SELECT COUNT(*)", $sql);

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetchColumn();

        // 获取分页数据
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $tools = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'tools' => $tools,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 获取启用的创作工具列表
     *
     * @param string|null $category
     * @return array
     */
    public static function getActiveTools(?string $category = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}creation_tools` WHERE is_active = 1";

        $params = [];

        if ($category) {
            $sql .= " AND category = :category";
            $params[':category'] = $category;
        }

        $sql .= " ORDER BY sort_order ASC, created_at DESC";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 更新创作工具
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = ['name', 'code', 'category', 'description', 'icon', 'prompt_template', 'input_schema', 'output_schema', 'is_active', 'sort_order'];
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

        $sql = "UPDATE `{$prefix}creation_tools` SET " . implode(', ', $updates) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除创作工具
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $pdo->beginTransaction();

            // 删除相关的使用记录
            $deleteLogs = $pdo->prepare("DELETE FROM `{$prefix}creation_tool_usage_logs` WHERE tool_id = ?");
            $deleteLogs->execute([$id]);

            // 删除创作工具
            $deleteTool = $pdo->prepare("DELETE FROM `{$prefix}creation_tools` WHERE id = ?");
            $deleteTool->execute([$id]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 增加使用次数
     *
     * @param int $id
     * @return bool
     */
    public static function incrementUsage(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}creation_tools` SET usage_count = usage_count + 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 获取热门工具
     *
     * @param int $limit
     * @param string|null $category
     * @return array
     */
    public static function getPopularTools(int $limit = 10, ?string $category = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}creation_tools` WHERE is_active = 1";

        $params = [];

        if ($category) {
            $sql .= " AND category = :category";
            $params[':category'] = $category;
        }

        $sql .= " ORDER BY usage_count DESC, sort_order ASC LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取分类列表
     */
    public static function getCategories(): array
    {
        return [
            'worldview' => '世界观生成器',
            'brainstorm' => '脑洞生成器'
        ];
    }

    /**
     * 验证输入数据
     *
     * @param string $toolCode
     * @param array $inputData
     * @return array
     */
    public static function validateInput(string $toolCode, array $inputData): array
    {
        $tool = self::findByCode($toolCode);
        if (!$tool) {
            return ['valid' => false, 'errors' => ['工具不存在']];
        }

        $inputSchema = json_decode($tool['input_schema'], true);
        if (!$inputSchema) {
            return ['valid' => true, 'errors' => []];
        }

        $errors = [];
        
        foreach ($inputSchema as $field => $schema) {
            $isRequired = $schema['required'] ?? false;
            $fieldType = $schema['type'] ?? 'string';
            
            if ($isRequired && !isset($inputData[$field])) {
                $errors[] = "字段 {$field} 是必需的";
                continue;
            }
            
            if (isset($inputData[$field])) {
                $value = $inputData[$field];
                
                if ($fieldType === 'string' && !is_string($value)) {
                    $errors[] = "字段 {$field} 必须是字符串";
                }
                
                if ($fieldType === 'number' && !is_numeric($value)) {
                    $errors[] = "字段 {$field} 必须是数字";
                }
                
                if ($fieldType === 'boolean' && !is_bool($value)) {
                    $errors[] = "字段 {$field} 必须是布尔值";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 执行工具
     *
     * @param string $toolCode
     * @param array $inputData
     * @param int $userId
     * @return array
     */
    public static function executeTool(string $toolCode, array $inputData, int $userId): array
    {
        $tool = self::findByCode($toolCode);
        if (!$tool) {
            return ['success' => false, 'error' => '工具不存在'];
        }

        // 验证输入
        $validation = self::validateInput($toolCode, $inputData);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => implode(', ', $validation['errors'])];
        }

        $startTime = microtime(true);
        
        try {
            // 替换提示词模板中的变量
            $prompt = $tool['prompt_template'];
            foreach ($inputData as $key => $value) {
                $prompt = str_replace('{' . $key . '}', $value, $prompt);
            }

            // 这里应该调用AI服务来执行提示词
            // 暂时返回模拟结果
            $result = [
                'success' => true,
                'output' => $prompt, // 实际应该是AI生成的结果
                'execution_time' => round((microtime(true) - $startTime) * 1000),
                'tokens_used' => 100, // 模拟值
                'coins_spent' => 10   // 模拟值
            ];

            // 记录使用日志
            self::logUsage($tool['id'], $userId, $inputData, $result);

            // 增加使用次数
            self::incrementUsage($tool['id']);

            return $result;
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 记录工具使用日志
     *
     * @param int $toolId
     * @param int $userId
     * @param array $inputData
     * @param array $result
     * @return bool
     */
    private static function logUsage(int $toolId, int $userId, array $inputData, array $result): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}creation_tool_usage_logs` 
                (tool_id, user_id, input_data, output_data, execution_time, tokens_used, coins_spent) 
                VALUES (:tool_id, :user_id, :input_data, :output_data, :execution_time, :tokens_used, :coins_spent)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':tool_id' => $toolId,
            ':user_id' => $userId,
            ':input_data' => json_encode($inputData),
            ':output_data' => json_encode($result['output'] ?? null),
            ':execution_time' => $result['execution_time'] ?? 0,
            ':tokens_used' => $result['tokens_used'] ?? 0,
            ':coins_spent' => $result['coins_spent'] ?? 0
        ]);
    }

    /**
     * 获取工具使用统计
     *
     * @param int|null $toolId
     * @param int|null $userId
     * @param string|null $dateRange
     * @return array
     */
    public static function getUsageStats(?int $toolId = null, ?int $userId = null, ?string $dateRange = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    ct.id,
                    ct.name,
                    ct.code,
                    COUNT(ctul.id) as usage_count,
                    AVG(ctul.execution_time) as avg_execution_time,
                    SUM(ctul.tokens_used) as total_tokens_used,
                    SUM(ctul.coins_spent) as total_coins_spent
                FROM `{$prefix}creation_tools` ct
                LEFT JOIN `{$prefix}creation_tool_usage_logs` ctul ON ct.id = ctul.tool_id";

        $params = [];
        $where = [];

        if ($toolId) {
            $where[] = "ct.id = :tool_id";
            $params[':tool_id'] = $toolId;
        }

        if ($userId) {
            $where[] = "ctul.user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        if ($dateRange) {
            switch ($dateRange) {
                case 'today':
                    $where[] = "DATE(ctul.created_at) = CURDATE()";
                    break;
                case 'week':
                    $where[] = "ctul.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $where[] = "ctul.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                case 'year':
                    $where[] = "ctul.created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                    break;
            }
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " GROUP BY ct.id, ct.name, ct.code ORDER BY usage_count DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [];
        foreach ($results as $result) {
            $stats[$result['code']] = [
                'id' => (int)$result['id'],
                'name' => $result['name'],
                'usage_count' => (int)$result['usage_count'],
                'avg_execution_time' => round((float)$result['avg_execution_time'], 2),
                'total_tokens_used' => (int)$result['total_tokens_used'],
                'total_coins_spent' => (int)$result['total_coins_spent']
            ];
        }

        return $stats;
    }
}