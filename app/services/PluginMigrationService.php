<?php

namespace app\services;

use app\services\Database;

/**
 * 插件数据库迁移管理服务
 * 
 * 负责处理插件的数据库安装、升级、卸载等操作
 * 自动管理插件创建的数据库表和版本控制
 */
class PluginMigrationService
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
     * 构造函数
     */
    public function __construct()
    {
        $this->prefix = Database::prefix();
    }
    
    /**
     * 注册插件到插件管理表
     * 
     * @param string $pluginPath 插件路径
     * @return array 注册结果
     */
    public function registerPlugin($pluginPath)
    {
        $configFile = $pluginPath . '/plugin.json';
        
        if (!file_exists($configFile)) {
            return ['success' => false, 'message' => '插件配置文件不存在'];
        }
        
        $config = json_decode(file_get_contents($configFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => '插件配置文件格式错误'];
        }
        
        $pluginId = $config['plugin_id'] ?? $config['id'] ?? null;
        if (!$pluginId) {
            return ['success' => false, 'message' => '插件ID缺失'];
        }
        
        // 准备插件数据
        $pluginData = [
            'plugin_id' => $pluginId,
            'name' => $config['name'] ?? '',
            'version' => $config['version'] ?? '1.0.0',
            'type' => $config['type'] ?? 'unknown',
            'category' => $config['category'] ?? null,
            'description' => $config['description'] ?? null,
            'author' => $config['author'] ?? null,
            'website' => $config['website'] ?? null,
            'namespace' => $config['namespace'] ?? null,
            'main_class' => $config['main_class'] ?? null,
            'status' => 'installed',
            'config_json' => json_encode($config['config'] ?? []),
            'dependencies_json' => json_encode($config['dependencies'] ?? []),
            'requirements_json' => json_encode($config['requirements'] ?? []),
            'install_sql_path' => $config['install_sql'] ?? null,
            'uninstall_sql_path' => $config['uninstall_sql'] ?? null,
            'frontend_entry' => $config['frontend_entry'] ?? null,
            'admin_entry' => $config['admin_entry'] ?? null,
            'installed_at' => date('Y-m-d H:i:s')
        ];
        
        // 检查插件是否已存在
        $existingPlugin = Database::queryOne(
            "SELECT id FROM {$this->prefix}admin_plugins WHERE plugin_id = ?",
            [$pluginId]
        );
        
        if ($existingPlugin) {
            // 更新现有插件
            $result = Database::update('admin_plugins', $pluginData, 'plugin_id = ?', [$pluginId]);
        } else {
            // 插入新插件
            $result = Database::insert('admin_plugins', $pluginData);
        }
        
        if (!$result) {
            return ['success' => false, 'message' => '插件注册失败'];
        }
        
        return ['success' => true, 'plugin_id' => $pluginId];
    }
    
    /**
     * 安装插件数据库
     * 
     * @param string $pluginId 插件ID
     * @return array 安装结果
     */
    public function installPluginDatabase($pluginId)
    {
        $plugin = Database::queryOne(
            "SELECT * FROM {$this->prefix}admin_plugins WHERE plugin_id = ?",
            [$pluginId]
        );
        
        if (!$plugin) {
            return ['success' => false, 'message' => '插件不存在'];
        }
        
        $installSqlPath = $plugin['install_sql_path'];
        if (!$installSqlPath) {
            return ['success' => true, 'message' => '插件无需数据库安装'];
        }
        
        // 获取插件路径
        $pluginPath = $this->getPluginPath($pluginId);
        $sqlFile = $pluginPath . '/' . $installSqlPath;
        
        if (!file_exists($sqlFile)) {
            return ['success' => false, 'message' => '安装SQL文件不存在'];
        }
        
        $sqlContent = file_get_contents($sqlFile);
        
        // 记录迁移开始
        $migrationId = $this->recordMigration($pluginId, $plugin['version'], 'install', $installSqlPath, $sqlContent);
        
        try {
            // 解析并执行SQL
            $result = $this->executePluginSql($sqlContent, $pluginId);
            
            if ($result['success']) {
                // 更新迁移状态为完成
                $this->updateMigrationStatus($migrationId, 'completed');
                
                // 注册插件创建的表
                $this->registerPluginTables($pluginId, $sqlContent, $plugin['version']);
                
                return ['success' => true, 'message' => '插件数据库安装成功', 'tables' => $result['tables']];
            } else {
                // 更新迁移状态为失败
                $this->updateMigrationStatus($migrationId, 'failed', $result['message']);
                return $result;
            }
        } catch (\Exception $e) {
            $this->updateMigrationStatus($migrationId, 'failed', $e->getMessage());
            return ['success' => false, 'message' => '数据库安装失败: ' . $e->getMessage()];
        }
    }
    
    /**
     * 卸载插件数据库
     * 
     * @param string $pluginId 插件ID
     * @return array 卸载结果
     */
    public function uninstallPluginDatabase($pluginId)
    {
        $plugin = Database::queryOne(
            "SELECT * FROM {$this->prefix}admin_plugins WHERE plugin_id = ?",
            [$pluginId]
        );
        
        if (!$plugin) {
            return ['success' => false, 'message' => '插件不存在'];
        }
        
        $uninstallSqlPath = $plugin['uninstall_sql_path'];
        if (!$uninstallSqlPath) {
            // 如果没有卸载SQL，则删除插件创建的所有表
            return $this->dropPluginTables($pluginId);
        }
        
        $pluginPath = $this->getPluginPath($pluginId);
        $sqlFile = $pluginPath . '/' . $uninstallSqlPath;
        
        if (!file_exists($sqlFile)) {
            return ['success' => false, 'message' => '卸载SQL文件不存在'];
        }
        
        $sqlContent = file_get_contents($sqlFile);
        
        // 记录迁移开始
        $migrationId = $this->recordMigration($pluginId, $plugin['version'], 'uninstall', $uninstallSqlPath, $sqlContent);
        
        try {
            // 解析并执行SQL
            $result = $this->executePluginSql($sqlContent, $pluginId);
            
            if ($result['success']) {
                // 更新迁移状态为完成
                $this->updateMigrationStatus($migrationId, 'completed');
                
                // 删除插件表注册记录
                Database::execute("DELETE FROM {$this->prefix}admin_plugin_tables WHERE plugin_id = ?", [$pluginId]);
                
                return ['success' => true, 'message' => '插件数据库卸载成功'];
            } else {
                // 更新迁移状态为失败
                $this->updateMigrationStatus($migrationId, 'failed', $result['message']);
                return $result;
            }
        } catch (\Exception $e) {
            $this->updateMigrationStatus($migrationId, 'failed', $e->getMessage());
            return ['success' => false, 'message' => '数据库卸载失败: ' . $e->getMessage()];
        }
    }
    
    /**
     * 执行插件SQL
     *
     * @param string $sqlContent SQL内容
     * @param string $pluginId 插件ID
     * @return array 执行结果
     */
    public function executePluginSql($sqlContent, $pluginId)
    {
        // 替换前缀占位符
        $sqlContent = str_replace('__PREFIX__', $this->prefix, $sqlContent);
        
        // 分割SQL语句
        $statements = $this->splitSqlStatements($sqlContent);
        $tables = [];
        $errors = [];
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) {
                continue;
            }
            
            try {
                Database::execute($statement);
                
                // 提取创建的表名
                if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $statement, $matches)) {
                    $tables[] = $matches[1];
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode('; ', $errors)];
        }
        
        return ['success' => true, 'tables' => $tables];
    }
    
    /**
     * 分割SQL语句
     * 
     * @param string $sqlContent SQL内容
     * @return array SQL语句数组
     */
    protected function splitSqlStatements($sqlContent)
    {
        $statements = [];
        $currentStatement = '';
        $inString = false;
        $stringChar = '';
        
        $lines = explode("\n", $sqlContent);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // 跳过注释和空行
            if (empty($line) || strpos($line, '--') === 0 || strpos($line, '#') === 0) {
                continue;
            }
            
            $currentStatement .= $line . "\n";
            
            // 检查是否在字符串中
            for ($i = 0; $i < strlen($line); $i++) {
                $char = $line[$i];
                
                if (!$inString && ($char === '"' || $char === "'")) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($inString && $char === $stringChar && $line[$i-1] !== '\\') {
                    $inString = false;
                    $stringChar = '';
                }
            }
            
            // 如果不在字符串中且遇到分号，则分割语句
            if (!$inString && strpos($line, ';') !== false) {
                $statements[] = $currentStatement;
                $currentStatement = '';
            }
        }
        
        if (!empty(trim($currentStatement))) {
            $statements[] = $currentStatement;
        }
        
        return $statements;
    }
    
    /**
     * 注册插件创建的表
     * 
     * @param string $pluginId 插件ID
     * @param string $sqlContent SQL内容
     * @param string $version 插件版本
     */
    protected function registerPluginTables($pluginId, $sqlContent, $version)
    {
        $statements = $this->splitSqlStatements($sqlContent);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) {
                continue;
            }
            
            // 提取创建的表名
            if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $statement, $matches)) {
                $tableName = $matches[1];
                $fullTableName = $tableName;
                
                // 如果表名不包含前缀，则添加前缀
                if (strpos($tableName, $this->prefix) !== 0) {
                    $fullTableName = $this->prefix . $tableName;
                }
                
                // 检查表是否已注册
                $existing = Database::queryOne(
                    "SELECT id FROM {$this->prefix}admin_plugin_tables WHERE plugin_id = ? AND table_name = ?",
                    [$pluginId, $tableName]
                );
                
                if (!$existing) {
                    Database::insert('admin_plugin_tables', [
                        'plugin_id' => $pluginId,
                        'table_name' => $tableName,
                        'full_table_name' => $fullTableName,
                        'table_type' => 'data',
                        'install_version' => $version,
                        'sql_schema' => $statement
                    ]);
                }
            }
        }
    }
    
    /**
     * 删除插件创建的所有表
     * 
     * @param string $pluginId 插件ID
     * @return array 删除结果
     */
    protected function dropPluginTables($pluginId)
    {
        $tables = Database::queryAll(
            "SELECT full_table_name FROM {$this->prefix}admin_plugin_tables WHERE plugin_id = ?",
            [$pluginId]
        );
        
        $errors = [];
        $droppedTables = [];
        
        foreach ($tables as $table) {
            $tableName = $table['full_table_name'];
            try {
                Database::execute("DROP TABLE IF EXISTS `{$tableName}`");
                $droppedTables[] = $tableName;
            } catch (\Exception $e) {
                $errors[] = "删除表 {$tableName} 失败: " . $e->getMessage();
            }
        }
        
        // 删除表注册记录
        Database::execute("DELETE FROM {$this->prefix}admin_plugin_tables WHERE plugin_id = ?", [$pluginId]);
        
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode('; ', $errors)];
        }
        
        return ['success' => true, 'message' => '成功删除 ' . count($droppedTables) . ' 个表'];
    }
    
    /**
     * 记录迁移
     *
     * @param string $pluginId 插件ID
     * @param string $version 版本
     * @param string $type 迁移类型
     * @param string $file 文件路径
     * @param string $sql SQL内容
     * @return int 迁移ID
     */
    public function recordMigration($pluginId, $version, $type, $file, $sql)
    {
        return Database::insert('admin_plugin_migrations', [
            'plugin_id' => $pluginId,
            'migration_version' => $version,
            'migration_type' => $type,
            'migration_file' => $file,
            'sql_content' => $sql,
            'status' => 'running',
            'executed_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * 更新迁移状态
     * 
     * @param int $migrationId 迁移ID
     * @param string $status 状态
     * @param string $errorMessage 错误信息
     */
    protected function updateMigrationStatus($migrationId, $status, $errorMessage = null)
    {
        $updateData = [
            'status' => $status
        ];
        
        if ($errorMessage) {
            $updateData['error_message'] = $errorMessage;
        }
        
        Database::update('admin_plugin_migrations', $updateData, 'id = ?', [$migrationId]);
    }
    
    /**
     * 获取插件路径
     * 
     * @param string $pluginId 插件ID
     * @return string 插件路径
     */
    protected function getPluginPath($pluginId)
    {
        return realpath(__DIR__ . '/../../public/plugins') . '/apps/' . $pluginId;
    }
    
    /**
     * 获取插件创建的所有表
     * 
     * @param string $pluginId 插件ID
     * @return array 表列表
     */
    public function getPluginTables($pluginId)
    {
        return Database::queryAll(
            "SELECT * FROM {$this->prefix}admin_plugin_tables WHERE plugin_id = ?",
            [$pluginId]
        );
    }
    
    /**
     * 获取插件迁移历史
     * 
     * @param string $pluginId 插件ID
     * @return array 迁移历史
     */
    public function getPluginMigrations($pluginId)
    {
        return Database::queryAll(
            "SELECT * FROM {$this->prefix}admin_plugin_migrations WHERE plugin_id = ? ORDER BY created_at DESC",
            [$pluginId]
        );
    }
    
    /**
     * 批量扫描并注册所有插件
     * 
     * @return array 扫描结果
     */
    public function scanAndRegisterAllPlugins()
    {
        $pluginsDir = realpath(__DIR__ . '/../../public/plugins');
        $results = [];
        $registeredPlugins = [];

        if (!$pluginsDir || !is_dir($pluginsDir)) {
            return [
                'success' => false,
                'message' => '插件目录不存在',
                'results' => [],
                'registered_plugins' => []
            ];
        }

        // 扫描所有类型的插件目录
        // 这里仍然以一级目录类型进行限制，但对每个类型目录内部改为递归查找 plugin.json，
        // 以便支持 verification/basic/simple 这类多级子目录结构的插件。
        $pluginTypes = ['apps', 'email', 'payment', 'sms', 'verification', 'identity', 'notification'];

        foreach ($pluginTypes as $type) {
            $typeDir = $pluginsDir . '/' . $type;
            if (!is_dir($typeDir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($typeDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            /** @var \SplFileInfo $fileInfo */
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile()) {
                    continue;
                }

                if ($fileInfo->getFilename() !== 'plugin.json') {
                    continue;
                }

                // pluginPath 为实际插件根目录（plugin.json 所在目录）
                $pluginPath = $fileInfo->getPathname();
                $pluginPath = dirname($pluginPath);

                // 生成一个稳定的键用于结果数组展示，例如 "verification/basic/simple"
                $relativePath = str_replace($pluginsDir . DIRECTORY_SEPARATOR, '', $pluginPath);
                $relativePath = str_replace('\\', '/', $relativePath);

                $result = $this->registerPlugin($pluginPath);
                $results[$relativePath] = $result;

                if (!empty($result['success'])) {
                    $registeredPlugins[] = $result['plugin_id'];
                }
            }
        }

        return [
            'success' => true,
            'message' => '插件扫描完成',
            'results' => $results,
            'registered_plugins' => $registeredPlugins
        ];
    }
    
    /**
     * 安装所有插件
     * 
     * @return array 安装结果
     */
    public function installAllPlugins()
    {
        $plugins = Database::queryAll("SELECT plugin_id FROM {$this->prefix}admin_plugins WHERE status = 'installed'");
        $installedPlugins = [];
        $errors = [];
        
        foreach ($plugins as $plugin) {
            $pluginId = $plugin['plugin_id'];
            $result = $this->installPluginDatabase($pluginId);
            
            if ($result['success']) {
                $installedPlugins[] = $pluginId;
            } else {
                $errors[] = "插件 {$pluginId} 安装失败: " . $result['message'];
            }
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => '部分插件安装失败',
                'installed_plugins' => $installedPlugins,
                'errors' => $errors
            ];
        }
        
        return [
            'success' => true,
            'message' => '所有插件安装成功',
            'installed_plugins' => $installedPlugins
        ];
    }
    
    /**
     * 修复所有插件
     * 
     * @return array 修复结果
     */
    public function repairAllPlugins()
    {
        $plugins = Database::queryAll("SELECT plugin_id FROM {$this->prefix}admin_plugins");
        $repairedPlugins = [];
        $errors = [];
        
        foreach ($plugins as $plugin) {
            $pluginId = $plugin['plugin_id'];
            
            // 检查插件表是否存在
            $tables = $this->getPluginTables($pluginId);
            $missingTables = [];
            
            foreach ($tables as $table) {
                $tableName = $table['full_table_name'];
                try {
                    Database::execute("SELECT 1 FROM `{$tableName}` LIMIT 1");
                } catch (\Exception $e) {
                    $missingTables[] = $tableName;
                }
            }
            
            if (!empty($missingTables)) {
                // 重新安装插件数据库
                $result = $this->installPluginDatabase($pluginId);
                
                if ($result['success']) {
                    $repairedPlugins[] = $pluginId;
                } else {
                    $errors[] = "插件 {$pluginId} 修复失败: " . $result['message'];
                }
            } else {
                $repairedPlugins[] = $pluginId;
            }
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => '部分插件修复失败',
                'repaired_plugins' => $repairedPlugins,
                'errors' => $errors
            ];
        }
        
        return [
            'success' => true,
            'message' => '所有插件修复成功',
            'repaired_plugins' => $repairedPlugins
        ];
    }
    
    /**
     * 生成数据库报告
     * 
     * @return array 数据库报告
     */
    public function generateDatabaseReport()
    {
        $plugins = Database::queryAll("SELECT * FROM {$this->prefix}admin_plugins");
        $report = [];
        
        foreach ($plugins as $plugin) {
            $pluginId = $plugin['plugin_id'];
            $tables = $this->getPluginTables($pluginId);
            $migrations = $this->getPluginMigrations($pluginId);
            
            $report[$pluginId] = [
                'name' => $plugin['name'],
                'version' => $plugin['version'],
                'status' => $plugin['status'],
                'tables_count' => count($tables),
                'tables' => $tables,
                'migrations_count' => count($migrations),
                'last_migration' => !empty($migrations) ? $migrations[0] : null
            ];
        }
        
        return [
            'success' => true,
            'message' => '数据库报告生成成功',
            'report' => $report,
            'total_plugins' => count($plugins),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * 检查系统健康状态
     * 
     * @return array 健康检查结果
     */
    public function checkSystemHealth()
    {
        $issues = [];
        $warnings = [];
        
        // 检查插件管理表是否存在
        try {
            Database::execute("SELECT 1 FROM {$this->prefix}admin_plugins LIMIT 1");
                        Database::execute("SELECT 1 FROM {$this->prefix}admin_plugin_tables LIMIT 1");
                        Database::execute("SELECT 1 FROM {$this->prefix}admin_plugin_migrations LIMIT 1");
        } catch (\Exception $e) {
            $issues[] = '插件管理表不存在或损坏';
        }
        
        // 检查插件表注册
        $plugins = Database::queryAll("SELECT plugin_id FROM {$this->prefix}plugins");
        foreach ($plugins as $plugin) {
            $pluginId = $plugin['plugin_id'];
            $tables = $this->getPluginTables($pluginId);
            
            foreach ($tables as $table) {
                $tableName = $table['full_table_name'];
                try {
                    Database::execute("SELECT 1 FROM `{$tableName}` LIMIT 1");
                } catch (\Exception $e) {
                    $issues[] = "插件 {$pluginId} 的表 {$tableName} 不存在";
                }
            }
        }
        
        // 检查失败的迁移
        $failedMigrations = Database::queryAll(
            "SELECT plugin_id, migration_file, error_message FROM {$this->prefix}admin_plugin_migrations WHERE status = 'failed'"
        );
        
        foreach ($failedMigrations as $migration) {
            $warnings[] = "插件 {$migration['plugin_id']} 的迁移 {$migration['migration_file']} 失败: {$migration['error_message']}";
        }
        
        $health = 'healthy';
        if (!empty($issues)) {
            $health = 'critical';
        } elseif (!empty($warnings)) {
            $health = 'warning';
        }
        
        return [
            'success' => true,
            'message' => '系统健康检查完成',
            'health' => $health,
            'issues' => $issues,
            'warnings' => $warnings,
            'checked_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * 清理无效表注册
     * 
     * @return array 清理结果
     */
    public function cleanupInvalidTableRegistrations()
    {
        $invalidRegistrations = [];
        $cleanedCount = 0;
        
        // 获取所有插件表注册
        $tableRegistrations = Database::queryAll("SELECT * FROM {$this->prefix}admin_plugin_tables");
        
        foreach ($tableRegistrations as $registration) {
            $tableName = $registration['full_table_name'];
            
            try {
                Database::execute("SELECT 1 FROM `{$tableName}` LIMIT 1");
            } catch (\Exception $e) {
                // 表不存在，删除注册记录
                Database::execute(
                    "DELETE FROM {$this->prefix}admin_plugin_tables WHERE id = ?",
                    [$registration['id']]
                );
                $invalidRegistrations[] = $tableName;
                $cleanedCount++;
            }
        }
        
        return [
            'success' => true,
            'message' => '无效表注册清理完成',
            'cleaned_count' => $cleanedCount,
            'invalid_registrations' => $invalidRegistrations,
            'cleaned_at' => date('Y-m-d H:i:s')
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
