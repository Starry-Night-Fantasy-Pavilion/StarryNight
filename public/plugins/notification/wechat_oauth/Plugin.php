<?php
/**
 * 微信登录插件
 * 通过微信开放平台进行第三方登录，使用OAuth2认证流程
 */

namespace Plugins\Notification\WechatOauth;

use Core\PluginBase;

// 防止类重复声明
if (class_exists(__NAMESPACE__ . '\Plugin')) {
    return;
}

class Plugin extends PluginBase
{
    private $authUrl = 'https://open.weixin.qq.com/connect/qrconnect';
    private $tokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token';
    private $userInfoUrl = 'https://api.weixin.qq.com/sns/userinfo';
    
    // 配置缓存
    private ?array $configCache = null;
    private int $configCacheTime = 0;
    private const CONFIG_CACHE_TTL = 300; // 5分钟缓存
    
    public function getInfo(): array
    {
        return [
            'plugin_id' => 'wechat_oauth',
            'name' => '微信登录',
            'version' => '1.0.0',
            'description' => '通过微信开放平台进行第三方登录，使用OAuth2认证流程',
            'author' => 'StarryNight Team',
            'website' => 'https://open.weixin.qq.com',
            'type' => 'thirdparty_login',
            'category' => 'oauth2'
        ];
    }
    
    public function install(): bool
    {
        // 创建登录日志数据表
        $schema = [
            'columns' => [
                'id' => 'INT UNSIGNED AUTO_INCREMENT',
                'user_id' => 'INT UNSIGNED DEFAULT NULL',
                'openid' => 'VARCHAR(255) DEFAULT NULL',
                'unionid' => 'VARCHAR(255) DEFAULT NULL',
                'nickname' => 'VARCHAR(100) DEFAULT NULL',
                'headimgurl' => 'VARCHAR(500) DEFAULT NULL',
                'access_token' => 'TEXT DEFAULT NULL',
                'refresh_token' => 'TEXT DEFAULT NULL',
                'expires_at' => 'INT UNSIGNED DEFAULT NULL',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                'ip_address' => 'VARCHAR(45) DEFAULT NULL'
            ],
            'primary' => 'id',
            'indexes' => ['user_id', 'openid', 'unionid', 'expires_at'],
            'unique_indexes' => []
        ];
        
        if (!$this->createTable('wechat_oauth_logins', $schema)) {
            $this->log('error', '创建微信登录数据表失败');
            return false;
        }
        
        $this->log('info', '微信登录插件安装成功');
        return true;
    }
    
    public function uninstall(): bool
    {
        // 删除登录日志数据表
        if (!$this->dropTable('wechat_oauth_logins')) {
            $this->log('error', '删除微信登录数据表失败');
            return false;
        }
        
        $this->log('info', '微信登录插件卸载成功');
        return true;
    }
    
    public function enable(): bool
    {
        $this->log('info', '微信登录插件已启用');
        return true;
    }
    
    public function disable(): bool
    {
        $this->log('info', '微信登录插件已禁用');
        return true;
    }
    
    public function updateConfig(array $config): bool
    {
        return parent::updateConfig($config);
    }
    
    public function getConfigForm(): array
    {
        $parentForm = parent::getConfigForm();
        
        return array_merge($parentForm, [
            [
                'name' => 'app_id',
                'title' => '微信AppID',
                'type' => 'text',
                'default' => '',
                'required' => true,
                'description' => '从微信开放平台获取的AppID'
            ],
            [
                'name' => 'app_secret',
                'title' => '微信AppSecret',
                'type' => 'password',
                'default' => '',
                'required' => true,
                'description' => '从微信开放平台获取的AppSecret'
            ],
            [
                'name' => 'redirect_uri',
                'title' => '回调地址',
                'type' => 'text',
                'default' => '',
                'required' => true,
                'description' => 'OAuth2回调地址，需要在微信开放平台中注册'
            ],
            [
                'name' => 'scope',
                'title' => '授权范围',
                'type' => 'text',
                'default' => 'snsapi_login',
                'required' => false,
                'description' => 'OAuth2授权范围，默认为snsapi_login'
            ],
            [
                'name' => 'api_timeout',
                'title' => 'API超时时间',
                'type' => 'number',
                'default' => 10,
                'min' => 1,
                'max' => 60,
                'required' => false,
                'description' => 'API请求的超时时间（秒）'
            ]
        ]);
    }
    
