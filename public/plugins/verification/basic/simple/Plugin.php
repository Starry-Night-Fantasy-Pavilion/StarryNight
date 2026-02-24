<?php
/**
 * 简单验证码插件 - 本地数学运算验证码
 * 无需外部API，生成简单的数学运算题
 */

namespace Plugins\Verification\Basic\Simple;

use Core\VerificationPlugin;

// 防止类重复声明
if (class_exists(__NAMESPACE__ . '\SimplePlugin')) {
    return;
}

class SimplePlugin extends VerificationPlugin
{
    // 继承自PluginBase的protected $config属性，无需重复定义
    
    public function getInfo(): array
    {
        return [
            'name' => '简单验证码',
            'version' => '1.0.0',
            'description' => '简单的数学运算验证码，无需外部API',
            'author' => 'StarryNight Team',
            'website' => '',
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
                'token' => 'VARCHAR(32) NOT NULL',
                'question' => 'VARCHAR(100) NOT NULL',
                'answer' => 'VARCHAR(255) NOT NULL',
                'expires_at' => 'INT UNSIGNED NOT NULL',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'ip_address' => 'VARCHAR(45) DEFAULT NULL'
            ],
            'primary' => 'id',
            'indexes' => ['expires_at'],
            'unique_indexes' => ['token'],
            'composite_indexes' => [
                'token_expires' => ['token', 'expires_at']
            ]
        ];
        
        if (!$this->createTable('captchas', $schema)) {
            $this->log('error', '创建验证码数据表失败');
            return false;
        }
        
