<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 动漫企划模型
 */
class AnimeProject
{
    /**
     * 创建动漫企划
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}anime_projects` 
                (user_id, title, genre, target_audience, core_concept, episode_count, episode_duration, production_mode, has_intro_outro, intro_duration, outro_duration, main_content_duration, status, cover_image, description, tags, budget_estimate, timeline_days, team_size, ai_assistance_level) 
                VALUES (:user_id, :title, :genre, :target_audience, :core_concept, :episode_count, :episode_duration, :production_mode, :has_intro_outro, :intro_duration, :outro_duration, :main_content_duration, :status, :cover_image, :description, :tags, :budget_estimate, :timeline_days, :team_size, :ai_assistance_level)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':title' => $data['title'],
            ':genre' => $data['genre'] ?? null,
            ':target_audience' => $data['target_audience'] ?? null,
            ':core_concept' => $data['core_concept'] ?? null,
            ':episode_count' => $data['episode_count'] ?? 12,
            ':episode_duration' => $data['episode_duration'] ?? 20,
            ':production_mode' => $data['production_mode'] ?? 'long',
            ':has_intro_outro' => $data['has_intro_outro'] ?? 1,
            ':intro_duration' => $data['intro_duration'] ?? 3,
            ':outro_duration' => $data['outro_duration'] ?? 3,
            ':main_content_duration' => $data['main_content_duration'] ?? 14,
            ':status' => $data['status'] ?? 'draft',
            ':cover_image' => $data['cover_image'] ?? null,
            ':description' => $data['description'] ?? null,
            ':tags' => $data['tags'] ?? null,
            ':budget_estimate' => $data['budget_estimate'] ?? 0.00,
            ':timeline_days' => $data['timeline_days'] ?? 0,
            ':team_size' => $data['team_size'] ?? 0,
            ':ai_assistance_level' => $data['ai_assistance_level'] ?? 'partial'
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取动漫企划
     *
     * @param int $id
     * @return array|false
     */
    public static function getById(int $id)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ap.*, u.username, u.nickname, u.avatar 
                FROM `{$prefix}anime_projects` ap 
                LEFT JOIN `{$prefix}users` u ON ap.user_id = u.id 
                WHERE ap.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取动漫企划列表
     *
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getList(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $where = ["1=1"];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = "ap.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        if (!empty($filters['genre'])) {
            $where[] = "ap.genre = :genre";
            $params[':genre'] = $filters['genre'];
        }

        if (!empty($filters['status'])) {
            $where[] = "ap.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['production_mode'])) {
            $where[] = "ap.production_mode = :production_mode";
            $params[':production_mode'] = $filters['production_mode'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(ap.title LIKE :search OR ap.description LIKE :search OR ap.core_concept LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql = "SELECT ap.*, u.username, u.nickname, u.avatar 
                FROM `{$prefix}anime_projects` ap 
                LEFT JOIN `{$prefix}users` u ON ap.user_id = u.id 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY ap.created_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 使用AI生成企划
     *
     * @param array $params
     * @return array|false
     */
    public static function generateWithAI(array $params): array
    {
        $prompt = self::buildPlanningPrompt($params);
        
        // 这里应该调用AI服务生成企划
        // 暂时返回模拟数据
        return [
            'title' => $params['title'] ?? '未命名动漫企划',
            'genre' => $params['genre'] ?? '奇幻',
            'target_audience' => $params['target_audience'] ?? '青少年',
            'core_concept' => '这是一个充满想象力的奇幻冒险故事',
            'episode_count' => $params['episode_count'] ?? 12,
            'episode_duration' => $params['episode_duration'] ?? 20,
            'production_mode' => $params['production_mode'] ?? 'long',
            'description' => '基于AI生成的动漫企划描述',
            'tags' => '奇幻,冒险,成长',
            'budget_estimate' => 500000.00,
            'timeline_days' => 180,
            'team_size' => 8,
            'ai_assistance_level' => 'heavy'
        ];
    }

    /**
     * 构建企划提示词
     *
     * @param array $params
     * @return string
     */
    private static function buildPlanningPrompt(array $params): string
    {
        $prompt = "动漫企划提示词：\n";
        $prompt .= "你是一位资深动漫制片人。请根据以下信息制定动漫企划：\n";
        
        if (!empty($params['genre'])) {
            $prompt .= "【题材】：{$params['genre']}\n";
        }
        
        if (!empty($params['target_audience'])) {
            $prompt .= "【目标受众】：{$params['target_audience']}\n";
        }
        
        if (!empty($params['core_concept'])) {
            $prompt .= "【核心创意】：{$params['core_concept']}\n";
        }
        
        if (!empty($params['episode_count'])) {
            $prompt .= "【预期集数】：{$params['episode_count']}\n";
        }

        $prompt .= "\n请生成包含以下内容的企划方案：
1. 作品定位
   - 题材分类
   - 风格定位
   - 市场分析
   
2. 世界观设定
   - 世界背景
   - 基本规则
   - 独特设定
   
3. 核心卖点
   - 创新点
   - 吸引力
   - 差异化
   
4. 制作规划
   - 总体结构
   - 分集规划
   - 制作周期

要求：定位清晰、特色鲜明、可执行性强。";

        return $prompt;
    }

    /**
     * 更新动漫企划
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
            'title', 'genre', 'target_audience', 'core_concept', 'episode_count', 
            'episode_duration', 'production_mode', 'has_intro_outro', 'intro_duration', 
            'outro_duration', 'main_content_duration', 'status', 'cover_image', 
            'description', 'tags', 'budget_estimate', 'timeline_days', 'team_size', 
            'ai_assistance_level'
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

        $sql = "UPDATE `{$prefix}anime_projects` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * 删除动漫企划
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}anime_projects` WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }

    /**
     * 获取企划的完整信息（包含关联数据）
     *
     * @param int $id
     * @return array|false
     */
    public static function getFullProject(int $id)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        // 获取基本信息
        $project = self::getById($id);
        if (!$project) {
            return false;
        }

        // 获取世界观设定
        $sql = "SELECT * FROM `{$prefix}anime_world_settings` WHERE project_id = :project_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $id]);
        $project['world_settings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取角色设定
        $sql = "SELECT * FROM `{$prefix}anime_characters` WHERE project_id = :project_id ORDER BY sort_order";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $id]);
        $project['characters'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取分集脚本
        $sql = "SELECT * FROM `{$prefix}anime_episode_scripts` WHERE project_id = :project_id ORDER BY episode_number";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $id]);
        $project['episodes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取制作进度
        $sql = "SELECT * FROM `{$prefix}anime_production_progress` WHERE project_id = :project_id ORDER BY episode_number, stage";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $id]);
        $project['progress'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $project;
    }

