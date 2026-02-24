<?php

/**
 * 全局助手函数文件
 *
 * 此文件包含整个应用程序中都可以使用的全局辅助函数。
 * 通过 Composer 的 "files" 自动加载机制被引入。
 */

use app\services\Database;

// 加载核心框架辅助函数
$coreHelpersFile = __DIR__ . '/../core/helpers.php';
if (file_exists($coreHelpersFile)) {
    require_once $coreHelpersFile;
}

if (!function_exists('get_env')) {
    /**
     * 安全地获取环境变量的值。
     *
     * 从一个预加载的全局常量 ENV_SETTINGS 中读取配置项。
     * 这个常量通常在 `config.php` 文件中被定义，该文件包含了从 .env 解析的配置。
     * 这种方法避免了在代码各处直接依赖 `getenv()` 或 `$_ENV`，提供了更好的封装性。
     *
     * @param string $key     要获取的配置项的键名。
     * @param mixed  $default 如果找不到对应的键，则返回此默认值。
     * @return mixed 返回找到的配置值，如果找不到则返回默认值。
     */
    function get_env($key, $default = null)
    {
        // 检查 ENV_SETTINGS 常量是否已定义，并且键是否存在于数组中
        if (defined('ENV_SETTINGS') && array_key_exists($key, ENV_SETTINGS)) {
            return ENV_SETTINGS[$key];
        }
        // 如果未找到，返回指定的默认值
        return $default;
    }
}

// 加载图标辅助函数
if (file_exists(__DIR__ . '/helpers/icon_helper.php')) {
    require_once __DIR__ . '/helpers/icon_helper.php';
}

// csrf_field() 已在 core/helpers.php 中定义，使用核心 CSRF 中间件

