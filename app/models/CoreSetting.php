<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 核心设定存储模型
 */
class CoreSetting
{
    /**
     * 创建核心设定
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}core_settings` 
                (user_id, project_id, project_type, setting_type, setting_key, setting_value, vector_id, embedding_model, metadata, is_active) 
                VALUES (:user_id, :project_id, :project_type, :setting_type, :setting_key, :setting_value, :vector_id, :embedding_model, :metadata, :is_active)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':project_id' => $data['project_id'],
            ':project_type' => $data['project_type'],
            ':setting_type' => $data['setting_type'],
            ':setting_key' => $data['setting_key'],
            ':setting_value' => $data['setting_value'],
            ':vector_id' => $data['vector_id'] ?? null,
            ':embedding_model' => $data['embedding_model'] ?? null,
            ':metadata' => $data['metadata'] ?? null,
            ':is_active' => $data['is_active'] ?? 1
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取核心设定
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT cs.*, u.username, u.nickname as user_nickname,
                       CASE 
                           WHEN cs.project_type = 'novel' THEN (SELECT title FROM `{$prefix}novels` WHERE id = cs.project_id)
                           WHEN cs.project_type = 'anime' THEN (SELECT title FROM `{$prefix}anime_projects` WHERE id = cs.project_id)
                           WHEN cs.project_type = 'music' THEN (SELECT title FROM `{$prefix}music_projects` WHERE id = cs.project_id)
                       END as project_title
                FROM `{$prefix}core_settings` cs
                LEFT JOIN `{$prefix}users` u ON cs.user_id = u.id
                WHERE cs.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 获取项目的核心设定列表
     *
     * @param int $projectId
     * @param string $projectType
     * @param string|null $settingType
     * @param bool|null $isActive
     * @return array
     */
    public static function getByProject(int $projectId, string $projectType, ?string $settingType = null, ?bool $isActive = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}core_settings` 
                WHERE project_id = :project_id AND project_type = :project_type";

        $params = [
            ':project_id' => $projectId,
            ':project_type' => $projectType
        ];

        if ($settingType) {
            $sql .= " AND setting_type = :setting_type";
            $params[':setting_type'] = $settingType;
        }

        if ($isActive !== null) {
            $sql .= " AND is_active = :is_active";
            $params[':is_active'] = $isActive ? 1 : 0;
        }

        $sql .= " ORDER BY setting_type ASC, setting_key ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取用户的核心设定列表
     *
     * @param int $userId
     * @param string|null $projectType
     * @param string|null $settingType
     * @return array
     */
    public static function getByUser(int $userId, ?string $projectType = null, ?string $settingType = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT cs.*, 
                       CASE 
                           WHEN cs.project_type = 'novel' THEN (SELECT title FROM `{$prefix}novels` WHERE id = cs.project_id)
                           WHEN cs.project_type = 'anime' THEN (SELECT title FROM `{$prefix}anime_projects` WHERE id = cs.project_id)
                           WHEN cs.project_type = 'music' THEN (SELECT title FROM `{$prefix}music_projects` WHERE id = cs.project_id)
                       END as project_title
                FROM `{$prefix}core_settings` cs
                WHERE cs.user_id = :user_id";

        $params = [':user_id' => $userId];

        if ($projectType) {
            $sql .= " AND cs.project_type = :project_type";
            $params[':project_type'] = $projectType;
        }

        if ($settingType) {
            $sql .= " AND cs.setting_type = :setting_type";
            $params[':setting_type'] = $settingType;
        }

        $sql .= " ORDER BY cs.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 根据向量ID获取核心设定
     *
     * @param string $vectorId
     * @return array|null
     */
    public static function findByVectorId(string $vectorId): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}core_settings` WHERE vector_id = :vector_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':vector_id' => $vectorId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * 更新核心设定
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = ['setting_value', 'vector_id', 'embedding_model', 'metadata', 'is_active'];
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