    /**
     * 获取企划统计信息
     *
     * @param int $projectId
     * @return array
     */
    public static function getProjectStats(int $projectId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(DISTINCT ws.id) as world_settings_count,
                    COUNT(DISTINCT c.id) as characters_count,
                    COUNT(DISTINCT es.id) as episodes_count,
                    COUNT(DISTINCT pp.id) as progress_items_count,
                    COUNT(DISTINCT CASE WHEN pp.status = 'completed' THEN pp.id END) as completed_items,
                    COALESCE(AVG(pp.progress_percentage), 0) as avg_progress,
                    COUNT(DISTINCT ag.id) as ai_generations_count,
                    COALESCE(SUM(ag.cost), 0) as total_ai_cost
                FROM `{$prefix}anime_projects` ap
                LEFT JOIN `{$prefix}anime_world_settings` ws ON ap.id = ws.project_id
                LEFT JOIN `{$prefix}anime_characters` c ON ap.id = c.project_id
                LEFT JOIN `{$prefix}anime_episode_scripts` es ON ap.id = es.project_id
                LEFT JOIN `{$prefix}anime_production_progress` pp ON ap.id = pp.project_id
                LEFT JOIN `{$prefix}anime_ai_generations` ag ON ap.id = ag.project_id
                WHERE ap.id = :project_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取用户企划统计
     *
     * @param int $userId
     * @return array
     */
    public static function getUserStats(int $userId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_projects,
                    COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_count,
                    COUNT(CASE WHEN status = 'planning' THEN 1 END) as planning_count,
                    COUNT(CASE WHEN status = 'in_production' THEN 1 END) as in_production_count,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count,
                    COALESCE(SUM(budget_estimate), 0) as total_budget,
                    COALESCE(AVG(budget_estimate), 0) as avg_budget
                FROM `{$prefix}anime_projects` 
                WHERE user_id = :user_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取全局企划统计
     *
     * @return array
     */
    public static function getGlobalStats(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_projects,
                    COUNT(DISTINCT user_id) as active_creators,
                    COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_count,
                    COUNT(CASE WHEN status = 'planning' THEN 1 END) as planning_count,
                    COUNT(CASE WHEN status = 'in_production' THEN 1 END) as in_production_count,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count,
                    COUNT(CASE WHEN production_mode = 'long' THEN 1 END) as long_projects,
                    COUNT(CASE WHEN production_mode = 'short' THEN 1 END) as short_projects,
                    COALESCE(SUM(budget_estimate), 0) as total_budget,
                    COALESCE(AVG(budget_estimate), 0) as avg_budget
                FROM `{$prefix}anime_projects`";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 复制企划
     *
     * @param int $id
     * @param int $newUserId
     * @return int|false
     */
    public static function duplicate(int $id, int $newUserId): int|false
    {
        $original = self::getById($id);
        if (!$original) {
            return false;
        }

        // 复制基本企划信息
        $newData = $original;
        unset($newData['id'], $newData['username'], $newData['nickname'], $newData['avatar']);
        $newData['user_id'] = $newUserId;
        $newData['title'] = $original['title'] . ' (副本)';
        $newData['status'] = 'draft';
        
        $newProjectId = self::create($newData);
        
        if ($newProjectId) {
            // 复制关联数据（世界观、角色等）
            // 这里可以添加复制关联数据的逻辑
        }
        
        return $newProjectId;
    }
}