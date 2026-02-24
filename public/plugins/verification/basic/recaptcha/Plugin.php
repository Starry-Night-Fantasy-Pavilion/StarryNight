<?php
/**
 * 基础验证码插件 - 融合中文数字运算和随机验证码
 * 随机调用两个API提供多样化验证方式
 */

namespace Plugins\Verification\Basic\Recaptcha;

use Core\VerificationPlugin;

// 防止类重复声明
if (class_exists(__NAMESPACE__ . '\RecaptchaPlugin')) {
    return;
}

class RecaptchaPlugin extends VerificationPlugin
{
    private $apiEndpoints = [
        'chinese' => 'https://v2.xxapi.cn/api/chineseCaptcha',
        'random' => 'https://v2.xxapi.cn/api/captcha'
    ];
    
    public function getInfo(): array
    {
        return [
            'name' => '基础验证码',
            'version' => '1.0.0',
            'description' => '融合中文数字运算验证码和随机验证码，随机调用提供多样化验证方式',
            'author' => 'StarryNight Team',
            'website' => 'https://xxapi.cn',
            'type' => 'verification',
            'category' => 'basic'
        ];
    }
    
    public function install(): bool
    {
        // 创建验证码数据表
        $schema = [
            'columns' => [
                'id' => 'INT UNSIGNED AUTO_INCREMENT',
                'token' => 'VARCHAR(64) NOT NULL',
                'type' => 'VARCHAR(20) NOT NULL',
                'image' => 'TEXT DEFAULT NULL',
                'question' => 'VARCHAR(255) DEFAULT NULL',
                'answer' => 'VARCHAR(255) NOT NULL',
                'expires_at' => 'INT UNSIGNED NOT NULL',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'ip_address' => 'VARCHAR(45) DEFAULT NULL'
            ],
            'primary' => 'id',
            'indexes' => ['expires_at', 'type'],
            'unique_indexes' => ['token'],
            'composite_indexes' => [
                'token_expires' => ['token', 'expires_at'],
                'type_expires' => ['type', 'expires_at']
            ]
        ];
        
        if (!$this->createTable('captchas', $schema)) {
            $this->log('error', '创建验证码数据表失败');
            return false;
        }
        
        $this->log('info', '基础验证码插件安装成功');
        return true;
    }
    
    public function uninstall(): bool
    {
        // 删除验证码数据表
        if (!$this->dropTable('captchas')) {
            $this->log('error', '删除验证码数据表失败');
            return false;
        }
        
        $this->log('info', '基础验证码插件卸载成功');
        return true;
    }
    
    public function enable(): bool
    {
        $this->log('info', '基础验证码插件已启用');
        return true;
    }
    
    public function disable(): bool
    {
        $this->log('info', '基础验证码插件已禁用');
        return true;
    }
    
    public function updateConfig(array $config): bool
    {
        return parent::updateConfig($config);
    }
    
    public function getConfigForm(): array
    {
        // 获取父类的配置表单（包含使用场景配置）
        $parentForm = parent::getConfigForm();
        
        // 合并当前插件的配置表单，简化配置项，适合0基础用户使用
        return array_merge($parentForm, [
            [
                'name' => 'api_key',
                'title' => 'API Key',
                'type' => 'password',
                'options' => [],
                'default' => '',
                'required' => true,
                'description' => '从 xxapi.cn 获取的 API Key，用于验证请求合法性'
            ],
            [
                'name' => 'use_chinese',
                'title' => '启用中文数字运算验证码',
                'type' => 'checkbox',
                'options' => [],
                'default' => true,
                'description' => '启用后会生成中文数字运算题，如："五十六加三十四等于多少？"，推荐开启'
            ],
            [
                'name' => 'use_random',
                'title' => '启用随机验证码',
                'type' => 'checkbox',
                'options' => [],
                'default' => true,
                'description' => '启用后会生成随机字符串验证码，如："a1B2c3"，推荐开启'
            ],
            [
                'name' => 'cache_duration',
                'title' => '验证码有效期',
                'type' => 'number',
                'options' => [],
                'default' => 300,
                'min' => 60,
                'max' => 3600,
                'description' => '验证码的有效时间，单位为秒，默认5分钟，推荐保持默认设置'
            ],
            [
                'name' => 'api_timeout',
                'title' => 'API超时时间',
                'type' => 'number',
                'options' => [],
                'default' => 10,
                'min' => 1,
                'max' => 60,
                'description' => '获取验证码时的网络超时时间，单位为秒，默认10秒，推荐保持默认设置'
            ]
        ]);
    }
    