    /**
     * 生成授权URL
     * @param array $state 状态参数
     * @return string 授权URL
     */
    public function getAuthorizationUrl(array $state = []): string
    {
        $config = $this->getConfig();
        
        if (empty($config['app_id']) || empty($config['redirect_uri'])) {
            throw new \Exception('微信开放平台配置不完整');
        }
        
        $params = [
            'appid' => $config['app_id'],
            'redirect_uri' => urlencode($config['redirect_uri']),
            'response_type' => 'code',
            'scope' => $config['scope'] ?? 'snsapi_login',
            'state' => urlencode(json_encode($state))
        ];
        
        return $this->authUrl . '?' . http_build_query($params) . '#wechat_redirect';
    }
    
    /**
     * 处理回调，获取访问令牌
     * @param string $code 授权码
     * @return array 令牌信息
     */
    public function getAccessToken(string $code): array
    {
        $config = $this->getCachedConfig();
        
        if (empty($config['app_id']) || empty($config['app_secret'])) {
            throw new \Exception('微信开放平台配置不完整');
        }
        
        $params = [
            'appid' => $config['app_id'],
            'secret' => $config['app_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code'
        ];
        
        $result = $this->makeHttpRequest($this->tokenUrl, $params, '获取访问令牌');
        
        if (empty($result['access_token'])) {
            $this->log('error', '响应中没有访问令牌');
            throw new \Exception('响应中没有访问令牌');
        }
        
        return $result;
    }
    
    /**
     * 获取用户信息
     * @param string $accessToken 访问令牌
     * @param string $openid OpenID
     * @return array 用户信息
     */
    public function getUserInfo(string $accessToken, string $openid): array
    {
        $params = [
            'access_token' => $accessToken,
            'openid' => $openid
        ];
        
        return $this->makeHttpRequest($this->userInfoUrl, $params, '获取用户信息');
    }
    
    /**
     * 处理登录流程
     * @param string $code 授权码
     * @return array 登录结果
     */
    public function handleLogin(string $code): array
    {
        try {
            // 获取访问令牌
            $tokenData = $this->getAccessToken($code);
            $accessToken = $tokenData['access_token'];
            $openid = $tokenData['openid'];
            
            // 获取用户信息
            $userInfo = $this->getUserInfo($accessToken, $openid);
            
            // 存储登录信息
            $loginData = [
                'openid' => $openid,
                'unionid' => $userInfo['unionid'] ?? '',
                'nickname' => $userInfo['nickname'] ?? '',
                'headimgurl' => $userInfo['headimgurl'] ?? '',
                'access_token' => $accessToken,
                'refresh_token' => $tokenData['refresh_token'] ?? '',
                'expires_at' => time() + ($tokenData['expires_in'] ?? 7200),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ];
            
            $this->storeLogin($loginData);
            
            return [
                'success' => true,
                'user' => $userInfo,
                'token' => $tokenData
            ];
        } catch (\Exception $e) {
            $this->log('error', '登录失败: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取登录按钮HTML
     * @param array $options 选项
     * @return string 登录按钮HTML
     */
    public function getLoginButton(array $options = []): string
    {
        try {
            $config = $this->getConfig();
            
            // 检查配置是否完整，如果不完整，显示提示但仍然渲染按钮
            $configIncomplete = empty($config['app_id']) || empty($config['app_secret']) || empty($config['redirect_uri']);
            
            if ($configIncomplete) {
                $authUrl = 'javascript:void(0)';
                $buttonText = '使用微信登录';
                $buttonClass = 'wechat-login-button disabled';
                $onclick = 'alert(\'请联系管理员配置微信登录参数\');';
            } else {
                $authUrl = $this->getAuthorizationUrl($options);
                $buttonText = '使用微信登录';
                $buttonClass = 'wechat-login-button';
                $onclick = '';
            }
            
            $pluginPath = $this->getPluginPath();
            
            // 查找logo图片，支持多种格式和路径（插件包内必须携带）
            $logoPaths = [
                $pluginPath . '/logo.png',
                $pluginPath . '/logo.jpg',
                $pluginPath . '/logo.svg',
                $pluginPath . '/assets/logo.png',
                $pluginPath . '/assets/logo.jpg',
                $pluginPath . '/assets/logo.svg',
                $pluginPath . '/assets/icons/logo.png',
                $pluginPath . '/assets/icons/logo.jpg',
            ];
            
            $logoUrl = '';
            $logoPath = '';
            foreach ($logoPaths as $path) {
                if (file_exists($path)) {
                    $logoPath = $path;
                    // 统一使用正斜杠处理路径，兼容 Windows 和 Unix 系统
                    $normalizedPluginPath = str_replace('\\', '/', $pluginPath);
                    $normalizedPath = str_replace('\\', '/', $path);
                    $relativePath = str_replace($normalizedPluginPath . '/', '', $normalizedPath);
                    $logoUrl = $this->getPluginAssetUrl($relativePath);
                    break;
                }
            }
            
            // 如果没有 logo 图片，使用占位符
            if (empty($logoUrl) || empty($logoPath)) {
                $logoUrl = 'data:image/svg+xml;base64,' . base64_encode('<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="12" fill="#07C160"/><path d="M12 6C8.69 6 6 8.69 6 12C6 15.31 8.69 18 12 18C15.31 18 18 15.31 18 12C18 8.69 15.31 6 12 6ZM12 8C9.79 8 8 9.79 8 12C8 14.21 9.79 16 12 16C14.21 16 16 14.21 16 12C16 9.79 14.21 8 12 8Z" fill="rgba(255,255,255,0.3)"/></svg>');
            }
            
            ob_start();
            ?>
            <a href="<?php echo htmlspecialchars($authUrl); ?>" class="<?php echo htmlspecialchars($buttonClass); ?>" <?php echo $onclick ? 'onclick="' . htmlspecialchars($onclick) . '"' : ''; ?>>
                <div class="login-button-content">
                    <div class="login-button-icon">
                        <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="微信" width="24" height="24" style="display: block;">
                    </div>
                    <div class="login-button-text"><?php echo htmlspecialchars($buttonText); ?></div>
                </div>
            </a>
            <style>
                .wechat-login-button {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 100%;
                    background: linear-gradient(135deg, rgba(9, 187, 7, 0.15), rgba(9, 187, 7, 0.08));
                    border: 1px solid rgba(9, 187, 7, 0.3);
                    border-radius: 12px;
                    padding: 14px 20px;
                    text-decoration: none;
                    transition: all 0.3s ease;
                    font-size: 15px;
                    font-weight: 500;
                    gap: 12px;
                    color: #fff;
                }
                
                .wechat-login-button:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
                    background: linear-gradient(135deg, rgba(9, 187, 7, 0.25), rgba(9, 187, 7, 0.12));
                    border-color: rgba(9, 187, 7, 0.5);
                }
                
                .wechat-login-button.disabled {
                    opacity: 0.6;
                    cursor: pointer;
                }
                
                .wechat-login-button.disabled:hover {
                    transform: none;
                    box-shadow: none;
                    background: linear-gradient(135deg, rgba(9, 187, 7, 0.15), rgba(9, 187, 7, 0.08));
                    border-color: rgba(9, 187, 7, 0.3);
                }
                
                .login-button-content {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 12px;
                }
                
                .login-button-icon {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 24px;
                    height: 24px;
                    flex-shrink: 0;
                }
                
                .login-button-icon img {
                    width: 24px;
                    height: 24px;
                    border-radius: 6px;
                }
                
                .login-button-text {
                    flex: 1;
                    text-align: center;
                }
            </style>
            <?php
            return ob_get_clean();
        } catch (\Exception $e) {
            $this->log('error', '获取登录按钮失败: ' . $e->getMessage());
            return '<div class="login-error">登录按钮加载失败</div>';
        }
    }
    
    /**
     * 执行 cURL 请求
     * @param string $url 请求URL
     * @param array $params 请求参数
     * @param string $action 操作描述（用于日志）
     * @return array 响应数据
     * @throws \Exception 请求失败时抛出异常
     */
    private function makeCurlRequest(string $url, array $params, string $action): array
    {
        $config = $this->getConfig();
        $timeout = $config['api_timeout'] ?? 10;
        $connectTimeout = $config['api_connect_timeout'] ?? 5;
        
        $ch = curl_init();
        try {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            
            $response = curl_exec($ch);

            if ($response === false) {
                $error = curl_error($ch);
                $this->log('error', "{$action}失败: {$error}");
                throw new \Exception("{$action}失败: {$error}");
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode !== 200) {
                $this->log('error', "{$action}失败，状态码: {$httpCode}");
                throw new \Exception("{$action}失败，状态码: {$httpCode}");
            }
            
            $result = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errorMsg = json_last_error_msg();
                $this->log('error', "解析响应失败: {$errorMsg}");
                throw new \Exception("解析响应失败: {$errorMsg}");
            }
            
            if (isset($result['errcode'])) {
                $this->log('error', "微信API错误: {$result['errmsg']}");
                throw new \Exception("微信API错误: {$result['errmsg']}");
            }
            
            return $result;
        } finally {
            if (is_resource($ch)) {
                curl_close($ch);
            }
        }
    }
    
    /**
     * 获取配置（带缓存）
     * @return array
     */
    private function getCachedConfig(): array
    {
        $now = time();
        
        // 检查缓存是否有效
        if ($this->configCache !== null && ($now - $this->configCacheTime) < self::CONFIG_CACHE_TTL) {
            return $this->configCache;
        }
        
        // 从父类获取配置
        $config = parent::getConfig();
        
        // 更新缓存
        $this->configCache = $config;
        $this->configCacheTime = $now;
        
        return $config;
    }
    
    /**
     * 清除配置缓存
     * @return void
     */
    private function clearConfigCache(): void
    {
        $this->configCache = null;
        $this->configCacheTime = 0;
    }
    
    /**
     * 执行 HTTP 请求（使用 Guzzle）
     * @param string $url 请求URL
     * @param array $params 请求参数
     * @param string $action 操作描述（用于日志）
     * @return array 响应数据
     * @throws \Exception 请求失败时抛出异常
     */
    private function makeHttpRequest(string $url, array $params, string $action): array
    {
        $config = $this->getConfig();
        $timeout = $config['api_timeout'] ?? 10;
        $connectTimeout = $config['api_connect_timeout'] ?? 5;
        
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => $timeout,
                'connect_timeout' => $connectTimeout,
                'verify' => true,
                'http_errors' => false
            ]);
            
            $response = $client->get($url, [
                'query' => $params,
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'StarryNight-WeChatOAuth/1.0'
                ]
            ]);
            
