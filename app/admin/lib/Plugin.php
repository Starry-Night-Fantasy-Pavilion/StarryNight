<?php

namespace app\admin\lib;

use app\services\Database;

/**
 * 兼容旧架构的插件基类
 *
 * 许多旧版邮箱 / 短信 / 支付插件会 `extends \app\admin\lib\Plugin`，
 * 这里提供通用的日志、配置读取等能力，同时保持向后兼容。
 */
abstract class Plugin
{
    protected $config = [];

    /**
     * 获取插件信息
     * @return array
     */
    abstract public function getInfo(): array;

    /**
     * 安装插件（兼容旧架构，允许返回数组或其他类型）
     * @return mixed
     */
    public function install()
    {
        return true;
    }

    /**
     * 卸载插件（兼容旧架构，允许返回混合类型）
     * @return mixed
     */
    public function uninstall()
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
        return $info['plugin_id'] ?? $info['name'] ?? '';
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
}

