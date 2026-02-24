<?php

namespace app\admin\controller;

use app\models\UserConsistencyConfig;
use app\models\ConsistencyReport;
use app\models\ConsistencyConflict;
use app\models\CoreSetting;
use app\models\VectorDbConfig;
use app\models\EmbeddingModelConfig;
use app\models\VectorDbUsageLog;
use app\services\ConsistencyCheckIntegrationService;
use app\services\Database;
use app\admin\controller\BaseController;

/**
 * 一致性检查管理控制器
 */
class ConsistencyController extends BaseController
{
    /**
     * 一致性检查配置页面
     */
    public function config()
    {
        try {
            $this->checkPermission('consistency_config');
            
            $userId = $_SESSION['user_id'] ?? 0;
            $config = UserConsistencyConfig::getOrCreateByUserId($userId);
            
            // 获取可用的选项（供下拉列表使用）
            try {
                $vectorDbOptions = VectorDbConfig::getEnabled();
            } catch (\Exception $e) {
                $vectorDbOptions = [];
            }
            
            try {
                $embeddingModelOptions = EmbeddingModelConfig::getEnabled();
            } catch (\Exception $e) {
                $embeddingModelOptions = [];
            }
            
            // 统一变量命名，方便视图层使用
            $vectorDbs = $vectorDbOptions;
            $embeddingModels = $embeddingModelOptions;

            // 供视图层使用的选项数组
            $db_mode_options = UserConsistencyConfig::getDbModeOptions();
            $check_frequency_options = UserConsistencyConfig::getCheckFrequencyOptions();
            $check_scope_options = UserConsistencyConfig::getCheckScopeOptions();

            // 系统健康状态（视图中用于右侧状态卡片）
            try {
                $integrationService = new ConsistencyCheckIntegrationService();
                $health = $integrationService->getSystemHealthStatus();
                $systemStatus = [
                    'vector_db' => ($health['vector_service']['status'] ?? null) === 'healthy',
                    'embedding_model' => ($health['rag_service']['overall'] ?? null) === 'healthy',
                    // 下面两个字段主要用于展示数量，为避免额外查询，使用最近错误/报告数量的近似信息
                    'core_settings' => true,
                    'core_settings_count' => $health['rag_service']['indexed_core_settings'] ?? 0,
                    'recent_checks' => true,
                    'recent_checks_count' => count($health['recent_errors'] ?? []),
                ];
            } catch (\Exception $e) {
                // 出现异常时保证视图中依然有结构化数据可用，避免报错
                $systemStatus = [
                    'vector_db' => false,
                    'embedding_model' => false,
                    'core_settings' => false,
                    'core_settings_count' => 0,
                    'recent_checks' => false,
                    'recent_checks_count' => 0,
                ];
            }

            $title = '一致性检查系统';
            $currentPage = 'consistency-config';
            $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
            
            ob_start();
            require __DIR__ . '/../views/consistency/config.php';
            $content = ob_get_clean();
            
            require __DIR__ . '/../views/layout.php';
        } catch (\Exception $e) {
            error_log("ConsistencyController::config() error: " . $e->getMessage());
            http_response_code(500);
            echo "错误: " . htmlspecialchars($e->getMessage());
        }
    }
    
