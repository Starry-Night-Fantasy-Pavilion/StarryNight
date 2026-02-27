<?php

namespace app\frontend\controller;

use app\models\Novel;
use app\models\NovelChapter;
use app\models\NovelOutline;
use app\models\NovelCharacter;
use app\services\NovelAIService;
use app\services\StarryNightPermissionService;
use app\config\FrontendConfig;

/**
 * 小说创作工具控制器
 * 处理智能编辑器、大纲生成、角色管理、章节分析等AI创作功能
 */
class NovelCreationController
{
    private $viewPath;

    public function __construct()
    {
        $this->viewPath = dirname(__DIR__) . '/views';
    }

    /**
     * 检查用户登录状态
     */
    private function checkAuth()
    {
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            $redirectUrl = $_SERVER['REQUEST_URI'] ?? '/novel_creation';
            header('Location: /login?redirect=' . urlencode($redirectUrl));
            exit;
        }
        return $_SESSION['user_id'];
    }

    /**
     * 检查用户是否有权限使用星夜创作引擎
     */
    private function checkStarryNightPermission(string $engineVersion = 'basic'): bool
    {
        $userId = $this->checkAuth();
        $permissionService = new StarryNightPermissionService();
        $permission = $permissionService->checkPermission($userId, $engineVersion);
        return $permission['has_permission'];
    }

    /**
     * 获取用户可用的星夜创作引擎版本
     */
    private function getAvailableEngineVersion(): ?string
    {
        $userId = $this->checkAuth();
        $permissionService = new StarryNightPermissionService();
        return $permissionService->getCurrentVersion($userId);
    }

    /**
     * 渲染视图
     */
    private function render(string $view, array $data = [])
    {
        $viewFile = $this->viewPath . '/' . $view . '.php';
        if (!file_exists($viewFile)) {
            \app\services\ErrorHandler::handleNotFound('视图文件不存在');
            return;
        }
        extract($data);
        ob_start();
        include $viewFile;
        $content = ob_get_clean();
        
        // 使用layout
        $title = $data['title'] ?? '小说创作工具';
        $extra_css = $data['extra_css'] ?? [];
        $extra_js = $data['extra_js'] ?? [];

        // 添加小说创作页面的CSS / JS
        // 统一为小说创作相关视图（novel_tools/* 和 novel/project/*）加载
        if (strpos($view, 'novel_tools/') === 0 || strpos($view, 'novel/project/') === 0) {
            $themeManager = new \app\services\ThemeManager();
            $activeThemeId = $themeManager->getActiveThemeId('web') ?? FrontendConfig::THEME_DEFAULT;
            $themeVersion = FrontendConfig::CACHE_VERSION;

            // 通过当前启用的前台主题加载小说创作相关样式
            $extra_css[] = FrontendConfig::getThemeCssUrl('pages/novel-creation.css', $activeThemeId, $themeVersion);

            // 加载小说创作工具相关 JS 模块
            $extra_js[] = FrontendConfig::getAssetUrl(
                FrontendConfig::PATH_STATIC_FRONTEND_WEB_JS . '/modules/novel-creation.js',
                $themeVersion
            );
        }

        $data['extra_css'] = $extra_css;
        $data['extra_js'] = $extra_js;
        extract($data);
        include $this->viewPath . '/layout.php';
    }

    /**
     * 小说创作工具首页
     */
    public function index()
    {
        try {
            $userId = $this->checkAuth();
            $permissionService = new StarryNightPermissionService();
            
            try {
                $availableVersion = $permissionService->getCurrentVersion($userId);
            } catch (\Exception $e) {
                // 如果权限检查失败，记录错误但继续显示页面
                error_log('权限检查失败: ' . $e->getMessage());
                $availableVersion = null;
            }
            
            if (!$availableVersion) {
                $this->render('novel_tools/no_permission', [
                    'title' => '无权限访问 - 星夜阁',
                    'message' => '您当前没有权限使用星夜创作引擎，请升级会员后使用。'
                ]);
                return;
            }

            $this->render('novel_tools/index', [
                'title' => 'AI小说创作工具 - 星夜阁',
                'available_version' => $availableVersion
            ]);
        } catch (\Exception $e) {
            error_log('小说创作工具页面错误: ' . $e->getMessage());
            \app\services\ErrorHandler::handleServerError($e);
        }
    }

    /**
     * 章节分析页面
     */
    public function chapterAnalysis()
    {
        $this->checkAuth();
        $this->render('novel_tools/chapter_analysis/index', ['title' => '章节分析 - 星夜阁']);
    }

    /**
      * 执行章节分析
      */
     public function doChapterAnalysis()
     {
         $this->checkAuth();
         
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             header('Location: /novel_creation/chapter_analysis');
             exit;
         }
         
         $params = [
             'chapter_content' => $_POST['chapter_content'] ?? '',
             'character_settings' => $_POST['character_settings'] ?? '',
             'plot_background' => $_POST['plot_background'] ?? '',
             'model' => $_POST['model'] ?? null
         ];
         
         $result = NovelAIService::analyzeChapter($params);
         
         $this->render('novel_tools/result', [
             'title' => '章节分析结果 - 星夜阁',
             'tool_name' => '章节分析',
             'params' => $params,
             'result' => $result,
             'back_url' => '/novel_creation/chapter_analysis'
         ]);
     }

    /**
     * 拆书分析页面
     */
    public function bookAnalysis()
    {
        $this->checkAuth();
        $this->render('novel_tools/book_analysis/index', ['title' => '拆书分析 - 星夜阁']);
    }

    /**
     * 执行拆书分析
     */
    public function doBookAnalysis()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /novel_creation/book_analysis');
            exit;
        }
        
        $params = [
            'reference_text' => $_POST['reference_text'] ?? ''
        ];
        
        $result = NovelAIService::analyzeWritingTechnique($params);
        
        // 保存分析结果到session供后续使用
        $_SESSION['book_analysis_result'] = $result['content'] ?? '';
        $_SESSION['reference_text'] = $params['reference_text'];
        
        $this->render('novel_tools/book_analysis/result', [
            'title' => '拆书分析结果 - 星夜阁',
            'params' => $params,
            'result' => $result
        ]);
    }

    /**
     * 仿写创作页面
     */
    public function imitationWriting()
    {
        $this->checkAuth();
        
        $analysisResult = $_SESSION['book_analysis_result'] ?? '';
        $referenceText = $_SESSION['reference_text'] ?? '';
        
        if (empty($analysisResult)) {
            header('Location: /novel_creation/book_analysis');
            exit;
        }
        
        $this->render('novel_tools/rewriting/index', [
            'title' => '仿写创作 - 星夜阁',
            'analysis_result' => $analysisResult,
            'reference_text' => $referenceText
        ]);
    }

    /**
     * 执行仿写创作
     */
    public function doImitationWriting()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /novel_creation/imitation_writing');
            exit;
        }
        
        $params = [
            'reference_text' => $_POST['reference_text'] ?? '',
            'analysis' => $_POST['analysis'] ?? '',
            'new_theme' => $_POST['new_theme'] ?? '',
            'requirements' => $_POST['requirements'] ?? '',
            'word_count' => (int)($_POST['word_count'] ?? 500),
            'model' => $_POST['model'] ?? null
        ];
        
        $result = NovelAIService::generateImitation($params);
        
        $this->render('novel_tools/result', [
            'title' => '仿写创作结果 - 星夜阁',
            'tool_name' => '仿写创作',
            'params' => $params,
            'result' => $result,
            'back_url' => '/novel_creation/imitation_writing'
        ]);
    }

    /**
     * 黄金开篇页面
     */
    public function openingGenerator()
    {
        $this->checkAuth();
        $this->render('novel_tools/opening_generator/index', ['title' => '黄金开篇 - 星夜阁']);
    }

    /**
     * 执行黄金开篇
     */
    public function doOpeningGenerator()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /novel_creation/opening_generator');
            exit;
        }
        
        $params = [
            'novel_type' => $_POST['novel_type'] ?? '',
            'core_theme' => $_POST['core_theme'] ?? '',
            'main_character' => $_POST['main_character'] ?? '',
            'opening_atmosphere' => $_POST['opening_atmosphere'] ?? '',
            'word_count' => (int)($_POST['word_count'] ?? 500)
        ];
        
        $result = NovelAIService::generateOpening($params);
        
        $this->render('novel_tools/result', [
            'title' => '黄金开篇结果 - 星夜阁',
            'tool_name' => '黄金开篇',
            'params' => $params,
            'result' => $result,
            'back_url' => '/novel_creation/opening_generator'
        ]);
    }

    /**
     * 书名生成页面
     */
    public function titleGenerator()
    {
        $this->checkAuth();
        $this->render('novel_tools/title_generator/index', ['title' => '书名生成 - 星夜阁']);
    }

    /**
     * 执行书名生成
     */
    public function doTitleGenerator()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /novel_creation/title_generator');
            exit;
        }
        
        $params = [
            'novel_type' => $_POST['novel_type'] ?? '',
            'core_theme' => $_POST['core_theme'] ?? '',
            'keywords' => $_POST['keywords'] ?? '',
            'count' => (int)($_POST['count'] ?? 5)
        ];
        
        $result = NovelAIService::generateTitle($params);
        
        $this->render('novel_tools/result', [
            'title' => '书名生成结果 - 星夜阁',
            'tool_name' => '书名生成',
            'params' => $params,
            'result' => $result,
            'back_url' => '/novel_creation/title_generator'
        ]);
    }

    /**
     * 简介生成页面
     */
    public function descriptionGenerator()
    {
        $this->checkAuth();
        $this->render('novel_tools/description_generator/index', ['title' => '简介生成 - 星夜阁']);
    }

    /**
     * 执行简介生成
     */
    public function doDescriptionGenerator()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /novel_creation/description_generator');
            exit;
        }
        
        $params = [
            'title' => $_POST['title'] ?? '',
            'novel_type' => $_POST['novel_type'] ?? '',
            'core_theme' => $_POST['core_theme'] ?? '',
            'main_character' => $_POST['main_character'] ?? '',
            'word_count' => (int)($_POST['word_count'] ?? 200)
        ];
        
        $result = NovelAIService::generateDescription($params);
        
        $this->render('novel_tools/result', [
            'title' => '简介生成结果 - 星夜阁',
            'tool_name' => '简介生成',
            'params' => $params,
            'result' => $result,
            'back_url' => '/novel_creation/description_generator'
        ]);
    }

    /**
     * 金手指生成页面
     */
    public function cheatGenerator()
    {
        $this->checkAuth();
        $this->render('novel_tools/cheat_generator/index', ['title' => '金手指生成 - 星夜阁']);
    }

    /**
     * 执行金手指生成
     */
    public function doCheatGenerator()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /novel_creation/cheat_generator');
            exit;
        }
        
        $params = [
            'novel_type' => $_POST['novel_type'] ?? '',
            'core_theme' => $_POST['core_theme'] ?? '',
            'main_character' => $_POST['main_character'] ?? ''
        ];
        
        $result = NovelAIService::generateCheat($params);
        
        $this->render('novel_tools/result', [
            'title' => '金手指生成结果 - 星夜阁',
            'tool_name' => '金手指生成',
            'params' => $params,
            'result' => $result,
            'back_url' => '/novel_creation/cheat_generator'
        ]);
    }

    /**
     * 名字生成页面
     */
    public function nameGenerator()
    {
        $this->checkAuth();
        $this->render('novel_tools/name_generator/index', ['title' => '名字生成 - 星夜阁']);
    }

    /**
     * 执行名字生成
     */
    public function doNameGenerator()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /novel_creation/name_generator');
            exit;
        }
        
        $params = [
            'name_type' => $_POST['name_type'] ?? 'character',
            'genre' => $_POST['genre'] ?? '',
            'style' => $_POST['style'] ?? '',
            'count' => (int)($_POST['count'] ?? 10)
        ];
        
        $result = NovelAIService::generateName($params);
        
        $this->render('novel_tools/result', [
            'title' => '名字生成结果 - 星夜阁',
            'tool_name' => '名字生成',
            'params' => $params,
            'result' => $result,
            'back_url' => '/novel_creation/name_generator'
        ]);
    }

    /**
     * 封面描述页面
     */
    public function coverGenerator()
    {
        $this->checkAuth();
        $this->render('novel_tools/cover_generator/index', ['title' => '封面描述 - 星夜阁']);
    }

    /**
     * 执行封面描述
     */
    public function doCoverGenerator()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /novel_creation/cover_generator');
            exit;
        }
        
        $params = [
            'title' => $_POST['title'] ?? '',
            'novel_type' => $_POST['novel_type'] ?? '',
            'core_theme' => $_POST['core_theme'] ?? '',
            'key_elements' => $_POST['key_elements'] ?? ''
        ];
        
        $result = NovelAIService::generateCoverDescription($params);
        
        $this->render('novel_tools/result', [
            'title' => '封面描述结果 - 星夜阁',
            'tool_name' => '封面描述',
            'params' => $params,
            'result' => $result,
            'back_url' => '/novel_creation/cover_generator'
        ]);
    }

    /**
     * 短篇创作页面
     */
    public function shortStory()
    {
        $this->checkAuth();
        $this->render('novel_tools/short_story/index', ['title' => '短篇创作 - 星夜阁']);
    }

    /**
     * 执行短篇创作
     */
    public function doShortStory()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /novel_creation/short_story');
            exit;
        }
        
        $params = [
            'genre' => $_POST['genre'] ?? '',
            'theme' => $_POST['theme'] ?? '',
            'main_character' => $_POST['main_character'] ?? '',
            'plot' => $_POST['plot'] ?? '',
            'word_count' => (int)($_POST['word_count'] ?? 2000)
        ];
        
        $result = NovelAIService::writeShortStory($params);
        
        $this->render('novel_tools/result', [
            'title' => '短篇创作结果 - 星夜阁',
            'tool_name' => '短篇创作',
            'params' => $params,
            'result' => $result,
            'back_url' => '/novel_creation/short_story'
        ]);
    }

    /**
     * 短剧剧本页面
     */
    public function shortDrama()
    {
        $this->checkAuth();
        $this->render('novel_tools/short_drama/index', ['title' => '短剧剧本 - 星夜阁']);
    }

    /**
     * 执行短剧剧本
     */
    public function doShortDrama()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /novel_creation/short_drama');
            exit;
        }
        
        $params = [
            'title' => $_POST['title'] ?? '',
            'genre' => $_POST['genre'] ?? '',
            'main_character' => $_POST['main_character'] ?? '',
            'plot' => $_POST['plot'] ?? '',
            'episode_count' => (int)($_POST['episode_count'] ?? 1)
        ];
        
        $result = NovelAIService::writeShortDrama($params);
        
        $this->render('novel_tools/result', [
            'title' => '短剧剧本结果 - 星夜阁',
            'tool_name' => '短剧剧本',
            'params' => $params,
            'result' => $result,
            'back_url' => '/novel_creation/short_drama'
        ]);
    }

    // ==================== 智能编辑器功能 ====================

    /**
     * 智能编辑器页面
     */
    public function editor()
    {
        $this->checkAuth();
        
        $novelId = (int)($_GET['novel_id'] ?? 0);
        $chapterId = (int)($_GET['chapter_id'] ?? 0);
        
        $novels = Novel::findByUser($this->checkAuth());
        $currentNovel = null;
        $currentChapter = null;
        $chapters = [];
        
        if ($novelId > 0) {
            $currentNovel = Novel::find($novelId);
            if ($currentNovel) {
                $chapters = NovelChapter::findByNovel($novelId);
            }
        }
        
        if ($chapterId > 0) {
            $currentChapter = NovelChapter::find($chapterId);
            if ($currentChapter && !$novelId) {
                $novelId = $currentChapter['novel_id'];
                $currentNovel = Novel::find($novelId);
                $chapters = NovelChapter::findByNovel($novelId);
            }
        }
        
        $this->render('novel/project/editor', [
            'title' => '智能编辑器 - 星夜阁',
            'novels' => $novels,
            'current_novel' => $currentNovel,
            'chapters' => $chapters,
            'current_chapter' => $currentChapter,
            'novel_id' => $novelId,
            'chapter_id' => $chapterId
        ]);
    }

    /**
     * 获取章节版本历史
     */
    public function getChapterVersions()
    {
        $this->checkAuth();
        
        $chapterId = (int)($_GET['chapter_id'] ?? 0);
        if ($chapterId <= 0) {
            echo json_encode(['success' => false, 'error' => '无效的章节ID']);
            exit;
        }
        
        $versions = NovelChapter::getVersions($chapterId);
        echo json_encode(['success' => true, 'versions' => $versions]);
    }

    /**
     * 恢复章节版本
     */
    public function restoreVersion()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => '不支持的请求方法']);
            exit;
        }
        
        $versionId = (int)($_POST['version_id'] ?? 0);
        $chapterId = (int)($_POST['chapter_id'] ?? 0);
        
        if ($versionId <= 0 || $chapterId <= 0) {
            echo json_encode(['success' => false, 'error' => '无效的参数']);
            exit;
        }
        
        $versions = NovelChapter::getVersions($chapterId);
        $targetVersion = null;
        foreach ($versions as $v) {
            if ($v['id'] == $versionId) {
                $targetVersion = $v;
                break;
            }
        }
        
        if (!$targetVersion) {
            echo json_encode(['success' => false, 'error' => '版本不存在']);
            exit;
        }
        
        // 恢复内容
        NovelChapter::update($chapterId, [
            'content' => $targetVersion['content'],
            'word_count' => $targetVersion['word_count']
        ]);
        
        echo json_encode(['success' => true, 'message' => '版本已恢复']);
    }

    /**
     * AI续写
     */
    public function aiContinue()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => '不支持的请求方法']);
            exit;
        }
        
        $params = [
            'context' => $_POST['context'] ?? '',
            'characters' => $_POST['characters'] ?? '',
            'plot_requirements' => $_POST['plot_requirements'] ?? '',
            'style' => $_POST['style'] ?? '',
            'word_count' => (int)($_POST['word_count'] ?? 500)
        ];
        
        $result = NovelAIService::continueWriting($params);
        echo json_encode($result);
    }

    /**
     * AI改写
     */
    public function aiRewrite()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => '不支持的请求方法']);
            exit;
        }
        
        $params = [
            'content' => $_POST['content'] ?? '',
            'requirements' => $_POST['requirements'] ?? ''
        ];
        
        $result = NovelAIService::rewrite($params);
        echo json_encode($result);
    }

    /**
     * AI扩写
     */
    public function aiExpand()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => '不支持的请求方法']);
            exit;
        }
        
        $params = [
            'content' => $_POST['content'] ?? '',
            'target_words' => (int)($_POST['target_words'] ?? 1000),
            'direction' => $_POST['direction'] ?? ''
        ];
        
        $result = NovelAIService::expand($params);
        echo json_encode($result);
    }

    /**
     * AI润色
     */
    public function aiPolish()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => '不支持的请求方法']);
            exit;
        }
        
        $params = [
            'content' => $_POST['content'] ?? '',
            'style' => $_POST['style'] ?? ''
        ];
        
        $result = NovelAIService::polish($params);
        echo json_encode($result);
    }

    // ==================== 大纲生成系统 ====================

    /**
     * 大纲生成页面
     */
    public function outlineGenerator()
    {
        $this->checkAuth();
        
        $novelId = (int)($_GET['novel_id'] ?? 0);
        $outlineType = $_GET['outline_type'] ?? 'chapter';
        
        $novels = Novel::findByUser($this->checkAuth());
        $outlines = [];
        
        if ($novelId > 0) {
            $outlines = NovelOutline::findByNovel($novelId, $outlineType);
        }
        
        $this->render('novel_tools/outline/index', [
            'title' => '大纲生成 - 星夜阁',
            'novels' => $novels,
            'novel_id' => $novelId,
            'outlines' => $outlines,
            'outline_type' => $outlineType
        ]);
    }

    /**
     * 执行大纲生成
     */
    public function doOutlineGenerator()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /novel_creation/outline_generator');
            exit;
        }
        
        $params = [
            'genre' => $_POST['genre'] ?? '',
            'type' => $_POST['type'] ?? '',
            'theme' => $_POST['theme'] ?? '',
            'target_words' => (int)($_POST['target_words'] ?? 0),
            'conflict' => $_POST['conflict'] ?? '',
            'outline_level' => $_POST['outline_level'] ?? 'detailed' // chapter/plot/detail
        ];
        
        $result = NovelAIService::generateOutline($params);
        
        $this->render('novel_tools/outline/result', [
            'title' => '大纲生成结果 - 星夜阁',
            'tool_name' => '大纲生成',
            'params' => $params,
            'result' => $result,
            'back_url' => '/novel_creation/outline_generator'
        ]);
    }

    /**
     * 保存大纲
     */
    public function saveOutline()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => '不支持的请求方法']);
            exit;
        }
        
        $novelId = (int)($_POST['novel_id'] ?? 0);
        $outlineType = $_POST['outline_type'] ?? 'chapter';
        $outlineData = $_POST['outline_data'] ?? '';
        
        if ($novelId <= 0) {
            echo json_encode(['success' => false, 'error' => '请先选择或创建小说']);
            exit;
        }
        
        // 解析JSON大纲数据
        $outlines = json_decode($outlineData, true);
        if (!$outlines) {
            echo json_encode(['success' => false, 'error' => '无效的大纲数据']);
            exit;
        }
        
        $levelMap = [
            'chapter' => 1,
            'plot' => 2,
            'detail' => 3
        ];
        $level = $levelMap[$outlineType] ?? 1;
        
        foreach ($outlines as $index => $item) {
            NovelOutline::create([
                'novel_id' => $novelId,
                'outline_type' => $outlineType,
                'title' => $item['title'] ?? '',
                'content' => $item['content'] ?? $item['description'] ?? '',
                'level' => $level,
                'sort_order' => $index
            ]);
        }
        
        echo json_encode(['success' => true, 'message' => '大纲已保存']);
    }

    /**
     * 删除大纲
     */
    public function deleteOutline()
    {
        $this->checkAuth();
        
        $outlineId = (int)($_POST['outline_id'] ?? 0);
        
        if ($outlineId > 0) {
            NovelOutline::delete($outlineId);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => '无效的大纲ID']);
        }
    }

    // ==================== 角色管理系统 ====================

    /**
     * 角色管理页面
     */
    public function characterManager()
    {
        $this->checkAuth();
        
        $novelId = (int)($_GET['novel_id'] ?? 0);
        
        $novels = Novel::findByUser($this->checkAuth());
        $characters = [];
        $relationships = [];
        
        if ($novelId > 0) {
            $characters = NovelCharacter::findByNovel($novelId);
            $relationships = NovelCharacter::getRelationships($novelId);
        }
        
        $this->render('novel_tools/characters/manager', [
            'title' => '角色管理 - 星夜阁',
            'novels' => $novels,
            'novel_id' => $novelId,
            'characters' => $characters,
            'relationships' => $relationships
        ]);
    }

    /**
     * 角色生成页面
     */
    public function characterGenerator()
    {
        $this->checkAuth();
        
        $novelId = (int)($_GET['novel_id'] ?? 0);
        
        $this->render('novel_tools/characters/generator', [
            'title' => 'AI角色生成 - 星夜阁',
            'novel_id' => $novelId
        ]);
    }

    /**
     * 执行角色生成
     */
    public function doCharacterGenerator()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /novel_creation/character_generator');
            exit;
        }
        
        $params = [
            'role_type' => $_POST['role_type'] ?? 'other',
            'story_background' => $_POST['story_background'] ?? '',
            'personality_hints' => $_POST['personality_hints'] ?? '',
            'story_function' => $_POST['story_function'] ?? ''
        ];
        
        $result = NovelAIService::generateCharacter($params);
        
        $this->render('novel_tools/characters/result', [
            'title' => '角色生成结果 - 星夜阁',
            'tool_name' => 'AI角色生成',
            'params' => $params,
            'result' => $result,
            'back_url' => '/novel_creation/character_generator'
        ]);
    }

    /**
     * 保存角色
     */
    public function saveCharacter()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => '不支持的请求方法']);
            exit;
        }
        
        $novelId = (int)($_POST['novel_id'] ?? 0);
        $characterData = $_POST['character_data'] ?? '';
        $characterId = (int)($_POST['character_id'] ?? 0);
        
        if ($novelId <= 0) {
            echo json_encode(['success' => false, 'error' => '请先选择或创建小说']);
            exit;
        }
        
        $data = json_decode($characterData, true);
        if (!$data) {
            echo json_encode(['success' => false, 'error' => '无效的角色数据']);
            exit;
        }
        
        $data['novel_id'] = $novelId;
        
        if ($characterId > 0) {
            NovelCharacter::update($characterId, $data);
            echo json_encode(['success' => true, 'message' => '角色已更新', 'character_id' => $characterId]);
        } else {
            $newId = NovelCharacter::create($data);
            echo json_encode(['success' => true, 'message' => '角色已创建', 'character_id' => $newId]);
        }
    }

    /**
     * 删除角色
     */
    public function deleteCharacter()
    {
        $this->checkAuth();
        
        $characterId = (int)($_POST['character_id'] ?? 0);
        
        if ($characterId > 0) {
            NovelCharacter::delete($characterId);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => '无效的角色ID']);
        }
    }

    /**
     * 角色一致性检查页面
     */
    public function characterConsistencyCheck()
    {
        $this->checkAuth();
        
        $novelId = (int)($_GET['novel_id'] ?? 0);
        $chapterId = (int)($_GET['chapter_id'] ?? 0);
        
        $novels = Novel::findByUser($this->checkAuth());
        $characters = [];
        $chapters = [];
        
        if ($novelId > 0) {
            $characters = NovelCharacter::findByNovel($novelId);
            $chapters = NovelChapter::findByNovel($novelId);
        }
        
        $currentChapter = null;
        if ($chapterId > 0) {
            $currentChapter = NovelChapter::find($chapterId);
        }
        
        $this->render('novel_tools/characters/consistency', [
            'title' => '角色一致性检查 - 星夜阁',
            'novels' => $novels,
            'novel_id' => $novelId,
            'characters' => $characters,
            'chapters' => $chapters,
            'current_chapter' => $currentChapter,
            'chapter_id' => $chapterId
        ]);
    }

    /**
     * 执行角色一致性检查
     */
    public function doCharacterConsistencyCheck()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /novel_creation/character_consistency_check');
            exit;
        }
        
        $params = [
            'chapter_content' => $_POST['chapter_content'] ?? '',
            'character_settings' => $_POST['character_settings'] ?? '',
            'model' => $_POST['model'] ?? null
        ];
        
        $result = NovelAIService::checkCharacterConsistency($params);
        
        $this->render('novel_tools/result', [
            'title' => '角色一致性检查结果 - 星夜阁',
            'tool_name' => '角色一致性检查',
            'params' => $params,
            'result' => $result,
            'back_url' => '/novel_creation/character_consistency_check'
        ]);
    }

    // ==================== 小说项目管理 ====================

    /**
     * 创建小说
     */
    public function createNovel()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => '不支持的请求方法']);
            exit;
        }
        
        $novelId = Novel::create([
            'user_id' => $this->checkAuth(),
            'title' => $_POST['title'] ?? '未命名小说',
            'genre' => $_POST['genre'] ?? '',
            'type' => $_POST['type'] ?? '',
            'theme' => $_POST['theme'] ?? '',
            'target_words' => (int)($_POST['target_words'] ?? 0),
            'description' => $_POST['description'] ?? ''
        ]);
        
        echo json_encode(['success' => true, 'novel_id' => $novelId]);
    }

    /**
     * 保存章节
     */
    public function saveChapter()
    {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => '不支持的请求方法']);
            exit;
        }
        
        $novelId = (int)($_POST['novel_id'] ?? 0);
        $chapterId = (int)($_POST['chapter_id'] ?? 0);
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $status = $_POST['status'] ?? 'draft';
        
        if ($novelId <= 0) {
            echo json_encode(['success' => false, 'error' => '无效的小说ID']);
            exit;
        }
        
        $wordCount = mb_strlen(strip_tags($content), 'UTF-8');
        
        if ($chapterId > 0) {
            NovelChapter::update($chapterId, [
                'title' => $title,
                'content' => $content,
                'status' => $status,
                'word_count' => $wordCount
            ]);
            
            // 重新计算小说总字数
            Novel::updateWordCount($novelId);
            
            echo json_encode(['success' => true, 'chapter_id' => $chapterId, 'message' => '章节已保存']);
        } else {
            // 获取最大章节号
            $chapters = NovelChapter::findByNovel($novelId);
            $maxChapterNumber = 0;
            foreach ($chapters as $c) {
                $maxChapterNumber = max($maxChapterNumber, (int)$c['chapter_number']);
            }
            
            $newChapterId = NovelChapter::create([
                'novel_id' => $novelId,
                'chapter_number' => $maxChapterNumber + 1,
                'title' => $title ?: '第 ' . ($maxChapterNumber + 1) . ' 章',
                'content' => $content,
                'status' => $status,
                'word_count' => $wordCount,
                'sort_order' => count($chapters)
            ]);
            
            echo json_encode(['success' => true, 'chapter_id' => $newChapterId, 'message' => '章节已创建']);
        }
    }

    /**
     * 删除章节
     */
    public function deleteChapter()
    {
        $this->checkAuth();
        
        $chapterId = (int)($_POST['chapter_id'] ?? 0);
        
        if ($chapterId > 0) {
            NovelChapter::delete($chapterId);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => '无效的章节ID']);
        }
    }

    /**
     * 重新排序章节
     */
    public function reorderChapters()
    {
        $this->checkAuth();
        
        $orders = $_POST['orders'] ?? [];
        
        foreach ($orders as $index => $chapterId) {
            NovelChapter::update((int)$chapterId, [
                'sort_order' => (int)$index,
                'chapter_number' => $index + 1
            ]);
        }
        
        echo json_encode(['success' => true]);
    }

    /**
     * 世界观生成器
     */
    public function worldviewGenerator()
    {
        $userId = $this->checkAuth();
        
        $this->render('novel_tools/worldview_generator/index', [
            'title' => '世界观生成器 - 小说创作工具',
        ]);
    }

    /**
     * 执行世界观生成
     */
    public function doWorldviewGenerator()
    {
        $userId = $this->checkAuth();
        
        if (!$this->checkStarryNightPermission()) {
            echo json_encode(['success' => false, 'message' => '您没有权限使用此功能']);
            exit;
        }

        $theme = $_POST['theme'] ?? '';
        $genre = $_POST['genre'] ?? '';
        $elements = $_POST['elements'] ?? [];
        $complexity = $_POST['complexity'] ?? 'medium';

        try {
            $aiService = new NovelAIService();
            $availableVersion = $this->getAvailableEngineVersion();
            
            $prompt = "请生成一个{$genre}类型的世界观设定，主题为：{$theme}。";
            if (!empty($elements)) {
                $prompt .= "需要包含以下元素：" . implode('、', $elements) . "。";
            }
            $prompt .= "复杂度：{$complexity}。请详细描述世界观的核心设定、地理环境、社会结构、历史背景、魔法/科技体系等。";

            $result = $aiService->generateContent($prompt, $availableVersion ?? 'basic');
            
            echo json_encode([
                'success' => true,
                'worldview' => $result['content'] ?? '',
            ]);
        } catch (\Exception $e) {
            error_log('世界观生成失败: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => '生成失败：' . $e->getMessage()]);
        }
    }

    /**
     * 脑洞生成器
     */
    public function brainstormGenerator()
    {
        $userId = $this->checkAuth();
        
        $this->render('novel_tools/brainstorm/index', [
            'title' => '脑洞生成器 - 小说创作工具',
        ]);
    }

    /**
     * 执行脑洞生成
     */
    public function doBrainstormGenerator()
    {
        $userId = $this->checkAuth();
        
        if (!$this->checkStarryNightPermission()) {
            echo json_encode(['success' => false, 'message' => '您没有权限使用此功能']);
            exit;
        }

        $type = $_POST['type'] ?? 'plot';
        $keywords = $_POST['keywords'] ?? '';
        $count = intval($_POST['count'] ?? 5);

        try {
            $aiService = new NovelAIService();
            $availableVersion = $this->getAvailableEngineVersion();
            
            $typeNames = [
                'plot' => '情节',
                'character' => '角色',
                'setting' => '设定',
                'conflict' => '冲突',
                'twist' => '反转'
            ];
            
            $typeName = $typeNames[$type] ?? '情节';
            
            $prompt = "请基于关键词：{$keywords}，生成{$count}个{$typeName}相关的创意脑洞。每个脑洞应该独特、有趣、有潜力发展成完整的故事。";

            $result = $aiService->generateContent($prompt, $availableVersion ?? 'basic');
            
            // 尝试解析为数组格式
            $ideas = [];
            $content = $result['content'] ?? '';
            $lines = explode("\n", $content);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line) && (preg_match('/^\d+[\.、]/', $line) || preg_match('/^[•·-]/', $line))) {
                    $ideas[] = preg_replace('/^\d+[\.、]\s*|^[•·-]\s*/', '', $line);
                }
            }
            
            if (empty($ideas)) {
                $ideas = [$content];
            }
            
            echo json_encode([
                'success' => true,
                'ideas' => array_slice($ideas, 0, $count),
            ]);
        } catch (\Exception $e) {
            error_log('脑洞生成失败: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => '生成失败：' . $e->getMessage()]);
        }
    }
}