        $sql = "UPDATE `{$prefix}core_settings` SET " . implode(', ', $updates) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除核心设定
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}core_settings` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * 批量删除项目的核心设定
     *
     * @param int $projectId
     * @param string $projectType
     * @return bool
     */
    public static function deleteByProject(int $projectId, string $projectType): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}core_settings` WHERE project_id = :project_id AND project_type = :project_type";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':project_id' => $projectId,
            ':project_type' => $projectType
        ]);
    }

    /**
     * 激活核心设定
     *
     * @param int $id
     * @return bool
     */
    public static function activate(int $id): bool
    {
        return self::update($id, ['is_active' => 1]);
    }

    /**
     * 停用核心设定
     *
     * @param int $id
     * @return bool
     */
    public static function deactivate(int $id): bool
    {
        return self::update($id, ['is_active' => 0]);
    }

    /**
     * 搜索核心设定
     *
     * @param int $userId
     * @param string $keyword
     * @param string|null $projectType
     * @param string|null $settingType
     * @return array
     */
    public static function search(int $userId, string $keyword, ?string $projectType = null, ?string $settingType = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT cs.*, 
                       CASE 
                           WHEN cs.project_type = 'novel' THEN (SELECT title FROM `{$prefix}novels` WHERE id = cs.project_id)
                           WHEN cs.project_type = 'anime' THEN (SELECT title FROM `{$prefix}anime_projects` WHERE id = cs.project_id)
                           WHEN cs.project_type = 'music' THEN (SELECT title FROM `{$prefix}music_projects` WHERE id = cs.project_id)
                       END as project_title
                FROM `{$prefix}core_settings` cs
                WHERE cs.user_id = :user_id 
                AND (cs.setting_key LIKE :keyword OR cs.setting_value LIKE :keyword)";

        $params = [
            ':user_id' => $userId,
            ':keyword' => '%' . $keyword . '%'
        ];

        if ($projectType) {
            $sql .= " AND cs.project_type = :project_type";
            $params[':project_type'] = $projectType;
        }

        if ($settingType) {
            $sql .= " AND cs.setting_type = :setting_type";
            $params[':setting_type'] = $settingType;
        }

        $sql .= " ORDER BY cs.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取项目类型选项
     */
    public static function getProjectTypeOptions(): array
    {
        return [
            'novel' => '小说',
            'anime' => '动漫',
            'music' => '音乐'
        ];
    }

    /**
     * 获取设定类型选项
     */
    public static function getSettingTypeOptions(): array
    {
        return [
            'worldview' => '世界观',
            'character' => '角色',
            'event' => '事件',
            'theme' => '主题',
            'timeline' => '时间线',
            'geography' => '地理',
            'magic_system' => '魔法系统',
            'tech_system' => '科技系统',
            'culture' => '文化',
            'plot' => '情节',
            'scene' => '场景',
            'melody' => '旋律',
            'style' => '风格',
            'instrument' => '乐器配置'
        ];
    }

    /**
     * 根据项目类型获取设定类型选项
     *
     * @param string $projectType
     * @return array
     */
    public static function getSettingTypeOptionsByProject(string $projectType): array
    {
        $allOptions = self::getSettingTypeOptions();
        
        switch ($projectType) {
            case 'novel':
                return array_intersect_key($allOptions, array_flip([
                    'worldview', 'character', 'event', 'theme', 'timeline', 
                    'geography', 'magic_system', 'tech_system', 'culture', 'plot'
                ]));
            case 'anime':
                return array_intersect_key($allOptions, array_flip([
                    'worldview', 'character', 'event', 'theme', 'scene', 
                    'geography', 'magic_system', 'tech_system', 'culture'
                ]));
            case 'music':
                return array_intersect_key($allOptions, array_flip([
                    'theme', 'melody', 'style', 'instrument'
                ]));
            default:
                return $allOptions;
        }
    }

    /**
     * 验证设定数据
     *
     * @param array $data
     * @return array
     */
    public static function validateSetting(array $data): array
    {
        $errors = [];

        // 验证项目类型
        if (isset($data['project_type']) && !in_array($data['project_type'], array_keys(self::getProjectTypeOptions()))) {
            $errors[] = '无效的项目类型';
        }

        // 验证设定类型
        if (isset($data['setting_type']) && !in_array($data['setting_type'], array_keys(self::getSettingTypeOptions()))) {
            $errors[] = '无效的设定类型';
        }

        // 验证设定键名
        if (empty($data['setting_key'])) {
            $errors[] = '设定键名不能为空';
        }

        // 验证设定值
        if (empty($data['setting_value'])) {
            $errors[] = '设定值不能为空';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 获取核心设定统计
     *
     * @param int|null $userId
     * @param string|null $projectType
     * @return array
     */
    public static function getSettingStats(?int $userId = null, ?string $projectType = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    project_type,
                    setting_type,
                    COUNT(*) as total_settings,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_settings
                FROM `{$prefix}core_settings`";

        $params = [];
        $where = [];

        if ($userId) {
            $where[] = "user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        if ($projectType) {
            $where[] = "project_type = :project_type";
            $params[':project_type'] = $projectType;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " GROUP BY project_type, setting_type";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [];
        foreach ($results as $result) {
            $projectType = $result['project_type'];
            if (!isset($stats[$projectType])) {
                $stats[$projectType] = [];
            }
            
            $stats[$projectType][$result['setting_type']] = [
                'total_settings' => (int)$result['total_settings'],
                'active_settings' => (int)$result['active_settings']
            ];
        }

        return $stats;
    }

    /**
     * 导出核心设定
     *
     * @param int $projectId
     * @param string $projectType
     * @return array
     */
    public static function exportSettings(int $projectId, string $projectType): array
    {
        $settings = self::getByProject($projectId, $projectType);
        
        $export = [
            'project_id' => $projectId,
            'project_type' => $projectType,
            'export_time' => date('Y-m-d H:i:s'),
            'settings' => []
        ];

        foreach ($settings as $setting) {
            $export['settings'][] = [
                'type' => $setting['setting_type'],
                'key' => $setting['setting_key'],
                'value' => $setting['setting_value'],
                'metadata' => json_decode($setting['metadata'], true) ?? []
            ];
        }

        return $export;
    }

    /**
     * 导入核心设定
     *
     * @param int $userId
     * @param int $projectId
     * @param string $projectType
     * @param array $settings
     * @return array
     */
    public static function importSettings(int $userId, int $projectId, string $projectType, array $settings): array
    {
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($settings as $settingData) {
            $data = [
                'user_id' => $userId,
                'project_id' => $projectId,
                'project_type' => $projectType,
                'setting_type' => $settingData['type'],
                'setting_key' => $settingData['key'],
                'setting_value' => $settingData['value'],
                'metadata' => isset($settingData['metadata']) ? json_encode($settingData['metadata']) : null
            ];

            $validation = self::validateSetting($data);
            if (!$validation['valid']) {
                $errorCount++;
                $errors[] = "设定 {$settingData['key']}: " . implode(', ', $validation['errors']);
                continue;
            }

            if (self::create($data)) {
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "设定 {$settingData['key']}: 导入失败";
            }
        }

        return [
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors
        ];
    }
}