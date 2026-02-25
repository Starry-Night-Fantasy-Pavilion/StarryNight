<?php

namespace Core;

use Core\Api\ApiResponse;
use app\services\FrontendDataService;
use app\services\TemplateVariableResolver;

class Controller
{
    /**
     * 渲染视图文件
     * 优化：使用FrontendDataService准备视图数据
     *
     * @param string $viewPath 视图文件的路径 (相对于 app/views/)
     * @param array $data 传递给视图的数据
     * @param array $defaults 默认数据
     */
    protected function view(string $viewPath, array $data = [], array $defaults = [])
    {
        // 使用FrontendDataService准备视图数据
        $preparedData = FrontendDataService::prepareViewData($data, $defaults);
        
        // 将数组的键转换为变量
        extract($preparedData);

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
     * 优化：统一使用ApiResponse类
     *
     * @param array $data 要编码为 JSON 的数据
     * @param int $statusCode HTTP 状态码
     * @deprecated 使用 apiResponse() 方法代替
     */
    protected function json(array $data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 返回统一的API响应
     * 使用Core\Api\ApiResponse类
     *
     * @param mixed $data 响应数据
     * @param string $message 消息
     * @param array $meta 元数据
     * @return ApiResponse
     */
    protected function apiResponse($data = null, string $message = '操作成功', array $meta = []): ApiResponse
    {
        return FrontendDataService::createApiResponse($data, $message, $meta);
    }

    /**
     * 发送API成功响应
     *
     * @param mixed $data 响应数据
     * @param string $message 消息
     * @param array $meta 元数据
     * @return void
     */
    protected function sendSuccess($data = null, string $message = '操作成功', array $meta = []): void
    {
        $this->apiResponse($data, $message, $meta)->send();
    }

    /**
     * 发送API错误响应
     *
     * @param int $code 错误码
     * @param string|null $message 消息
     * @param array $errors 错误详情
     * @return void
     */
    protected function sendError(int $code, ?string $message = null, array $errors = []): void
    {
        ApiResponse::error($code, $message, $errors)->send();
    }

    /**
     * 发送分页响应
     *
     * @param array $items 数据项
     * @param int $total 总数
     * @param int $page 当前页
     * @param int $perPage 每页数量
     * @param string $message 消息
     * @return void
     */
    protected function sendPaginated(array $items, int $total, int $page, int $perPage, string $message = '获取成功'): void
    {
        FrontendDataService::createPaginatedResponse($items, $total, $page, $perPage, $message)->send();
    }

    /**
     * 验证输入数据
     *
     * @param array $data 输入数据
     * @param array $rules 验证规则
     * @return array ['valid' => bool, 'data' => array, 'errors' => array]
     */
    protected function validateInput(array $data, array $rules): array
    {
        return FrontendDataService::validateInput($data, $rules);
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

    /**
     * 解析模板变量
     * 使用TemplateVariableResolver统一处理模板变量
     *
     * @param string $content 模板内容
     * @param array $variables 变量
     * @param array $options 选项
     * @return string
     */
    protected function resolveTemplate(string $content, array $variables = [], array $options = []): string
    {
        return TemplateVariableResolver::resolve($content, $variables, $options);
    }
}