            $httpCode = $response->getStatusCode();
            
            if ($httpCode !== 200) {
                $this->log('error', "{$action}失败，状态码: {$httpCode}");
                throw new \Exception("{$action}失败，状态码: {$httpCode}");
            }
            
            $body = (string) $response->getBody();
            $result = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errorMsg = json_last_error_msg();
                $this->log('error', "解析响应失败: {$errorMsg}");
                throw new \Exception("解析响应失败: {$errorMsg}");
            }
            
            if (isset($result['errcode']) && $result['errcode'] != 0) {
                $this->log('error', "微信API错误: {$result['errmsg']}");
                throw new \Exception("微信API错误: {$result['errmsg']}");
            }
            
            return $result;
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $this->log('error', "{$action}失败: {$e->getMessage()}");
            throw new \Exception("{$action}失败: {$e->getMessage()}", 0, $e);
        }
    }
    
    /**
     * 存储登录信息
     * @param array $loginData 登录数据
     */
    private function storeLogin(array $loginData): void
    {
        try {
            $db = $this->getDatabase();
            $tableName = $this->getTableName('wechat_oauth_logins');
            
            $stmt = $db->prepare("
                INSERT INTO $tableName (openid, unionid, nickname, headimgurl, access_token, refresh_token, expires_at, ip_address)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    nickname = VALUES(nickname),
                    headimgurl = VALUES(headimgurl),
                    access_token = VALUES(access_token),
                    refresh_token = VALUES(refresh_token),
                    expires_at = VALUES(expires_at),
                    ip_address = VALUES(ip_address)
            ");
            
            $stmt->execute([
                $loginData['openid'],
                $loginData['unionid'],
                $loginData['nickname'],
                $loginData['headimgurl'],
                $loginData['access_token'],
                $loginData['refresh_token'],
                $loginData['expires_at'],
                $loginData['ip_address']
            ]);
        } catch (\Exception $e) {
            $this->log('error', '存储登录信息失败: ' . $e->getMessage());
        }
    }
}
