<?php

namespace app\frontend\controller;

/**
 * 前端用户基础控制器
 * 提供统一的布局和认证检查
 */
class BaseUserController
{
    protected $viewPath;
    protected $currentPage = 'dashboard';

    public function __construct()
    {
        $this->viewPath = dirname(__DIR__) . '/views';
    }

    /**
     * 检查用户登录状态
     */
    protected function checkAuth()
    {
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        return $_SESSION['user_id'];
    }

    /**
     * 获取当前用户信息
     */
    protected function getCurrentUser()
    {
        $userId = $this->checkAuth();
        return \app\models\User::find($userId);
    }

    /**
     * 渲染视图（使用用户中心布局）
     */
    protected function render(string $view, array $data = [])
    {
        $viewFile = $this->viewPath . '/' . $view . '.php';
        if (!file_exists($viewFile)) {
            \app\services\ErrorHandler::handleNotFound('视图文件不存在: ' . $view);
            return;
        }
        
        extract($data);
        ob_start();
        include $viewFile;
        $content = ob_get_clean();
        
        // 使用用户中心后台风格布局
        $title = $data['title'] ?? '用户中心';
        $currentPage = $data['currentPage'] ?? $this->currentPage;
        $user = $data['user'] ?? $this->getCurrentUser();
        
        include $this->viewPath . '/user_center_layout.php';
    }
}
