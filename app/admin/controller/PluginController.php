<?php
namespace app\admin\controller;

use app\services\Database;
use app\services\SqlRunner;

class PluginController extends BaseController
{
    public function index()
    {
        $title = '插件管理';
        $currentPage = 'plugins';
        
        $pluginsDir = $this->getPluginsDir();
        $plugins = $this->discoverPlugins($pluginsDir);

        ob_start();
        require __DIR__ . '/../views/plugins.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    public function install()
    {
        $pluginId = (string)($_POST['plugin'] ?? '');
        $pluginId = trim($pluginId);
        $redirectCat = (string)($_POST['redirect_cat'] ?? '');
        $isAjax = !empty($_POST['_ajax']) || !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
        
        if ($pluginId === '') {
            if ($isAjax) {
                $this->jsonResponse(false, '插件ID不能为空');
                return;
            }
            $this->redirectToIndex($redirectCat);
        }

        $pluginsDir = $this->getPluginsDir();
        $configPath = $this->getPluginConfigPath($pluginsDir, $pluginId);
        $isLegacy = !is_file($configPath);

        // 旧架构插件：没有 plugin.json，只维护启用状态，不执行 SQL 文件
        if ($isLegacy) {
            $states = $this->loadLegacyPluginStates();
            $states[$pluginId] = [
                'installed' => true,
                'status' => 'enabled',
            ];
            $this->saveLegacyPluginStates($states);
            
            if ($isAjax) {
                $this->jsonResponse(true, '插件安装成功', [
                    'plugin' => $pluginId,
                    'installed' => true,
                    'status' => 'enabled'
                ]);
                return;
            }
            $this->redirectToIndex($redirectCat);
        }

        $config = $this->readJson($configPath);

        $type = (string)($config['type'] ?? '');
        if ($type === 'app') {
            $frontendEntry = (string)($config['frontend_entry'] ?? '');
            $adminEntry = (string)($config['admin_entry'] ?? '');
            if (trim($frontendEntry) === '' || trim($adminEntry) === '') {
                $errorMsg = '安装失败：应用类插件必须配置 frontend_entry 与 admin_entry。';
                if ($isAjax) {
                    $this->jsonResponse(false, $errorMsg);
                    return;
                }
                $this->error($errorMsg, 400);
                return;
            }
        }

        $mainClassFilename = (string)($config['main_class'] ?? 'Plugin.php');
        $mainClassPath = realpath($pluginsDir . DIRECTORY_SEPARATOR . $pluginId . DIRECTORY_SEPARATOR . $mainClassFilename);
        if (!$mainClassPath || !is_readable($mainClassPath)) {
            $errorMsg = '安装失败：缺少主类文件：' . htmlspecialchars($mainClassFilename, ENT_QUOTES, 'UTF-8');
            if ($isAjax) {
                $this->jsonResponse(false, $errorMsg);
                return;
            }
            $this->error($errorMsg, 400);
            return;
        }

        $installRel = $config['install_sql'] ?? null;
        if ($installRel !== null && $installRel !== '') {
            $installRel = (string)$installRel;
        $installPath = realpath($pluginsDir . DIRECTORY_SEPARATOR . $pluginId . DIRECTORY_SEPARATOR . $installRel);
        if (!$installPath || !is_readable($installPath)) {
            $errorMsg = '安装失败：缺少 SQL 文件：' . htmlspecialchars($installRel, ENT_QUOTES, 'UTF-8');
            if ($isAjax) {
                $this->jsonResponse(false, $errorMsg);
                return;
            }
            $this->error($errorMsg, 400);
            return;
        }

        $pdo = Database::pdo();
        SqlRunner::runSqlFile($pdo, $installPath, Database::prefix());
        }

        $config['installed'] = true;
        $config['status'] = 'enabled';

        // 如果是验证类插件，则确保同一类别下只启用一个（例如：基础验证 / 云验证 各只能启用一个）
        if (($config['type'] ?? '') === 'verification') {
            $this->disableOtherVerificationPlugins($pluginId, $config);
        }

        $this->writeJson($configPath, $config);

        // 同步更新数据库中的插件状态
        $this->syncPluginToDatabase($pluginId, $config);

        if ($isAjax) {
            $this->jsonResponse(true, '插件安装成功', [
                'plugin' => $pluginId,
                'installed' => true,
                'status' => 'enabled'
            ]);
            return;
        }
        $this->redirectToIndex($redirectCat);
    }

    public function uninstall()
    {
        $pluginId = (string)($_POST['plugin'] ?? '');
        $pluginId = trim($pluginId);
        $redirectCat = (string)($_POST['redirect_cat'] ?? '');
        $isAjax = !empty($_POST['_ajax']) || !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
        
        if ($pluginId === '') {
            if ($isAjax) {
                $this->jsonResponse(false, '插件ID不能为空');
                return;
            }
            $this->redirectToIndex($redirectCat);
        }

        $pluginsDir = $this->getPluginsDir();
        $configPath = $this->getPluginConfigPath($pluginsDir, $pluginId);
        $isLegacy = !is_file($configPath);

        // 旧架构插件：仅更新本地状态，不执行 SQL
        if ($isLegacy) {
            $states = $this->loadLegacyPluginStates();
            $states[$pluginId] = [
                'installed' => false,
                'status' => 'disabled',
            ];
            $this->saveLegacyPluginStates($states);
            
            if ($isAjax) {
                $this->jsonResponse(true, '插件卸载成功', [
                    'plugin' => $pluginId,
                    'installed' => false,
                    'status' => 'disabled'
                ]);
                return;
            }
            $this->redirectToIndex($redirectCat);
        }

        $config = $this->readJson($configPath);

        $installed = $config['installed'] ?? false;
        $installedOk = ($installed === true || $installed === 1 || $installed === '1' || $installed === 'true');
        if (!$installedOk) {
            if ($isAjax) {
                $this->jsonResponse(false, '插件未安装');
                return;
            }
            $this->redirectToIndex($redirectCat);
        }

        $status = (string)($config['status'] ?? 'disabled');
        if ($status === 'enabled') {
            if ($isAjax) {
                $this->jsonResponse(false, '卸载前请先禁用插件');
                return;
            }
            $this->error('卸载前请先禁用插件。', 400);
            return;
        }

        $uninstallRel = (string)($config['uninstall_sql'] ?? 'database/uninstall.sql');
        $uninstallPath = realpath($pluginsDir . DIRECTORY_SEPARATOR . $pluginId . DIRECTORY_SEPARATOR . $uninstallRel);
        if ($uninstallPath && is_readable($uninstallPath)) {
            $pdo = Database::pdo();
            SqlRunner::runSqlFile($pdo, $uninstallPath, Database::prefix());
        }

        $config['installed'] = false;
        $config['status'] = 'disabled';
        $this->writeJson($configPath, $config);

        // 同步更新数据库中的插件状态
        $this->syncPluginToDatabase($pluginId, $config);

        if ($isAjax) {
            $this->jsonResponse(true, '插件卸载成功', [
                'plugin' => $pluginId,
                'installed' => false,
                'status' => 'disabled'
            ]);
            return;
        }
        $this->redirectToIndex($redirectCat);
    }

    public function toggle()
    {
        $pluginId = (string)($_POST['plugin'] ?? '');
        $pluginId = trim($pluginId);
        $redirectCat = (string)($_POST['redirect_cat'] ?? '');
        $isAjax = !empty($_POST['_ajax']) || !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
        
        if ($pluginId === '') {
            if ($isAjax) {
                $this->jsonResponse(false, '插件ID不能为空');
                return;
            }
            $this->redirectToIndex($redirectCat);
        }

        $pluginsDir = $this->getPluginsDir();
        $configPath = $this->getPluginConfigPath($pluginsDir, $pluginId);
        $isLegacy = !is_file($configPath);

        // 旧架构插件：仅在本地状态文件中切换启用/禁用
        if ($isLegacy) {
            $states = $this->loadLegacyPluginStates();
            $current = $states[$pluginId] ?? ['installed' => true, 'status' => 'enabled'];

            // 未安装则视为无法切换
            if (empty($current['installed'])) {
                if ($isAjax) {
                    $this->jsonResponse(false, '插件未安装');
                    return;
                }
                $this->redirectToIndex($redirectCat);
            }

            $status = (string)($current['status'] ?? 'enabled');
            $newStatus = $status === 'enabled' ? 'disabled' : 'enabled';
            $current['status'] = $newStatus;
            $states[$pluginId] = $current;
            $this->saveLegacyPluginStates($states);
            
            if ($isAjax) {
                $this->jsonResponse(true, $newStatus === 'enabled' ? '插件已启用' : '插件已禁用', [
                    'plugin' => $pluginId,
                    'status' => $newStatus
                ]);
                return;
            }
            $this->redirectToIndex($redirectCat);
        }

        $config = $this->readJson($configPath);

        $installed = $config['installed'] ?? false;
        $installedOk = ($installed === true || $installed === 1 || $installed === '1' || $installed === 'true');
        if (!$installedOk) {
            if ($isAjax) {
                $this->jsonResponse(false, '请先安装插件后再启用/禁用');
                return;
            }
            $this->error('请先安装插件后再启用/禁用。', 400);
            return;
        }

        $status = (string)($config['status'] ?? 'disabled');
        $newStatus = $status === 'enabled' ? 'disabled' : 'enabled';
        $config['status'] = $newStatus;

        // 如果是切换为启用状态的验证类插件，则关闭同一类别下的其他验证插件
        if ($newStatus === 'enabled' && ($config['type'] ?? '') === 'verification') {
            $this->disableOtherVerificationPlugins($pluginId, $config);
        }

        $this->writeJson($configPath, $config);

        // 同步更新数据库中的插件状态
        $this->syncPluginToDatabase($pluginId, $config);

        if ($isAjax) {
            $this->jsonResponse(true, $newStatus === 'enabled' ? '插件已启用' : '插件已禁用', [
                'plugin' => $pluginId,
                'status' => $newStatus,
                'installed' => true
            ]);
            return;
        }
        $this->redirectToIndex($redirectCat);
    }

    public function config()
    {
        try {
            $pluginId = (string)($_GET['plugin'] ?? '');
            $pluginId = trim($pluginId);
            if ($pluginId === '') {
                $this->error('插件ID不能为空', 400);
                return;
            }

            $pluginsDir = $this->getPluginsDir();
            if (!$pluginsDir) {
                $this->error('插件目录不存在', 500);
                return;
            }
            
            $configPath = $this->getPluginConfigPath($pluginsDir, $pluginId);
            $isLegacy = !file_exists($configPath);
            
            // 处理旧架构插件配置
            if ($isLegacy) {
                $this->handleLegacyPluginConfig($pluginId, $pluginsDir);
                return;
            }

            $pluginConfig = $this->readJson($configPath);
            if (empty($pluginConfig)) {
                $this->error('插件配置文件无效', 400);
                return;
            }
            
            $installed = $pluginConfig['installed'] ?? false;
            $installedOk = ($installed === true || $installed === 1 || $installed === '1' || $installed === 'true');
            if (!$installedOk) {
                $this->error('请先安装插件', 400);
                return;
            }

            // 加载插件类
            $mainClassFilename = (string)($pluginConfig['main_class'] ?? 'Plugin.php');
            $mainClassPath = realpath($pluginsDir . DIRECTORY_SEPARATOR . $pluginId . DIRECTORY_SEPARATOR . $mainClassFilename);
            if (!$mainClassPath || !is_readable($mainClassPath)) {
                $this->error('插件主类文件不存在', 400);
                return;
            }

            $namespace = $pluginConfig['namespace'] ?? '';
            $className = $namespace . '\\' . pathinfo($mainClassFilename, PATHINFO_FILENAME);
            
            // 只有当类不存在时才加载文件，避免重复声明错误
            if (!class_exists($className)) {
                require_once $mainClassPath;
            }
            
            if (!class_exists($className)) {
                $this->error('插件类不存在: ' . $className, 400);
                return;
            }

            $pluginInstance = new $className();

            // 处理配置保存
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $rawData = file_get_contents('php://input');
                $postData = [];
                parse_str($rawData, $postData);
                $formData = [];
                if (isset($postData['config'])) {
                    if (is_string($postData['config'])) {
                        $formData = json_decode($postData['config'], true) ?? [];
                    } else {
                        $formData = $postData['config'];
                    }
                }
                
                // 清理formData中可能多次序列化的值
                $formData = $this->cleanSerializedValue($formData);
                // 确保formData中的所有字符串都是UTF-8编码
                $formData = $this->ensureUtf8Encoding($formData);
                
                // 获取当前配置，用于保留空密码字段的原有值
                $pdo = Database::pdo();
                $table = Database::prefix() . 'admin_plugins';
                $stmt = $pdo->prepare("SELECT `config_json` FROM `{$table}` WHERE `plugin_id` = ?");
                $stmt->execute([$pluginId]);
                $pluginData = $stmt->fetch(\PDO::FETCH_ASSOC);
                $currentConfig = [];
                if ($pluginData && !empty($pluginData['config_json'])) {
                    $configJson = $pluginData['config_json'];
                    // 确保JSON解码时正确处理UTF-8编码
                    $currentConfig = json_decode($configJson, true, 512, JSON_UNESCAPED_UNICODE);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log('JSON解码失败 [' . $pluginId . ']: ' . json_last_error_msg());
                        $currentConfig = [];
                    } else {
                        // 清理多次序列化的值
                        $currentConfig = $this->cleanSerializedValue($currentConfig);
                        // 确保currentConfig中的所有字符串都是UTF-8编码
                        $currentConfig = $this->ensureUtf8Encoding($currentConfig);
                    }
                }
                
                // 检查配置表单中的密码字段，如果为空则保留原有值
                // 同时清理formData中可能错误存储的字段定义
                if (method_exists($pluginInstance, 'getConfigForm')) {
                    $configForm = $pluginInstance->getConfigForm();
                    if (is_array($configForm)) {
                        foreach ($configForm as $field) {
                            $fieldName = $field['name'] ?? '';
                            $fieldType = $field['type'] ?? '';
                            if ($fieldType === 'password' && (!isset($formData[$fieldName]) || $formData[$fieldName] === '')) {
                                if (isset($currentConfig[$fieldName]) && !empty($currentConfig[$fieldName])) {
                                    $formData[$fieldName] = $currentConfig[$fieldName];
                                }
                            }
                            
                            // 检查formData中的值是否是字段定义本身（错误存储的情况）
                            if (!empty($fieldName) && isset($formData[$fieldName])) {
                                $value = $formData[$fieldName];
                                if (is_array($value) && (isset($value['type']) || isset($value['label']) || isset($value['title']) || isset($value['name']))) {
                                    // 这是字段定义，不是配置值，移除它
                                    unset($formData[$fieldName]);
                                } elseif (is_string($value) && !empty($value)) {
                                    // 检查是否是JSON字符串且包含字段定义的特征
                                    $decoded = json_decode($value, true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && (isset($decoded['type']) || isset($decoded['label']) || isset($decoded['title']) || isset($decoded['name']))) {
                                        // 这是字段定义的JSON字符串，移除它
                                        unset($formData[$fieldName]);
                                    }
                                }
                            }
                        }
                    }
                }
                
                $configJson = json_encode($formData, JSON_UNESCAPED_UNICODE);
                
                // 保存到数据库
                if ($pluginData) {
                    $stmt = $pdo->prepare("UPDATE `{$table}` SET `config_json` = ? WHERE `plugin_id` = ?");
                    $stmt->execute([$configJson, $pluginId]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO `{$table}` (`plugin_id`, `type`, `status`, `config_json`) VALUES (?, ?, ?, ?)");
                    $type = (string)($pluginConfig['type'] ?? '');
                    $status = (string)($pluginConfig['status'] ?? 'disabled');
                    $stmt->execute([$pluginId, $type, $status, $configJson]);
                }
                
                // 更新插件实例的配置
                if (method_exists($pluginInstance, 'updateConfig')) {
                    $pluginInstance->updateConfig($formData);
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => '配置已保存']);
                return;
            }

            // 获取当前配置
            $currentConfig = [];
            try {
                $pdo = Database::pdo();
                $table = Database::prefix() . 'admin_plugins';
                $stmt = $pdo->prepare("SELECT `config_json` FROM `{$table}` WHERE `plugin_id` = ?");
                $stmt->execute([$pluginId]);
                $pluginData = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($pluginData && !empty($pluginData['config_json'])) {
                    $configJson = $pluginData['config_json'];
                    // 确保JSON解码时正确处理UTF-8编码
                    $currentConfig = json_decode($configJson, true, 512, JSON_UNESCAPED_UNICODE);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log('JSON解码失败 [' . $pluginId . ']: ' . json_last_error_msg());
                        $currentConfig = [];
                    } else {
                        // 确保currentConfig中的所有字符串都是UTF-8编码
                        $currentConfig = $this->ensureUtf8Encoding($currentConfig);
                    }
                }
            } catch (\Exception $e) {
                error_log('获取插件配置失败 [' . $pluginId . ']: ' . $e->getMessage());
                // 继续执行，使用空配置
            }

