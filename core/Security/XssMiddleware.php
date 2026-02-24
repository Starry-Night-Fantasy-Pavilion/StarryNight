<?php
/**
 * XSS安全中间件
 * 
 * @package Core\Security
 * @version 1.0.0
 */

namespace Core\Security;

use Core\Exceptions\XssException;

/**
 * XSS防护类
 */
class XssMiddleware
{
    /**
     * 危险标签
     */
    protected array $dangerousTags = [
        'script', 'iframe', 'object', 'embed', 'applet',
        'meta', 'link', 'style', 'base', 'form',
    ];

    /**
     * 危险属性
     */
    protected array $dangerousAttributes = [
        'onload', 'onerror', 'onclick', 'onmouseover', 'onmouseout',
        'onkeydown', 'onkeyup', 'onkeypress', 'onfocus', 'onblur',
        'onsubmit', 'onreset', 'onchange', 'oninput', 'onselect',
        'ondrag', 'ondrop', 'onscroll', 'onwheel', 'oncopy',
        'oncut', 'onpaste', 'oncontextmenu', 'onresize',
    ];

    /**
     * 危险协议
     */
    protected array $dangerousProtocols = [
        'javascript:', 'vbscript:', 'data:text/html',
        'data:application/javascript', 'data:application/x-javascript',
    ];

    /**
     * 是否严格模式（检测到XSS直接抛异常）
     */
    protected bool $strictMode = false;

    /**
     * 是否自动清理
     */
    protected bool $autoClean = true;

    /**
     * 需要检测的字段
     */
    protected array $checkFields = [];

    /**
     * 构造函数
     */
    public function __construct(array $config = [])
    {
        if (isset($config['strict_mode'])) {
            $this->strictMode = $config['strict_mode'];
        }
        if (isset($config['auto_clean'])) {
            $this->autoClean = $config['auto_clean'];
        }
        if (isset($config['check_fields'])) {
            $this->checkFields = $config['check_fields'];
        }
        if (isset($config['dangerous_tags'])) {
            $this->dangerousTags = array_merge($this->dangerousTags, $config['dangerous_tags']);
        }
        if (isset($config['dangerous_attributes'])) {
            $this->dangerousAttributes = array_merge($this->dangerousAttributes, $config['dangerous_attributes']);
        }
    }

    /**
     * 处理请求
     */
    public function handle(): void
    {
        // 检查GET参数
        $this->checkArray($_GET, 'GET');

        // 检查POST参数
        $this->checkArray($_POST, 'POST');

        // 检查JSON body
        $this->checkJsonBody();

        // 自动清理
        if ($this->autoClean) {
            $_GET = $this->cleanArray($_GET);
            $_POST = $this->cleanArray($_POST);
            $_REQUEST = $this->cleanArray($_REQUEST);
        }
    }

