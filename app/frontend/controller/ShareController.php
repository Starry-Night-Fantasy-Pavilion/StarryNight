<?php

namespace app\frontend\controller;

use app\services\ThemeManager;
use app\models\ResourceShare;
use app\models\KnowledgeBase;
use app\models\AIPromptTemplate;
use app\models\CreationTemplate;
use app\models\AIAgent;
use app\models\Setting;

class ShareController
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
     * 资源分享平台首页
     */
    public function index()
    {
        $resourceType = $_GET['type'] ?? 'all';
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 12;
        $searchTerm = $_GET['search'] ?? null;
        $sortBy = $_GET['sort_by'] ?? 'created_at';
        $sortOrder = $_GET['sort_order'] ?? 'desc';

        $result = ResourceShare::getAll(
            (int)$page,
            (int)$perPage,
            $searchTerm,
            $resourceType !== 'all' ? $resourceType : null,
            true, // 只显示公开资源
            $sortBy,
            $sortOrder
        );

        $resourceTypes = [
            'all' => '全部资源',
            'knowledge' => '知识库',
            'prompt' => '提示词',
            'template' => '模板',
            'agent' => '智能体'
        ];

        $this->render('share/index', [
            'title' => '资源分享平台 - 星夜阁',
            'resources' => $result['shares'] ?? [],
            'pagination' => [
                'total' => $result['total'] ?? 0,
                'page' => $result['page'] ?? 1,
                'perPage' => $result['perPage'] ?? 12,
                'totalPages' => $result['totalPages'] ?? 1,
            ],
            'resourceTypes' => $resourceTypes,
            'currentType' => $resourceType,
            'searchTerm' => $searchTerm,
        ]);
    }

    /**
     * 知识库分享
     */
    public function knowledge()
    {
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 12;
        $searchTerm = $_GET['search'] ?? null;

        $result = KnowledgeBase::getAll(
            (int)$page,
            (int)$perPage,
            $searchTerm,
            null,
            true, // 只显示公开知识库
            'created_at',
            'desc'
        );

        $this->render('share/knowledge', [
            'title' => '知识库分享 - 资源分享平台',
            'knowledgeBases' => $result['knowledge_bases'] ?? [],
            'pagination' => [
                'total' => $result['total'] ?? 0,
                'page' => $result['page'] ?? 1,
                'perPage' => $result['perPage'] ?? 12,
                'totalPages' => $result['totalPages'] ?? 1,
            ],
            'searchTerm' => $searchTerm,
        ]);
    }

    /**
     * 提示词分享
     */
    public function prompts()
    {
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 12;
        $searchTerm = $_GET['search'] ?? null;
        $category = $_GET['category'] ?? null;

        $result = AIPromptTemplate::getAll(
            (int)$page,
            (int)$perPage,
            $searchTerm,
            $category,
            true, // 只显示公开提示词
            'created_at',
            'desc'
        );

        $this->render('share/prompts', [
            'title' => '提示词分享 - 资源分享平台',
            'prompts' => $result['templates'] ?? [],
            'pagination' => [
                'total' => $result['total'] ?? 0,
                'page' => $result['page'] ?? 1,
                'perPage' => $result['perPage'] ?? 12,
                'totalPages' => $result['totalPages'] ?? 1,
            ],
            'searchTerm' => $searchTerm,
            'currentCategory' => $category,
        ]);
    }

    /**
     * 模板分享
     */
    public function templates()
    {
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 12;
        $searchTerm = $_GET['search'] ?? null;
        $category = $_GET['category'] ?? null;

        $result = CreationTemplate::getAll(
            (int)$page,
            (int)$perPage,
            $searchTerm,
            $category,
            null,
            true, // 只显示公开模板
            'created_at',
            'desc'
        );

        $this->render('share/templates', [
            'title' => '模板分享 - 资源分享平台',
            'templates' => $result['templates'] ?? [],
            'pagination' => [
                'total' => $result['total'] ?? 0,
                'page' => $result['page'] ?? 1,
                'perPage' => $result['perPage'] ?? 12,
                'totalPages' => $result['totalPages'] ?? 1,
            ],
            'searchTerm' => $searchTerm,
            'currentCategory' => $category,
        ]);
    }

    /**
     * 智能体分享
     */
    public function agents()
    {
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 12;
        $searchTerm = $_GET['search'] ?? null;
        $category = $_GET['category'] ?? null;

        $result = AIAgent::getAll(
            (int)$page,
            (int)$perPage,
            $searchTerm,
            $category,
            null,
            true, // 只显示公开智能体
            'created_at',
            'desc'
        );

        $this->render('share/agents', [
            'title' => '智能体分享 - 资源分享平台',
            'agents' => $result['agents'] ?? [],
            'pagination' => [
                'total' => $result['total'] ?? 0,
                'page' => $result['page'] ?? 1,
                'perPage' => $result['perPage'] ?? 12,
                'totalPages' => $result['totalPages'] ?? 1,
            ],
            'searchTerm' => $searchTerm,
            'currentCategory' => $category,
        ]);
    }

    /**
     * 资源详情页
     */
    public function show()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /share');
            exit;
        }

        $resource = ResourceShare::find((int)$id);
        if (!$resource) {
            header('Location: /share');
            exit;
        }

        // 根据资源类型获取详细信息
        $detail = null;
        switch ($resource['resource_type']) {
            case 'knowledge':
                $detail = KnowledgeBase::find($resource['resource_id']);
                break;
            case 'prompt':
                $detail = AIPromptTemplate::find($resource['resource_id']);
                break;
            case 'template':
                $detail = CreationTemplate::find($resource['resource_id']);
                break;
            case 'agent':
                $detail = AIAgent::find($resource['resource_id']);
                break;
        }

        // 增加查看次数
        ResourceShare::incrementViewCount((int)$id);

        $this->render('share/show', [
            'title' => $resource['title'] . ' - 资源分享',
            'resource' => $resource,
            'detail' => $detail,
        ]);
    }
}
