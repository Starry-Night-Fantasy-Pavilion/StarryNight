<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 动漫场景设计模型
 */
class AnimeScene
{
    /**
     * 创建场景设计
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}anime_scenes` 
                (project_id, scene_name, scene_type, description, location, architecture_style, atmosphere, time_of_day, weather, lighting, color_scheme, key_elements, props, background_story, concept_image_url, reference_images, usage_frequency, sort_order) 
                VALUES (:project_id, :scene_name, :scene_type, :description, :location, :architecture_style, :atmosphere, :time_of_day, :weather, :lighting, :color_scheme, :key_elements, :props, :background_story, :concept_image_url, :reference_images, :usage_frequency, :sort_order)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':project_id' => $data['project_id'],
            ':scene_name' => $data['scene_name'],
            ':scene_type' => $data['scene_type'] ?? 'indoor',
            ':description' => $data['description'] ?? null,
            ':location' => $data['location'] ?? null,
            ':architecture_style' => $data['architecture_style'] ?? null,
            ':atmosphere' => $data['atmosphere'] ?? null,
            ':time_of_day' => $data['time_of_day'] ?? null,
            ':weather' => $data['weather'] ?? null,
            ':lighting' => $data['lighting'] ?? null,
            ':color_scheme' => $data['color_scheme'] ?? null,
            ':key_elements' => $data['key_elements'] ?? null,
            ':props' => $data['props'] ?? null,
            ':background_story' => $data['background_story'] ?? null,
            ':concept_image_url' => $data['concept_image_url'] ?? null,
            ':reference_images' => isset($data['reference_images']) ? json_encode($data['reference_images']) : null,
            ':usage_frequency' => $data['usage_frequency'] ?? 0,
            ':sort_order' => $data['sort_order'] ?? 0
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取场景设计
     *
     * @param int $id
     * @return array|false
     */
    public static function getById(int $id)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT s.*, ap.title as project_title 
                FROM `{$prefix}anime_scenes` s 
                LEFT JOIN `{$prefix}anime_projects` ap ON s.project_id = ap.id 
                WHERE s.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['reference_images']) {
            $result['reference_images'] = json_decode($result['reference_images'], true);
        }
        
        return $result;
    }

    /**
     * 获取项目的场景设计列表
     *
     * @param int $projectId
     * @param array $filters
     * @return array
     */
    public static function getByProject(int $projectId, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $where = ["s.project_id = :project_id"];
        $params = [':project_id' => $projectId];

        if (!empty($filters['scene_type'])) {
            $where[] = "s.scene_type = :scene_type";
            $params[':scene_type'] = $filters['scene_type'];
        }

        if (!empty($filters['atmosphere'])) {
            $where[] = "s.atmosphere = :atmosphere";
            $params[':atmosphere'] = $filters['atmosphere'];
        }

        $sql = "SELECT s.* 
                FROM `{$prefix}anime_scenes` s 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY s.sort_order, s.created_at";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $scenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 解析JSON字段
        foreach ($scenes as &$scene) {
            if ($scene['reference_images']) {
                $scene['reference_images'] = json_decode($scene['reference_images'], true);
            }
        }

        return $scenes;
    }

    /**
     * 更新场景设计
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'scene_name', 'scene_type', 'description', 'location', 'architecture_style', 
            'atmosphere', 'time_of_day', 'weather', 'lighting', 'color_scheme', 
            'key_elements', 'props', 'background_story', 'concept_image_url', 
            'reference_images', 'usage_frequency', 'sort_order'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                if ($field === 'reference_images') {
                    $params[":{$field}"] = json_encode($data[$field]);
                } else {
                    $params[":{$field}"] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `{$prefix}anime_scenes` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除场景设计
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}anime_scenes` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 使用AI生成场景设计
     *
     * @param array $params
     * @return array|false
     */
    public static function generateWithAI(array $params): array
    {
        $prompt = self::buildScenePrompt($params);
        
        // 这里应该调用AI服务生成场景设计
        // 暂时返回模拟数据
        return [
            'scene_name' => $params['scene_name'] ?? '未命名场景',
            'scene_type' => $params['scene_type'] ?? 'indoor',
            'description' => '这是一个充满氛围感的场景，具有独特的视觉风格',
            'location' => $params['location'] ?? '未知地点',
            'architecture_style' => $params['architecture_style'] ?? '现代风格',
            'atmosphere' => $params['atmosphere'] ?? '神秘',
            'time_of_day' => $params['time_of_day'] ?? '黄昏',
            'weather' => $params['weather'] ?? '晴朗',
            'lighting' => '柔和的自然光线透过窗户洒入室内',
            'color_scheme' => '暖色调为主，搭配冷色调点缀',
            'key_elements' => '主要元素包括家具、装饰品和光线效果',
            'props' => '场景中的道具丰富多样，增强真实感',
            'background_story' => '这个场景承载着重要的故事情节',
            'concept_image_url' => null,
            'reference_images' => []
        ];
    }

    /**
     * 构建场景设计提示词
     *
     * @param array $params
     * @return string
     */
    private static function buildScenePrompt(array $params): string
    {
        $prompt = "动漫场景设计提示词：\n";
        $prompt .= "你是一位动漫场景设计师。请根据以下信息设计场景：\n";
        
        if (!empty($params['scene_name'])) {
            $prompt .= "【场景名称】：{$params['scene_name']}\n";
        }
        
        if (!empty($params['story_background'])) {
            $prompt .= "【故事背景】：{$params['story_background']}\n";
        }
        
        if (!empty($params['scene_function'])) {
            $prompt .= "【场景作用】：{$params['scene_function']}\n";
        }
        
        if (!empty($params['atmosphere'])) {
            $prompt .= "【氛围要求】：{$params['atmosphere']}\n";
        }
        
        if (!empty($params['art_style'])) {
            $prompt .= "【风格要求】：{$params['art_style']}\n";
        }

        $prompt .= "\n请生成包含以下内容的场景设计方案：
1. 场景概念图描述
   - 整体构图
   - 关键元素
   - 色彩搭配
   
2. 场景设定
   - 地理位置
   - 建筑风格
   - 植被、天气
   - 光影效果
   
3. 道具设计
   - 场景内重要道具描述
   - 道具与场景的互动
   
4. 氛围营造
   - 如何通过视觉元素传达氛围

要求：细节丰富、符合故事需求、具有视觉冲击力。";

        return $prompt;
    }

    /**
     * 获取场景统计信息
     *
     * @param int $projectId
     * @return array
     */
    public static function getSceneStats(int $projectId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_scenes,
                    COUNT(CASE WHEN scene_type = 'indoor' THEN 1 END) as indoor_scenes,
                    COUNT(CASE WHEN scene_type = 'outdoor' THEN 1 END) as outdoor_scenes,
                    COUNT(CASE WHEN scene_type = 'mixed' THEN 1 END) as mixed_scenes,
                    COUNT(CASE WHEN scene_type = 'virtual' THEN 1 END) as virtual_scenes,
                    SUM(usage_frequency) as total_usage
                FROM `{$prefix}anime_scenes` 
                WHERE project_id = :project_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 增加场景使用频率
     *
     * @param int $id
     * @return bool
     */
    public static function incrementUsage(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}anime_scenes` SET usage_frequency = usage_frequency + 1 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 获取高频使用的场景
     *
     * @param int $projectId
     * @param int $limit
     * @return array
     */
    public static function getMostUsedScenes(int $projectId, int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}anime_scenes` 
                WHERE project_id = :project_id 
                ORDER BY usage_frequency DESC, created_at DESC 
                LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}