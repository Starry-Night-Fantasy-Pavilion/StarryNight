<?php

declare(strict_types=1);

namespace app\services;

use Core\Api\ApiResponse;

/**
 * 前端UI数据绑定服务类
 * 
 * 提供统一的前端数据绑定、格式化、验证等功能
 * 优化前端UI与后端类的交互设计
 */
class FrontendDataService
{
    /**
     * 准备视图数据
     * 统一处理传递给视图的数据，包括默认值、格式化等
     *
     * @param array $data 原始数据
     * @param array $defaults 默认值
     * @return array 处理后的数据
     */
    public static function prepareViewData(array $data = [], array $defaults = []): array
    {
        // 合并默认值
        $merged = array_merge($defaults, $data);
        
        // 添加全局默认值
        $merged['site_name'] = $merged['site_name'] ?? (string)get_env('APP_NAME', '星夜阁');
        $merged['site_url'] = $merged['site_url'] ?? (string)get_env('APP_URL', '/');
        $merged['current_year'] = $merged['current_year'] ?? date('Y');
        $merged['current_time'] = $merged['current_time'] ?? date('Y-m-d H:i:s');
        
        // 处理用户信息
        if (isset($_SESSION['user_id'])) {
            $merged['user_id'] = $_SESSION['user_id'];
            $merged['username'] = $merged['username'] ?? ($_SESSION['username'] ?? '');
            $merged['user_logged_in'] = true;
        } else {
            $merged['user_logged_in'] = false;
        }
        
        // 处理分页信息
        if (isset($merged['pagination'])) {
            $merged['pagination'] = self::formatPagination($merged['pagination']);
        }
        
        return $merged;
    }

    /**
     * 格式化分页数据
     *
     * @param array $pagination 分页数据
     * @return array 格式化后的分页数据
     */
    public static function formatPagination(array $pagination): array
    {
        $defaults = [
            'page' => 1,
            'per_page' => 20,
            'total' => 0,
            'total_pages' => 0,
            'has_more' => false,
        ];
        
        $formatted = array_merge($defaults, $pagination);
        
        // 计算总页数
        if ($formatted['per_page'] > 0) {
            $formatted['total_pages'] = (int)ceil($formatted['total'] / $formatted['per_page']);
        }
        
        // 判断是否有更多页
        $formatted['has_more'] = ($formatted['page'] * $formatted['per_page']) < $formatted['total'];
        
        return $formatted;
    }

    /**
     * 准备API响应数据
     * 统一处理API响应的数据格式
     *
     * @param mixed $data 数据
     * @param array $meta 元数据
     * @return array 格式化后的响应数据
     */
    public static function prepareApiData($data = null, array $meta = []): array
    {
        $response = [];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }
        
