<?php

declare(strict_types=1);

namespace app\services;

/**
 * 模板变量解析器
 * 
 * 统一处理各种模板变量格式（{{variable}}、{$variable}等）
 * 提供统一的变量替换机制
 */
class TemplateVariableResolver
{
    /**
     * 解析模板内容并替换变量
     *
     * @param string $content 模板内容
     * @param array $variables 变量数组
     * @param array $options 选项
     * @return string 解析后的内容
     */
    public static function resolve(string $content, array $variables = [], array $options = []): string
    {
        // 合并默认变量
        $defaults = self::getDefaultVariables();
        $variables = array_merge($defaults, $variables);
        
        // 处理username特殊逻辑
        if (isset($variables['username']) && !empty($variables['username'])) {
            $variables['username'] = '，<strong style="color: #667eea;">' . 
                htmlspecialchars((string)$variables['username'], ENT_QUOTES, 'UTF-8') . '</strong>';
        } else {
            $variables['username'] = '';
        }
        
        // 支持的模板格式
        $formats = $options['formats'] ?? ['double_brace', 'single_brace', 'dollar'];
        
        // 处理双花括号格式: {{variable}}
        if (in_array('double_brace', $formats)) {
            $content = self::replaceDoubleBrace($content, $variables);
        }
        
        // 处理单花括号格式: {variable}
        if (in_array('single_brace', $formats)) {
            $content = self::replaceSingleBrace($content, $variables);
        }
        
        // 处理美元符号格式: {$variable}
        if (in_array('dollar', $formats)) {
            $content = self::replaceDollar($content, $variables);
        }
        
        return $content;
    }

    /**
     * 替换双花括号格式变量 {{variable}}
     *
     * @param string $content 内容
     * @param array $variables 变量
     * @return string
     */
    private static function replaceDoubleBrace(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content = str_replace($placeholder, (string)$value, $content);
        }
        
        return $content;
    }

    /**
     * 替换单花括号格式变量 {variable}
     *
     * @param string $content 内容
     * @param array $variables 变量
     * @return string
     */
    private static function replaceSingleBrace(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $placeholder = '{' . $key . '}';
            $content = str_replace($placeholder, (string)$value, $content);
        }
        
        return $content;
    }

    /**
     * 替换美元符号格式变量 {$variable}
     *
     * @param string $content 内容
     * @param array $variables 变量
     * @return string
     */
    private static function replaceDollar(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $placeholder = '{$' . $key . '}';
            $content = str_replace($placeholder, (string)$value, $content);
        }
        
        return $content;
    }

    /**
     * 获取默认变量
     *
     * @return array
     */
    public static function getDefaultVariables(): array
    {
        return [
            'site_name' => (string)get_env('APP_NAME', '星夜阁'),
            'site_url' => (string)get_env('APP_URL', '/'),
            'current_year' => date('Y'),
            'current_time' => date('Y-m-d H:i:s'),
            'version' => (string)get_env('APP_VERSION', '1.0.0'),
        ];
    }

    /**
     * 从上下文获取变量
     * 自动从SESSION、GET、POST等获取常用变量
     *
     * @return array
     */
    public static function getContextVariables(): array
    {
        $variables = self::getDefaultVariables();
        
        // 从SESSION获取用户信息
        if (isset($_SESSION['user_id'])) {
            $variables['user_id'] = $_SESSION['user_id'];
            $variables['username'] = $_SESSION['username'] ?? '';
        }
        
        return $variables;
    }

    /**
     * 解析邮件模板
     * 专门用于邮件模板的变量替换
     *
     * @param string $content 模板内容
     * @param array $placeholders 占位符
     * @return string
     */
    public static function resolveEmailTemplate(string $content, array $placeholders = []): string
    {
        return self::resolve($content, $placeholders, ['formats' => ['double_brace']]);
    }

    /**
     * 解析视图模板
     * 专门用于视图模板的变量替换
     *
     * @param string $content 模板内容
     * @param array $variables 变量
     * @return string
     */
    public static function resolveViewTemplate(string $content, array $variables = []): string
    {
        return self::resolve($content, $variables, ['formats' => ['dollar', 'single_brace']]);
    }

    /**
     * 提取模板中的变量名
     *
     * @param string $content 模板内容
     * @param string $format 格式类型
     * @return array 变量名数组
     */
    public static function extractVariables(string $content, string $format = 'double_brace'): array
    {
        $variables = [];
        
        switch ($format) {
            case 'double_brace':
                preg_match_all('/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/', $content, $matches);
                break;
            case 'single_brace':
                preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $content, $matches);
                break;
            case 'dollar':
                preg_match_all('/\{\$([a-zA-Z_][a-zA-Z0-9_]*)\}/', $content, $matches);
                break;
            default:
                return [];
        }
        
        if (isset($matches[1])) {
            $variables = array_unique($matches[1]);
        }
        
        return $variables;
    }

    /**
     * 验证模板变量是否完整
     *
     * @param string $content 模板内容
     * @param array $variables 提供的变量
     * @param string $format 格式类型
     * @return array ['valid' => bool, 'missing' => array]
     */
    public static function validateVariables(string $content, array $variables, string $format = 'double_brace'): array
    {
        $required = self::extractVariables($content, $format);
        $provided = array_keys($variables);
        $missing = array_diff($required, $provided);
        
        return [
            'valid' => empty($missing),
            'missing' => array_values($missing),
        ];
    }
}