    /**
     * 检查数组
     */
    protected function checkArray(array &$data, string $source): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->checkArray($value, $source);
            } elseif (is_string($value)) {
                if ($this->detectXss($value)) {
                    if ($this->strictMode) {
                        throw new XssException("检测到潜在的XSS攻击: {$source}[{$key}]");
                    }
                    // 记录日志
                    error_log("XSS检测: {$source}[{$key}] = " . substr($value, 0, 100));
                }
            }
        }
    }

    /**
     * 检查JSON body
     */
    protected function checkJsonBody(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') === false) {
            return;
        }

        $body = file_get_contents('php://input');
        if (empty($body)) {
            return;
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            return;
        }

        $this->checkArray($data, 'JSON');
    }

    /**
     * 检测XSS
     */
    public function detectXss(string $input): bool
    {
        // 转换为小写进行检测
        $lowerInput = strtolower($input);

        // 检测危险标签
        foreach ($this->dangerousTags as $tag) {
            $patterns = [
                '#<' . $tag . '[\s/>]#i',
                '#<' . $tag . '\s*>#i',
                '#</' . $tag . '>#i',
            ];
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $input)) {
                    return true;
                }
            }
        }

        // 检测危险属性
        foreach ($this->dangerousAttributes as $attr) {
            if (preg_match('#' . $attr . '\s*=#i', $input)) {
                return true;
            }
        }

        // 检测危险协议
        foreach ($this->dangerousProtocols as $protocol) {
            if (stripos($lowerInput, $protocol) !== false) {
                return true;
            }
        }

        // 检测编码绕过尝试
        if ($this->detectEncodingBypass($input)) {
            return true;
        }

        // 检测HTML实体编码攻击
        if ($this->detectHtmlEntityAttack($input)) {
            return true;
        }

        return false;
    }

    /**
     * 检测编码绕过
     */
    protected function detectEncodingBypass(string $input): bool
    {
        // 检测URL编码
        if (preg_match('#%[0-9a-fA-F]{2}#', $input)) {
            $decoded = urldecode($input);
            if ($decoded !== $input && $this->detectXss($decoded)) {
                return true;
            }
        }

        // 检测HTML实体编码
        if (preg_match('#&#[0-9]+;#', $input) || preg_match('#&#[xX][0-9a-fA-F]+;#', $input)) {
            $decoded = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded !== $input && $this->detectXss($decoded)) {
                return true;
            }
        }

        // 检测Base64编码（在属性中）
        if (preg_match('#base64[,\s]*[A-Za-z0-9+/=]+#i', $input)) {
            return true;
        }

        return false;
    }

    /**
     * 检测HTML实体攻击
     */
    protected function detectHtmlEntityAttack(string $input): bool
    {
        // 检测不带分号的HTML实体
        if (preg_match('#&#x?[0-9a-fA-F]+[^;]#i', $input)) {
            return true;
        }

        // 检测Unicode编码
        if (preg_match('#\\u[0-9a-fA-F]{4}#i', $input)) {
            return true;
        }

        return false;
    }

    /**
     * 清理输入
     */
    public function clean(string $input): string
    {
        // 移除NULL字节
        $input = str_replace("\0", '', $input);

        // 移除危险标签
        foreach ($this->dangerousTags as $tag) {
            $input = preg_replace('#<' . $tag . '[^>]*>.*?</' . $tag . '>#is', '', $input);
            $input = preg_replace('#<' . $tag . '[^>]*/?>#is', '', $input);
            $input = preg_replace('#</' . $tag . '>#is', '', $input);
        }

        // 移除危险属性
        foreach ($this->dangerousAttributes as $attr) {
            $input = preg_replace('#\s+' . $attr . '\s*=\s*["\'][^"\']*["\']#is', '', $input);
            $input = preg_replace('#\s+' . $attr . '\s*=\s*[^\s>]+#is', '', $input);
        }

        // 移除危险协议
        foreach ($this->dangerousProtocols as $protocol) {
            $input = preg_replace('#' . preg_quote($protocol, '#') . '#is', '', $input);
        }

        // HTML转义
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $input;
    }

    /**
     * 清理数组
     */
    public function cleanArray(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $key = $this->clean($key);
            if (is_array($value)) {
                $result[$key] = $this->cleanArray($value);
            } elseif (is_string($value)) {
                $result[$key] = $this->clean($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * 安全的HTML输出
     */
    public static function escape(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * 安全的属性输出
     */
    public static function escapeAttribute(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * 安全的JavaScript输出
     */
    public static function escapeJs(string $input): string
    {
        return json_encode($input, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    /**
     * 安全的URL输出
     */
    public static function escapeUrl(string $input): string
    {
        return rawurlencode($input);
    }

    /**
     * 安全的CSS输出
     */
    public static function escapeCss(string $input): string
    {
        return preg_replace('/[^a-zA-Z0-9\-_]/', '', $input);
    }

    /**
     * 过滤HTML标签（保留安全标签）
     */
    public static function filterHtml(string $input, array $allowedTags = ['p', 'br', 'strong', 'em', 'u', 'a', 'img', 'ul', 'ol', 'li', 'blockquote', 'code', 'pre']): string
    {
        // 先完全清理
        $cleaned = strip_tags($input, '<' . implode('><', $allowedTags) . '>');

        // 移除所有事件处理器
        $cleaned = preg_replace('#\s+on\w+\s*=\s*["\'][^"\']*["\']#is', '', $cleaned);
        $cleaned = preg_replace('#\s+on\w+\s*=\s*[^\s>]+#is', '', $cleaned);

        // 移除javascript协议
        $cleaned = preg_replace('#href\s*=\s*["\']?\s*javascript:[^"\'>\s]*#is', 'href="#"', $cleaned);
        $cleaned = preg_replace('#src\s*=\s*["\']?\s*javascript:[^"\'>\s]*#is', '', $cleaned);

        return $cleaned;
    }

    /**
     * 验证URL是否安全
     */
    public static function isSafeUrl(string $url): bool
    {
        // 解析URL
        $parsed = parse_url($url);
        if ($parsed === false) {
            return false;
        }

        // 检查协议
        $scheme = $parsed['scheme'] ?? '';
        $allowedSchemes = ['http', 'https', 'mailto', 'tel', 'ftp'];
        if (!empty($scheme) && !in_array(strtolower($scheme), $allowedSchemes, true)) {
            return false;
        }

        // 检查是否有javascript协议
        if (preg_match('#^\s*javascript:#i', $url)) {
            return false;
        }

        return true;
    }
}
