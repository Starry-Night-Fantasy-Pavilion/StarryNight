<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 动漫角色模型
 */
class AnimeCharacter
{
    /**
     * 创建角色
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}anime_characters` 
                (project_id, character_name, character_type, age, gender, appearance, personality, background, abilities, relationships, character_arc, voice_type, design_notes, image_url, sort_order) 
                VALUES (:project_id, :character_name, :character_type, :age, :gender, :appearance, :personality, :background, :abilities, :relationships, :character_arc, :voice_type, :design_notes, :image_url, :sort_order)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':project_id' => $data['project_id'],
            ':character_name' => $data['character_name'],
            ':character_type' => $data['character_type'] ?? 'supporting',
            ':age' => $data['age'] ?? null,
            ':gender' => $data['gender'] ?? 'unknown',
            ':appearance' => $data['appearance'] ?? null,
            ':personality' => $data['personality'] ?? null,
            ':background' => $data['background'] ?? null,
            ':abilities' => $data['abilities'] ?? null,
            ':relationships' => $data['relationships'] ?? null,
            ':character_arc' => $data['character_arc'] ?? null,
            ':voice_type' => $data['voice_type'] ?? null,
            ':design_notes' => $data['design_notes'] ?? null,
            ':image_url' => $data['image_url'] ?? null,
            ':sort_order' => $data['sort_order'] ?? 0
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取角色
     *
     * @param int $id
     * @return array|false
     */
    public static function getById(int $id)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ac.*, ap.title as project_title 
                FROM `{$prefix}anime_characters` ac 
                LEFT JOIN `{$prefix}anime_projects` ap ON ac.project_id = ap.id 
                WHERE ac.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取项目的角色列表
     *
     * @param int $projectId
     * @param array $filters
     * @return array
     */
    public static function getByProject(int $projectId, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $where = ["ac.project_id = :project_id"];
        $params = [':project_id' => $projectId];

        if (!empty($filters['character_type'])) {
            $where[] = "ac.character_type = :character_type";
            $params[':character_type'] = $filters['character_type'];
        }

        $sql = "SELECT ac.* 
                FROM `{$prefix}anime_characters` ac 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY ac.sort_order, ac.created_at";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 更新角色
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
            'character_name', 'character_type', 'age', 'gender', 'appearance', 
            'personality', 'background', 'abilities', 'relationships', 
            'character_arc', 'voice_type', 'design_notes', 'image_url', 'sort_order'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `{$prefix}anime_characters` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * 删除角色
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}anime_characters` WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }

    /**
     * 更新角色排序
     *
     * @param int $projectId
     * @param array $characterOrders 格式: [character_id => sort_order]
     * @return bool
     */
    public static function updateSortOrder(int $projectId, array $characterOrders): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}anime_characters` SET sort_order = :sort_order WHERE id = :id AND project_id = :project_id";
        $stmt = $pdo->prepare($sql);

        foreach ($characterOrders as $characterId => $sortOrder) {
            $result = $stmt->execute([
                ':id' => $characterId,
                ':sort_order' => $sortOrder,
                ':project_id' => $projectId
            ]);
            
            if (!$result) {
                return false;
            }
        }

