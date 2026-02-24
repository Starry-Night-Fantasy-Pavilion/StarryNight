<?php

namespace app\services;

use app\services\Database;

/**
 * 插件版本管理服务
 * 
 * 负责管理插件的版本控制和数据库迁移
 */
class PluginVersionService
{
    /**
     * @var PluginMigrationService 迁移服务
     */
    protected $migrationService;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->migrationService = new PluginMigrationService();
    }
    
    /**
     * 检查插件是否需要升级
     * 
     * @param string $pluginId 插件ID
     * @param string $newVersion 新版本
     * @return array 检查结果
     */
    public function checkPluginUpgrade($pluginId, $newVersion)
    {
        $plugin = Database::queryOne(
            "SELECT version, status FROM " . Database::prefix() . "plugins WHERE plugin_id = ?",
            [$pluginId]
        );
        
        if (!$plugin) {
            return ['needs_upgrade' => false, 'reason' => '插件不存在'];
        }
        
        $currentVersion = $plugin['version'];
        
        if (version_compare($newVersion, $currentVersion, '<=')) {
            return ['needs_upgrade' => false, 'reason' => '已是最新版本'];
        }
        
        return [
            'needs_upgrade' => true,
            'current_version' => $currentVersion,
            'new_version' => $newVersion
        ];
    }
    
    /**
     * 升级插件
     * 
     * @param string $pluginId 插件ID
     * @param string $newVersion 新版本
     * @param string $upgradeSql 升级SQL
     * @return array 升级结果
     */
    public function upgradePlugin($pluginId, $newVersion, $upgradeSql = null)
    {
        $checkResult = $this->checkPluginUpgrade($pluginId, $newVersion);
        
        if (!$checkResult['needs_upgrade']) {
            return ['success' => false, 'message' => $checkResult['reason']];
        }
        
        Database::beginTransaction();
        
        try {
            // 执行升级SQL（如果提供）
            if ($upgradeSql) {
                $result = $this->migrationService->executePluginSql($upgradeSql, $pluginId);
                if (!$result['success']) {
                    throw new \Exception('升级SQL执行失败: ' . $result['message']);
                }
                
                // 记录升级迁移
                $this->migrationService->recordMigration(
                    $pluginId, 
                    $newVersion, 
                    'upgrade', 
                    'upgrade_' . $newVersion, 
                    $upgradeSql
                );
            }
            
            // 更新插件版本
            Database::update(
                'admin_plugins',
                ['version' => $newVersion], 
                'plugin_id = ?', 
                [$pluginId]
            );
            
            Database::commit();
            
            return [
                'success' => true, 
                'message' => '插件升级成功',
                'from_version' => $checkResult['current_version'],
                'to_version' => $newVersion
            ];
            
        } catch (\Exception $e) {
            Database::rollback();
            return ['success' => false, 'message' => '插件升级失败: ' . $e->getMessage()];
        }
    }
    
    /**
     * 修复插件数据库
     * 
     * @param string $pluginId 插件ID
     * @return array 修复结果
     */
    public function repairPlugin($pluginId)
    {
        $plugin = Database::queryOne(
            "SELECT * FROM " . Database::prefix() . "admin_plugins WHERE plugin_id = ?",
            [$pluginId]
        );
        
        if (!$plugin) {
            return ['success' => false, 'message' => '插件不存在'];
        }
        
        Database::beginTransaction();
        
        try {
            // 重新安装插件数据库
            $result = $this->migrationService->installPluginDatabase($pluginId);
            if (!$result['success']) {
                throw new \Exception('数据库重新安装失败: ' . $result['message']);
            }
            
            // 记录修复迁移
            $this->migrationService->recordMigration(
                $pluginId, 
                $plugin['version'], 
                'repair', 
                'repair_' . date('Y-m-d_H-i-s'), 
                'Plugin repair operation'
            );
            
            Database::commit();
            
            return ['success' => true, 'message' => '插件修复成功'];
            
        } catch (\Exception $e) {
            Database::rollback();
            return ['success' => false, 'message' => '插件修复失败: ' . $e->getMessage()];
        }
    }
    
    /**
     * 获取插件版本历史
     * 
     * @param string $pluginId 插件ID
     * @return array 版本历史
     */
    public function getPluginVersionHistory($pluginId)
    {
        return Database::queryAll(
            "SELECT migration_version, migration_type, status, executed_at, error_message 
             FROM " . Database::prefix() . "plugin_migrations 
             WHERE plugin_id = ? 
             ORDER BY created_at DESC",
            [$pluginId]
        );
    }
    
    /**
     * 批量检查所有插件的版本状态
     * 
     * @return array 版本状态报告
     */
    public function batchCheckPluginVersions()
    {
        $plugins = Database::queryAll(
            "SELECT plugin_id, name, version, install_sql_path FROM " . Database::prefix() . "admin_plugins"
        );
        
        $report = [];
        
        foreach ($plugins as $plugin) {
            $pluginId = $plugin['plugin_id'];
            $pluginPath = $this->getPluginPath($pluginId);
            
            if (!file_exists($pluginPath . '/plugin.json')) {
                $report[$pluginId] = [
                    'status' => 'error',
                    'message' => '插件配置文件不存在'
                ];
                continue;
            }
            
            $config = json_decode(file_get_contents($pluginPath . '/plugin.json'), true);
            $configVersion = $config['version'] ?? 'unknown';
            
            if (version_compare($configVersion, $plugin['version'], '>')) {
                $report[$pluginId] = [
                    'status' => 'needs_upgrade',
                    'current_version' => $plugin['version'],
                    'available_version' => $configVersion,
                    'plugin_name' => $plugin['name']
                ];
            } else {
                $report[$pluginId] = [
                    'status' => 'up_to_date',
                    'current_version' => $plugin['version'],
                    'plugin_name' => $plugin['name']
                ];
            }
        }
        
        return $report;
    }
    
    /**
     * 批量升级所有插件
     * 
     * @return array 升级结果
     */
    public function batchUpgradePlugins()
    {
        $versionReport = $this->batchCheckPluginVersions();
        $results = [];
        
        foreach ($versionReport as $pluginId => $report) {
            if ($report['status'] === 'needs_upgrade') {
                $pluginPath = $this->getPluginPath($pluginId);
                $config = json_decode(file_get_contents($pluginPath . '/plugin.json'), true);
                
                // 检查是否有升级SQL文件
                $upgradeSqlPath = $pluginPath . '/database/upgrade.sql';
                $upgradeSql = null;
                
                if (file_exists($upgradeSqlPath)) {
                    $upgradeSql = file_get_contents($upgradeSqlPath);
                }
                
                $result = $this->upgradePlugin($pluginId, $report['available_version'], $upgradeSql);
                $results[$pluginId] = $result;
            }
        }
        
        return $results;
    }
    
    /**
     * 获取插件路径
     * 
     * @param string $pluginId 插件ID
     * @return string 插件路径
     */
    protected function getPluginPath($pluginId)
    {
        $pluginsDir = realpath(__DIR__ . '/../../public/plugins');
        
        // 尝试在不同类型的插件目录中查找
        $types = ['apps', 'email', 'payment', 'sms', 'verification', 'identity', 'notification'];
        
        foreach ($types as $type) {
            $pluginPath = $pluginsDir . '/' . $type . '/' . $pluginId;
            if (is_dir($pluginPath)) {
                return $pluginPath;
            }
        }
        
        return $pluginsDir . '/apps/' . $pluginId; // 默认返回apps目录
    }
    
    /**
     * 创建插件备份
     * 
     * @param string $pluginId 插件ID
     * @return array 备份结果
     */
    public function createPluginBackup($pluginId)
    {
        $plugin = Database::queryOne(
            "SELECT * FROM " . Database::prefix() . "admin_plugins WHERE plugin_id = ?",
            [$pluginId]
        );
        
        if (!$plugin) {
            return ['success' => false, 'message' => '插件不存在'];
        }
        
        $tables = $this->migrationService->getPluginTables($pluginId);
        $backupData = [];
        
        foreach ($tables as $table) {
            $tableName = $table['full_table_name'];
            $data = Database::queryAll("SELECT * FROM {$tableName}");
            $backupData[$tableName] = $data;
        }
        
        $backupFile = realpath(__DIR__ . '/../../storage') . '/backups/plugin_' . $pluginId . '_' . date('Y-m-d_H-i-s') . '.json';
        
        $backupDir = dirname($backupFile);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupContent = [
            'plugin_id' => $pluginId,
            'plugin_info' => $plugin,
            'backup_time' => date('Y-m-d H:i:s'),
            'tables' => $backupData
        ];
        
        if (file_put_contents($backupFile, json_encode($backupContent, JSON_PRETTY_PRINT))) {
            return ['success' => true, 'backup_file' => $backupFile];
        }
        
        return ['success' => false, 'message' => '备份文件创建失败'];
    }
    
    /**
     * 恢复插件备份
     * 
     * @param string $backupFile 备份文件路径
     * @return array 恢复结果
     */
    public function restorePluginBackup($backupFile)
    {
        if (!file_exists($backupFile)) {
            return ['success' => false, 'message' => '备份文件不存在'];
        }
        
        $backupContent = json_decode(file_get_contents($backupFile), true);
        
        if (!$backupContent || !isset($backupContent['plugin_id'])) {
            return ['success' => false, 'message' => '备份文件格式错误'];
        }
        
        Database::beginTransaction();
        
        try {
            foreach ($backupContent['tables'] as $tableName => $data) {
                // 清空现有数据
                Database::execute("DELETE FROM {$tableName}");
                
                // 恢复数据
                foreach ($data as $row) {
                    $columns = array_keys($row);
                    $placeholders = array_fill(0, count($columns), '?');
                    
                    $sql = "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
                    Database::execute($sql, array_values($row));
                }
            }
            
            Database::commit();
            
            return [
                'success' => true, 
                'message' => '插件数据恢复成功',
                'plugin_id' => $backupContent['plugin_id'],
                'backup_time' => $backupContent['backup_time']
            ];
            
        } catch (\Exception $e) {
            Database::rollback();
            return ['success' => false, 'message' => '插件数据恢复失败: ' . $e->getMessage()];
        }
    }
    
    /**
     * 升级所有插件
     * 
     * @return array 升级结果
     */
    public function upgradeAllPlugins()
    {
        $versionReport = $this->batchCheckPluginVersions();
        $upgradedPlugins = [];
        $errors = [];
        
        foreach ($versionReport as $pluginId => $report) {
            if ($report['status'] === 'needs_upgrade') {
                $pluginPath = $this->getPluginPath($pluginId);
                $config = json_decode(file_get_contents($pluginPath . '/plugin.json'), true);
                
                // 检查是否有升级SQL文件
                $upgradeSqlPath = $pluginPath . '/database/upgrade.sql';
                $upgradeSql = null;
                
                if (file_exists($upgradeSqlPath)) {
                    $upgradeSql = file_get_contents($upgradeSqlPath);
                }
                
                $result = $this->upgradePlugin($pluginId, $report['available_version'], $upgradeSql);
                
                if ($result['success']) {
                    $upgradedPlugins[] = $pluginId;
                } else {
                    $errors[] = "插件 {$pluginId} 升级失败: " . $result['message'];
                }
            }
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => '部分插件升级失败',
                'upgraded_plugins' => $upgradedPlugins,
                'errors' => $errors
            ];
        }
        
        return [
            'success' => true,
            'message' => '所有插件升级成功',
            'upgraded_plugins' => $upgradedPlugins
        ];
    }
    
    /**
     * 备份所有插件
     * 
     * @return array 备份结果
     */
    public function backupAllPlugins()
    {
        $plugins = Database::queryAll("SELECT plugin_id FROM " . Database::prefix() . "admin_plugins");
        $backedUpPlugins = [];
        $errors = [];
        
        foreach ($plugins as $plugin) {
            $pluginId = $plugin['plugin_id'];
            $result = $this->createPluginBackup($pluginId);
            
            if ($result['success']) {
                $backedUpPlugins[] = [
                    'plugin_id' => $pluginId,
                    'backup_file' => $result['backup_file']
                ];
            } else {
                $errors[] = "插件 {$pluginId} 备份失败: " . $result['message'];
            }
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => '部分插件备份失败',
                'backed_up_plugins' => $backedUpPlugins,
                'errors' => $errors
            ];
        }
        
        return [
            'success' => true,
            'message' => '所有插件备份成功',
            'backed_up_plugins' => $backedUpPlugins
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
