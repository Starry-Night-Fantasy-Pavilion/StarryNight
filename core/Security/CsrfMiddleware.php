<?php
/**
 * CSRF安全中间件
 * 
 * @package Core\Security
 * @version 1.0.0
 */

namespace Core\Security;

use Core\Exceptions\CsrfException;

/**
 * CSRF保护类
 */
class CsrfMiddleware
{
    /**
     * Token名称
     */
    protected string $tokenName = '_csrf_token';

    /**
     * Header名称
     */
    protected string $headerName = 'X-CSRF-TOKEN';

    /**
     * Token长度
     */
    protected int $tokenLength = 32;

    /**
     * Token过期时间（秒）
     */
    protected int $tokenTtl = 3600;

    /**
     * 不需要验证的方法
     */
    protected array $safeMethods = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * 不需要验证的路由
     */
    protected array $exceptRoutes = [];

    /**
     * 构造函数
     */
    public function __construct(array $config = [])
    {
        if (isset($config['token_name'])) {
            $this->tokenName = $config['token_name'];
        }
        if (isset($config['header_name'])) {
            $this->headerName = $config['header_name'];
        }
        if (isset($config['token_length'])) {
            $this->tokenLength = $config['token_length'];
        }
        if (isset($config['token_ttl'])) {
            $this->tokenTtl = $config['token_ttl'];
        }
        if (isset($config['except_routes'])) {
            $this->exceptRoutes = $config['except_routes'];
        }
    }

    /**
     * 处理请求
     */
    public function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // 安全方法不需要验证
        if (in_array($method, $this->safeMethods, true)) {
            return;
        }

        // 检查是否在排除路由中
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        foreach ($this->exceptRoutes as $route) {
            if (preg_match('#' . $route . '#', $uri)) {
                return;
            }
        }

        // 验证Token
        if (!$this->verifyToken()) {
            throw new CsrfException('CSRF验证失败，请刷新页面重试');
        }
    }

    /**
     * 验证CSRF Token
     */
    protected function verifyToken(): bool
    {
        $sessionToken = $this->getSessionToken();
        $requestToken = $this->getRequestToken();

        if (empty($sessionToken) || empty($requestToken)) {
            return false;
        }

        // 使用时间安全的比较
        return hash_equals($sessionToken, $requestToken);
    }

    /**
     * 获取Session中的Token
     */
    protected function getSessionToken(): ?string
    {
        if (!isset($_SESSION[$this->tokenName])) {
            return null;
        }

        $tokenData = $_SESSION[$this->tokenName];

        // 检查是否过期
        if (isset($tokenData['expires_at']) && $tokenData['expires_at'] < time()) {
            unset($_SESSION[$this->tokenName]);
            return null;
        }

        return $tokenData['token'] ?? null;
    }

    /**
     * 获取请求中的Token
     */
    protected function getRequestToken(): ?string
    {
        // 优先从Header获取
        $headers = getallheaders();
        if (isset($headers[$this->headerName])) {
            return $headers[$this->headerName];
        }
        if (isset($headers[strtolower($this->headerName)])) {
            return $headers[strtolower($this->headerName)];
        }

        // 从POST数据获取
        if (isset($_POST[$this->tokenName])) {
            return $_POST[$this->tokenName];
        }

        // 从JSON body获取
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $body = file_get_contents('php://input');
            $data = json_decode($body, true);
            if (isset($data[$this->tokenName])) {
                return $data[$this->tokenName];
            }
        }

        return null;
    }

    /**
     * 生成CSRF Token
     */
    public function generateToken(): string
    {
        $token = bin2hex(random_bytes($this->tokenLength));

        $_SESSION[$this->tokenName] = [
            'token' => $token,
            'created_at' => time(),
            'expires_at' => time() + $this->tokenTtl,
        ];

        return $token;
    }

    /**
     * 获取当前Token（如果不存在则生成）
     */
    public function getToken(): string
    {
        $sessionToken = $this->getSessionToken();
        if ($sessionToken !== null) {
            return $sessionToken;
        }
        return $this->generateToken();
    }

    /**
     * 刷新Token
     */
    public function refreshToken(): string
    {
        return $this->generateToken();
    }

    /**
     * 获取Token字段名
     */
    public function getTokenName(): string
    {
        return $this->tokenName;
    }

    /**
     * 获取Header名
     */
    public function getHeaderName(): string
    {
        return $this->headerName;
    }

    /**
     * 生成隐藏表单字段
     */
    public function field(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars($this->tokenName, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->getToken(), ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * 生成Meta标签
     */
    public function meta(): string
    {
        return sprintf(
            '<meta name="csrf-token" content="%s">',
            htmlspecialchars($this->getToken(), ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * 验证Token（静态方法，方便调用）
     */
    public static function validate(?string $token = null): bool
    {
        $csrf = new self();
        
        if ($token === null) {
            try {
                $csrf->handle();
                return true;
            } catch (CsrfException $e) {
                return false;
            }
        }

        $sessionToken = $csrf->getSessionToken();
        if (empty($sessionToken)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}
