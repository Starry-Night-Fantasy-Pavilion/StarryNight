<?php

namespace Plugins\Email\Smtp_service;

use Core\EmailPlugin;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

// 防止类重复声明
if (class_exists(__NAMESPACE__ . '\Plugin')) {
    return;
}

/**
 * SMTP 邮箱服务插件（新架构）
 *
 * 说明：
 * - 插件配置通过后台「插件管理」->「SMTP 邮箱服务」进行设置
 * - send() 方法将被全局辅助函数 send_system_mail 调用
 */
class Plugin extends EmailPlugin
{
    /**
     * @var string|null
     */
    protected ?string $lastError = null;

    public function getInfo(): array
    {
        return [
            'plugin_id' => 'email/smtp_service',
            'name' => 'SMTP 邮箱服务',
            'version' => '1.0.0',
            'description' => '基于 SMTP 的系统邮件发送服务，使用 PHPMailer 实现，支持 SSL/TLS。',
            'author' => '星夜幻梦',
            'website' => '',
            'type' => 'email',
            'category' => 'smtp',
        ];
    }

    /**
     * 配置表单定义，与 plugin.json 中的 config 保持一致
     */
    public function getConfigForm(): array
    {
        return [
            [
                'name' => 'host',
                'title' => 'SMTP 服务器地址',
                'type' => 'text',
                'default' => '',
                'required' => true,
                'description' => '例如：smtp.qq.com、mail.example.com',
            ],
            [
                'name' => 'port',
                'title' => 'SMTP 端口',
                'type' => 'number',
                'default' => 465,
                'required' => true,
            ],
            [
                'name' => 'username',
                'title' => 'SMTP 登录用户名',
                'type' => 'text',
                'default' => '',
                'required' => true,
            ],
            [
                'name' => 'password',
                'title' => 'SMTP 登录密码',
                'type' => 'password',
                'default' => '',
                'required' => true,
            ],
            [
                'name' => 'smtpsecure',
                'title' => '加密方式',
                'type' => 'select',
                'options' => [
                    'ssl' => 'SSL（常用端口 465）',
                    'tls' => 'TLS（常用端口 587）',
                    'none' => '不加密',
                ],
                'default' => 'ssl',
                'required' => true,
            ],
            [
                'name' => 'fromname',
                'title' => '发件人名称',
                'type' => 'text',
                'default' => '',
                'required' => false,
            ],
            [
                'name' => 'systememail',
                'title' => '系统邮箱地址',
                'type' => 'text',
                'default' => '',
                'required' => true,
                'description' => '用于作为发件人邮箱地址，需与 SMTP 账号匹配',
            ],
            [
                'name' => 'charset',
                'title' => '邮件编码',
                'type' => 'text',
                'default' => 'utf-8',
                'required' => false,
            ],
            [
                'name' => 'timeout',
                'title' => '连接超时时间（秒）',
                'type' => 'number',
                'default' => 30,
                'min' => 5,
                'max' => 120,
                'required' => false,
                'description' => 'SMTP连接超时时间，建议30秒',
            ],
            [
                'name' => 'keepalive',
                'title' => '保持连接',
                'type' => 'checkbox',
                'default' => false,
                'required' => false,
                'description' => '启用后保持SMTP连接，提高发送效率',
            ],
            [
                'name' => 'retry_attempts',
                'title' => '重试次数',
                'type' => 'number',
                'default' => 3,
                'min' => 1,
                'max' => 10,
                'required' => false,
                'description' => '发送失败时的重试次数',
            ],
            [
                'name' => 'retry_delay',
                'title' => '重试延迟（秒）',
                'type' => 'number',
                'default' => 5,
                'min' => 1,
                'max' => 60,
                'required' => false,
                'description' => '每次重试之间的延迟时间',
            ],
            [
                'name' => 'verify_peer',
                'title' => '验证SSL证书',
                'type' => 'checkbox',
                'default' => false,
                'required' => false,
                'description' => '启用后验证SSL证书，如果连接失败可尝试关闭此选项',
            ],
            [
                'name' => 'verify_peer_name',
                'title' => '验证SSL证书名称',
                'type' => 'checkbox',
                'default' => false,
                'required' => false,
                'description' => '启用后验证SSL证书名称，如果连接失败可尝试关闭此选项',
            ],
            [
                'name' => 'debug',
                'title' => '调试级别',
                'type' => 'number',
                'default' => 0,
                'min' => 0,
                'max' => 4,
                'required' => false,
                'description' => '0=关闭, 1=客户端消息, 2=客户端和服务器消息, 3=连接信息, 4=详细调试',
            ],
        ];
    }