    /**
     * 保存配置
     */
    public function saveConfig()
    {
        $this->checkPermission('consistency_config');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user_id'] ?? 0;
            $configData = $_POST;
            
            // 验证配置数据
            $validation = UserConsistencyConfig::validateConfig($configData);
            if (!$validation['valid']) {
                $this->json(['success' => false, 'errors' => $validation['errors']]);
                return;
            }
            
            // 更新配置
            $result = UserConsistencyConfig::updateByUserId($userId, $configData);
            
            if ($result) {
                $this->json(['success' => true, 'message' => '配置保存成功']);
            } else {
                $this->json(['success' => false, 'message' => '配置保存失败']);
            }
        }
    }
    
    /**
     * 获取启用的验证插件
     *
     * @param string $scene 使用场景，如 consistency_check
     * @return object|null
     */
    private function getVerificationPlugin(string $scene = 'consistency_check'): ?object
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
                
                // 必须是已安装且启用的
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
            
            // 加载插件主类文件
            $mainClass = $foundConfig['main_class'] ?? 'Plugin.php';
            $mainClassPath = $pluginPath . DIRECTORY_SEPARATOR . $mainClass;
            if (!is_readable($mainClassPath)) {
                return null;
            }
            
            // 构建预期的类名
            $namespace = (string)($foundConfig['namespace'] ?? '');
            $namespacePrefix = $namespace !== '' ? $namespace . '\\' : '';
            $expectedClass = $namespacePrefix . pathinfo($mainClass, PATHINFO_FILENAME);
            
            $beforeClasses = get_declared_classes();
            
            // 只有当类不存在时才加载文件，避免重复声明错误
            if (!class_exists($expectedClass)) {
                require_once $mainClassPath;
            }
            
            $afterClasses = get_declared_classes();

            $newClasses = array_diff($afterClasses, $beforeClasses);

            $namespace = (string)($foundConfig['namespace'] ?? '');
            $namespacePrefix = $namespace !== '' ? $namespace . '\\' : '';

            $candidateClass = null;

            $defaultClass = $namespacePrefix . pathinfo($mainClass, PATHINFO_FILENAME);
            if ($defaultClass !== '' && class_exists($defaultClass)) {
                $candidateClass = $defaultClass;
            } else {
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
            error_log('加载验证插件失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 一致性检查页面
     */
    public function check()
    {
        try {
            $this->checkPermission('consistency_manual_check');
            
            $userId = $_SESSION['user_id'] ?? 0;
            
            // 获取最近的检查记录
            try {
                $recentChecksResult = ConsistencyReport::getAll(1, 10, $userId);
                $recentChecks = $recentChecksResult['reports'] ?? [];
            } catch (\Exception $e) {
                $recentChecks = [];
            }
            
            // 获取验证码插件HTML
            $captchaHtml = '';
            $verificationPlugin = $this->getVerificationPlugin('consistency_check');
            if ($verificationPlugin && method_exists($verificationPlugin, 'getHtml')) {
                $captchaHtml = $verificationPlugin->getHtml();
            }
            
            $title = '一致性检查';
            $currentPage = 'consistency-check';
            $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
            
            ob_start();
            require __DIR__ . '/../views/consistency/check.php';
            $content = ob_get_clean();
            
            require __DIR__ . '/../views/layout.php';
        } catch (\Exception $e) {
            error_log("ConsistencyController::check() error: " . $e->getMessage());
            http_response_code(500);
            echo "错误: " . htmlspecialchars($e->getMessage());
        }
    }
    
    /**
     * 一致性检查报告页面
     */
    public function reports()
    {
        try {
            $this->checkPermission('consistency_reports');
            
            $page = (int)($_GET['page'] ?? 1);
            $perPage = 20;
            $userId = $_SESSION['user_id'] ?? 0;
            $projectType = $_GET['project_type'] ?? null;
            $status = $_GET['status'] ?? null;
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;
            
            try {
                $reportsResult = ConsistencyReport::getAll(
                    $page, 
                    $perPage, 
                    $userId, 
                    $projectType, 
                    $status, 
                    $dateFrom, 
                    $dateTo
                );
                $reports = $reportsResult['reports'] ?? [];
                $totalPages = $reportsResult['totalPages'] ?? 1;
            } catch (\Exception $e) {
                $reports = [];
                $totalPages = 1;
            }
            
            $title = '检查报告';
            $currentPage = 'consistency-reports';
            $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
            $currentPageNum = $page;
            try {
                $project_type_options = ConsistencyReport::getProjectTypeOptions();
            } catch (\Exception $e) {
                $project_type_options = [];
            }
            
            ob_start();
            require __DIR__ . '/../views/consistency/reports.php';
            $content = ob_get_clean();
            
            require __DIR__ . '/../views/layout.php';
        } catch (\Exception $e) {
            error_log("ConsistencyController::reports() error: " . $e->getMessage());
            http_response_code(500);
            echo "错误: " . htmlspecialchars($e->getMessage());
        }
    }
    
    /**
     * 查看报告详情
     */
    public function reportDetail()
    {
        $this->checkPermission('consistency_reports');
        
        $reportId = (int)($_GET['id'] ?? 0);
        $report = ConsistencyReport::find($reportId);
        
        if (!$report) {
            $this->json(['success' => false, 'message' => '报告不存在']);
            return;
        }
        
        // 获取冲突记录
        $conflicts = ConsistencyConflict::getAll(1, 100, $reportId);
        
        $this->view('consistency_report_detail', [
            'report' => $report,
            'conflicts' => $conflicts['conflicts'],
            'project_title' => $report['project_title']
        ]);
    }
    
    /**
     * 核心设定管理页面
     */
    public function coreSettings()
    {
        try {
            $this->checkPermission('core_settings');
            
            $userId = $_SESSION['user_id'] ?? 0;
            $projectId = (int)($_GET['project_id'] ?? 0);
            $projectType = $_GET['project_type'] ?? 'novel';
            
            // 获取项目的核心设定
            try {
                $settings = CoreSetting::getByProject($projectId, $projectType);
            } catch (\Exception $e) {
                $settings = [];
            }
            
            $title = '核心设定管理';
            $currentPage = 'consistency-core-settings';
            $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
            try {
                $setting_type_options = CoreSetting::getSettingTypeOptionsByProject($projectType);
            } catch (\Exception $e) {
                $setting_type_options = [];
            }
            
            ob_start();
            require __DIR__ . '/../views/consistency/core-settings.php';
            $content = ob_get_clean();
            
            require __DIR__ . '/../views/layout.php';
        } catch (\Exception $e) {
            error_log("ConsistencyController::coreSettings() error: " . $e->getMessage());
            http_response_code(500);
            echo "错误: " . htmlspecialchars($e->getMessage());
        }
    }
    
    /**
     * 保存核心设定
     */
    public function saveCoreSettings()
    {
        $this->checkPermission('core_settings');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user_id'] ?? 0;
            $projectId = (int)($_POST['project_id'] ?? 0);
            $projectType = $_POST['project_type'] ?? 'novel';
            $settings = $_POST['settings'] ?? [];
            
            $integrationService = new ConsistencyCheckIntegrationService();
            $success = $integrationService->updateCoreSettings($userId, $projectId, $projectType, $settings);
            
            if ($success) {
                $this->json(['success' => true, 'message' => '核心设定保存成功']);
            } else {
                $this->json(['success' => false, 'message' => '核心设定保存失败']);
            }
        }
    }
    
    /**
     * 向量数据库配置页面
     */
    public function vectorDbConfig()
    {
        $this->checkPermission('vector_db_config');
        
        $configs = VectorDbConfig::getAll();
        
        $this->view('vector_db_config', [
            'configs' => $configs,
            'type_options' => VectorDbConfig::getTypeOptions()
        ]);
    }
    
    /**
     * 保存向量数据库配置
     */
    public function saveVectorDbConfig()
    {
        $this->checkPermission('vector_db_config');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $configData = $_POST;
            
            // 验证配置数据
            $validation = VectorDbConfig::validateConfig($configData);
            if (!$validation['valid']) {
                $this->json(['success' => false, 'errors' => $validation['errors']]);
                return;
            }
            
            // 更新配置
            $configId = (int)($configData['id'] ?? 0);
            $success = VectorDbConfig::update($configId, $configData);
            
            if ($success) {
                $this->json(['success' => true, 'message' => '向量数据库配置保存成功']);
            } else {
                $this->json(['success' => false, 'message' => '向量数据库配置保存失败']);
            }
        }
    }
    
    /**
     * 测试向量数据库连接
     */
    public function testVectorDbConnection()
    {
        $this->checkPermission('vector_db_config');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $configId = (int)($_POST['id'] ?? 0);
            
            $result = VectorDbConfig::testConnection($configId);
            
            $this->json($result);
        }
    }
    
    /**
     * 嵌入式模型配置页面
     */
    public function embeddingModelConfig()
    {
        $this->checkPermission('embedding_model_config');
        
        $configs = EmbeddingModelConfig::getAll();
        
        $this->view('embedding_model_config', [
            'configs' => $configs,
            'type_options' => EmbeddingModelConfig::getTypeOptions()
        ]);
    }
    
    /**
     * 保存嵌入式模型配置
     */
    public function saveEmbeddingModelConfig()
    {
        $this->checkPermission('embedding_model_config');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $configData = $_POST;
            
            // 验证配置数据
            $validation = EmbeddingModelConfig::validateConfig($configData);
            if (!$validation['valid']) {
                $this->json(['success' => false, 'errors' => $validation['errors']]);
                return;
            }
            
            // 更新配置
            $configId = (int)($configData['id'] ?? 0);
            $success = EmbeddingModelConfig::update($configId, $configData);
            
            if ($success) {
                $this->json(['success' => true, 'message' => '嵌入式模型配置保存成功']);
            } else {
                $this->json(['success' => false, 'message' => '嵌入式模型配置保存失败']);
            }
        }
    }
    
    /**
     * 测试嵌入式模型
     */
    public function testEmbeddingModel()
    {
        $this->checkPermission('embedding_model_config');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $configId = (int)($_POST['id'] ?? 0);
            $testText = $_POST['test_text'] ?? '测试文本';
            
            $result = EmbeddingModelConfig::testModel($configId, $testText);
            
            $this->json($result);
        }
    }
    
    /**
     * 统计分析页面
     */
    public function analytics()
    {
        try {
            $this->checkPermission('consistency_analytics');
            
            $userId = $_SESSION['user_id'] ?? 0;
            $dateRange = $_GET['date_range'] ?? 'month';
            
            try {
                $integrationService = new ConsistencyCheckIntegrationService();
                $stats = $integrationService->getConsistencyStats($userId, $dateRange);
            } catch (\Exception $e) {
                $stats = [];
            }
            
            $title = '分析统计';
            $currentPage = 'consistency-analytics';
            $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
            $date_range_options = [
                'today' => '今天',
                'week' => '本周',
                'month' => '本月',
                'year' => '本年'
            ];
            
            ob_start();
            require __DIR__ . '/../views/consistency/analytics.php';
            $content = ob_get_clean();
            
            require __DIR__ . '/../views/layout.php';
        } catch (\Exception $e) {
            error_log("ConsistencyController::analytics() error: " . $e->getMessage());
            http_response_code(500);
            echo "错误: " . htmlspecialchars($e->getMessage());
        }
    }
    
    /**
     * 系统健康状态页面
     */
    public function systemHealth()
    {
        $this->checkPermission('system_health');
        
        $integrationService = new ConsistencyCheckIntegrationService();
        $health = $integrationService->getSystemHealthStatus();
        
        $this->view('system_health', [
            'health' => $health,
            'last_check' => $health['last_check']
        ]);
    }
    
    /**
     * 手动执行一致性检查
     */
    public function manualCheck()
    {
        $this->checkPermission('consistency_manual_check');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 验证验证码（如果启用了验证插件）
            $verificationPlugin = $this->getVerificationPlugin('consistency_check');
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
                    $this->json(['success' => false, 'message' => '请完成安全验证']);
                    return;
                }
                
                if (!$verificationPlugin->verify($captchaValue, $captchaToken)) {
                    $this->json(['success' => false, 'message' => '安全验证失败，请重试']);
                    return;
                }
            }
            
            $userId = $_SESSION['user_id'] ?? 0;
            $content = $_POST['content'] ?? '';
            $projectId = (int)($_POST['project_id'] ?? 0);
            $projectType = $_POST['project_type'] ?? 'novel';
            $contentId = (int)($_POST['content_id'] ?? null);
            $contentType = $_POST['content_type'] ?? null;
            
            $integrationService = new ConsistencyCheckIntegrationService();
            $result = $integrationService->performConsistencyCheck(
                $userId,
                $content,
                $projectId,
                $projectType,
                $contentId,
                $contentType
            );
            
            $this->json($result);
        }
    }
    
    /**
     * 获取用户配置（API接口）
     */
    public function getUserConfig()
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $integrationService = new ConsistencyCheckIntegrationService();
        $config = $integrationService->getUserConsistencyConfig($userId);
        
        $this->json($config);
    }
    
    /**
     * 更新用户配置（API接口）
     */
    public function updateUserConfig()
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $configData = $_POST;
        
        $integrationService = new ConsistencyCheckIntegrationService();
        $result = $integrationService->updateUserConsistencyConfig($userId, $configData);
        
        $this->json($result);
    }
    
    /**
     * 获取检查统计（API接口）
     */
    public function getCheckStats()
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $dateRange = $_GET['date_range'] ?? 'month';
        
        $integrationService = new ConsistencyCheckIntegrationService();
        $stats = $integrationService->getConsistencyStats($userId, $dateRange);
        
        $this->json($stats);
    }
}