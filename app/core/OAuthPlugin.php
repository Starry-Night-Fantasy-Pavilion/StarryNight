<?php

declare(strict_types=1);

namespace Core;

/**
 * OAuth登录插件基类
 * 
 * 第三方登录插件必须继承此类
 */
abstract class OAuthPlugin extends Plugin
{
    /**
     * @var string 插件类型
     */
    protected string $type = 'oauth';

    /**
     * 获取插件元数据
     *
     * @return array<string, mixed>
     */
    abstract public static function meta(): array;

    /**
     * 获取配置表单
     *
     * @return array<string, array<string, mixed>>
     */
    abstract public static function config(): array;

    /**
     * 生成授权URL
     *
     * @param array $params 参数
     * @return string
     */
    abstract public static function url(array $params): string;

    /**
     * 处理回调
     *
     * @param array $params 回调参数
     * @return array<string, mixed>
     * @throws \Exception
     */
    abstract public static function callback(array $params): array;

    /**
     * 安装插件
     *
     * @return bool
     */
    public function install(): bool
    {
        // OAuth插件通常不需要创建数据库表
        return true;
    }

    /**
     * 卸载插件
     *
     * @return bool
     */
    public function uninstall(): bool
    {
        // OAuth插件通常不需要删除数据库表
        return true;
    }

    /**
     * 获取授权URL
     *
     * @param string $callback 回调地址
     * @param string $state 状态码
     * @return string
     */
    public function getAuthUrl(string $callback, string $state = ''): string
    {
        $config = $this->getConfig();
        $params = array_merge($config, [
            'callback' => $callback,
            'state' => $state ?: md5(uniqid(mt_rand(), true))
        ]);

        return static::url($params);
    }

    /**
     * 处理授权回调
     *
     * @param array $params 回调参数
     * @return array<string, mixed>
     */
    public function handleCallback(array $params): array
    {
        $config = $this->getConfig();
        $params = array_merge($config, $params);

        return static::callback($params);
    }
}
