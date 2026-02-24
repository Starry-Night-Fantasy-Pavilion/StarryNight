# 智简魔方财务系统 - 迁移指南

## 目录
1. [迁移概述](#迁移概述)
2. [环境准备](#环境准备)
3. [数据迁移](#数据迁移)
4. [插件迁移](#插件迁移)
5. [主题迁移](#主题迁移)
6. [配置迁移](#配置迁移)
7. [验证与测试](#验证与测试)
8. [常见问题](#常见问题)

---

## 迁移概述

### 迁移目标

将智简魔方财务系统的插件架构和主题系统从现有环境迁移到新的项目环境中，确保功能完整性和数据一致性。

### 迁移范围

- 插件系统迁移
- 主题系统迁移
- 配置数据迁移
- 数据库结构迁移
- 静态资源迁移

### 迁移原则

1. **数据完整性**: 确保所有数据完整迁移
2. **功能一致性**: 保持功能逻辑不变
3. **最小停机**: 减少系统停机时间
4. **可回滚**: 支持迁移失败时的回滚
5. **充分测试**: 迁移前后进行充分测试

---

## 环境准备

### 源环境检查

```bash
#!/bin/bash

# 源环境检查脚本

echo "=== 智简魔方财务系统迁移 - 源环境检查 ==="
echo ""

# 检查PHP版本
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo "PHP版本: $PHP_VERSION"

# 检查MySQL版本
MYSQL_VERSION=$(mysql --version | awk '{print $5}')
echo "MySQL版本: $MYSQL_VERSION"

# 检查磁盘空间
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}')
echo "磁盘使用率: $DISK_USAGE"

# 检查插件目录
if [ -d "public/plugins" ]; then
    PLUGIN_COUNT=$(find public/plugins -type d -mindepth 1 | wc -l)
    echo "插件数量: $PLUGIN_COUNT"
else
    echo "警告: 插件目录不存在"
fi

# 检查主题目录
if [ -d "public/themes" ]; then
    THEME_COUNT=$(find public/themes -type d -mindepth 1 | wc -l)
    echo "主题数量: $THEME_COUNT"
else
    echo "警告: 主题目录不存在"
fi

# 检查数据库连接
DB_HOST=$(grep "hostname" config/database.php | awk -F"'" '{print $4}')
DB_NAME=$(grep "database" config/database.php | awk -F"'" '{print $4}')
DB_USER=$(grep "username" config/database.php | awk -F"'" '{print $4}')

echo "数据库配置:"
echo "  主机: $DB_HOST"
echo "  数据库: $DB_NAME"
echo "  用户: $DB_USER"

# 测试数据库连接
if mysql -h"$DB_HOST" -u"$DB_USER" -p"$(grep "password" config/database.php | awk -F"'" '{print $4}')" -e "USE $DB_NAME;" 2>/dev/null; then
    echo "数据库连接: 成功"
else
    echo "数据库连接: 失败"
fi

echo ""
echo "=== 检查完成 ==="
```

### 目标环境准备

```bash
#!/bin/bash

# 目标环境准备脚本

echo "=== 智简魔方财务系统迁移 - 目标环境准备 ==="
echo ""

# 创建项目目录
PROJECT_DIR="/path/to/new-project"
echo "创建项目目录: $PROJECT_DIR"
mkdir -p "$PROJECT_DIR"

# 创建必要的目录结构
echo "创建目录结构..."
mkdir -p "$PROJECT_DIR/app"
mkdir -p "$PROJECT_DIR/public"
mkdir -p "$PROJECT_DIR/config"
mkdir -p "$PROJECT_DIR/runtime"
mkdir -p "$PROJECT_DIR/data"

# 设置目录权限
echo "设置目录权限..."
chmod -R 755 "$PROJECT_DIR/app"
chmod -R 755 "$PROJECT_DIR/public"
chmod -R 777 "$PROJECT_DIR/runtime"
chmod -R 777 "$PROJECT_DIR/data"

# 创建数据库
echo "创建数据库..."
mysql -u root -p << EOF
CREATE DATABASE IF NOT EXISTS zjmfmanger_new CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON zjmfmanger_new.* TO 'zjmfmanger'@'localhost' IDENTIFIED BY 'your_password';
FLUSH PRIVILEGES;
EOF

# 复制配置文件
echo "复制配置文件..."
cp config/database.example.php "$PROJECT_DIR/config/database.php"

echo ""
echo "=== 准备完成 ==="
```

### 环境对比

| 检查项 | 源环境 | 目标环境 | 状态 |
|---------|---------|-----------|------|
| PHP版本 | 7.4 | 7.4+ | ✓ |
| MySQL版本 | 5.7 | 5.7+ | ✓ |
| 磁盘空间 | 50% | 20% | ✓ |
| 插件数量 | 15 | 0 | 需迁移 |
| 主题数量 | 3 | 0 | 需迁移 |
| 数据库连接 | 成功 | 成功 | ✓ |

---

## 数据迁移

### 数据库迁移

```php
<?php
// migration/DatabaseMigration.php

class DatabaseMigration
{
    private $sourceDb;
    private $targetDb;
    private $migrationLog = [];
    
    public function __construct($sourceConfig, $targetConfig)
    {
        $this->sourceDb = $this->connect($sourceConfig);
        $this->targetDb = $this->connect($targetConfig);
    }
    
    /**
     * 连接数据库
     */
    private function connect($config)
    {
        try {
            $dsn = "mysql:host={$config['hostname']};dbname={$config['database']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
    }
    
    /**
     * 执行迁移
     */
    public function migrate()
    {
        echo "开始数据库迁移...\n";
        
        // 迁移插件表
        $this->migratePluginTable();
        
        // 迁移主题表
        $this->migrateThemeTable();
        
        // 迁移配置表
        $this->migrateConfigTable();
        
        // 迁移用户数据
        $this->migrateUserData();
        
        // 迁移订单数据
        $this->migrateOrderData();
        
        echo "数据库迁移完成!\n";
        echo "迁移日志:\n";
        print_r($this->migrationLog);
    }
    
    /**
     * 迁移插件表
     */
    private function migratePluginTable()
    {
        echo "迁移插件表...\n";
        
        // 创建目标表
        $sql = "CREATE TABLE IF NOT EXISTS `plugin` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL COMMENT '插件名称',
            `type` varchar(50) NOT NULL COMMENT '插件类型',
            `title` varchar(100) NOT NULL COMMENT '插件标题',
            `description` text COMMENT '插件描述',
            `version` varchar(20) NOT NULL COMMENT '版本号',
            `author` varchar(100) DEFAULT NULL COMMENT '作者',
            `status` tinyint(1) DEFAULT '0' COMMENT '状态',
            `config` text COMMENT '配置信息',
            `install_time` int(11) DEFAULT NULL COMMENT '安装时间',
            `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`),
            KEY `type` (`type`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='插件表';";
        
        $this->targetDb->exec($sql);
        
        // 迁移数据
        $stmt = $this->sourceDb->query("SELECT * FROM plugin");
        $plugins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $insertSql = "INSERT INTO plugin (name, type, title, description, version, author, status, config, install_time, update_time) 
                      VALUES (:name, :type, :title, :description, :version, :author, :status, :config, :install_time, :update_time)";
        
        $insertStmt = $this->targetDb->prepare($insertSql);
        
        foreach ($plugins as $plugin) {
            try {
                $insertStmt->execute([
                    ':name' => $plugin['name'],
                    ':type' => $plugin['type'],
                    ':title' => $plugin['title'],
                    ':description' => $plugin['description'],
                    ':version' => $plugin['version'],
                    ':author' => $plugin['author'],
                    ':status' => $plugin['status'],
                    ':config' => $plugin['config'],
                    ':install_time' => $plugin['install_time'],
                    ':update_time' => $plugin['update_time']
                ]);
                
                $this->migrationLog[] = "插件迁移成功: {$plugin['name']}";
            } catch (Exception $e) {
                $this->migrationLog[] = "插件迁移失败: {$plugin['name']} - " . $e->getMessage();
            }
        }
        
        echo "插件表迁移完成，共迁移 " . count($plugins) . " 个插件\n";
    }
    
    /**
     * 迁移主题表
     */
    private function migrateThemeTable()
    {
        echo "迁移主题表...\n";
        
        // 创建目标表
        $sql = "CREATE TABLE IF NOT EXISTS `theme` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `type` varchar(50) NOT NULL COMMENT '主题类型',
            `name` varchar(100) NOT NULL COMMENT '主题名称',
            `title` varchar(100) NOT NULL COMMENT '主题标题',
            `description` text COMMENT '主题描述',
            `author` varchar(100) DEFAULT NULL COMMENT '作者',
            `version` varchar(20) NOT NULL COMMENT '版本号',
            `parent` varchar(100) DEFAULT NULL COMMENT '父主题',
            `screenshot` varchar(255) DEFAULT NULL COMMENT '截图',
            `status` tinyint(1) DEFAULT '0' COMMENT '状态',
            `config` text COMMENT '配置信息',
            `install_time` int(11) DEFAULT NULL COMMENT '安装时间',
            `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
            PRIMARY KEY (`id`),
            UNIQUE KEY `theme_type_name` (`type`, `name`),
            KEY `type` (`type`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='主题表';";
        
        $this->targetDb->exec($sql);
        
        // 迁移数据
        $stmt = $this->sourceDb->query("SELECT * FROM theme");
        $themes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $insertSql = "INSERT INTO theme (type, name, title, description, author, version, parent, screenshot, status, config, install_time, update_time) 
                      VALUES (:type, :name, :title, :description, :author, :version, :parent, :screenshot, :status, :config, :install_time, :update_time)";
        
        $insertStmt = $this->targetDb->prepare($insertSql);
        
        foreach ($themes as $theme) {
            try {
                $insertStmt->execute([
                    ':type' => $theme['type'],
                    ':name' => $theme['name'],
                    ':title' => $theme['title'],
                    ':description' => $theme['description'],
                    ':author' => $theme['author'],
                    ':version' => $theme['version'],
                    ':parent' => $theme['parent'],
                    ':screenshot' => $theme['screenshot'],
                    ':status' => $theme['status'],
                    ':config' => $theme['config'],
                    ':install_time' => $theme['install_time'],
                    ':update_time' => $theme['update_time']
                ]);
                
                $this->migrationLog[] = "主题迁移成功: {$theme['name']}";
            } catch (Exception $e) {
                $this->migrationLog[] = "主题迁移失败: {$theme['name']} - " . $e->getMessage();
            }
        }
        
        echo "主题表迁移完成，共迁移 " . count($themes) . " 个主题\n";
    }
    
    /**
     * 迁移配置表
     */
    private function migrateConfigTable()
    {
        echo "迁移配置表...\n";
        
        // 迁移系统配置
        $stmt = $this->sourceDb->query("SELECT * FROM config");
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($configs as $config) {
            try {
                $insertSql = "INSERT INTO config (name, value, type, description) 
                              VALUES (:name, :value, :type, :description)
                              ON DUPLICATE KEY UPDATE value = VALUES(value)";
                
                $insertStmt = $this->targetDb->prepare($insertSql);
                $insertStmt->execute([
                    ':name' => $config['name'],
                    ':value' => $config['value'],
                    ':type' => $config['type'],
                    ':description' => $config['description']
                ]);
                
                $this->migrationLog[] = "配置迁移成功: {$config['name']}";
            } catch (Exception $e) {
                $this->migrationLog[] = "配置迁移失败: {$config['name']} - " . $e->getMessage();
            }
        }
        
        echo "配置表迁移完成，共迁移 " . count($configs) . " 条配置\n";
    }
    
    /**
     * 迁移用户数据
     */
    private function migrateUserData()
    {
        echo "迁移用户数据...\n";
        
        // 迁移用户表
        $stmt = $this->sourceDb->query("SELECT * FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $user) {
            try {
                $insertSql = "INSERT INTO users (id, username, email, password, phone, status, create_time, update_time) 
                              VALUES (:id, :username, :email, :password, :phone, :status, :create_time, :update_time)
                              ON DUPLICATE KEY UPDATE 
                              username = VALUES(username),
                              email = VALUES(email),
                              password = VALUES(password),
                              phone = VALUES(phone),
                              status = VALUES(status)";
                
                $insertStmt = $this->targetDb->prepare($insertSql);
                $insertStmt->execute([
                    ':id' => $user['id'],
                    ':username' => $user['username'],
                    ':email' => $user['email'],
                    ':password' => $user['password'],
                    ':phone' => $user['phone'],
                    ':status' => $user['status'],
                    ':create_time' => $user['create_time'],
                    ':update_time' => $user['update_time']
                ]);
                
                $this->migrationLog[] = "用户迁移成功: {$user['username']}";
            } catch (Exception $e) {
                $this->migrationLog[] = "用户迁移失败: {$user['username']} - " . $e->getMessage();
            }
        }
        
        echo "用户数据迁移完成，共迁移 " . count($users) . " 个用户\n";
    }
    
    /**
     * 迁移订单数据
     */
    private function migrateOrderData()
    {
        echo "迁移订单数据...\n";
        
        // 迁移订单表
        $stmt = $this->sourceDb->query("SELECT * FROM orders");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($orders as $order) {
            try {
                $insertSql = "INSERT INTO orders (id, user_id, order_no, amount, status, create_time, update_time) 
                              VALUES (:id, :user_id, :order_no, :amount, :status, :create_time, :update_time)
                              ON DUPLICATE KEY UPDATE 
                              amount = VALUES(amount),
                              status = VALUES(status),
                              update_time = VALUES(update_time)";
                
                $insertStmt = $this->targetDb->prepare($insertSql);
                $insertStmt->execute([
                    ':id' => $order['id'],
                    ':user_id' => $order['user_id'],
                    ':order_no' => $order['order_no'],
                    ':amount' => $order['amount'],
                    ':status' => $order['status'],
                    ':create_time' => $order['create_time'],
                    ':update_time' => $order['update_time']
                ]);
                
                $this->migrationLog[] = "订单迁移成功: {$order['order_no']}";
            } catch (Exception $e) {
                $this->migrationLog[] = "订单迁移失败: {$order['order_no']} - " . $e->getMessage();
            }
        }
        
        echo "订单数据迁移完成，共迁移 " . count($orders) . " 个订单\n";
    }
}

// 使用示例
$sourceConfig = [
    'hostname' => 'localhost',
    'database' => 'zjmfmanger',
    'username' => 'root',
    'password' => 'password'
];

$targetConfig = [
    'hostname' => 'localhost',
    'database' => 'zjmfmanger_new',
    'username' => 'root',
    'password' => 'password'
];

$migration = new DatabaseMigration($sourceConfig, $targetConfig);
$migration->migrate();
```

---

## 插件迁移

### 插件文件迁移

```bash
#!/bin/bash

# 插件文件迁移脚本

SOURCE_DIR="/path/to/old-project/public/plugins"
TARGET_DIR="/path/to/new-project/public/plugins"

echo "=== 智简魔方财务系统迁移 - 插件文件迁移 ==="
echo ""

# 创建目标目录
mkdir -p "$TARGET_DIR"

# 迁移所有插件
for plugin_type in oauth gateways certification addons; do
    if [ -d "$SOURCE_DIR/$plugin_type" ]; then
        echo "迁移 $plugin_type 插件..."
        
        # 创建插件类型目录
        mkdir -p "$TARGET_DIR/$plugin_type"
        
        # 复制所有插件
        for plugin in "$SOURCE_DIR/$plugin_type"/*; do
            if [ -d "$plugin" ]; then
                plugin_name=$(basename "$plugin")
                echo "  迁移插件: $plugin_name"
                cp -r "$plugin" "$TARGET_DIR/$plugin_type/"
            fi
        done
    fi
done

# 迁移插件配置
if [ -d "data/plugin_configs" ]; then
    echo "迁移插件配置..."
    mkdir -p "$TARGET_DIR/../data/plugin_configs"
    cp -r data/plugin_configs/* "$TARGET_DIR/../data/plugin_configs/"
fi

# 迁移插件缓存
if [ -d "runtime/plugin_cache" ]; then
    echo "迁移插件缓存..."
    mkdir -p "$TARGET_DIR/../runtime/plugin_cache"
    cp -r runtime/plugin_cache/* "$TARGET_DIR/../runtime/plugin_cache/"
fi

echo ""
echo "=== 插件文件迁移完成 ==="
```

### 插件配置迁移

```php
<?php
// migration/PluginConfigMigration.php

class PluginConfigMigration
{
    private $sourceConfigDir;
    private $targetConfigDir;
    private $migrationLog = [];
    
    public function __construct($sourceConfigDir, $targetConfigDir)
    {
        $this->sourceConfigDir = $sourceConfigDir;
        $this->targetConfigDir = $targetConfigDir;
        
        if (!is_dir($this->targetConfigDir)) {
            mkdir($this->targetConfigDir, 0755, true);
        }
    }
    
    /**
     * 执行迁移
     */
    public function migrate()
    {
        echo "开始插件配置迁移...\n";
        
        $configFiles = glob($this->sourceConfigDir . '*.json');
        
        foreach ($configFiles as $configFile) {
            $configName = basename($configFile, '.json');
            $this->migrateConfig($configName, $configFile);
        }
        
        echo "插件配置迁移完成!\n";
        echo "迁移日志:\n";
        print_r($this->migrationLog);
    }
    
    /**
     * 迁移单个配置
     */
    private function migrateConfig($configName, $configFile)
    {
        echo "迁移配置: $configName\n";
        
        try {
            // 读取源配置
            $sourceConfig = json_decode(file_get_contents($configFile), true);
            
            if (!$sourceConfig) {
                throw new Exception("配置文件格式错误");
            }
            
            // 转换配置格式
            $targetConfig = $this->transformConfig($sourceConfig);
            
            // 保存目标配置
            $targetConfigFile = $this->targetConfigDir . $configName . '.json';
            file_put_contents($targetConfigFile, json_encode($targetConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            $this->migrationLog[] = "配置迁移成功: $configName";
        } catch (Exception $e) {
            $this->migrationLog[] = "配置迁移失败: $configName - " . $e->getMessage();
        }
    }
    
    /**
     * 转换配置格式
     */
    private function transformConfig($sourceConfig)
    {
        $targetConfig = [];
        
        foreach ($sourceConfig as $key => $value) {
            $newKey = $this->transformConfigKey($key);
            $newValue = $this->transformConfigValue($value);
            $targetConfig[$newKey] = $newValue;
        }
        
        return $targetConfig;
    }
    
    /**
     * 转换配置键
     */
    private function transformConfigKey($key)
    {
        $keyMap = [
            'app_id' => 'appId',
            'app_secret' => 'appSecret',
            'merchant_key' => 'merchantKey',
            'notify_url' => 'notifyUrl',
            'return_url' => 'returnUrl',
            'api_url' => 'apiUrl',
            'access_key' => 'accessKey',
            'secret_key' => 'secretKey'
        ];
        
        return $keyMap[$key] ?? $key;
    }
    
    /**
     * 转换配置值
     */
    private function transformConfigValue($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'transformConfigValue'], $value);
        }
        
        if (is_string($value)) {
            return trim($value);
        }
        
        return $value;
    }
}

// 使用示例
$sourceConfigDir = '/path/to/old-project/data/plugin_configs/';
$targetConfigDir = '/path/to/new-project/data/plugin_configs/';

$migration = new PluginConfigMigration($sourceConfigDir, $targetConfigDir);
$migration->migrate();
```

---

## 主题迁移

### 主题文件迁移

```bash
#!/bin/bash

# 主题文件迁移脚本

SOURCE_DIR="/path/to/old-project/public/themes"
TARGET_DIR="/path/to/new-project/public/themes"

echo "=== 智简魔方财务系统迁移 - 主题文件迁移 ==="
echo ""

# 创建目标目录
mkdir -p "$TARGET_DIR"

# 迁移所有主题
for theme_type in cart clientarea web; do
    if [ -d "$SOURCE_DIR/$theme_type" ]; then
        echo "迁移 $theme_type 主题..."
        
        # 创建主题类型目录
        mkdir -p "$TARGET_DIR/$theme_type"
        
        # 复制所有主题
        for theme in "$SOURCE_DIR/$theme_type"/*; do
            if [ -d "$theme" ]; then
                theme_name=$(basename "$theme")
                echo "  迁移主题: $theme_name"
                cp -r "$theme" "$TARGET_DIR/$theme_type/"
            fi
        done
    fi
done

# 迁移主题配置
if [ -d "data/theme_configs" ]; then
    echo "迁移主题配置..."
    mkdir -p "$TARGET_DIR/../data/theme_configs"
    cp -r data/theme_configs/* "$TARGET_DIR/../data/theme_configs/"
fi

# 迁移主题缓存
if [ -d "runtime/theme_cache" ]; then
    echo "迁移主题缓存..."
    mkdir -p "$TARGET_DIR/../runtime/theme_cache"
    cp -r runtime/theme_cache/* "$TARGET_DIR/../runtime/theme_cache/"
fi

echo ""
echo "=== 主题文件迁移完成 ==="
```

### 主题配置迁移

```php
<?php
// migration/ThemeConfigMigration.php

class ThemeConfigMigration
{
    private $sourceConfigDir;
    private $targetConfigDir;
    private $migrationLog = [];
    
    public function __construct($sourceConfigDir, $targetConfigDir)
    {
        $this->sourceConfigDir = $sourceConfigDir;
        $this->targetConfigDir = $targetConfigDir;
        
        if (!is_dir($this->targetConfigDir)) {
            mkdir($this->targetConfigDir, 0755, true);
        }
    }
    
    /**
     * 执行迁移
     */
    public function migrate()
    {
        echo "开始主题配置迁移...\n";
        
        $configFiles = glob($this->sourceConfigDir . '*.json');
        
        foreach ($configFiles as $configFile) {
            $configName = basename($configFile, '.json');
            $this->migrateConfig($configName, $configFile);
        }
        
        echo "主题配置迁移完成!\n";
        echo "迁移日志:\n";
        print_r($this->migrationLog);
    }
    
    /**
     * 迁移单个配置
     */
    private function migrateConfig($configName, $configFile)
    {
        echo "迁移配置: $configName\n";
        
        try {
            // 读取源配置
            $sourceConfig = json_decode(file_get_contents($configFile), true);
            
            if (!$sourceConfig) {
                throw new Exception("配置文件格式错误");
            }
            
            // 转换配置格式
            $targetConfig = $this->transformConfig($sourceConfig);
            
            // 保存目标配置
            $targetConfigFile = $this->targetConfigDir . $configName . '.json';
            file_put_contents($targetConfigFile, json_encode($targetConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            $this->migrationLog[] = "配置迁移成功: $configName";
        } catch (Exception $e) {
            $this->migrationLog[] = "配置迁移失败: $configName - " . $e->getMessage();
        }
    }
    
    /**
     * 转换配置格式
     */
    private function transformConfig($sourceConfig)
    {
        $targetConfig = [];
        
        foreach ($sourceConfig as $key => $value) {
            $newKey = $this->transformConfigKey($key);
            $newValue = $this->transformConfigValue($value);
            $targetConfig[$newKey] = $newValue;
        }
        
        return $targetConfig;
    }
    
    /**
     * 转换配置键
     */
    private function transformConfigKey($key)
    {
        $keyMap = [
            'theme_name' => 'name',
            'theme_title' => 'title',
            'theme_desc' => 'description',
            'theme_author' => 'author',
            'theme_version' => 'version',
            'parent_theme' => 'parent',
            'theme_screenshot' => 'screenshot'
        ];
        
        return $keyMap[$key] ?? $key;
    }
    
    /**
     * 转换配置值
     */
    private function transformConfigValue($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'transformConfigValue'], $value);
        }
        
        if (is_string($value)) {
            return trim($value);
        }
        
        return $value;
    }
}

// 使用示例
$sourceConfigDir = '/path/to/old-project/data/theme_configs/';
$targetConfigDir = '/path/to/new-project/data/theme_configs/';

$migration = new ThemeConfigMigration($sourceConfigDir, $targetConfigDir);
$migration->migrate();
```

---

## 配置迁移

### 系统配置迁移

```php
<?php
// migration/SystemConfigMigration.php

class SystemConfigMigration
{
    private $sourceDb;
    private $targetDb;
    private $migrationLog = [];
    
    public function __construct($sourceConfig, $targetConfig)
    {
        $this->sourceDb = $this->connect($sourceConfig);
        $this->targetDb = $this->connect($targetConfig);
    }
    
    /**
     * 连接数据库
     */
    private function connect($config)
    {
        try {
            $dsn = "mysql:host={$config['hostname']};dbname={$config['database']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
    }
    
    /**
     * 执行迁移
     */
    public function migrate()
    {
        echo "开始系统配置迁移...\n";
        
        // 迁移站点配置
        $this->migrateSiteConfig();
        
        // 迁移邮件配置
        $this->migrateEmailConfig();
        
        // 迁移支付配置
        $this->migratePaymentConfig();
        
        // 迁移安全配置
        $this->migrateSecurityConfig();
        
        echo "系统配置迁移完成!\n";
        echo "迁移日志:\n";
        print_r($this->migrationLog);
    }
    
    /**
     * 迁移站点配置
     */
    private function migrateSiteConfig()
    {
        echo "迁移站点配置...\n";
        
        $siteConfigs = [
            'site_name',
            'site_url',
            'site_logo',
            'site_description',
            'site_keywords',
            'site_icp',
            'site_copyright'
        ];
        
        foreach ($siteConfigs as $configName) {
            try {
                $stmt = $this->sourceDb->prepare("SELECT * FROM config WHERE name = ?");
                $stmt->execute([$configName]);
                $config = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($config) {
                    $insertSql = "INSERT INTO config (name, value, type, description) 
                                  VALUES (:name, :value, :type, :description)
                                  ON DUPLICATE KEY UPDATE value = VALUES(value)";
                    
                    $insertStmt = $this->targetDb->prepare($insertSql);
                    $insertStmt->execute([
                        ':name' => $config['name'],
                        ':value' => $config['value'],
                        ':type' => $config['type'],
                        ':description' => $config['description']
                    ]);
                    
                    $this->migrationLog[] = "站点配置迁移成功: {$config['name']}";
                }
            } catch (Exception $e) {
                $this->migrationLog[] = "站点配置迁移失败: {$configName} - " . $e->getMessage();
            }
        }
    }
    
    /**
     * 迁移邮件配置
     */
    private function migrateEmailConfig()
    {
        echo "迁移邮件配置...\n";
        
        $emailConfigs = [
            'email_host',
            'email_port',
            'email_username',
            'email_password',
            'email_from',
            'email_from_name',
            'email_encryption'
        ];
        
        foreach ($emailConfigs as $configName) {
            try {
                $stmt = $this->sourceDb->prepare("SELECT * FROM config WHERE name = ?");
                $stmt->execute([$configName]);
                $config = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($config) {
                    $insertSql = "INSERT INTO config (name, value, type, description) 
                                  VALUES (:name, :value, :type, :description)
                                  ON DUPLICATE KEY UPDATE value = VALUES(value)";
                    
                    $insertStmt = $this->targetDb->prepare($insertSql);
                    $insertStmt->execute([
                        ':name' => $config['name'],
                        ':value' => $config['value'],
                        ':type' => $config['type'],
                        ':description' => $config['description']
                    ]);
                    
                    $this->migrationLog[] = "邮件配置迁移成功: {$config['name']}";
                }
            } catch (Exception $e) {
                $this->migrationLog[] = "邮件配置迁移失败: {$configName} - " . $e->getMessage();
            }
        }
    }
    
    /**
     * 迁移支付配置
     */
    private function migratePaymentConfig()
    {
        echo "迁移支付配置...\n";
        
        $paymentConfigs = [
            'payment_default',
            'payment_currency',
            'payment_timeout'
        ];
        
        foreach ($paymentConfigs as $configName) {
            try {
                $stmt = $this->sourceDb->prepare("SELECT * FROM config WHERE name = ?");
                $stmt->execute([$configName]);
                $config = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($config) {
                    $insertSql = "INSERT INTO config (name, value, type, description) 
                                  VALUES (:name, :value, :type, :description)
                                  ON DUPLICATE KEY UPDATE value = VALUES(value)";
                    
                    $insertStmt = $this->targetDb->prepare($insertSql);
                    $insertStmt->execute([
                        ':name' => $config['name'],
                        ':value' => $config['value'],
                        ':type' => $config['type'],
                        ':description' => $config['description']
                    ]);
                    
                    $this->migrationLog[] = "支付配置迁移成功: {$config['name']}";
                }
            } catch (Exception $e) {
                $this->migrationLog[] = "支付配置迁移失败: {$configName} - " . $e->getMessage();
            }
        }
    }
    
    /**
     * 迁移安全配置
     */
    private function migrateSecurityConfig()
    {
        echo "迁移安全配置...\n";
        
        $securityConfigs = [
            'security_login_attempts',
            'security_login_lockout',
            'security_password_min_length',
            'security_password_complexity',
            'security_session_timeout'
        ];
        
        foreach ($securityConfigs as $configName) {
            try {
                $stmt = $this->sourceDb->prepare("SELECT * FROM config WHERE name = ?");
                $stmt->execute([$configName]);
                $config = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($config) {
                    $insertSql = "INSERT INTO config (name, value, type, description) 
                                  VALUES (:name, :value, :type, :description)
                                  ON DUPLICATE KEY UPDATE value = VALUES(value)";
                    
                    $insertStmt = $this->targetDb->prepare($insertSql);
                    $insertStmt->execute([
                        ':name' => $config['name'],
                        ':value' => $config['value'],
                        ':type' => $config['type'],
                        ':description' => $config['description']
                    ]);
                    
                    $this->migrationLog[] = "安全配置迁移成功: {$config['name']}";
                }
            } catch (Exception $e) {
                $this->migrationLog[] = "安全配置迁移失败: {$configName} - " . $e->getMessage();
            }
        }
    }
}

// 使用示例
$sourceConfig = [
    'hostname' => 'localhost',
    'database' => 'zjmfmanger',
    'username' => 'root',
    'password' => 'password'
];

$targetConfig = [
    'hostname' => 'localhost',
    'database' => 'zjmfmanger_new',
    'username' => 'root',
    'password' => 'password'
];

$migration = new SystemConfigMigration($sourceConfig, $targetConfig);
$migration->migrate();
```

---

## 验证与测试

### 功能验证清单

#### 插件功能验证

- [ ] OAuth登录插件正常工作
- [ ] 支付网关插件正常工作
- [ ] 实名认证插件正常工作
- [ ] 功能扩展插件正常工作
- [ ] 插件配置保存/读取正常
- [ ] 插件安装/卸载功能正常
- [ ] 插件激活/停用功能正常

#### 主题功能验证

- [ ] 主题切换功能正常
- [ ] 主题继承机制正常
- [ ] 模板渲染正常
- [ ] 样式加载正常
- [ ] JavaScript功能正常
- [ ] 响应式设计正常
- [ ] 语言包加载正常

#### 系统功能验证

- [ ] 用户登录/注册正常
- [ ] 订单创建/支付正常
- [ ] 账单生成/支付正常
- [ ] 数据查询正常
- [ ] 权限控制正常
- [ ] 日志记录正常

### 性能验证

```php
<?php
// migration/PerformanceTest.php

class PerformanceTest
{
    private $baseUrl;
    
    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }
    
    /**
     * 执行性能测试
     */
    public function runTests()
    {
        echo "=== 性能测试 ===\n";
        
        $this->testPageLoadTime();
        $this->testApiPerformance();
        $this->testDatabasePerformance();
        $this->testPluginPerformance();
        $this->testThemePerformance();
    }
    
    /**
     * 测试页面加载时间
     */
    private function testPageLoadTime()
    {
        echo "\n页面加载时间测试:\n";
        
        $pages = [
            '/' => '首页',
            '/clientarea' => '客户端首页',
            '/cart' => '购物车',
            '/web/about' => '关于页面'
        ];
        
        foreach ($pages as $url => $name) {
            $startTime = microtime(true);
            $response = $this->httpGet($this->baseUrl . $url);
            $endTime = microtime(true);
            $loadTime = ($endTime - $startTime) * 1000;
            
            echo "  {$name}: {$loadTime}ms\n";
        }
    }
    
    /**
     * 测试API性能
     */
    private function testApiPerformance()
    {
        echo "\nAPI性能测试:\n";
        
        $apis = [
            '/api/plugins' => '插件列表API',
            '/api/themes' => '主题列表API',
            '/api/user/info' => '用户信息API'
        ];
        
        foreach ($apis as $url => $name) {
            $startTime = microtime(true);
            $response = $this->httpGet($this->baseUrl . $url);
            $endTime = microtime(true);
            $loadTime = ($endTime - $startTime) * 1000;
            
            echo "  {$name}: {$loadTime}ms\n";
        }
    }
    
    /**
     * 测试数据库性能
     */
    private function testDatabasePerformance()
    {
        echo "\n数据库性能测试:\n";
        
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=zjmfmanger_new', 'root', 'password');
            
            // 测试查询性能
            $startTime = microtime(true);
            $stmt = $pdo->query("SELECT * FROM users LIMIT 100");
            $users = $stmt->fetchAll();
            $endTime = microtime(true);
            $queryTime = ($endTime - $startTime) * 1000;
            
            echo "  用户查询: {$queryTime}ms\n";
            
            // 测试插入性能
            $startTime = microtime(true);
            $stmt = $pdo->prepare("INSERT INTO test_table (name, value) VALUES (?, ?)");
            for ($i = 0; $i < 100; $i++) {
                $stmt->execute(["test_{$i}", "value_{$i}"]);
            }
            $endTime = microtime(true);
            $insertTime = ($endTime - $startTime) * 1000;
            
            echo "  批量插入: {$insertTime}ms\n";
            
            // 清理测试数据
            $pdo->exec("DELETE FROM test_table WHERE name LIKE 'test_%'");
            
        } catch (PDOException $e) {
            echo "  数据库测试失败: {$e->getMessage()}\n";
        }
    }
    
    /**
     * 测试插件性能
     */
    private function testPluginPerformance()
    {
        echo "\n插件性能测试:\n";
        
        // 测试插件加载时间
        $startTime = microtime(true);
        $pluginManager = new \app\admin\lib\PluginManager();
        $plugins = $pluginManager->discoverAll();
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000;
        
        echo "  插件发现: {$loadTime}ms\n";
        echo "  插件数量: " . count($plugins) . "\n";
    }
    
    /**
     * 测试主题性能
     */
    private function testThemePerformance()
    {
        echo "\n主题性能测试:\n";
        
        // 测试主题加载时间
        $startTime = microtime(true);
        $themeManager = new \app\common\lib\ThemeManager();
        $themes = $themeManager->getAllThemes();
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000;
        
        echo "  主题发现: {$loadTime}ms\n";
        echo "  主题数量: " . count($themes) . "\n";
    }
    
    /**
     * HTTP GET请求
     */
    private function httpGet($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}

// 使用示例
$baseUrl = 'http://localhost';
$test = new PerformanceTest($baseUrl);
$test->runTests();
```

---

## 常见问题

### 问题1: 插件加载失败

**症状**: 插件无法正常加载，报错"Plugin class not found"

**原因**: 命名空间不匹配或文件路径错误

**解决方案**:
```php
// 检查插件文件路径和命名空间
$pluginPath = 'public/plugins/oauth/weixin/weixin.php';
$namespace = 'oauth\weixin';
$className = 'Weixin';

// 确保文件路径、命名空间和类名一致
```

### 问题2: 主题继承不生效

**症状**: 子主题无法继承父主题的模板

**原因**: 继承配置错误或循环继承

**解决方案**:
```php
// 检查继承配置
$themeConfig = parseThemeConfig($themePath);

if (isset($themeConfig['parent'])) {
    $parentTheme = $themeConfig['parent'];
    
    // 检查循环继承
    if ($themeManager->hasCircularInheritance($type, $themeName)) {
        throw new Exception('存在循环继承');
    }
}
```

### 问题3: 数据迁移失败

**症状**: 数据迁移过程中出现错误

**原因**: 数据库连接失败或数据格式不兼容

**解决方案**:
```php
// 检查数据库连接
try {
    $pdo = new PDO($dsn, $username, $password);
    echo "数据库连接成功\n";
} catch (PDOException $e) {
    echo "数据库连接失败: " . $e->getMessage() . "\n";
}

// 使用事务确保数据一致性
try {
    $pdo->beginTransaction();
    
    // 执行迁移操作
    
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    echo "迁移失败，已回滚: " . $e->getMessage() . "\n";
}
```

### 问题4: 性能下降

**症状**: 迁移后系统性能明显下降

**原因**: 缓存未清除或索引未优化

**解决方案**:
```bash
# 清除缓存
rm -rf runtime/cache/*
rm -rf runtime/template_cache/*
rm -rf runtime/plugin_cache/*

# 优化数据库表
mysql -u root -p -e "OPTIMIZE TABLE zjmfmanger_new.*;"

# 重建索引
mysql -u root -p -e "ANALYZE TABLE zjmfmanger_new.*;"
```

---

## 总结

本迁移指南提供了完整的迁移流程，从环境准备到验证测试的全过程。

### 迁移步骤总结

1. **环境准备**: 检查源环境和准备目标环境
2. **数据迁移**: 迁移数据库数据和配置
3. **插件迁移**: 迁移插件文件和配置
4. **主题迁移**: 迁移主题文件和配置
5. **配置迁移**: 迁移系统配置
6. **验证测试**: 验证功能和性能

### 最佳实践

1. **充分备份**: 迁移前做好完整备份
2. **分步迁移**: 按步骤逐步迁移
3. **充分测试**: 每步迁移后进行测试
4. **监控日志**: 密切关注迁移日志
5. **准备回滚**: 准备回滚方案

### 注意事项

1. 确保网络连接稳定
2. 确保磁盘空间充足
3. 确保数据库连接正常
4. 确保文件权限正确
5. 确保服务正常运行

通过本指南的指导，可以安全、高效地完成智简魔方财务系统的迁移工作。
