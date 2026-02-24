<?php

declare(strict_types=1);

namespace Core;

/**
 * 实名认证插件基类
 * 
 * 身份验证插件必须继承此类
 */
abstract class CertificationPlugin extends Plugin
{
    /**
     * @var string 插件类型
     */
    protected string $type = 'certification';

    /**
     * 个人认证
     *
     * @param array $certifi 认证信息
     * @return string HTML响应
     */
    abstract public function personal(array $certifi): string;

    /**
     * 企业认证
     *
     * @param array $certifi 认证信息
     * @return string HTML响应
     */
    abstract public function company(array $certifi): string;

    /**
     * 获取认证表单字段
     *
     * @return array<string, array<string, mixed>>
     */
    abstract public function collectionInfo(): array;

    /**
     * 查询认证状态
     *
     * @param array $certifi 认证信息
     * @return array<string, mixed>
     */
    abstract public function getStatus(array $certifi): array;

    /**
     * 安装插件
     *
     * @return bool
     */
    public function install(): bool
    {
        // 实名认证插件通常不需要创建数据库表
        return true;
    }

    /**
     * 卸载插件
     *
     * @return bool
     */
    public function uninstall(): bool
    {
        // 实名认证插件通常不需要删除数据库表
        return true;
    }

    /**
     * 更新认证状态
     *
     * @param array $data 状态数据
     * @return bool
     */
    protected function updateCertifiStatus(array $data): bool
    {
        // 子类可以实现具体的更新逻辑
        return true;
    }
}
