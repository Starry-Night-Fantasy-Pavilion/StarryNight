<?php

namespace app\frontend\controller;

use app\models\AnimeProject;
use app\models\AnimeCharacter;
use app\models\AnimeEpisodeScript;
use app\models\AnimeScene;
use app\models\AnimeStoryboard;
use app\models\AnimeAnimation;
use app\models\AnimeAudioProduction;
use app\models\AnimeVideoComposition;
use app\models\AnimeShortDrama;
use app\models\AnimePublication;
use app\models\AnimeAIGeneration;

/**
 * 动漫制作综合控制器
 */
class AnimeProductionController extends BaseUserController
{
    protected $currentPage = 'anime_production';
    
    /**
     * 动漫制作首页 - 创作中心
     */
    public function index()
    {
        try {
            $userId = $this->checkAuth();
            $user = $this->getCurrentUser();

            // 获取用户最近的项目（最多2个）
            $allProjects = AnimeProject::getList(['user_id' => $userId], 100, 0);
            $recentProjects = array_slice($allProjects, 0, 2);

            $this->render('anime/project/center', [
                'title' => '动漫 / 短剧创作中心',
                'projects' => $recentProjects,
            ]);
        } catch (\Exception $e) {
            error_log('动漫制作页面错误: ' . $e->getMessage());
            \app\services\ErrorHandler::handleServerError($e);
        }
    }
    
