<?php

namespace app\frontend\controller;

use app\services\ThemeManager;
use app\models\CreationTemplate;
use app\models\Setting;
use app\models\User;

class TemplateController
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
     * 模板库首页
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

        $result = CreationTemplate::getAll(
            (int)$page,
            (int)$perPage,
            $searchTerm,
            $category,
            $type,
            true, // 只显示公开模板
            $sortBy,
            $sortOrder
        );

        $categories = ['novel' => '小说模板', 'anime' => '动漫模板', 'music' => '音乐模板', 'other' => '其他模板'];
        $types = ['preset' => '预设模板', 'custom' => '自定义模板'];

        $this->render('templates/index', [
            'title' => '模板库 - 星夜阁',
            'templates' => $result['templates'] ?? [],
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
     * 模板详情页
     */
    public function show()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /templates');
            exit;
        }

        $template = CreationTemplate::find((int)$id);
        if (!$template) {
            header('Location: /templates');
            exit;
        }

        // 增加查看次数（如果模型有该方法）
        // CreationTemplate::incrementViewCount((int)$id);

        $this->render('templates/show', [
            'title' => $template['title'] . ' - 模板库',
            'template' => $template,
        ]);
    }

    /**
     * 创建模板页面
     */
    public function create()
    {
        $this->checkAuth();

        $categories = ['novel' => '小说模板', 'anime' => '动漫模板', 'music' => '音乐模板', 'other' => '其他模板'];
        $types = ['preset' => '预设模板', 'custom' => '自定义模板'];

        $this->render('templates/create', [
            'title' => '创建模板 - 模板库',
            'categories' => $categories,
            'types' => $types,
        ]);
    }

    /**
     * 保存模板
     */
    public function store()
    {
        $userId = $this->checkAuth();

        $data = [
            'user_id' => $userId,
            'title' => $_POST['title'] ?? '',
            'category' => $_POST['category'] ?? 'novel',
            'type' => $_POST['type'] ?? 'custom',
            'content' => $_POST['content'] ?? '',
            'structure' => $_POST['structure'] ?? null,
            'description' => $_POST['description'] ?? '',
            'tags' => $_POST['tags'] ?? '',
            'is_public' => isset($_POST['is_public']) ? 1 : 0,
            'price' => floatval($_POST['price'] ?? 0),
        ];

        $id = CreationTemplate::create($data);
        if ($id) {
            header('Location: /templates/' . $id);
            exit;
        }

        header('Location: /templates/create?error=创建失败');
        exit;
    }

    /**
     * 应用模板
     */
    public function apply()
    {
        $userId = $this->checkAuth();
        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => '模板ID不能为空']);
            exit;
        }

        $template = CreationTemplate::find((int)$id);
        if (!$template) {
            echo json_encode(['success' => false, 'message' => '模板不存在']);
            exit;
        }

        // 增加使用次数
        CreationTemplate::incrementUsage((int)$id);

        echo json_encode([
            'success' => true,
            'template' => $template,
        ]);
        exit;
    }
}
