<?php
/**
 * Cloudflare Turnstile验证码插件
 * 提供无干扰的人机验证体验
 */

namespace Plugins\Verification\Thirdparty\Cloudflare;

use Core\VerificationPlugin;

// 防止类重复声明
if (class_exists(__NAMESPACE__ . '\Plugin')) {
    return;
}

class Plugin extends VerificationPlugin
{
    private $apiEndpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    
    public function getInfo(): array
    {
        return [
            'name' => 'Cloudflare验证码',
            'version' => '1.0.0',
            'description' => 'Cloudflare Turnstile验证码服务，提供无干扰的人机验证体验',
            'author' => 'StarryNight Team',
            'website' => 'https://www.cloudflare.com',
            'type' => 'verification',
            'category' => 'thirdparty'
        ];
    }
    
    public function install(): bool
    {
        // 创建验证码数据表
        $schema = [
            'columns' => [
                'id' => 'INT UNSIGNED AUTO_INCREMENT',
                'token' => 'VARCHAR(64) NOT NULL',
                'response' => 'TEXT DEFAULT NULL',
                'expires_at' => 'INT UNSIGNED NOT NULL',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'ip_address' => 'VARCHAR(45) DEFAULT NULL'
            ],
            'primary' => 'id',
            'indexes' => ['expires_at'],
            'unique_indexes' => ['token']
        ];
        
        if (!$this->createTable('cloudflare_captchas', $schema)) {
            $this->log('error', '创建Cloudflare验证码数据表失败');
            return false;
        }
        
        $this->log('info', 'Cloudflare验证码插件安装成功');
        return true;
    }
    
    public function uninstall(): bool
    {
        // 删除验证码数据表
        if (!$this->dropTable('cloudflare_captchas')) {
            $this->log('error', '删除Cloudflare验证码数据表失败');
            return false;
        }
        
        $this->log('info', 'Cloudflare验证码插件卸载成功');
        return true;
    }
    
    public function enable(): bool
    {
        $this->log('info', 'Cloudflare验证码插件已启用');
        return true;
    }
    