    public function generate(array $options = []): array
    {
        $config = $this->getConfig();
        
        $useChinese = $config['use_chinese'] ?? true;
        $useRandom = $config['use_random'] ?? true;
        
        if (!$useChinese && !$useRandom) {
            throw new \Exception('至少需要启用一种验证码类型');
        }
        
        $apiType = $this->selectApiType($useChinese, $useRandom);
        $endpoint = $this->apiEndpoints[$apiType];
        
        $result = $this->callApi($endpoint, $config);
        
        if (!$result || !isset($result['success']) || !$result['success']) {
            throw new \Exception('验证码生成失败: ' . ($result['message'] ?? '未知错误'));
        }
        
        $token = $this->generateToken();
        
        $captchaData = [
            'token' => $token,
            'type' => $apiType,
            'image' => $result['data']['image'] ?? '',
            'question' => $result['data']['question'] ?? '',
            'answer' => $result['data']['answer'] ?? '',
            'expires_at' => time() + ($config['cache_duration'] ?? 300)
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
        
        // 确保两边都进行trim和大小写不敏感比较
        $storedAnswer = trim((string)($captchaData['answer'] ?? ''));
        $userAnswer = trim($code);
        
        // 对于数字答案，直接比较；对于文本答案，进行不区分大小写比较
        $isValid = false;
        if (is_numeric($storedAnswer) && is_numeric($userAnswer)) {
            // 数字答案：直接比较
            $isValid = (int)$storedAnswer === (int)$userAnswer;
        } else {
            // 文本答案：不区分大小写比较
            $isValid = strtolower($storedAnswer) === strtolower($userAnswer);
        }
        
        if ($isValid) {
            $this->deleteCaptcha($token);
            $this->log('info', '验证码验证成功: token=' . $token . ', stored=' . $storedAnswer . ', user=' . $userAnswer);
        } else {
            $this->log('warning', '验证码验证失败: token=' . $token . ', stored=' . $storedAnswer . ', user=' . $userAnswer);
        }
        
        return $isValid;
    }
    
    public function getHtml(array $options = []): string
    {
        return $this->getWidget();
    }
    
    public function cleanup(): int
    {
        // 清理过期的验证码
        $config = $this->getConfig();
        $cacheDir = $config['cache_dir'] ?? sys_get_temp_dir();
        $expiredCount = 0;
        
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/captcha_*.txt');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                $data = json_decode($content, true);
                if ($data && isset($data['expires_at']) && time() > $data['expires_at']) {
                    unlink($file);
                    $expiredCount++;
                }
            }
        }
        
