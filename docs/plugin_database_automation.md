# 插件数据库自动化管理系统

## 概述

这个系统提供了一个完整的插件数据库自动化管理解决方案，能够一次性处理所有插件的数据库表安装、升级和维护，无需为每个插件单独更新数据库表。

## 系统架构

### 核心组件

1. **数据库表结构** (`database/migrations/003_add_plugin_management_tables.sql`)
   - `plugins` - 插件元数据表
   - `plugin_tables` - 插件数据库表注册表
   - `plugin_migrations` - 插件迁移记录表
   - `plugin_configs` - 插件配置表

2. **服务类**
   - `PluginMigrationService` - 插件数据库迁移管理
   - `PluginVersionService` - 插件版本管理
   - `PluginAutomationService` - 插件自动化处理
   - `PluginManager` - 更新的插件管理器

3. **命令行工具** (`scripts/plugin_automation.php`)
   - 提供完整的CLI接口进行插件管理

## 功能特性

### 1. 自动插件发现和注册
- 扫描所有插件目录（apps, email, payment, sms, verification, identity, notification）
- 自动读取插件配置文件
- 注册插件到统一管理表

### 2. 数据库表自动管理
- 自动执行插件的install.sql文件
- 支持前缀替换（__PREFIX__）
- 自动注册插件创建的表
- 智能SQL解析和执行

### 3. 版本控制和升级
- 检查插件版本差异
- 支持升级SQL文件
- 完整的迁移历史记录
- 回滚和修复功能

### 4. 自动化维护
- 一键安装所有插件数据库
- 批量升级插件
- 系统健康检查
- 无效表注册清理
- 自动备份功能

## 使用方法

### 命令行使用

```bash
# 安装所有插件数据库
php scripts/plugin_automation.php install

# 升级所有插件
php scripts/plugin_automation.php upgrade

# 修复所有插件
php scripts/plugin_automation.php repair

# 执行完整维护流程
php scripts/plugin_automation.php maintenance

# 生成数据库报告
php scripts/plugin_automation.php report

# 检查系统健康状态
php scripts/plugin_automation.php health

# 清理无效表注册
php scripts/plugin_automation.php cleanup

# 备份所有插件数据
php scripts/plugin_automation.php backup
```

### 编程接口使用

```php
use app\services\PluginAutomationService;

$automation = new PluginAutomationService();

// 一键安装所有插件
$result = $automation->autoInstallAllPlugins();

// 批量升级插件
$result = $automation->autoUpgradeAllPlugins();

// 完整维护流程
$result = $automation->performFullMaintenance();

// 生成报告
$report = $automation->generateDatabaseReport();
```

## 插件开发规范

### 插件配置文件 (plugin.json)

```json
{
    "plugin_id": "my_plugin",
    "name": "我的插件",
    "version": "1.0.0",
    "type": "app",
    "category": "content",
    "install_sql": "database/install.sql",
    "uninstall_sql": "database/uninstall.sql",
    "dependencies": [],
    "config": {}
}
```

### 数据库文件规范

1. **install.sql** - 插件安装时执行的SQL
   ```sql
   CREATE TABLE IF NOT EXISTS `__PREFIX__my_table` (
     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
     `name` varchar(255) NOT NULL,
     `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
     PRIMARY KEY (`id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
   ```

2. **upgrade.sql** - 插件升级时执行的SQL（可选）
3. **uninstall.sql** - 插件卸载时执行的SQL（可选）

### 支持的插件类型

- `apps` - 应用插件
- `email` - 邮件服务插件
- `payment` - 支付插件
- `sms` - 短信插件
- `verification` - 验证插件
- `identity` - 身份验证插件
- `notification` - 通知插件

## 系统优势

### 1. 自动化程度高
- 无需手动管理每个插件的数据库表
- 自动发现和注册新插件
- 智能版本控制和升级

### 2. 完整的版本管理
- 详细的迁移历史记录
- 支持回滚和修复
- 自动备份和恢复

### 3. 强大的维护功能
- 系统健康检查
- 无效表自动清理
- 批量操作支持

### 4. 灵活的扩展性
- 支持多种插件类型
- 可配置的SQL执行
- 完善的错误处理

## 数据库表说明

### plugins 表
存储所有插件的基本信息和配置

### plugin_tables 表
记录插件创建的所有数据库表，便于管理和追踪

### plugin_migrations 表
记录所有数据库迁移操作，提供完整的版本历史

### plugin_configs 表
存储插件的运行时配置，支持加密存储

## 安全特性

1. **SQL注入防护** - 使用预处理语句
2. **事务支持** - 确保数据一致性
3. **错误处理** - 完善的异常处理机制
4. **备份机制** - 自动备份重要数据

## 性能优化

1. **批量操作** - 减少数据库连接次数
2. **索引优化** - 合理的数据库索引设计
3. **缓存支持** - 插件配置缓存机制

## 故障排除

### 常见问题

1. **插件安装失败**
   - 检查SQL文件语法
   - 确认数据库权限
   - 查看错误日志

2. **版本升级问题**
   - 验证版本号格式
   - 检查升级SQL文件
   - 运行修复功能

3. **表注册不一致**
   - 运行清理命令
   - 检查手动修改
   - 重新扫描插件

### 日志查看

```bash
# 查看迁移历史
php scripts/plugin_automation.php report

# 检查系统健康
php scripts/plugin_automation.php health
```

## 最佳实践

1. **插件开发**
   - 遵循配置文件规范
   - 使用标准SQL语法
   - 提供完整的安装/卸载脚本

2. **系统维护**
   - 定期运行完整维护
   - 备份重要数据
   - 监控系统健康状态

3. **版本管理**
   - 使用语义化版本号
   - 提供升级脚本
   - 记录变更日志

## 扩展开发

系统采用模块化设计，可以轻松扩展：

1. **添加新的插件类型** - 在扫描逻辑中添加新目录
2. **自定义迁移逻辑** - 扩展迁移服务
3. **增强报告功能** - 添加新的统计维度
4. **集成外部系统** - 通过服务接口集成

这个系统彻底解决了插件数据库管理的复杂性，实现了真正的"一次性处理所有插件"的目标。