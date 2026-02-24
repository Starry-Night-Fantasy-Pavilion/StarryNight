<?php

declare(strict_types=1);

namespace api\controllers;

use app\services\ApiResponse;
use Core\Exceptions\ErrorCode;
use Core\Security\CsrfMiddleware;

/**
 * API基础控制器
 * 提供通用的API响应方法和认证检查
 * 整合统一错误码体系和CSRF防护
 */
abstract class BaseApiController
{
    /**
     * 设置JSON响应头
     */
    protected function setJsonHeader(): void
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * 获取当前登录用户ID
     * @return int|null 用户ID，未登录返回null
     */
    protected function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * 检查用户是否已登录
     * 如果未登录，自动返回错误响应并终止执行
     * @return int 用户ID
     */
    protected function requireAuth(): int
    {
        $userId = $this->getUserId();
        if (!$userId) {
            ApiResponse::sendError(ErrorCode::AUTH_LOGIN_REQUIRED);
        }
        return (int)$userId;
    }

    /**
     * 检查用户是否有权限操作指定小说
     * @param int $novelId 小说ID
     * @return array 小说数据
     */
    protected function requireNovelAccess(int $novelId): array
    {
        $userId = $this->requireAuth();
        $novel = \app\models\Novel::find($novelId);
        
        if (!$novel || $novel['user_id'] != $userId) {
            ApiResponse::sendError(ErrorCode::NOVEL_ACCESS_DENIED);
        }
        
        return $novel;
    }

    /**
     * 检查用户是否有权限操作指定章节
     * @param int $chapterId 章节ID
     * @return array 包含章节和小说数据的数组
     */
    protected function requireChapterAccess(int $chapterId): array
    {
        $userId = $this->requireAuth();
        $chapter = \app\models\NovelChapter::find($chapterId);
        
        if (!$chapter) {
            ApiResponse::sendError(ErrorCode::NOVEL_CHAPTER_NOT_FOUND);
        }
        
        $novel = \app\models\Novel::find($chapter['novel_id']);
        if (!$novel || $novel['user_id'] != $userId) {
            ApiResponse::sendError(ErrorCode::AUTH_PERMISSION_DENIED);
        }
        
        return ['chapter' => $chapter, 'novel' => $novel];
    }

    /**
     * 验证CSRF Token
     * @param bool $required 是否必须验证
     * @return bool 验证结果
     */
    protected function validateCsrf(bool $required = true): bool
    {
        $csrf = new CsrfMiddleware();
        $token = $this->getCsrfToken();
        
        if (empty($token)) {
            if ($required) {
                ApiResponse::sendError(ErrorCode::SECURITY_CSRF_TOKEN_MISMATCH);
            }
            return false;
        }
        
        if (!CsrfMiddleware::validate($token)) {
            if ($required) {
                ApiResponse::sendError(ErrorCode::SECURITY_CSRF_TOKEN_MISMATCH);
            }
            return false;
        }
        
        return true;
    }

    /**
     * 获取请求中的CSRF Token
     * @return string|null
     */
    protected function getCsrfToken(): ?string
    {
        // 从Header获取
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        if (isset($headers['X-CSRF-TOKEN'])) {
            return $headers['X-CSRF-TOKEN'];
        }
        if (isset($headers['x-csrf-token'])) {
            return $headers['x-csrf-token'];
        }
        
        // 从POST获取
        if (isset($_POST['csrf_token'])) {
            return $_POST['csrf_token'];
        }
        
        // 从JSON body获取
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $body = file_get_contents('php://input');
            $data = json_decode($body, true);
            if (isset($data['csrf_token'])) {
                return $data['csrf_token'];
            }
        }
        
        return null;
    }

    /**
     * 返回成功响应
     * @param mixed $data 响应数据
     * @param string $message 成功消息
     */
    protected function success($data = null, string $message = '操作成功'): void
    {
        ApiResponse::sendSuccess($data, $message);
    }

    /**
     * 返回错误响应（使用错误码）
     * @param int $code 错误码
     * @param string|null $customMessage 自定义错误消息
     * @param array $data 附加数据
     */
    protected function errorCode(int $code, ?string $customMessage = null, array $data = []): void
    {
        ApiResponse::sendError($code, $customMessage, $data);
    }

    /**
     * 返回错误响应（兼容旧方法）
     * @param string $error 错误信息
     * @param int $code HTTP状态码
     */
    protected function error(string $error, int $code = 400): void
    {
        $this->setJsonHeader();
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $error,
            'message' => '操作失败'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 返回分页数据
     * @param array $items 数据项
     * @param int $total 总数
     * @param int $page 当前页
     * @param int $pageSize 每页数量
     * @param string $message 消息
     */
    protected function paginated(array $items, int $total, int $page, int $pageSize, string $message = '获取成功'): void
    {
        ApiResponse::sendPaginated($items, $total, $page, $pageSize, $message);
    }

    /**
     * 获取请求参数
     * @param string $key 参数名
     * @param mixed $default 默认值
     * @param string $type 参数类型 (post/get)
     * @return mixed
     */
    protected function input(string $key, $default = null, string $type = 'post')
    {
        $source = $type === 'get' ? $_GET : $_POST;
        return $source[$key] ?? $default;
    }

    /**
     * 获取整数类型参数
     * @param string $key 参数名
     * @param int $default 默认值
     * @param string $type 参数类型 (post/get)
     * @return int
     */
    protected function inputInt(string $key, int $default = 0, string $type = 'post'): int
    {
        return (int)$this->input($key, $default, $type);
    }

    /**
     * 获取POST参数
     */
    protected function post(string $key, $default = null)
    {
        return $this->input($key, $default, 'post');
    }

    /**
     * 获取GET参数
     */
    protected function get(string $key, $default = null)
    {
        return $this->input($key, $default, 'get');
    }

    /**
     * 获取POST参数（整数类型）
     */
    protected function postInt(string $key, int $default = 0): int
    {
        return $this->inputInt($key, $default, 'post');
    }

    /**
     * 获取GET参数（整数类型）
     */
    protected function getInt(string $key, int $default = 0): int
    {
        return $this->inputInt($key, $default, 'get');
    }

    /**
     * 验证文件上传
     * @param string $fieldName 文件字段名
     * @return array 文件信息
     */
    protected function validateUpload(string $fieldName = 'file'): array
    {
        if (!isset($_FILES[$fieldName])) {
            $this->error('没有上传文件');
        }
        return $_FILES[$fieldName];
    }

    /**
     * 获取上传路径
     */
    protected function getUploadPath(int $userId, string $subPath = ''): string
    {
        $basePath = $this->getStorageBasePath();
        $path = $basePath . '/uploads/users/' . $userId;
        return $subPath ? $path . '/' . trim($subPath, '/') : $path;
    }

    /**
     * 获取存储基础路径
     */
    protected function getStorageBasePath(): string
    {
        $config = \app\models\StorageConfig::getActive();
        if ($config && $config['storage_type'] === 'local') {
            $configValue = json_decode($config['config_value'], true);
            return $configValue['base_path'] ?? '/data';
        }
        return '/data';
    }
}