    /**
     * 发送邮件（带重试机制）
     *
     * @param string $to 收件人邮箱
     * @param string $subject 邮件主题
     * @param string $body 邮件内容（HTML）
     * @param array  $options 其他选项：
     *                         - attachments: string|array 附件列表
     *                         - cc: string|array 抄送
     *                         - bcc: string|array 密送
     *                         - config: array 手动传入配置（优先级最高）
     *                         - retry_attempts: int 重试次数（默认3次）
     *                         - retry_delay: int 重试延迟（秒，默认5秒）
     */
    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        $this->lastError = null;

        // 合并配置：优先使用 options['config']，否则使用已注入的配置
        if (!empty($options['config']) && is_array($options['config'])) {
            $this->updateConfig($options['config']);
        }

        $config = $this->getConfig();

        if (!$this->validateConfig($config)) {
            return false;
        }

        // 重试配置
        $retryAttempts = (int)($options['retry_attempts'] ?? $config['retry_attempts'] ?? 3);
        $retryDelay = (int)($options['retry_delay'] ?? $config['retry_delay'] ?? 5);
        $attempt = 0;

        while ($attempt < $retryAttempts) {
            $attempt++;
            
            try {
                $mail = $this->createMailer($config);

                $mail->addAddress($to);

                // 抄送
                if (!empty($options['cc'])) {
                    foreach ($this->normalizeList($options['cc']) as $cc) {
                        $mail->addCC($cc);
                    }
                }

                // 密送
                if (!empty($options['bcc'])) {
                    foreach ($this->normalizeList($options['bcc']) as $bcc) {
                        $mail->addBCC($bcc);
                    }
                }

                // 附件
                if (!empty($options['attachments'])) {
                    foreach ($this->normalizeList($options['attachments']) as $file) {
                        if (is_string($file) && $file !== '' && is_file($file)) {
                            $mail->addAttachment($file);
                        }
                    }
                }

                $mail->Subject = $subject;
                $mail->Body = $body;

                if ($mail->send()) {
                    if ($attempt > 1) {
                        $this->log('info', "邮件发送成功（第{$attempt}次尝试）");
                    }
                    return true;
                }

                // 处理发送失败的错误信息
                $phpmailerError = $mail->ErrorInfo ?: '邮件发送失败';
                
                // 将技术性错误转换为用户友好的提示
                if (stripos($phpmailerError, 'Could not connect') !== false || stripos($phpmailerError, 'SSL') !== false || stripos($phpmailerError, '操作成功完成') !== false) {
                    $this->lastError = "邮件服务器连接失败，请检查SMTP配置或联系管理员";
                } elseif (stripos($phpmailerError, 'authentication') !== false) {
                    $this->lastError = "邮件服务器认证失败，请检查用户名和密码是否正确";
                } elseif (stripos($phpmailerError, 'timeout') !== false) {
                    $this->lastError = "连接邮件服务器超时，请稍后重试";
                } else {
                    $this->lastError = "邮件发送失败，请稍后重试或联系管理员";
                }
                
                // 如果是最后一次尝试，记录错误并返回
                if ($attempt >= $retryAttempts) {
                    $this->log('error', "SMTP 邮件发送失败（已重试{$attempt}次）: " . $this->lastError);
                    return false;
                }
                
                // 等待后重试
                $this->log('warning', "邮件发送失败，{$retryDelay}秒后重试（第{$attempt}次尝试）: " . $this->lastError);
                sleep($retryDelay);
                
            } catch (MailException $e) {
                $errorMsg = $e->getMessage();
                $phpmailerError = $mail->ErrorInfo ?? '';
                
                // 处理Windows系统上的SSL错误，转换为用户友好的提示
                if (stripos($errorMsg, 'SSL') !== false || stripos($errorMsg, '操作成功完成') !== false || stripos($phpmailerError, 'Could not connect') !== false) {
                    $this->lastError = "邮件服务器连接失败，请检查SMTP配置或联系管理员";
                } elseif (stripos($errorMsg, 'authentication') !== false || stripos($phpmailerError, 'authentication') !== false) {
                    $this->lastError = "邮件服务器认证失败，请检查用户名和密码是否正确";
                } elseif (stripos($errorMsg, 'timeout') !== false || stripos($phpmailerError, 'timeout') !== false) {
                    $this->lastError = "连接邮件服务器超时，请稍后重试";
                } else {
                    // 通用错误，简化技术细节
                    $this->lastError = "邮件发送失败，请稍后重试或联系管理员";
                }
                
                // 如果是最后一次尝试，记录错误并返回
                if ($attempt >= $retryAttempts) {
                    $this->log('error', "SMTP 邮件发送异常（已重试{$attempt}次）: " . $this->lastError);
                    return false;
                }
                
                // 等待后重试
                $this->log('warning', "邮件发送异常，{$retryDelay}秒后重试（第{$attempt}次尝试）: " . $this->lastError);
                sleep($retryDelay);
                
            } catch (\Throwable $e) {
                $this->lastError = '未知错误: ' . $e->getMessage();
                $this->log('error', $this->lastError . ' | 文件: ' . $e->getFile() . ' | 行号: ' . $e->getLine());
                return false; // 未知错误不重试
            }
        }