    public function disable(): bool
    {
        $this->log('info', 'Cloudflare验证码插件已禁用');
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
                'name' => 'site_key',
                'title' => 'Site Key',
                'type' => 'text',
                'default' => '',
                'required' => true,
                'description' => '从Cloudflare Turnstile获取的Site Key'
            ],
            [
                'name' => 'secret_key',
                'title' => 'Secret Key',
                'type' => 'password',
                'default' => '',
                'required' => true,
                'description' => '从Cloudflare Turnstile获取的Secret Key'
            ],
            [
                'name' => 'theme',
                'title' => '主题',
                'type' => 'select',
                'options' => [
                    'light' => '浅色',
                    'dark' => '深色',
                    'auto' => '自动'
                ],
                'default' => 'auto',
                'required' => false,
                'description' => '验证码的显示主题'
            ],
            [
                'name' => 'size',
                'title' => '尺寸',
                'type' => 'select',
                'options' => [
                    'normal' => '标准',
                    'compact' => '紧凑'
                ],
                'default' => 'normal',
                'required' => false,
                'description' => '验证码的显示尺寸'
            ],
            [
                'name' => 'api_timeout',
                'title' => 'API超时时间',
                'type' => 'number',
                'default' => 10,
                'min' => 1,
                'max' => 60,
                'required' => false,
                'description' => '验证请求的超时时间（秒）'
            ]
        ]);
    }
    
    public function generate(array $options = []): array
    {
        $config = $this->getConfig();
        
        if (empty($config['site_key'])) {
            throw new \Exception('Cloudflare Site Key未配置');
        }
        
        $token = $this->generateToken();
        
        $captchaData = [
            'token' => $token,
            'site_key' => $config['site_key'],
            'theme' => $config['theme'] ?? 'auto',
            'size' => $config['size'] ?? 'normal',
            'expires_at' => time() + 300
        ];
        
        $this->storeCaptcha($token, $captchaData);
        
        return $captchaData;
    }
    
    public function verify(string $code, string $token): bool
    {
        $captchaData = $this->getCaptcha($token);
        
        if (!$captchaData) {
            $this->log('warning', '验证码不存在或已过期: ' . $token);
            return false;
        }
        
        if (time() > $captchaData['expires_at']) {
            $this->log('warning', '验证码已过期: ' . $token);
            $this->deleteCaptcha($token);
            return false;
        }
        
        $config = $this->getConfig();
        
        if (empty($config['secret_key'])) {
            $this->log('error', 'Cloudflare Secret Key未配置');
            return false;
        }
        
        $result = $this->verifyTurnstileResponse($code, $config['secret_key']);
        
        if ($result) {
            $this->deleteCaptcha($token);
            $this->log('info', 'Cloudflare验证码验证成功: ' . $token);
        } else {
            $this->log('warning', 'Cloudflare验证码验证失败: ' . $token);
        }
        
        return $result;
    }
    
    public function getHtml(array $options = []): string
    {
        return $this->getWidget();
    }
    
    public function cleanup(): int
    {
        try {
            $db = $this->getDatabase();
            $tableName = $this->getTableName('cloudflare_captchas');
            $result = $db->exec("DELETE FROM $tableName WHERE expires_at < " . time());
            return $result;
        } catch (\Exception $e) {
            $this->log('error', '清理过期验证码失败: ' . $e->getMessage());
            return 0;
        }
    }

    public function getWidget(): string
    {
        try {
            $captcha = $this->generate();
            
            ob_start();
            ?>
            <div class="captcha-widget" data-token="<?php echo htmlspecialchars($captcha['token']); ?>">
                <div class="cf-turnstile" 
                     data-sitekey="<?php echo htmlspecialchars($captcha['site_key']); ?>"
                     data-theme="<?php echo htmlspecialchars($captcha['theme']); ?>"
                     data-size="<?php echo htmlspecialchars($captcha['size']); ?>">
                </div>
                
                <input type="hidden" 
                       name="captcha_token" 
                       value="<?php echo htmlspecialchars($captcha['token']); ?>">
            </div>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            <style>
                .captcha-widget {
                    display: inline-block;
                    padding: 15px;
                    background: rgba(255, 255, 255, 0.05);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 14px;
                    margin: 10px 0;
                }
            </style>
            <?php
            return ob_get_clean();
        } catch (\Exception $e) {
            $this->log('error', '获取验证码组件失败: ' . $e->getMessage());
            return '<div class="captcha-error">验证码加载失败，请刷新页面重试</div>';
        }
    }
    
    private function verifyTurnstileResponse(string $response, string $secretKey): bool
    {
        $config = $this->getConfig();
        $timeout = $config['api_timeout'] ?? 10;
        
        $postData = [
            'secret' => $secretKey,
            'response' => $response,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            $error = curl_error($ch);
            $this->log('error', 'Cloudflare API请求失败: ' . $error);
            return false;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode !== 200) {
            $this->log('error', 'Cloudflare API返回错误状态码: ' . $httpCode . ', 响应: ' . $response);
            return false;
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log('error', 'Cloudflare API响应解析失败: ' . json_last_error_msg());
            return false;
        }
        
        return isset($result['success']) && $result['success'] === true;
    }
    
    private function generateToken(): string
    {
        return md5(uniqid() . mt_rand() . time());
    }
    
    private function storeCaptcha(string $token, array $data): bool
    {
        try {
            $db = $this->getDatabase();
            $tableName = $this->getTableName('cloudflare_captchas');
            
            $stmt = $db->prepare("INSERT INTO $tableName (token, response, expires_at, ip_address) VALUES (?, ?, ?, ?)");
            
            return $stmt->execute([
                $token,
                json_encode($data),
                $data['expires_at'],
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (\Exception $e) {
            $this->log('error', '存储验证码失败: ' . $e->getMessage());
            return false;
        }
    }
    
    private function getCaptcha(string $token): ?array
    {
        try {
            $db = $this->getDatabase();
            $tableName = $this->getTableName('cloudflare_captchas');
            
            $stmt = $db->prepare("SELECT * FROM $tableName WHERE token = ? AND expires_at > ?");
            $stmt->execute([$token, time()]);
            
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($row) {
                $row['response'] = json_decode($row['response'], true);
            }
            
            return $row ?: null;
        } catch (\Exception $e) {
            $this->log('error', '获取验证码失败: ' . $e->getMessage());
            return null;
        }
    }
    
    private function deleteCaptcha(string $token): bool
    {
        try {
            $db = $this->getDatabase();
            $tableName = $this->getTableName('cloudflare_captchas');
            
            $stmt = $db->prepare("DELETE FROM $tableName WHERE token = ?");
            return $stmt->execute([$token]);
        } catch (\Exception $e) {
            $this->log('error', '删除验证码失败: ' . $e->getMessage());
            return false;
        }
    }
}