        return $expiredCount;
    }

    public function getWidget(): string
    {
        try {
            $captcha = $this->generate();
            
            ob_start();
            ?>
            <div class="captcha-widget" data-token="<?php echo htmlspecialchars($captcha['token']); ?>">
                <?php if (!empty($captcha['image'])): ?>
                    <img src="<?php echo htmlspecialchars($captcha['image']); ?>" 
                         alt="验证码" 
                         class="captcha-image"
                         onclick="this.src='<?php echo htmlspecialchars($captcha['image']); ?>&t=' + Date.now();"
                         style="cursor: pointer;">
                <?php endif; ?>
                
                <?php if (!empty($captcha['question'])): ?>
                    <p class="captcha-question"><?php echo htmlspecialchars($captcha['question']); ?></p>
                <?php endif; ?>
                
                <input type="text" 
                       name="captcha_value" 
                       class="captcha-input" 
                       placeholder="请输入验证码" 
                       required>
                
                <input type="hidden" 
                       name="captcha_token" 
                       value="<?php echo htmlspecialchars($captcha['token']); ?>">
            </div>
            <style>
                .captcha-widget {
                    display: inline-block;
                    padding: 15px;
                    background: #f5f5f5;
                    border-radius: 5px;
                    margin: 10px 0;
                }
                .captcha-image {
                    display: block;
                    border: 1px solid #ddd;
                    border-radius: 3px;
                    margin-bottom: 10px;
                }
                .captcha-question {
                    font-size: 16px;
                    margin: 0 0 10px 0;
                    color: #333;
                }
                .captcha-input {
                    padding: 8px 12px;
                    border: 1px solid #ddd;
                    border-radius: 3px;
                    font-size: 14px;
                    width: 200px;
                }
            </style>
            <?php
            return ob_get_clean();
        } catch (\Exception $e) {
            $this->log('error', '获取验证码组件失败: ' . $e->getMessage());
            return '<div class="captcha-error">验证码加载失败，请刷新页面重试</div>';
        }
    }
    
    private function selectApiType(bool $useChinese, bool $useRandom): string
    {
        if ($useChinese && !$useRandom) {
            return 'chinese';
        }
        
        if (!$useChinese && $useRandom) {
            return 'random';
        }
        
        return (rand(0, 1) === 0) ? 'chinese' : 'random';
    }
    
    private function callApi(string $endpoint, array $config): array
    {
        $timeout = $config['api_timeout'] ?? 10;
        $apiKey = $config['api_key'] ?? '';
        
        if (empty($apiKey)) {
            throw new \Exception('API Key is required');
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // 添加API Key认证
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey
        ]);
        
        $response = curl_exec($ch);

        if ($response === false) {
            throw new \Exception('API请求失败: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode !== 200) {
            throw new \Exception('API返回错误状态码: ' . $httpCode . ', 响应: ' . $response);
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('API响应解析失败: ' . json_last_error_msg());
        }
        
        return $result;
    }
    
    /**
     * 生成验证码令牌
     * 
     * @return string
     */
    private function generateToken(): string
    {
        return md5(uniqid() . mt_rand() . time());
    }
    
    /**
     * 存储验证码数据
     * 
     * @param string $token 验证码令牌
     * @param array $data 验证码数据
     * @return bool
     */
    private function storeCaptcha(string $token, array $data): bool
    {
        try {
            $db = $this->getDatabase();
            $tableName = $this->getTableName('captchas');
            
            $stmt = $db->prepare("INSERT INTO $tableName (token, type, image, question, answer, expires_at, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            return $stmt->execute([
                $token,
                $data['type'],
                $data['image'],
                $data['question'],
                $data['answer'],
                $data['expires_at'],
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (\Exception $e) {
            $this->log('error', '存储验证码失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取验证码数据
     * 
     * @param string $token 验证码令牌
     * @return array|null
     */
    private function getCaptcha(string $token): ?array
    {
        try {
            $db = $this->getDatabase();
            $tableName = $this->getTableName('captchas');
            
            $stmt = $db->prepare("SELECT * FROM $tableName WHERE token = ? AND expires_at > ?");
            $stmt->execute([$token, time()]);
            
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            $this->log('error', '获取验证码失败: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 删除验证码数据
     * 
     * @param string $token 验证码令牌
     * @return bool
     */
    private function deleteCaptcha(string $token): bool
    {
        try {
            $db = $this->getDatabase();
            $tableName = $this->getTableName('captchas');
            
            $stmt = $db->prepare("DELETE FROM $tableName WHERE token = ?");
            return $stmt->execute([$token]);
        } catch (\Exception $e) {
            $this->log('error', '删除验证码失败: ' . $e->getMessage());
            return false;
        }
    }
}