        return $response;
    }

    /**
     * 格式化列表数据
     * 统一处理列表数据的格式
     *
     * @param array $items 列表项
     * @param callable|null $formatter 格式化回调函数
     * @return array 格式化后的列表
     */
    public static function formatListData(array $items, ?callable $formatter = null): array
    {
        if ($formatter === null) {
            return $items;
        }
        
        return array_map($formatter, $items);
    }

    /**
     * 验证和清理输入数据
     *
     * @param array $data 输入数据
     * @param array $rules 验证规则
     * @return array ['valid' => bool, 'data' => array, 'errors' => array]
     */
    public static function validateInput(array $data, array $rules): array
    {
        $errors = [];
        $cleaned = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // 必填验证
            if (isset($rule['required']) && $rule['required'] && ($value === null || $value === '')) {
                $errors[$field] = $rule['message'] ?? "字段 {$field} 是必填的";
                continue;
            }
            
            // 如果值为空且不是必填，跳过
            if ($value === null || $value === '') {
                if (isset($rule['default'])) {
                    $cleaned[$field] = $rule['default'];
                }
                continue;
            }
            
            // 类型验证
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'int':
                        $value = filter_var($value, FILTER_VALIDATE_INT);
                        if ($value === false) {
                            $errors[$field] = $rule['message'] ?? "字段 {$field} 必须是整数";
                            continue 2;
                        }
                        break;
                    case 'float':
                        $value = filter_var($value, FILTER_VALIDATE_FLOAT);
                        if ($value === false) {
                            $errors[$field] = $rule['message'] ?? "字段 {$field} 必须是数字";
                            continue 2;
                        }
                        break;
                    case 'email':
                        $value = filter_var($value, FILTER_VALIDATE_EMAIL);
                        if ($value === false) {
                            $errors[$field] = $rule['message'] ?? "字段 {$field} 必须是有效的邮箱地址";
                            continue 2;
                        }
                        break;
                    case 'url':
                        $value = filter_var($value, FILTER_VALIDATE_URL);
                        if ($value === false) {
                            $errors[$field] = $rule['message'] ?? "字段 {$field} 必须是有效的URL";
                            continue 2;
                        }
                        break;
                    case 'string':
                        $value = (string)$value;
                        if (isset($rule['max_length']) && mb_strlen($value) > $rule['max_length']) {
                            $errors[$field] = $rule['message'] ?? "字段 {$field} 长度不能超过 {$rule['max_length']} 个字符";
                            continue 2;
                        }
                        if (isset($rule['min_length']) && mb_strlen($value) < $rule['min_length']) {
                            $errors[$field] = $rule['message'] ?? "字段 {$field} 长度不能少于 {$rule['min_length']} 个字符";
                            continue 2;
                        }
                        break;
                }
            }
            
            // 自定义验证
            if (isset($rule['validator']) && is_callable($rule['validator'])) {
                $result = $rule['validator']($value);
                if ($result !== true) {
                    $errors[$field] = is_string($result) ? $result : ($rule['message'] ?? "字段 {$field} 验证失败");
                    continue;
                }
            }
            
            $cleaned[$field] = $value;
        }
        
        return [
            'valid' => empty($errors),
            'data' => $cleaned,
            'errors' => $errors,
        ];
    }

    /**
     * 创建统一的API响应
     *
     * @param mixed $data 数据
     * @param string $message 消息
     * @param array $meta 元数据
     * @return ApiResponse
     */
    public static function createApiResponse($data = null, string $message = '操作成功', array $meta = []): ApiResponse
    {
        $response = ApiResponse::success($data, $message);
        
        if (!empty($meta)) {
            $response->meta($meta);
        }
        
        return $response;
    }

    /**
     * 创建分页API响应
     *
     * @param array $items 数据项
     * @param int $total 总数
     * @param int $page 当前页
     * @param int $perPage 每页数量
     * @param string $message 消息
     * @return ApiResponse
     */
    public static function createPaginatedResponse(
        array $items,
        int $total,
        int $page,
        int $perPage,
        string $message = '获取成功'
    ): ApiResponse {
        return ApiResponse::paginated($items, $total, $page, $perPage, $message);
    }

    /**
     * 转义HTML输出
     *
     * @param mixed $value 值
     * @param bool $doubleEncode 是否双重编码
     * @return string
     */
    public static function escapeHtml($value, bool $doubleEncode = true): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }

    /**
     * 格式化日期时间
     *
     * @param string|int $datetime 日期时间
     * @param string $format 格式
     * @return string
     */
    public static function formatDateTime($datetime, string $format = 'Y-m-d H:i:s'): string
    {
        if (is_numeric($datetime)) {
            return date($format, (int)$datetime);
        }
        
        $timestamp = strtotime((string)$datetime);
        if ($timestamp === false) {
            return (string)$datetime;
        }
        
        return date($format, $timestamp);
    }

    /**
     * 格式化文件大小
     *
     * @param int $bytes 字节数
     * @param int $precision 精度
     * @return string
     */
    public static function formatFileSize(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
