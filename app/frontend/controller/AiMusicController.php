<?php

namespace app\frontend\controller;

use app\models\AiMusicProject;
use app\models\AiMusicLyrics;
use app\models\AiMusicTrack;
use app\models\AiMusicMixMaster;
use app\models\AiMusicExport;
use app\models\User;

class AiMusicController
{
    private $projectModel;
    private $lyricsModel;
    private $trackModel;
    private $mixMasterModel;
    private $exportModel;

    public function __construct()
    {
        $this->projectModel = new AiMusicProject();
        $this->lyricsModel = new AiMusicLyrics();
        $this->trackModel = new AiMusicTrack();
        $this->mixMasterModel = new AiMusicMixMaster();
        $this->exportModel = new AiMusicExport();
    }

    /**
     * 检查用户登录状态
     */
    private function checkAuth()
    {
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            $redirectUrl = $_SERVER['REQUEST_URI'] ?? '/ai_music';
            header('Location: /login?redirect=' . urlencode($redirectUrl));
            exit;
        }
        return $_SESSION['user_id'];
    }

    /**
     * 获取当前用户ID
     */
    private function getCurrentUserId(): int
    {
        return $this->checkAuth();
    }

    /**
     * 返回JSON响应
     */
    private function jsonResponse($data, int $statusCode = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * AI音乐首页
     */
    public function index()
    {
        try {
            header('Content-Type: text/html; charset=utf-8');
            
            $siteName = (string) get_env('APP_NAME', '星夜阁');
            $userId = $this->getCurrentUserId();
            
            // 使用主题系统渲染页面
            $themeManager = new \app\services\ThemeManager();
            $theme = $themeManager->loadActiveThemeInstance();
            
            if ($theme) {
                $content = $theme->renderTemplate('ai_music', [
                    'site_name' => $siteName,
                    'user_id' => $userId,
                ]);
                
                echo $theme->renderTemplate('layout', [
                    'title' => 'AI音乐工坊 - ' . $siteName,
                    'site_name' => $siteName,
                    'page_class' => 'page-ai-music',
                    'current_page' => 'ai_music',
                    'content' => $content,
                ]);
            } else {
                // 如果没有主题，使用简单视图
                echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>AI音乐工坊</title></head><body><h1>AI音乐工坊</h1><p>功能开发中...</p></body></html>';
            }
        } catch (\Exception $e) {
            error_log('AI音乐页面错误: ' . $e->getMessage());
            \app\services\ErrorHandler::handleServerError($e);
        }
    }

    /**
     * 验证请求参数
     */
    private function validateRequired(array $data, array $required): array
    {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->jsonResponse([
                'success' => false,
                'error' => '缺少必需参数: ' . implode(', ', $missing)
            ], 400);
        }
        
        return $data;
    }

    /**
     * 创建音乐项目
     */
    public function createProject(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $data = $this->validateRequired($data, ['title']);
        
        $data['user_id'] = $this->getCurrentUserId();
        
        if ($this->projectModel->create($data)) {
            $projectId = $this->getDb()->lastInsertId();
            $project = $this->projectModel->getById($projectId);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $project
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => '创建项目失败'
            ], 500);
        }
    }

    /**
     * 获取音乐项目列表
     */
    public function getProjects(): void
    {
        $userId = $this->getCurrentUserId();
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        $status = $_GET['status'] ?? null;
        
        $projects = $this->projectModel->getByUserId($userId, $page, $limit, $status);
        $totalCount = $this->projectModel->getUserTotalCount($userId, $status);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $projects,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalCount,
                'pages' => ceil($totalCount / $limit)
            ]
        ]);
    }

    /**
     * 获取音乐项目详情
     */
    public function getProject(int $id): void
    {
        $project = $this->projectModel->getById($id);
        
        if (!$project) {
            $this->jsonResponse([
                'success' => false,
                'error' => '项目不存在'
            ], 404);
        }
        
        // 检查权限
        if ($project['user_id'] !== $this->getCurrentUserId() && !$project['is_public']) {
            $this->jsonResponse([
                'success' => false,
                'error' => '无权访问此项目'
            ], 403);
        }
        
        // 获取项目统计信息
        $stats = $this->projectModel->getProjectStats($id);
        
        // 获取相关数据
        $lyrics = $this->lyricsModel->getByProjectId($id);
        $tracks = $this->trackModel->getByProjectId($id);
        $mixes = $this->mixMasterModel->getByProjectId($id);
        $exports = $this->exportModel->getByProjectId($id);
        
        $this->jsonResponse([
            'success' => true,
            'data' => [
                'project' => $project,
                'stats' => $stats,
                'lyrics' => $lyrics,
                'tracks' => $tracks,
                'mixes' => $mixes,
                'exports' => $exports
            ]
        ]);
    }

    /**
     * 更新音乐项目
     */
    public function updateProject(int $id): void
    {
        $project = $this->projectModel->getById($id);
        
        if (!$project) {
            $this->jsonResponse([
                'success' => false,
                'error' => '项目不存在'
            ], 404);
        }
        
        if ($project['user_id'] !== $this->getCurrentUserId()) {
            $this->jsonResponse([
                'success' => false,
                'error' => '无权修改此项目'
            ], 403);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($this->projectModel->update($id, $data)) {
            $updatedProject = $this->projectModel->getById($id);
            $this->jsonResponse([
                'success' => true,
                'data' => $updatedProject
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => '更新项目失败'
            ], 500);
        }
    }

    /**
     * 删除音乐项目
     */
    public function deleteProject(int $id): void
    {
        $project = $this->projectModel->getById($id);
        
        if (!$project) {
            $this->jsonResponse([
                'success' => false,
                'error' => '项目不存在'
            ], 404);
        }
        
        if ($project['user_id'] !== $this->getCurrentUserId()) {
            $this->jsonResponse([
                'success' => false,
                'error' => '无权删除此项目'
            ], 403);
        }
        
        if ($this->projectModel->delete($id)) {
            $this->jsonResponse([
                'success' => true,
                'message' => '项目删除成功'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => '删除项目失败'
            ], 500);
        }
    }

    /**
     * 创建歌词
     */
    public function createLyrics(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $data = $this->validateRequired($data, ['project_id', 'content']);
        
        // 检查项目权限
        $project = $this->projectModel->getById($data['project_id']);
        if (!$project || $project['user_id'] !== $this->getCurrentUserId()) {
            $this->jsonResponse([
                'success' => false,
                'error' => '无权访问此项目'
            ], 403);
        }
        
        // 分析歌词
        if (!isset($data['emotion_analysis'])) {
            $data['emotion_analysis'] = $this->lyricsModel->analyzeEmotion($data['content']);
        }
        
        if (!isset($data['structure'])) {
            $data['structure'] = $this->lyricsModel->analyzeStructure($data['content']);
        }
        
        if (!isset($data['rhyme_scheme'])) {
            $data['rhyme_scheme'] = $this->lyricsModel->analyzeRhyme($data['content']);
        }
        
        if (!isset($data['syllable_count'])) {
            $data['syllable_count'] = $this->lyricsModel->countSyllables($data['content']);
        }
        
        if ($this->lyricsModel->create($data)) {
            $lyricsId = $this->getDb()->lastInsertId();
            $lyrics = $this->lyricsModel->getById($lyricsId);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $lyrics
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => '创建歌词失败'
            ], 500);
        }
    }

    /**
     * AI生成歌词
     */
    public function generateLyrics(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $data = $this->validateRequired($data, ['project_id']);
        
        // 检查项目权限
        $project = $this->projectModel->getById($data['project_id']);
        if (!$project || $project['user_id'] !== $this->getCurrentUserId()) {
            $this->jsonResponse([
                'success' => false,
                'error' => '无权访问此项目'
            ], 403);
        }
        
        $generationParams = [
            'theme' => $data['theme'] ?? $project['title'],
            'emotion' => $data['emotion'] ?? 'happy',
            'style' => $data['style'] ?? $project['genre'] ?? 'pop',
            'word_count' => $data['word_count'] ?? 200
        ];
        
        $generatedLyrics = $this->lyricsModel->generateLyrics($generationParams);
        
        if ($generatedLyrics) {
            $lyricsData = [
                'project_id' => $data['project_id'],
                'content' => $generatedLyrics['content'],
                'emotion_analysis' => $generatedLyrics['emotion_analysis'],
                'structure' => $generatedLyrics['structure'],
                'rhyme_scheme' => $generatedLyrics['rhyme_scheme'],
                'syllable_count' => $generatedLyrics['syllable_count'],
                'is_ai_generated' => 1,
                'generation_prompt' => $generatedLyrics['generation_prompt']
            ];
            
            if ($this->lyricsModel->create($lyricsData)) {
                $lyricsId = $this->getDb()->lastInsertId();
                $lyrics = $this->lyricsModel->getById($lyricsId);
                
                $this->jsonResponse([
                    'success' => true,
                    'data' => $lyrics
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => '保存生成的歌词失败'
                ], 500);
            }
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'AI生成歌词失败'
            ], 500);
        }
    }

    /**
     * 创建音轨
     */
    public function createTrack(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $data = $this->validateRequired($data, ['project_id', 'name', 'type']);
        
        // 检查项目权限
        $project = $this->projectModel->getById($data['project_id']);
        if (!$project || $project['user_id'] !== $this->getCurrentUserId()) {
            $this->jsonResponse([
                'success' => false,
                'error' => '无权访问此项目'
            ], 403);
        }
        
        // 设置位置
        if (!isset($data['position'])) {
            $data['position'] = $this->trackModel->getNextPosition($data['project_id']);
        }
        
        if ($this->trackModel->create($data)) {
            $trackId = $this->getDb()->lastInsertId();
            $track = $this->trackModel->getById($trackId);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $track
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => '创建音轨失败'
            ], 500);
        }
    }

    /**
     * 更新音轨
     */
    public function updateTrack(int $id): void
    {
        $track = $this->trackModel->getById($id);
        
        if (!$track) {
            $this->jsonResponse([
                'success' => false,
                'error' => '音轨不存在'
            ], 404);
        }
        
        // 检查项目权限
        $project = $this->projectModel->getById($track['project_id']);
        if (!$project || $project['user_id'] !== $this->getCurrentUserId()) {
            $this->jsonResponse([
                'success' => false,
                'error' => '无权修改此音轨'
            ], 403);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($this->trackModel->update($id, $data)) {
            $updatedTrack = $this->trackModel->getById($id);
            $this->jsonResponse([
                'success' => true,
                'data' => $updatedTrack
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => '更新音轨失败'
            ], 500);
        }
    }

    /**
     * 删除音轨
     */
    public function deleteTrack(int $id): void
    {
        $track = $this->trackModel->getById($id);
        
        if (!$track) {
            $this->jsonResponse([
                'success' => false,
                'error' => '音轨不存在'
            ], 404);
        }
        
        // 检查项目权限
        $project = $this->projectModel->getById($track['project_id']);
        if (!$project || $project['user_id'] !== $this->getCurrentUserId()) {
            $this->jsonResponse([
                'success' => false,
                'error' => '无权删除此音轨'
            ], 403);
        }
        
        if ($this->trackModel->delete($id)) {
            $this->jsonResponse([
                'success' => true,
                'message' => '音轨删除成功'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => '删除音轨失败'
            ], 500);
        }
    }

    /**
     * AI音轨分离
     */
    public function separateTracks(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $data = $this->validateRequired($data, ['project_id', 'source_audio_url']);
        
        // 检查项目权限
        $project = $this->projectModel->getById($data['project_id']);
        if (!$project || $project['user_id'] !== $this->getCurrentUserId()) {
            $this->jsonResponse([
                'success' => false,
                'error' => '无权访问此项目'
            ], 403);
        }
        
        $separatedTracks = $this->trackModel->separateTracks($data['project_id'], $data['source_audio_url']);
        
        if (!empty($separatedTracks)) {
            $this->jsonResponse([
                'success' => true,
                'data' => $separatedTracks,
                'message' => '音轨分离成功'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => '音轨分离失败'
            ], 500);
        }
    }

    /**
     * AI自动混音
     */
    public function autoMix(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $data = $this->validateRequired($data, ['project_id']);
        
        // 检查项目权限
        $project = $this->projectModel->getById($data['project_id']);
        if (!$project || $project['user_id'] !== $this->getCurrentUserId()) {
            $this->jsonResponse([
                'success' => false,
                'error' => '无权访问此项目'
            ], 403);
        }
        
        $mixParams = $data['params'] ?? [];
        $result = $this->mixMasterModel->autoMix($data['project_id'], $mixParams);
        
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result,
                'message' => 'AI混音成功'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => $result['error'] ?? 'AI混音失败'
            ], 500);
        }
    }

    /**
     * AI自动母带
     */
    public function autoMaster(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $data = $this->validateRequired($data, ['project_id']);
        
        // 检查项目权限
        $project = $this->projectModel->getById($data['project_id']);
        if (!$project || $project['user_id'] !== $this->getCurrentUserId()) {
            $this->jsonResponse([
                'success' => false,
                'error' => '无权访问此项目'
            ], 403);
        }
        
        $masterParams = $data['params'] ?? [];
        $result = $this->mixMasterModel->autoMaster($data['project_id'], $masterParams);
        
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result,
                'message' => 'AI母带处理成功'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => $result['error'] ?? 'AI母带处理失败'
            ], 500);
        }
    }

    /**
     * 导出音频
     */
    public function exportAudio(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $data = $this->validateRequired($data, ['project_id', 'format', 'quality']);
        
        // 检查项目权限
        $project = $this->projectModel->getById($data['project_id']);
        if (!$project || $project['user_id'] !== $this->getCurrentUserId()) {
            $this->jsonResponse([
                'success' => false,
                'error' => '无权访问此项目'
            ], 403);
        }
        
        $result = $this->exportModel->exportAudio($data['project_id'], $data);
        
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result,
                'message' => '音频导出成功'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => $result['error'] ?? '音频导出失败'
            ], 500);
        }
    }

    /**
     * 获取支持的导出格式
     */
    public function getExportFormats(): void
    {
        $formats = $this->exportModel->getSupportedFormats();
        
        $this->jsonResponse([
            'success' => true,
            'data' => $formats
        ]);
    }

    /**
     * 获取用户项目统计
     */
    public function getUserStats(): void
    {
        $userId = $this->getCurrentUserId();
        $stats = $this->projectModel->getUserProjectStats($userId);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * 搜索公开项目
     */
    public function searchPublicProjects(): void
    {
        $keyword = $_GET['keyword'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        
        if (empty($keyword)) {
            $this->jsonResponse([
                'success' => false,
                'error' => '搜索关键词不能为空'
            ], 400);
        }
        
        $projects = $this->projectModel->search($keyword, $page, $limit);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $projects
        ]);
    }

    /**
     * 获取热门项目
     */
    public function getPopularProjects(): void
    {
        $limit = (int)($_GET['limit'] ?? 10);
        $genre = $_GET['genre'] ?? null;
        
        $projects = $this->projectModel->getPopularProjects($limit, $genre);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $projects
        ]);
    }

    /**
     * 获取最新项目
     */
    public function getLatestProjects(): void
    {
        $limit = (int)($_GET['limit'] ?? 10);
        $genre = $_GET['genre'] ?? null;
        
        $projects = $this->projectModel->getLatestProjects($limit, $genre);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $projects
        ]);
    }

    /**
     * 获取数据库连接
     */
    private function getDb()
    {
        return \app\services\Database::pdo();
    }
}