        return false;
    }

    /**
     * 获取最后一次发送的错误信息
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * 校验配置是否完整
     */
    protected function validateConfig(array $config): bool
    {
        $required = ['host', 'port', 'username', 'password', 'systememail'];

        foreach ($required as $key) {
            if (empty($config[$key])) {
                $this->lastError = '邮件配置不完整，请检查：' . $key;
                $this->log('error', $this->lastError);
                return false;
            }
        }

        // 验证端口和加密方式的匹配性
        $port = (int)$config['port'];
        $secure = strtolower((string)($config['smtpsecure'] ?? 'ssl'));
        
        if ($secure === 'ssl' && $port !== 465 && $port !== 994) {
            $this->log('warning', "警告：SSL加密通常使用端口465或994，当前配置为端口{$port}");
        }
        
        if ($secure === 'tls' && $port !== 587 && $port !== 25) {
            $this->log('warning', "警告：TLS加密通常使用端口587或25，当前配置为端口{$port}");
        }

        return true;
    }

    /**
     * 创建并配置 PHPMailer 实例
     */
    protected function createMailer(array $config): PHPMailer
    {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->SMTPAuth = true;
        
        // 增强超时配置
        $mail->Timeout = (int)($config['timeout'] ?? 30);
        $mail->SMTPKeepAlive = (bool)($config['keepalive'] ?? false);

        $mail->Host = (string)$config['host'];
        $mail->Port = (int)$config['port'];

        $secure = strtolower((string)($config['smtpsecure'] ?? 'ssl'));
        if ($secure === 'none') {
            $mail->SMTPSecure = '';
        } else {
            $mail->SMTPSecure = $secure;
        }

        // 增强SSL/TLS配置，提高稳定性
        // 根据加密方式设置不同的crypto_method
        $cryptoMethod = null;
        if ($secure === 'tls') {
            // TLS通常使用TLSv1.2或更高版本
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            } elseif (defined('STREAM_CRYPTO_METHOD_TLS_CLIENT')) {
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            }
        } elseif ($secure === 'ssl') {
            // SSL使用SSLv3或TLS
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            } elseif (defined('STREAM_CRYPTO_METHOD_SSLv23_CLIENT')) {
                $cryptoMethod = STREAM_CRYPTO_METHOD_SSLv23_CLIENT;
            }
        }

        $sslOptions = [
            'verify_peer' => (bool)($config['verify_peer'] ?? false),
            'verify_peer_name' => (bool)($config['verify_peer_name'] ?? false),
            'allow_self_signed' => (bool)($config['allow_self_signed'] ?? true),
        ];

     
        // 添加CA文件路径（如果配置了）
        if (!empty($config['cafile'])) {
            $sslOptions['cafile'] = $config['cafile'];
        }
        if (!empty($config['capath'])) {
            $sslOptions['capath'] = $config['capath'];
        }

        $mail->SMTPOptions = [
            'ssl' => $sslOptions,
        ];

        $mail->CharSet = (string)($config['charset'] ?? 'utf-8');
        $mail->FromName = (string)($config['fromname'] ?? ($config['username'] ?? ''));
        $mail->Username = (string)$config['username'];
        $mail->Password = (string)$config['password'];
        $mail->From = (string)$config['systememail'];

        $mail->isHTML(true);
        
        // 强制禁用调试输出以避免JSON格式错误
        $mail->SMTPDebug = 0; // 完全禁用调试输出

        return $mail;
    }

    /**
     * 将字符串/数组统一转换为数组
     *
     * @param mixed $value
     * @return array<int, string>
     */
    protected function normalizeList($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value), static function ($item) {
                return $item !== '';
            }));
        }

        if (is_string($value) && $value !== '') {
            // 支持逗号分隔
            $parts = preg_split('/[;,]+/', $value);
            if ($parts === false) {
                return [$value];
            }
            return array_values(array_filter(array_map('trim', $parts), static function ($item) {
                return $item !== '';
            }));
        }

        return [];
    }
}

