<?php

namespace app\frontend\controller;

use app\services\Database;
use app\services\ThemeManager;
use app\services\PluginManager;
use app\models\Setting;
use Core\Exceptions\ErrorCode;
use Core\Events\Events;

class AuthController
{
    /**
     * 获取启用的验证插件
     *
     * @param string $scene 使用场景，如 login、register
     * @return object|null
     */
    private function getVerificationPlugin(string $scene = 'login'): ?object
    {
        try {
            // 先从数据库查询所有已启用的验证码插件
            $pdo = Database::pdo();
            $table = Database::prefix() . 'admin_plugins';
            $stmt = $pdo->prepare("SELECT plugin_id, namespace, main_class, config_json FROM `{$table}` WHERE `type` = 'verification' AND `status` = 'enabled' AND `installed_at` IS NOT NULL LIMIT 1");
            $stmt->execute();
            $pluginData = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$pluginData || empty($pluginData['plugin_id'])) {
                // 如果数据库中没有找到，尝试从 plugin.json 文件读取（兼容旧方式）
                return $this->getVerificationPluginFromFiles();
            }
            
            $pluginId = $pluginData['plugin_id'];
            
            // 根据 plugin_id 查找插件目录
            $pluginsDir = realpath(__DIR__ . '/../../public/plugins');
            if (!$pluginsDir || !is_dir($pluginsDir)) {
                $pluginsDir = realpath(__DIR__ . '/../../../public/plugins');
                if (!$pluginsDir || !is_dir($pluginsDir)) {
                    $pluginsDir = realpath($_SERVER['DOCUMENT_ROOT'] . '/plugins');
                    if (!$pluginsDir || !is_dir($pluginsDir)) {
                        return null;
                    }
                }
            }
            
            // 尝试通过 plugin_id 找到插件目录
            // plugin_id 可能是路径格式（如 verification/basic/simple）或简单ID（如 simple_captcha）
            $pluginPath = null;
            $foundConfig = null;
            
            // 先尝试路径格式
            $possiblePaths = [
                $pluginsDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $pluginId),
                $pluginsDir . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $pluginId),
            ];
            
            foreach ($possiblePaths as $path) {
                $configFile = $path . DIRECTORY_SEPARATOR . 'plugin.json';
                if (is_file($configFile)) {
                    $pluginPath = $path;
                    $foundConfig = json_decode(@file_get_contents($configFile), true);
                    break;
                }
            }
            
            // 如果没找到，递归搜索
            if (!$pluginPath) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($pluginsDir, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
                
                foreach ($iterator as $fileInfo) {
                    if (!$fileInfo->isFile() || $fileInfo->getFilename() !== 'plugin.json') {
                        continue;
                    }
                    
                    $configFile = $fileInfo->getPathname();
                    $config = json_decode(@file_get_contents($configFile), true);
                    if (!is_array($config)) {
                        continue;
                    }
                    
                    $configPluginId = $config['plugin_id'] ?? '';
                    if ($configPluginId === $pluginId || 
                        (($config['type'] ?? '') === 'verification' && strpos($configPluginId, $pluginId) !== false)) {
                        $foundConfig = $config;
                        $pluginPath = dirname($configFile);
                        break;
                    }
                }
            }
            
            if (!$foundConfig || !$pluginPath || !is_dir($pluginPath)) {
                return null;
            }
            
            // 加载插件主类文件
            $mainClass = $pluginData['main_class'] ?? ($foundConfig['main_class'] ?? 'Plugin.php');
            $mainClassPath = $pluginPath . DIRECTORY_SEPARATOR . $mainClass;
            if (!is_readable($mainClassPath)) {
                return null;
            }
            
            // 构建预期的类名
            $namespace = $pluginData['namespace'] ?? ($foundConfig['namespace'] ?? '');
            $namespacePrefix = $namespace !== '' ? $namespace . '\\' : '';
            $expectedClass = $namespacePrefix . pathinfo($mainClass, PATHINFO_FILENAME);
            
            // 记录 require 前已声明的类，用于后续比对
            $beforeClasses = get_declared_classes();
            
            // 只有当类不存在时才加载文件，避免重复声明错误
            if (!class_exists($expectedClass)) {
                require_once $mainClassPath;
            }
            
            $afterClasses = get_declared_classes();

            // 新增的类集合
            $newClasses = array_diff($afterClasses, $beforeClasses);

            $namespace = $pluginData['namespace'] ?? ($foundConfig['namespace'] ?? '');
            $namespacePrefix = $namespace !== '' ? $namespace . '\\' : '';

            $candidateClass = null;

            // 1. 优先尝试 "{namespace}\{mainClass文件名}" 这种常规写法
            $defaultClass = $namespacePrefix . pathinfo($mainClass, PATHINFO_FILENAME);
            if ($defaultClass !== '' && class_exists($defaultClass)) {
                $candidateClass = $defaultClass;
            } else {
                // 2. 从新增类中，选出命名空间前缀匹配的最后一个类（通常就是插件类）
                foreach ($newClasses as $cls) {
                    if ($namespacePrefix !== '' && strpos($cls, $namespacePrefix) === 0) {
                        $candidateClass = $cls;
                    }
                }
            }

            if ($candidateClass === null || !class_exists($candidateClass)) {
                return null;
            }
            
            $pluginInstance = new $candidateClass();
            
            // 如果插件有配置，从数据库加载配置
            if (method_exists($pluginInstance, 'updateConfig')) {
                $configJson = $pluginData['config_json'] ?? null;
                if ($configJson) {
                    $config = json_decode($configJson, true);
                    if (is_array($config)) {
                        // 处理可能的多次序列化
                        for ($i = 0; $i < 2 && is_string($config); $i++) {
                            $config = json_decode($config, true);
                        }
                        if (is_array($config) && !empty($config)) {
                            // 直接使用配置数组，插件内部会处理格式
                            try {
                                $pluginInstance->updateConfig($config);
                            } catch (\Exception $e) {
                                error_log('更新验证码插件配置失败: ' . $e->getMessage());
                            }
                        }
                    }
                }
            }
            
            return $pluginInstance;
            
        } catch (\Exception $e) {
            error_log('加载验证插件失败: ' . $e->getMessage());
            // 如果数据库查询失败，回退到从文件读取
            try {
                return $this->getVerificationPluginFromFiles();
            } catch (\Exception $e2) {
                error_log('从文件加载验证插件也失败: ' . $e2->getMessage());
                return null;
            }
        } catch (\Throwable $e) {
            error_log('加载验证插件发生严重错误: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 从文件系统读取验证码插件（兼容旧方式）
     */
    private function getVerificationPluginFromFiles(): ?object
    {
        try {
            $pluginsDir = realpath(__DIR__ . '/../../public/plugins');
            if (!$pluginsDir || !is_dir($pluginsDir)) {
                $pluginsDir = realpath(__DIR__ . '/../../../public/plugins');
                if (!$pluginsDir || !is_dir($pluginsDir)) {
                    $pluginsDir = realpath($_SERVER['DOCUMENT_ROOT'] . '/plugins');
                    if (!$pluginsDir || !is_dir($pluginsDir)) {
                        return null;
                    }
                }
            }

            $foundConfig = null;
            $pluginPath = null;

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($pluginsDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile() || $fileInfo->getFilename() !== 'plugin.json') {
                    continue;
                }

                $configFile = $fileInfo->getPathname();
                $config = json_decode(@file_get_contents($configFile), true);
                if (!is_array($config)) {
                    continue;
                }

                // 只考虑 type=verification 的插件
                if (($config['type'] ?? '') !== 'verification') {
                    continue;
                }
            
                // 必须是已安装且启用的（从文件读取）
                $installed = $config['installed'] ?? false;
                $installedOk = ($installed === true || $installed === 1 || $installed === '1' || $installed === 'true');
                if (!$installedOk) {
                    continue;
                }
                $status = (string)($config['status'] ?? 'disabled');
                if ($status !== 'enabled') {
                    continue;
                }

                // 找到第一个符合条件的验证插件
                $foundConfig = $config;
                $pluginPath = dirname($configFile);
                break;
            }

            if (!$foundConfig || !$pluginPath || !is_dir($pluginPath)) {
                return null;
            }
            
            // 加载插件主类文件，并尽可能智能地解析实际类名
            $mainClass = $foundConfig['main_class'] ?? 'Plugin.php';
            $mainClassPath = $pluginPath . DIRECTORY_SEPARATOR . $mainClass;
            if (!is_readable($mainClassPath)) {
                return null;
            }
            
            // 构建预期的类名
            $namespace = (string)($foundConfig['namespace'] ?? '');
            $namespacePrefix = $namespace !== '' ? $namespace . '\\' : '';
            $expectedClass = $namespacePrefix . pathinfo($mainClass, PATHINFO_FILENAME);
            
            // 记录 require 前已声明的类，用于后续比对
            $beforeClasses = get_declared_classes();
            
            // 只有当类不存在时才加载文件，避免重复声明错误
            if (!class_exists($expectedClass)) {
                require_once $mainClassPath;
            }
            
            $afterClasses = get_declared_classes();

            // 新增的类集合
            $newClasses = array_diff($afterClasses, $beforeClasses);

            $namespace = (string)($foundConfig['namespace'] ?? '');
            $namespacePrefix = $namespace !== '' ? $namespace . '\\' : '';

            $candidateClass = null;

            // 1. 优先尝试 "{namespace}\{mainClass文件名}" 这种常规写法
            $defaultClass = $namespacePrefix . pathinfo($mainClass, PATHINFO_FILENAME);
            if ($defaultClass !== '' && class_exists($defaultClass)) {
                $candidateClass = $defaultClass;
            } else {
                // 2. 从新增类中，选出命名空间前缀匹配的最后一个类（通常就是插件类）
                foreach ($newClasses as $cls) {
                    if ($namespacePrefix !== '' && strpos($cls, $namespacePrefix) === 0) {
                        $candidateClass = $cls;
                    }
                }
            }

            if ($candidateClass === null || !class_exists($candidateClass)) {
                return null;
            }
            
            return new $candidateClass();
            
        } catch (\Exception $e) {
            error_log('从文件加载验证插件失败: ' . $e->getMessage());
            return null;
        } catch (\Throwable $e) {
            error_log('从文件加载验证插件发生严重错误: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 获取所有启用的第三方登录插件
     * @return array
     */
    private function getThirdPartyLoginPlugins(): array
    {
        $plugins = [];
        try {
            // 先从数据库查询所有已启用的第三方登录插件
            // 注意：status 为 'enabled' 表示已安装且启用，installed_at 不为空表示已安装
            $pdo = Database::pdo();
            $table = Database::prefix() . 'admin_plugins';
            $stmt = $pdo->prepare("SELECT plugin_id, name FROM `{$table}` WHERE `type` = 'thirdparty_login' AND `status` = 'enabled' AND `installed_at` IS NOT NULL");
            $stmt->execute();
            $dbPlugins = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            error_log('查询到的第三方登录插件数量: ' . count($dbPlugins));
            if (empty($dbPlugins)) {
                error_log('没有找到已启用的第三方登录插件');
                // 调试：检查所有第三方登录插件
                $debugStmt = $pdo->prepare("SELECT plugin_id, name, type, status, installed_at FROM `{$table}` WHERE `type` = 'thirdparty_login'");
                $debugStmt->execute();
                $allThirdPartyPlugins = $debugStmt->fetchAll(\PDO::FETCH_ASSOC);
                error_log('所有第三方登录插件: ' . json_encode($allThirdPartyPlugins));
                return $plugins;
            }
            
            // 遍历数据库中的插件，找到对应的 plugin.json
            $pluginsDir = realpath(__DIR__ . '/../../public/plugins');
            if (!$pluginsDir || !is_dir($pluginsDir)) {
                // 尝试其他可能的路径
                $pluginsDir = realpath(__DIR__ . '/../../../public/plugins');
                if (!$pluginsDir || !is_dir($pluginsDir)) {
                    $pluginsDir = realpath($_SERVER['DOCUMENT_ROOT'] . '/plugins');
                    if (!$pluginsDir || !is_dir($pluginsDir)) {
                        error_log('插件目录不存在，尝试的路径: ' . __DIR__ . '/../../public/plugins');
                        error_log('当前工作目录: ' . getcwd());
                        error_log('__DIR__: ' . __DIR__);
                        return $plugins;
                    }
                }
            }
            error_log('使用插件目录: ' . $pluginsDir);
            
            // 递归搜索所有 plugin.json 文件
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($pluginsDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            
            // 构建 plugin_id 到 plugin.json 路径的映射
            // 同时构建路径到 plugin_id 的映射，用于匹配数据库中的路径格式ID
            $pluginJsonMap = [];
            $pathToPluginIdMap = [];
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile() || $fileInfo->getFilename() !== 'plugin.json') {
                    continue;
                }
                
                $configFile = $fileInfo->getPathname();
                $config = json_decode(file_get_contents($configFile), true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }
                
                $pluginId = (string)($config['plugin_id'] ?? '');
                $pluginPath = dirname($configFile);
                
                if (!empty($pluginId)) {
                    $pluginJsonMap[$pluginId] = [
                        'path' => $pluginPath,
                        'config' => $config
                    ];
                    
                    // 构建路径格式的ID（如 notification/linuxdo）
                    $relativePath = str_replace($pluginsDir . DIRECTORY_SEPARATOR, '', $pluginPath);
                    $relativePath = str_replace('\\', '/', $relativePath);
                    $relativePath = trim($relativePath, '/');
                    
                    // 同时支持路径格式的ID和 plugin_id
                    if (!empty($relativePath)) {
                        $pathToPluginIdMap[$relativePath] = $pluginId;
                        // 无论路径格式的ID和 plugin_id 是否相同，都添加到映射中
                        // 这样数据库中的路径格式ID（如 notification/linuxdo）可以直接匹配
                        $pluginJsonMap[$relativePath] = [
                            'path' => $pluginPath,
                            'config' => $config
                        ];
                        error_log("添加路径映射: {$relativePath} -> plugin_id={$pluginId}");
                    }
                }
            }
            
            // 对于每个数据库插件，查找对应的 plugin.json
            foreach ($dbPlugins as $dbPlugin) {
                $dbPluginId = $dbPlugin['plugin_id'];
                
                error_log("查找插件 [{$dbPluginId}] 的 plugin.json");
                
                // 首先尝试直接匹配
                $pluginId = $dbPluginId;
                $pluginData = null;
                
                if (isset($pluginJsonMap[$dbPluginId])) {
                    $pluginData = $pluginJsonMap[$dbPluginId];
                    error_log("直接匹配到插件: {$dbPluginId}");
                } else {
                    // 如果直接匹配失败，尝试通过路径匹配
                    // 数据库中的ID可能是路径格式（如 notification/linuxdo）
                    // 而 plugin.json 中的 plugin_id 可能是不同的格式（如 linuxdo_login）
                    error_log("直接匹配失败，尝试路径匹配");
                    error_log("可用的插件ID: " . implode(', ', array_keys($pluginJsonMap)));
                    
                    // 检查路径映射
                    if (isset($pathToPluginIdMap[$dbPluginId])) {
                        $actualPluginId = $pathToPluginIdMap[$dbPluginId];
                        if (isset($pluginJsonMap[$actualPluginId])) {
                            $pluginId = $actualPluginId;
                            $pluginData = $pluginJsonMap[$actualPluginId];
                            error_log("通过路径映射找到插件: {$dbPluginId} -> {$actualPluginId}");
                        }
                    }
                    
                    // 如果还是没找到，尝试遍历所有插件，通过路径匹配
                    if (!$pluginData) {
                        foreach ($pluginJsonMap as $jsonPluginId => $data) {
                            $pluginPath = $data['path'];
                            $relativePath = str_replace($pluginsDir . DIRECTORY_SEPARATOR, '', $pluginPath);
                            $relativePath = str_replace('\\', '/', $relativePath);
                            $relativePath = trim($relativePath, '/');
                            
                            error_log("比较路径: 数据库ID={$dbPluginId}, 文件路径={$relativePath}, plugin_id={$jsonPluginId}");
                            
                            if ($relativePath === $dbPluginId) {
                                $pluginId = $jsonPluginId;
                                $pluginData = $data;
                                error_log("通过路径遍历找到插件: {$dbPluginId} -> {$jsonPluginId} (路径: {$relativePath})");
                                break;
                            }
                        }
                    }
                    
                    if (!$pluginData) {
                        error_log("无法找到插件 [{$dbPluginId}] 对应的 plugin.json");
                        continue;
                    }
                }
                
                // $pluginData 已经在上面找到了，不需要重复赋值
                $pluginPath = $pluginData['path'];
                $config = $pluginData['config'];
                
                error_log("成功找到插件数据: pluginId={$pluginId}, path={$pluginPath}, type=" . ($config['type'] ?? 'unknown'));
                
                // 检查插件类型
                $pluginType = (string)($config['type'] ?? '');
                if ($pluginType !== 'thirdparty_login') {
                    continue;
                }
                
                // 加载插件类
                $mainClass = $config['main_class'] ?? 'Plugin.php';
                $mainClassPath = $pluginPath . '/' . $mainClass;
                
                if (!is_readable($mainClassPath)) {
                    error_log("第三方登录插件主类文件不可读: {$mainClassPath}");
                    continue;
                }
                
                // 根据命名空间构造类名
                $namespace = $config['namespace'] ?? '';
                $mainClassFile = pathinfo($mainClass, PATHINFO_FILENAME);
                $className = $namespace . '\\' . $mainClassFile;
                
                // 只有当类不存在时才加载文件，避免重复声明错误
                if (!class_exists($className)) {
                    require_once $mainClassPath;
                }
                
                error_log("尝试加载插件类: {$className} (命名空间: {$namespace}, 主类文件: {$mainClassFile})");
                
                if (!class_exists($className)) {
                    error_log("第三方登录插件类不存在: {$className}");
                    // 尝试使用不同的命名空间格式
                    $altClassName = str_replace('\\', '\\\\', $namespace) . '\\' . $mainClassFile;
                    if (class_exists($altClassName)) {
                        $className = $altClassName;
                        error_log("使用备用类名: {$className}");
                    } else {
                        continue;
                    }
                }
                
                // 实例化插件
                try {
                    $pluginInstance = new $className();
                    error_log("成功实例化插件: {$pluginId}");
                } catch (\Exception $e) {
                    error_log("实例化第三方登录插件失败 [{$pluginId}]: " . $e->getMessage());
                    error_log("异常堆栈: " . $e->getTraceAsString());
                    continue;
                }
                
                // 检查是否有 getLoginButton 方法
                if (method_exists($pluginInstance, 'getLoginButton')) {
                    $plugins[] = [
                        'id' => $pluginId,
                        'name' => $config['name'] ?? $pluginId,
                        'instance' => $pluginInstance
                    ];
                    error_log("成功添加第三方登录插件到列表: {$pluginId}");
                } else {
                    error_log("第三方登录插件 [{$pluginId}] 缺少 getLoginButton 方法");
                    error_log("插件实例的方法: " . implode(', ', get_class_methods($pluginInstance)));
                }
            }
        } catch (\Exception $e) {
            error_log('加载第三方登录插件失败: ' . $e->getMessage());
            error_log('异常堆栈: ' . $e->getTraceAsString());
        }
        
        error_log('getThirdPartyLoginPlugins 返回 ' . count($plugins) . ' 个插件');
        return $plugins;
    }

    public function loginForm()
    {
        // 从后台设置读取站点信息（与后台登录保持一致），失败则回落到环境变量
        try {
            $siteName = Setting::get('site_name') ?: (string) get_env('APP_NAME', '');
            $siteLogo = Setting::get('site_logo') ?: '';
            $userAgreementPath = Setting::get('user_agreement_txt_path') ?: '';
            $privacyPolicyPath = Setting::get('privacy_policy_txt_path') ?: '';
        } catch (\Exception $e) {
            $siteName = (string) get_env('APP_NAME', '');
            $siteLogo = '';
            $userAgreementPath = '';
            $privacyPolicyPath = '';
        }
        $action = '/login';
        
        // Map error codes to user-friendly messages
        $messages = [
            '1' => '用户名或密码错误',
            '2' => '账户已被禁用',
            '3' => '验证码错误',
            '4' => '登录已过期，请重新登录',
        ];
        $error = isset($_GET['error']) && isset($messages[$_GET['error']]) ? $messages[$_GET['error']] : '';

        // 获取验证插件（登录场景）
        $verificationPlugin = $this->getVerificationPlugin('login');
        $captchaHtml = '';
        
        if ($verificationPlugin && method_exists($verificationPlugin, 'getWidget')) {
            try {
                $captchaHtml = $verificationPlugin->getWidget();
            } catch (\Exception $e) {
                error_log('加载验证码失败: ' . $e->getMessage());
            }
        }

        // 获取第三方登录插件
        $thirdPartyLoginPlugins = $this->getThirdPartyLoginPlugins();
        error_log('找到 ' . count($thirdPartyLoginPlugins) . ' 个第三方登录插件');
        error_log('第三方登录插件列表: ' . json_encode($thirdPartyLoginPlugins));
        $thirdPartyLoginButtons = [];
        foreach ($thirdPartyLoginPlugins as $plugin) {
            try {
                error_log('正在加载第三方登录插件: ' . $plugin['name']);
                $buttonHtml = $plugin['instance']->getLoginButton();
                error_log('插件 [' . $plugin['name'] . '] 返回的按钮HTML长度: ' . strlen($buttonHtml));
                error_log('按钮HTML前200字符: ' . substr($buttonHtml, 0, 200));
                if (!empty($buttonHtml)) {
                    $thirdPartyLoginButtons[] = $buttonHtml;
                    error_log('成功加载第三方登录插件按钮: ' . $plugin['name']);
                } else {
                    error_log('第三方登录插件 [' . $plugin['name'] . '] 返回了空内容');
                }
            } catch (\Exception $e) {
                error_log('获取第三方登录按钮失败 [' . $plugin['name'] . ']: ' . $e->getMessage());
            }
        }
        error_log('最终生成 ' . count($thirdPartyLoginButtons) . ' 个第三方登录按钮');
        
        // 如果没有任何按钮，记录详细信息用于调试
        if (empty($thirdPartyLoginButtons)) {
            error_log('警告: 第三方登录按钮数组为空');
            error_log('插件列表数量: ' . count($thirdPartyLoginPlugins));
            if (!empty($thirdPartyLoginPlugins)) {
                foreach ($thirdPartyLoginPlugins as $plugin) {
                    error_log('插件: ' . ($plugin['name'] ?? 'N/A') . ', ID: ' . ($plugin['id'] ?? 'N/A'));
                }
            }
        }

        // 使用主题系统渲染登录页面
        $themeManager = new \app\services\ThemeManager();
        $theme = $themeManager->loadActiveThemeInstance();
        
        error_log('第三方登录按钮数量（传递给视图前）: ' . count($thirdPartyLoginButtons));
        error_log('第三方登录按钮内容: ' . json_encode(array_map(function($btn) {
            return substr(strip_tags($btn), 0, 100);
        }, $thirdPartyLoginButtons)));
        
        if ($theme) {
            // 确保变量正确传递
            error_log('准备渲染登录模板，第三方登录按钮数量: ' . count($thirdPartyLoginButtons));
            $loginContent = $theme->renderTemplate('login', [
                'action' => $action,
                'error' => $error,
                'captcha_html' => $captchaHtml,
                'third_party_login_buttons' => $thirdPartyLoginButtons,
                'title' => $siteName . ' - 用户登录',
                'site_name' => $siteName,
                'site_logo' => $siteLogo,
                'user_agreement_txt_path' => $userAgreementPath,
                'privacy_policy_txt_path' => $privacyPolicyPath,
            ]);
            error_log('登录模板渲染完成，内容长度: ' . strlen($loginContent));
            
            // 获取主题基础路径
            $themeManager = new \app\services\ThemeManager();
            $activeThemeId = $themeManager->getActiveThemeId('web') ?? \app\config\FrontendConfig::THEME_DEFAULT;
            $themeBasePath = \app\config\FrontendConfig::getThemePath($activeThemeId);
            
            echo $theme->renderTemplate('layout', [
                'title' => $siteName . ' - 用户登录',
                'site_name' => $siteName,
                'site_logo' => $siteLogo,
                'page_class' => 'page-login',
                'current_page' => 'login',
                'content' => $loginContent,
                'theme_base_path' => $themeBasePath,
            ]);
        } else {
            // 如果没有主题，使用独立视图
            $viewVars = [
                'action' => $action,
                'error' => $error,
                'captcha_html' => $captchaHtml,
                'third_party_login_buttons' => $thirdPartyLoginButtons,
                'title' => $siteName . ' - 用户登录',
                'siteName' => $siteName,
                'site_logo' => $siteLogo,
            ];
            
            extract($viewVars);
            
            ob_start();
            require __DIR__ . '/../views/login.php';
            $content = ob_get_clean();
            echo $content;
        }
    }

    public function login()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            header('Location: /login?error=1');
            exit;
        }

        // 验证验证码（如果启用了验证插件）
        $verificationPlugin = $this->getVerificationPlugin('login');
        if ($verificationPlugin && method_exists($verificationPlugin, 'verify')) {
            $captchaToken = $_POST['captcha_token'] ?? '';

            // 兼容多种验证码类型：
            // - 文本输入型：captcha_value
            // - Cloudflare Turnstile 等滑块型：cf-turnstile-response
            // - 其他可能的统一字段：verification_response、g-recaptcha-response
            $captchaValue = $_POST['captcha_value'] ?? '';
            if ($captchaValue === '' && isset($_POST['cf-turnstile-response'])) {
                $captchaValue = (string)$_POST['cf-turnstile-response'];
            }
            if ($captchaValue === '' && isset($_POST['verification_response'])) {
                $captchaValue = (string)$_POST['verification_response'];
            }
            if ($captchaValue === '' && isset($_POST['g-recaptcha-response'])) {
                $captchaValue = (string)$_POST['g-recaptcha-response'];
            }
            
            if ($captchaToken === '' || $captchaValue === '') {
                header('Location: /login?error=3');
                exit;
            }
            
            if (!$verificationPlugin->verify($captchaValue, $captchaToken)) {
                header('Location: /login?error=3');
                exit;
            }
        }

        // 触发登录前钩子
        $pluginManager = new PluginManager();
        $pluginManager->triggerHook('before_user_login', $username);

        try {
            $pdo = Database::pdo();
            $table = Database::prefix() . 'users';

            // 支持用户名、邮箱或手机号登录
            // 先检查是否是手机号（纯数字，11位）
            $isPhone = preg_match('/^1[3-9]\d{9}$/', $username);
            
            if ($isPhone) {
                // 手机号登录
                $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE `phone` = :phone LIMIT 1");
                $stmt->execute([':phone' => $username]);
            } else {
                // 用户名或邮箱登录
                $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE `username` = :username OR `email` = :username LIMIT 1");
                $stmt->execute([':username' => $username]);
            }
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // 检查账户状态
                if (isset($user['status']) && $user['status'] !== 'active' && $user['status'] !== 1) {
                    header('Location: /login?error=2');
                    exit;
                }

                // 触发登录验证成功钩子
                $pluginManager->triggerHook('user_login_validated', $user);

                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_last_activity'] = time();
                
                // 触发登录成功钩子
                $pluginManager->triggerHook('user_login_success', $user);
                
                // 触发登录事件（使用核心事件系统）
                if (function_exists('event')) {
                    event(Events::USER_LOGIN, [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'] ?? '',
                    ]);
                }
                
                // 重定向到用户中心或之前访问的页面
                $redirect = $_GET['redirect'] ?? '/user_center';
                header('Location: ' . $redirect);
                exit;
            }
        } catch (\PDOException $e) {
            error_log('User login database error: ' . $e->getMessage());
        }

        // 触发登录失败钩子
        $pluginManager->triggerHook('user_login_failed', $username);

        header('Location: /login?error=1');
        exit;
    }

    public function registerForm()
    {
        // 从后台设置读取站点信息（与后台登录保持一致），失败则回落到环境变量
        try {
            $siteName = Setting::get('site_name') ?: (string) get_env('APP_NAME', '');
            $siteLogo = Setting::get('site_logo') ?: '';
            $registerEmailEnabled = Setting::get('register_email_enabled');
            $registerPhoneEnabled = Setting::get('register_phone_enabled');
            $registerDefaultMethod = Setting::get('register_default_method');
        } catch (\Exception $e) {
            $siteName = (string) get_env('APP_NAME', '');
            $siteLogo = '';
            $registerEmailEnabled = '1';
            $registerPhoneEnabled = '1';
            $registerDefaultMethod = 'email';
        }
        
        // 默认值处理
        if ($registerEmailEnabled === null || $registerEmailEnabled === '') $registerEmailEnabled = '1';
        if ($registerPhoneEnabled === null || $registerPhoneEnabled === '') $registerPhoneEnabled = '1';
        if ($registerDefaultMethod === null || $registerDefaultMethod === '') $registerDefaultMethod = 'email';
        
        $action = '/register';
        
        // Map error codes to user-friendly messages
        $messages = [
            '1' => '用户名已存在',
            '2' => '邮箱已被注册',
            '3' => '手机号已被注册',
            '4' => '验证码错误',
            '5' => '密码不一致',
            '6' => '注册失败，请重试',
            '7' => '邮箱注册已关闭',
            '8' => '手机号注册已关闭',
            '9' => '请填写邮箱地址',
            '10' => '请填写手机号',
            '11' => '安全验证失败，请重新完成验证码',
        ];
        $error = isset($_GET['error']) && isset($messages[$_GET['error']]) ? $messages[$_GET['error']] : '';
        $success = isset($_GET['success']) ? '注册成功！请登录' : '';

        // 获取验证插件（注册场景）
        $verificationPlugin = $this->getVerificationPlugin('register');
        $captchaHtml = '';
        
        if ($verificationPlugin && method_exists($verificationPlugin, 'getWidget')) {
            try {
                $captchaHtml = $verificationPlugin->getWidget();
            } catch (\Exception $e) {
                error_log('加载验证码失败: ' . $e->getMessage());
            }
        }

        // 使用主题系统渲染注册页面
        $themeManager = new \app\services\ThemeManager();
        $theme = $themeManager->loadActiveThemeInstance();
        
        if ($theme) {
            $registerContent = $theme->renderTemplate('register', [
                'action' => $action,
                'error' => $error,
                'success' => $success,
                'captcha_html' => $captchaHtml,
                'site_name' => $siteName,
                'site_logo' => $siteLogo,
                'register_email_enabled' => $registerEmailEnabled,
                'register_phone_enabled' => $registerPhoneEnabled,
                'register_default_method' => $registerDefaultMethod,
            ]);

            // 获取主题基础路径（与 loginForm 保持一致，确保 auth 页引用到正确的主题静态资源）
            $themeManager = new \app\services\ThemeManager();
            $activeThemeId = $themeManager->getActiveThemeId('web') ?? \app\config\FrontendConfig::THEME_DEFAULT;
            $themeBasePath = \app\config\FrontendConfig::getThemePath($activeThemeId);
            if (method_exists($theme, 'getThemeDir')) {
                $themeDir = $theme->getThemeDir();
                $publicPath = realpath(__DIR__ . '/../../public');
                if ($publicPath && strpos($themeDir, $publicPath) === 0) {
                    $themeBasePath = str_replace('\\', '/', substr($themeDir, strlen($publicPath)));
                    $themeBasePath = rtrim($themeBasePath, '/');
                }
            }
            
            echo $theme->renderTemplate('layout', [
                'title' => $siteName . ' - 用户注册',
                'site_name' => $siteName,
                'site_logo' => $siteLogo,
                'page_class' => 'page-register',
                'current_page' => 'register',
                'content' => $registerContent,
                'theme_base_path' => $themeBasePath,
            ]);
        } else {
            // 如果没有主题，使用独立视图
            ob_start();
            $action = $action;
            $error = $error;
            $success = $success;
            $captcha_html = $captchaHtml;
            $title = $siteName . ' - 用户注册';
            require __DIR__ . '/../views/register.php';
            $content = ob_get_clean();
            echo $content;
        }
    }

    public function register()
    {
        // 读取注册设置
        try {
            $registerEmailEnabled = Setting::get('register_email_enabled');
            $registerPhoneEnabled = Setting::get('register_phone_enabled');
        } catch (\Exception $e) {
            $registerEmailEnabled = '1';
            $registerPhoneEnabled = '1';
        }
        
        // 默认值处理
        if ($registerEmailEnabled === null || $registerEmailEnabled === '') $registerEmailEnabled = '1';
        if ($registerPhoneEnabled === null || $registerPhoneEnabled === '') $registerPhoneEnabled = '1';
        
        $registerMethod = $_POST['register_method'] ?? 'email';
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $verifyCode = trim((string)($_POST['verify_code'] ?? ''));

        // 验证必填字段
        if (empty($username) || empty($password)) {
            header('Location: /register?error=6');
            exit;
        }
        
        // 根据注册方式验证
        if ($registerMethod === 'email') {
            if ($registerEmailEnabled !== '1') {
                header('Location: /register?error=7');
                exit;
            }
            if (empty($email)) {
                header('Location: /register?error=9');
                exit;
            }
        } elseif ($registerMethod === 'phone') {
            if ($registerPhoneEnabled !== '1') {
                header('Location: /register?error=8');
                exit;
            }
            if (empty($phone)) {
                header('Location: /register?error=10');
                exit;
            }
        } else {
            // 兼容旧逻辑：如果两种都开启，邮箱必填
            if ($registerEmailEnabled === '1' && empty($email)) {
                header('Location: /register?error=9');
                exit;
            }
        }

        // 短信/邮箱验证码校验
        if ($registerMethod === 'email' || $registerMethod === 'phone') {
            $sessionData = $_SESSION['register_verify'] ?? null;

            if ($verifyCode === '' || !$sessionData || !is_array($sessionData)) {
                header('Location: /register?error=4');
                exit;
            }

            $isExpired = time() > (int)($sessionData['expires_at'] ?? 0);
            $codeMatch = (string)($sessionData['code'] ?? '') === $verifyCode;

            if ($isExpired || !$codeMatch) {
                // 记录调试信息，便于排查实际对比的数据
                error_log('Register verify code mismatch: input=' . $verifyCode
                    . ', stored=' . ($sessionData['code'] ?? 'null')
                    . ', expired=' . ($isExpired ? '1' : '0'));
                header('Location: /register?error=4');
                exit;
            }
        }

        // 验证密码一致性
        if ($password !== $passwordConfirm) {
            header('Location: /register?error=5');
            exit;
        }

        // 验证手机号格式（如果提供）
        if (!empty($phone) && !preg_match('/^1[3-9]\d{9}$/', $phone)) {
            header('Location: /register?error=6');
            exit;
        }

        // 验证图形/行为验证码（如果启用了验证插件）
        $verificationPlugin = $this->getVerificationPlugin('register');
        if ($verificationPlugin && method_exists($verificationPlugin, 'verify')) {
            $captchaToken = $_POST['captcha_token'] ?? '';

            // 兼容多种验证码类型：文本输入型 / 滑块型 / 第三方类型
            $captchaValue = $_POST['captcha_value'] ?? '';
            if ($captchaValue === '' && isset($_POST['cf-turnstile-response'])) {
                $captchaValue = (string)$_POST['cf-turnstile-response'];
            }
            if ($captchaValue === '' && isset($_POST['verification_response'])) {
                $captchaValue = (string)$_POST['verification_response'];
            }
            if ($captchaValue === '' && isset($_POST['g-recaptcha-response'])) {
                $captchaValue = (string)$_POST['g-recaptcha-response'];
            }
            
            if ($captchaToken === '' || $captchaValue === '') {
                header('Location: /register?error=11');
                exit;
            }
            
            if (!$verificationPlugin->verify($captchaValue, $captchaToken)) {
                header('Location: /register?error=11');
                exit;
            }
        }

        // 触发注册前钩子
        $pluginManager = new PluginManager();
        $pluginManager->triggerHook('before_user_register', $username, $email, $phone);

        try {
            $pdo = Database::pdo();
            $table = Database::prefix() . 'users';

            // 检查用户名是否已存在
            $stmt = $pdo->prepare("SELECT id FROM `{$table}` WHERE `username` = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            if ($stmt->fetch()) {
                header('Location: /register?error=1');
                exit;
            }

            // 检查邮箱是否已存在（如果提供了邮箱）
            if (!empty($email)) {
                $stmt = $pdo->prepare("SELECT id FROM `{$table}` WHERE `email` = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                if ($stmt->fetch()) {
                    header('Location: /register?error=2');
                    exit;
                }
            }

            // 检查手机号是否已存在（如果提供了手机号）
            if (!empty($phone)) {
                $stmt = $pdo->prepare("SELECT id FROM `{$table}` WHERE `phone` = :phone LIMIT 1");
                $stmt->execute([':phone' => $phone]);
                if ($stmt->fetch()) {
                    header('Location: /register?error=3');
                    exit;
                }
            }

            // 获取客户端IP地址
            $registerIp = $this->getClientIp();
            
            // 创建用户
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO `{$table}` (username, email, phone, password, register_ip) VALUES (:username, :email, :phone, :password, :register_ip)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':email' => !empty($email) ? $email : null,
                ':phone' => !empty($phone) ? $phone : null,
                ':password' => $hashedPassword,
                ':register_ip' => $registerIp,
            ]);

            $userId = $pdo->lastInsertId();

            // 触发注册成功钩子
            $pluginManager->triggerHook('user_register_success', $userId, $username, $email);
            
            // 触发注册事件（使用核心事件系统）
            if (function_exists('event')) {
                event(Events::USER_REGISTERED, [
                    'id' => $userId,
                    'username' => $username,
                    'email' => $email,
                    'phone' => $phone,
                ]);
            }

            header('Location: /register?success=1');
            exit;

        } catch (\PDOException $e) {
            error_log('User register database error: ' . $e->getMessage());
            header('Location: /register?error=6');
            exit;
        }
    }

    public function logout()
    {
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? null;
        
        // 触发登出前钩子
        $pluginManager = new PluginManager();
        $pluginManager->triggerHook('before_user_logout', $userId);
        
        // 触发登出事件（使用核心事件系统）
        if (function_exists('event')) {
            event(Events::USER_LOGOUT, [
                'id' => $userId,
                'username' => $username,
            ]);
        }
        
        unset($_SESSION['user_logged_in']);
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['user_last_activity']);
        
        // 触发登出成功钩子
        $pluginManager->triggerHook('user_logout_success', $userId);
        
        header('Location: /');
        exit;
    }

    /**
     * 重置密码表单（整合找回密码和重置密码功能）
     */
    public function resetPasswordForm()
    {
        $siteName = Setting::get('site_name') ?: (string) get_env('APP_NAME', '');
        $siteLogo = Setting::get('site_logo') ?: '';

        $title = $siteName . ' - 重置密码';
        $error = isset($_GET['error']) ? (string)$_GET['error'] : '';
        $message = isset($_GET['message']) ? (string)$_GET['message'] : '';
        $token = isset($_GET['token']) ? (string)$_GET['token'] : '';

        // 如果没有 token，显示第一步（输入账号信息）
        $captchaHtml = '';
        if (empty($token)) {
            $verificationPlugin = $this->getVerificationPlugin('forgot_password');
            if ($verificationPlugin && method_exists($verificationPlugin, 'getWidget')) {
                try {
                    $captchaHtml = $verificationPlugin->getWidget();
                } catch (\Exception $e) {
                    error_log('加载验证码失败: ' . $e->getMessage());
                }
            }
        }

        // 使用独立视图文件渲染（不走主题系统）
        $viewVars = [
            'title' => $title,
            'siteName' => $siteName,
            'site_logo' => $siteLogo,
            'error' => $error,
            'message' => $message,
            'token' => $token,
            'captcha_html' => $captchaHtml,
        ];

        extract($viewVars);

        ob_start();
        require __DIR__ . '/../views/reset_password.php';
        $content = ob_get_clean();
        echo $content;
    }

    /**
     * 重置密码处理（整合发送验证码和重置密码功能）
     */
    public function resetPassword()
    {
        $token = (string)($_POST['token'] ?? '');
        
        // 如果没有 token，说明是第一步：发送验证码
        if (empty($token)) {
            // 验证验证码（如果启用了验证插件）
            $verificationPlugin = $this->getVerificationPlugin('forgot_password');
            if ($verificationPlugin && method_exists($verificationPlugin, 'verify')) {
                $captchaToken = $_POST['captcha_token'] ?? '';
                
                // 兼容多种验证码类型
                $captchaValue = $_POST['captcha_value'] ?? '';
                if ($captchaValue === '' && isset($_POST['cf-turnstile-response'])) {
                    $captchaValue = (string)$_POST['cf-turnstile-response'];
                }
                if ($captchaValue === '' && isset($_POST['verification_response'])) {
                    $captchaValue = (string)$_POST['verification_response'];
                }
                if ($captchaValue === '' && isset($_POST['g-recaptcha-response'])) {
                    $captchaValue = (string)$_POST['g-recaptcha-response'];
                }
                
                if ($captchaToken === '' || $captchaValue === '') {
                    header('Location: /reset-password?error=请完成安全验证');
                    exit;
                }
                
                if (!$verificationPlugin->verify($captchaValue, $captchaToken)) {
                    header('Location: /reset-password?error=安全验证失败，请重试');
                    exit;
                }
            }
            
            $identifier = trim((string)($_POST['identifier'] ?? '')); // 用户名 / 邮箱 / 手机号
            $method = (string)($_POST['method'] ?? 'email'); // email | sms

            if ($identifier === '') {
                header('Location: /reset-password?error=请输入账号、邮箱或手机号');
                exit;
            }

            try {
                $pdo = Database::pdo();
                $table = Database::prefix() . 'users';

                // 查找用户
                $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE `username` = :id OR `email` = :id OR `phone` = :id LIMIT 1");
                $stmt->execute([':id' => $identifier]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$user) {
                    header('Location: /reset-password?error=账号不存在');
                    exit;
                }

                $code = random_int(100000, 999999);
                $token = bin2hex(random_bytes(16));
                $expiresAt = time() + 15 * 60;

                // 用 session 简单保存，避免额外建表（后续如需更专业可改为数据库表）
                $_SESSION['pwd_reset'] = [
                    'user_id' => (int)$user['id'],
                    'method' => $method,
                    'code' => (string)$code,
                    'token' => $token,
                    'expires_at' => $expiresAt,
                ];

                if ($method === 'sms') {
                    $phone = (string)($user['phone'] ?? '');
                    if ($phone === '') {
                        header('Location: /reset-password?error=' . urlencode('该账号未绑定手机号，无法使用短信找回'));
                        exit;
                    }
                    try {
                        $this->sendSmsResetCode($phone, $code);
                    } catch (\Exception $e) {
                        $errorMsg = $e->getMessage();
                        header('Location: /reset-password?error=' . urlencode($errorMsg));
                        exit;
                    }
                } else {
                    $email = (string)($user['email'] ?? '');
                    if ($email === '') {
                        header('Location: /reset-password?error=' . urlencode('该账号未绑定邮箱，无法使用邮箱找回'));
                        exit;
                    }
                    try {
                        $this->sendEmailResetCode($email, $code);
                    } catch (\Exception $e) {
                        $errorMsg = $e->getMessage();
                        header('Location: /reset-password?error=' . urlencode($errorMsg));
                        exit;
                    }
                }

                header('Location: /reset-password?token=' . urlencode($token));
                exit;
            } catch (\Exception $e) {
                error_log('发送重置密码验证码失败: ' . $e->getMessage());
                $errorMsg = $e->getMessage();
                if (strpos($errorMsg, '邮件') !== false || strpos($errorMsg, '邮件插件') !== false || strpos($errorMsg, '邮件配置') !== false ||
                    strpos($errorMsg, '短信') !== false || strpos($errorMsg, '短信插件') !== false || strpos($errorMsg, '短信配置') !== false ||
                    strpos($errorMsg, '发送失败') !== false || strpos($errorMsg, '未配置') !== false) {
                    header('Location: /reset-password?error=' . urlencode($errorMsg));
                } else {
                    header('Location: /reset-password?error=' . urlencode('发送验证码失败：' . $errorMsg));
                }
                exit;
            }
        }
        
        // 第二步：重置密码
        $code = trim((string)($_POST['code'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

        $sessionData = $_SESSION['pwd_reset'] ?? null;
        if (!$sessionData || !is_array($sessionData)) {
            header('Location: /reset-password?error=' . urlencode('重置请求已失效，请重新获取验证码'));
            exit;
        }

        if ($token === '' || $token !== (string)$sessionData['token']) {
            header('Location: /reset-password?token=' . urlencode($token) . '&error=' . urlencode('链接已失效，请重新获取验证码'));
            exit;
        }

        if (time() > (int)$sessionData['expires_at']) {
            unset($_SESSION['pwd_reset']);
            header('Location: /reset-password?error=' . urlencode('验证码已过期，请重新获取'));
            exit;
        }

        if ($code === '' || $code !== (string)$sessionData['code']) {
            header('Location: /reset-password?token=' . urlencode($token) . '&error=' . urlencode('验证码错误'));
            exit;
        }

        if ($password === '' || $password !== $passwordConfirm) {
            header('Location: /reset-password?token=' . urlencode($token) . '&error=' . urlencode('两次输入的密码不一致'));
            exit;
        }

        try {
            $pdo = Database::pdo();
            $table = Database::prefix() . 'users';

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE `{$table}` SET `password` = :pwd WHERE `id` = :id");
            $stmt->execute([
                ':pwd' => $hashedPassword,
                ':id' => (int)$sessionData['user_id'],
            ]);

            unset($_SESSION['pwd_reset']);

            header('Location: /login?success=1');
            exit;
        } catch (\Exception $e) {
            error_log('重置密码失败: ' . $e->getMessage());
            header('Location: /reset-password?token=' . urlencode($token) . '&error=' . urlencode('重置密码失败，请稍后重试'));
            exit;
        }
    }

    /**
     * 发送重置密码验证码（AJAX API）
     */
    public function sendResetCode()
    {
        header('Content-Type: application/json; charset=utf-8');

        // 验证验证码（如果启用了验证插件）
        $verificationPlugin = $this->getVerificationPlugin('forgot_password');
        if ($verificationPlugin && method_exists($verificationPlugin, 'verify')) {
            $captchaToken = $_POST['captcha_token'] ?? '';
            
            // 兼容多种验证码类型
            $captchaValue = $_POST['captcha_value'] ?? '';
            if ($captchaValue === '' && isset($_POST['cf-turnstile-response'])) {
                $captchaValue = (string)$_POST['cf-turnstile-response'];
            }
            if ($captchaValue === '' && isset($_POST['verification_response'])) {
                $captchaValue = (string)$_POST['verification_response'];
            }
            if ($captchaValue === '' && isset($_POST['g-recaptcha-response'])) {
                $captchaValue = (string)$_POST['g-recaptcha-response'];
            }
            
            if ($captchaToken === '' || $captchaValue === '') {
                echo json_encode(['success' => false, 'message' => '请完成安全验证']);
                return;
            }
            
            if (!$verificationPlugin->verify($captchaValue, $captchaToken)) {
                echo json_encode(['success' => false, 'message' => '安全验证失败，请重试']);
                return;
            }
        }
        
        $identifier = trim((string)($_POST['identifier'] ?? '')); // 用户名 / 邮箱 / 手机号
        $method = (string)($_POST['method'] ?? 'email'); // email | sms

        if ($identifier === '') {
            echo json_encode(['success' => false, 'message' => '请输入账号、邮箱或手机号']);
            return;
        }

        try {
            $pdo = Database::pdo();
            $table = Database::prefix() . 'users';

            // 查找用户
            $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE `username` = :id OR `email` = :id OR `phone` = :id LIMIT 1");
            $stmt->execute([':id' => $identifier]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['success' => false, 'message' => '账号不存在']);
                return;
            }

            $code = random_int(100000, 999999);
            $token = bin2hex(random_bytes(16));
            $expiresAt = time() + 15 * 60;

            // 用 session 简单保存，避免额外建表（后续如需更专业可改为数据库表）
            $_SESSION['pwd_reset'] = [
                'user_id' => (int)$user['id'],
                'method' => $method,
                'code' => (string)$code,
                'token' => $token,
                'expires_at' => $expiresAt,
            ];

            if ($method === 'sms') {
                $phone = (string)($user['phone'] ?? '');
                if ($phone === '') {
                    echo json_encode(['success' => false, 'message' => '该账号未绑定手机号，无法使用短信找回']);
                    return;
                }
                try {
                    $this->sendSmsResetCode($phone, $code);
                } catch (\Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    return;
                }
            } else {
                $email = (string)($user['email'] ?? '');
                if ($email === '') {
                    echo json_encode(['success' => false, 'message' => '该账号未绑定邮箱，无法使用邮箱找回']);
                    return;
                }
                try {
                    $this->sendEmailResetCode($email, $code);
                } catch (\Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    return;
                }
            }

            echo json_encode([
                'success' => true,
                'message' => '验证码已发送，请注意查收',
                'token' => $token
            ]);
        } catch (\Exception $e) {
            error_log('发送重置密码验证码失败: ' . $e->getMessage());
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, '邮件') !== false || strpos($errorMsg, '邮件插件') !== false || strpos($errorMsg, '邮件配置') !== false ||
                strpos($errorMsg, '短信') !== false || strpos($errorMsg, '短信插件') !== false || strpos($errorMsg, '短信配置') !== false ||
                strpos($errorMsg, '发送失败') !== false || strpos($errorMsg, '未配置') !== false) {
                echo json_encode(['success' => false, 'message' => $errorMsg]);
            } else {
                echo json_encode(['success' => false, 'message' => '发送验证码失败：' . $errorMsg]);
            }
        }
    }

    /**
     * 注册页发送短信/邮箱验证码
     */
    public function sendRegisterCode()
    {
        header('Content-Type: application/json; charset=utf-8');

        $method = (string)($_POST['method'] ?? 'email'); // email | phone
        $target = trim((string)($_POST['target'] ?? ''));

        if ($target === '') {
            echo json_encode(['success' => false, 'message' => '请先填写邮箱或手机号']);
            return;
        }

        try {
            // 读取注册设置，判断对应方式是否启用
            $registerEmailEnabled = Setting::get('register_email_enabled');
            $registerPhoneEnabled = Setting::get('register_phone_enabled');
        } catch (\Exception $e) {
            $registerEmailEnabled = '1';
            $registerPhoneEnabled = '1';
        }
        if ($registerEmailEnabled === null || $registerEmailEnabled === '') $registerEmailEnabled = '1';
        if ($registerPhoneEnabled === null || $registerPhoneEnabled === '') $registerPhoneEnabled = '1';

        if ($method === 'email') {
            if ($registerEmailEnabled !== '1') {
                echo json_encode(['success' => false, 'message' => '邮箱注册已关闭']);
                return;
            }
            if (!filter_var($target, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => '请输入正确的邮箱地址']);
                return;
            }
        } elseif ($method === 'phone') {
            if ($registerPhoneEnabled !== '1') {
                echo json_encode(['success' => false, 'message' => '手机号注册已关闭']);
                return;
            }
            if (!preg_match('/^1[3-9]\d{9}$/', $target)) {
                echo json_encode(['success' => false, 'message' => '请输入正确的手机号']);
                return;
            }
        } else {
            echo json_encode(['success' => false, 'message' => '不支持的验证方式']);
            return;
        }

        // 生成验证码并存入 Session
        $code = (string)random_int(100000, 999999);

        // 从系统配置读取有效时间（分钟），默认 10 分钟，最大 60 分钟
        try {
            $expireMinutes = (int)Setting::get('register_code_expire_minutes');
        } catch (\Throwable $e) {
            $expireMinutes = 10;
        }
        if ($expireMinutes <= 0) {
            $expireMinutes = 10;
        } elseif ($expireMinutes > 60) {
            $expireMinutes = 60;
        }
        $expiresAt = time() + $expireMinutes * 60;
        $_SESSION['register_verify'] = [
            'method' => $method,
            'target' => $target,
            'code' => $code,
            'expires_at' => $expiresAt,
        ];

        // 实际发送
        try {
            $ok = false;
            $errorMessage = '';
            
            if ($method === 'email') {
                if (function_exists('send_system_mail')) {
                    // 尝试使用模板
                    $template = null;
                    if (function_exists('get_email_template')) {
                        $template = get_email_template('register_verify_email', [
                            'code' => $code,
                            'minutes' => (string)$expireMinutes
                        ]);
                    }
                    
                    if ($template) {
                        $subject = $template['subject'] ?: '注册验证码';
                        $content = $template['body'];
                    } else {
                        // 回退到默认文本
                        $subject = '注册验证码';
                        $content = '您的注册验证码为：' . $code . '，' . $expireMinutes . ' 分钟内有效。';
                    }
                    
                    $ok = send_system_mail($target, $subject, $content, $errorMessage);
                    if (!$ok) {
                        error_log('发送注册验证码邮件失败: method=email, target=' . $target . ', error=' . $errorMessage);
                        // 优化错误信息显示，将技术性错误转换为用户友好的提示
                        $errorMsg = $errorMessage ?: '邮件发送失败，请检查邮件配置';
                        // 简化技术性错误信息
                        if (stripos($errorMsg, 'SMTP连接失败') !== false || stripos($errorMsg, 'Could not connect') !== false || stripos($errorMsg, 'SSL') !== false) {
                            $errorMsg = '邮件服务器连接失败，请检查SMTP配置或联系管理员';
                        } elseif (stripos($errorMsg, 'authentication') !== false) {
                            $errorMsg = '邮件服务器认证失败，请检查用户名和密码';
                        } elseif (stripos($errorMsg, 'timeout') !== false) {
                            $errorMsg = '连接邮件服务器超时，请稍后重试';
                        }
                        echo json_encode(['success' => false, 'message' => $errorMsg]);
                        return;
                    }
                } else {
                    error_log('发送注册验证码失败: 邮件发送功能未配置');
                    echo json_encode(['success' => false, 'message' => '邮件发送功能未配置，请联系管理员']);
                    return;
                }
            } else {
                if (function_exists('send_system_sms')) {
                    $content = '您的注册验证码为：{code}，' . $expireMinutes . ' 分钟内有效。';
                    $ok = send_system_sms($target, $content, ['code' => $code, 'minutes' => $expireMinutes]);
                    if (!$ok) {
                        error_log('发送注册验证码短信失败: method=phone, target=' . $target);
                        echo json_encode(['success' => false, 'message' => '短信发送失败，请检查短信配置']);
                        return;
                    }
                } else {
                    error_log('发送注册验证码失败: 短信发送功能未配置');
                    echo json_encode(['success' => false, 'message' => '短信发送功能未配置，请联系管理员']);
                    return;
                }
            }

            if ($ok) {
                echo json_encode(['success' => true, 'message' => '验证码已发送，请注意查收']);
            } else {
                echo json_encode(['success' => false, 'message' => $errorMessage ?: '发送失败，请稍后重试']);
            }
        } catch (\Exception $e) {
            $errorMsg = '发送异常: ' . $e->getMessage();
            error_log('发送注册验证码异常: method=' . $method . ', target=' . $target . ', error=' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $errorMsg]);
        }
    }

    /**
     * 使用后台启用的邮箱插件发送重置验证码
     */
    private function sendEmailResetCode(string $email, string $code): void
    {
        // 这里复用旧架构的邮件插件体系，通过 admin\lib\PluginLoader（如有）或直接实例化启用的邮件插件。
        // 为简单起见，这里走系统内置的邮件发送辅助函数（如存在），否则留空即可由后续扩展。
        if (function_exists('send_system_mail')) {
            // 尝试使用模板
            $template = null;
            if (function_exists('get_email_template')) {
                $template = get_email_template('reset_password_email', [
                    'code' => $code,
                    'minutes' => '15'
                ]);
            }
            
            if ($template) {
                $subject = $template['subject'] ?: '密码重置验证码';
                $content = $template['body'];
            } else {
                // 回退到默认文本
                $subject = '密码重置验证码';
                $content = '您的密码重置验证码为：' . $code . '，15 分钟内有效。';
            }
            
            $errorMessage = null;
            $ok = send_system_mail($email, $subject, $content, $errorMessage);
            if (!$ok) {
                error_log('发送重置邮件失败: ' . ($errorMessage ?: '未知错误'));
                // 优化错误信息显示
                $errorMsg = $errorMessage ?: '邮件发送失败，请检查邮件配置';
                // 简化技术性错误信息
                if (stripos($errorMsg, 'SMTP连接失败') !== false || stripos($errorMsg, 'Could not connect') !== false || stripos($errorMsg, 'SSL') !== false) {
                    $errorMsg = '邮件服务器连接失败，请检查SMTP配置或联系管理员';
                } elseif (stripos($errorMsg, 'authentication') !== false) {
                    $errorMsg = '邮件服务器认证失败，请检查用户名和密码';
                } elseif (stripos($errorMsg, 'timeout') !== false) {
                    $errorMsg = '连接邮件服务器超时，请稍后重试';
                }
                throw new \Exception($errorMsg);
            }
        } else {
            throw new \Exception('邮件发送功能未配置，请联系管理员');
        }
    }

    /**
     * 使用后台启用的短信插件发送重置验证码
     */
    private function sendSmsResetCode(string $phone, string $code): void
    {
        // 这里调用短信插件统一入口（如果项目已有），否则简单占位，方便后续接入。
        if (function_exists('send_system_sms')) {
            $content = '您的密码重置验证码为：{code}，15 分钟内有效。';
            $params = ['code' => $code];
            $ok = send_system_sms($phone, $content, $params);
            if (!$ok) {
                error_log('发送重置短信失败: phone=' . $phone);
                throw new \Exception('短信发送失败，请检查短信配置');
            }
        } else {
            error_log('发送重置短信失败: 短信发送功能未配置');
            throw new \Exception('短信发送功能未配置，请联系管理员');
        }
    }

    /**
     * 刷新验证码（AJAX API）
     */
    public function refreshCaptcha()
    {
        header('Content-Type: application/json');
        
        try {
            $token = $_GET['token'] ?? '';
            
            // 获取验证码插件
            $verificationPlugin = $this->getVerificationPlugin('register');
            if (!$verificationPlugin || !method_exists($verificationPlugin, 'generate')) {
                echo json_encode([
                    'success' => false,
                    'message' => '验证码插件未启用'
                ]);
                return;
            }
            
            // 生成新的验证码
            $captcha = $verificationPlugin->generate();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'token' => $captcha['token'],
                    'question' => $captcha['question']
                ]
            ]);
        } catch (\Exception $e) {
            error_log('刷新验证码失败: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => '刷新验证码失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 获取客户端IP地址
     * 支持代理和负载均衡环境
     *
     * @return string IP地址
     */
    private function getClientIp(): string
    {
        // 检查各种可能的IP头信息
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',      // Cloudflare
            'HTTP_X_REAL_IP',              // Nginx
            'HTTP_X_FORWARDED_FOR',        // 代理/负载均衡
            'HTTP_CLIENT_IP',              // 部分代理
            'REMOTE_ADDR'                  // 直连IP
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim($_SERVER[$key]);
                
                // 处理 X-Forwarded-For 可能包含多个IP的情况
                if ($key === 'HTTP_X_FORWARDED_FOR') {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // 验证IP地址格式
                if ($this->isValidIp($ip)) {
                    return $ip;
                }
            }
        }

        // 如果都获取不到，返回未知
        return '0.0.0.0';
    }

    /**
     * 验证IP地址格式
     *
     * @param string $ip IP地址
     * @return bool 是否有效
     */
    private function isValidIp(string $ip): bool
    {
        // 过滤掉内网IP和无效IP
        if (in_array($ip, ['127.0.0.1', 'localhost', '::1'])) {
            return false;
        }

        // 验证IPv4格式
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }

        // 验证IPv6格式
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }

        return false;
    }
}
