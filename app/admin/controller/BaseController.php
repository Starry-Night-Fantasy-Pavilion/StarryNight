<?php

namespace app\admin\controller;

class BaseController
{
    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $this->checkAuth();
    }

    protected function checkAuth()
    {
        $isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');

        if (!$isLoggedIn) {
            header('Location: /' . $adminPrefix . '/login');
            exit;
        }

        // Session timeout logic
        // Session timeout logic
        $session_duration = (int)get_env('SESSION_LIFETIME', 1800); // 30 minutes default
        if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity'] > $session_duration)) {
            // Destroy session and redirect to login
            session_unset();
            session_destroy();
            header('Location: /' . $adminPrefix . '/login?error=4'); // 4 for session expired
            exit;
        }
        $_SESSION['admin_last_activity'] = time(); // Update last activity time
    }

    /**
     * 检查用户权限
     */
    protected function checkPermission($permission)
    {
        // 简单的权限检查实现
        // 在实际应用中，这里应该检查用户的角色和权限
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'message' => '未登录']);
            exit;
        }
        
        // 这里可以添加更复杂的权限检查逻辑
        return true;
    }

    /**
     * 渲染视图
     */
    protected function view($template, $data = [])
    {
        // 提取数据到局部变量
        extract($data);
        
        // 设置视图文件路径
        $viewFile = __DIR__ . '/../views/' . $template . '.php';
        
        if (!file_exists($viewFile)) {
            throw new \Exception("视图文件不存在: $template");
        }
        
        // 包含视图文件
        include $viewFile;
    }

    /**
     * 返回JSON响应
     */
    protected function json($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 返回错误响应
     * @param string $message 错误信息
     * @param int $code HTTP状态码
     */
    protected function error(string $message, int $code = 400)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'code' => $code
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 是否为 POST 请求（供后台控制器复用）
     */
    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /**
     * 简单判断当前是否为后台管理员（基于会话）
     */
    protected function isAdmin(): bool
    {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
}
