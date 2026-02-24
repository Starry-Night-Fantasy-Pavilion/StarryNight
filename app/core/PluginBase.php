<?php

namespace Core;

use app\services\Database;
use PDO;

/**
 * 插件基类
 * 所有插件都应该继承此类
 */
abstract class PluginBase
{
    protected $config = [];

    /**
     * 获取插件信息
     * @return array
     */
    abstract public function getInfo(): array;

    /**
     * 安装插件
     * @return bool
     */
    public function install(): bool
    {
        return true;
    }

    /**
     * 卸载插件
     * @return bool
     */
    public function uninstall(): bool
    {
        return true;
    }

    /**
     * 启用插件
     * @return bool
     */
    public function enable(): bool
    {
        return true;
    }

    /**
     * 禁用插件
     * @return bool
     */
    public function disable(): bool
    {
        return true;
    }

    /**
     * 获取配置
     * @return array
     */
    public function getConfig(): array
    {
        if (empty($this->config)) {
            $this->loadConfigFromDatabase();
        }
        return $this->config;
    }

    /**
     * 从数据库加载配置
     * @return void
     */
    protected function loadConfigFromDatabase(): void
    {
        try {
            $pluginId = $this->getPluginId();
            if (empty($pluginId)) {
                return;
            }

            $pdo = Database::pdo();
            $prefix = Database::prefix();
            $stmt = $pdo->prepare("SELECT config_key, config_value FROM {$prefix}admin_plugin_configs WHERE plugin_id = ?");
            $stmt->execute([$pluginId]);
            $configs = $stmt->fetchAll();

            foreach ($configs as $config) {
                $this->config[$config['config_key']] = $config['config_value'];
            }
        } catch (\Exception $e) {
            error_log('加载插件配置失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取插件ID
     * @return string
     */
    protected function getPluginId(): string
    {
        $info = $this->getInfo();
        return $info['plugin_id'] ?? '';
    }

    /**
     * 更新配置
     * @param array $config
     * @return bool
     */
    public function updateConfig(array $config): bool
    {
        $this->config = array_merge($this->config, $config);
        return true;
    }

    /**
     * 获取配置表单
     * @return array
     */
    public function getConfigForm(): array
    {
        return [];
    }

    /**
     * 获取插件目录路径
     * @return string
     */
    protected function getPluginPath(): string
    {
        $reflection = new \ReflectionClass($this);
        $pluginFile = $reflection->getFileName();
        return dirname($pluginFile);
    }

    /**
     * 获取插件URL路径
     * @return string
     */
    protected function getPluginUrl(): string
    {
        $pluginPath = $this->getPluginPath();
        $publicPath = realpath(__DIR__ . '/../../public');
        
        // 统一使用正斜杠进行比较，兼容 Windows 和 Unix 系统
        $normalizedPluginPath = str_replace('\\', '/', $pluginPath);
        $normalizedPublicPath = $publicPath ? str_replace('\\', '/', $publicPath) : '';
        
        // 如果插件路径在 public 目录下，返回相对于 public 的路径
        if ($normalizedPublicPath && strpos($normalizedPluginPath, $normalizedPublicPath) === 0) {
            $relativePath = substr($normalizedPluginPath, strlen($normalizedPublicPath));
            // 移除开头的 /，因为网站根目录就是 public
            $relativePath = ltrim($relativePath, '/');
            return '/' . $relativePath;
        }
        
        // 如果无法确定相对路径，尝试从插件路径推断
        if (strpos($normalizedPluginPath, 'public/plugins') !== false) {
            $parts = preg_split('/public\/plugins/', $normalizedPluginPath);
            if (isset($parts[1])) {
                return '/plugins' . $parts[1];
            }
        }
        
        // 最后尝试：直接查找 plugins 目录
        if (strpos($normalizedPluginPath, '/plugins/') !== false) {
            $parts = preg_split('/\/plugins\//', $normalizedPluginPath);
            if (isset($parts[1])) {
                return '/plugins/' . $parts[1];
            }
        }
        
        return '';
    }

    /**
     * 获取插件资源URL（如图片、CSS等）
     * @param string $resourcePath 资源相对路径，如 'assets/logo.png'
     * @return string
     */
    protected function getPluginAssetUrl(string $resourcePath): string
    {
        $pluginUrl = $this->getPluginUrl();
        return rtrim($pluginUrl, '/') . '/' . ltrim($resourcePath, '/');
    }

    /**
     * 获取数据库连接
     * @return PDO
     */
    protected function getDatabase(): PDO
    {
        return Database::pdo();
    }

    /**
     * 获取表名（带前缀）
     * @param string $table
     * @return string
     */
    protected function getTableName(string $table): string
    {
        $prefix = Database::prefix();
        return $prefix . $table;
    }

    /**
     * 创建数据表
     * @param string $table
     * @param array $schema
     * @return bool
     */
    protected function createTable(string $table, array $schema): bool
    {
        try {
            $pdo = $this->getDatabase();
            $tableName = $this->getTableName($table);
            
            $columns = [];
            foreach ($schema['columns'] ?? [] as $col => $def) {
                $columns[] = "`{$col}` {$def}";
            }
            
            $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (" . implode(', ', $columns);
            
            if (!empty($schema['primary'])) {
                $sql .= ", PRIMARY KEY (`{$schema['primary']}`)";
            }
            
            if (!empty($schema['indexes'])) {
                foreach ($schema['indexes'] as $idx) {
                    $sql .= ", KEY `idx_{$idx}` (`{$idx}`)";
                }
            }
            
            if (!empty($schema['unique_indexes'])) {
                foreach ($schema['unique_indexes'] as $idx) {
                    $sql .= ", UNIQUE KEY `uniq_{$idx}` (`{$idx}`)";
                }
            }
            
            if (!empty($schema['composite_indexes'])) {
                foreach ($schema['composite_indexes'] as $name => $cols) {
                    $colsStr = implode('`, `', $cols);
                    $sql .= ", KEY `idx_{$name}` (`{$colsStr}`)";
                }
            }
            
            $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            return true;
        } catch (\Exception $e) {
            $this->log('error', '创建表失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 删除数据表
     * @param string $table
     * @return bool
     */
    protected function dropTable(string $table): bool
    {
        try {
            $pdo = $this->getDatabase();
            $tableName = $this->getTableName($table);
            $pdo->exec("DROP TABLE IF EXISTS `{$tableName}`");
            return true;
        } catch (\Exception $e) {
            $this->log('error', '删除表失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 记录日志
     * @param string $level
     * @param string $message
     * @return void
     */
    protected function log(string $level, string $message): void
    {
        // 简单的日志记录，可以扩展为写入文件或数据库
        error_log("[Plugin] [{$level}] {$message}");
    }
}
