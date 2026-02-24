<?php
/**
 * Linux DO社区登录插件
 * 通过Linux DO Connect进行第三方登录，使用OAuth2认证流程
 */

namespace Plugins\Notification\Linuxdo;

use Core\PluginBase;

// 防止类重复声明
if (class_exists(__NAMESPACE__ . '\Plugin')) {
    return;
}

class Plugin extends PluginBase
{
    private $authUrl = 'https://connect.linux.do/oauth2/authorize';
    private $tokenUrl = 'https://connect.linux.do/oauth2/token';
    private $userInfoUrl = 'https://connect.linux.do/api/user';
    
    public function getInfo(): array
    {
        return [
            'plugin_id' => 'linuxdo_login',
            'name' => 'Linux DO社区登录',
            'version' => '1.0.0',
            'description' => '通过Linux DO Connect进行第三方登录，使用OAuth2认证流程',
            'author' => 'StarryNight Team',
            'website' => 'https://wiki.linux.do/Community/LinuxDoConnect',
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
                'linuxdo_id' => 'VARCHAR(255) DEFAULT NULL',
                'username' => 'VARCHAR(100) DEFAULT NULL',
                'email' => 'VARCHAR(255) DEFAULT NULL',
                'access_token' => 'TEXT DEFAULT NULL',
                'refresh_token' => 'TEXT DEFAULT NULL',
                'expires_at' => 'INT UNSIGNED DEFAULT NULL',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'ip_address' => 'VARCHAR(45) DEFAULT NULL'
            ],
            'primary' => 'id',
            'indexes' => ['user_id', 'linuxdo_id', 'expires_at'],
            'unique_indexes' => []
        ];
        
        if (!$this->createTable('linuxdo_logins', $schema)) {
            $this->log('error', '创建Linux DO登录数据表失败');
            return false;
        }
        
