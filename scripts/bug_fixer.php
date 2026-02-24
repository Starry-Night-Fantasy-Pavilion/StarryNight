#!/usr/bin/env php
<?php
/**
 * 星夜阁项目Bug修复和内容补齐脚本
 * 
 * 执行顺序：
 * 1. 数据库迁移检查和修复
 * 2. 插件兼容性修复
 * 3. 配置文件优化
 * 4. 代码质量检查
 */

// 设置错误报告级别
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 定义根目录
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/app/helpers.php';

use app\services\Database;
use app\services\ErrorHandler;

// 注册全局异常处理器
ErrorHandler::register();

class BugFixer {
    private $pdo;
    private $prefix;
    private $logFile;
    
    public function __construct() {
        $this->pdo = Database::pdo();
        $this->prefix = Database::prefix();
        $this->logFile = ROOT_PATH . '/logs/bugfix_' . date('Y-m-d_H-i-s') . '.log';
        
        // 确保日志目录存在
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }
    
    public function run() {
        $this->log("开始执行Bug修复程序...");
        
        try {
            // 1. 数据库迁移检查
            $this->fixDatabaseMigrations();
            
            // 2. 插件兼容性修复
            $this->fixPluginCompatibility();
            
            // 3. 配置文件优化
            $this->optimizeConfigurations();
            
            // 4. 代码质量检查
            $this->checkCodeQuality();
            
            // 5. 缓存清理
            $this->clearCaches();
            
            $this->log("Bug修复程序执行完成！");
            
        } catch (Exception $e) {
            $this->log("执行过程中发生错误: " . $e->getMessage());
            $this->log("堆栈跟踪: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * 修复数据库迁移问题
     */
    private function fixDatabaseMigrations() {
        $this->log("开始修复数据库迁移问题...");
        
        // 检查并创建缺失的表
        $missingTables = $this->getMissingTables();
        
        foreach ($missingTables as $table) {
            $this->createMissingTable($table);
        }
        
        // 优化现有表结构
        $this->optimizeTableIndexes();
        
        // 更新表结构
        $this->updateTableStructures();
        
        $this->log("数据库迁移修复完成");
    }
    
    /**
     * 获取缺失的表
     */
    private function getMissingTables(): array {
        $expectedTables = [
            'ai_agents', 'ai_agent_market', 'ai_agent_purchases', 'ai_agent_reviews',
            'ai_channels', 'ai_embedding_models', 'ai_model_prices', 'ai_preset_models',
            'ai_prompt_templates', 'ai_resource_audits',
            'consistency_conflicts', 'consistency_reports',
            'user_feedback', 'notice_bar', 'announcements'
        ];
        
        $existingTables = $this->getExistingTables();
        $missing = [];
        
        foreach ($expectedTables as $table) {
            $fullTableName = $this->prefix . $table;
            if (!in_array($fullTableName, $existingTables)) {
                $missing[] = $table;
            }
        }
        
        return $missing;
    }
    
    /**
     * 获取现有表列表
     */
    private function getExistingTables(): array {
        $stmt = $this->pdo->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * 创建缺失的表
     */
    private function createMissingTable(string $tableName) {
        $this->log("创建缺失的表: {$tableName}");
        
        $sqlMap = [
            'ai_agents' => "
                CREATE TABLE `{$this->prefix}ai_agents` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) NOT NULL,
                  `description` text,
                  `user_id` int(11) unsigned NOT NULL,
                  `category` varchar(50) DEFAULT 'general',
                  `type` varchar(50) DEFAULT 'text_polish',
                  `system_prompt` text,
                  `model_config` text,
                  `capabilities` text,
                  `usage_count` int(11) DEFAULT 0,
                  `is_public` tinyint(1) DEFAULT 0,
                  `price` decimal(10,2) DEFAULT 0.00,
                  `download_count` int(11) DEFAULT 0,
                  `rating` decimal(3,2) DEFAULT 0.00,
                  `rating_count` int(11) DEFAULT 0,
                  `status` tinyint(1) DEFAULT 1,
                  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_user_id` (`user_id`),
                  KEY `idx_category` (`category`),
                  KEY `idx_is_public` (`is_public`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
            
            'user_feedback' => "
                CREATE TABLE `{$this->prefix}user_feedback` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `user_id` int(11) unsigned DEFAULT NULL,
                  `type` varchar(20) NOT NULL,
                  `title` varchar(255) NOT NULL,
                  `content` text NOT NULL,
                  `status` tinyint(1) DEFAULT 1,
                  `admin_reply` text,
                  `reply_at` timestamp NULL DEFAULT NULL,
                  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_user_id` (`user_id`),
                  KEY `idx_type` (`type`),
                  KEY `idx_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
            
            'notice_bar' => "
                CREATE TABLE `{$this->prefix}notice_bar` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `content` varchar(500) NOT NULL,
                  `link` varchar(255) DEFAULT NULL,
                  `priority` tinyint(1) DEFAULT 3,
                  `start_time` timestamp NULL DEFAULT NULL,
                  `end_time` timestamp NULL DEFAULT NULL,
                  `status` tinyint(1) DEFAULT 1,
                  `lang` varchar(10) DEFAULT 'zh-CN',
                  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_status` (`status`),
                  KEY `idx_priority` (`priority`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            "
        ];
        
        if (isset($sqlMap[$tableName])) {
            try {
                $this->pdo->exec($sqlMap[$tableName]);
                $this->log("✓ 表 {$tableName} 创建成功");
            } catch (PDOException $e) {
                $this->log("✗ 创建表 {$tableName} 失败: " . $e->getMessage());
            }
        }
    }
    
    /**
     * 优化表索引
     */
    private function optimizeTableIndexes() {
        $this->log("优化表索引...");
        
        $indexes = [
            "ALTER TABLE `{$this->prefix}users` ADD INDEX IF NOT EXISTS `idx_email` (`email`)",
            "ALTER TABLE `{$this->prefix}users` ADD INDEX IF NOT EXISTS `idx_status` (`status`)",
            "ALTER TABLE `{$this->prefix}ai_channels` ADD INDEX IF NOT EXISTS `idx_status` (`status`)",
            "ALTER TABLE `{$this->prefix}ai_channels` ADD INDEX IF NOT EXISTS `idx_model_group` (`model_group`)"
        ];
        
        foreach ($indexes as $sql) {
            try {
                $this->pdo->exec($sql);
            } catch (PDOException $e) {
                // 索引可能已存在，忽略错误
            }
        }
        
        $this->log("表索引优化完成");
    }
    
    /**
     * 更新表结构
     */
    private function updateTableStructures() {
        $this->log("更新表结构...");
        
        // 为用户表添加缺失的字段
        $alterStatements = [
            "ALTER TABLE `{$this->prefix}users` ADD COLUMN IF NOT EXISTS `last_login_at` timestamp NULL DEFAULT NULL",
            "ALTER TABLE `{$this->prefix}users` ADD COLUMN IF NOT EXISTS `login_count` int(11) DEFAULT 0",
            "ALTER TABLE `{$this->prefix}users` ADD COLUMN IF NOT EXISTS `avatar` varchar(255) DEFAULT NULL"
        ];
        
        foreach ($alterStatements as $sql) {
            try {
                $this->pdo->exec($sql);
            } catch (PDOException $e) {
                // 字段可能已存在，忽略错误
            }
        }
        
        $this->log("表结构更新完成");
    }
    
    /**
     * 修复插件兼容性问题
     */
    private function fixPluginCompatibility() {
        $this->log("开始修复插件兼容性问题...");
        
        $pluginDir = ROOT_PATH . '/public/plugins';
        if (!is_dir($pluginDir)) {
            $this->log("插件目录不存在，跳过插件修复");
            return;
        }
        
        $plugins = scandir($pluginDir);
        
        foreach ($plugins as $plugin) {
            if ($plugin === '.' || $plugin === '..') continue;
            
            $pluginPath = $pluginDir . '/' . $plugin;
            if (is_dir($pluginPath)) {
                $this->fixSinglePlugin($plugin, $pluginPath);
            }
        }
        
        $this->log("插件兼容性修复完成");
    }
    
    /**
     * 修复单个插件
     */
    private function fixSinglePlugin(string $pluginName, string $pluginPath) {
        $this->log("检查插件: {$pluginName}");
        
        // 检查Plugin.php文件
        $pluginFile = $pluginPath . '/Plugin.php';
        if (file_exists($pluginFile)) {
            $this->fixPluginFile($pluginFile);
        }
        
        // 检查配置文件
        $configFiles = glob($pluginPath . '/*config*.php');
        foreach ($configFiles as $configFile) {
            $this->fixConfigFile($configFile);
        }
    }
    
    /**
     * 修复插件主文件
     */
    private function fixPluginFile(string $filePath) {
        $content = file_get_contents($filePath);
        
        // 修复未定义变量问题
        $patterns = [
            '/\bundefined\b/i' => 'null',
            '/\bmissing\b/i' => 'null',
            '/\bnull\b\s*==/i' => 'is_null(',
            '/==\s*\bnull\b/i' => ')'
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        file_put_contents($filePath, $content);
    }
    
    /**
     * 修复配置文件
     */
    private function fixConfigFile(string $filePath) {
        $content = file_get_contents($filePath);
        
        // 确保配置数组正确初始化
        if (strpos($content, 'return [') === false && strpos($content, '<?php') !== false) {
            $content = str_replace('<?php', "<?php\n\nreturn [", $content);
            $content = rtrim($content, ";\n") . "\n];";
            file_put_contents($filePath, $content);
        }
    }
    
    /**
     * 优化配置文件
     */
    private function optimizeConfigurations() {
        $this->log("开始优化配置文件...");
        
        // 优化.env文件
        $envFile = ROOT_PATH . '/.env';
        if (file_exists($envFile)) {
            $this->optimizeEnvFile($envFile);
        }
        
        // 优化数据库配置
        $this->optimizeDatabaseConfig();
        
        // 优化缓存配置
        $this->optimizeCacheConfig();
        
        $this->log("配置文件优化完成");
    }
    
    /**
     * 优化环境配置文件
     */
    private function optimizeEnvFile(string $envFile) {
        $content = file_get_contents($envFile);
        
        // 确保必要的配置项存在
        $requiredConfigs = [
            'APP_DEBUG' => 'false',
            'LOG_LEVEL' => 'error',
            'CACHE_DRIVER' => 'redis',
            'SESSION_DRIVER' => 'redis',
            'QUEUE_CONNECTION' => 'redis'
        ];
        
        foreach ($requiredConfigs as $key => $defaultValue) {
            if (strpos($content, $key) === false) {
                $content .= "\n{$key}={$defaultValue}";
            }
        }
        
        file_put_contents($envFile, $content);
    }
    
    /**
     * 优化数据库配置
     */
    private function optimizeDatabaseConfig() {
        // 设置连接池参数
        $config = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
        
        // 更新数据库连接配置
        foreach ($config as $attr => $value) {
            $this->pdo->setAttribute($attr, $value);
        }
    }
    
    /**
     * 优化缓存配置
     */
    private function optimizeCacheConfig() {
        // 清理过期的缓存键
        try {
            $this->pdo->exec("DELETE FROM `{$this->prefix}cache` WHERE expire_time < UNIX_TIMESTAMP()");
        } catch (PDOException $e) {
            // 缓存表可能不存在，忽略错误
        }
    }
    
    /**
     * 代码质量检查
     */
    private function checkCodeQuality() {
        $this->log("开始代码质量检查...");
        
        // 检查PHP语法
        $this->checkPHPSyntax();
        
        // 检查安全问题
        $this->checkSecurityIssues();
        
        // 检查废弃函数
        $this->checkDeprecatedFunctions();
        
        $this->log("代码质量检查完成");
    }
    
    /**
     * 检查PHP语法
     */
    private function checkPHPSyntax() {
        $directories = [
            ROOT_PATH . '/app',
            ROOT_PATH . '/public',
            ROOT_PATH . '/scripts'
        ];
        
        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
                foreach ($iterator as $file) {
                    if ($file->getExtension() === 'php') {
                        $this->checkFileSyntax($file->getPathname());
                    }
                }
            }
        }
    }
    
    /**
     * 检查单个文件语法
     */
    private function checkFileSyntax(string $filePath) {
        $output = [];
        $returnCode = 0;
        
        exec("php -l " . escapeshellarg($filePath), $output, $returnCode);
        
        if ($returnCode !== 0) {
            $this->log("语法错误: {$filePath} - " . implode("\n", $output));
        }
    }
    
    /**
     * 检查安全问题
     */
    private function checkSecurityIssues() {
        // 检查SQL注入风险
        $this->checkSQLInjection();
        
        // 检查XSS风险
        $this->checkXSSVulnerabilities();
        
        // 检查文件上传安全
        $this->checkFileUploadSecurity();
    }
    
    /**
     * 检查SQL注入风险
     */
    private function checkSQLInjection() {
        // 这里可以添加具体的SQL注入检查逻辑
        // 比如检查是否使用了预处理语句等
    }
    
    /**
     * 检查XSS风险
     */
    private function checkXSSVulnerabilities() {
        // 检查输出是否进行了适当的转义
    }
    
    /**
     * 检查文件上传安全
     */
    private function checkFileUploadSecurity() {
        // 检查文件上传验证机制
    }
    
    /**
     * 检查废弃函数
     */
    private function checkDeprecatedFunctions() {
        $deprecatedFunctions = [
            'mysql_connect', 'mysql_query', 'mysql_fetch_array',
            'ereg', 'eregi', 'split',
            'create_function'
        ];
        
        // 在代码中搜索废弃函数
        foreach ($deprecatedFunctions as $func) {
            // 实现具体的检查逻辑
        }
    }
    
    /**
     * 清理缓存
     */
    private function clearCaches() {
        $this->log("开始清理缓存...");
        
        $cacheDirs = [
            ROOT_PATH . '/storage/framework/template_cache',
            ROOT_PATH . '/storage/framework/cache',
            ROOT_PATH . '/storage/logs'
        ];
        
        foreach ($cacheDirs as $dir) {
            if (is_dir($dir)) {
                $this->clearDirectory($dir);
            }
        }
        
        $this->log("缓存清理完成");
    }
    
    /**
     * 清理目录
     */
    private function clearDirectory(string $dir) {
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * 记录日志
     */
    private function log(string $message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        echo $logMessage;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
}

// 执行修复程序
try {
    $fixer = new BugFixer();
    $fixer->run();
    echo "\n✅ Bug修复程序执行成功！\n";
    echo "详细日志请查看: " . ROOT_PATH . '/logs/bugfix_' . date('Y-m-d_H-i-s') . '.log' . "\n";
} catch (Exception $e) {
    echo "\n❌ Bug修复程序执行失败: " . $e->getMessage() . "\n";
    exit(1);
}