        $this->log('info', '简单验证码插件安装成功');
        return true;
    }
    
    public function uninstall(): bool
    {
        // 删除验证码数据表
        if (!$this->dropTable('captchas')) {
            $this->log('error', '删除验证码数据表失败');
            return false;
        }
        
        $this->log('info', '简单验证码插件卸载成功');
        return true;
    }
    
    public function enable(): bool
    {
        $this->log('info', '简单验证码插件已启用');
        return true;
    }
    
    public function disable(): bool
    {
        $this->log('info', '简单验证码插件已禁用');
        return true;
    }
    
    // 使用基类的getConfig方法，无需重写
    
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
                'name' => 'difficulty',
                'title' => '验证码难度',
                'type' => 'select',
                'options' => [
                    'easy' => '简单模式 - 适合普通用户',
                    'medium' => '中等模式 - 平衡安全性和用户体验',
                    'hard' => '困难模式 - 高安全性要求'
                ],
                'default' => 'easy',
                'description' => '设置验证码的数学运算难度，默认为简单模式，推荐新手用户使用'
            ],
            [
                'name' => 'operators',
                'title' => '运算类型',
                'type' => 'checkbox',
                'options' => [
                    '+' => '加法运算',
                    '-' => '减法运算'
                ],
                'default' => ['+', '-'],
                'description' => '选择验证码支持的数学运算类型，默认同时开启加法和减法，推荐保持默认设置'
            ]
        ]);
    }
    
    public function generate(array $options = []): array
    {
        $config = $this->getConfig();
        
        // 确保 difficulty 是字符串类型
        $difficulty = isset($config['difficulty']) ? (string)$config['difficulty'] : 'easy';
        if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $difficulty = 'easy';
        }
        
        // 确保 operators 是数组类型，且只包含有效的运算符
        $operators = $config['operators'] ?? ['+', '-'];
        if (!is_array($operators)) {
            // 如果是字符串，尝试解析
            if (is_string($operators)) {
                $decoded = json_decode($operators, true);
                $operators = is_array($decoded) ? $decoded : ['+', '-'];
            } else {
                $operators = ['+', '-'];
            }
        }
        
        // 过滤出有效的运算符（只保留 + 和 -）
        $validOperators = ['+', '-'];
        $operators = array_filter($operators, function($op) use ($validOperators) {
            return in_array($op, $validOperators, true);
        });
        $operators = array_values($operators); // 重新索引数组
        
        // 确保 operators 不为空
        if (empty($operators)) {
            $operators = ['+', '-'];
        }
        
        // 根据难度设置数字范围
        $ranges = [
            'easy' => [1, 10],
            'medium' => [1, 20],
            'hard' => [1, 50]
        ];
        
        $range = $ranges[$difficulty] ?? $ranges['easy'];
        $operator = $operators[array_rand($operators)];
        
        $num1 = rand($range[0], $range[1]);
        $num2 = rand($range[0], $range[1]);
        
        // 确保减法结果不为负数
        if ($operator === '-' && $num2 > $num1) {
            [$num1, $num2] = [$num2, $num1];
        }
        
        $result = $operator === '+' ? $num1 + $num2 : $num1 - $num2;
        
        $token = $this->generateToken();
        $question = "{$num1} {$operator} {$num2} = ?";
        
        $captchaData = [
            'token' => $token,
            'question' => $question,
            'answer' => (string)$result,
            'expires_at' => time() + 300 // 5分钟过期
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
        
        // 确保两边都进行trim和大小写不敏感比较（对于数学题等）
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
        try {
            $db = $this->getDatabase();
            $tableName = $this->getTableName('captchas');
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
            $widgetId = 'captcha-widget-' . uniqid();
            
            ob_start();
            ?>
            <div class="captcha-widget" id="<?php echo htmlspecialchars($widgetId); ?>" data-token="<?php echo htmlspecialchars($captcha['token']); ?>">
                <p class="captcha-question" onclick="refreshCaptcha('<?php echo htmlspecialchars($widgetId); ?>')" title="点击换一个"><?php echo htmlspecialchars($captcha['question']); ?></p>
                <input type="text" 
                       name="captcha_value" 
                       class="captcha-input" 
                       placeholder="请输入答案" 
                       required>
                
                <input type="hidden" 
                       name="captcha_token" 
                       class="captcha-token"
                       value="<?php echo htmlspecialchars($captcha['token']); ?>">
            </div>
            <style>
                .captcha-widget {
                    display: inline-block;
                    padding: 15px;
                    background: rgba(255, 255, 255, 0.05);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 14px;
                    margin: 10px 0;
                    width: 100%;
                }
                .captcha-question {
                    font-size: 16px;
                    margin: 0 0 10px 0;
                    color: var(--text-bright, #ffffff);
                    cursor: pointer;
                    transition: color 0.3s ease;
                }
                .captcha-question:hover {
                    color: var(--primary-color, #c084fc);
                }
                .captcha-input {
                    width: 100%;
                    padding: 14px 18px;
                    background: rgba(255, 255, 255, 0.05);
                    border: 1px solid var(--glass-border, rgba(255, 255, 255, 0.1));
                    border-radius: 14px;
                    color: #fff;
                    font-size: 15px;
                    transition: all 0.3s ease;
                }
                .captcha-input::placeholder {
                    color: rgba(255, 255, 255, 0.2);
                }
                .captcha-input:focus {
                    outline: none;
                    border-color: var(--primary-color, #c084fc);
                    background: rgba(255, 255, 255, 0.08);
                    box-shadow: 0 0 0 4px rgba(192, 132, 252, 0.1);
                }
            </style>
            <script>
            (function() {
                // 确保函数只定义一次
                if (window.refreshCaptcha) return;
                
                window.refreshCaptcha = function(widgetId) {
                    var widget = document.getElementById(widgetId);
                    if (!widget) {
                        console.error('验证码组件不存在:', widgetId);
                        location.reload();
                        return;
                    }
                    
                    var tokenInput = widget.querySelector('.captcha-token');
                    var questionElement = widget.querySelector('.captcha-question');
                    var refreshBtn = widget.querySelector('.captcha-refresh');
                    
                    if (!tokenInput || !questionElement) {
                        console.error('验证码组件结构不完整');
                        location.reload();
                        return;
                    }
                    
                    if (refreshBtn) {
                        refreshBtn.disabled = true;
                        refreshBtn.style.opacity = '0.5';
                        refreshBtn.style.cursor = 'not-allowed';
                    }
                    
                    // 发送 AJAX 请求获取新的验证码
                    var xhr = new XMLHttpRequest();
                    xhr.timeout = 5000; // 5秒超时
                    xhr.open('GET', '/api/captcha/refresh?token=' + encodeURIComponent(tokenInput.value), true);
                    
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            if (refreshBtn) {
                                refreshBtn.disabled = false;
                                refreshBtn.style.opacity = '1';
                                refreshBtn.style.cursor = 'pointer';
                            }
                            
                            if (xhr.status === 200) {
                                try {
                                    var response = JSON.parse(xhr.responseText);
                                    if (response.success && response.data) {
                                        questionElement.textContent = response.data.question;
                                        tokenInput.value = response.data.token;
                                        widget.setAttribute('data-token', response.data.token);
                                        // 清空输入框
                                        var input = widget.querySelector('.captcha-input');
                                        if (input) input.value = '';
                                    } else {
                                        console.error('刷新验证码失败:', response.message || '未知错误');
                                        location.reload();
                                    }
                                } catch (e) {
                                    console.error('解析响应失败:', e);
                                    location.reload();
                                }
                            } else {
                                console.error('API 请求失败，状态码:', xhr.status);
                                location.reload();
                            }
                        }
                    };
                    
                    xhr.onerror = function() {
                        console.error('网络错误，刷新页面');
                        if (refreshBtn) {
                            refreshBtn.disabled = false;
                            refreshBtn.style.opacity = '1';
                            refreshBtn.style.cursor = 'pointer';
                        }
                        location.reload();
                    };
                    
                    xhr.ontimeout = function() {
                        console.error('请求超时，刷新页面');
                        if (refreshBtn) {
                            refreshBtn.disabled = false;
                            refreshBtn.style.opacity = '1';
                            refreshBtn.style.cursor = 'pointer';
                        }
                        location.reload();
                    };
                    
                    xhr.send();
                };
            })();
            </script>
            <?php
            return ob_get_clean();
        } catch (\Exception $e) {
            $this->log('error', '获取验证码组件失败: ' . $e->getMessage());
            return '<div class="captcha-error">验证码加载失败，请刷新页面重试</div>';
        }
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
        // 优先尝试写入数据库，如果失败则自动回退到 Session 存储，避免因为缺少数据表导致「验证码永远错误」
        $savedToDb = false;
        try {
            $db = $this->getDatabase();
            $tableName = $this->getTableName('captchas');
            
            $stmt = $db->prepare("INSERT INTO $tableName (token, question, answer, expires_at, ip_address) VALUES (?, ?, ?, ?, ?)");
            
            $savedToDb = $stmt->execute([
                $token,
                $data['question'],
                $data['answer'],
                $data['expires_at'],
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (\Exception $e) {
            $this->log('error', '存储验证码失败: ' . $e->getMessage());
        }

        // 无论数据库是否成功，始终在 Session 中冗余一份，确保在未建表 / 连接异常时仍可正常验证
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        if (!isset($_SESSION['simple_captcha']) || !is_array($_SESSION['simple_captcha'])) {
            $_SESSION['simple_captcha'] = [];
        }
        $_SESSION['simple_captcha'][$token] = $data;

        return $savedToDb || true;
    }
    
    /**
     * 获取验证码数据
     * 
     * @param string $token 验证码令牌
     * @return array|null
     */
    private function getCaptcha(string $token): ?array
    {
        // 先尝试从数据库读取
        try {
            $db = $this->getDatabase();
            $tableName = $this->getTableName('captchas');
            
            $stmt = $db->prepare("SELECT * FROM $tableName WHERE token = ? AND expires_at > ?");
            $stmt->execute([$token, time()]);
            
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        } catch (\Exception $e) {
            $this->log('error', '获取验证码失败: ' . $e->getMessage());
        }

        // 如果数据库不可用或没有查到记录，则回退到 Session 中的验证码数据
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        if (!empty($_SESSION['simple_captcha'][$token]) && is_array($_SESSION['simple_captcha'][$token])) {
            return $_SESSION['simple_captcha'][$token];
        }

        return null;
    }
    
    /**
     * 删除验证码数据
     * 
     * @param string $token 验证码令牌
     * @return bool
     */
    private function deleteCaptcha(string $token): bool
    {
        $ok = false;

        // 尝试从数据库中删除
        try {
            $db = $this->getDatabase();
            $tableName = $this->getTableName('captchas');
            
            $stmt = $db->prepare("DELETE FROM $tableName WHERE token = ?");
            $ok = $stmt->execute([$token]);
        } catch (\Exception $e) {
            $this->log('error', '删除验证码失败: ' . $e->getMessage());
        }

        // 同时删除 Session 中的冗余数据
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        if (isset($_SESSION['simple_captcha'][$token])) {
            unset($_SESSION['simple_captcha'][$token]);
            $ok = true;
        }

        return $ok;
    }
    
    // 使用基类中定义的公共方法，不再重复实现
    
    // 使用基类的log方法，无需重写
}