    /**
     * 我的动漫项目列表
     */
    public function projectList()
    {
        try {
            $userId = $this->checkAuth();
            $user = $this->getCurrentUser();

            $filters = [];
            if (isset($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            if (isset($_GET['genre'])) {
                $filters['genre'] = $_GET['genre'];
            }
            if (isset($_GET['production_mode'])) {
                $filters['production_mode'] = $_GET['production_mode'];
            }

            $projects = AnimeProject::getList(['user_id' => $userId] + $filters, 100, 0);

            $this->render('anime/project/index', [
                'title' => '我的动漫项目',
                'projects' => $projects,
            ]);
        } catch (\Exception $e) {
            error_log('动漫项目列表页面错误: ' . $e->getMessage());
            \app\services\ErrorHandler::handleServerError($e);
        }
    }

    /**
     * 返回JSON错误响应
     * @param string $message 错误消息
     * @param int $code HTTP状态码
     */
    private function error(string $message, int $code = 400)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 返回JSON成功响应
     * @param array $data 响应数据
     * @param string $message 成功消息
     */
    private function success(array $data = [], string $message = '操作成功')
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => $data,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 获取项目仪表板数据
     */
    public function getDashboard()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $userId = $this->checkAuth();
            if (!$userId) {
                throw new \Exception('请先登录');
            }

            // 获取用户的项目统计
            $projects = AnimeProject::getList(['user_id' => $userId], 10, 0);
            $projectStats = $this->calculateProjectStats($projects);
            
            // 获取最近的AI生成记录
            $recentGenerations = AnimeAIGeneration::getByUser($userId, [], 5, 0);
            
            // 获取进行中的任务
            $ongoingTasks = $this->getOngoingTasks($userId);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'project_stats' => $projectStats,
                    'recent_projects' => array_slice($projects, 0, 5),
                    'recent_generations' => $recentGenerations,
                    'ongoing_tasks' => $ongoingTasks,
                    'quick_actions' => [
                        'create_project' => '/anime-production/create-project',
                        'create_short_drama' => '/anime-production/create-short-drama',
                        'ai_assistant' => '/anime-production/ai-assistant',
                        'templates' => '/anime-production/templates'
                    ]
                ],
                'message' => '获取仪表板数据成功'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            $this->error('获取仪表板数据失败: ' . $e->getMessage());
        }
    }

    /**
     * 创建新项目
     */
    public function createProject()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $userId = $this->checkAuth();
            if (!$userId) {
                throw new \Exception('请先登录');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new \Exception('无效的请求数据');
            }
            
            // 验证必填字段
            $requiredFields = ['title', 'production_mode'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("字段 {$field} 不能为空");
                }
            }
            
            $data['user_id'] = $userId;
            
            $projectId = AnimeProject::create($data);
            if (!$projectId) {
                throw new \Exception('创建项目失败');
            }
            
            // 如果需要AI辅助生成企划
            if (!empty($data['use_ai_assistance']) && $data['use_ai_assistance']) {
                $aiResult = AnimeProject::generateWithAI($data);
                if ($aiResult) {
                    AnimeProject::update($projectId, $aiResult);
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'project_id' => $projectId,
                    'message' => '项目创建成功'
                ],
                'message' => '创建项目成功'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            $this->error('创建项目失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取项目详情
     */
    public function getProjectDetail($projectId)
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $userId = $this->checkAuth();
            if (!$userId) {
                throw new \Exception('请先登录');
            }
            
            $project = AnimeProject::getFullProject(intval($projectId));
            if (!$project) {
                throw new \Exception('项目不存在');
            }
            
            // 检查权限
            if ($project['user_id'] != $userId) {
                throw new \Exception('无权限访问此项目');
            }
            
            // 获取项目的完整数据
            $projectData = $this->getFullProjectData($project);
            
            echo json_encode([
                'success' => true,
                'data' => $projectData,
                'message' => '获取项目详情成功'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            $this->error('获取项目详情失败: ' . $e->getMessage());
        }
    }

    /**
     * AI生成角色
     */
    public function generateCharacter()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $userId = $this->checkAuth();
            if (!$userId) {
                throw new \Exception('请先登录');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new \Exception('无效的请求数据');
            }
            
            // 验证必填字段
            $requiredFields = ['project_id', 'character_name'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("字段 {$field} 不能为空");
                }
            }
            
            // 检查项目权限
            $project = AnimeProject::getById($data['project_id']);
            if (!$project || $project['user_id'] != $userId) {
                throw new \Exception('无权限访问此项目');
            }
            
            // AI生成角色
            $aiResult = AnimeCharacter::generateWithAI($data);
            if ($aiResult) {
                $aiResult['project_id'] = $data['project_id'];
                $characterId = AnimeCharacter::create($aiResult);
                
                if ($characterId) {
                    // 记录AI生成历史
                    AnimeAIGeneration::create([
                        'user_id' => $userId,
                        'project_id' => $data['project_id'],
                        'generation_type' => 'character',
                        'prompt' => $data['prompt'] ?? '角色生成',
                        'result' => json_encode($aiResult),
                        'ai_model' => $data['ai_model'] ?? 'gpt-4',
                        'status' => 'completed'
                    ]);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'character_id' => $characterId,
                            'character_data' => $aiResult
                        ],
                        'message' => '角色生成成功'
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    throw new \Exception('保存角色失败');
                }
            } else {
                throw new \Exception('AI生成角色失败');
            }
            
        } catch (\Exception $e) {
            $this->error('生成角色失败: ' . $e->getMessage());
        }
    }

    /**
     * AI生成脚本
     */
    public function generateScript()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $userId = $this->checkAuth();
            if (!$userId) {
                throw new \Exception('请先登录');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new \Exception('无效的请求数据');
            }
            
            // 验证必填字段
            $requiredFields = ['project_id', 'episode_number'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("字段 {$field} 不能为空");
                }
            }
            
            // 检查项目权限
            $project = AnimeProject::getById($data['project_id']);
            if (!$project || $project['user_id'] != $userId) {
                throw new \Exception('无权限访问此项目');
            }
            
            // AI生成脚本
            $aiResult = AnimeEpisodeScript::generateWithAI($data);
            if ($aiResult) {
                $aiResult['project_id'] = $data['project_id'];
                $scriptId = AnimeEpisodeScript::create($aiResult);
                
                if ($scriptId) {
                    // 记录AI生成历史
                    AnimeAIGeneration::create([
                        'user_id' => $userId,
                        'project_id' => $data['project_id'],
                        'generation_type' => 'script',
                        'prompt' => $data['prompt'] ?? '脚本生成',
                        'result' => json_encode($aiResult),
                        'ai_model' => $data['ai_model'] ?? 'gpt-4',
                        'status' => 'completed'
                    ]);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'script_id' => $scriptId,
                            'script_data' => $aiResult
                        ],
                        'message' => '脚本生成成功'
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    throw new \Exception('保存脚本失败');
                }
            } else {
                throw new \Exception('AI生成脚本失败');
            }
            
        } catch (\Exception $e) {
            $this->error('生成脚本失败: ' . $e->getMessage());
        }
    }

    /**
     * AI生成短剧
     */
    public function generateShortDrama()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $userId = $this->checkAuth();
            if (!$userId) {
                throw new \Exception('请先登录');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new \Exception('无效的请求数据');
            }
            
            // 验证必填字段
            $requiredFields = ['project_id', 'title', 'drama_style'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("字段 {$field} 不能为空");
                }
            }
            
            // 检查项目权限
            $project = AnimeProject::getById($data['project_id']);
            if (!$project || $project['user_id'] != $userId) {
                throw new \Exception('无权限访问此项目');
            }
            
            // AI生成短剧
            $aiResult = AnimeShortDrama::generateWithAI($data);
            if ($aiResult) {
                $aiResult['project_id'] = $data['project_id'];
                $dramaId = AnimeShortDrama::create($aiResult);
                
                if ($dramaId) {
                    // 记录AI生成历史
                    AnimeAIGeneration::create([
                        'user_id' => $userId,
                        'project_id' => $data['project_id'],
                        'generation_type' => 'short_drama',
                        'prompt' => $data['prompt'] ?? '短剧生成',
                        'result' => json_encode($aiResult),
                        'ai_model' => $data['ai_model'] ?? 'sora2',
                        'status' => 'completed'
                    ]);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'drama_id' => $dramaId,
                            'drama_data' => $aiResult
                        ],
                        'message' => '短剧生成成功'
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    throw new \Exception('保存短剧失败');
                }
            } else {
                throw new \Exception('AI生成短剧失败');
            }
            
        } catch (\Exception $e) {
            $this->error('生成短剧失败: ' . $e->getMessage());
        }
    }

    /**
     * 发布视频
     */
    public function publishVideo()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $userId = $this->checkAuth();
            if (!$userId) {
                throw new \Exception('请先登录');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new \Exception('无效的请求数据');
            }
            
            // 验证必填字段
            $requiredFields = ['video_composition_id', 'platform'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("字段 {$field} 不能为空");
                }
            }
            
            // 检查视频权限
            $videoComposition = AnimeVideoComposition::getById($data['video_composition_id']);
            if (!$videoComposition) {
                throw new \Exception('视频不存在');
            }
            
            $project = AnimeProject::getById($videoComposition['project_id']);
            if (!$project || $project['user_id'] != $userId) {
                throw new \Exception('无权限访问此视频');
            }
            
            // 发布视频
            $publishResult = AnimePublication::publishToPlatform(
                $data['video_composition_id'],
                $data['platform'],
                $data
            );
            
            echo json_encode([
                'success' => $publishResult['success'],
                'data' => $publishResult,
                'message' => $publishResult['success'] ? '视频发布成功' : '视频发布失败'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            $this->error('发布视频失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取AI生成历史
     */
    public function getGenerationHistory()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $userId = $this->checkAuth();
            if (!$userId) {
                throw new \Exception('请先登录');
            }
            
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = min(max(intval($_GET['limit'] ?? 20), 1), 100);
            $offset = ($page - 1) * $limit;
            
            $filters = [];
            if (!empty($_GET['generation_type'])) {
                $filters['generation_type'] = $_GET['generation_type'];
            }
            if (!empty($_GET['project_id'])) {
                $filters['project_id'] = $_GET['project_id'];
            }
            
            $generations = AnimeAIGeneration::getByUser($userId, $filters, $limit, $offset);
            $totalGenerations = count(AnimeAIGeneration::getByUser($userId, $filters, 1000, 0));
            $totalPages = ceil($totalGenerations / $limit);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'generations' => $generations,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total_items' => $totalGenerations,
                        'items_per_page' => $limit
                    ]
                ],
                'message' => '获取生成历史成功'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            $this->error('获取生成历史失败: ' . $e->getMessage());
        }
    }

    /**
     * 计算项目统计
     */
    private function calculateProjectStats(array $projects): array
    {
        $stats = [
            'total_projects' => count($projects),
            'completed_projects' => 0,
            'in_progress_projects' => 0,
            'planning_projects' => 0,
            'total_episodes' => 0,
            'completed_episodes' => 0,
            'total_characters' => 0,
            'recent_activity' => []
        ];
        
        foreach ($projects as $project) {
            switch ($project['status']) {
                case 'completed':
                    $stats['completed_projects']++;
                    break;
                case 'in_production':
                    $stats['in_progress_projects']++;
                    break;
                case 'planning':
                    $stats['planning_projects']++;
                    break;
            }
            
            $stats['total_episodes'] += $project['target_episodes'] ?? 0;
            $stats['completed_episodes'] += $project['completed_episodes'] ?? 0;
        }
        
        // 获取最近活动
        $recentProjects = array_slice($projects, 0, 5);
        foreach ($recentProjects as $project) {
            $stats['recent_activity'][] = [
                'project_id' => $project['id'],
                'title' => $project['title'],
                'status' => $project['status'],
                'updated_at' => $project['updated_at']
            ];
        }
        
        return $stats;
    }

    /**
     * 获取进行中的任务
     */
    private function getOngoingTasks(int $userId): array
    {
        // 这里应该从各个模型中获取进行中的任务
        // 暂时返回模拟数据
        return [
            [
                'type' => 'script_generation',
                'title' => '第3集脚本生成',
                'progress' => 75,
                'project_id' => 1,
                'project_title' => '我的动漫项目'
            ],
            [
                'type' => 'character_design',
                'title' => '主角角色设计',
                'progress' => 45,
                'project_id' => 1,
                'project_title' => '我的动漫项目'
            ]
        ];
    }

    /**
     * 获取完整项目数据
     */
    private function getFullProjectData(array $project): array
    {
        $projectId = $project['id'];
        
        return [
            'project_info' => $project,
            'characters' => AnimeCharacter::getByProject($projectId),
            'scripts' => AnimeEpisodeScript::getByProject($projectId),
            'scenes' => AnimeScene::getByProject($projectId),
            'storyboards' => AnimeStoryboard::getByProject($projectId),
            'animations' => AnimeAnimation::getByProject($projectId),
            'audio_productions' => AnimeAudioProduction::getByProject($projectId),
            'video_compositions' => AnimeVideoComposition::getByProject($projectId),
            'short_dramas' => AnimeShortDrama::getByProject($projectId),
            'publications' => AnimePublication::getByProject($projectId),
            'stats' => [
                'character_stats' => AnimeCharacter::getCharacterStats($projectId),
                'script_stats' => AnimeEpisodeScript::getProjectStats($projectId),
                'scene_stats' => AnimeScene::getSceneStats($projectId),
                'animation_stats' => AnimeAnimation::getAnimationStats($projectId),
                'audio_stats' => AnimeAudioProduction::getAudioStats($projectId),
                'video_stats' => AnimeVideoComposition::getVideoStats($projectId),
                'publication_stats' => AnimePublication::getPublicationStats($projectId)
            ]
        ];
    }
}