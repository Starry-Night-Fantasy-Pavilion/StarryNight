<?php

declare(strict_types=1);

namespace app\services;

/**
 * 输入验证器
 * 
 * 提供输入验证和过滤功能，确保数据安全性
 * PHP 8.0+ 兼容版本
 */
class InputValidator
{
    /**
     * @var array<string, string> 验证错误信息
     */
    private array $errors = [];

    /**
     * 验证数据
     *
     * @param array $data 要验证的数据
     * @param array $rules 验证规则
     * @return bool
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;

            // 检查必填
            if (isset($rule['required']) && $rule['required']) {
                if (empty($value) && $value !== '0' && $value !== 0) {
                    $this->errors[$field] = $rule['message'] ?? "{$field} 是必填项";
                    continue;
                }
            }

            // 如果值为空且不是必填，跳过其他验证
            if (empty($value) && $value !== '0' && $value !== 0) {
                continue;
            }

            // 类型验证
            if (isset($rule['type'])) {
                if (!$this->validateType($value, $rule['type'])) {
                    $this->errors[$field] = $rule['message'] ?? "{$field} 类型不正确";
                    continue;
                }
            }

            // 正则验证
            if (isset($rule['pattern'])) {
                if (!preg_match($rule['pattern'], (string)$value)) {
                    $this->errors[$field] = $rule['message'] ?? "{$field} 格式不正确";
                    continue;
                }
            }

            // 最小长度验证
            if (isset($rule['min'])) {
                if (is_string($value) && mb_strlen($value) < $rule['min']) {
                    $this->errors[$field] = $rule['message'] ?? "{$field} 长度不能少于 {$rule['min']}";
                    continue;
                }
                if (is_numeric($value) && $value < $rule['min']) {
                    $this->errors[$field] = $rule['message'] ?? "{$field} 不能小于 {$rule['min']}";
                    continue;
                }
            }

            // 最大长度验证
            if (isset($rule['max'])) {
                if (is_string($value) && mb_strlen($value) > $rule['max']) {
                    $this->errors[$field] = $rule['message'] ?? "{$field} 长度不能超过 {$rule['max']}";
                    continue;
                }
                if (is_numeric($value) && $value > $rule['max']) {
                    $this->errors[$field] = $rule['message'] ?? "{$field} 不能大于 {$rule['max']}";
                    continue;
                }
            }

            // 枚举验证
            if (isset($rule['enum'])) {
                if (!in_array($value, $rule['enum'], true)) {
                    $this->errors[$field] = $rule['message'] ?? "{$field} 值不在允许范围内";
                    continue;
                }
            }

            // 自定义验证函数
            if (isset($rule['custom']) && is_callable($rule['custom'])) {
                if (!call_user_func($rule['custom'], $value, $data)) {
                    $this->errors[$field] = $rule['message'] ?? "{$field} 验证失败";
                    continue;
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * 验证类型
     *
     * @param mixed $value 值
     * @param string $type 类型
     * @return bool
     */
    private function validateType($value, string $type): bool
    {
        return match ($type) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'ip' => filter_var($value, FILTER_VALIDATE_IP) !== false,
            'int', 'integer' => is_numeric($value) && (int)$value == $value,
            'float', 'double' => is_numeric($value) && (float)$value == $value,
            'bool', 'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false', 'on', 'off'], true),
            'string' => is_string($value),
            'array' => is_array($value),
            'date' => strtotime((string)$value) !== false,
            'json' => $this->isValidJson($value),
            default => true,
        };
    }

    /**
     * 检查是否为有效的JSON
     *
     * @param mixed $value
     * @return bool
     */
    private function isValidJson($value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * 获取验证错误
     *
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 获取第一个错误
     *
     * @return string|null
     */
    public function getFirstError(): ?string
    {
        return $this->errors ? reset($this->errors) : null;
    }

    /**
     * 过滤数据
     *
     * @param mixed $data 数据
     * @return mixed
     */
    public function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }

        if (is_string($data)) {
            // 去除HTML标签
            $data = strip_tags($data);
            // 转义特殊字符
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
            // 去除首尾空白
            $data = trim($data);
        }

        return $data;
    }

    /**
     * 过滤HTML
     *
     * @param string $html HTML内容
     * @param array<int, string> $allowedTags 允许的标签
     * @return string
     */
    public function sanitizeHtml(string $html, array $allowedTags = []): string
    {
        if (empty($allowedTags)) {
            return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
        }

        $allowed = '<' . implode('><', $allowedTags) . '>';
        return strip_tags($html, $allowed);
    }

    /**
     * 验证邮箱
     *
     * @param string $email 邮箱地址
     * @return bool
     */
    public static function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * 验证URL
     *
     * @param string $url URL地址
     * @return bool
     */
    public static function isUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * 验证IP地址
     *
     * @param string $ip IP地址
     * @return bool
     */
    public static function isIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * 验证手机号（中国大陆）
     *
     * @param string $phone 手机号
     * @return bool
     */
    public static function isPhone(string $phone): bool
    {
        return preg_match('/^1[3-9]\d{9}$/', $phone) === 1;
    }

    /**
     * 验证身份证号码
     *
     * @param string $idCard 身份证号码
     * @return bool
     */
    public static function isIdCard(string $idCard): bool
    {
        return preg_match('/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/', $idCard) === 1;
    }

    /**
     * 验证UUID
     *
     * @param string $uuid UUID字符串
     * @return bool
     */
    public static function isUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }

    /**
     * 验证日期格式
     *
     * @param string $date 日期字符串
     * @param string $format 日期格式
     * @return bool
     */
    public static function isDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * 统一错误处理
     */
    protected function handleError(\Exception $e, $operation = '') {
        $errorMessage = $operation ? $operation . '失败: ' . $e->getMessage() : $e->getMessage();
        
        // 记录错误日志
        error_log('Service Error: ' . $errorMessage);
        
        // 抛出自定义异常
        throw new \Exception($errorMessage, $e->getCode(), $e);
    }
}