        return true;
    }

    /**
     * 使用AI生成角色设计
     *
     * @param array $params
     * @return array|false
     */
    public static function generateWithAI(array $params): array
    {
        $prompt = self::buildCharacterPrompt($params);
        
        // 这里应该调用AI服务生成角色设计
        // 暂时返回模拟数据
        return [
            'character_name' => $params['character_name'] ?? '未命名角色',
            'role_type' => $params['role_type'] ?? 'supporting',
            'age' => $params['age'] ?? 20,
            'gender' => $params['gender'] ?? 'unknown',
            'appearance' => self::generateMockAppearance($params),
            'personality' => self::generateMockPersonality($params),
            'background' => self::generateMockBackground($params),
            'abilities' => self::generateMockAbilities($params),
            'relationships' => self::generateMockRelationships($params),
            'character_arc' => self::generateMockCharacterArc($params),
            'voice_type' => self::generateMockVoiceType($params),
            'design_notes' => self::generateMockDesignNotes($params),
            'avatar' => null,
            'sort_order' => 0
        ];
    }

    /**
     * 构建角色设计提示词
     *
     * @param array $params
     * @return string
     */
    private static function buildCharacterPrompt(array $params): string
    {
        $prompt = "动漫角色设计提示词：\n";
        $prompt .= "你是一位动漫角色设计师。请根据以下信息设计角色：\n";
        
        if (!empty($params['character_name'])) {
            $prompt .= "【角色名称】：{$params['character_name']}\n";
        }
        
        if (!empty($params['role_type'])) {
            $prompt .= "【角色定位】：{$params['role_type']}\n";
        }
        
        if (!empty($params['personality'])) {
            $prompt .= "【性格特点】：{$params['personality']}\n";
        }
        
        if (!empty($params['story_background'])) {
            $prompt .= "【故事背景】：{$params['story_background']}\n";
        }
        
        if (!empty($params['art_style'])) {
            $prompt .= "【风格要求】：{$params['art_style']}\n";
        }

        $prompt .= "\n请生成包含以下内容的角色设计方案：
1. 角色立绘描述
   - 外貌特征（发型、瞳色、服装、配饰）
   - 姿态、表情
   
2. 角色三视图描述
   - 正面、侧面、背面特征
   
3. 角色设定
   - 详细性格描述
   - 关键背景故事
   - 特殊能力或道具
   
4. 表情包建议
   - 至少3种常用表情（开心、生气、惊讶）

要求：形象鲜明、符合设定、具有辨识度。";

        return $prompt;
    }

    /**
     * 生成模拟外貌描述
     *
     * @param array $params
     * @return string
     */
    private static function generateMockAppearance(array $params): string
    {
        $roleType = $params['role_type'] ?? 'supporting';
        $gender = $params['gender'] ?? 'unknown';
        
        $appearances = [
            'protagonist' => [
                'male' => '身材修长，短发黑发，眼神坚定，穿着简约但实用的服装，给人可靠的感觉',
                'female' => '长发飘逸，眼眸明亮，穿着优雅但便于行动的服装，气质温柔而坚强',
                'unknown' => '中性外观，短发，眼神深邃，穿着时尚前卫的服装，个性十足'
            ],
            'supporting' => [
                'male' => '中等身材，发型整洁，穿着普通但干净的服装，给人亲切的感觉',
                'female' => '中等身高，长发或中发，穿着日常服装，性格温和友善',
                'unknown' => '普通身材，发型简单，穿着休闲服装，容易融入环境'
            ],
            'antagonist' => [
                'male' => '高大威猛，眼神锐利，穿着深色系服装，给人压迫感',
                'female' => '身材妖娆，眼神魅惑，穿着华丽但暗藏危险的服装',
                'unknown' => '神秘莫测，总是戴着面具或兜帽，服装遮蔽身形'
            ]
        ];
        
        return $appearances[$roleType][$gender] ?? $appearances['supporting']['unknown'];
    }

    /**
     * 生成模拟性格描述
     *
     * @param array $params
     * @return string
     */
    private static function generateMockPersonality(array $params): string
    {
        $roleType = $params['role_type'] ?? 'supporting';
        
        $personalities = [
            'protagonist' => '勇敢正直，富有正义感，面对困难从不退缩，关心朋友，有时会冲动但本性善良',
            'supporting' => '温和友善，乐于助人，虽然不是主角但总在关键时刻提供支持，有自己的原则',
            'antagonist' => '冷酷无情，为达目的不择手段，聪明但偏执，有着复杂的过去和扭曲的价值观'
        ];
        
        return $personalities[$roleType] ?? $personalities['supporting'];
    }

    /**
     * 生成模拟背景故事
     *
     * @param array $params
     * @return string
     */
    private static function generateMockBackground(array $params): string
    {
        $roleType = $params['role_type'] ?? 'supporting';
        
        $backgrounds = [
            'protagonist' => '出身普通家庭，从小立志成为英雄，经历过失去亲人的痛苦，因此更加珍惜身边的人',
            'supporting' => '来自富裕家庭，但选择追求自己的梦想，与主角是青梅竹马，一直默默支持着主角',
            'antagonist' => '曾经也是正义的伙伴，但因为一次背叛而变得冷酷，认为只有力量才能改变世界'
        ];
        
        return $backgrounds[$roleType] ?? $backgrounds['supporting'];
    }

    /**
     * 生成模拟能力描述
     *
     * @param array $params
     * @return string
     */
    private static function generateMockAbilities(array $params): string
    {
        $roleType = $params['role_type'] ?? 'supporting';
        
        $abilities = [
            'protagonist' => '拥有超强的体术和战斗直觉，在危急时刻能爆发出惊人力量，擅长鼓舞同伴',
            'supporting' => '精通治疗魔法和辅助技能，虽然战斗力不强但能大幅提升团队整体实力',
            'antagonist' => '掌握黑暗魔法和禁术，力量强大但会消耗生命力，有着多种致命技能'
        ];
        
        return $abilities[$roleType] ?? $abilities['supporting'];
    }

    /**
     * 生成模拟关系描述
     *
     * @param array $params
     * @return string
     */
    private static function generateMockRelationships(array $params): string
    {
        $roleType = $params['role_type'] ?? 'supporting';
        
        $relationships = [
            'protagonist' => '与青梅竹马有着深厚的感情，被反派视为眼中钉，受到许多人的尊敬和信赖',
            'supporting' => '是主角最信任的伙伴，与反派有过复杂的过去，在团队中起到粘合剂作用',
            'antagonist' => '曾经是主角的导师，现在与主角势不两立，手下有许多忠诚的部下'
        ];
        
        return $relationships[$roleType] ?? $relationships['supporting'];
    }

    /**
     * 生成模拟角色弧光
     *
     * @param array $params
     * @return string
     */
    private static function generateMockCharacterArc(array $params): string
    {
        $roleType = $params['role_type'] ?? 'supporting';
        
        $arcs = [
            'protagonist' => '从冲动少年成长为成熟领袖，学会承担责任，最终理解真正的强大不是力量而是守护',
            'supporting' => '从依赖他人变得独立，找到自己的价值和道路，在关键时刻做出重要选择',
            'antagonist' => '从堕入黑暗到最终救赎，认识到自己的错误，为保护重要的人而牺牲'
        ];
        
        return $arcs[$roleType] ?? $arcs['supporting'];
    }

    /**
     * 生成模拟声音类型
     *
     * @param array $params
     * @return string
     */
    private static function generateMockVoiceType(array $params): string
    {
        $roleType = $params['role_type'] ?? 'supporting';
        $gender = $params['gender'] ?? 'unknown';
        
        $voiceTypes = [
            'protagonist' => [
                'male' => '清澈有力，中音偏高，充满激情和决心',
                'female' => '温柔坚定，中音，既有女性柔美又有坚强意志',
                'unknown' => '中性清晰，音调适中，给人可靠感'
            ],
            'supporting' => [
                'male' => '温和亲切，中音，让人感到安心',
                'female' => '甜美柔和，高音，充满活力',
                'unknown' => '平和友善，音调平稳，容易亲近'
            ],
            'antagonist' => [
                'male' => '低沉有力，充满威压感，让人不寒而栗',
                'female' => '魅惑危险，中音偏低，既吸引人又暗藏杀机',
                'unknown' => '神秘莫测，音调变化多端，难以捉摸'
            ]
        ];
        
        return $voiceTypes[$roleType][$gender] ?? $voiceTypes['supporting']['unknown'];
    }

    /**
     * 生成模拟设计备注
     *
     * @param array $params
     * @return string
     */
    private static function generateMockDesignNotes(array $params): string
    {
        return '角色设计注重细节表现，服装要有层次感，表情要丰富多变，动作要流畅自然。色彩搭配要符合角色性格，整体风格要与作品世界观保持一致。';
    }

    /**
     * 获取角色统计信息
     *
     * @param int $projectId
     * @return array
     */
    public static function getCharacterStats(int $projectId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT
                    COUNT(*) as total_characters,
                    COUNT(CASE WHEN role_type = 'protagonist' THEN 1 END) as protagonist_count,
                    COUNT(CASE WHEN role_type = 'supporting' THEN 1 END) as supporting_count,
                    COUNT(CASE WHEN role_type = 'antagonist' THEN 1 END) as antagonist_count,
                    COUNT(CASE WHEN gender = 'male' THEN 1 END) as male_count,
                    COUNT(CASE WHEN gender = 'female' THEN 1 END) as female_count,
                    COUNT(CASE WHEN gender = 'unknown' THEN 1 END) as unknown_gender_count,
                    AVG(age) as avg_age
                FROM `{$prefix}anime_characters`
                WHERE project_id = :project_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 批量创建角色
     *
     * @param array $characters
     * @return array
     */
    public static function createBatch(array $characters): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $createdIds = [];
        $errors = [];
        
        $pdo->beginTransaction();
        
        try {
            foreach ($characters as $character) {
                $id = self::create($character);
                if ($id) {
                    $createdIds[] = $id;
                } else {
                    $errors[] = "创建角色失败: " . json_encode($character);
                }
            }
            
            if (empty($errors)) {
                $pdo->commit();
            } else {
                $pdo->rollBack();
            }
        } catch (\Exception $e) {
            $pdo->rollBack();
            $errors[] = "数据库错误: " . $e->getMessage();
        }
        
        return [
            'success' => empty($errors),
            'created_ids' => $createdIds,
            'errors' => $errors
        ];
    }
}