<?php

namespace Core;

class Controller
{
    /**
     * 渲染视图文件
     *
     * @param string $viewPath 视图文件的路径 (相对于 app/views/)
     * @param array $data 传递给视图的数据
     */
    protected function view(string $viewPath, array $data = [])
    {
        // 将数组的键转换为变量
        extract($data);

        // 构建完整的视图文件路径
        $fullPath = realpath(__DIR__ . '/../views/' . $viewPath . '.php');

        if ($fullPath && file_exists($fullPath)) {
            ob_start();
            require $fullPath;
            $content = ob_get_clean();
            echo $content;
        } else {
            \app\services\ErrorHandler::handleNotFound('视图文件不存在');
        }
    }

    /**
     * 以 JSON 格式返回响应
     *
     * @param array $data 要编码为 JSON 的数据
     * @param int $statusCode HTTP 状态码
     */
    protected function json(array $data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 重定向到指定的 URL
     *
     * @param string $url 要重定向到的 URL
     */
    protected function redirect(string $url)
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * 检查当前请求是否为 POST 请求
     *
     * @return bool
     */
    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }


    /**
     * 检查用户是否已登录
     *
     * @return bool
     */
    protected function isLoggedIn(): bool
    {
        return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] && isset($_SESSION['user_id']);
    }

    /**
     * 获取当前登录用户的ID
     *
     * @return int|null
     */
    protected function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * 检查当前登录用户是否为管理员
     *
     * @return bool
     */
    protected function isAdmin(): bool
    {
        return $this->isLoggedIn() && ($_SESSION['user_role'] === 'admin');
    }
}
