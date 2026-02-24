<?php

namespace app\services;

use app\services\Database;
use PDO;

/**
 * 插件自动化服务
 * 
 * 负责处理应用安装、数据库迁移等自动化任务
 */
class PluginAutomationService
{
    /**
     * @var Database 数据库服务实例
     */
    protected $db;

    /**
     * @var string 数据库表前缀
     */
    protected $prefix;

    /**
     * @var PluginMigrationService 插件迁移服务
     */
    protected $migrationService;

    /**
     * @var PluginVersionService 插件版本服务
     */
    protected $versionService;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->prefix = Database::prefix();
        $this->migrationService = new PluginMigrationService();
        $this->versionService = new PluginVersionService();
    }

    /**
     * 运行所有数据库迁移
     *
     * @return array 迁移结果
     */
    public function runMigrations()
    {
        // 首先，确保迁移记录表本身存在
        $this->createMigrationsTableIfNeeded();

        $migrationsDir = realpath(__DIR__ . '/../../database/migrations');
        if (!is_dir($migrationsDir)) {
            return ['success' => true, 'message' => '没有找到迁移目录，无需执行。'];
        }

        // 获取已执行的迁移
        $executedMigrations = $this->getExecutedMigrations();

        // 获取所有迁移文件
        $migrationFiles = glob($migrationsDir . '/*.sql');
        sort($migrationFiles); // 按文件名排序，确保执行顺序

        $results = [];
        foreach ($migrationFiles as $file) {
            $fileName = basename($file);

            if (in_array($fileName, $executedMigrations)) {
                continue; // 跳过已执行的迁移
            }

            $sqlContent = file_get_contents($file);
            $sqlContent = str_replace('__PREFIX__', $this->prefix, $sqlContent);
            
            try {
                $statements = preg_split('/;\s*[\r\n]+/', $sqlContent);
                foreach ($statements as $stmt) {
                    $stmt = trim($stmt);
                    if ($stmt === '' || preg_match('/^\s*--/', $stmt)) {
                        continue;
                    }
                    Database::pdo()->exec($stmt . ';');
                }
                $this->recordMigration($fileName);
                $results[$fileName] = ['success' => true];
            } catch (\Exception $e) {
                $results[$fileName] = ['success' => false, 'message' => $e->getMessage()];
                // 如果一个迁移失败，则停止后续迁移
                return ['success' => false, 'message' => "迁移文件 {$fileName} 执行失败: " . $e->getMessage(), 'results' => $results];
            }
        }

        return ['success' => true, 'message' => '数据库迁移成功完成。', 'results' => $results];
    }

    /**
     * 如果需要，创建迁移记录表
     */
    private function createMigrationsTableIfNeeded()
    {
        $tableName = $this->prefix . 'migrations';
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `migration` varchar(255) NOT NULL,
            `executed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        try {
            Database::pdo()->exec($sql);
        } catch (\Exception $e) {
            // 如果这里失败，我们无法继续，所以抛出异常
            throw new \Exception("无法创建核心迁移记录表: " . $e->getMessage());
        }
    }

    /**
     * 获取已执行的迁移列表
     *
     * @return array
     */
    private function getExecutedMigrations()
    {
        $tableName = $this->prefix . 'migrations';
        try {
            $stmt = Database::pdo()->query("SELECT migration FROM `{$tableName}`");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            // 如果查询失败（例如，表不存在），返回空数组
            return [];
        }
    }

    /**
     * 记录已执行的迁移
     *
     * @param string $fileName
     */
    private function recordMigration($fileName)
    {
        $tableName = $this->prefix . 'migrations';
        $sql = "INSERT INTO `{$tableName}` (migration) VALUES (?)";
        Database::execute($sql, [$fileName]);
    }

    /**
     * 自动安装所有插件
     *
     * @return array 安装结果
     */
    public function autoInstallAllPlugins()
    {
        $result = $this->migrationService->scanAndRegisterAllPlugins();
        
        if (!$result['success']) {
            return ['success' => false, 'message' => '插件扫描注册失败: ' . $result['message']];
        }

        $installResult = $this->migrationService->installAllPlugins();
        
        if (!$installResult['success']) {
            return ['success' => false, 'message' => '插件安装失败: ' . $installResult['message']];
        }

        return [
            'success' => true,
            'message' => '所有插件安装成功',
            'registered_plugins' => $result['registered_plugins'],
            'installed_plugins' => $installResult['installed_plugins']
        ];
    }

    /**
     * 自动升级所有插件
     *
     * @return array 升级结果
     */
    public function autoUpgradeAllPlugins()
    {
        $result = $this->versionService->upgradeAllPlugins();
        
        if (!$result['success']) {
            return ['success' => false, 'message' => '插件升级失败: ' . $result['message']];
        }

        return [
            'success' => true,
            'message' => '所有插件升级成功',
            'upgraded_plugins' => $result['upgraded_plugins']
        ];
    }

    /**
     * 自动修复所有插件
     *
     * @return array 修复结果
     */
    public function autoRepairAllPlugins()
    {
        $result = $this->migrationService->repairAllPlugins();
        
        if (!$result['success']) {
            return ['success' => false, 'message' => '插件修复失败: ' . $result['message']];
        }

        return [
            'success' => true,
            'message' => '所有插件修复成功',
            'repaired_plugins' => $result['repaired_plugins']
        ];
    }

    /**
     * 生成数据库报告
     *
     * @return array 报告结果
     */
    public function generateDatabaseReport()
    {
        return $this->migrationService->generateDatabaseReport();
    }

    /**
     * 检查系统健康状态
     *
     * @return array 健康检查结果
     */
    public function checkSystemHealth()
    {
        return $this->migrationService->checkSystemHealth();
    }

    /**
     * 清理无效表注册
     *
     * @return array 清理结果
     */
    public function cleanupInvalidTableRegistrations()
    {
        return $this->migrationService->cleanupInvalidTableRegistrations();
    }

    /**
     * 备份所有插件
     *
     * @return array 备份结果
     */
    public function backupAllPlugins()
    {
        return $this->versionService->backupAllPlugins();
    }

    /**
     * 完整维护流程
     *
     * @return array 维护结果
     */
    public function fullMaintenance()
    {
        $results = [];
        
        // 1. 扫描注册插件
        $registerResult = $this->migrationService->scanAndRegisterAllPlugins();
        $results['register'] = $registerResult;
        
        // 2. 安装插件
        $installResult = $this->migrationService->installAllPlugins();
        $results['install'] = $installResult;
        
        // 3. 升级插件
        $upgradeResult = $this->versionService->upgradeAllPlugins();
        $results['upgrade'] = $upgradeResult;
        
        // 4. 修复插件
        $repairResult = $this->migrationService->repairAllPlugins();
        $results['repair'] = $repairResult;
        
        // 5. 清理无效表注册
        $cleanupResult = $this->migrationService->cleanupInvalidTableRegistrations();
        $results['cleanup'] = $cleanupResult;
        
        // 检查是否有任何步骤失败
        $hasFailures = false;
        foreach ($results as $step => $result) {
            if (!$result['success']) {
                $hasFailures = true;
                break;
            }
        }
        
        return [
            'success' => !$hasFailures,
            'message' => $hasFailures ? '维护过程中有部分步骤失败' : '完整维护流程成功完成',
            'results' => $results
        ];
    }

    /**
     * 统一错误处理
     */
    protected function handleError(\Exception $e, $operation = '') {
        $errorMessage = $operation ? $operation . '失败: ' . $e->getMessage() : $e->getMessage();
        
        // 记录错误日志
        error_log('Service Error: ' . $errorMessage);
        
        // 抛出自定义异常
        throw new \Exception($errorMessage, $e->getCode(), $e);
    }
}
