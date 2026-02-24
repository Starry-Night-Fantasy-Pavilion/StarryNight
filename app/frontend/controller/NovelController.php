<?php

namespace app\frontend\controller;

use app\models\Novel;
use app\models\NovelChapter;
use app\models\NovelOutline;
use app\models\NovelCharacter;
use app\services\NovelAIService;

class NovelController extends BaseUserController
{
    protected $currentPage = 'novel';

    /**
     * 渲染用户中心内的小说视图
     *
     * 注意：必须保持与 BaseUserController 中 render 的可见性一致或更宽松
     * 否则会触发「Access level to ...::render() must be protected or weaker」致命错误
     */
    protected function render(string $view, array $vars = []): void
    {
        $userId = $this->checkAuth();
        $user = $this->getCurrentUser();
        
        $title = $vars['title'] ?? '我的小说';
        ob_start();
        extract($vars);
        require __DIR__ . '/../views/novel/' . $view . '.php';
        $content = ob_get_clean();
        
        // 使用用户中心布局
        $currentPage = $this->currentPage;
        include __DIR__ . '/../views/user_center_layout.php';
        exit;
    }

    /**
     * 小说列表
     */
    public function index()
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

        $novels = Novel::findByUser((int)$userId, $filters);

        $this->render('index', [
            'title' => '我的小说',
            'novels' => $novels,
        ]);
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in NovelController::index: ' . $e->getMessage());
            