            // 获取配置表单
            $configForm = [];
            try {
                if (method_exists($pluginInstance, 'getConfigForm')) {
                    $configForm = $pluginInstance->getConfigForm();
                    if (!is_array($configForm)) {
                        $configForm = [];
                    }
                }
            } catch (\Exception $e) {
                error_log('获取插件配置表单失败 [' . $pluginId . ']: ' . $e->getMessage());
                // 继续执行，使用空表单
            }

            // 确保所有变量都已定义
            if (!isset($currentConfig) || !is_array($currentConfig)) {
                $currentConfig = [];
            }
            if (!isset($pluginId) || empty($pluginId)) {
                $pluginId = '';
            }
            if (!isset($pluginConfig) || !is_array($pluginConfig)) {
                $pluginConfig = [];
            }
            if (!isset($configForm) || !is_array($configForm)) {
                $configForm = [];
            }
            if (!isset($title) || empty($title)) {
                $title = '插件配置 - ' . ($pluginConfig['name'] ?? $pluginId);
            }

            // 渲染配置页面
            ob_start();
            require __DIR__ . '/../views/plugin_config.php';
            $content = ob_get_clean();
            if ($content === false) {
                throw new \Exception('渲染配置页面失败');
            }
            echo $content;
        } catch (\Exception $e) {
            error_log('插件配置页面错误: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->error('加载配置页面失败: ' . $e->getMessage(), 500);
            return;
        }
    }

    /**
     * 处理旧架构插件配置
     */
    private function handleLegacyPluginConfig(string $pluginId, string $pluginsDir): void
    {
        try {
            $pluginPath = $pluginsDir . DIRECTORY_SEPARATOR . $pluginId;
            $configPhpPath = $pluginPath . DIRECTORY_SEPARATOR . 'config.php';
            
            if (!file_exists($configPhpPath)) {
                $this->error('插件配置文件不存在', 404);
                return;
            }
            
            // 检查插件是否已安装
            $states = $this->loadLegacyPluginStates();
            $state = $states[$pluginId] ?? ['installed' => false, 'status' => 'disabled'];
            if (empty($state['installed'])) {
                $this->error('请先安装插件', 400);
                return;
            }
            
            // 读取配置定义
            try {
                $configDef = require $configPhpPath;
                if (!is_array($configDef)) {
                    $configDef = [];
                }
                // 确保配置定义中的字符串都是UTF-8编码
                $configDef = $this->ensureUtf8Encoding($configDef);
            } catch (\Exception $e) {
                error_log('读取旧架构插件配置文件失败 [' . $pluginId . ']: ' . $e->getMessage());
                $this->error('读取配置文件失败: ' . $e->getMessage(), 500);
                return;
            }
            
            // 处理配置保存
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rawData = file_get_contents('php://input');
            $postData = [];
            parse_str($rawData, $postData);
            $formData = [];
            if (isset($postData['config'])) {
                if (is_string($postData['config'])) {
                    $formData = json_decode($postData['config'], true) ?? [];
                } else {
                    $formData = $postData['config'];
                }
            }
            
            // 清理formData中可能多次序列化的值
            $formData = $this->cleanSerializedValue($formData);
            // 确保formData中的所有字符串都是UTF-8编码
            $formData = $this->ensureUtf8Encoding($formData);
            
            // 保存配置到数据库
            try {
                $pdo = Database::pdo();
                $table = Database::prefix() . 'admin_plugins';
                $stmt = $pdo->prepare("SELECT `config_json` FROM `{$table}` WHERE `plugin_id` = ?");
                $stmt->execute([$pluginId]);
                $pluginData = $stmt->fetch(\PDO::FETCH_ASSOC);
                $currentConfig = [];
                if ($pluginData && !empty($pluginData['config_json'])) {
                    $configJson = $pluginData['config_json'];
                    // 确保JSON解码时正确处理UTF-8编码
                    $currentConfig = json_decode($configJson, true, 512, JSON_UNESCAPED_UNICODE);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log('JSON解码失败 [' . $pluginId . ']: ' . json_last_error_msg());
                        $currentConfig = [];
                    } else {
                        // 清理多次序列化的值
                        $currentConfig = $this->cleanSerializedValue($currentConfig);
                        // 确保currentConfig中的所有字符串都是UTF-8编码
                        $currentConfig = $this->ensureUtf8Encoding($currentConfig);
                    }
                }
                
                // 检查config.php中定义的密码字段，如果为空则保留原有值
                // 同时清理formData中可能错误存储的字段定义
                foreach ($configDef as $fieldName => $field) {
                    if (is_array($field)) {
                        $fieldType = $field['type'] ?? '';
                        if ($fieldType === 'password' && (!isset($formData[$fieldName]) || $formData[$fieldName] === '')) {
                            if (isset($currentConfig[$fieldName]) && !empty($currentConfig[$fieldName])) {
                                $formData[$fieldName] = $currentConfig[$fieldName];
                            }
                        }
                        
                        // 检查formData中的值是否是字段定义本身（错误存储的情况）
                        if (isset($formData[$fieldName])) {
                            $value = $formData[$fieldName];
                            if (is_array($value) && (isset($value['type']) || isset($value['label']) || isset($value['title']))) {
                                // 这是字段定义，不是配置值，移除它
                                unset($formData[$fieldName]);
                            } elseif (is_string($value) && !empty($value)) {
                                // 检查是否是JSON字符串且包含字段定义的特征
                                $decoded = json_decode($value, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && (isset($decoded['type']) || isset($decoded['label']) || isset($decoded['title']))) {
                                    // 这是字段定义的JSON字符串，移除它
                                    unset($formData[$fieldName]);
                                }
                            }
                        }
                    }
                }
                
                $configJson = json_encode($formData, JSON_UNESCAPED_UNICODE);
                
                if ($pluginData) {
                    $stmt = $pdo->prepare("UPDATE `{$table}` SET `config_json` = ? WHERE `plugin_id` = ?");
                    $stmt->execute([$configJson, $pluginId]);
                } else {
                    // 获取插件信息
                    $segments = explode('/', $pluginId);
                    $type = $segments[0] ?? 'unknown';
                    $stmt = $pdo->prepare("INSERT INTO `{$table}` (`plugin_id`, `type`, `status`, `config_json`) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$pluginId, $type, (string)($state['status'] ?? 'disabled'), $configJson]);
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => '配置已保存']);
                return;
            } catch (\Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '保存配置失败: ' . $e->getMessage()]);
                return;
            }
            }
            
            // 获取当前配置
            $currentConfig = [];
            try {
                $pdo = Database::pdo();
                $table = Database::prefix() . 'admin_plugins';
                $stmt = $pdo->prepare("SELECT `config_json` FROM `{$table}` WHERE `plugin_id` = ?");
                $stmt->execute([$pluginId]);
                $pluginData = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($pluginData && !empty($pluginData['config_json'])) {
                    $configJson = $pluginData['config_json'];
                    // 确保JSON解码时正确处理UTF-8编码
                    $currentConfig = json_decode($configJson, true, 512, JSON_UNESCAPED_UNICODE);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log('JSON解码失败 [' . $pluginId . ']: ' . json_last_error_msg());
                        $currentConfig = [];
                    } else {
                        // 清理多次序列化的值
                        $currentConfig = $this->cleanSerializedValue($currentConfig);
                    }
                }
            } catch (\Exception $e) {
                error_log('获取旧架构插件配置失败 [' . $pluginId . ']: ' . $e->getMessage());
                // 继续执行，使用空配置
            }
            
            // 获取插件名称
            $pluginName = $pluginId;
            $pluginClassFile = null;
            foreach (glob($pluginPath . DIRECTORY_SEPARATOR . '*Plugin.php') as $phpFile) {
                $pluginClassFile = $phpFile;
                break;
            }
            
            if ($pluginClassFile && is_readable($pluginClassFile)) {
                try {
                    $fileContent = file_get_contents($pluginClassFile);
                    if (preg_match('/\$info\s*=\s*array\s*\(/s', $fileContent, $match, PREG_OFFSET_CAPTURE)) {
                        $startPos = $match[0][1] + strlen($match[0][0]);
                        $depth = 1;
                        $pos = $startPos;
                        $len = strlen($fileContent);
                        
                        while ($pos < $len && $depth > 0) {
                            $char = $fileContent[$pos];
                            if ($char === '(') {
                                $depth++;
                            } elseif ($char === ')') {
                                $depth--;
                                if ($depth === 0) {
                                    $infoStr = substr($fileContent, $startPos, $pos - $startPos);
                                    if (preg_match('/["\']title["\']\s*=>\s*["\']([^"\']+)["\']/', $infoStr, $titleMatch)) {
                                        $pluginName = trim($titleMatch[1]);
                                    }
                                    break;
                                }
                            }
                            $pos++;
                        }
                    }
                } catch (\Exception $e) {
                    // 忽略错误，使用默认名称
                    error_log('读取旧架构插件名称失败 [' . $pluginId . ']: ' . $e->getMessage());
                }
            }
            
            // 确保所有变量都已定义
            if (!isset($configDef) || !is_array($configDef)) {
                $configDef = [];
            }
            if (!isset($currentConfig) || !is_array($currentConfig)) {
                $currentConfig = [];
            }
            if (!isset($pluginName) || empty($pluginName)) {
                $pluginName = $pluginId;
            }
            if (!isset($title) || empty($title)) {
                $title = '插件配置 - ' . $pluginName;
            }
            
            // 渲染配置页面
            ob_start();
            require __DIR__ . '/../views/plugin_config_legacy.php';
            $content = ob_get_clean();
            if ($content === false) {
                throw new \Exception('渲染旧架构插件配置页面失败');
            }
            echo $content;
        } catch (\Exception $e) {
            error_log('旧架构插件配置页面错误: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->error('加载配置页面失败: ' . $e->getMessage(), 500);
            return;
        }
    }

    private function redirectToIndex(string $category = ''): void
    {
        $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/');
        $url = '/' . $adminPrefix . '/plugins';
        $category = trim((string)$category, '/');
        if ($category !== '') {
            $url .= '?cat=' . rawurlencode($category);
        }
        header('Location: ' . $url);
        exit;
    }

    private function getPluginsDir(): string
    {
        // 从当前控制器目录回溯到项目根目录，再进入 public/plugins
        $dir = realpath(__DIR__ . '/../../../public/plugins');
        if (!$dir || !is_dir($dir)) {
            $this->error('插件目录不存在', 500);
            return '';
        }
        return $dir;
    }

    private function discoverPlugins(string $pluginsDir): array
    {
        $plugins = [];
        $existsMap = [];
        $legacyStates = $this->loadLegacyPluginStates();

        // 批量从数据库获取所有插件信息，避免 N+1 查询问题
        $dbPluginsMap = [];
        try {
            $pdo = Database::pdo();
            $table = Database::prefix() . 'admin_plugins';
            $stmt = $pdo->prepare("SELECT plugin_id, name, version, description, status FROM `{$table}`");
            $stmt->execute();
            $allDbPlugins = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // 构建插件ID到数据库信息的映射
            foreach ($allDbPlugins as $dbPlugin) {
                $dbPluginsMap[$dbPlugin['plugin_id']] = $dbPlugin;
            }
        } catch (\Exception $e) {
            error_log('批量获取插件数据库信息失败: ' . $e->getMessage());
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pluginsDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof \SplFileInfo) {
                continue;
            }
            if (!$fileInfo->isFile()) {
                continue;
            }
            if ($fileInfo->getFilename() !== 'plugin.json') {
                continue;
            }

            $configPath = $fileInfo->getPathname();
            if (!is_readable($configPath)) {
                continue;
            }

            $pluginDir = realpath(dirname($configPath));
            if (!$pluginDir) {
                continue;
            }

            $relativeDir = substr($pluginDir, strlen($pluginsDir));
            $relativeDir = ltrim((string)$relativeDir, "\\/ \t\n\r\0\x0B");
            $pluginId = str_replace('\\', '/', $relativeDir);
            $pluginId = trim($pluginId, '/');
            if ($pluginId === '') {
                continue;
            }

            $segments = array_values(array_filter(explode('/', $pluginId), function ($s) {
                return $s !== '';
            }));
            if (count($segments) < 2) {
                continue;
            }

            $pluginName = (string)end($segments);
            $category = (string)($segments[0] ?? '');
            $categoryPath = implode('/', array_slice($segments, 0, -1));

            $config = $this->readJson($configPath);

            $installed = $config['installed'] ?? false;
            $installedOk = ($installed === true || $installed === 1 || $installed === '1' || $installed === 'true');

            // 检查插件是否有配置表单（使用轻量级检查）
            $hasConfig = false;
            if ($installedOk) {
                // 方法1：检查配置文件中是否声明了 has_config
                if (!empty($config['has_config'])) {
                    $hasConfig = true;
                }
                // 方法2：检查是否有 admin_entry（后台配置入口）
                elseif (!empty($config['admin_entry'])) {
                    $hasConfig = true;
                }
                // 方法3：检查主类文件是否存在（轻量级检查，不实例化）
                else {
                    $mainClassFilename = (string)($config['main_class'] ?? 'Plugin.php');
                    $mainClassPath = realpath($pluginDir . DIRECTORY_SEPARATOR . $mainClassFilename);
                    if ($mainClassPath && is_readable($mainClassPath)) {
                        // 读取文件内容，检查是否定义了 getConfigForm 方法
                        $fileContent = file_get_contents($mainClassPath);
                        $hasConfig = strpos($fileContent, 'getConfigForm') !== false;
                    }
                }
            }
            
            // 从配置文件获取插件信息
            $pluginNameFromConfig = (string)($config['name'] ?? $pluginId);
            $pluginVersionFromConfig = (string)($config['version'] ?? '');
            $pluginDescriptionFromConfig = (string)($config['description'] ?? '');
            
            // 如果插件已安装，从预加载的数据库信息中读取
            $dbName = $pluginNameFromConfig;
            if ($installedOk && isset($dbPluginsMap[$pluginId])) {
                $dbPlugin = $dbPluginsMap[$pluginId];
                // 如果数据库中的名称是空的或者是英文的（可能是旧的），使用配置文件中的名称
                $dbNameValue = (string)($dbPlugin['name'] ?? '');
                if (empty($dbNameValue) || $dbNameValue === $pluginId || $dbNameValue === $pluginName) {
                    // 数据库中的名称是空的或旧的，使用配置文件中的名称
                    $dbName = $pluginNameFromConfig;
                    // 同步更新数据库
                    $this->syncPluginNameToDatabase($pluginId, $config);
                } else {
                    // 数据库中的名称存在且不是默认值，优先使用数据库中的名称
                    $dbName = $dbNameValue;
                }
                
                // 同步数据库中的状态到配置文件（确保状态一致）
                $dbStatus = (string)($dbPlugin['status'] ?? 'disabled');
                $configStatus = (string)($config['status'] ?? 'disabled');
                if ($dbStatus !== $configStatus) {
                    // 如果状态不一致，以数据库为准（数据库是权威来源）
                    $config['status'] = $dbStatus;
                    $this->writeJson($configPath, $config);
                }
            } elseif ($installedOk) {
                // 数据库中没有记录，同步到数据库
                $this->syncPluginNameToDatabase($pluginId, $config);
            }
            
            $pluginData = [
                'id' => $pluginId,
                'category' => $category,
                'category_path' => $categoryPath,
                'plugin_name' => $pluginName,
                'name' => $dbName,
                'version' => $pluginVersionFromConfig,
                'description' => $pluginDescriptionFromConfig,
                'type' => (string)($config['type'] ?? ''),
                'installed' => $installedOk,
                'status' => (string)($config['status'] ?? 'disabled'),
                'frontend_entry' => (string)($config['frontend_entry'] ?? ''),
                'admin_entry' => (string)($config['admin_entry'] ?? ''),
                'install_sql' => (string)($config['install_sql'] ?? 'database/install.sql'),
                'uninstall_sql' => (string)($config['uninstall_sql'] ?? 'database/uninstall.sql'),
                'has_config' => $hasConfig,
            ];
            $plugins[] = $pluginData;
            $existsMap[$pluginId] = true;
        }

        // 兼容旧架构插件：没有 plugin.json，但有 config.php 或 *Plugin.php 的插件
        // 这些插件通过本地状态文件维护“已安装/启用”状态。
        $legacyTypes = ['payment', 'sms', 'email', 'certification'];
        foreach ($legacyTypes as $type) {
            $typeDir = $pluginsDir . DIRECTORY_SEPARATOR . $type;
            if (!is_dir($typeDir)) {
                continue;
            }

            $items = array_diff(scandir($typeDir) ?: [], ['.', '..']);
            foreach ($items as $item) {
                $pluginPath = $typeDir . DIRECTORY_SEPARATOR . $item;
                if (!is_dir($pluginPath)) {
                    continue;
                }

                // 兼容像 EpayWxpay/epay_wxpay 这种多级结构，只把第二级当作插件 ID 结尾部分
                $relative = substr($pluginPath, strlen($pluginsDir));
                $relative = ltrim((string)$relative, "\\/ \t\n\r\0\x0B");
                $relative = str_replace('\\', '/', $relative);
                $pluginId = trim($relative, '/');

                if ($pluginId === '' || isset($existsMap[$pluginId])) {
                    continue;
                }

                // 检测是否为旧架构插件：存在 config.php 或 *Plugin.php
                $hasConfigPhp = file_exists($pluginPath . DIRECTORY_SEPARATOR . 'config.php');
                $hasPluginPhp = false;
                foreach (glob($pluginPath . DIRECTORY_SEPARATOR . '*Plugin.php') as $phpFile) {
                    $hasPluginPhp = true;
                    break;
                }

                if (!$hasConfigPhp && !$hasPluginPhp) {
                    continue;
                }

                $segments = array_values(array_filter(explode('/', $pluginId), function ($s) {
                    return $s !== '';
                }));
                if (count($segments) === 0) {
                    continue;
                }

                $pluginName = (string)end($segments);
                $category = (string)($segments[0] ?? $type);
                $categoryPath = implode('/', array_slice($segments, 0, -1));

                $state = $legacyStates[$pluginId] ?? ['installed' => false, 'status' => 'disabled'];

                // 尝试从插件类读取中文名称
                $displayName = $pluginName;
                $version = '';
                $description = '';
                
                // 查找插件类文件
                $pluginClassFile = null;
                foreach (glob($pluginPath . DIRECTORY_SEPARATOR . '*Plugin.php') as $phpFile) {
                    $pluginClassFile = $phpFile;
                    break;
                }
                
                if ($pluginClassFile && is_readable($pluginClassFile)) {
                    try {
                        // 读取文件内容，直接从代码中提取 $info 数组信息
                        $fileContent = file_get_contents($pluginClassFile);
                        
                        // 提取 $info 数组内容（支持 array() 和 [] 两种格式，支持多行和注释）
                        $infoStr = '';
                        
                        // 匹配 $info = array(...) 格式（更常见）
                        // 使用更健壮的方法：找到开始位置，然后匹配括号
                        if (preg_match('/\$info\s*=\s*array\s*\(/s', $fileContent, $match, PREG_OFFSET_CAPTURE)) {
                            $startPos = $match[0][1] + strlen($match[0][0]);
                            $depth = 1;
                            $pos = $startPos;
                            $len = strlen($fileContent);
                            
                            // 找到匹配的结束括号
                            while ($pos < $len && $depth > 0) {
                                $char = $fileContent[$pos];
                                if ($char === '(') {
                                    $depth++;
                                } elseif ($char === ')') {
                                    $depth--;
                                    if ($depth === 0) {
                                        $infoStr = substr($fileContent, $startPos, $pos - $startPos);
                                        break;
                                    }
                                }
                                $pos++;
                            }
                        }
                        // 匹配 $info = [...] 格式（新格式）
                        elseif (preg_match('/\$info\s*=\s*\[/s', $fileContent, $match, PREG_OFFSET_CAPTURE)) {
                            $startPos = $match[0][1] + strlen($match[0][0]);
                            $depth = 1;
                            $pos = $startPos;
                            $len = strlen($fileContent);
                            
                            // 找到匹配的结束括号
                            while ($pos < $len && $depth > 0) {
                                $char = $fileContent[$pos];
                                if ($char === '[') {
                                    $depth++;
                                } elseif ($char === ']') {
                                    $depth--;
                                    if ($depth === 0) {
                                        $infoStr = substr($fileContent, $startPos, $pos - $startPos);
                                        break;
                                    }
                                }
                                $pos++;
                            }
                        }
                        
                        if (!empty($infoStr)) {
                            // 移除单行注释（// 注释）
                            $infoStr = preg_replace('/\/\/.*$/m', '', $infoStr);
                            // 移除多行注释（/* */ 注释）
                            $infoStr = preg_replace('/\/\*.*?\*\//s', '', $infoStr);
                            
                            // 提取 title（中文名称）- 支持单引号和双引号，支持多行
                            if (preg_match('/["\']title["\']\s*=>\s*["\']([^"\']+)["\']/', $infoStr, $titleMatch)) {
                                $displayName = trim($titleMatch[1]);
                            }
                            
                            // 提取 version
                            if (preg_match('/["\']version["\']\s*=>\s*["\']([^"\']+)["\']/', $infoStr, $versionMatch)) {
                                $version = trim($versionMatch[1]);
                            }
                            
                            // 提取 description
                            if (preg_match('/["\']description["\']\s*=>\s*["\']([^"\']+)["\']/', $infoStr, $descMatch)) {
                                $description = trim($descMatch[1]);
                            }
                        }
                    } catch (\Exception $e) {
                        // 忽略错误，使用默认名称
                        error_log('读取旧架构插件信息失败 [' . $pluginId . ']: ' . $e->getMessage());
                    }
                }

                // 如果旧架构插件已安装，尝试同步名称到数据库
                if (!empty($state['installed'])) {
                    try {
                        $legacyConfig = [
                            'name' => $displayName,
                            'version' => $version,
                            'description' => $description,
                            'type' => $type,
                            'category' => $category,
                            'status' => (string)($state['status'] ?? 'disabled'),
                            'author' => '',
                            'website' => '',
                            'namespace' => '',
                            'main_class' => '',
                        ];
                        $this->syncPluginNameToDatabase($pluginId, $legacyConfig);
                    } catch (\Exception $e) {
                        // 忽略错误
                        error_log('同步旧架构插件名称到数据库失败 [' . $pluginId . ']: ' . $e->getMessage());
                    }
                }
                
                $plugins[] = [
                    'id' => $pluginId,
                    'category' => $category,
                    'category_path' => $categoryPath,
                    'plugin_name' => $pluginName,
                    'name' => $displayName,
                    'version' => $version,
                    'description' => $description,
                    'type' => $type,
                    'installed' => !empty($state['installed']),
                    'status' => (string)($state['status'] ?? 'disabled'),
                    'frontend_entry' => '',
                    'admin_entry' => '',
                    'install_sql' => '',
                    'uninstall_sql' => '',
                    // 旧架构插件如果有config.php文件，则显示配置按钮
                    'has_config' => $hasConfigPhp,
                    'legacy' => true,
                ];
                $existsMap[$pluginId] = true;
            }
        }

        usort($plugins, function ($a, $b) {
            return strcmp($a['id'], $b['id']);
        });
    
        return $plugins;
    }

    /**
     * 旧架构插件状态文件路径
     */
    private function getLegacyPluginStateFile(): string
    {
        $root = realpath(__DIR__ . '/../../..');
        return $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'legacy_plugins.json';
    }

    /**
     * 加载旧架构插件状态
     *
     * @return array<string, array{installed: bool, status: string}>
     */
    private function loadLegacyPluginStates(): array
    {
        $file = $this->getLegacyPluginStateFile();
        if (!is_file($file)) {
            return [];
        }
        $json = (string)file_get_contents($file);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    /**
     * 保存旧架构插件状态
     *
     * @param array<string, array{installed: bool, status: string}> $states
     */
    private function saveLegacyPluginStates(array $states): void
    {
        $file = $this->getLegacyPluginStateFile();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        file_put_contents($file, json_encode($states, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function getPluginConfigPath(string $pluginsDir, string $pluginId): string
    {
        return $pluginsDir . DIRECTORY_SEPARATOR . $pluginId . DIRECTORY_SEPARATOR . 'plugin.json';
    }

    private function readJson(string $path): array
    {
        $json = (string)file_get_contents($path);
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }
        return $data;
    }

    private function writeJson(string $path, array $data): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        file_put_contents($path, $json . PHP_EOL);
    }

    /**
     * 限制验证类插件：同一类别下（如 basic / thirdparty）只允许启用一个
     *
     * @param string $currentPluginId 当前正在启用的插件ID（目录形式，如 verification/basic/recaptcha）
     * @param array  $currentConfig   当前插件的配置（来自 plugin.json）
     * @return void
     */
    private function disableOtherVerificationPlugins(string $currentPluginId, array $currentConfig): void
    {
        $type = (string)($currentConfig['type'] ?? '');
        $category = (string)($currentConfig['category'] ?? '');

        // 只处理验证类插件，且必须有明确的类别（basic / thirdparty 等）
        if ($type !== 'verification' || $category === '') {
            return;
        }

        $pluginsDir = $this->getPluginsDir();
        if ($pluginsDir === '') {
            return;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($pluginsDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo instanceof \SplFileInfo || !$fileInfo->isFile()) {
                    continue;
                }
                if ($fileInfo->getFilename() !== 'plugin.json') {
                    continue;
                }

                $configPath = $fileInfo->getPathname();
                $pluginDir = realpath(dirname($configPath));
                if (!$pluginDir) {
                    continue;
                }

                // 计算当前 plugin.json 对应的插件ID（目录形式）
                $relativeDir = substr($pluginDir, strlen($pluginsDir));
                $relativeDir = ltrim((string)$relativeDir, "\\/ \t\n\r\0\x0B");
                $pluginId = str_replace('\\', '/', (string)$relativeDir);
                $pluginId = trim($pluginId, '/');

                // 跳过自身或无效ID
                if ($pluginId === '' || $pluginId === $currentPluginId) {
                    continue;
                }

                $config = $this->readJson($configPath);

                // 仅处理验证类且同一类别的插件
                if (($config['type'] ?? '') !== 'verification') {
                    continue;
                }
                if ((string)($config['category'] ?? '') !== $category) {
                    continue;
                }

                // 仅处理已安装且当前为启用状态的插件
                $installed = $config['installed'] ?? false;
                $installedOk = ($installed === true || $installed === 1 || $installed === '1' || $installed === 'true');
                if (!$installedOk) {
                    continue;
                }

                $status = (string)($config['status'] ?? 'disabled');
                if ($status !== 'enabled') {
                    continue;
                }

                // 关闭同一类别下的其他验证插件
                $config['status'] = 'disabled';
                $this->writeJson($configPath, $config);
                $this->syncPluginToDatabase($pluginId, $config);
            }
        } catch (\Exception $e) {
            error_log('禁用其他验证类插件失败: ' . $e->getMessage());
        }
    }

    /**
     * 同步插件名称到数据库（仅更新名称和基本信息，不改变状态）
     */
    private function syncPluginNameToDatabase(string $pluginId, array $config): void
    {
        try {
            $pdo = Database::pdo();
            $table = Database::prefix() . 'admin_plugins';
            
            $stmt = $pdo->prepare("SELECT id, status FROM `{$table}` WHERE `plugin_id` = ?");
            $stmt->execute([$pluginId]);
            $exists = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($exists) {
                // 只更新名称和相关字段，保留数据库中的状态
                $currentStatus = (string)($exists['status'] ?? 'disabled');
                $stmt = $pdo->prepare("UPDATE `{$table}` SET `name` = ?, `version` = ?, `description` = ?, `author` = ?, `website` = ?, `updated_at` = ? WHERE `plugin_id` = ?");
                $stmt->execute([
                    $config['name'] ?? '',
                    $config['version'] ?? '1.0.0',
                    $config['description'] ?? '',
                    $config['author'] ?? '',
                    $config['website'] ?? '',
                    date('Y-m-d H:i:s'),
                    $pluginId
                ]);
            } else {
                // 如果不存在，插入新记录（使用配置文件中的状态）
                $stmt = $pdo->prepare("INSERT INTO `{$table}` (`plugin_id`, `name`, `version`, `type`, `category`, `description`, `author`, `website`, `namespace`, `main_class`, `status`, `config_json`, `installed_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $pluginId,
                    $config['name'] ?? '',
                    $config['version'] ?? '1.0.0',
                    $config['type'] ?? '',
                    $config['category'] ?? '',
                    $config['description'] ?? '',
                    $config['author'] ?? '',
                    $config['website'] ?? '',
                    $config['namespace'] ?? '',
                    $config['main_class'] ?? 'Plugin.php',
                    (string)($config['status'] ?? 'disabled'),
                    json_encode($config['config'] ?? [], JSON_UNESCAPED_UNICODE),
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s')
                ]);
            }
        } catch (\Exception $e) {
            error_log('同步插件名称到数据库失败: ' . $e->getMessage());
        }
    }

    private function syncPluginToDatabase(string $pluginId, array $config): void
    {
        try {
            $pdo = Database::pdo();
            $table = Database::prefix() . 'admin_plugins';
            
            $stmt = $pdo->prepare("SELECT id, status FROM `{$table}` WHERE `plugin_id` = ?");
            $stmt->execute([$pluginId]);
            $exists = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            $status = (string)($config['status'] ?? 'disabled');
            $installed = $config['installed'] ?? false;
            
            if ($exists) {
                // 更新时也更新名称和其他字段，确保数据同步
                // 如果数据库中的状态与配置文件不同，以配置文件为准（因为这是最新的操作结果）
                $stmt = $pdo->prepare("UPDATE `{$table}` SET `name` = ?, `version` = ?, `type` = ?, `category` = ?, `description` = ?, `author` = ?, `website` = ?, `namespace` = ?, `main_class` = ?, `status` = ?, `updated_at` = ? WHERE `plugin_id` = ?");
                $stmt->execute([
                    $config['name'] ?? '',
                    $config['version'] ?? '1.0.0',
                    $config['type'] ?? '',
                    $config['category'] ?? '',
                    $config['description'] ?? '',
                    $config['author'] ?? '',
                    $config['website'] ?? '',
                    $config['namespace'] ?? '',
                    $config['main_class'] ?? 'Plugin.php',
                    $status,
                    date('Y-m-d H:i:s'),
                    $pluginId
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO `{$table}` (`plugin_id`, `name`, `version`, `type`, `category`, `description`, `author`, `website`, `namespace`, `main_class`, `status`, `config_json`, `installed_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $pluginId,
                    $config['name'] ?? '',
                    $config['version'] ?? '1.0.0',
                    $config['type'] ?? '',
                    $config['category'] ?? '',
                    $config['description'] ?? '',
                    $config['author'] ?? '',
                    $config['website'] ?? '',
                    $config['namespace'] ?? '',
                    $config['main_class'] ?? 'Plugin.php',
                    $status,
                    json_encode($config['config'] ?? [], JSON_UNESCAPED_UNICODE),
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s')
                ]);
            }
        } catch (\Exception $e) {
            error_log('同步插件到数据库失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 确保数组中的所有字符串都是UTF-8编码
     * @param mixed $data
     * @return mixed
     */
    private function ensureUtf8Encoding($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->ensureUtf8Encoding($value);
            }
        } elseif (is_string($data)) {
            // 检查并转换编码
            if (!mb_check_encoding($data, 'UTF-8')) {
                $data = mb_convert_encoding($data, 'UTF-8', 'auto');
            }
        }
        return $data;
    }

    /**
     * 清理多次序列化的配置值
     * 递归解析 JSON 字符串，直到得到实际值
     */
    private function cleanSerializedValue($value)
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->cleanSerializedValue($item);
            }
            return $result;
        } elseif (is_string($value) && !empty($value)) {
            // 尝试解析 JSON 字符串
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // 如果解析成功，递归处理解析后的值
                if (is_string($decoded) && $decoded !== $value) {
                    // 如果解析后仍然是字符串且与原值不同，继续递归解析
                    return $this->cleanSerializedValue($decoded);
                } elseif (is_array($decoded)) {
                    // 如果是数组，递归清理
                    return $this->cleanSerializedValue($decoded);
                } else {
                    // 如果是简单值（数字、布尔值等），直接返回
                    return $decoded;
                }
            }
            // 如果解析失败，返回原值
            return $value;
        }
        return $value;
    }

    /**
     * 返回 JSON 响应（用于 AJAX 请求）
     * @param bool $success 是否成功
     * @param string $message 消息
     * @param array $data 额外数据
     */
    private function jsonResponse(bool $success, string $message, array $data = []): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $response = [
            'success' => $success,
            'message' => $message,
        ];
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