        $this->log('info', 'Linux DO社区登录插件安装成功');
        return true;
    }
    
    public function uninstall(): bool
    {
        // 删除登录日志数据表
        if (!$this->dropTable('linuxdo_logins')) {
            $this->log('error', '删除Linux DO登录数据表失败');
            return false;
        }
        
        $this->log('info', 'Linux DO社区登录插件卸载成功');
        return true;
    }
    
    public function enable(): bool
    {
        $this->log('info', 'Linux DO社区登录插件已启用');
        return true;
    }
    
    public function disable(): bool
    {
        $this->log('info', 'Linux DO社区登录插件已禁用');
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
                'name' => 'client_id',
                'title' => 'Client ID',
                'type' => 'text',
                'default' => '',
                'required' => true,
                'description' => '从Linux DO Connect获取的Client ID'
            ],
            [
                'name' => 'client_secret',
                'title' => 'Client Secret',
                'type' => 'password',
                'default' => '',
                'required' => true,
                'description' => '从Linux DO Connect获取的Client Secret'
            ],
            [
                'name' => 'redirect_uri',
                'title' => '回调地址',
                'type' => 'text',
                'default' => '',
                'required' => true,
                'description' => 'OAuth2回调地址，需要在Linux DO Connect中注册'
            ],
            [
                'name' => 'scope',
                'title' => '授权范围',
                'type' => 'text',
                'default' => 'user',
                'required' => false,
                'description' => 'OAuth2授权范围，默认为user'
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
        
        if (empty($config['client_id']) || empty($config['redirect_uri'])) {
            throw new \Exception('Linux DO Connect配置不完整');
        }
        
        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => $config['scope'] ?? 'user',
            'state' => urlencode(json_encode($state))
        ];
        
        return $this->authUrl . '?' . http_build_query($params);
    }
    
    /**
     * 处理回调，获取访问令牌
     * @param string $code 授权码
     * @return array 令牌信息
     */
    public function getAccessToken(string $code): array
    {
        $config = $this->getConfig();
        
        if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['redirect_uri'])) {
            throw new \Exception('Linux DO Connect配置不完整');
        }
        
        $timeout = $config['api_timeout'] ?? 10;
        
        $postData = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            $this->log('error', '获取访问令牌失败: ' . $error);
            throw new \Exception('获取访问令牌失败: ' . $error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode !== 200) {
            $this->log('error', '获取访问令牌失败，状态码: ' . $httpCode . ', 响应: ' . $response);
            throw new \Exception('获取访问令牌失败，状态码: ' . $httpCode . ', 响应: ' . $response);
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log('error', '解析响应失败: ' . json_last_error_msg());
            throw new \Exception('解析响应失败: ' . json_last_error_msg());
        }
        
        if (empty($result['access_token'])) {
            $this->log('error', '响应中没有访问令牌: ' . $response);
            throw new \Exception('响应中没有访问令牌');
        }
        
        return $result;
    }
    
    /**
     * 获取用户信息
     * @param string $accessToken 访问令牌
     * @return array 用户信息
     */
    public function getUserInfo(string $accessToken): array
    {
        $config = $this->getConfig();
        $timeout = $config['api_timeout'] ?? 10;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->userInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            $this->log('error', '获取用户信息失败: ' . $error);
            throw new \Exception('获取用户信息失败: ' . $error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode !== 200) {
            $this->log('error', '获取用户信息失败，状态码: ' . $httpCode . ', 响应: ' . $response);
            throw new \Exception('获取用户信息失败，状态码: ' . $httpCode . ', 响应: ' . $response);
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log('error', '解析用户信息失败: ' . json_last_error_msg());
            throw new \Exception('解析用户信息失败: ' . json_last_error_msg());
        }
        
        return $result;
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
            
            // 获取用户信息
            $userInfo = $this->getUserInfo($accessToken);
            
            // 存储登录信息
            $loginData = [
                'linuxdo_id' => $userInfo['id'] ?? '',
                'username' => $userInfo['username'] ?? '',
                'email' => $userInfo['email'] ?? '',
                'access_token' => $accessToken,
                'refresh_token' => $tokenData['refresh_token'] ?? '',
                'expires_at' => time() + ($tokenData['expires_in'] ?? 3600),
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
            $configIncomplete = empty($config['client_id']) || empty($config['client_secret']) || empty($config['redirect_uri']);
            
            if ($configIncomplete) {
                $authUrl = 'javascript:void(0)';
                $buttonText = '使用 Linux DO 登录';
                $buttonClass = 'linuxdo-login-button disabled';
                $onclick = 'alert(\'请联系管理员配置 Linux DO 登录参数\');';
            } else {
                $authUrl = $this->getAuthorizationUrl($options);
                $buttonText = '使用 Linux DO 登录';
                $buttonClass = 'linuxdo-login-button';
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
                $logoUrl = 'data:image/svg+xml;base64,' . base64_encode('<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="12" fill="#4CAF50"/><path d="M12 5C8.13 5 5 8.13 5 12C5 15.87 8.13 19 12 19C15.87 19 19 15.87 19 12C19 8.13 15.87 5 12 5ZM12 7C9.24 7 7 9.24 7 12C7 14.76 9.24 17 12 17C14.76 17 17 14.76 17 12C17 9.24 14.76 7 12 7Z" fill="rgba(255,255,255,0.3)"/></svg>');
            }
            
            ob_start();
            ?>
            <a href="<?php echo htmlspecialchars($authUrl); ?>" class="<?php echo htmlspecialchars($buttonClass); ?>" <?php echo $onclick ? 'onclick="' . htmlspecialchars($onclick) . '"' : ''; ?>>
                <div class="login-button-content">
                    <div class="login-button-icon">
                        <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Linux DO" width="24" height="24" style="display: block;">
                    </div>
                    <div class="login-button-text"><?php echo htmlspecialchars($buttonText); ?></div>
                </div>
            </a>
            <style>
                .linuxdo-login-button {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 100%;
                    background: linear-gradient(135deg, rgba(76, 175, 80, 0.15), rgba(76, 175, 80, 0.08));
                    border: 1px solid rgba(76, 175, 80, 0.3);
                    border-radius: 12px;
                    padding: 14px 20px;
                    text-decoration: none;
                    transition: all 0.3s ease;
                    font-size: 15px;
                    font-weight: 500;
                    gap: 12px;
                    color: #fff;
                }
                
                .linuxdo-login-button:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
                    background: linear-gradient(135deg, rgba(76, 175, 80, 0.25), rgba(76, 175, 80, 0.12));
                    border-color: rgba(76, 175, 80, 0.5);
                }
                
                .linuxdo-login-button.disabled {
                    opacity: 0.6;
                    cursor: pointer;
                }
                
                .linuxdo-login-button.disabled:hover {
                    transform: none;
                    box-shadow: none;
                    background: linear-gradient(135deg, rgba(76, 175, 80, 0.15), rgba(76, 175, 80, 0.08));
                    border-color: rgba(76, 175, 80, 0.3);
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
     * 存储登录信息
     * @param array $loginData 登录数据
     */
    private function storeLogin(array $loginData): void
    {
        try {
            $db = $this->getDatabase();
            $tableName = $this->getTableName('linuxdo_logins');
            
            // 确保所有必需的键都存在，避免未定义变量引用
            $loginData = array_merge([
                'linuxdo_id' => '',
                'username' => '',
                'email' => '',
                'access_token' => '',
                'refresh_token' => '',
                'expires_at' => 0,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ], $loginData);
            
            $stmt = $db->prepare("
                INSERT INTO $tableName (linuxdo_id, username, email, access_token, refresh_token, expires_at, ip_address)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    username = VALUES(username),
                    email = VALUES(email),
                    access_token = VALUES(access_token),
                    refresh_token = VALUES(refresh_token),
                    expires_at = VALUES(expires_at),
                    ip_address = VALUES(ip_address)
            ");
            
            $stmt->execute([
                $loginData['linuxdo_id'] ?? '',
                $loginData['username'] ?? '',
                $loginData['email'] ?? '',
                $loginData['access_token'] ?? '',
                $loginData['refresh_token'] ?? '',
                $loginData['expires_at'] ?? 0,
                $loginData['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? '')
            ]);
        } catch (\Exception $e) {
            $this->log('error', '存储登录信息失败: ' . $e->getMessage());
        }
    }
}
