<?php

namespace Core;

/**
 * 验证码插件基类
 */
abstract class VerificationPlugin extends PluginBase
{
    /**
     * 生成验证码
     * @param array $options
     * @return array
     */
    abstract public function generate(array $options = []): array;

    /**
     * 验证验证码
     * @param string $code
     * @param string $token
     * @return bool
     */
    abstract public function verify(string $code, string $token): bool;

    /**
     * 获取HTML组件
     * @param array $options
     * @return string
     */
    abstract public function getHtml(array $options = []): string;
}