            // 返回友好的错误信息
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                return json_encode([
                    'success' => false,
                    'message' => '操作失败，请稍后重试',
                    'error' => DEBUG ? $e->getMessage() : '系统内部错误'
                ]);
            } else {
                // 对于普通请求，显示错误页面
                http_response_code(500);
                echo '<h1>系统错误</h1><p>抱歉，系统遇到了一些问题，请稍后重试。</p>';
                if (DEBUG) {
                    echo '<p>错误详情: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                exit;
            }
        }
    }

    /**
     * 创建新小说
     */
    public function create()
    {
        try {
        $userId = $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $novelId = Novel::create([
                'user_id' => (int)$userId,
                'title' => $_POST['title'] ?? '',
                'genre' => $_POST['genre'] ?? null,
                'type' => $_POST['type'] ?? null,
                'theme' => $_POST['theme'] ?? null,
                'target_words' => (int)($_POST['target_words'] ?? 0),
                'description' => $_POST['description'] ?? null,
                'tags' => $_POST['tags'] ?? null,
            ]);

            header('Location: /novel/' . $novelId . '/editor');
            exit;
        }

        $this->render('create', [
            'title' => '创建新小说',
        ]);
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in NovelController::create: ' . $e->getMessage());
            
            // 返回友好的错误信息
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                return json_encode([
                    'success' => false,
                    'message' => '操作失败，请稍后重试',
                    'error' => DEBUG ? $e->getMessage() : '系统内部错误'
                ]);
            } else {
                // 对于普通请求，显示错误页面
                http_response_code(500);
                echo '<h1>系统错误</h1><p>抱歉，系统遇到了一些问题，请稍后重试。</p>';
                if (DEBUG) {
                    echo '<p>错误详情: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                exit;
            }
        }
    }

    /**
     * 编辑器页面
     */
    public function editor($novelId)
    {
        $userId = $this->checkAuth();

        $novel = Novel::find((int)$novelId);
        if (!$novel || $novel['user_id'] != $userId) {
            header('Location: /novel');
            exit;
        }

        $chapters = NovelChapter::findByNovel((int)$novelId);
        $characters = NovelCharacter::findByNovel((int)$novelId);
        $outlines = NovelOutline::findByNovel((int)$novelId);

        $this->render('editor', [
            'title' => $novel['title'] . ' - 编辑器',
            'novel' => $novel,
            'chapters' => $chapters,
            'characters' => $characters,
            'outlines' => $outlines,
        ]);
    }

    /**
     * AI续写
     */
    public function aiContinue()
    {
        header('Content-Type: application/json');
        
        $userId = $this->checkAuth();

        $novelId = (int)($_POST['novel_id'] ?? 0);
        $chapterId = (int)($_POST['chapter_id'] ?? 0);
        $context = $_POST['context'] ?? '';
        $wordCount = (int)($_POST['word_count'] ?? 500);

        $novel = Novel::find($novelId);
        if (!$novel || $novel['user_id'] != $userId) {
            echo json_encode(['success' => false, 'error' => '小说不存在']);
            exit;
        }

        // 获取角色信息
        $characters = NovelCharacter::findByNovel($novelId);
        $charactersText = '';
        foreach ($characters as $char) {
            $charactersText .= $char['name'] . '：' . ($char['personality'] ?? '') . "\n";
        }

        $result = NovelAIService::continueWriting([
            'context' => $context,
            'characters' => $charactersText,
            'plot_requirements' => $novel['theme'] ?? '',
            'style' => '',
            'word_count' => $wordCount,
        ]);

        echo json_encode($result);
    }

    /**
     * AI改写
     */
    public function aiRewrite()
    {
        header('Content-Type: application/json');
        
        $userId = $this->checkAuth();

        $content = $_POST['content'] ?? '';
        $requirements = $_POST['requirements'] ?? '';

        $result = NovelAIService::rewrite([
            'content' => $content,
            'requirements' => $requirements,
        ]);

        echo json_encode($result);
    }

    /**
     * AI扩写
     */
    public function aiExpand()
    {
        header('Content-Type: application/json');
        
        $userId = $this->checkAuth();

        $content = $_POST['content'] ?? '';
        $targetWords = (int)($_POST['target_words'] ?? 1000);
        $direction = $_POST['direction'] ?? '';

        $result = NovelAIService::expand([
            'content' => $content,
            'target_words' => $targetWords,
            'direction' => $direction,
        ]);

        echo json_encode($result);
    }

    /**
     * AI润色
     */
    public function aiPolish()
    {
        header('Content-Type: application/json');
        
        $userId = $this->checkAuth();

        $content = $_POST['content'] ?? '';
        $style = $_POST['style'] ?? '';

        $result = NovelAIService::polish([
            'content' => $content,
            'style' => $style,
        ]);

        echo json_encode($result);
    }

    /**
     * 生成大纲
     */
    public function generateOutline()
    {
        header('Content-Type: application/json');
        
        $userId = $this->checkAuth();

        $novelId = (int)($_POST['novel_id'] ?? 0);
        $outlineType = $_POST['outline_type'] ?? 'chapter';

        $novel = Novel::find($novelId);
        if (!$novel || $novel['user_id'] != $userId) {
            echo json_encode(['success' => false, 'error' => '小说不存在']);
            exit;
        }

        $result = NovelAIService::generateOutline([
            'genre' => $novel['genre'] ?? '',
            'type' => $novel['type'] ?? '',
            'theme' => $novel['theme'] ?? '',
            'target_words' => $novel['target_words'] ?? 0,
            'conflict' => '',
        ]);

        if ($result['success']) {
            // 保存大纲
            $outlineData = $result['outline'] ?? $result['content'];
            NovelOutline::create([
                'novel_id' => $novelId,
                'outline_type' => $outlineType,
                'title' => 'AI生成大纲',
                'content' => is_array($outlineData) ? $outlineData : ['content' => $outlineData],
            ]);
        }

        echo json_encode($result);
    }

    /**
     * 生成角色
     */
    public function generateCharacter()
    {
        header('Content-Type: application/json');
        
        $userId = $this->checkAuth();

        $novelId = (int)($_POST['novel_id'] ?? 0);
        $roleType = $_POST['role_type'] ?? 'other';

        $novel = Novel::find($novelId);
        if (!$novel || $novel['user_id'] != $userId) {
            echo json_encode(['success' => false, 'error' => '小说不存在']);
            exit;
        }

        $result = NovelAIService::generateCharacter([
            'role_type' => $roleType,
            'story_background' => $novel['theme'] ?? '',
            'personality_hints' => $_POST['personality_hints'] ?? '',
            'story_function' => $_POST['story_function'] ?? '',
        ]);

        if ($result['success']) {
            // 保存角色
            $charData = $result['character'] ?? [];
            if (is_array($charData) && !empty($charData)) {
                NovelCharacter::create([
                    'novel_id' => $novelId,
                    'name' => $charData['name'] ?? '未命名角色',
                    'role_type' => $roleType,
                    'age' => $charData['age'] ?? null,
                    'gender' => $charData['gender'] ?? 'unknown',
                    'appearance' => $charData['appearance'] ?? null,
                    'personality' => $charData['personality'] ?? null,
                    'background' => $charData['background'] ?? null,
                    'abilities' => $charData['abilities'] ?? null,
                    'motivation' => $charData['motivation'] ?? null,
                ]);
            }
        }

        echo json_encode($result);
    }

    /**
     * 获取章节详情
     */
    public function getChapter($chapterId)
    {
        header('Content-Type: application/json');
        
        $userId = $this->checkAuth();

        $chapter = NovelChapter::find((int)$chapterId);
        if (!$chapter) {
            echo json_encode(['success' => false, 'error' => '章节不存在']);
            exit;
        }

        $novel = Novel::find($chapter['novel_id']);
        if (!$novel || $novel['user_id'] != $userId) {
            echo json_encode(['success' => false, 'error' => '无权限']);
            exit;
        }

        echo json_encode(['success' => true, 'chapter' => $chapter]);
    }

    /**
     * 保存章节
     */
    public function saveChapter()
    {
        header('Content-Type: application/json');
        
        $userId = $this->checkAuth();

        $novelId = (int)($_POST['novel_id'] ?? 0);
        $chapterId = (int)($_POST['chapter_id'] ?? 0);
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';

        $novel = Novel::find($novelId);
        if (!$novel || $novel['user_id'] != $userId) {
            echo json_encode(['success' => false, 'error' => '小说不存在']);
            exit;
        }

        if ($chapterId > 0) {
            // 更新
            NovelChapter::update($chapterId, [
                'title' => $title,
                'content' => $content,
            ]);
        } else {
            // 新建
            $chapters = NovelChapter::findByNovel($novelId);
            $chapterNumber = count($chapters) + 1;
            $chapterId = NovelChapter::create([
                'novel_id' => $novelId,
                'chapter_number' => $chapterNumber,
                'title' => $title,
                'content' => $content,
            ]);
        }

        echo json_encode(['success' => true, 'chapter_id' => $chapterId]);
    }
}
