<?php

namespace app\frontend\controller;

use app\services\ThemeManager;
use app\models\AIAgent;
use app\models\Setting;
use app\models\User;

class AgentController
{
    private function render(string $template, array $viewVars = []): void
    {
        header('Content-Type: text/html; charset=utf-8');

        $siteName = (string) Setting::get('site_name') ?: (string) get_env('APP_NAME', '星夜阁');
        $viewPath = dirname(__DIR__) . '/views';
        
        // 尝试从主题包查找模板
        $themeManager = new ThemeManager();
        $theme = $themeManager->loadActiveThemeInstance();
        $content = null;
        
        if ($theme) {
            try {
                $content = $theme->renderTemplate($template, $viewVars);
            } catch (\Exception $e) {
                // 主题包中找不到，继续尝试从 app/frontend/views 查找
            }
        }
        
        // 如果主题包中找不到，从 app/frontend/views 查找
        if ($content === null) {
            $viewFile = $viewPath . '/' . $template . '.php';
            if (file_exists($viewFile)) {
                extract($viewVars);
                ob_start();
                include $viewFile;
                $content = ob_get_clean();
            } else {
                echo $viewVars['content'] ?? ('模板渲染失败: ' . htmlspecialchars($template));
                return;
            }
        }

        // 使用 layout.php
        $title = (string)($viewVars['title'] ?? $siteName);
        $page_class = (string)($viewVars['page_class'] ?? '');
        extract($viewVars);
        include $viewPath . '/layout.php';
    }

    private function checkAuth()
    {
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        return $_SESSION['user_id'];
    }

    /**
     * 智能体首页
     */
    public function index()
    {
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 12;
        $searchTerm = $_GET['search'] ?? null;
        $category = $_GET['category'] ?? null;
        $type = $_GET['type'] ?? null;
        $sortBy = $_GET['sort_by'] ?? 'created_at';
        $sortOrder = $_GET['sort_order'] ?? 'desc';

        $result = AIAgent::getAll(
            (int)$page,
            (int)$perPage,
            $searchTerm,
            $category,
            $type,
            true, // 只显示公开智能体
            $sortBy,
            $sortOrder
        );

        $categories = [
            'editor' => '编辑助手',
            'plot' => '情节顾问',
            'character' => '角色专家',
            'dialogue' => '对话专家',
            'worldview' => '世界观构建',
            'other' => '其他'
        ];
        $types = ['preset' => '预设智能体', 'custom' => '自定义智能体'];

        $this->render('agents/index', [
            'title' => '智能体 - 星夜阁',
            'agents' => $result['agents'] ?? [],
            'pagination' => [
                'total' => $result['total'] ?? 0,
                'page' => $result['page'] ?? 1,
                'perPage' => $result['perPage'] ?? 12,
                'totalPages' => $result['totalPages'] ?? 1,
            ],
            'categories' => $categories,
            'types' => $types,
            'currentCategory' => $category,
            'currentType' => $type,
            'searchTerm' => $searchTerm,
        ]);
    }

    /**
     * 智能体详情页
     */
    public function show()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /agents');
            exit;
        }

        $agent = AIAgent::find((int)$id);
        if (!$agent) {
            header('Location: /agents');
            exit;
        }

        // 增加查看次数（如果模型有该方法）
        // AIAgent::incrementViewCount((int)$id);

        $this->render('agents/show', [
            'title' => $agent['name'] . ' - 智能体',
            'agent' => $agent,
        ]);
    }

    /**
     * 创建智能体页面
     */
    public function create()
    {
        $this->checkAuth();

        $categories = [
            'editor' => '编辑助手',
            'plot' => '情节顾问',
            'character' => '角色专家',
            'dialogue' => '对话专家',
            'worldview' => '世界观构建',
            'other' => '其他'
        ];
        $types = ['preset' => '预设智能体', 'custom' => '自定义智能体'];

        $this->render('agents/create', [
            'title' => '创建智能体 - 智能体',
            'categories' => $categories,
            'types' => $types,
        ]);
    }

    /**
     * 保存智能体
     */
    public function store()
    {
        $userId = $this->checkAuth();

        $data = [
            'user_id' => $userId,
            'name' => $_POST['name'] ?? '',
            'category' => $_POST['category'] ?? 'editor',
            'type' => $_POST['type'] ?? 'custom',
            'description' => $_POST['description'] ?? '',
            'system_prompt' => $_POST['system_prompt'] ?? '',
            'model_config' => $_POST['model_config'] ?? null,
            'capabilities' => $_POST['capabilities'] ?? null,
            'is_public' => isset($_POST['is_public']) ? 1 : 0,
            'price' => floatval($_POST['price'] ?? 0),
        ];

        $id = AIAgent::create($data);
        if ($id) {
            header('Location: /agents/' . $id);
            exit;
        }

        header('Location: /agents/create?error=创建失败');
        exit;
    }

    /**
     * 使用智能体
     */
    public function use()
    {
        $userId = $this->checkAuth();
        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => '智能体ID不能为空']);
            exit;
        }

        $agent = AIAgent::find((int)$id);
        if (!$agent) {
            echo json_encode(['success' => false, 'message' => '智能体不存在']);
            exit;
        }

        // 增加使用次数
        AIAgent::incrementUsage((int)$id);

        echo json_encode([
            'success' => true,
            'agent' => $agent,
        ]);
        exit;
    }
}