if (!function_exists('send_system_mail')) {
    /**
     * 使用后台启用的邮箱插件发送系统邮件（兼容旧架构插件）
     *
     * 当前主要支持 legacy_plugins.json 中启用的 email/asiayun_smtp_pro 插件。
     *
     * @param string $to
     * @param string $subject
     * @param string $content
     * @param string|null $errorMsg 输出参数，用于返回错误信息
     * @return bool
     */
    function send_system_mail(string $to, string $subject, string $content, ?string &$errorMsg = null): bool
    {
        $errorMsg = null;

        try {
            // 优先使用新架构 SMTP 邮箱服务插件：email/smtp_service
            $newArchitecturePluginEnabled = false;
            try {
                $pdo = Database::pdo();
                $table = Database::prefix() . 'admin_plugins';
                
                // 检查所有新架构邮箱插件（email/smtp_service 或其他）
                $stmt = $pdo->prepare("SELECT `plugin_id`, `status`, `config_json` FROM `{$table}` WHERE `plugin_id` LIKE 'email/%' AND `status` = 'enabled' ORDER BY `plugin_id`");
                $stmt->execute();
                $enabledEmailPlugins = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                // 自动发现邮箱插件：优先使用新架构插件，然后使用旧架构插件
                $pluginData = null;
                
                // 分离新架构和旧架构插件
                $newArchPlugins = [];
                $oldArchPlugins = [];
                
                foreach ($enabledEmailPlugins as $plugin) {
                    $pluginId = $plugin['plugin_id'];
                    
                    // 检查是否为新架构插件（有 plugin.json 文件）
                    $pluginDir = __DIR__ . '/../public/plugins/email/' . str_replace('email/', '', $pluginId);
                    if (is_dir($pluginDir)) {
                        // 检查是否有 plugin.json（新架构标识）
                        if (is_file($pluginDir . '/plugin.json')) {
                            $newArchPlugins[] = $plugin;
                        } else {
                            // 旧架构插件：检查是否有 Plugin.php 或 {Name}Plugin.php
                            $pluginFile = $pluginDir . '/Plugin.php';
                            if (!is_file($pluginFile)) {
                                // 尝试查找 {Name}Plugin.php
                                $files = scandir($pluginDir);
                                foreach ($files as $file) {
                                    if (preg_match('/^[A-Z]\w+Plugin\.php$/', $file)) {
                                        $pluginFile = $pluginDir . '/' . $file;
                                        break;
                                    }
                                }
                            }
                            if (is_file($pluginFile)) {
                                $oldArchPlugins[] = $plugin;
                            }
                        }
                    }
                }
                
                // 优先使用新架构插件
                if (!empty($newArchPlugins)) {
                    $pluginData = $newArchPlugins[0];
                } elseif (!empty($oldArchPlugins)) {
                    $pluginData = $oldArchPlugins[0];
                }
                
                // 如果没有找到支持的插件，记录日志
                if (!$pluginData && !empty($enabledEmailPlugins)) {
                    $unsupportedPlugins = array_map(function($p) { return $p['plugin_id']; }, $enabledEmailPlugins);
                    error_log('No supported email plugin found. Available plugins: ' . implode(', ', $unsupportedPlugins));
                }

                if ($pluginData && (string)($pluginData['status'] ?? 'disabled') === 'enabled') {
                    $newArchitecturePluginEnabled = true;
                    $pluginId = $pluginData['plugin_id'];
                    
                    $config = [];
                    if (!empty($pluginData['config_json'])) {
                        $configJson = $pluginData['config_json'];
                        $decoded = json_decode($configJson, true);
                        // 处理可能的多次序列化问题
                        for ($i = 0; $i < 2 && is_string($decoded); $i++) {
                            $decoded = json_decode($decoded, true);
                        }
                        if (is_array($decoded)) {
                            $config = $decoded;
                        }
                    }

                    // 自动发现和加载插件
                    $pluginDirName = str_replace('email/', '', $pluginId);
                    $pluginDir = __DIR__ . '/../public/plugins/email/' . $pluginDirName;
                    
                    if (!is_dir($pluginDir)) {
                        $errorMsg = '插件目录不存在: ' . $pluginDir;
                        $newArchitecturePluginEnabled = false;
                        goto fallback;
                    }
                    
                    // 检查是否为新架构插件（有 plugin.json）
                    $isNewArchitecture = is_file($pluginDir . '/plugin.json');
                    $pluginFile = null;
                    $className = null;
                    
                    if ($isNewArchitecture) {
                        // 新架构插件
                        $pluginFile = $pluginDir . '/Plugin.php';
                        $className = '\\Plugins\\Email\\' . str_replace('/', '\\', $pluginDirName) . '\\Plugin';
                    } else {
                        // 旧架构插件：查找插件文件
                        $pluginFile = $pluginDir . '/Plugin.php';
                        if (!is_file($pluginFile)) {
                            // 尝试查找 {Name}Plugin.php
                            $files = scandir($pluginDir);
                            foreach ($files as $file) {
                                if (preg_match('/^[A-Z]\w+Plugin\.php$/', $file)) {
                                    $pluginFile = $pluginDir . '/' . $file;
                                    // 从文件名推断类名
                                    $classNameWithoutExt = str_replace('Plugin.php', '', $file);
                                    $className = '\\mail\\' . $pluginDirName . '\\' . $classNameWithoutExt . 'Plugin';
                                    break;
                                }
                            }
                        } else {
                            // Plugin.php 存在，尝试推断命名空间
                            $className = '\\mail\\' . $pluginDirName . '\\Plugin';
                        }
                    }
                    
                    if (!$pluginFile || !is_file($pluginFile)) {
                        $errorMsg = '插件文件不存在: ' . $pluginDir;
                        $newArchitecturePluginEnabled = false;
                        goto fallback;
                    }
                    
                    // 只有当类不存在时才加载文件，避免重复声明错误
                    if ($className && !class_exists($className)) {
                        require_once $pluginFile;
                    }
                    
                    if (!$className || !class_exists($className)) {
                        $errorMsg = '插件类不存在: ' . $className;
                        $newArchitecturePluginEnabled = false;
                        goto fallback;
                    }
                    
                    $plugin = new $className();
                    
                    if ($isNewArchitecture) {
                        // 新架构插件接口
                        if (!empty($config) && method_exists($plugin, 'updateConfig')) {
                            $plugin->updateConfig($config);
                        }
                        $ok = $plugin->send($to, $subject, $content, ['config' => $config]);
                    } else {
                        // 旧架构插件接口
                        $params = [
                            'email' => $to,
                            'subject' => $subject,
                            'content' => $content,
                            'config' => $config,
                        ];
                        $result = $plugin->send($params);
                        $ok = isset($result['status']) && $result['status'] === 'success';
                        if (!$ok && isset($result['msg'])) {
                            $errorMsg = $result['msg'];
                        }
                    }
                    
                    if ($ok) {
                        return true;
                    }

                    if (!$errorMsg && method_exists($plugin, 'getLastError')) {
                        $pluginError = $plugin->getLastError();
                        if ($pluginError) {
                            $errorMsg = str_replace(["\n", "|"], ["，", "；"], $pluginError);
                            if (stripos($errorMsg, 'SMTP连接失败') !== false || stripos($errorMsg, 'Could not connect') !== false) {
                                $errorMsg = "邮件服务器连接失败，请检查SMTP配置或联系管理员";
                            }
                        }
                    }
                    
                    fallback:
                    
                    // 如果新架构插件已启用但发送失败或未加载成功，直接返回错误，不再回退到旧架构
                    if ($newArchitecturePluginEnabled) {
                        $errorMsg = $errorMsg ?: '邮件发送失败，请检查邮箱插件配置';
                        return false;
                    }
                }
            } catch (\Throwable $e) {
                // 如果新架构插件已启用，即使出现异常也不回退到旧架构
                if ($newArchitecturePluginEnabled) {
                    $errorMsg = '邮件发送异常: ' . $e->getMessage();
                    error_log('send_system_mail via new architecture plugin failed: ' . $e->getMessage());
                    return false;
                }
                // 新插件未启用或不存在时，记录日志并继续回退到旧架构
                error_log('send_system_mail via email/smtp_service failed: ' . $e->getMessage());
            }

            // 回退：使用旧架构 asiayun_smtp_pro 插件（仅在没有任何新架构插件启用时）
            // 如果新架构插件已启用，不应该执行到这里
            if ($newArchitecturePluginEnabled) {
                $errorMsg = $errorMsg ?: '邮件插件已启用但发送失败，请检查配置';
                return false;
            }

            $legacyFile = __DIR__ . '/../storage/framework/legacy_plugins.json';
            if (!is_file($legacyFile)) {
                $errorMsg = $errorMsg ?: '邮件插件配置文件不存在';
                return false;
            }

            $json = file_get_contents($legacyFile);
            $plugins = json_decode($json, true);
            if (!is_array($plugins) || empty($plugins['email/asiayun_smtp_pro']['installed']) || $plugins['email/asiayun_smtp_pro']['status'] !== 'enabled') {
                $errorMsg = $errorMsg ?: '邮件插件未启用';
                return false;
            }

            $pluginDir = __DIR__ . '/../public/plugins/email/asiayun_smtp_pro';
            $pluginFile = $pluginDir . '/AsiayunSmtpProPlugin.php';
            if (!is_file($pluginFile)) {
                $errorMsg = $errorMsg ?: '邮件插件文件不存在';
                return false;
            }

            // 先从数据库读取实际配置
            $config = [];
            try {
                $pdo = Database::pdo();
                $table = Database::prefix() . 'admin_plugins';
                $stmt = $pdo->prepare("SELECT `config_json` FROM `{$table}` WHERE `plugin_id` = ?");
                $stmt->execute(['email/asiayun_smtp_pro']);
                $pluginData = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($pluginData && !empty($pluginData['config_json'])) {
                    $configJson = $pluginData['config_json'];
                    // 处理可能的多次序列化问题
                    $decoded = json_decode($configJson, true);
                    if (is_string($decoded)) {
                        $decoded = json_decode($decoded, true);
                    }
                    if (is_string($decoded)) {
                        $decoded = json_decode($decoded, true);
                    }
                    if (is_array($decoded)) {
                        $config = $decoded;
                    }
                }
            } catch (\Exception $e) {
                error_log('读取邮件插件配置失败: ' . $e->getMessage());
            }

            // 如果数据库配置为空，则从 config.php 读取默认值
            if (empty($config)) {
                $configDefFile = $pluginDir . '/config.php';
                if (is_file($configDefFile)) {
                    $def = require $configDefFile;
                    if (is_array($def)) {
                        foreach ($def as $key => $item) {
                            if (is_array($item) && array_key_exists('value', $item)) {
                                $config[$key] = $item['value'];
                            }
                        }
                    }
                }
            }

            // 检查必要配置项
            if (empty($config['host']) || empty($config['port']) || empty($config['username']) || empty($config['password']) || empty($config['systememail'])) {
                $errorMsg = $errorMsg ?: '邮件配置不完整，请检查SMTP主机、端口、用户名、密码和系统邮箱配置';
                return false;
            }

            $class = '\\mail\\asiayun_smtp_pro\\AsiayunSmtpProPlugin';
            
            // 只有当类不存在时才加载文件，避免重复声明错误
            if (!class_exists($class)) {
                require_once $pluginFile;
            }

            if (!class_exists($class)) {
                $errorMsg = $errorMsg ?: '邮件插件类不存在';
                return false;
            }

            $plugin = new $class();
            $result = $plugin->send([
                'email' => $to,
                'subject' => $subject,
                'content' => $content,
                'attachments' => '',
                'config' => $config,
            ]);

            if (is_array($result) && ($result['status'] ?? '') === 'success') {
                return true;
            } else {
                // 获取插件返回的错误信息
                $errorMsg = $errorMsg ?: ($result['msg'] ?? '邮件发送失败，未知错误');
                return false;
            }
        } catch (\Throwable $e) {
            $errorMsg = '邮件发送异常: ' . $e->getMessage();
            error_log('send_system_mail failed: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('get_email_template')) {
    /**
     * 获取邮件模板内容
     *
     * @param string $code 模板编码
     * @param array $placeholders 占位符替换数组，例如 ['code' => '123456', 'username' => 'test']
     * @return array|null 返回 ['subject' => '主题', 'body' => '内容']，如果模板不存在返回null
     */
    function get_email_template(string $code, array $placeholders = []): ?array
    {
        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();
            
            $stmt = $pdo->prepare("SELECT * FROM `{$prefix}notification_templates` WHERE channel = 'email' AND code = :code LIMIT 1");
            $stmt->execute([':code' => $code]);
            $tpl = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$tpl) {
                return null;
            }
            
            // 加载模板文件内容
            $body = '';
            $publicRoot = realpath(__DIR__ . '/../public');
            if ($publicRoot && !empty($tpl['content'])) {
                // 邮件模板路径：static/errors/html/Email/
                $filePath = $publicRoot . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'Email' . DIRECTORY_SEPARATOR . $tpl['content'];
                if (is_file($filePath)) {
                    $body = (string)file_get_contents($filePath);
                }
            }
            
            // 如果文件不存在，使用数据库中的content字段（可能是HTML内容）
            if ($body === '' && !empty($tpl['content'])) {
                $body = $tpl['content'];
            }
            
            // 默认占位符
            $defaultPlaceholders = [
                '{{site_name}}' => (string)get_env('APP_NAME', '星夜阁'),
                '{{site_url}}' => (string)get_env('APP_URL', ''),
                '{{current_year}}' => date('Y'),
            ];
            
            // 合并用户提供的占位符
            foreach ($placeholders as $key => $value) {
                $defaultPlaceholders['{{' . $key . '}}'] = (string)$value;
            }
            
            // 处理username占位符：如果username存在，显示"，username"；如果不存在，显示空字符串
            if (isset($placeholders['username']) && !empty($placeholders['username'])) {
                $defaultPlaceholders['{{username}}'] = '，<strong style="color: #667eea;">' . htmlspecialchars((string)$placeholders['username'], ENT_QUOTES, 'UTF-8') . '</strong>';
            } else {
                $defaultPlaceholders['{{username}}'] = '';
            }
            
            // 替换占位符
            $body = strtr($body, $defaultPlaceholders);
            
            $subject = (string)($tpl['title'] ?? '');
            // 替换主题中的占位符
            $subject = strtr($subject, $defaultPlaceholders);
            
            return [
                'subject' => $subject,
                'body' => $body
            ];
        } catch (\Throwable $e) {
            error_log('get_email_template failed: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('send_system_sms')) {
    /**
     * 使用后台启用的短信插件发送系统短信（占位实现）
     *
     * 如需真正启用短信找回密码，请在此根据 legacy_plugins.json 中的 sms/* 插件
     * 实例化对应插件并调用其 sendCnSms / sendGlobalSms 方法。
     *
     * @param string $phone
     * @param string $content 模板内容，支持 {code} 变量
     * @param array $params 变量数组，例如 ['code' => '123456']
     * @return bool
     */
    function send_system_sms(string $phone, string $content, array $params = []): bool
    {
        static $errorMsg = null;
        
        try {
            $pdo = Database::pdo();
            $table = Database::prefix() . 'admin_plugins';
            
            // 检查所有启用的短信插件
            $stmt = $pdo->prepare("SELECT `plugin_id`, `status`, `config_json` FROM `{$table}` WHERE `plugin_id` LIKE 'sms/%' AND `status` = 'enabled' ORDER BY `plugin_id`");
            $stmt->execute();
            $enabledSmsPlugins = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if (empty($enabledSmsPlugins)) {
                $errorMsg = '未找到启用的短信插件';
                return false;
            }
            
            // 自动发现短信插件
            $pluginData = null;
            
            foreach ($enabledSmsPlugins as $plugin) {
                $pluginId = $plugin['plugin_id'];
                $pluginDirName = str_replace('sms/', '', $pluginId);
                $pluginDir = __DIR__ . '/../public/plugins/sms/' . $pluginDirName;
                
                if (!is_dir($pluginDir)) {
                    continue;
                }
                
                // 检查插件文件是否存在
                $pluginFile = $pluginDir . '/' . $pluginDirName . 'Plugin.php';
                if (!is_file($pluginFile)) {
                    // 尝试查找其他命名模式
                    $files = scandir($pluginDir);
                    foreach ($files as $file) {
                        if (preg_match('/^[A-Z]\w+Plugin\.php$/', $file)) {
                            $pluginFile = $pluginDir . '/' . $file;
                            break;
                        }
                    }
                }
                
                if (is_file($pluginFile)) {
                    $pluginData = $plugin;
                    $pluginData['_plugin_file'] = $pluginFile;
                    $pluginData['_plugin_dir'] = $pluginDirName;
                    break;
                }
            }
            
            if (!$pluginData) {
                $errorMsg = '未找到可用的短信插件文件';
                return false;
            }
            
            // 解析配置
            $config = [];
            if (!empty($pluginData['config_json'])) {
                $configJson = $pluginData['config_json'];
                $decoded = json_decode($configJson, true);
                for ($i = 0; $i < 2 && is_string($decoded); $i++) {
                    $decoded = json_decode($decoded, true);
                }
                if (is_array($decoded)) {
                    $config = $decoded;
                }
            }
            
            // 加载插件
            $pluginFile = $pluginData['_plugin_file'];
            $pluginDirName = $pluginData['_plugin_dir'];
            
            // 推断类名
            $className = '\\sms\\' . $pluginDirName . '\\' . ucfirst($pluginDirName) . 'Plugin';
            
            // 只有当类不存在时才加载文件，避免重复声明错误
            if (!class_exists($className)) {
                require_once $pluginFile;
            }
            
            if (!class_exists($className)) {
                // 尝试其他命名模式
                $files = scandir(dirname($pluginFile));
                foreach ($files as $file) {
                    if (preg_match('/^([A-Z]\w+)Plugin\.php$/', $file, $matches)) {
                        $className = '\\sms\\' . $pluginDirName . '\\' . $matches[1] . 'Plugin';
                        if (class_exists($className)) {
                            break;
                        }
                    }
                }
            }
            
            if (!class_exists($className)) {
                $errorMsg = '短信插件类不存在: ' . $className;
                return false;
            }
            
            $plugin = new $className();
            
            // 调用短信插件的发送方法
            // 旧架构短信插件通常使用 sendCnSms 或 sendGlobalSms 方法
            $smsParams = [
                'mobile' => $phone,
                'content' => $content,
                'templateParam' => $params,
                'config' => $config,
            ];
            
            if (method_exists($plugin, 'sendCnSms')) {
                $result = $plugin->sendCnSms($smsParams);
            } elseif (method_exists($plugin, 'sendGlobalSms')) {
                $result = $plugin->sendGlobalSms($smsParams);
            } elseif (method_exists($plugin, 'send')) {
                $result = $plugin->send($smsParams);
            } else {
                $errorMsg = '短信插件不支持发送方法';
                return false;
            }
            
            // 处理返回结果
            if (is_array($result)) {
                if (isset($result['status']) && $result['status'] === 'success') {
                    return true;
                }
                if (isset($result['msg'])) {
                    $errorMsg = $result['msg'];
                }
            } elseif ($result === true) {
                return true;
            }
            
            return false;
            
        } catch (\Throwable $e) {
            $errorMsg = '短信发送异常: ' . $e->getMessage();
            error_log('send_system_sms failed: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('asset_version')) {
    /**
     * 获取静态资源版本号
     *
     * 使用文件修改时间作为版本号，确保资源更新时能正确刷新缓存
     * 同时避免使用 time() 导致每次请求都重新加载资源
     *
     * @param string $path 资源路径，相对于 public 目录
     * @return string 版本号
     */
    function asset_version(string $path): string
    {
        static $versionCache = [];
        
        // 使用缓存避免重复读取文件
        if (isset($versionCache[$path])) {
            return $versionCache[$path];
        }
        
        $fullPath = __DIR__ . '/../public' . $path;
        
        if (file_exists($fullPath)) {
            $versionCache[$path] = (string)filemtime($fullPath);
        } else {
            // 文件不存在时使用默认版本号
            $versionCache[$path] = '1.0.0';
        }
        
        return $versionCache[$path];
    }
}

if (!function_exists('asset')) {
    /**
     * 生成带版本号的静态资源 URL
     *
     * @param string $path 资源路径，相对于 public 目录
     * @return string 完整的资源 URL
     */
    function asset(string $path): string
    {
        $version = asset_version($path);
        return '/' . ltrim($path, '/') . '?v=' . $version;
    }
}
